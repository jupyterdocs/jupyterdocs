<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Jobs\ConvertResourceToPdf;
use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\ResourceVote;
use App\Models\Tag;
use App\Models\University;
use App\Support\OfficeDocumentInspector;
use App\Support\TagGenerator;
use App\Support\ThumbnailStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::approved()
            ->search($request->query('q'))
            ->when($request->query('type'), fn ($q, $typeId) => $q->where('resource_type_id', $typeId))
            ->with(['uploader', 'resourceType', 'course', 'university'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $resourceTypes = ResourceType::orderBy('name')->get();

        $savedIds = $request->user()
            ? $request->user()->savedResources()->whereIn('resources.id', $resources->pluck('id'))->pluck('resources.id')->all()
            : [];

        return view('resources.index', [
            'resources' => $resources,
            'savedIds' => $savedIds,
            'resourceTypes' => $resourceTypes,
            'q' => $request->query('q'),
            'selectedType' => $request->query('type'),
        ]);
    }

    public function show(Resource $resource)
    {
        abort_unless(
            $resource->isViewableBy(auth()->user(), session('guest_uploads', [])),
            404
        );

        $resource->load(['uploader', 'resourceType', 'course', 'university', 'tags'])
            ->loadCount([
                'votes as likes_count' => fn ($q) => $q->where('value', ResourceVote::LIKE),
                'votes as dislikes_count' => fn ($q) => $q->where('value', ResourceVote::DISLIKE),
            ]);

        $user = auth()->user();
        $userVote = $user ? ResourceVote::where('user_id', $user->id)->where('resource_id', $resource->id)->value('value') : null;

        $related = Resource::approved()
            ->where('id', '!=', $resource->id)
            ->where(function ($q) use ($resource) {
                $q->where('resource_type_id', $resource->resource_type_id);

                if ($resource->course_id) {
                    $q->orWhere('course_id', $resource->course_id);
                }
            })
            ->with(['resourceType'])
            ->latest()
            ->limit(6)
            ->get();

        return view('resources.show', [
            'resource' => $resource,
            'canDownload' => $resource->isDownloadableBy(auth()->user()),
            'canPreview' => $resource->isPreviewableBy(auth()->user()),
            'related' => $related,
            'canInteract' => $resource->isInteractableBy($user),
            'isSaved' => $user ? $user->savedResources()->whereKey($resource->id)->exists() : false,
            'userVote' => match ($userVote) {
                ResourceVote::LIKE => 'like',
                ResourceVote::DISLIKE => 'dislike',
                default => null,
            },
            'hasReported' => $user ? $resource->reports()->where('user_id', $user->id)->where('status', 'open')->exists() : false,
            'votes' => $resource->voteSummary(),
        ]);
    }

    public function read(Resource $resource)
    {
        abort_unless(
            $resource->isViewableBy(auth()->user(), session('guest_uploads', [])),
            404
        );

        return $this->streamInline($resource);
    }

    public function preview(Resource $resource)
    {
        abort_unless($resource->isPreviewableBy(auth()->user()), 403);

        return view('resources.preview', ['resource' => $resource]);
    }

    /**
     * Serves whichever file actually has a PDF preview: the original file
     * for native PDFs, or the CloudConvert/Gotenberg-converted copy for
     * everything else. resources.read always points here so pdf.js never
     * needs to know which one it's getting.
     */
    private function streamInline(Resource $resource)
    {
        $path = $resource->previewSourcePath();
        $disk = Storage::disk(config('filesystems.resource_disk'));

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response(
            $path,
            $resource->title.'.pdf',
            ['Content-Disposition' => 'inline; filename="'.$resource->title.'.pdf"']
        );
    }

    public function create()
    {
        return view('resources.create', [
            'resourceTypes' => ResourceType::orderBy('name')->get(),
        ]);
    }

    public function store(StoreResourceRequest $request)
    {
        $validated = $request->validated();

        $file = $request->file('file');
        $format = $file->getClientOriginalExtension();

        $thumbnailPath = ThumbnailStorage::storeFromDataUrl($validated['thumbnail_data'] ?? null);
        $pages = $validated['pages'] ?? null;

        // PDF thumbnails/page counts come from the browser (pdf.js) above.
        // PowerPoint/Word/Excel files are zip archives that often embed a
        // ready-made thumbnail and, for pptx/docx, their true page count —
        // pull those out server-side when the client didn't already send one.
        // This runs against the upload's own local temp path (always a real
        // filesystem path, regardless of which disk the file ends up on)
        // BEFORE storing, so it works whether resource_disk is local or R2.
        if (in_array($format, ['pptx', 'docx', 'xlsx'], true)) {
            $tempPath = $file->getRealPath();

            if (! $thumbnailPath && $binary = OfficeDocumentInspector::extractThumbnail($tempPath)) {
                $thumbnailPath = ThumbnailStorage::storeFromBinary($binary);
            }

            if (! $pages) {
                $pages = OfficeDocumentInspector::extractPageCount($tempPath, $format);
            }
        }

        $path = $file->store('resources', config('filesystems.resource_disk'));

        if ($path === false) {
            return back()
                ->withErrors(['file' => 'We could not save your file right now. Please try again in a moment.'])
                ->withInput();
        }

        $resource = Resource::create([
            'uploader_id' => auth()->id(),
            'uploader_name' => auth()->guest() ? $validated['uploader_name'] : null,
            'uploader_email' => auth()->guest() ? ($validated['uploader_email'] ?? null) : null,
            'resource_type_id' => $validated['resource_type_id'],
            'university_id' => $this->firstOrCreateByName(University::class, $validated['university'] ?? null)?->id,
            'course_id' => $this->firstOrCreateByName(Course::class, $validated['course'] ?? null)?->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'file_size' => $file->getSize(),
            'format' => $format,
            'pages' => $pages,
        ]);

        // Full page-by-page preview (not just a thumbnail) needs real PDF
        // conversion — queued so it never blocks the upload response.
        if (in_array($format, config('conversion.formats'), true)) {
            $resource->conversion_status = 'pending';
            $resource->save();

            ConvertResourceToPdf::dispatch($resource->id);
        }

        $tagNames = TagGenerator::generate($validated['title'], $validated['description']);

        $tagIds = collect($tagNames)->map(fn ($name) => Tag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        )->id);

        $resource->tags()->sync($tagIds);

        if (auth()->guest()) {
            session()->push('guest_uploads', $resource->id);

            return redirect()->route('resources.show', $resource)
                ->with('status', 'Uploaded! It\'ll appear once an admin reviews it. Create an account to track your uploads and unlock downloads.');
        }

        return redirect()->route('resources.mine')
            ->with('status', 'Uploaded! It already counts toward unlocking your downloads — no need to wait for admin approval. It\'ll appear in search once an admin reviews it.');
    }

    public function mine()
    {
        $resources = auth()->user()->resources()
            ->with(['resourceType'])
            ->latest()
            ->paginate(15);

        return view('resources.mine', ['resources' => $resources]);
    }

    public function destroy(Resource $resource)
    {
        $resource->delete();

        return redirect()->route('resources.index')->with('status', 'Resource deleted.');
    }

    private function firstOrCreateByName(string $modelClass, ?string $name)
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $modelClass::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );
    }
}

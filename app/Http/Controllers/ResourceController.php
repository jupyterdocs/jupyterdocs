<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Tag;
use App\Models\University;
use App\Support\OfficeDocumentInspector;
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

        return view('resources.index', [
            'resources' => $resources,
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

        $resource->load(['uploader', 'resourceType', 'course', 'university', 'tags']);

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

    private function streamInline(Resource $resource)
    {
        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->response(
            $resource->file_path,
            $resource->title.'.'.$resource->format,
            ['Content-Disposition' => 'inline; filename="'.$resource->title.'.'.$resource->format.'"']
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
        $path = $file->store('resources', 'local');
        $format = $file->getClientOriginalExtension();

        $thumbnailPath = ThumbnailStorage::storeFromDataUrl($validated['thumbnail_data'] ?? null);
        $pages = $validated['pages'] ?? null;

        // PDF thumbnails/page counts come from the browser (pdf.js) above.
        // PowerPoint/Word/Excel files are zip archives that often embed a
        // ready-made thumbnail and, for pptx/docx, their true page count —
        // pull those out server-side when the client didn't already send one.
        if (in_array($format, ['pptx', 'docx', 'xlsx'], true)) {
            $absolutePath = Storage::disk('local')->path($path);

            if (! $thumbnailPath && $binary = OfficeDocumentInspector::extractThumbnail($absolutePath)) {
                $thumbnailPath = ThumbnailStorage::storeFromBinary($binary);
            }

            if (! $pages) {
                $pages = OfficeDocumentInspector::extractPageCount($absolutePath, $format);
            }
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

        if (! empty($validated['tags'])) {
            $tagIds = collect(explode(',', $validated['tags']))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->map(fn ($name) => Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id);

            $resource->tags()->sync($tagIds);
        }

        if (auth()->guest()) {
            session()->push('guest_uploads', $resource->id);

            return redirect()->route('resources.show', $resource)
                ->with('status', 'Uploaded! It will appear once an admin approves it. Create an account to track your uploads and unlock downloads.');
        }

        return redirect()->route('resources.mine')
            ->with('status', 'Uploaded! It will appear once an admin approves it.');
    }

    public function mine()
    {
        $resources = auth()->user()->resources()
            ->with(['resourceType'])
            ->latest()
            ->paginate(15);

        return view('resources.mine', ['resources' => $resources]);
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

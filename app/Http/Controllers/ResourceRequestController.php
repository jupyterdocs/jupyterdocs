<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ResourceRequest;
use App\Models\ResourceRequestVote;
use App\Models\ResourceType;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResourceRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $requests = ResourceRequest::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->withUpvoteCount()
            ->with(['user', 'resourceType', 'course', 'university', 'fulfilledResource'])
            ->orderByDesc('upvotes_count')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $votedIds = $request->user()
            ? $request->user()->resourceRequestVotes()->whereIn('resource_request_id', $requests->pluck('id'))->pluck('resource_request_id')->all()
            : [];

        return view('resources.requests.index', [
            'requests' => $requests,
            'status' => $status,
            'votedIds' => $votedIds,
        ]);
    }

    public function create()
    {
        return view('resources.requests.create', [
            'resourceTypes' => ResourceType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'resource_type_id' => ['nullable', 'exists:resource_types,id'],
            'course' => ['nullable', 'string', 'max:255'],
            'university' => ['nullable', 'string', 'max:255'],
        ]);

        $resourceRequest = ResourceRequest::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'resource_type_id' => $validated['resource_type_id'] ?? null,
            'course_id' => $this->firstOrCreateByName(Course::class, $validated['course'] ?? null)?->id,
            'university_id' => $this->firstOrCreateByName(University::class, $validated['university'] ?? null)?->id,
        ]);

        // Requesting it is the same as wanting it — count the requester's own upvote.
        ResourceRequestVote::create([
            'user_id' => $request->user()->id,
            'resource_request_id' => $resourceRequest->id,
        ]);

        return redirect()->route('requests.index')->with('status', 'Thanks — your request is up. Others can now upvote it.');
    }

    public function upvote(Request $request, ResourceRequest $resourceRequest)
    {
        abort_unless($resourceRequest->status === 'open', 404);

        $user = $request->user();

        $existing = ResourceRequestVote::where('user_id', $user->id)->where('resource_request_id', $resourceRequest->id)->first();

        if ($existing) {
            $existing->delete();
            $upvoted = false;
        } else {
            ResourceRequestVote::create(['user_id' => $user->id, 'resource_request_id' => $resourceRequest->id]);
            $upvoted = true;
        }

        $message = $upvoted ? 'Upvoted.' : 'Upvote removed.';

        if ($request->expectsJson()) {
            return response()->json([
                'upvoted' => $upvoted,
                'upvotes_count' => $resourceRequest->votes()->count(),
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
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

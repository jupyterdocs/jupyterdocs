<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResourceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $requests = ResourceRequest::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->withUpvoteCount()
            ->with(['user', 'reviewer', 'resourceType', 'course', 'university'])
            ->orderByDesc('upvotes_count')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.requests.index', [
            'requests' => $requests,
            'status' => $status,
            'openCount' => ResourceRequest::open()->count(),
        ]);
    }

    public function update(Request $request, ResourceRequest $resourceRequest)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['fulfilled', 'declined'])],
        ]);

        $resourceRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Request marked as '.$validated['status'].'.');
    }
}

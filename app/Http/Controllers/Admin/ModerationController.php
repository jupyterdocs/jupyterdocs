<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index()
    {
        $resources = Resource::where('status', 'pending')
            ->with(['uploader', 'resourceType', 'course', 'university'])
            ->oldest()
            ->paginate(15);

        return view('admin.moderation.index', ['resources' => $resources]);
    }

    public function approve(Resource $resource)
    {
        $resource->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Resource approved.');
    }

    public function reject(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:255'],
        ]);

        $resource->update([
            'status' => 'rejected',
            'rejected_reason' => $validated['rejected_reason'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Resource rejected.');
    }
}

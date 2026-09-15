<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceReport;
use App\Models\ResourceVote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceInteractionController extends Controller
{
    /**
     * Toggle a bookmark so the user can come back to the document later.
     * Saving never downloads the file.
     */
    public function toggleSave(Request $request, Resource $resource)
    {
        $user = $request->user();
        abort_unless($resource->isInteractableBy($user), 404);

        $saved = $user->savedResources()->toggle($resource->id)['attached'] !== [];

        return $this->respond($request, [
            'saved' => $saved,
        ], $saved ? 'Saved for later.' : 'Removed from saved.');
    }

    /**
     * Like or dislike. Clicking the vote you already cast removes it;
     * clicking the other one switches it.
     */
    public function vote(Request $request, Resource $resource)
    {
        $user = $request->user();
        abort_unless($resource->isInteractableBy($user), 404);

        $validated = $request->validate([
            'vote' => ['required', Rule::in(['like', 'dislike'])],
        ]);

        $value = $validated['vote'] === 'like' ? ResourceVote::LIKE : ResourceVote::DISLIKE;
        $existing = ResourceVote::where('user_id', $user->id)->where('resource_id', $resource->id)->first();

        if ($existing && $existing->value === $value) {
            $existing->delete();
            $current = null;
        } else {
            ResourceVote::updateOrCreate(
                ['user_id' => $user->id, 'resource_id' => $resource->id],
                ['value' => $value]
            );
            $current = $validated['vote'];
        }

        return $this->respond($request, [
            'vote' => $current,
            ...$resource->voteSummary(),
        ], $current ? 'Thanks for your feedback.' : 'Vote removed.');
    }

    public function report(Request $request, Resource $resource)
    {
        $user = $request->user();
        abort_unless($resource->isInteractableBy($user), 404);

        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(ResourceReport::REASONS))],
            'details' => ['nullable', 'string', 'max:1000', Rule::requiredIf($request->input('reason') === 'other')],
        ]);

        // One report per user per document; re-reporting reopens and updates it.
        ResourceReport::updateOrCreate(
            ['user_id' => $user->id, 'resource_id' => $resource->id],
            [
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'status' => 'open',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return $this->respond($request, [
            'reported' => true,
        ], 'Thanks — an admin will review your report.');
    }

    public function saved(Request $request)
    {
        $resources = $request->user()->savedResources()
            ->approved()
            ->with(['uploader', 'resourceType'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('resources.saved', ['resources' => $resources]);
    }

    private function respond(Request $request, array $payload, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([...$payload, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}

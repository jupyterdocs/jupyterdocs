<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function store(Resource $resource)
    {
        $user = auth()->user();

        abort_unless($resource->isDownloadableBy($user), 403, sprintf(
            'You have no downloads left. Upload %d more document(s) to earn another.',
            $user->uploadsNeededToUnlockDownloads()
        ));

        $disk = Storage::disk(config('filesystems.resource_disk'));

        abort_unless($disk->exists($resource->file_path), 404, 'File is missing.');

        $this->spendDownloadCredit($user, $resource);

        $download = Download::firstOrNew([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
        ]);

        if (! $download->exists) {
            $download->downloaded_at = now();
            $download->save();
            $resource->increment('downloads_count');
        }

        return $disk->download(
            $resource->file_path,
            $resource->title.'.'.$resource->format
        );
    }

    /**
     * Uploaders, admins and repeat downloads cost nothing; everything else
     * spends one download credit.
     */
    private function spendDownloadCredit(User $user, Resource $resource): void
    {
        if ($user->id === $resource->uploader_id
            || $user->isAdmin()
            || $user->downloads()->where('resource_id', $resource->id)->exists()) {
            return;
        }

        // Conditional update (used <= floor(uploads/2)) so simultaneous requests can't overspend.
        $spent = User::whereKey($user->id)
            ->whereRaw('downloads_used * ? <= uploads_count', [Resource::UPLOADS_PER_DOWNLOAD])
            ->increment('downloads_used');

        abort_unless($spent, 403, 'You have no downloads left.');
    }
}

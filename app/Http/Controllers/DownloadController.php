<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function store(Resource $resource)
    {
        $user = auth()->user();

        abort_unless($resource->isDownloadableBy($user), 403, sprintf(
            'You need %d more approved upload(s) before you can download resources.',
            $user->uploadsNeededToUnlockDownloads()
        ));

        $download = Download::firstOrNew([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
        ]);

        if (! $download->exists) {
            $download->downloaded_at = now();
            $download->save();
            $resource->increment('downloads_count');
        }

        $disk = Storage::disk(config('filesystems.resource_disk'));

        abort_unless($disk->exists($resource->file_path), 404, 'File is missing.');

        return $disk->download(
            $resource->file_path,
            $resource->title.'.'.$resource->format
        );
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThumbnailStorage
{
    private const MAX_WIDTH = 400;

    public static function storeFromDataUrl(?string $dataUrl): ?string
    {
        if (! $dataUrl || ! str_contains($dataUrl, ',')) {
            return null;
        }

        [, $encoded] = explode(',', $dataUrl, 2);
        $binary = base64_decode($encoded, true);

        return $binary === false ? null : self::storeFromBinary($binary);
    }

    public static function storeFromBinary(string $binary): ?string
    {
        // Never trust client- or archive-supplied "image" bytes directly:
        // decode through GD and re-encode, which discards anything that
        // isn't a real raster image.
        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_WIDTH) {
            $newHeight = (int) round($height * (self::MAX_WIDTH / $width));
            $resized = imagecreatetruecolor(self::MAX_WIDTH, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, self::MAX_WIDTH, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $path = 'thumbnails/'.Str::random(40).'.jpg';

        ob_start();
        imagejpeg($image, null, 80);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $jpeg);

        return $path;
    }
}

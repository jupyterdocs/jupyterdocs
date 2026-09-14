<?php

namespace App\Support;

use ZipArchive;

/**
 * PowerPoint, Word, and Excel files are just zip archives (OOXML). When a
 * document was saved with "Save thumbnail" on (PowerPoint does this by
 * default), the zip embeds a ready-made preview image we can lift directly
 * instead of rendering the document ourselves.
 */
class OfficeDocumentInspector
{
    private const THUMBNAIL_ENTRIES = [
        'docProps/thumbnail.jpeg',
        'docProps/thumbnail.jpg',
        'docProps/thumbnail.png',
    ];

    public static function extractThumbnail(string $absolutePath): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            return null;
        }

        $binary = null;

        foreach (self::THUMBNAIL_ENTRIES as $entry) {
            $data = $zip->getFromName($entry);

            if ($data !== false) {
                $binary = $data;
                break;
            }
        }

        $zip->close();

        return $binary ?: null;
    }

    public static function extractPageCount(string $absolutePath, string $format): ?int
    {
        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            return null;
        }

        $count = match ($format) {
            'pptx' => self::countSlides($zip),
            'docx' => self::countWordPages($zip),
            default => null,
        };

        $zip->close();

        return $count;
    }

    private static function countSlides(ZipArchive $zip): ?int
    {
        $count = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (preg_match('#^ppt/slides/slide\d+\.xml$#', $zip->getNameIndex($i))) {
                $count++;
            }
        }

        return $count > 0 ? $count : null;
    }

    private static function countWordPages(ZipArchive $zip): ?int
    {
        // Word writes the page count it last calculated into docProps/app.xml;
        // it's an estimate (not recomputed until the file is opened in Word),
        // but it's the only page count available without rendering the file.
        $xml = $zip->getFromName('docProps/app.xml');

        if ($xml === false) {
            return null;
        }

        if (preg_match('#<Pages>(\d+)</Pages>#', $xml, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}

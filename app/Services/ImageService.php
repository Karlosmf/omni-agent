<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageService
{
    /**
     * Encode an uploaded file as WebP and persist it to the given disk folder.
     *
     * @return string Relative path stored on the disk (e.g. "ideas/abc123.webp")
     */
    public static function storeWebp(UploadedFile $file, string $folder, int $quality = 90, string $disk = 'uploads'): string
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->decode($file->getRealPath());
        $encoded = $image->encode(new WebpEncoder($quality));
        $filename = Str::random(40).'.webp';

        Storage::disk($disk)->put("{$folder}/{$filename}", (string) $encoded);

        return "{$folder}/{$filename}";
    }
}

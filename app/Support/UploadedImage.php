<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gambar disimpan di database sebagai path relatif terhadap folder public:
 * - gambar bawaan seeder: "images/recipes/x.jpg"
 * - hasil unggahan:       "storage/recipes/x.jpg" (butuh `php artisan storage:link`)
 */
class UploadedImage
{
    private const PREFIX = 'storage/';

    public static function store(UploadedFile $file, string $folder): string
    {
        return self::PREFIX.$file->store($folder, 'public');
    }

    /**
     * Hanya menghapus file hasil unggahan, bukan gambar bawaan.
     */
    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::PREFIX)) {
            Storage::disk('public')->delete(substr($path, strlen(self::PREFIX)));
        }
    }
}

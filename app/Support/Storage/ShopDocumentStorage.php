<?php

namespace App\Support\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopDocumentStorage
{
    public const DISK = 'local';

    private const PREFIX = 'private/shops';

    public static function store(UploadedFile $file, int $userId, string $type): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $directory = self::PREFIX.'/'.$userId.'/'.$type;

        Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);

        return $directory.'/'.$filename;
    }

    public static function delete(?string $path): void
    {
        if (! self::isPrivatePath($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public static function isPrivatePath(?string $path): bool
    {
        return filled($path) && str_starts_with($path, self::PREFIX.'/');
    }

    public static function documentTypeFromPath(?string $path): ?string
    {
        if (! self::isPrivatePath($path)) {
            return null;
        }

        if (str_contains($path, '/id_front/')) {
            return 'id_front';
        }

        if (str_contains($path, '/id_back/')) {
            return 'id_back';
        }

        return null;
    }
}

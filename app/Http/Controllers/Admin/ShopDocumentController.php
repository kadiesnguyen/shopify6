<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Storage\ShopDocumentStorage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShopDocumentController extends Controller
{
    public function show(User $user, string $document): BinaryFileResponse
    {
        abort_unless(in_array($document, ['id_front', 'id_back'], true), 404);

        $shop = $user->shop;
        abort_unless($shop, 404);

        $path = $shop->{$document};
        abort_unless(ShopDocumentStorage::isPrivatePath($path), 404);
        abort_unless(Storage::disk(ShopDocumentStorage::DISK)->exists($path), 404);

        $absolutePath = Storage::disk(ShopDocumentStorage::DISK)->path($path);

        return response()->file($absolutePath, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}

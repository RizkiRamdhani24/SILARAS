<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EbookCoverStorageService
{
    private const URL_PROBE = '__silaras_ebook_cover_url_probe__';

    public function store(UploadedFile $cover): string
    {
        $path = $cover->storePublicly('ebook/cover', 's3');

        if (! $path) {
            throw new RuntimeException('Gagal menyimpan cover Ebook ke storage.');
        }

        return Storage::disk('s3')->url($path);
    }

    public function delete(?string $url): void
    {
        if (! $url) {
            return;
        }

        $disk = Storage::disk('s3');
        $probeUrl = $disk->url(self::URL_PROBE);
        $probeSuffix = '/'.self::URL_PROBE;

        if (! str_ends_with($probeUrl, $probeSuffix)) {
            throw new RuntimeException('URL publik storage tidak dapat dipetakan ke lokasi file cover.');
        }

        $baseUrl = substr($probeUrl, 0, -strlen($probeSuffix));
        $prefix = rtrim($baseUrl, '/').'/';

        if (! str_starts_with($url, $prefix)) {
            throw new RuntimeException('URL cover Ebook tidak berasal dari disk storage yang dikonfigurasi.');
        }

        $path = rawurldecode(substr($url, strlen($prefix)));

        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Gagal menghapus cover Ebook dari storage.');
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        foreach ($public->allFiles('buku/pdf') as $path) {
            if ($private->exists($path)) {
                if (! $this->matches($public->path($path), $private->path($path))) {
                    throw new RuntimeException(
                        "PDF buku {$path} sudah ada di private storage dengan isi yang berbeda."
                    );
                }

                if (! $public->delete($path)) {
                    throw new RuntimeException("PDF buku publik {$path} gagal dipindahkan.");
                }

                continue;
            }

            $stream = $public->readStream($path);

            if (! is_resource($stream)) {
                throw new RuntimeException("PDF buku publik {$path} gagal dibaca.");
            }

            try {
                $stored = $private->put($path, $stream);
            } finally {
                fclose($stream);
            }

            if (
                ! $stored
                || ! $private->exists($path)
                || ! $this->matches($public->path($path), $private->path($path))
            ) {
                $private->delete($path);

                throw new RuntimeException("PDF buku {$path} gagal disalin dengan utuh.");
            }

            if (! $public->delete($path)) {
                throw new RuntimeException("PDF buku publik {$path} gagal dipindahkan.");
            }
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'PDF buku tidak dipindahkan kembali ke public storage agar aksesnya tetap terlindungi.'
        );
    }

    private function matches(string $publicPath, string $privatePath): bool
    {
        $publicHash = hash_file('sha256', $publicPath);
        $privateHash = hash_file('sha256', $privatePath);

        return $publicHash !== false
            && $privateHash !== false
            && hash_equals($publicHash, $privateHash);
    }
};

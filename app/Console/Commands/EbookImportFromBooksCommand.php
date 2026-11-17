<?php

namespace App\Console\Commands;

use App\Models\Ebook;
use App\Models\Peminjaman;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EbookImportFromBooksCommand extends Command
{
    protected $signature = 'ebook:impor-dari-buku
        {--tautan= : Jalur CSV UTF-8 dengan kolom judul,link_drive}
        {--pemetaan= : Jalur berkas pemetaan JSON di luar repo}
        {--jalankan : Jalankan impor setelah validasi}
        {--urungkan : Batalkan impor berdasarkan berkas pemetaan}';

    protected $description = 'Impor buku digital lama menjadi Ebook (default hanya dry-run).';

    public function handle(): int
    {
        if ($this->option('urungkan')) {
            if ($this->option('jalankan') || $this->option('tautan')) {
                $this->error('Gunakan --urungkan tanpa --jalankan atau --tautan.');

                return self::FAILURE;
            }

            return $this->undoImport();
        }

        $csvPath = (string) $this->option('tautan');
        if ($csvPath === '') {
            $this->error('Opsi --tautan wajib diisi.');

            return self::FAILURE;
        }

        try {
            $csvPath = $this->resolveExistingPath($csvPath);
            $this->assertOutsideRepository($csvPath);
            $rows = $this->readCsv($csvPath);
            $mappingPath = $this->option('pemetaan')
                ? $this->resolveMappingPath((string) $this->option('pemetaan'))
                : null;
            $existingMappings = $mappingPath && is_file($mappingPath)
                ? $this->readMapping($mappingPath)
                : [];
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $availableBooks = DB::table('buku')
            ->where('diarsipkan', false)
            ->orderBy('id_buku')
            ->get([
                'id_buku',
                'judul_buku',
                'pengarang',
                'cover',
                'pdf',
            ]);

        $csvByTitle = [];
        foreach ($rows as $row) {
            $csvByTitle[$this->normalizeTitle($row['judul'])][] = $row;
        }

        $booksByTitle = [];
        foreach ($availableBooks as $book) {
            $booksByTitle[$this->normalizeTitle($book->judul_buku)][] = $book;
        }

        $mappedBooks = $this->mappedBooks($existingMappings);
        $mappedByTitle = [];
        foreach ($mappedBooks as $mappedBook) {
            $mappedByTitle[$this->normalizeTitle($mappedBook['judul'])][] = $mappedBook;
        }

        $mismatches = [];
        $candidateBooks = [];
        $alreadyImported = [];
        foreach ($csvByTitle as $title => $matchingRows) {
            if (count($matchingRows) > 1) {
                $mismatches[] = 'Judul ganda di CSV: '.$matchingRows[0]['judul'];

            }
            $matchingBooks = $booksByTitle[$title] ?? [];
            if (count($matchingBooks) > 1) {
                $mismatches[] = 'Judul buku cocok dengan lebih dari satu baris buku: '.$matchingRows[0]['judul'];

                continue;
            }
            if (count($matchingBooks) === 1) {
                $candidateBooks[$matchingBooks[0]->id_buku] = $matchingBooks[0];

                continue;
            }

            $mapped = $mappedByTitle[$title] ?? [];
            if (
                count($matchingRows) === 1
                && count($mapped) === 1
                && $mapped[0]['link_drive'] === $matchingRows[0]['link_drive']
            ) {
                $alreadyImported[] = $mapped[0];
            } else {
                $mismatches[] = 'Judul CSV tidak ditemukan pada satu buku aktif: '.$matchingRows[0]['judul'];
            }
        }

        $books = collect($candidateBooks)->sortBy('id_buku')->values();
        $otherPdfBooks = DB::table('buku')
            ->whereNotNull('pdf')
            ->where('pdf', '<>', '')
            ->orderBy('id_buku')
            ->get(['id_buku', 'judul_buku'])
            ->filter(fn ($book): bool => ! isset($csvByTitle[$this->normalizeTitle($book->judul_buku)]))
            ->values();

        $effectiveCount = $books->count() + count($alreadyImported);
        if ($effectiveCount !== count($rows)) {
            $mismatches[] = "Jumlah tidak sama: kandidat {$books->count()} + sudah diimpor ".count($alreadyImported).', CSV '.count($rows).'.';
        }

        try {
            $loanInfo = $this->loanInformation($books->pluck('id_buku')->all());
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Mode: '.($this->option('jalankan') ? 'JALANKAN' : 'DRY-RUN'));
        $this->line('Kandidat baru: '.$books->count().'; sudah diimpor: '.count($alreadyImported).'; baris CSV: '.count($rows).'.');
        if ($otherPdfBooks->isNotEmpty()) {
            $this->line('PDF terisi di luar CSV (informasi; tidak disentuh):');
            $this->table(
                ['ID buku', 'Judul'],
                $otherPdfBooks->map(fn ($book): array => [$book->id_buku, $book->judul_buku])->all()
            );
        }
        if ($books->isEmpty()) {
            $this->line('Tidak ada buku digital baru untuk diimpor.');
        } else {
            $this->table(
                ['ID buku', 'Judul', 'Penulis', 'Pencocokan CSV', 'Peminjaman aktif', 'Riwayat'],
                $books->map(function ($book) use ($loanInfo) {
                    $loans = $loanInfo[$book->id_buku] ?? ['aktif' => 0, 'riwayat' => 0];

                    return [
                        $book->id_buku,
                        $book->judul_buku,
                        $book->pengarang,
                        'cocok',
                        $loans['aktif'],
                        $loans['riwayat'],
                    ];
                })->all()
            );
        }

        if ($mismatches !== []) {
            $this->error('Impor ditolak karena judul/jumlah CSV tidak cocok atau ganda:');
            foreach (array_unique($mismatches) as $mismatch) {
                $this->line('- '.$mismatch);
            }

            return self::FAILURE;
        }

        $activeBooks = array_filter(
            $loanInfo,
            static fn (array $info): bool => $info['aktif'] > 0
        );
        if ($activeBooks !== []) {
            $this->error('Impor ditolak: terdapat peminjaman aktif pada kandidat buku.');

            return self::FAILURE;
        }

        if (! $this->option('jalankan')) {
            $this->info('Dry-run selesai; database dan storage tidak diubah.');

            return self::SUCCESS;
        }

        if ($books->isEmpty()) {
            $this->info('Tidak ada perubahan; impor sebelumnya sudah selesai (idempoten).');

            return self::SUCCESS;
        }

        if ($mappingPath === null) {
            $this->error('Opsi --pemetaan wajib diisi bersama --jalankan.');

            return self::FAILURE;
        }
        if (is_file($mappingPath)) {
            $this->error('Berkas --pemetaan sudah ada; gunakan jalur baru atau --urungkan terlebih dahulu.');

            return self::FAILURE;
        }

        $rowsByTitle = [];
        foreach ($rows as $row) {
            $rowsByTitle[$this->normalizeTitle($row['judul'])] = $row;
        }
        $copiedCovers = [];
        $mapping = [];

        try {
            DB::transaction(function () use ($books, $rowsByTitle, &$copiedCovers, &$mapping, $mappingPath): void {
                foreach ($books as $book) {
                    $csvRow = $rowsByTitle[$this->normalizeTitle($book->judul_buku)];
                    $coverCountBeforeCopy = count($copiedCovers);
                    $coverUrl = $this->copyCover($book->cover, $copiedCovers);
                    $ebook = Ebook::query()->create([
                        'judul' => $book->judul_buku,
                        'penulis' => $book->pengarang,
                        'cover' => $coverUrl,
                        'link_drive' => $csvRow['link_drive'],
                    ]);

                    DB::table('buku')
                        ->where('id_buku', $book->id_buku)
                        ->update(['diarsipkan' => true, 'updated_at' => now()]);

                    $mapping[] = [
                        'id_buku' => (int) $book->id_buku,
                        'id_ebook' => (int) $ebook->getKey(),
                        'judul' => $book->judul_buku,
                        'link_drive' => $csvRow['link_drive'],
                        'cover_path' => $copiedCovers[$coverCountBeforeCopy] ?? null,
                    ];
                }

                $this->writeMapping($mappingPath, $mapping);
            });
        } catch (Throwable $exception) {
            foreach ($copiedCovers as $path) {
                if (Storage::disk('s3')->exists($path) && ! Storage::disk('s3')->delete($path)) {
                    $this->warn('Cover impor gagal dibersihkan setelah rollback: '.$path);
                }
            }
            if (is_file($mappingPath)) {
                unlink($mappingPath);
            }
            $this->error('Impor gagal dan transaksi dibatalkan: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Impor selesai: '.count($mapping).' ebook dibuat dan buku lama diarsipkan.');
        foreach ($copiedCovers as $path) {
            $this->line('Cover disalin: '.$path);
        }

        return self::SUCCESS;
    }

    private function undoImport(): int
    {
        $path = (string) $this->option('pemetaan');
        if ($path === '') {
            $this->error('Opsi --pemetaan wajib diisi bersama --urungkan.');

            return self::FAILURE;
        }

        try {
            $path = $this->resolveExistingPath($path);
            $this->assertOutsideRepository($path);
            $mappings = $this->readMapping($path);
            $importedCoverPaths = [];
            DB::transaction(function () use ($mappings, &$importedCoverPaths): void {
                foreach ($mappings as $row) {
                    $ebook = Ebook::query()->find($row['id_ebook']);
                    if ($ebook) {
                        $coverPath = $row['cover_path'] ?? null;
                        if (is_string($coverPath) && str_starts_with($coverPath, 'ebook/cover/')) {
                            $importedCoverPaths[] = $coverPath;
                        }

                        DB::table('unduhan_ebook')
                            ->where('ebook_id', $ebook->getKey())
                            ->update([
                                'judul_ebook' => $ebook->judul,
                                'penulis_ebook' => $ebook->penulis,
                                'ebook_id' => null,
                            ]);
                        $ebook->delete();
                    }

                    DB::table('buku')
                        ->where('id_buku', $row['id_buku'])
                        ->update(['diarsipkan' => false, 'updated_at' => now()]);
                }
            });
            foreach ($importedCoverPaths as $coverPath) {
                if (Storage::disk('s3')->exists($coverPath) && ! Storage::disk('s3')->delete($coverPath)) {
                    $this->warn('Cover impor tidak dapat dihapus saat undo: '.$coverPath);
                }
            }
        } catch (Throwable $exception) {
            $this->error('Undo gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! unlink($path)) {
            $this->error('Undo database berhasil, tetapi berkas pemetaan tidak dapat dihapus: '.$path);

            return self::FAILURE;
        }

        $this->info('Undo selesai: '.count($mappings).' ebook dihapus dan buku lama dikembalikan.');

        return self::SUCCESS;
    }

    private function readCsv(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false || preg_match('//u', $contents) !== 1) {
            throw new RuntimeException('Berkas tautan tidak terbaca atau bukan UTF-8.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Berkas tautan tidak dapat dibuka.');
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            if (! is_array($header)) {
                throw new RuntimeException('CSV kosong atau tidak memiliki header.');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $header = array_map(static fn ($value): string => trim((string) $value), $header);
            if ($header !== ['judul', 'link_drive']) {
                throw new RuntimeException('Header CSV harus tepat: judul,link_drive.');
            }

            $rows = [];
            while (($fields = fgetcsv($handle, null, ',', '"', '')) !== false) {
                if ($fields === [null] || $fields === []) {
                    continue;
                }
                if (count($fields) !== 2) {
                    throw new RuntimeException('Setiap baris CSV harus memiliki tepat dua kolom.');
                }

                $title = trim((string) $fields[0]);
                $link = trim((string) $fields[1]);
                if ($title === '' || $link === '') {
                    throw new RuntimeException('Kolom judul dan link_drive tidak boleh kosong.');
                }

                $rows[] = ['judul' => $title, 'link_drive' => $link];
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function loanInformation(array $bookIds): array
    {
        if ($bookIds === []) {
            return [];
        }
        if (! Schema::hasTable('peminjaman')) {
            throw new RuntimeException('Tabel peminjaman tidak ditemukan; pemeriksaan riwayat tidak dapat dilakukan.');
        }

        $loans = DB::table('peminjaman')
            ->whereIn('id_buku', $bookIds)
            ->get(['id_buku', 'status_peminjaman']);
        $information = [];
        foreach ($bookIds as $bookId) {
            $information[$bookId] = ['aktif' => 0, 'riwayat' => 0];
        }
        foreach ($loans as $loan) {
            $information[$loan->id_buku]['riwayat']++;
            if (in_array($loan->status_peminjaman, Peminjaman::STATUS_AKTIF, true)) {
                $information[$loan->id_buku]['aktif']++;
            }
        }

        return $information;
    }

    private function copyCover(?string $cover, array &$copiedCovers): ?string
    {
        if (! $cover) {
            return null;
        }

        $disk = Storage::disk('s3');
        try {
            $source = $this->storageKey($cover);
            $extension = pathinfo($source, PATHINFO_EXTENSION);
            $destination = 'ebook/cover/'.Str::uuid().($extension !== '' ? '.'.$extension : '');
            if (! $disk->exists($source) || ! $disk->copy($source, $destination)) {
                $this->warn("Cover gagal disalin; URL lama dipakai: {$cover}");

                return $this->coverUrl($cover);
            }
        } catch (Throwable $exception) {
            $this->warn("Cover gagal disalin ({$exception->getMessage()}); URL lama dipakai: {$cover}");

            return $this->coverUrl($cover);
        }

        $copiedCovers[] = $destination;

        return $disk->url($destination);
    }

    private function storageKey(string $cover): string
    {
        if (! str_contains($cover, '://')) {
            return ltrim($cover, '/');
        }

        $disk = Storage::disk('s3');
        $probe = '__silaras_ebook_import_probe__';
        $probeUrl = $disk->url($probe);
        $suffix = '/'.$probe;
        if (! str_ends_with($probeUrl, $suffix)) {
            throw new RuntimeException('URL cover tidak dapat dipetakan ke disk s3.');
        }

        $prefix = substr($probeUrl, 0, -strlen($suffix)).'/';
        if (! str_starts_with($cover, $prefix)) {
            throw new RuntimeException('URL cover lama bukan berasal dari disk s3.');
        }

        return rawurldecode(substr($cover, strlen($prefix)));
    }

    private function coverUrl(string $cover): string
    {
        return str_contains($cover, '://') ? $cover : Storage::disk('s3')->url($cover);
    }

    private function readMapping(string $path): array
    {
        $contents = file_get_contents($path);
        $decoded = $contents === false ? null : json_decode($contents, true);
        if (
            ! is_array($decoded)
            || ($decoded['format'] ?? null) !== 'silaras-ebook-import-v1'
            || ! is_array($decoded['mappings'] ?? null)
        ) {
            throw new RuntimeException('Berkas pemetaan tidak valid atau bukan hasil perintah impor.');
        }

        $seenBooks = [];
        foreach ($decoded['mappings'] as $row) {
            if (
                ! is_array($row)
                || ! isset($row['id_buku'], $row['id_ebook'], $row['judul'], $row['link_drive'])
                || ! is_numeric($row['id_buku'])
                || ! is_numeric($row['id_ebook'])
                || isset($seenBooks[$row['id_buku']])
                || (isset($row['cover_path']) && (
                    ! is_string($row['cover_path'])
                    || ! str_starts_with($row['cover_path'], 'ebook/cover/')
                    || str_contains($row['cover_path'], '..')
                ))
            ) {
                throw new RuntimeException('Isi berkas pemetaan tidak valid.');
            }
            $seenBooks[$row['id_buku']] = true;
        }

        return $decoded['mappings'];
    }

    private function mappedBooks(array $mappings): array
    {
        $mapped = [];
        foreach ($mappings as $row) {
            $book = DB::table('buku')->where('id_buku', $row['id_buku'])->first();
            $ebook = Ebook::query()->find($row['id_ebook']);
            if (
                $book
                && $ebook
                && (bool) $book->diarsipkan
                && $ebook->judul === $row['judul']
                && $ebook->link_drive === $row['link_drive']
            ) {
                $mapped[] = $row;
            }
        }

        return $mapped;
    }

    private function writeMapping(string $path, array $mappings): void
    {
        $contents = json_encode([
            'format' => 'silaras-ebook-import-v1',
            'mappings' => $mappings,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($contents === false) {
            throw new RuntimeException('Berkas pemetaan tidak dapat dienkode.');
        }

        $temporaryPath = $path.'.'.Str::uuid().'.tmp';
        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Berkas pemetaan tidak dapat ditulis.');
        }
        if (! rename($temporaryPath, $path)) {
            unlink($temporaryPath);
            throw new RuntimeException('Berkas pemetaan tidak dapat dipasang di jalur tujuan.');
        }
    }

    private function resolveExistingPath(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved)) {
            throw new RuntimeException("Berkas tidak ditemukan: {$path}");
        }

        return $resolved;
    }

    private function resolveMappingPath(string $path): string
    {
        $directory = realpath(dirname($path));
        if ($directory === false || ! is_dir($directory)) {
            throw new RuntimeException('Folder tujuan berkas pemetaan harus sudah ada.');
        }

        $resolved = $directory.DIRECTORY_SEPARATOR.basename($path);
        $this->assertOutsideRepository($resolved);

        return $resolved;
    }

    private function assertOutsideRepository(string $path): void
    {
        $repository = rtrim(realpath(base_path()) ?: base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (str_starts_with(strtolower($normalized), strtolower($repository))) {
            throw new RuntimeException('Berkas CSV dan pemetaan harus berada di luar repo.');
        }
    }

    private function normalizeTitle(string $title): string
    {
        return mb_strtolower(trim($title), 'UTF-8');
    }
}

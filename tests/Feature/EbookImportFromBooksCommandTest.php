<?php

namespace Tests\Feature;

use App\Models\Ebook;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EbookImportFromBooksCommandTest extends TestCase
{
    private string $workDirectory;

    private string $csvPath;

    private string $mappingPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Storage::fake('s3');

        $this->workDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'silaras-ebook-import-'.uniqid('', true);
        mkdir($this->workDirectory);
        $this->csvPath = $this->workDirectory.DIRECTORY_SEPARATOR.'tautan.csv';
        $this->mappingPath = $this->workDirectory.DIRECTORY_SEPARATOR.'pemetaan.json';

        Schema::create('buku', function (Blueprint $table): void {
            $table->id('id_buku');
            $table->string('judul_buku');
            $table->string('pengarang');
            $table->string('cover')->nullable();
            $table->string('pdf')->nullable();
            $table->boolean('diarsipkan')->default(false);
            $table->timestamps();
        });
        Schema::create('peminjaman', function (Blueprint $table): void {
            $table->id('id_peminjaman');
            $table->unsignedBigInteger('id_buku');
            $table->string('status_peminjaman');
        });
        Schema::create('ebook', function (Blueprint $table): void {
            $table->id();
            $table->string('judul');
            $table->string('penulis');
            $table->text('cover')->nullable();
            $table->text('link_drive');
            $table->timestamps();
        });
        Schema::create('unduhan_ebook', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ebook_id')->nullable();
            $table->string('judul_ebook');
            $table->string('penulis_ebook');
        });
    }

    protected function tearDown(): void
    {
        foreach (['unduhan_ebook', 'ebook', 'peminjaman', 'buku'] as $table) {
            Schema::dropIfExists($table);
        }

        foreach ([$this->csvPath, $this->mappingPath] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->workDirectory)) {
            rmdir($this->workDirectory);
        }

        parent::tearDown();
    }

    public function test_default_dry_run_reports_candidates_without_writing(): void
    {
        $this->createBook('  Buku Digital  ', 'Penulis');
        $this->writeCsv([['buku digital', 'https://drive.example/one']]);

        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
        ])->expectsOutputToContain('Mode: DRY-RUN')
            ->assertExitCode(0);
        $this->assertDatabaseCount('ebook', 0);
        $this->assertDatabaseHas('buku', ['diarsipkan' => false]);
        $this->assertFileDoesNotExist($this->mappingPath);
    }

    public function test_import_copies_cover_and_archives_the_book(): void
    {
        $coverPath = 'cover/import-source.jpg';
        Storage::disk('s3')->put($coverPath, 'image contents');
        $bookId = $this->createBook('Buku Digital', 'Penulis', $coverPath);
        $this->writeCsv([[' buku digital ', 'https://drive.example/one']]);

        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ])->assertExitCode(0);
        $ebook = Ebook::query()->sole();
        $this->assertSame('Buku Digital', $ebook->judul);
        $this->assertSame('Penulis', $ebook->penulis);
        $this->assertSame('https://drive.example/one', $ebook->link_drive);
        $this->assertNotNull($ebook->cover);
        $this->assertStringContainsString('/ebook/cover/', $ebook->cover);
        $this->assertSame('image contents', Storage::disk('s3')->get($this->pathFromCoverUrl($ebook->cover)));
        $this->assertDatabaseHas('buku', ['id_buku' => $bookId, 'diarsipkan' => true]);
        $this->assertFileExists($this->mappingPath);
    }

    public function test_csv_titles_select_only_matching_books_and_report_other_pdf_books(): void
    {
        $selectedBookIds = [
            $this->createBook('Ebook Satu', 'Penulis Satu'),
            $this->createBook('Ebook Dua', 'Penulis Dua'),
            $this->createBook('Ebook Tiga', 'Penulis Tiga'),
        ];
        $otherBookIds = [
            $this->createBook('Buku Fisik Rak A', 'Penulis Fisik'),
            $this->createBook('Buku Fisik Rak B', 'Penulis Fisik'),
        ];
        $this->writeCsv([
            [' ebook satu ', 'https://drive.example/one'],
            ['EBOOK DUA', 'https://drive.example/two'],
            ['Ebook Tiga', 'https://drive.example/three'],
        ]);

        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ])->expectsOutputToContain('PDF terisi di luar CSV (informasi; tidak disentuh)')
            ->expectsOutputToContain('Buku Fisik Rak A')
            ->expectsOutputToContain('Buku Fisik Rak B')
            ->assertExitCode(0);

        $this->assertDatabaseCount('ebook', 3);
        foreach ($selectedBookIds as $bookId) {
            $this->assertDatabaseHas('buku', ['id_buku' => $bookId, 'diarsipkan' => true]);
        }
        foreach ($otherBookIds as $bookId) {
            $this->assertDatabaseHas('buku', ['id_buku' => $bookId, 'diarsipkan' => false]);
        }
    }

    public function test_a_csv_matched_book_does_not_need_a_pdf_path(): void
    {
        $bookId = $this->createBook('Buku Tanpa PDF', 'Penulis', pdf: null);
        $this->writeCsv([['Buku Tanpa PDF', 'https://drive.example/one']]);

        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('ebook', 1);
        $this->assertDatabaseHas('buku', ['id_buku' => $bookId, 'diarsipkan' => true]);
    }

    public function test_running_import_twice_is_idempotent(): void
    {
        $this->createBook('Buku Digital', 'Penulis');
        $this->writeCsv([['Buku Digital', 'https://drive.example/one']]);
        $options = [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ];

        $this->artisan('ebook:impor-dari-buku', $options)->assertExitCode(0);
        $this->artisan('ebook:impor-dari-buku', $options)
            ->expectsOutputToContain('idempoten')
            ->assertExitCode(0);

        $this->assertDatabaseCount('ebook', 1);
    }

    public function test_import_refuses_unmatched_titles_without_writing(): void
    {
        $this->createBook('Buku Digital', 'Penulis');
        $this->writeCsv([['Judul lain', 'https://drive.example/one']]);

        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseCount('ebook', 0);
        $this->assertDatabaseHas('buku', ['diarsipkan' => false]);
        $this->assertFileDoesNotExist($this->mappingPath);
    }

    public function test_import_refuses_a_book_with_an_active_loan(): void
    {
        $bookId = $this->createBook('Buku Dipinjam', 'Penulis');
        DB::table('peminjaman')->insert([
            'id_buku' => $bookId,
            'status_peminjaman' => 'dipinjam',
        ]);
        $this->writeCsv([['Buku Dipinjam', 'https://drive.example/one']]);

        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ])->expectsOutputToContain('peminjaman aktif')
            ->assertExitCode(1);

        $this->assertDatabaseCount('ebook', 0);
        $this->assertDatabaseHas('buku', ['id_buku' => $bookId, 'diarsipkan' => false]);
        $this->assertFileDoesNotExist($this->mappingPath);
    }

    public function test_undo_removes_imported_ebook_and_unarchives_the_book(): void
    {
        Storage::disk('s3')->put('cover/undo.jpg', 'cover to remove');
        $bookId = $this->createBook('Buku Digital', 'Penulis', 'cover/undo.jpg');
        $this->writeCsv([['Buku Digital', 'https://drive.example/one']]);
        $this->artisan('ebook:impor-dari-buku', [
            '--tautan' => $this->csvPath,
            '--pemetaan' => $this->mappingPath,
            '--jalankan' => true,
        ])->assertExitCode(0);

        $ebookCover = Ebook::query()->sole()->cover;
        $importedCoverPath = $this->pathFromCoverUrl($ebookCover);
        $this->artisan('ebook:impor-dari-buku', [
            '--pemetaan' => $this->mappingPath,
            '--urungkan' => true,
        ])->assertExitCode(0);
        $this->assertDatabaseCount('ebook', 0);
        $this->assertDatabaseHas('buku', ['id_buku' => $bookId, 'diarsipkan' => false]);
        $this->assertFileDoesNotExist($this->mappingPath);
        Storage::disk('s3')->assertMissing($importedCoverPath);
        Storage::disk('s3')->assertExists('cover/undo.jpg');
    }

    private function createBook(
        string $title,
        string $author,
        ?string $cover = null,
        ?string $pdf = 'buku/pdf/digital.pdf'
    ): int {
        return (int) DB::table('buku')->insertGetId([
            'judul_buku' => $title,
            'pengarang' => $author,
            'cover' => $cover,
            'pdf' => $pdf,
            'diarsipkan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');
    }

    private function writeCsv(array $rows): void
    {
        $handle = fopen($this->csvPath, 'wb');
        fputcsv($handle, ['judul', 'link_drive'], ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
        fclose($handle);
    }

    private function pathFromCoverUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $position = strpos($path, 'ebook/cover/');
        $this->assertNotFalse($position);

        return rawurldecode(substr($path, $position));
    }
}

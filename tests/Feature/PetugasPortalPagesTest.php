<?php

namespace Tests\Feature;

use App\Exceptions\LogAktivitasException;
use App\Models\Anggota;
use App\Models\Petugas;
use App\Services\LogAktivitasService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PetugasPortalPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
        });

        Schema::create('petugas', function (Blueprint $table) {
            $table->id('id_petugas');
            $table->string('nama_petugas');
            $table->timestamps();
        });

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('petugas_id');
            $table->string('nama_petugas');
            $table->string('aksi');
            $table->string('objek');
            $table->string('keterangan')->nullable();
            $table->timestamp('waktu');
        });
        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->string('judul_buku');
            $table->string('pengarang');
            $table->string('penerbit')->nullable();
            $table->string('kategori')->nullable();
            $table->string('isbn')->nullable();
            $table->string('rak')->nullable();
            $table->string('cover')->nullable();
            $table->string('pdf')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('diarsipkan')->default(false);
            $table->timestamps();
        });

        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('id_peminjaman');
            $table->unsignedBigInteger('id_buku');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->date('tgl_peminjaman')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->string('status_peminjaman');
            $table->timestamps();
        });

        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id('id_pengembalian');
            $table->unsignedBigInteger('id_peminjaman');
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->date('tgl_pengembalian')->nullable();
            $table->decimal('denda', 10, 2)->default(0);
            $table->string('kondisi_buku')->nullable();
            $table->string('status_pengembalian');
            $table->string('status_denda')->nullable();
            $table->timestamps();
        });

        Schema::create('tagihan_denda', function (Blueprint $table) {
            $table->id('id_tagihan_denda');
            $table->unsignedBigInteger('id_peminjaman');
            $table->unsignedBigInteger('id_anggota');
            $table->decimal('nominal_tagihan', 10, 2);
            $table->decimal('nominal_terlambat', 10, 2)->default(0);
            $table->decimal('nominal_rusak', 10, 2)->default(0);
            $table->decimal('nominal_hilang', 10, 2)->default(0);
            $table->timestamp('dibekukan_at')->nullable();
            $table->date('tgl_tagihan')->nullable();
            $table->date('tgl_terakhir_diperbarui')->nullable();
            $table->string('status_tagihan');
            $table->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->string('nilai');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
        DB::table('pengaturan')->insert([
            'kunci' => 'tarif_denda_per_hari',
            'nilai' => '5000',
        ]);

        Schema::create('pembayaran_denda', function (Blueprint $table) {
            $table->id('id_pembayaran_denda');
            $table->unsignedBigInteger('id_tagihan_denda');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_petugas_verifikasi')->nullable();
            $table->string('metode_pembayaran');
            $table->decimal('jumlah_pembayaran', 10, 2);
            $table->string('bukti_pembayaran')->nullable();
            $table->string('status_pembayaran');
            $table->string('catatan_petugas')->nullable();
            $table->timestamp('waktu_pembayaran')->nullable();
            $table->timestamp('waktu_verifikasi')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (
            [
                'pembayaran_denda',
                'tagihan_denda',
                'pengaturan',
                'pengembalian',
                'peminjaman',
                'buku',
                'log_aktivitas',
                'petugas',
                'anggota',
            ] as $table
        ) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_petugas_management_pages_render_html_and_books_api_stays_json(): void
    {
        $this->actingAs(new Petugas(['nama_petugas' => 'Petugas Uji']), 'petugas');

        foreach (
            [
                '/petugas/buku' => 'Pengelolaan Buku',
                '/petugas/peminjaman' => 'Pengelolaan Peminjaman',
                '/petugas/pengembalian' => 'Pengelolaan Pengembalian',
                '/petugas/denda' => 'Pengelolaan Denda',
            ] as $uri => $heading
        ) {
            $this->get($uri)
                ->assertOk()
                ->assertHeader('content-type', 'text/html; charset=UTF-8')
                ->assertSeeText($heading)
                ->assertSee('/petugas/buku')
                ->assertSee('/petugas/peminjaman')
                ->assertSee('/petugas/pengembalian')
                ->assertSee('/petugas/denda')
                ->assertDontSee('/dev/anggota/pengembalian')
                ->assertDontSee('/dev/anggota/peminjaman');
        }

        foreach (
            [
                '/dev/anggota/peminjaman',
                '/dev/anggota/pengembalian',
            ] as $uri
        ) {
            $this->get($uri)->assertNotFound();
        }

        DB::table('buku')->insert([
            'judul_buku' => 'Buku Tanpa Field PDF API',
            'pengarang' => 'Penulis Uji',
            'stock' => 1,
            'pdf' => 'buku/pdf/legacy.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/buku')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.judul_buku', 'Buku Tanpa Field PDF API')
            ->assertJsonMissingPath('data.0.pdf');
    }

    public function test_dashboard_uses_database_activity_counts_dates_and_configured_fine_rate(): void
    {
        $today = now('Asia/Jakarta')->startOfDay();
        DB::table('anggota')->insert([
            'id_anggota' => 1,
            'nama_anggota' => 'Anggota Dashboard',
        ]);
        DB::table('buku')->insert([
            'id_buku' => 1,
            'judul_buku' => 'Buku Dashboard',
            'pengarang' => 'Penulis Uji',
            'isbn' => '9780000000001',
            'rak' => 'A1',
            'stock' => 2,
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        DB::table('peminjaman')->insert([
            [
                'id_peminjaman' => 1,
                'id_buku' => 1,
                'id_anggota' => 1,
                'tgl_peminjaman' => $today->toDateString(),
                'tgl_jatuh_tempo' => $today->copy()->addDays(7)->toDateString(),
                'status_peminjaman' => 'menunggu validasi',
                'created_at' => $today,
                'updated_at' => $today,
            ],
            [
                'id_peminjaman' => 2,
                'id_buku' => 1,
                'id_anggota' => 1,
                'tgl_peminjaman' => $today->toDateString(),
                'tgl_jatuh_tempo' => $today->copy()->addDays(7)->toDateString(),
                'status_peminjaman' => 'dikembalikan',
                'created_at' => $today,
                'updated_at' => $today,
            ],
        ]);
        DB::table('pengembalian')->insert([
            'id_pengembalian' => 1,
            'id_peminjaman' => 2,
            'tgl_pengembalian' => $today->toDateString(),
            'denda' => 0,
            'status_pengembalian' => 'selesai',
            'status_denda' => 'tidak ada',
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        DB::table('log_aktivitas')->insert([
            'petugas_id' => 1,
            'nama_petugas' => 'Petugas Log Nyata',
            'aksi' => 'ubah',
            'objek' => 'buku',
            'keterangan' => 'Buku dari log',
            'waktu' => $today,
        ]);

        $this->actingAs(new Petugas(['nama_petugas' => 'Petugas Dashboard']), 'petugas')
            ->get('/petugas/dashboard')
            ->assertOk()
            ->assertSeeText('Petugas Dashboard')
            ->assertSeeText('1 tertunda')
            ->assertSeeText('ID Anggota: 1')
            ->assertSeeText($today->format('d/m/Y'))
            ->assertSeeText('Tarif Rp 5.000/hari')
            ->assertSeeText('Petugas Log Nyata')
            ->assertSeeText('Ubah buku — Buku dari log')
            ->assertSee('Peminjaman Hari ini: 2', false)
            ->assertSee('Pengembalian Hari ini: 1', false)
            ->assertDontSeeText('Eom Seonghyeon')
            ->assertDontSeeText('3 tertunda')
            ->assertDontSeeText('15 Okt 2024');
    }

    public function test_anggota_cannot_access_petugas_management_pages(): void
    {
        $this->actingAs(new Anggota(['nama_anggota' => 'Anggota Uji']), 'anggota');

        foreach (
            [
                '/petugas/buku',
                '/petugas/peminjaman',
                '/petugas/pengembalian',
                '/petugas/denda',
            ] as $uri
        ) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_petugas_can_create_book_with_cover_image(): void
    {
        Storage::fake('s3');
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas Uji']);
        $this->actingAs($staff, 'petugas');

        $this->post(route('petugas.buku.store'), [
            'judul_buku' => 'Buku Dengan Cover',
            'pengarang' => 'Penulis Uji',
            'stock' => 1,
            'cover' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('petugas.kelola-buku.index'));

        $book = DB::table('buku')->where('judul_buku', 'Buku Dengan Cover')->first();

        $this->assertNotNull($book);
        $this->assertNotNull($book->cover);
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'tambah',
            'objek' => 'buku',
            'keterangan' => 'Buku Dengan Cover',
        ]);
        Storage::disk('s3')->assertExists($book->cover);
    }

    public function test_book_create_rolls_back_and_removes_uploaded_cover_when_activity_logging_fails(): void
    {
        Storage::fake('s3');
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas Uji']);
        $this->actingAs($staff, 'petugas');

        $this->mock(LogAktivitasService::class, function ($mock): void {
            $mock->shouldReceive('catat')
                ->once()
                ->andThrow(new LogAktivitasException);
        });

        $response = $this->from(route('petugas.kelola-buku.create'))
            ->post(route('petugas.buku.store'), [
                'judul_buku' => 'Buku Tidak Tersimpan',
                'pengarang' => 'Penulis Uji',
                'stock' => 1,
                'cover' => UploadedFile::fake()->create('cover-gagal.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertRedirect(route('petugas.kelola-buku.create'))
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('buku', ['judul_buku' => 'Buku Tidak Tersimpan']);
        $this->assertDatabaseCount('log_aktivitas', 0);
        $this->assertCount(0, Storage::disk('s3')->allFiles('cover'));
    }

    public function test_book_update_and_archive_are_logged_with_the_required_actions(): void
    {
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas Uji']);
        $bookId = DB::table('buku')->insertGetId([
            'judul_buku' => 'Buku Log',
            'pengarang' => 'Penulis',
            'stock' => 1,
            'diarsipkan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');

        $this->actingAs($staff, 'petugas')
            ->put(route('petugas.kelola-buku.update', $bookId), [
                'judul_buku' => 'Buku Log Diubah',
                'pengarang' => 'Penulis',
                'stock' => 1,
            ])
            ->assertRedirect(route('petugas.kelola-buku.index'));

        $this->delete(route('petugas.kelola-buku.destroy', $bookId))
            ->assertRedirect(route('petugas.kelola-buku.index'));

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'ubah',
            'objek' => 'buku',
            'keterangan' => 'Buku Log Diubah',
        ]);
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'hapus',
            'objek' => 'buku',
            'keterangan' => 'Buku Log Diubah',
        ]);
    }

    public function test_book_csv_template_and_atomic_import(): void
    {
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas CSV']);
        $this->actingAs($staff, 'petugas');

        $template = $this->get(route('petugas.kelola-buku.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        ob_start();
        $template->baseResponse->sendContent();
        $templateContent = ob_get_clean();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $templateContent);
        $this->assertStringContainsString('judul_buku,pengarang,penerbit,stock,kategori,isbn,rak', $templateContent);

        $csv = "judul_buku,pengarang,penerbit,stock,kategori,isbn,rak\n".
            "Buku CSV Satu,Penulis Satu,Penerbit A,3,Umum,978-111,R1\n".
            "Buku CSV Dua,Penulis Dua,,0,Fiksi,,R2\n";

        $this->post(route('petugas.kelola-buku.import'), [
            'file' => UploadedFile::fake()->createWithContent('buku.csv', $csv),
        ])->assertRedirect(route('petugas.kelola-buku.index'))
            ->assertSessionHas('success', '2 buku berhasil diimpor.');

        $this->assertDatabaseHas('buku', [
            'judul_buku' => 'Buku CSV Satu',
            'stock' => 3,
            'isbn' => '978-111',
        ]);
        $this->assertDatabaseHas('buku', [
            'judul_buku' => 'Buku CSV Dua',
            'penerbit' => null,
            'stock' => 0,
        ]);
        $this->assertDatabaseCount('log_aktivitas', 2);
    }

    public function test_book_csv_import_rejects_duplicate_batch_without_partial_writes(): void
    {
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas CSV']);
        $this->actingAs($staff, 'petugas');
        DB::table('buku')->insert([
            'judul_buku' => 'Buku Sudah Ada',
            'pengarang' => 'Penulis Ada',
            'stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $csv = "judul_buku,pengarang,penerbit,stock,kategori,isbn,rak\n".
            "Buku Baru,Penulis Baru,,1,Umum,,R1\n".
            "Buku Sudah Ada,Penulis Ada,,1,Umum,,R2\n";

        $this->from(route('petugas.kelola-buku.index'))
            ->post(route('petugas.kelola-buku.import'), [
                'file' => UploadedFile::fake()->createWithContent('duplikat.csv', $csv),
            ])
            ->assertRedirect(route('petugas.kelola-buku.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('buku', ['judul_buku' => 'Buku Baru']);
        $this->assertDatabaseCount('log_aktivitas', 0);
    }

    public function test_bulk_book_delete_uses_all_filtered_and_refuses_books_with_loan_history(): void
    {
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas Hapus']);
        $this->actingAs($staff, 'petugas');
        $availableId = DB::table('buku')->insertGetId([
            'judul_buku' => 'Buku Tersedia',
            'pengarang' => 'Penulis',
            'stock' => 1,
            'diarsipkan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');
        $emptyStockId = DB::table('buku')->insertGetId([
            'judul_buku' => 'Buku Habis',
            'pengarang' => 'Penulis',
            'stock' => 0,
            'diarsipkan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');

        $this->post(route('petugas.kelola-buku.bulk-destroy'), [
            'all_filtered' => 1,
            'ketersediaan' => 'tersedia',
        ])->assertRedirect(route('petugas.kelola-buku.index'))
            ->assertSessionHas('success', '1 buku berhasil dihapus permanen.');

        $this->assertDatabaseMissing('buku', ['id_buku' => $availableId]);
        $this->assertDatabaseHas('buku', ['id_buku' => $emptyStockId]);

        $loanBookId = DB::table('buku')->insertGetId([
            'judul_buku' => 'Buku Bersejarah',
            'pengarang' => 'Penulis',
            'stock' => 1,
            'diarsipkan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');
        DB::table('peminjaman')->insert([
            'id_buku' => $loanBookId,
            'id_anggota' => 1,
            'status_peminjaman' => 'dikembalikan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('petugas.kelola-buku.index'))
            ->post(route('petugas.kelola-buku.bulk-destroy'), ['ids' => [$loanBookId]])
            ->assertRedirect(route('petugas.kelola-buku.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('buku', ['id_buku' => $loanBookId]);
        $this->assertDatabaseCount('log_aktivitas', 1);
    }

    public function test_book_can_be_permanently_deleted_only_without_loan_history(): void
    {
        $staff = Petugas::query()->create(['nama_petugas' => 'Petugas Hapus']);
        $this->actingAs($staff, 'petugas');
        $bookId = DB::table('buku')->insertGetId([
            'judul_buku' => 'Buku Tanpa Riwayat',
            'pengarang' => 'Penulis',
            'stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');

        $this->delete(route('petugas.kelola-buku.permanent-destroy', $bookId))
            ->assertRedirect(route('petugas.kelola-buku.index'))
            ->assertSessionHas('success', 'Buku berhasil dihapus permanen.');
        $this->assertDatabaseMissing('buku', ['id_buku' => $bookId]);

        $bookWithHistory = DB::table('buku')->insertGetId([
            'judul_buku' => 'Buku Dengan Riwayat',
            'pengarang' => 'Penulis',
            'stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_buku');
        DB::table('peminjaman')->insert([
            'id_buku' => $bookWithHistory,
            'id_anggota' => 1,
            'status_peminjaman' => 'dikembalikan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('petugas.kelola-buku.index'))
            ->delete(route('petugas.kelola-buku.permanent-destroy', $bookWithHistory))
            ->assertRedirect(route('petugas.kelola-buku.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('buku', ['id_buku' => $bookWithHistory]);
        $this->assertDatabaseCount('log_aktivitas', 1);
    }

    public function test_petugas_can_open_denda_details(): void
    {
        DB::table('anggota')->insert([
            'id_anggota' => 1,
            'nama_anggota' => 'Anggota Uji',
        ]);
        DB::table('buku')->insert([
            'id_buku' => 1,
            'judul_buku' => 'Buku Uji',
            'pengarang' => 'Penulis Uji',
            'stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('peminjaman')->insert([
            'id_peminjaman' => 1,
            'id_buku' => 1,
            'id_anggota' => 1,
            'tgl_peminjaman' => '2026-10-01',
            'tgl_jatuh_tempo' => '2026-10-08',
            'status_peminjaman' => 'dikembalikan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tagihan_denda')->insert([
            'id_tagihan_denda' => 1,
            'id_peminjaman' => 1,
            'id_anggota' => 1,
            'nominal_tagihan' => 2000,
            'tgl_tagihan' => '2026-10-09',
            'status_tagihan' => 'belum_lunas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(new Petugas(['nama_petugas' => 'Petugas Uji']), 'petugas')
            ->get('/petugas/denda/1')
            ->assertOk()
            ->assertSeeText('Detail Tagihan Denda')
            ->assertSeeText('Anggota Uji')
            ->assertSeeText('Buku Uji')
            ->assertSeeText('2.000');
    }

    public function test_petugas_transaction_history_uses_real_records_and_filters_them(): void
    {
        $now = now();
        DB::table('anggota')->insert([
            'id_anggota' => 1,
            'nama_anggota' => 'Anggota Transaksi',
        ]);
        DB::table('petugas')->insert([
            'id_petugas' => 1,
            'nama_petugas' => 'Petugas Transaksi',
        ]);
        DB::table('buku')->insert([
            'id_buku' => 1,
            'judul_buku' => 'Buku Transaksi Nyata',
            'pengarang' => 'Penulis Uji',
            'stock' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('peminjaman')->insert([
            'id_peminjaman' => 1,
            'id_buku' => 1,
            'id_anggota' => 1,
            'id_petugas' => 1,
            'tgl_peminjaman' => $now->toDateString(),
            'tgl_jatuh_tempo' => $now->copy()->addDays(7)->toDateString(),
            'status_peminjaman' => 'dikembalikan',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('pengembalian')->insert([
            'id_pengembalian' => 1,
            'id_peminjaman' => 1,
            'id_petugas' => 1,
            'tgl_pengembalian' => $now->toDateString(),
            'denda' => 5000,
            'kondisi_buku' => 'baik',
            'status_pengembalian' => 'selesai',
            'status_denda' => 'lunas',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('tagihan_denda')->insert([
            'id_tagihan_denda' => 1,
            'id_peminjaman' => 1,
            'id_anggota' => 1,
            'nominal_tagihan' => 5000,
            'tgl_tagihan' => $now->toDateString(),
            'status_tagihan' => 'lunas',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('pembayaran_denda')->insert([
            'id_pembayaran_denda' => 1,
            'id_tagihan_denda' => 1,
            'id_anggota' => 1,
            'id_petugas_verifikasi' => 1,
            'metode_pembayaran' => 'tunai',
            'jumlah_pembayaran' => 5000,
            'status_pembayaran' => 'diverifikasi',
            'waktu_pembayaran' => $now,
            'waktu_verifikasi' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs(new Petugas(['nama_petugas' => 'Petugas Uji']), 'petugas');

        $this->get(route('petugas.transaksi'))
            ->assertOk()
            ->assertSeeText('PMJ-1')
            ->assertSeeText('PGM-1')
            ->assertSeeText('DND-1')
            ->assertSeeText('Anggota Transaksi')
            ->assertSeeText('Buku Transaksi Nyata')
            ->assertSeeText('Rp 5.000')
            ->assertDontSeeText('Farhan Maulana')
            ->assertDontSeeText('1.428');

        $this->get(route('petugas.transaksi', [
            'jenis' => 'peminjaman',
            'q' => 'Anggota Transaksi',
        ]))
            ->assertOk()
            ->assertSeeText('PMJ-1')
            ->assertDontSeeText('PGM-1')
            ->assertDontSeeText('DND-1');
    }
}

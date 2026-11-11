<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Petugas;
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

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
        });

        Schema::create('petugas', function (Blueprint $table) {
            $table->id('id_petugas');
            $table->string('nama_petugas');
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

        $this->getJson('/api/buku')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
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

    public function test_petugas_can_create_book_with_optional_pdf_and_cover_image(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs(new Petugas(['nama_petugas' => 'Petugas Uji']), 'petugas');

        $this->post(route('petugas.buku.store'), [
            'judul_buku' => 'Buku Dengan Cover',
            'pengarang' => 'Penulis Uji',
            'stock' => 1,
            'cover' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('petugas.kelola-buku.index'));

        $book = DB::table('buku')->where('judul_buku', 'Buku Dengan Cover')->first();

        $this->assertNotNull($book);
        $this->assertNotNull($book->cover);
        $this->assertNull($book->pdf);
        $this->assertTrue(Storage::disk('public')->exists($book->cover));
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

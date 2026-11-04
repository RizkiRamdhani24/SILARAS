<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnggotaDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
            $table->string('email_anggota')->unique();
            $table->string('no_telp')->nullable();
            $table->string('password');
            $table->string('status_anggota')->default(Anggota::STATUS_AKTIF);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->string('judul_buku');
            $table->string('penerbit')->nullable();
            $table->string('pengarang');
            $table->unsignedInteger('stock')->default(0);
            $table->string('kategori')->nullable();
            $table->string('isbn')->nullable();
            $table->string('rak')->nullable();
            $table->string('cover')->nullable();
            $table->timestamps();
        });

        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('id_peminjaman');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_buku');
            $table->date('tgl_peminjaman');
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->string('status_peminjaman');
            $table->timestamps();
        });

        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id('id_pengembalian');
            $table->unsignedBigInteger('id_peminjaman');
            $table->date('tgl_pengembalian')->nullable();
            $table->string('kondisi_buku')->nullable();
            $table->decimal('denda', 10, 2)->default(0);
            $table->string('status_denda')->nullable();
            $table->string('status_pengembalian')->nullable();
            $table->timestamps();
        });
    }

    public function test_authenticated_member_sees_book_metadata_from_requested_database_record(): void
    {
        $member = Anggota::query()->create([
            'nama_anggota' => 'Anggota Integrasi',
            'email_anggota' => 'integrasi@example.test',
            'password' => 'password',
            'status_anggota' => Anggota::STATUS_AKTIF,
        ]);
        $book = Buku::query()->create([
            'judul_buku' => 'Metadata dari Database',
            'pengarang' => 'Penulis Integrasi',
            'penerbit' => 'Penerbit Integrasi',
            'kategori' => 'Teknologi',
            'stock' => 2,
            'isbn' => '978-1-111-11111-1',
            'rak' => 'T-01',
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.show', $book->getKey()))
            ->assertOk()
            ->assertSee('ID: ' . $book->getKey())
            ->assertSee('Metadata dari Database')
            ->assertSee('Penulis Integrasi')
            ->assertSee('Penerbit Integrasi')
            ->assertSee('978-1-111-11111-1')
            ->assertSee('Rak T-01');
    }

    public function test_member_dashboard_stays_protected(): void
    {
        $this->get('/anggota/dashboard')
            ->assertRedirect('/login');
    }

    public function test_member_catalog_stays_protected(): void
    {
        $this->get('/anggota/katalog')->assertRedirect('/login');
    }

    public function test_member_book_detail_stays_protected(): void
    {
        $this->get('/anggota/katalog/1')->assertRedirect('/login');
    }

    public function test_missing_book_id_returns_not_found_for_authenticated_member(): void
    {
        $member = Anggota::query()->create([
            'nama_anggota' => 'Anggota Aktif',
            'email_anggota' => 'aktif@example.test',
            'password' => 'password',
            'status_anggota' => Anggota::STATUS_AKTIF,
        ]);

        $this->actingAs($member, 'anggota')
            ->get('/anggota/katalog/999999')
            ->assertNotFound();
    }

    public function test_petugas_cannot_open_member_routes(): void
    {
        $petugas = new \App\Models\Petugas([
            'nama_petugas' => 'Petugas Pengujian',
            'email_petugas' => 'petugas@example.test',
        ]);
        $petugas->id_petugas = 1;

        foreach (['/anggota/dashboard', '/anggota/katalog', '/anggota/katalog/1'] as $uri) {
            $this->actingAs($petugas, 'petugas')->get($uri)->assertForbidden();
        }
    }

    public function test_book_detail_renders_only_available_catalog_fields(): void
    {
        $book = new Buku([
            'judul_buku' => 'Arsitektur Perangkat Lunak',
            'pengarang' => 'Penulis Terverifikasi',
            'penerbit' => 'Penerbit Kampus',
            'kategori' => 'Teknologi',
            'stock' => 3,
        ]);
        $book->id_buku = 25;
        $book->isbn = '978-000-000';
        $book->rak = '07';
        $book->cover = null;

        $response = $this->view('anggota.buku.show', [
            'member' => null,
            'book' => $book,
            'relatedBooks' => collect(),
        ]);

        $response->assertSee('Arsitektur Perangkat Lunak')
            ->assertSee('Penulis Terverifikasi')
            ->assertSee('Penerbit Kampus')
            ->assertSee('978-000-000')
            ->assertSee('Rak 07')
            ->assertSee('Buku fisik')
            ->assertDontSee('Filosofi Teras');
    }

    public function test_available_book_detail_shows_frontend_only_loan_request_ui(): void
    {
        $member = $this->createMember();
        $book = $this->createBook('Buku Siap Dipinjam', ['stock' => 2]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.show', $book->getKey()))
            ->assertOk()
            ->assertSee('Informasi Peminjaman')
            ->assertSee('Pengajuan online belum dapat diproses')
            ->assertSee('data-loan-request-form', false)
            ->assertDontSee('Pengajuan berhasil');
    }

    public function test_catalog_search_matches_each_supported_book_field(): void
    {
        $member = $this->createMember();
        $fields = [
            'judul_buku' => 'JarumJudul',
            'pengarang' => 'JarumPengarang',
            'penerbit' => 'JarumPenerbit',
            'kategori' => 'JarumKategori',
            'isbn' => 'JarumISBN',
        ];

        foreach ($fields as $field => $needle) {
            $matching = $this->createBook('Cocok ' . $field, [$field => $needle]);
            $other = $this->createBook('Bukan ' . $field);

            $this->actingAs($member, 'anggota')
                ->get(route('anggota.katalog', ['q' => $needle]))
                ->assertOk()
                ->assertSee($matching->judul_buku)
                ->assertDontSee($other->judul_buku);

            $matching->delete();
            $other->delete();
        }
    }

    public function test_catalog_filters_category_and_availability(): void
    {
        $member = $this->createMember();
        $availableTechnology = $this->createBook('Teknologi Tersedia', ['kategori' => 'Teknologi', 'stock' => 4]);
        $unavailableTechnology = $this->createBook('Teknologi Habis', ['kategori' => 'Teknologi', 'stock' => 0]);
        $availableHistory = $this->createBook('Sejarah Tersedia', ['kategori' => 'Sejarah', 'stock' => 3]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog', ['kategori' => 'Teknologi', 'ketersediaan' => 'tersedia']))
            ->assertOk()
            ->assertSee($availableTechnology->judul_buku)
            ->assertDontSee($unavailableTechnology->judul_buku)
            ->assertDontSee($availableHistory->judul_buku);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog', ['kategori' => 'Teknologi', 'ketersediaan' => 'habis']))
            ->assertOk()
            ->assertSee($unavailableTechnology->judul_buku)
            ->assertDontSee($availableTechnology->judul_buku)
            ->assertDontSee($availableHistory->judul_buku);
    }

    public function test_catalog_pagination_keeps_search_and_filters(): void
    {
        $member = $this->createMember();

        foreach (range(1, 9) as $number) {
            $this->createBook(sprintf('Buku Teknologi %02d', $number), [
                'kategori' => 'Teknologi',
                'stock' => 1,
                'isbn' => 'FILTER-' . $number,
            ]);
        }

        $response = $this->actingAs($member, 'anggota')->get(route('anggota.katalog', [
            'q' => 'Buku',
            'kategori' => 'Teknologi',
            'ketersediaan' => 'tersedia',
        ]));

        $response->assertOk()
            ->assertSee('Buku Teknologi 01')
            ->assertSee('Buku Teknologi 08')
            ->assertDontSee('Buku Teknologi 09')
            ->assertSee('page=2', false)
            ->assertSee('q=Buku', false)
            ->assertSee('kategori=Teknologi', false)
            ->assertSee('ketersediaan=tersedia', false);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog', [
                'q' => 'Buku',
                'kategori' => 'Teknologi',
                'ketersediaan' => 'tersedia',
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('Buku Teknologi 09')
            ->assertDontSee('Buku Teknologi 01');
    }

    public function test_nullable_book_metadata_and_cover_render_through_routes(): void
    {
        $member = $this->createMember();
        $book = $this->createBook('Metadata Nullable', [
            'penerbit' => null,
            'kategori' => null,
            'isbn' => null,
            'rak' => null,
            'cover' => null,
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog'))
            ->assertOk()
            ->assertSee('Metadata Nullable')
            ->assertSee('Sampul buku tidak tersedia');

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.show', $book->getKey()))
            ->assertOk()
            ->assertSee('Metadata Nullable')
            ->assertSee('Belum tersedia')
            ->assertSee('Lokasi belum tersedia')
            ->assertSee('Sampul buku tidak tersedia');
    }

    public function test_related_books_come_only_from_selected_category_database_records(): void
    {
        $member = $this->createMember();
        $selected = $this->createBook('Buku Utama', ['kategori' => 'Teknologi']);
        $related = $this->createBook('Buku Terkait Database', ['kategori' => 'Teknologi']);
        $unrelated = $this->createBook('Buku Kategori Lain', ['kategori' => 'Sejarah']);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.show', $selected->getKey()))
            ->assertOk()
            ->assertSee($related->judul_buku)
            ->assertDontSee($unrelated->judul_buku)
            ->assertDontSee('Filosofi Teras');
    }

    public function test_member_dashboard_uses_only_authenticated_members_data(): void
    {
        $member = Anggota::query()->create([
            'nama_anggota' => 'Anggota Aktif',
            'email_anggota' => 'aktif@example.test',
            'password' => 'password',
            'status_anggota' => Anggota::STATUS_AKTIF,
        ]);

        $otherMember = Anggota::query()->create([
            'nama_anggota' => 'Anggota Lain',
            'email_anggota' => 'lain@example.test',
            'password' => 'password',
            'status_anggota' => Anggota::STATUS_AKTIF,
        ]);

        $activeBook = Buku::query()->create([
            'judul_buku' => 'Buku Aktif Milik Saya',
            'pengarang' => 'Penulis Aktif',
            'stock' => 2,
        ]);

        $returnedBook = Buku::query()->create([
            'judul_buku' => 'Buku Selesai Milik Saya',
            'pengarang' => 'Penulis Selesai',
            'stock' => 1,
        ]);

        $otherBook = Buku::query()->create([
            'judul_buku' => 'Buku Anggota Lain',
            'pengarang' => 'Penulis Lain',
            'stock' => 1,
        ]);

        Peminjaman::query()->create([
            'id_anggota' => $member->getKey(),
            'id_buku' => $activeBook->getKey(),
            'tgl_peminjaman' => Carbon::parse('2026-10-20'),
            'tgl_jatuh_tempo' => Carbon::parse('2026-10-30'),
            'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
        ]);

        Peminjaman::query()->create([
            'id_anggota' => $member->getKey(),
            'id_buku' => $returnedBook->getKey(),
            'tgl_peminjaman' => Carbon::parse('2026-09-01'),
            'tgl_jatuh_tempo' => Carbon::parse('2026-09-08'),
            'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
        ]);

        Peminjaman::query()->create([
            'id_anggota' => $otherMember->getKey(),
            'id_buku' => $otherBook->getKey(),
            'tgl_peminjaman' => Carbon::parse('2026-10-21'),
            'tgl_jatuh_tempo' => Carbon::parse('2026-10-31'),
            'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
        ]);

        $this->actingAs($member, 'anggota')
            ->get('/anggota/dashboard')
            ->assertOk()
            ->assertSee('Anggota Aktif')
            ->assertSee('Buku Aktif Milik Saya')
            ->assertDontSee('Anggota Lain')
            ->assertDontSee('Buku Anggota Lain');
    }

    private function createMember(): Anggota
    {
        static $number = 0;
        $number++;

        return Anggota::query()->create([
            'nama_anggota' => 'Anggota Pengujian',
            'email_anggota' => 'anggota' . $number . '@example.test',
            'password' => 'password',
            'status_anggota' => Anggota::STATUS_AKTIF,
        ]);
    }

    private function createBook(string $title, array $attributes = []): Buku
    {
        return Buku::query()->create(array_merge([
            'judul_buku' => $title,
            'pengarang' => 'Penulis Pengujian',
            'penerbit' => 'Penerbit Pengujian',
            'kategori' => 'Umum',
            'stock' => 1,
            'isbn' => null,
            'rak' => null,
            'cover' => null,
        ], $attributes));
    }
}

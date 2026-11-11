<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Notifikasi;
use App\Models\PembayaranDenda;
use App\Models\Peminjaman;
use App\Models\Petugas;
use App\Models\TagihanDenda;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $table->string('pdf')->nullable();
            $table->boolean('diarsipkan')->default(false);
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
            ->assertSee('ID: '.$book->getKey())
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

    public function test_catalog_remains_available_before_book_archive_migration(): void
    {
        $member = $this->createMember();
        $book = $this->createBook('Buku Sebelum Migration');
        Schema::table('buku', function (Blueprint $table) {
            $table->dropColumn('diarsipkan');
        });

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog'))
            ->assertOk()
            ->assertSee($book->judul_buku);
    }

    public function test_private_book_pdf_remains_readable_before_book_archive_migration(): void
    {
        Storage::fake('local');
        $member = $this->createMember();
        $book = $this->createBook('PDF Sebelum Migration', ['pdf' => 'buku/pdf/sample.pdf']);
        Storage::disk('local')->put($book->pdf, '%PDF-1.4 test content');
        Schema::table('buku', function (Blueprint $table) {
            $table->dropColumn('diarsipkan');
        });

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.pdf', $book->getKey()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertStreamedContent('%PDF-1.4 test content');
    }

    public function test_authenticated_member_can_download_private_book_pdf(): void
    {
        Storage::fake('local');
        $member = $this->createMember();
        $book = $this->createBook('Buku Uji Unduh', ['pdf' => 'buku/pdf/sample.pdf']);
        Storage::disk('local')->put($book->pdf, '%PDF-1.4 test content');

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.pdf.download', $book->getKey()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=buku-uji-unduh.pdf')
            ->assertStreamedContent('%PDF-1.4 test content');
    }

    public function test_book_pdf_download_stays_protected_from_guests_and_petugas(): void
    {
        $book = $this->createBook('Buku Unduhan Terlindungi', ['pdf' => 'buku/pdf/private.pdf']);

        $this->get(route('anggota.buku.pdf.download', $book->getKey()))
            ->assertRedirect('/login');

        $petugas = new Petugas([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas-download@example.test',
        ]);

        $this->actingAs($petugas, 'petugas')
            ->get(route('anggota.buku.pdf.download', $book->getKey()))
            ->assertForbidden();
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
        $petugas = new Petugas([
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
            ->assertSee(
                'data-confirm="Ajukan peminjaman buku Arsitektur Perangkat Lunak dengan durasi yang dipilih?"',
                false
            )
            ->assertDontSee('Filosofi Teras');
    }

    public function test_available_book_detail_offers_the_allowed_loan_durations(): void
    {
        $member = $this->createMember();
        $book = $this->createBook('Buku Siap Dipinjam', ['stock' => 2]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.show', $book->getKey()))
            ->assertOk()
            ->assertSee('Informasi Peminjaman')
            ->assertSee('Durasi peminjaman')
            ->assertSee('7 hari')
            ->assertSee('14 hari')
            ->assertSee('30 hari')
            ->assertSee(route('anggota.peminjaman.store'));
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
            $matching = $this->createBook('Cocok '.$field, [$field => $needle]);
            $other = $this->createBook('Bukan '.$field);

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
                'isbn' => 'FILTER-'.$number,
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

    public function test_missing_cover_file_uses_placeholder_in_catalog_and_book_detail(): void
    {
        Storage::fake('public');
        $member = $this->createMember();
        $book = $this->createBook('Cover Hilang', [
            'cover' => 'buku/cover/cover-tidak-ada.jpg',
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog'))
            ->assertOk()
            ->assertSee('Sampul buku tidak tersedia')
            ->assertDontSee('/storage/buku/cover/cover-tidak-ada.jpg');

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.buku.show', $book->getKey()))
            ->assertOk()
            ->assertSee('Sampul buku tidak tersedia')
            ->assertDontSee('/storage/buku/cover/cover-tidak-ada.jpg');
    }

    public function test_existing_cover_is_served_from_public_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('buku/cover/cover-tersedia.jpg', 'cover');
        $member = $this->createMember();
        $this->createBook('Cover Publik', [
            'cover' => 'buku/cover/cover-tersedia.jpg',
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.katalog'))
            ->assertOk()
            ->assertSee('/storage/buku/cover/cover-tersedia.jpg');
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

    public function test_dashboard_shows_blue_fine_hero_when_member_has_no_unpaid_bills(): void
    {
        $this->createFineTables();
        $member = $this->createMember();

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('member-fine-alert is-biru', false)
            ->assertSee('Tidak ada tunggakan denda yang perlu diselesaikan.');
    }

    public function test_dashboard_shows_yellow_fine_hero_and_total_for_unpaid_bill(): void
    {
        $this->createFineTables();
        $member = $this->createMember();
        $book = $this->createBook('Buku dengan Denda');
        $loan = Peminjaman::query()->create([
            'id_anggota' => $member->getKey(),
            'id_buku' => $book->getKey(),
            'tgl_peminjaman' => Carbon::today(),
            'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
        ]);
        TagihanDenda::query()->create([
            'id_peminjaman' => $loan->getKey(),
            'id_anggota' => $member->getKey(),
            'nominal_tagihan' => 15000,
            'tgl_tagihan' => Carbon::today(),
            'status_tagihan' => TagihanDenda::STATUS_BELUM_LUNAS,
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('member-fine-alert is-kuning', false)
            ->assertSee('Total tagihan belum lunas: Rp15.000');
    }

    public function test_dashboard_shows_red_fine_hero_while_late_fine_is_running(): void
    {
        $this->createFineTables();
        $member = $this->createMember();
        $book = $this->createBook('Buku Terlambat');
        $loan = Peminjaman::query()->create([
            'id_anggota' => $member->getKey(),
            'id_buku' => $book->getKey(),
            'tgl_peminjaman' => Carbon::today()->subDays(10),
            'tgl_jatuh_tempo' => Carbon::today()->subDay(),
            'status_peminjaman' => Peminjaman::STATUS_TERLAMBAT,
        ]);
        TagihanDenda::query()->create([
            'id_peminjaman' => $loan->getKey(),
            'id_anggota' => $member->getKey(),
            'nominal_tagihan' => 5000,
            'tgl_tagihan' => Carbon::today(),
            'status_tagihan' => TagihanDenda::STATUS_BELUM_LUNAS,
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('member-fine-alert is-merah', false)
            ->assertSee('Denda keterlambatan masih bertambah sampai pengembalian dikonfirmasi.');
    }

    public function test_dashboard_uses_the_latest_payment_attempt_for_fine_hero_status(): void
    {
        $this->createFineTables();
        $member = $this->createMember();
        $book = $this->createBook('Buku Pembayaran Ulang');
        $loan = Peminjaman::query()->create([
            'id_anggota' => $member->getKey(),
            'id_buku' => $book->getKey(),
            'tgl_peminjaman' => Carbon::today(),
            'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
        ]);
        $bill = TagihanDenda::query()->create([
            'id_peminjaman' => $loan->getKey(),
            'id_anggota' => $member->getKey(),
            'nominal_tagihan' => 15000,
            'tgl_tagihan' => Carbon::today(),
            'status_tagihan' => TagihanDenda::STATUS_BELUM_LUNAS,
        ]);
        $rejectedPayment = PembayaranDenda::query()->create([
            'id_tagihan_denda' => $bill->getKey(),
            'id_anggota' => $member->getKey(),
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => 15000,
            'status_pembayaran' => 'ditolak',
        ]);
        $rejectedPayment->created_at = Carbon::now()->subMinute();
        $rejectedPayment->save();
        PembayaranDenda::query()->create([
            'id_tagihan_denda' => $bill->getKey(),
            'id_anggota' => $member->getKey(),
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => 15000,
            'status_pembayaran' => 'menunggu_verifikasi',
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('member-fine-alert is-kuning', false)
            ->assertSee('Pembayaran sedang menunggu verifikasi Petugas.')
            ->assertDontSee('Pembayaran denda ditolak.');
    }

    public function test_member_can_view_and_open_only_their_own_notifications(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->unsignedBigInteger('id_anggota');
            $table->string('judul');
            $table->text('isi');
            $table->string('jenis')->default('info');
            $table->boolean('is_dibaca')->default(false);
            $table->string('tautan')->nullable();
            $table->timestamps();
        });

        $member = $this->createMember();
        $otherMember = $this->createMember();
        $notification = Notifikasi::query()->create([
            'id_anggota' => $member->getKey(),
            'judul' => 'Tagihan denda baru',
            'isi' => 'Silakan periksa tagihan Anda.',
            'jenis' => 'denda',
        ]);
        $otherNotification = Notifikasi::query()->create([
            'id_anggota' => $otherMember->getKey(),
            'judul' => 'Notifikasi privat',
            'isi' => 'Informasi anggota lain.',
            'jenis' => 'info',
        ]);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Tagihan denda baru')
            ->assertSee('1 belum dibaca');

        $this->get(route('anggota.notifikasi.index'))
            ->assertOk()
            ->assertSee('Tagihan denda baru')
            ->assertDontSee('Notifikasi privat');

        $this->post(route('anggota.notifikasi.buka', $notification->getKey()))
            ->assertRedirect(route('anggota.notifikasi.index'));

        $this->assertDatabaseHas('notifikasi', [
            'id_notifikasi' => $notification->getKey(),
            'is_dibaca' => true,
        ]);

        $this->post(route('anggota.notifikasi.buka', $otherNotification->getKey()))
            ->assertNotFound();
    }

    private function createFineTables(): void
    {
        Schema::create('tagihan_denda', function (Blueprint $table) {
            $table->id('id_tagihan_denda');
            $table->unsignedBigInteger('id_peminjaman');
            $table->unsignedBigInteger('id_anggota');
            $table->decimal('nominal_tagihan', 15, 2);
            $table->date('tgl_tagihan');
            $table->date('tgl_terakhir_diperbarui')->nullable();
            $table->timestamp('dibekukan_at')->nullable();
            $table->string('status_tagihan');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran_denda', function (Blueprint $table) {
            $table->id('id_pembayaran_denda');
            $table->unsignedBigInteger('id_tagihan_denda');
            $table->unsignedBigInteger('id_anggota');
            $table->string('metode_pembayaran');
            $table->decimal('jumlah_pembayaran', 15, 2);
            $table->string('bukti_pembayaran')->nullable();
            $table->string('status_pembayaran');
            $table->text('catatan_petugas')->nullable();
            $table->dateTime('waktu_pembayaran')->nullable();
            $table->dateTime('waktu_verifikasi')->nullable();
            $table->timestamps();
        });
    }

    private function createMember(): Anggota
    {
        static $number = 0;
        $number++;

        return Anggota::query()->create([
            'nama_anggota' => 'Anggota Pengujian',
            'email_anggota' => 'anggota'.$number.'@example.test',
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

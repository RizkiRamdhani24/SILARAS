<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Petugas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoanReturnWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
            $table->string('email_anggota')->nullable();
            $table->string('password')->nullable();
            $table->string('status_anggota')->default('aktif');
            $table->timestamps();
        });

        Schema::create('petugas', function (Blueprint $table) {
            $table->id('id_petugas');
            $table->string('nama_petugas');
            $table->string('email_petugas')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->string('judul_buku');
            $table->string('pengarang');
            $table->string('penerbit')->nullable();
            $table->string('kategori')->nullable();
            $table->string('isbn')->nullable();
            $table->string('rak')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('diarsipkan')->default(false);
            $table->timestamps();
        });

        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('id_peminjaman');
            $table->unsignedBigInteger('id_buku');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->date('tgl_peminjaman');
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->unsignedSmallInteger('durasi_peminjaman_hari')->nullable();
            $table->string('status_peminjaman');
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();
        });

        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id('id_pengembalian');
            $table->unsignedBigInteger('id_peminjaman')->index();
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->date('tgl_pengembalian')->nullable();
            $table->decimal('denda', 15, 2)->default(0);
            $table->string('kondisi_buku')->nullable();
            $table->string('status_pengembalian')->default('menunggu validasi');
            $table->string('status_denda')->default('tidak ada');
            $table->timestamps();
        });
        DB::statement("CREATE UNIQUE INDEX pengembalian_satu_aktif_per_peminjaman ON pengembalian (id_peminjaman) WHERE status_pengembalian <> 'dibatalkan'");

        Schema::create('tagihan_denda', function (Blueprint $table) {
            $table->id('id_tagihan_denda');
            $table->unsignedBigInteger('id_peminjaman')->unique();
            $table->unsignedBigInteger('id_anggota');
            $table->decimal('nominal_tagihan', 15, 2)->default(0);
            $table->decimal('nominal_terlambat', 15, 2)->default(0);
            $table->decimal('nominal_rusak', 15, 2)->default(0);
            $table->decimal('nominal_hilang', 15, 2)->default(0);
            $table->date('tgl_tagihan');
            $table->date('tgl_terakhir_diperbarui')->nullable();
            $table->dateTime('dibekukan_at')->nullable();
            $table->string('status_tagihan')->default('belum_lunas');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
        Schema::create('pembayaran_denda', function (Blueprint $table) {
            $table->id('id_pembayaran_denda');
            $table->unsignedBigInteger('id_tagihan_denda');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_petugas_verifikasi')->nullable();
            $table->string('metode_pembayaran');
            $table->decimal('jumlah_pembayaran', 15, 2);
            $table->string('bukti_pembayaran')->nullable();
            $table->string('status_pembayaran');
            $table->string('catatan_petugas')->nullable();
            $table->timestamp('waktu_pembayaran')->nullable();
            $table->timestamp('waktu_verifikasi')->nullable();
            $table->timestamps();
        });
        DB::statement("CREATE UNIQUE INDEX pembayaran_denda_satu_pending_per_tagihan ON pembayaran_denda (id_tagihan_denda) WHERE status_pembayaran = 'menunggu_verifikasi'");

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->string('nilai');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->unsignedBigInteger('id_anggota')->nullable();
            $table->unsignedBigInteger('id_petugas')->nullable();
            $table->string('judul');
            $table->text('isi');
            $table->string('jenis')->default('info');
            $table->boolean('is_dibaca')->default(false);
            $table->string('tautan')->nullable();
            $table->timestamps();
        });

        DB::table('pengaturan')->insert([
            ['kunci' => 'lama_peminjaman_hari', 'nilai' => '12'],
            ['kunci' => 'tarif_denda_per_hari', 'nilai' => '5000'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'notifikasi',
            'pengaturan',
            'pembayaran_denda',
            'tagihan_denda',
            'pengembalian',
            'peminjaman',
            'buku',
            'petugas',
            'anggota',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_member_can_request_the_same_book_until_the_three_loan_limit_is_reached(): void
    {
        $member = $this->createMember('Anggota Kuota');
        $book = $this->createBook();
        $this->actingAs($member, 'anggota');

        for ($index = 0; $index < 3; $index++) {
            $this->post(route('anggota.peminjaman.store'), [
                'id_buku' => $book->id_buku,
                'durasi_peminjaman_hari' => 14,
            ])->assertRedirect(route('anggota.peminjaman.index'));
        }

        $this->post(route('anggota.peminjaman.store'), [
            'id_buku' => $book->id_buku,
            'durasi_peminjaman_hari' => 7,
        ])->assertSessionHas('error', 'Kamu sudah mencapai batas maksimal 3 peminjaman berjalan.');

        $this->assertDatabaseCount('peminjaman', 3);
        $this->assertDatabaseHas('peminjaman', [
            'id_anggota' => $member->id_anggota,
            'id_buku' => $book->id_buku,
            'durasi_peminjaman_hari' => 14,
        ]);
    }

    public function test_member_with_unpaid_fine_sees_blocking_alert_and_cannot_submit_loan(): void
    {
        $member = $this->createMember('Anggota Menunggak');
        $book = $this->createBook();
        $loan = $this->createLoan($member, $this->createBook(), Peminjaman::STATUS_DIKEMBALIKAN);
        DB::table('tagihan_denda')->insert([
            'id_peminjaman' => $loan->id_peminjaman,
            'id_anggota' => $member->id_anggota,
            'nominal_tagihan' => 5000,
            'tgl_tagihan' => today()->toDateString(),
            'status_tagihan' => 'belum_lunas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($member, 'anggota');

        $message = 'Akun Anda sedang dibekukan karena masih memiliki tagihan denda. Harap bayar denda terlebih dahulu sebelum meminjam buku.';

        $this->from(route('anggota.buku.show', $book->id_buku))
            ->followingRedirects()
            ->post(route('anggota.peminjaman.store'), [
                'id_buku' => $book->id_buku,
                'durasi_peminjaman_hari' => 14,
            ])
            ->assertOk()
            ->assertSee('data-member-alert="'.$message.'"', false);

        $this->assertDatabaseCount('peminjaman', 1);
    }

    public function test_approval_rechecks_the_member_loan_limit_and_rejects_empty_stock_with_reason_and_notification(): void
    {
        $member = $this->createMember('Anggota Validasi');
        $petugas = $this->createPetugas();
        $book = $this->createBook();
        $pending = $this->createLoan($member, $book, Peminjaman::STATUS_MENUNGGU_VALIDASI);
        $pending->update(['durasi_peminjaman_hari' => 7]);
        $activeLoans = [
            $this->createLoan($member, $this->createBook(), Peminjaman::STATUS_DIPINJAM),
            $this->createLoan($member, $this->createBook(), Peminjaman::STATUS_TERLAMBAT),
            $this->createLoan($member, $this->createBook(), Peminjaman::STATUS_DIPINJAM),
        ];
        $this->actingAs($petugas, 'petugas');

        $this->patch(route('petugas.peminjaman.setujui', $pending->id_peminjaman))
            ->assertSessionHas('error', 'Anggota telah melewati batas maksimal 3 peminjaman berjalan.');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $pending->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_MENUNGGU_VALIDASI,
        ]);

        $pending->update(['status_peminjaman' => Peminjaman::STATUS_DIBATALKAN]);
        foreach ($activeLoans as $activeLoan) {
            $activeLoan->update(['status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN]);
        }
        $outOfStock = $this->createLoan($member, $book, Peminjaman::STATUS_MENUNGGU_VALIDASI);
        $book->update(['stock' => 0]);

        $this->patch(route('petugas.peminjaman.setujui', $outOfStock->id_peminjaman))
            ->assertSessionHas('error', 'Stok buku sudah habis.');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $outOfStock->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
            'alasan_penolakan' => 'Stok buku habis saat persetujuan.',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_anggota' => $member->id_anggota,
            'judul' => 'Pengajuan peminjaman ditolak',
        ]);
    }

    public function test_member_sees_only_own_loans_can_request_return_once_and_cannot_return_another_members_loan(): void
    {
        $member = $this->createMember('Anggota Pertama');
        $otherMember = $this->createMember('Anggota Kedua');
        $petugas = $this->createPetugas();
        $book = $this->createBook();
        $ownLoan = $this->createLoan($member, $book);
        $otherLoan = $this->createLoan($otherMember, $book);
        $this->actingAs($member, 'anggota');

        $this->get(route('anggota.peminjaman.index'))
            ->assertOk()
            ->assertSeeText('Buku Contoh')
            ->assertDontSeeText('#'.$otherLoan->id_peminjaman.' ·');

        $this->post(route('anggota.pengembalian.ajukan', $otherLoan->id_peminjaman))
            ->assertNotFound();

        $this->post(route('anggota.pengembalian.ajukan', $ownLoan->id_peminjaman))
            ->assertRedirect(route('anggota.pengembalian.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengembalian', [
            'id_peminjaman' => $ownLoan->id_peminjaman,
            'status_pengembalian' => Pengembalian::STATUS_MENUNGGU_VALIDASI,
            'tgl_pengembalian' => null,
        ]);
        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $ownLoan->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_petugas' => $petugas->id_petugas,
            'judul' => 'Pengajuan pengembalian baru',
        ]);

        $this->post(route('anggota.pengembalian.ajukan', $ownLoan->id_peminjaman))
            ->assertStatus(422);
        $this->assertDatabaseCount('pengembalian', 1);
    }

    public function test_member_loan_and_return_pages_and_return_request_work_without_notification_table(): void
    {
        $member = $this->createMember('Anggota Uji');
        $this->createPetugas();
        $loan = $this->createLoan($member, $this->createBook());
        Schema::drop('notifikasi');
        $this->actingAs($member, 'anggota');

        $this->get(route('anggota.peminjaman.index'))->assertOk();
        $this->get(route('anggota.pengembalian.index'))->assertOk();
        $this->post(route('anggota.pengembalian.ajukan', $loan->id_peminjaman))
            ->assertRedirect(route('anggota.pengembalian.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengembalian', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status_pengembalian' => Pengembalian::STATUS_MENUNGGU_VALIDASI,
        ]);
    }

    public function test_petugas_approval_uses_member_selected_duration_and_rejection_reason_is_recorded(): void
    {
        $member = $this->createMember('Anggota Uji');
        $petugas = $this->createPetugas();
        $book = $this->createBook();
        $pending = $this->createLoan($member, $book, Peminjaman::STATUS_MENUNGGU_VALIDASI);
        $pending->update(['durasi_peminjaman_hari' => 14]);
        $this->actingAs($petugas, 'petugas');

        $this->get(route('petugas.peminjaman.show', $pending->id_peminjaman))
            ->assertOk()
            ->assertSeeText('Tanggal persetujuan')
            ->assertSeeText('Tidak tersedia pada skema data saat ini');

        $this->patch(route('petugas.peminjaman.setujui', $pending->id_peminjaman))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $pending->id_peminjaman,
            'id_petugas' => $petugas->id_petugas,
            'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
        ]);
        $this->assertStringStartsWith(today()->addDays(14)->toDateString(), DB::table('peminjaman')
            ->where('id_peminjaman', $pending->id_peminjaman)
            ->value('tgl_jatuh_tempo'));

        $this->patch(route('petugas.peminjaman.setujui', $pending->id_peminjaman))
            ->assertSessionHas('error');

        $toReject = $this->createLoan($member, $book, Peminjaman::STATUS_MENUNGGU_VALIDASI);
        $this->patch(route('petugas.peminjaman.tolak', $toReject->id_peminjaman), [
            'alasan_penolakan' => 'Stok fisik buku tidak tersedia.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $toReject->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
            'alasan_penolakan' => 'Stok fisik buku tidak tersedia.',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_anggota' => $member->id_anggota,
            'judul' => 'Pengajuan peminjaman ditolak',
            'isi' => 'Pengajuan peminjaman #'.$toReject->id_peminjaman.' ditolak. Alasan: Stok fisik buku tidak tersedia.',
        ]);
    }

    public function test_petugas_confirms_member_return_and_freezes_final_late_fee(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 7)->startOfDay());
        $member = $this->createMember('Anggota Uji');
        $petugas = $this->createPetugas();
        $book = $this->createBook();
        $loan = $this->createLoan($member, $book, Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN, '2026-10-01');
        $return = Pengembalian::create([
            'id_peminjaman' => $loan->id_peminjaman,
            'status_pengembalian' => Pengembalian::STATUS_MENUNGGU_VALIDASI,
            'status_denda' => Pengembalian::DENDA_TIDAK_ADA,
        ]);

        Artisan::call('denda:sinkronisasi');
        $this->assertDatabaseHas('tagihan_denda', [
            'id_peminjaman' => $loan->id_peminjaman,
            'nominal_tagihan' => 185000,
        ]);

        $this->actingAs($petugas, 'petugas')
            ->patch(route('petugas.pengembalian.setujui', $return->id_pengembalian), [
                'kondisi_buku' => 'baik',
                'tgl_pengembalian' => '2026-11-05',
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('pengembalian', [
            'id_pengembalian' => $return->id_pengembalian,
            'id_petugas' => $petugas->id_petugas,
            'denda' => 175000,
            'status_pengembalian' => Pengembalian::STATUS_SELESAI,
        ]);
        $this->assertStringStartsWith('2026-11-05', DB::table('pengembalian')
            ->where('id_pengembalian', $return->id_pengembalian)
            ->value('tgl_pengembalian'));
        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
        ]);
        $this->assertDatabaseHas('buku', [
            'id_buku' => $book->id_buku,
            'stock' => 2,
        ]);

        $this->travelTo(now()->addDays(5));
        Artisan::call('denda:sinkronisasi');

        $this->assertDatabaseCount('tagihan_denda', 1);
        $this->assertDatabaseHas('tagihan_denda', [
            'id_peminjaman' => $loan->id_peminjaman,
            'nominal_tagihan' => 175000,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_anggota' => $member->id_anggota,
            'judul' => 'Pengembalian dikonfirmasi',
        ]);
    }

    public function test_member_can_cancel_return_request_resubmit_and_petugas_can_confirm_only_once(): void
    {
        $member = $this->createMember('Anggota Uji');
        $petugas = $this->createPetugas();
        $book = $this->createBook();
        $loan = $this->createLoan($member, $book, Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN);
        $return = Pengembalian::create([
            'id_peminjaman' => $loan->id_peminjaman,
            'status_pengembalian' => Pengembalian::STATUS_MENUNGGU_VALIDASI,
        ]);

        $this->actingAs($member, 'anggota')
            ->patch(route('anggota.pengembalian.batalkan', $return->id_pengembalian))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengembalian', [
            'id_pengembalian' => $return->id_pengembalian,
            'status_pengembalian' => Pengembalian::STATUS_DIBATALKAN,
        ]);
        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
        ]);

        $this->post(route('anggota.pengembalian.ajukan', $loan->id_peminjaman))
            ->assertRedirect(route('anggota.pengembalian.index'));
        $pengajuanUlang = Pengembalian::query()
            ->where('id_peminjaman', $loan->id_peminjaman)
            ->where('status_pengembalian', Pengembalian::STATUS_MENUNGGU_VALIDASI)
            ->firstOrFail();

        $this->assertDatabaseCount('pengembalian', 2);

        auth('anggota')->logout();
        $this->actingAs($petugas, 'petugas')
            ->patch(route('petugas.pengembalian.setujui', $pengajuanUlang->id_pengembalian), [
                'kondisi_buku' => 'baik',
                'tgl_pengembalian' => today()->toDateString(),
            ])->assertSessionHas('success');

        $this->assertDatabaseCount('tagihan_denda', 1);
        $this->patch(route('petugas.pengembalian.setujui', $pengajuanUlang->id_pengembalian), [
            'kondisi_buku' => 'baik',
        ])->assertStatus(422);

        $this->assertDatabaseCount('pengembalian', 2);
    }

    public function test_return_rejection_route_is_not_registered(): void
    {
        $this->assertFalse(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getName() === 'petugas.pengembalian.tolak')
        );
    }

    public function test_pending_loan_expires_and_is_recorded_with_reason(): void
    {
        $member = $this->createMember('Anggota Kedaluwarsa');
        $book = $this->createBook();
        $pending = $this->createLoan($member, $book, Peminjaman::STATUS_MENUNGGU_VALIDASI);
        $pending->created_at = now('Asia/Jakarta')->subDays(4);
        $pending->save();

        Artisan::call('denda:sinkronisasi');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $pending->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
            'alasan_penolakan' => 'Pengajuan kedaluwarsa setelah 3 hari tanpa diproses.',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_anggota' => $member->id_anggota,
            'judul' => 'Pengajuan peminjaman kedaluwarsa',
        ]);
    }

    public function test_scheduler_marks_overdue_loans_and_updates_the_running_fee(): void
    {
        $member = $this->createMember('Anggota Terlambat');
        $book = $this->createBook();
        $dueDate = now('Asia/Jakarta')->subDay()->toDateString();
        $loan = $this->createLoan($member, $book, Peminjaman::STATUS_DIPINJAM, $dueDate);

        Artisan::call('denda:sinkronisasi');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_TERLAMBAT,
        ]);
        $this->assertDatabaseHas('tagihan_denda', [
            'id_peminjaman' => $loan->id_peminjaman,
            'nominal_terlambat' => 5000,
            'status_tagihan' => 'belum_lunas',
        ]);
    }

    public function test_petugas_can_input_direct_return_once_and_cannot_return_completed_loan(): void
    {
        $member = $this->createMember('Anggota Uji');
        $petugas = $this->createPetugas();
        $book = $this->createBook();
        $loan = $this->createLoan($member, $book);
        $this->actingAs($petugas, 'petugas');

        $this->get(route('petugas.pengembalian.input'))
            ->assertOk()
            ->assertSeeText('Input Pengembalian')
            ->assertSeeText('Anggota Uji');

        $payload = [
            'id_peminjaman' => $loan->id_peminjaman,
            'tgl_pengembalian' => today()->toDateString(),
            'kondisi_buku' => 'baik',
        ];

        $this->post(route('petugas.pengembalian.store'), $payload)
            ->assertRedirect(route('petugas.pengembalian.index'));

        $this->assertDatabaseHas('pengembalian', [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_petugas' => $petugas->id_petugas,
            'status_pengembalian' => Pengembalian::STATUS_SELESAI,
        ]);
        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
        ]);

        $this->post(route('petugas.pengembalian.store'), $payload)
            ->assertStatus(422);
        $this->assertDatabaseCount('pengembalian', 1);
    }

    private function createMember(string $name): Anggota
    {
        return Anggota::create([
            'nama_anggota' => $name,
            'email_anggota' => str_replace(' ', '.', strtolower($name)).'@example.test',
            'status_anggota' => 'aktif',
        ]);
    }

    private function createPetugas(): Petugas
    {
        return Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas@example.test',
        ]);
    }

    private function createBook(): Buku
    {
        return Buku::create([
            'judul_buku' => 'Buku Contoh',
            'pengarang' => 'Penulis Contoh',
            'stock' => 1,
        ]);
    }

    private function createLoan(
        Anggota $member,
        Buku $book,
        string $status = Peminjaman::STATUS_DIPINJAM,
        string $dueDate = '2026-11-14'
    ): Peminjaman {
        return Peminjaman::create([
            'id_buku' => $book->id_buku,
            'id_anggota' => $member->id_anggota,
            'tgl_peminjaman' => today()->toDateString(),
            'tgl_jatuh_tempo' => $dueDate,
            'durasi_peminjaman_hari' => 7,
            'status_peminjaman' => $status,
        ]);
    }
}

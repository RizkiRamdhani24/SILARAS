<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\PembayaranDenda;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Petugas;
use App\Models\TagihanDenda;
use App\Services\DendaService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DendaPembayaranTest extends TestCase
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
            $table->string('status_pengembalian');
            $table->string('status_denda');
            $table->timestamps();
        });
        DB::statement("CREATE UNIQUE INDEX pengembalian_satu_aktif_per_peminjaman ON pengembalian (id_peminjaman) WHERE status_pengembalian <> 'dibatalkan'");

        Schema::create('tagihan_denda', function (Blueprint $table) {
            $table->id('id_tagihan_denda');
            $table->unsignedBigInteger('id_peminjaman')->unique();
            $table->unsignedBigInteger('id_anggota');
            $table->decimal('nominal_tagihan', 15, 2);
            $table->decimal('nominal_terlambat', 15, 2)->default(0);
            $table->decimal('nominal_rusak', 15, 2)->default(0);
            $table->decimal('nominal_hilang', 15, 2)->default(0);
            $table->date('tgl_tagihan');
            $table->date('tgl_terakhir_diperbarui')->nullable();
            $table->dateTime('dibekukan_at')->nullable();
            $table->string('status_tagihan', 20);
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
            $table->string('status_pembayaran', 30);
            $table->text('catatan_petugas')->nullable();
            $table->dateTime('waktu_pembayaran')->nullable();
            $table->dateTime('waktu_verifikasi')->nullable();
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

    public function test_member_must_submit_one_pending_full_qris_payment_with_proof(): void
    {
        Storage::fake('local');
        $member = $this->createMember('Anggota Pertama');
        $bill = $this->createBill($member);
        $this->actingAs($member, 'anggota');

        $this->post(route('anggota.denda.pembayaran.store', $bill), [
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => '100.00',
            'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
        ])->assertRedirect();

        $payment = PembayaranDenda::query()->firstOrFail();
        $this->assertSame('menunggu_verifikasi', $payment->status_pembayaran);
        $this->assertSame('qris', $payment->metode_pembayaran);
        $this->assertNotNull($payment->bukti_pembayaran);
        Storage::disk('local')->assertExists($payment->bukti_pembayaran);
        $this->assertSame(10000, app(DendaService::class)->sisaTagihanDalamSen($bill->fresh()));
        $this->assertSame('belum_lunas', $bill->fresh()->status_tagihan);

        $this->post(route('anggota.denda.pembayaran.store', $bill), [
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => '0',
            'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
        ])->assertSessionHasErrors('jumlah_pembayaran');

        $this->post(route('anggota.denda.pembayaran.store', $bill), [
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => '100.01',
            'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
        ])->assertSessionHasErrors('jumlah_pembayaran');

        $this->post(route('anggota.denda.pembayaran.store', $bill), [
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => '100.00',
            'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
        ])->assertSessionHasErrors('metode_pembayaran');

        $this->assertDatabaseCount('pembayaran_denda', 1);
    }

    public function test_qris_image_is_private_and_available_only_to_the_bill_owner(): void
    {
        Storage::fake('local');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/iwAAAABJRU5ErkJggg==');
        Storage::disk('local')->put('qris/qris.png', $png);

        $member = $this->createMember('Anggota Pertama');
        $otherMember = $this->createMember('Anggota Kedua');
        $bill = $this->createBill($member);
        $otherBill = $this->createBill($otherMember);

        $this->get(route('anggota.denda.qris', $bill))
            ->assertRedirect(route('login'));

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.denda.show', $bill))
            ->assertOk()
            ->assertSee('Bayar via QRIS')
            ->assertSee('src="'.route('anggota.denda.qris', $bill->id_tagihan_denda, false).'"', false)
            ->assertSee('name="jumlah_pembayaran" value="100.00"', false)
            ->assertSee('readonly', false)
            ->assertSee('accept=".jpg,.jpeg,.png,image/jpeg,image/png"', false);

        $response = $this->get(route('anggota.denda.qris', $bill))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $body = $response->getContent();
        $this->assertSame($png, $body);
        $this->assertSame('image/png', getimagesizefromstring($body)['mime']);
        $this->assertSame((string) strlen($png), $response->headers->get('Content-Length'));

        $this->get(route('anggota.denda.qris', $otherBill))
            ->assertForbidden();
    }

    public function test_missing_qris_file_is_logged_and_returns_a_clear_404(): void
    {
        Storage::fake('local');
        Log::spy();

        $member = $this->createMember('Anggota Pertama');
        $bill = $this->createBill($member);

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.denda.qris', $bill))
            ->assertNotFound();

        Log::shouldHaveReceived('error')
            ->once()
            ->with('QRIS image file not found.', [
                'path' => Storage::disk('local')->path('qris/qris.png'),
                'tagihan_id' => $bill->id_tagihan_denda,
            ]);
    }

    public function test_member_cannot_upload_a_non_jpg_or_png_payment_proof(): void
    {
        $member = $this->createMember('Anggota Pertama');
        $bill = $this->createBill($member);

        $this->actingAs($member, 'anggota')
            ->post(route('anggota.denda.pembayaran.store', $bill), [
                'metode_pembayaran' => 'qris',
                'jumlah_pembayaran' => '100.00',
                'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseCount('pembayaran_denda', 0);
    }

    public function test_staff_can_open_uploaded_payment_proof_using_a_same_origin_link(): void
    {
        Storage::fake('local');
        $member = $this->createMember('Anggota Pertama');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);
        $proofPath = 'bukti-pembayaran/bukti.png';
        Storage::disk('local')->put($proofPath, 'proof image data');
        $payment = $this->createPayment($bill, $member, '100.00');
        $payment->update(['bukti_pembayaran' => $proofPath]);
        $this->actingAs($staff, 'petugas');

        $this->get(route('petugas.denda.show', $bill))
            ->assertOk()
            ->assertSee(
                'href="'.route('petugas.denda.pembayaran.bukti', $payment->id_pembayaran_denda, false).'"',
                false
            );

        $this->get(route('petugas.denda.pembayaran.bukti', $payment))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename=bukti.png');
    }

    public function test_member_cannot_submit_payment_for_another_members_bill(): void
    {
        $member = $this->createMember('Anggota Pertama');
        $otherMember = $this->createMember('Anggota Kedua');
        $bill = $this->createBill($otherMember);
        $this->actingAs($member, 'anggota');

        $this->post(route('anggota.denda.pembayaran.store', $bill), [
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => '10.00',
            'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
        ])->assertForbidden();

        $this->assertDatabaseCount('pembayaran_denda', 0);
    }

    public function test_inactive_member_cannot_access_denda_payment_routes(): void
    {
        $member = $this->createMember('Anggota Nonaktif');
        $member->update(['status_anggota' => Anggota::STATUS_NONAKTIF]);
        $bill = $this->createBill($member);
        $this->actingAs($member, 'anggota');

        $this->get(route('anggota.denda.show', $bill))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('pembayaran_denda', 0);
    }

    public function test_petugas_verification_requires_full_amount_and_cannot_repeat(): void
    {
        $member = $this->createMember('Anggota Uji');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);
        $return = $this->createReturn($bill);
        $fullPayment = $this->createPayment($bill, $member, '100.00');
        $this->actingAs($staff, 'petugas');

        $this->patch(route('petugas.denda.pembayaran.verifikasi', $fullPayment), [
            'status_pembayaran' => 'diverifikasi',
        ])->assertRedirect();

        $this->assertSame(0, app(DendaService::class)->sisaTagihanDalamSen($bill->fresh()));
        $this->assertSame('lunas', $bill->fresh()->status_tagihan);
        $this->assertSame(Pengembalian::DENDA_LUNAS, $return->fresh()->status_denda);

        $this->patch(route('petugas.denda.pembayaran.verifikasi', $fullPayment), [
            'status_pembayaran' => 'diverifikasi',
        ])->assertSessionHasErrors('status_pembayaran');
    }

    public function test_rejected_payment_does_not_reduce_the_balance(): void
    {
        $member = $this->createMember('Anggota Uji');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);
        $payment = $this->createPayment($bill, $member, '25.00');
        $this->actingAs($staff, 'petugas');

        $this->patch(route('petugas.denda.pembayaran.verifikasi', $payment), [
            'status_pembayaran' => 'ditolak',
            'catatan_petugas' => 'Bukti pembayaran tidak sesuai.',
        ])->assertRedirect();

        $this->assertSame(10000, app(DendaService::class)->sisaTagihanDalamSen($bill->fresh()));
        $this->assertSame('belum_lunas', $bill->fresh()->status_tagihan);
        $this->assertSame('ditolak', $payment->fresh()->status_pembayaran);
    }

    public function test_one_pending_payment_per_bill_is_enforced_by_controller(): void
    {
        Storage::fake('local');
        $member = $this->createMember('Anggota Uji');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);
        $this->actingAs($member, 'anggota')
            ->post(route('anggota.denda.pembayaran.store', $bill), [
                'metode_pembayaran' => 'qris',
                'jumlah_pembayaran' => '100.00',
                'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
            ])->assertRedirect();

        $this->post(route('anggota.denda.pembayaran.store', $bill), [
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => '100.00',
            'bukti_pembayaran' => UploadedFile::fake()->create('qris.png', 10, 'image/png'),
        ])->assertSessionHasErrors('metode_pembayaran');

        $this->assertDatabaseCount('pembayaran_denda', 1);
        auth('anggota')->logout();
        $this->actingAs($staff, 'petugas');

        $this->patch(route('petugas.denda.pembayaran.verifikasi', PembayaranDenda::query()->firstOrFail()), [
            'status_pembayaran' => 'diverifikasi',
        ])->assertRedirect();
        $this->assertSame(0, app(DendaService::class)->sisaTagihanDalamSen($bill->fresh()));
    }

    public function test_petugas_can_record_full_cash_payment_as_paid(): void
    {
        $member = $this->createMember('Anggota Tunai');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);

        $this->actingAs($staff, 'petugas')
            ->post(route('petugas.denda.pembayaran.tunai', $bill))
            ->assertRedirect();

        $payment = PembayaranDenda::query()->firstOrFail();
        $this->assertSame('tunai', $payment->metode_pembayaran);
        $this->assertSame('diverifikasi', $payment->status_pembayaran);
        $this->assertSame((int) $staff->id_petugas, (int) $payment->id_petugas_verifikasi);
        $this->assertSame('lunas', $bill->fresh()->status_tagihan);
    }

    public function test_zero_value_legacy_payment_is_not_automatically_processed(): void
    {
        $member = $this->createMember('Anggota Uji');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);
        $legacyPayment = $this->createPayment($bill, $member, '0.00');
        $this->actingAs($staff, 'petugas');

        $this->patch(route('petugas.denda.pembayaran.verifikasi', $legacyPayment), [
            'status_pembayaran' => 'diverifikasi',
        ])->assertSessionHasErrors('status_pembayaran');

        $this->assertSame('menunggu_verifikasi', $legacyPayment->fresh()->status_pembayaran);
        $this->assertSame(10000, app(DendaService::class)->sisaTagihanDalamSen($bill->fresh()));
    }

    public function test_payment_and_bill_updates_roll_back_together_when_status_sync_fails(): void
    {
        $member = $this->createMember('Anggota Uji');
        $staff = $this->createStaff();
        $bill = $this->createBill($member);
        $payment = $this->createPayment($bill, $member, '100.00');

        TagihanDenda::updating(static function (): void {
            throw new RuntimeException('Simulated status synchronization failure.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($staff, 'petugas')
                ->patch(route('petugas.denda.pembayaran.verifikasi', $payment), [
                    'status_pembayaran' => 'diverifikasi',
                ]);
            $this->fail('The simulated failure should be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated status synchronization failure.', $exception->getMessage());
        }

        $this->assertSame('menunggu_verifikasi', $payment->fresh()->status_pembayaran);
        $this->assertSame('belum_lunas', $bill->fresh()->status_tagihan);
    }

    public function test_late_fee_uses_the_asia_jakarta_operational_date(): void
    {
        $this->travelTo(Carbon::parse('2026-11-07 17:30:00', 'UTC'));
        $member = $this->createMember('Anggota Uji');
        $loan = $this->createLoan($member, '2026-11-07');

        $this->assertSame(
            5000.0,
            app(DendaService::class)->hitungNominalDenda($loan)
        );
    }

    private function createMember(string $name): Anggota
    {
        return Anggota::create([
            'nama_anggota' => $name,
            'email_anggota' => str_replace(' ', '.', strtolower($name)).'@example.test',
            'status_anggota' => 'aktif',
        ]);
    }

    private function createStaff(): Petugas
    {
        return Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas@example.test',
        ]);
    }

    private function createLoan(Anggota $member, string $dueDate = '2026-11-07'): Peminjaman
    {
        $book = Buku::create([
            'judul_buku' => 'Buku Denda',
            'pengarang' => 'Penulis Uji',
            'stock' => 1,
        ]);

        return Peminjaman::create([
            'id_buku' => $book->id_buku,
            'id_anggota' => $member->id_anggota,
            'tgl_peminjaman' => '2026-10-01',
            'tgl_jatuh_tempo' => $dueDate,
            'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
        ]);
    }

    private function createBill(Anggota $member, string $amount = '100.00'): TagihanDenda
    {
        $loan = $this->createLoan($member);

        return TagihanDenda::create([
            'id_peminjaman' => $loan->id_peminjaman,
            'id_anggota' => $member->id_anggota,
            'nominal_tagihan' => $amount,
            'tgl_tagihan' => '2026-11-07',
            'status_tagihan' => 'belum_lunas',
        ]);
    }

    private function createReturn(TagihanDenda $bill): Pengembalian
    {
        return Pengembalian::create([
            'id_peminjaman' => $bill->id_peminjaman,
            'tgl_pengembalian' => '2026-11-07',
            'denda' => $bill->nominal_tagihan,
            'status_pengembalian' => Pengembalian::STATUS_SELESAI,
            'status_denda' => Pengembalian::DENDA_BELUM_DIBAYAR,
        ]);
    }

    private function createPayment(
        TagihanDenda $bill,
        Anggota $member,
        string $amount,
        string $status = 'menunggu_verifikasi'
    ): PembayaranDenda {
        return PembayaranDenda::create([
            'id_tagihan_denda' => $bill->id_tagihan_denda,
            'id_anggota' => $member->id_anggota,
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => $amount,
            'status_pembayaran' => $status,
        ]);
    }
}

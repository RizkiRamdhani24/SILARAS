<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PetugasLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('email_anggota')->unique();
            $table->string('password');
            $table->rememberToken();
        });

        Schema::create('petugas', function (Blueprint $table) {
            $table->id('id_petugas');
            $table->string('nama_petugas');
            $table->string('email_petugas')->unique();
            $table->string('shift')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Dashboard petugas kini membaca data asli, jadi tabel berikut harus ada.
        Schema::create('buku', function (Blueprint $table) {
            $table->id('id_buku');
            $table->string('judul_buku');
            $table->string('pengarang')->nullable();
            $table->string('kategori')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();
        });

        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('id_peminjaman');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_buku');
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
            $table->string('status_pengembalian')->nullable();
            $table->string('status_denda')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('petugas');
        Schema::dropIfExists('anggota');

        parent::tearDown();
    }

    public function test_petugas_login_page_shows_the_expected_fields_and_registration_link(): void
    {
        $this->get(route('petugas.login'))
            ->assertOk()
            ->assertSee('Masuk Petugas')
            ->assertSee('Email Petugas')
            ->assertSee('Masukkan email petugas')
            ->assertSee('Kata Sandi')
            ->assertSee('Masukkan kata sandi')
            ->assertSee('Daftar Petugas')
            ->assertDontSee('shift');
    }

    public function test_petugas_can_sign_in_with_a_hashed_password(): void
    {
        $petugas = Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas.ujian@example.test',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->post(route('petugas.login'), [
            'email_petugas' => 'PETUGAS.UJIAN@EXAMPLE.TEST',
            'password' => 'Sandi-aman-123',
        ])->assertRedirect(route('petugas.dashboard'));

        $this->assertAuthenticatedAs($petugas, 'petugas');
        $this->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSeeText('Dashboard Petugas');
    }

    public function test_petugas_login_rejects_an_invalid_password(): void
    {
        Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas.ujian@example.test',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->from(route('petugas.login'))
            ->post(route('petugas.login'), [
                'email_petugas' => 'petugas.ujian@example.test',
                'password' => 'kata-sandi-salah',
            ])
            ->assertRedirect(route('petugas.login'))
            ->assertSessionHasErrors('email_petugas');
    }
}

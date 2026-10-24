<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
            $table->string('email_anggota')->unique();
            $table->string('no_telp')->nullable();
            $table->string('password');
            $table->string('status_anggota')->default('aktif');
            $table->rememberToken();
            $table->timestamps();
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('anggota');
        Schema::dropIfExists('petugas');

        parent::tearDown();
    }

    public function test_member_can_sign_in_with_a_hashed_password(): void
    {
        $anggota = Anggota::create([
            'nama_anggota' => 'Anggota Uji',
            'email_anggota' => 'anggota.ujian@example.test',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->post(route('login'), [
            'email' => 'ANGGOTA.UJIAN@EXAMPLE.TEST',
            'password' => 'Sandi-aman-123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($anggota, 'anggota');
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Akses Pustaka Akademik Pintar');
    }

    public function test_member_sign_in_rejects_an_invalid_password(): void
    {
        Anggota::create([
            'nama_anggota' => 'Anggota Uji',
            'email_anggota' => 'anggota.ujian@example.test',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'anggota.ujian@example.test',
                'password' => 'kata-sandi-salah',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_staff_account_cannot_sign_in_through_the_member_login(): void
    {
        Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas.ujian@example.test',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'petugas.ujian@example.test',
                'password' => 'Sandi-aman-123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('anggota');
        $this->assertGuest('petugas');
    }
}

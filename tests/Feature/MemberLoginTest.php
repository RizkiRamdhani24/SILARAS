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
        ])->assertRedirect(route('anggota.dashboard'));

        $this->assertAuthenticatedAs($anggota, 'anggota');
        $this->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSeeText('Dashboard Anggota');
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

    public function test_member_can_view_profile_from_the_header_navigation(): void
    {
        $anggota = Anggota::create([
            'nama_anggota' => 'Anggota Profil',
            'email_anggota' => 'profil.anggota@example.test',
            'no_telp' => '08123456789',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->actingAs($anggota, 'anggota')
            ->get(route('anggota.profile'))
            ->assertOk()
            ->assertSeeText('Anggota Profil')
            ->assertSee('profil.anggota@example.test', false)
            ->assertSee('08123456789', false)
            ->assertSee(route('anggota.profile'), false);
    }

    public function test_member_can_update_own_name_and_phone_without_changing_email(): void
    {
        $anggota = Anggota::create([
            'nama_anggota' => 'Nama Lama',
            'email_anggota' => 'google.account@example.test',
            'no_telp' => '08123456789',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->actingAs($anggota, 'anggota')
            ->from(route('anggota.profile'))
            ->put(route('anggota.profile.update'), [
                'nama_anggota' => 'Nama Terbaru',
                'no_telp' => '+62 812-3456-7890',
                'email_anggota' => 'attacker@example.test',
            ])
            ->assertRedirect(route('anggota.profile'))
            ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $anggota->refresh();
        $this->assertSame('Nama Terbaru', $anggota->nama_anggota);
        $this->assertSame('+62 812-3456-7890', $anggota->no_telp);
        $this->assertSame('google.account@example.test', $anggota->email_anggota);
    }

    public function test_member_profile_rejects_invalid_phone_and_other_role_cannot_update_it(): void
    {
        $anggota = Anggota::create([
            'nama_anggota' => 'Anggota Uji',
            'email_anggota' => 'anggota.ujian@example.test',
            'no_telp' => '08123456789',
            'password' => Hash::make('Sandi-aman-123'),
        ]);
        $petugas = Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas.ujian@example.test',
            'password' => Hash::make('Sandi-aman-123'),
        ]);

        $this->actingAs($anggota, 'anggota')
            ->from(route('anggota.profile'))
            ->put(route('anggota.profile.update'), [
                'nama_anggota' => 'Nama Tidak Tersimpan',
                'no_telp' => '123',
            ])
            ->assertRedirect(route('anggota.profile'))
            ->assertSessionHasErrors('no_telp');

        $this->assertSame('Anggota Uji', $anggota->fresh()->nama_anggota);
        $this->actingAs($petugas, 'petugas')
            ->put(route('anggota.profile.update'), [
                'nama_anggota' => 'Nama Petugas',
                'no_telp' => '08123456789',
            ])
            ->assertForbidden();
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

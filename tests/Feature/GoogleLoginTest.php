<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
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

    public function test_member_login_page_offers_google_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk dengan Google')
            ->assertSee(route('auth.google.redirect'));
    }

    public function test_member_can_start_google_sign_in(): void
    {
        Socialite::fake('google', GoogleUser::fake());

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_verified_google_user_can_sign_in_and_get_an_account(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'email' => 'ANGGOTA.BARU@example.test',
            'email_verified' => true,
            'name' => 'Anggota Google',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $anggota = Anggota::where('email_anggota', 'anggota.baru@example.test')->firstOrFail();
        $this->assertSame('Anggota Google', $anggota->nama_anggota);
        $this->assertAuthenticatedAs($anggota, 'anggota');
    }

    public function test_existing_member_can_sign_in_with_a_verified_google_email(): void
    {
        $anggota = Anggota::create([
            'nama_anggota' => 'Anggota Terdaftar',
            'email_anggota' => 'anggota@example.test',
            'password' => 'password-hanya-untuk-uji',
        ]);

        Socialite::fake('google', GoogleUser::fake([
            'email' => 'ANGGOTA@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($anggota, 'anggota');
        $this->assertDatabaseCount('anggota', 1);
    }

    public function test_google_sign_in_rejects_unverified_email(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'email' => 'unverified@example.test',
            'email_verified' => false,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertDatabaseMissing('anggota', ['email_anggota' => 'unverified@example.test']);
        $this->assertGuest('anggota');
    }

    public function test_inactive_member_cannot_sign_in_with_google(): void
    {
        $anggota = Anggota::create([
            'nama_anggota' => 'Anggota Nonaktif',
            'email_anggota' => 'anggota.nonaktif@example.test',
            'password' => 'password-hanya-untuk-uji',
            'status_anggota' => Anggota::STATUS_NONAKTIF,
        ]);

        Socialite::fake('google', GoogleUser::fake([
            'email' => $anggota->email_anggota,
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest('anggota');
    }

    public function test_petugas_email_cannot_be_used_for_member_google_sign_in(): void
    {
        Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas@example.test',
            'password' => 'password-hanya-untuk-uji',
        ]);

        Socialite::fake('google', GoogleUser::fake([
            'email' => 'petugas@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertDatabaseMissing('anggota', ['email_anggota' => 'petugas@example.test']);
        $this->assertGuest('anggota');
    }
}

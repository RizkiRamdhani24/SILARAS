<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
            $table->string('email_anggota')->unique();
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

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('petugas');
        Schema::dropIfExists('anggota');

        parent::tearDown();
    }

    public function test_member_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();
        $member = Anggota::create([
            'nama_anggota' => 'Anggota Uji',
            'email_anggota' => 'anggota@example.test',
            'password' => 'KataLama-123',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $member->email_anggota])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        $token = $this->resetTokenSentTo($member);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $member->email_anggota,
        ]))->assertOk()->assertSee('Atur ulang kata sandi');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $member->email_anggota,
            'password' => 'KataBaru-123',
            'password_confirmation' => 'KataBaru-123',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('KataBaru-123', $member->fresh()->password));
    }

    public function test_petugas_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();
        $staff = Petugas::create([
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas@example.test',
            'password' => 'KataLama-123',
        ]);

        $this->get(route('petugas.login'))
            ->assertOk()
            ->assertSee(route('petugas.password.request'), false);

        $this->from(route('petugas.password.request'))
            ->post(route('petugas.password.email'), ['email' => $staff->email_petugas])
            ->assertRedirect(route('petugas.password.request'))
            ->assertSessionHas('status');

        $token = $this->resetTokenSentTo($staff);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $staff->email_petugas,
            'password' => 'KataBaru-456',
            'password_confirmation' => 'KataBaru-456',
        ])->assertRedirect(route('petugas.login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('KataBaru-456', $staff->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_request_confirmation(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'tidak-ada@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status')
            ->assertSessionDoesntHaveErrors();

        Notification::assertNothingSent();
    }

    private function resetTokenSentTo(Anggota|Petugas $user): string
    {
        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertIsString($token);

        return $token;
    }
}

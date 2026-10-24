<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PetugasRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('email_anggota')->unique();
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
        Schema::dropIfExists('petugas');
        Schema::dropIfExists('anggota');

        parent::tearDown();
    }

    public function test_petugas_registration_page_has_the_three_expected_fields(): void
    {
        $this->get(route('petugas.register'))
            ->assertOk()
            ->assertSee('NAMA PETUGAS')
            ->assertSee('EMAIL PETUGAS')
            ->assertSee('KATA SANDI')
            ->assertDontSee('shift');
    }

    public function test_petugas_can_register_without_shift_and_password_is_hashed(): void
    {
        $this->post(route('petugas.register'), [
            'nama_petugas' => 'Petugas Uji',
            'email_petugas' => 'petugas.ujian@example.test',
            'password' => 'Sandi-aman-123',
        ])->assertRedirect(route('petugas.daftar-sukses'));

        $petugas = Petugas::where('email_petugas', 'petugas.ujian@example.test')->firstOrFail();

        $this->assertNull($petugas->shift);
        $this->assertNotSame('Sandi-aman-123', $petugas->password);
        $this->assertTrue(Hash::check('Sandi-aman-123', $petugas->password));
        $this->assertAuthenticatedAs($petugas, 'petugas');
    }
}

<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Akses Pustaka Akademik Pintar')
            ->assertSee('Cari Buku')
            ->assertSee('Jelajahi Jenis Koleksi')
            ->assertSee('Login')
            ->assertSee(route('login'))
            ->assertDontSee(route('petugas.login'));
    }
}

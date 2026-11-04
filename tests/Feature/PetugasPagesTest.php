<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Petugas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PetugasPagesTest extends TestCase
{
    private Petugas $petugas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Schema::create('anggota', function (Blueprint $t) {
            $t->id('id_anggota');
            $t->string('nama_anggota');
            $t->string('email_anggota')->unique();
            $t->string('no_telp')->nullable();
            $t->string('password');
            $t->string('status_anggota')->default('aktif');
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('petugas', function (Blueprint $t) {
            $t->id('id_petugas');
            $t->string('nama_petugas');
            $t->string('email_petugas')->unique();
            $t->string('shift')->nullable();
            $t->string('password');
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('buku', function (Blueprint $t) {
            $t->id('id_buku');
            $t->string('judul_buku');
            $t->string('penerbit')->nullable();
            $t->string('pengarang');
            $t->unsignedInteger('stock')->default(0);
            $t->string('kategori')->nullable();
            $t->string('isbn')->nullable();
            $t->string('rak')->nullable();
            $t->string('cover')->nullable();
            $t->timestamps();
        });
        Schema::create('peminjaman', function (Blueprint $t) {
            $t->id('id_peminjaman');
            $t->unsignedBigInteger('id_anggota');
            $t->unsignedBigInteger('id_buku');
            $t->unsignedBigInteger('id_petugas')->nullable();
            $t->date('tgl_peminjaman');
            $t->date('tgl_jatuh_tempo')->nullable();
            $t->string('status_peminjaman');
            $t->timestamps();
        });
        Schema::create('pengembalian', function (Blueprint $t) {
            $t->id('id_pengembalian');
            $t->unsignedBigInteger('id_peminjaman');
            $t->unsignedBigInteger('id_petugas')->nullable();
            $t->date('tgl_pengembalian')->nullable();
            $t->decimal('denda', 10, 2)->default(0);
            $t->string('kondisi_buku')->nullable();
            $t->string('status_pengembalian')->nullable();
            $t->string('status_denda')->nullable();
            $t->timestamps();
        });
        Schema::create('pengaturan', function (Blueprint $t) {
            $t->string('kunci')->primary();
            $t->string('nilai');
            $t->string('keterangan')->nullable();
            $t->timestamps();
        });

        $this->petugas = Petugas::create(['nama_petugas' => 'Siti Aminah', 'email_petugas' => 'siti@example.test', 'password' => 'rahasia123']);
        $ani = Anggota::create(['nama_anggota' => 'Ani Lestari', 'email_anggota' => 'ani@example.test', 'password' => 'x', 'status_anggota' => 'aktif']);
        Anggota::create(['nama_anggota' => 'Bima Nonaktif', 'email_anggota' => 'bima@example.test', 'password' => 'x', 'status_anggota' => 'nonaktif']);
        $b1 = Buku::create(['judul_buku' => 'Pemrograman Laravel Dasar', 'pengarang' => 'Penulis A', 'penerbit' => 'Penerbit X', 'kategori' => 'Teknologi', 'stock' => 1, 'isbn' => '111', 'rak' => 'T-01']);
        $b2 = Buku::create(['judul_buku' => 'Sejarah Nusantara', 'pengarang' => 'Penulis B', 'stock' => 5, 'kategori' => 'Sejarah']);

        DB::table('pengaturan')->insert(['kunci' => 'tarif_denda_per_hari', 'nilai' => '1500']);

        Peminjaman::create(['id_anggota' => $ani->id_anggota, 'id_buku' => $b1->id_buku, 'tgl_peminjaman' => now()->toDateString(), 'tgl_jatuh_tempo' => now()->addDays(7)->toDateString(), 'status_peminjaman' => 'menunggu validasi']);
        $lama = Peminjaman::create(['id_anggota' => $ani->id_anggota, 'id_buku' => $b2->id_buku, 'id_petugas' => $this->petugas->id_petugas, 'tgl_peminjaman' => now()->subDays(20)->toDateString(), 'tgl_jatuh_tempo' => now()->subDays(13)->toDateString(), 'status_peminjaman' => 'pengembalian diajukan']);
        Pengembalian::create(['id_peminjaman' => $lama->id_peminjaman, 'tgl_pengembalian' => now()->subDays(10)->toDateString(), 'denda' => 4500, 'status_pengembalian' => 'selesai', 'status_denda' => 'belum dibayar', 'kondisi_buku' => 'baik', 'id_petugas' => $this->petugas->id_petugas]);
    }

    private function halaman(string $uri)
    {
        return $this->actingAs($this->petugas, 'petugas')->get($uri);
    }

    public function test_semua_halaman_petugas_terender(): void
    {
        foreach (['dashboard', 'buku', 'anggota', 'peminjaman', 'pengembalian', 'denda', 'transaksi'] as $h) {
            $this->halaman("/petugas/$h")->assertOk()->assertSee('Siti Aminah');
        }
    }

    public function test_tidak_ada_data_contoh(): void
    {
        foreach (['dashboard', 'buku', 'anggota', 'peminjaman', 'pengembalian', 'denda', 'transaksi'] as $h) {
            $this->halaman("/petugas/$h")->assertOk()
                ->assertDontSee('Arya Pratama')->assertDontSee('Eom Seonghyeon')->assertDontSee('Budi Santoso')->assertDontSee('NIM');
        }
    }

    public function test_data_dari_database_tampil(): void
    {
        $this->halaman('/petugas/buku')->assertSee('Pemrograman Laravel Dasar')->assertSee('Penulis B');
        $this->halaman('/petugas/anggota')->assertSee('Ani Lestari')->assertSee('Bima Nonaktif')->assertSee('Rp 4.500');
        $this->halaman('/petugas/peminjaman')->assertSee('Ani Lestari')->assertSee('Pemrograman Laravel Dasar');
        $this->halaman('/petugas/denda')->assertSee('Rp 4.500')->assertSee('Rp 1.500')->assertSee('Sejarah Nusantara');
        $this->halaman('/petugas/transaksi')->assertSee('PMJ-0001')->assertSee('KMB-0001');
        $this->halaman('/petugas/dashboard')->assertSee('Ani Lestari');
    }

    public function test_pengembalian_menampilkan_riwayat_validasi_petugas(): void
    {
        $this->halaman('/petugas/pengembalian')->assertSee('KMB-0001')->assertSee('Sejarah Nusantara');
    }

    public function test_halaman_tetap_dilindungi(): void
    {
        $this->get('/petugas/denda')->assertRedirect('/login');
    }
}

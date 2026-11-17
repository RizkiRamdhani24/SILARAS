<?php

namespace Tests\Feature;

use App\Exceptions\LogAktivitasException;
use App\Models\Ebook;
use App\Models\LogAktivitas;
use App\Models\Petugas;
use App\Models\UnduhanEbook;
use App\Services\LogAktivitasService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class EbookFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anggota', function (Blueprint $table) {
            $table->id('id_anggota');
            $table->string('nama_anggota');
            $table->string('email_anggota')->unique();
            $table->string('password')->nullable();
            $table->string('status_anggota')->default('aktif');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('petugas', function (Blueprint $table) {
            $table->id('id_petugas');
            $table->string('nama_petugas');
            $table->string('email_petugas')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('ebook', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('penulis');
            $table->text('cover')->nullable();
            $table->text('link_drive');
            $table->timestamps();
        });

        Schema::create('unduhan_ebook', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('ebook_id')->nullable();
            $table->string('judul_ebook');
            $table->string('penulis_ebook');
            $table->timestamp('waktu_unduh');
            $table->unique(['anggota_id', 'ebook_id']);
        });

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('petugas_id');
            $table->string('nama_petugas');
            $table->string('aksi');
            $table->string('objek');
            $table->string('keterangan')->nullable();
            $table->timestamp('waktu');
        });

    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('log_aktivitas');
        Schema::dropIfExists('unduhan_ebook');
        Schema::dropIfExists('ebook');
        Schema::dropIfExists('petugas');
        Schema::dropIfExists('anggota');

        parent::tearDown();
    }

    public function test_member_download_is_recorded_once_and_redirects_without_exposing_drive_link_in_html(): void
    {
        Storage::fake('s3');
        $member = $this->createMember('Anggota Ebook');
        $otherMember = $this->createMember('Anggota Kedua');
        $ebook = $this->createEbook('Panduan SILARAS', 'Penulis Ebook');

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.ebook.index'))
            ->assertOk()
            ->assertSee('Panduan SILARAS')
            ->assertSee('Penulis Ebook')
            ->assertDontSee($ebook->link_drive);

        $this->get(route('anggota.ebook.unduh', $ebook))
            ->assertRedirect($ebook->link_drive);
        $firstDownload = UnduhanEbook::query()->firstOrFail();
        $firstTimestamp = $firstDownload->waktu_unduh->toISOString();

        $this->get(route('anggota.ebook.unduh', $ebook))
            ->assertRedirect($ebook->link_drive);

        $this->assertDatabaseCount('unduhan_ebook', 1);
        $this->assertSame('Panduan SILARAS', $firstDownload->fresh()->judul_ebook);
        $this->assertSame($firstTimestamp, $firstDownload->fresh()->waktu_unduh->toISOString());

        auth('anggota')->logout();
        $this->actingAs($otherMember, 'anggota')
            ->get(route('anggota.ebook.unduh', $ebook))
            ->assertRedirect($ebook->link_drive);

        $this->assertDatabaseCount('unduhan_ebook', 2);
    }

    public function test_deleting_ebook_preserves_download_history_snapshots_and_removes_cover(): void
    {
        Storage::fake('s3');
        $member = $this->createMember('Anggota Pengunduh');
        $staff = $this->createStaff();
        $coverPath = 'ebook/cover/history-cover.jpg';
        Storage::disk('s3')->put($coverPath, 'cover');
        $ebook = $this->createEbook(
            'Judul Sebelum Hapus',
            'Penulis Sebelum Hapus',
            Storage::disk('s3')->url($coverPath)
        );

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.ebook.unduh', $ebook))
            ->assertRedirect($ebook->link_drive);
        auth('anggota')->logout();
        $this->actingAs($staff, 'petugas');
        $this->get(route('petugas.ebook.unduhan'))
            ->assertOk()
            ->assertSee('Anggota Pengunduh')
            ->assertSee('Judul Sebelum Hapus')
            ->assertSee('Penulis Sebelum Hapus');
        $this->delete(route('petugas.ebook.destroy', $ebook))
            ->assertRedirect(route('petugas.ebook.index'));

        $this->assertDatabaseMissing('ebook', ['id' => $ebook->getKey()]);
        $this->assertDatabaseHas('unduhan_ebook', [
            'anggota_id' => $member->getKey(),
            'ebook_id' => null,
            'judul_ebook' => 'Judul Sebelum Hapus',
            'penulis_ebook' => 'Penulis Sebelum Hapus',
        ]);
        Storage::disk('s3')->assertMissing($coverPath);
    }

    public function test_petugas_can_create_update_and_delete_ebook_with_activity_logs_and_cover_cleanup(): void
    {
        Storage::fake('s3');
        $staff = $this->createStaff();
        $this->actingAs($staff, 'petugas');

        $this->post(route('petugas.ebook.store'), [
            'judul' => 'Ebook Awal',
            'penulis' => 'Penulis Awal',
            'link_drive' => 'drive-link',
            'cover' => UploadedFile::fake()->create('awal.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('petugas.ebook.index'));

        $ebook = Ebook::query()->firstOrFail();
        $oldCoverUrl = $ebook->cover;
        $oldCoverPath = $this->pathFromPublicUrl($oldCoverUrl);
        Storage::disk('s3')->assertExists($oldCoverPath);

        $this->put(route('petugas.ebook.update', $ebook), [
            'judul' => 'Ebook Diperbarui',
            'penulis' => 'Penulis Baru',
            'link_drive' => 'new-drive-link',
        ])->assertRedirect(route('petugas.ebook.index'));

        $this->assertSame($oldCoverUrl, $ebook->fresh()->cover);
        Storage::disk('s3')->assertExists($oldCoverPath);

        $this->put(route('petugas.ebook.update', $ebook), [
            'judul' => 'Ebook Diperbarui',
            'penulis' => 'Penulis Baru',
            'link_drive' => 'new-drive-link',
            'cover' => UploadedFile::fake()->create('baru.webp', 10, 'image/webp'),
        ])->assertRedirect(route('petugas.ebook.index'));

        $newCoverPath = $this->pathFromPublicUrl($ebook->fresh()->cover);
        Storage::disk('s3')->assertMissing($oldCoverPath);
        Storage::disk('s3')->assertExists($newCoverPath);

        $this->delete(route('petugas.ebook.destroy', $ebook))
            ->assertRedirect(route('petugas.ebook.index'));

        Storage::disk('s3')->assertMissing($newCoverPath);
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'tambah',
            'objek' => 'ebook',
            'keterangan' => 'Ebook Awal',
        ]);
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'ubah',
            'objek' => 'ebook',
            'keterangan' => 'Ebook Diperbarui',
        ]);
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'hapus',
            'objek' => 'ebook',
            'keterangan' => 'Ebook Diperbarui',
        ]);
    }

    public function test_ebook_create_rolls_back_and_removes_uploaded_cover_when_activity_logging_fails(): void
    {
        Storage::fake('s3');
        $staff = $this->createStaff();
        $this->actingAs($staff, 'petugas');

        $this->mock(LogAktivitasService::class, function ($mock): void {
            $mock->shouldReceive('catat')
                ->once()
                ->andThrow(new LogAktivitasException());
        });

        $response = $this->from(route('petugas.ebook.create'))
            ->post(route('petugas.ebook.store'), [
                'judul' => 'Ebook Tidak Tersimpan',
                'penulis' => 'Penulis',
                'link_drive' => 'drive-link',
                'cover' => UploadedFile::fake()->create('gagal.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertRedirect(route('petugas.ebook.create'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('ebook', 0);
        $this->assertDatabaseCount('log_aktivitas', 0);
        $this->assertCount(0, Storage::disk('s3')->allFiles('ebook/cover'));
    }

    public function test_ebook_cover_validation_accepts_supported_image_types_and_rejects_bad_type_or_size(): void
    {
        Storage::fake('s3');
        $staff = $this->createStaff();
        $this->actingAs($staff, 'petugas');

        foreach ([
            ['cover.jpg', 'image/jpeg'],
            ['cover.png', 'image/png'],
            ['cover.webp', 'image/webp'],
        ] as [$filename, $mime]) {
            $this->post(route('petugas.ebook.store'), [
                'judul' => pathinfo($filename, PATHINFO_FILENAME),
                'penulis' => 'Penulis',
                'link_drive' => 'tautan tanpa validasi format',
                'cover' => UploadedFile::fake()->create($filename, 1024, $mime),
            ])->assertRedirect(route('petugas.ebook.index'));
        }

        $this->post(route('petugas.ebook.store'), [
            'judul' => 'Tanpa Cover',
            'penulis' => 'Penulis',
            'link_drive' => 'drive',
        ])->assertSessionHasErrors('cover');

        $this->post(route('petugas.ebook.store'), [
            'judul' => 'Format Salah',
            'penulis' => 'Penulis',
            'link_drive' => 'drive',
            'cover' => UploadedFile::fake()->create('cover.gif', 10, 'image/gif'),
        ])->assertSessionHasErrors('cover');

        $this->post(route('petugas.ebook.store'), [
            'judul' => 'Terlalu Besar',
            'penulis' => 'Penulis',
            'link_drive' => 'drive',
            'cover' => UploadedFile::fake()->create('cover.jpg', 1025, 'image/jpeg'),
        ])->assertSessionHasErrors('cover');

        $this->assertDatabaseCount('ebook', 3);
    }

    public function test_member_and_petugas_routes_are_separated_and_member_search_is_not_paginated(): void
    {
        Storage::fake('s3');
        $this->get('/anggota/ebook')->assertRedirect('/login');
        $this->get('/petugas/ebook')->assertRedirect('/login');

        $member = $this->createMember('Anggota');
        $staff = $this->createStaff();
        $ebook = $this->createEbook('Cari Ebook', 'Penulis Dicari');

        $this->actingAs($member, 'anggota')
            ->get('/petugas/ebook')
            ->assertForbidden();

        $this->get('/petugas/log-aktivitas')
            ->assertForbidden();

        auth('anggota')->logout();
        $this->actingAs($staff, 'petugas')
            ->get('/anggota/ebook')
            ->assertForbidden();

        auth('petugas')->logout();
        $this->actingAs($member, 'anggota')
            ->get(route('anggota.ebook.index', ['q' => 'Penulis Dicari']))
            ->assertOk()
            ->assertSee('Cari Ebook')
            ->assertDontSee('Ebook Tidak Cocok')
            ->assertDontSee($ebook->link_drive)
            ->assertDontSee('catalog-pagination');
    }

    public function test_member_ebook_card_shows_placeholder_when_cover_is_empty(): void
    {
        Storage::fake('s3');
        $member = $this->createMember('Anggota Tanpa Cover');
        $this->createEbook('Ebook Tanpa Sampul', 'Penulis Tanpa Sampul', '');

        $this->actingAs($member, 'anggota')
            ->get(route('anggota.ebook.index'))
            ->assertOk()
            ->assertSee('Sampul ebook tidak tersedia')
            ->assertDontSee('<img src=""', false);
    }

    public function test_log_model_rejects_updates_and_deletes(): void
    {
        $staff = $this->createStaff();
        $log = $this->createLog($staff);

        try {
            $log->update(['keterangan' => 'diubah']);
            $this->fail('Model update harus ditolak.');
        } catch (LogicException $exception) {
            $this->assertSame('Log aktivitas tidak dapat diubah.', $exception->getMessage());
        }

        try {
            $log->delete();
            $this->fail('Model delete harus ditolak.');
        } catch (LogicException $exception) {
            $this->assertSame('Log aktivitas tidak dapat dihapus.', $exception->getMessage());
        }

        try {
            LogAktivitas::query()->whereKey($log->getKey())->update(['keterangan' => 'diubah']);
            $this->fail('Query model update harus ditolak.');
        } catch (LogicException $exception) {
            $this->assertSame('Log aktivitas tidak dapat diubah.', $exception->getMessage());
        }

        try {
            LogAktivitas::query()->whereKey($log->getKey())->delete();
            $this->fail('Query model delete harus ditolak.');
        } catch (LogicException $exception) {
            $this->assertSame('Log aktivitas tidak dapat dihapus.', $exception->getMessage());
        }

        $this->assertDatabaseCount('log_aktivitas', 1);
    }

    public function test_activity_log_date_filter_limits_results_and_has_no_mutation_controls(): void
    {
        $staff = $this->createStaff();
        $this->createLog($staff, '2026-11-12 10:00:00');
        $this->createLog($staff, '2026-11-13 10:00:00');
        $this->actingAs($staff, 'petugas')
            ->get(route('petugas.log-aktivitas.index', [
                'dari' => '2026-11-13',
                'sampai' => '2026-11-13',
            ]))
            ->assertOk()
            ->assertSee('2026-11-13')
            ->assertDontSee('2026-11-12')
            ->assertDontSee('Hapus')
            ->assertDontSee('Ubah');
    }

    private function createMember(string $name)
    {
        return \App\Models\Anggota::query()->create([
            'nama_anggota' => $name,
            'email_anggota' => str_replace(' ', '.', strtolower($name)).'@example.test',
            'password' => 'password',
            'status_anggota' => 'aktif',
        ]);
    }

    private function createStaff(): Petugas
    {
        return Petugas::query()->create([
            'nama_petugas' => 'Petugas Ebook',
            'email_petugas' => uniqid('petugas-', true).'@example.test',
        ]);
    }

    private function createEbook(
        string $title,
        string $author,
        string $cover = 'https://storage.example.test/ebook/cover/default.jpg'
    ): Ebook {
        return Ebook::query()->create([
            'judul' => $title,
            'penulis' => $author,
            'cover' => $cover,
            'link_drive' => 'https://drive.google.com/file/d/example/view',
        ]);
    }

    private function createLog(Petugas $staff, string $time = '2026-11-13 12:00:00'): LogAktivitas
    {
        return LogAktivitas::query()->create([
            'petugas_id' => $staff->getKey(),
            'nama_petugas' => $staff->nama_petugas,
            'aksi' => 'tambah',
            'objek' => 'ebook',
            'keterangan' => 'Tes',
            'waktu' => $time,
        ]);
    }

    private function pathFromPublicUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $position = strpos($path, 'ebook/cover/');

        $this->assertNotFalse($position);

        return rawurldecode(substr($path, $position));
    }
}

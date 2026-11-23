# Panduan Bahan Presentasi SILARAS

Dokumen ini menjadi bahan sumber untuk AI pembuat slide. Isi presentasi harus mengikuti fakta yang tercantum di sini dan tidak menambahkan fitur yang belum ada di aplikasi.

## Brief Presentasi

- **Nama sistem:** SILARAS - Sistem Layanan Pustaka Cerdas.
- **Bentuk aplikasi:** aplikasi web perpustakaan dengan portal anggota, portal petugas, dan beberapa endpoint API.
- **Audiens:** dosen, mahasiswa, atau penguji proyek.
- **Tujuan:** menjelaskan masalah yang ditangani, pengguna, fitur, teknologi, alur kerja, data, keamanan, dan cara demonstrasi.
- **Bahasa:** Bahasa Indonesia yang jelas dan formal, tetapi mudah dipahami.
- **Format:** 16:9, sekitar 15-17 slide, durasi presentasi 10-15 menit.
- **Gaya visual:** profesional, bersih, dominan biru/navy, putih, dan aksen warna yang selaras dengan antarmuka aplikasi. Gunakan diagram, kartu, ikon, dan screenshot; hindari paragraf panjang di slide.
- **Aturan akurasi:** jangan menyebut aplikasi Android/iOS, AI rekomendasi buku, pembayaran otomatis, atau integrasi lain yang tidak dijelaskan di dokumen ini.

## Susunan Slide dan Penempatan Elemen

### Slide 1 - Sampul

**Judul:** SILARAS - Sistem Layanan Pustaka Cerdas  
**Subjudul:** Sistem layanan perpustakaan untuk anggota dan petugas.

**Penempatan:**
- Logo dan nama SILARAS di kiri atas.
- Judul besar di sisi kiri atau tengah.
- Screenshot dashboard/katalog sebagai visual utama di sisi kanan atau sebagai latar yang diredupkan.
- Nama presenter, kelas, institusi, dan tanggal di bagian bawah; isi dengan data tim yang benar.

**Catatan presenter:** sebutkan bahwa SILARAS mengelola katalog, peminjaman, pengembalian, denda, dan ebook melalui satu aplikasi.

### Slide 2 - Latar Belakang dan Tujuan

**Isi utama:**
- Layanan perpustakaan memiliki beberapa proses yang saling berhubungan: pencarian buku, peminjaman, pengembalian, denda, dan koleksi digital.
- SILARAS menyediakan pencatatan transaksi serta akses yang berbeda untuk anggota dan petugas.
- Tujuan sistem: memudahkan anggota memakai layanan mandiri dan membantu petugas mengelola proses serta status transaksi.

**Penempatan:**
- Kiri: 2-3 poin masalah/kebutuhan.
- Kanan: ilustrasi alur manual menjadi alur terpusat di SILARAS.
- Bagian bawah: satu kalimat manfaat utama.

Jangan menyatakan masalah seperti kehilangan data atau lamanya layanan sebagai fakta penelitian kecuali tim memiliki data pendukung sendiri.

### Slide 3 - Pengguna dan Hak Akses

**Anggota dapat:**
- Masuk dengan email/kata sandi atau Google.
- Melihat katalog dan detail buku.
- Mengajukan serta melihat peminjaman.
- Mengajukan pengembalian.
- Melihat tagihan, membayar melalui QRIS, dan mengirim bukti pembayaran.
- Mencari ebook, membuka tautan unduhan, melihat riwayat, notifikasi, dan profil.

**Petugas dapat:**
- Mengelola buku dan ebook.
- Memproses pengajuan peminjaman dan pengembalian.
- Mengelola anggota.
- Melihat dan memproses tagihan/pembayaran denda.
- Melihat transaksi, dashboard, dan log aktivitas.

**Penempatan:**
- Dua kolom sejajar: kartu “Anggota” di kiri dan “Petugas” di kanan.
- Gunakan ikon pengguna/anggota dan petugas.
- Beri label bahwa hak akses dipisahkan berdasarkan role.

### Slide 4 - Teknologi yang Digunakan

Susun sebagai tabel atau logo teknologi dengan peran masing-masing:

| Lapisan | Teknologi | Peran |
|---|---|---|
| Backend | PHP 8.2, Laravel 12 | Routing, autentikasi, validasi, proses bisnis, dan akses database |
| Database | PostgreSQL | Menyimpan akun, katalog, transaksi, tagihan, ebook, notifikasi, dan log |
| Antarmuka | Blade, CSS, JavaScript | Membuat halaman web anggota dan petugas |
| Asset frontend | Vite 7 dan Tailwind CSS 4 | Membangun serta mengelola asset frontend |
| API auth | Laravel Sanctum | Autentikasi token untuk endpoint API |
| Login Google | Laravel Socialite | Proses OAuth Google untuk anggota |
| File | Flysystem S3-compatible, dikonfigurasi untuk Supabase Storage | Penyimpanan file objek seperti bukti pembayaran dan cover |
| Ebook | Tautan Google Drive yang disimpan pada data ebook | Tujuan unduhan ebook |
| Email | SMTP yang dapat dikonfigurasi | Pengiriman tautan reset kata sandi |
| Build/deploy | Docker, Apache, Railway | Build aplikasi dan menjalankan aplikasi web |

**Penempatan:** tampilkan sebagai stack vertikal dari antarmuka ke database/infrastruktur. Jangan menyebut Google Drive sebagai tempat semua file aplikasi disimpan; tautan Drive khusus untuk ebook.

### Slide 5 - Arsitektur Sistem

**Diagram yang disarankan:**

`Browser Anggota/Petugas -> Route & Middleware Laravel -> Controller -> Service/Model -> PostgreSQL`

Tambahkan hubungan pendukung:

- Laravel -> Supabase S3-compatible storage untuk file yang dikelola aplikasi.
- Laravel -> Google OAuth untuk proses login anggota.
- Laravel -> SMTP untuk email reset kata sandi.
- Anggota -> tautan Google Drive untuk membuka ebook.
- Client API -> Laravel API -> Sanctum untuk endpoint yang memerlukan autentikasi.

**Penempatan:**
- Diagram alur horizontal di tengah slide.
- Kelompokkan backend Laravel di dalam satu kotak besar.
- Letakkan database dan storage di bawah/kanan backend.
- Letakkan layanan eksternal di sisi kanan dengan garis koneksi berlabel.

### Slide 6 - Autentikasi, Profil, dan Reset Kata Sandi

**Fitur:**
- Login dan registrasi anggota serta petugas memakai guard yang berbeda.
- Anggota dapat masuk menggunakan Google.
- Profil anggota dapat mengubah nama dan nomor telepon; email tidak diubah dari halaman profil.
- Reset kata sandi memakai tautan email dan token yang kedaluwarsa.
- Form reset tersedia dari halaman login anggota maupun petugas.

**Penempatan:**
- Kiri: alur `Login -> Verifikasi role -> Dashboard sesuai role`.
- Kanan: screenshot login anggota dan petugas (dua thumbnail).
- Bagian bawah: alur `Lupa kata sandi -> Email -> Tautan reset -> Kata sandi baru`.

**Catatan operasional:** kode fitur reset telah ditambahkan di workspace, tetapi pengiriman email produksi memerlukan SMTP pada Railway dan migration tabel token sudah dijalankan. Login Google tetap tersedia; anggota yang mengatur ulang kata sandi juga dapat memakai kata sandi lokal.

### Slide 7 - Katalog dan Detail Buku untuk Anggota

**Fitur:**
- Katalog buku menampilkan metadata buku.
- Pencarian mendukung judul, pengarang, dan data katalog terkait.
- Filter kategori dan ketersediaan serta pagination mempertahankan filter/pencarian.
- Halaman detail menampilkan informasi buku, stok/status, cover, dan buku terkait.

**Penempatan:**
- Kiri: screenshot katalog.
- Kanan: screenshot halaman detail buku.
- Tambahkan anotasi kecil pada search, filter kategori, status ketersediaan, dan tombol pengajuan.

**Lokasi di aplikasi:** menu katalog anggota (`/anggota/katalog`) dan halaman detail buku.

### Slide 8 - Alur Peminjaman Buku

**Alur proses:**
1. Anggota memilih buku yang tersedia.
2. Anggota memilih durasi 7, 14, atau 30 hari.
3. Sistem memeriksa status akun, stok, tagihan, dan batas peminjaman.
4. Pengajuan menunggu validasi petugas.
5. Petugas menyetujui atau menolak; stok dan status transaksi diperbarui sesuai hasil.
6. Anggota melihat status pengajuan dan riwayat peminjaman.

**Aturan bisnis yang perlu ditonjolkan:**
- Maksimal 3 peminjaman berjalan.
- Tagihan denda belum lunas memblokir pengajuan baru.
- Petugas memeriksa ulang stok dan kondisi akun sebelum menyetujui.
- Pengajuan yang belum diproses dapat dibatalkan oleh anggota.
- Pengajuan yang menunggu lebih dari 3 hari dapat ditolak otomatis oleh sinkronisasi terjadwal.

**Penempatan:** flowchart bernomor di tengah; letakkan validasi sistem sebagai decision diamond dan bedakan aktivitas anggota/petugas dengan dua warna.

### Slide 9 - Pengembalian dan Perhitungan Denda

**Alur proses:**
1. Anggota mengajukan pengembalian dari daftar peminjaman aktif.
2. Petugas mengonfirmasi pengembalian dan menentukan kondisi: baik, rusak, atau hilang.
3. Sistem menghitung tagihan akhir dan membekukan nilai setelah pengembalian selesai.
4. Buku berkondisi baik menambah stok; buku rusak/hilang tidak menambah stok.
5. Anggota mendapat notifikasi dan melihat tagihan jika ada denda.

**Aturan tarif saat ini:**
- Keterlambatan: tarif per hari dapat diatur petugas; nilai awal sistem Rp5.000 per hari.
- Rusak: Rp50.000.
- Hilang: Rp100.000.
- Denda terlambat dapat ditambahkan ke denda kerusakan/kehilangan.

**Penempatan:**
- Kiri: diagram pengajuan anggota -> pemeriksaan petugas -> hasil.
- Kanan: kartu tarif.
- Beri highlight pada tahap “tagihan dibekukan” agar jelas jumlah final tidak terus bertambah setelah transaksi pengembalian selesai.

### Slide 10 - Pembayaran Denda QRIS

**Fitur anggota:**
- Melihat rincian nominal keterlambatan, kerusakan, kehilangan, dan total.
- Membayar sesuai total tagihan melalui QRIS.
- Mengunggah bukti pembayaran JPG/JPEG/PNG maksimal 2 MB.
- Pembayaran berstatus menunggu verifikasi petugas.

**Fitur petugas:**
- Membuka bukti pembayaran dari halaman detail tagihan.
- Memverifikasi atau menolak pembayaran; alasan penolakan diwajibkan.
- Mencatat pembayaran tunai penuh.
- Status tagihan disinkronkan setelah pembayaran diverifikasi.

**Penempatan:**
- Screenshot halaman pembayaran anggota di kiri, detail tagihan petugas di kanan.
- Diagram status di bawah: `Menunggu verifikasi -> Diverifikasi / Ditolak`.
- Gunakan visual QRIS contoh yang sudah disetujui untuk presentasi; jangan menampilkan data pembayaran/identitas nyata.

**Catatan aset:** aplikasi mengharapkan gambar QRIS di storage dengan key `qris/qris.png`. Pastikan gambar tersedia pada environment demo/produksi.

### Slide 11 - Koleksi Ebook

**Anggota:**
- Mencari ebook berdasarkan judul atau penulis.
- Membuka tautan Google Drive.
- Aktivitas unduhan dicatat sekali per anggota-ebook dan muncul pada riwayat unduhan.
- Cover bersifat opsional pada tampilan anggota.

**Petugas:**
- Menambah, mengubah, dan menghapus data ebook.
- Mengimpor data melalui CSV dengan kolom `judul`, `penulis`, dan `link_drive`.
- Melihat riwayat unduhan.
- Menghapus ebook tetap mempertahankan snapshot riwayat unduhan.

**Penempatan:**
- Kiri: screenshot katalog ebook anggota.
- Kanan: screenshot daftar ebook petugas.
- Di bawah: contoh CSV sederhana dengan header yang benar.

### Slide 12 - Dashboard dan Pengelolaan Petugas

**Fitur petugas:**
- Dashboard menampilkan ringkasan operasional berdasarkan data transaksi.
- Pengelolaan buku mencakup tambah, ubah, arsip, hapus, cover, pencarian/filter, dan pagination.
- Impor buku memakai CSV dengan validasi dan pemeriksaan duplikasi.
- Penghapusan permanen buku ditolak jika masih memiliki riwayat peminjaman.
- Petugas dapat melihat daftar anggota dan menonaktifkan anggota.

**Penempatan:**
- Atas: screenshot dashboard petugas dengan kartu statistik.
- Bawah kiri: potongan halaman pengelolaan buku.
- Bawah kanan: ikon kecil untuk CSV, arsip, dan pengelolaan anggota.

### Slide 13 - Transaksi, Notifikasi, dan Audit

**Transaksi petugas:**
- Satu halaman menggabungkan data peminjaman, pengembalian, dan pembayaran denda.
- Mendukung filter jenis/status/tanggal dan pencarian.
- Menampilkan ringkasan jumlah transaksi dan pembayaran terverifikasi.

**Notifikasi anggota:**
- Notifikasi terkait peminjaman, pengembalian, dan denda.
- Anggota hanya dapat membuka notifikasi miliknya sendiri.

**Log aktivitas:**
- Mencatat tindakan petugas pada objek seperti buku, ebook, anggota, pengaturan, dan pembayaran.
- Log dirancang append-only; halaman log menyediakan filter tanggal tanpa kontrol edit/hapus.

**Penempatan:** screenshot transaksi sebagai gambar utama; di sisi kanan tampilkan tiga kartu kecil: notifikasi, audit log, dan ringkasan.

### Slide 14 - Struktur Data Utama

Buat ERD tingkat tinggi, tidak perlu menampilkan setiap kolom.

**Entitas utama dan hubungan:**
- `anggota` memiliki banyak `peminjaman`, `tagihan_denda`, `pembayaran_denda`, `notifikasi`, dan catatan `unduhan_ebook`.
- `buku` dapat muncul pada banyak `peminjaman`.
- `peminjaman` terkait dengan satu anggota dan satu buku; memiliki catatan pengembalian serta satu tagihan denda per peminjaman.
- `tagihan_denda` memiliki catatan `pembayaran_denda`.
- `petugas` memvalidasi peminjaman/pengembalian/pembayaran dan menghasilkan `log_aktivitas`.
- `ebook` memiliki catatan unduhan yang menyimpan snapshot judul dan penulis.
- `pengaturan` menyimpan nilai seperti tarif denda dan aturan masa pinjam.
- `password_reset_tokens` menyimpan token reset berbatas waktu.

**Penempatan:**
- `anggota`, `buku`, dan `peminjaman` di bagian atas.
- `pengembalian` dan `tagihan_denda` di tengah.
- `pembayaran_denda`, `notifikasi`, dan `log_aktivitas` di bawah.
- Letakkan `ebook` dan `unduhan_ebook` sebagai kelompok terpisah di sisi kanan.

### Slide 15 - API dan Otomatisasi

**Endpoint API yang tersedia:**
- `GET /api/buku`: daftar buku.
- `POST /api/login`: login anggota dan penerbitan token.
- `GET /api/me`: data akun aktif (memerlukan Sanctum).
- `POST /api/logout`: logout token (memerlukan Sanctum).
- `POST /api/peminjaman`: pengajuan peminjaman melalui API (memerlukan Sanctum).

**Otomatisasi denda:**
- Command `denda:sinkronisasi` memperbarui status peminjaman terlambat, tagihan berjalan, dan pengajuan yang kedaluwarsa.
- Jadwal dikonfigurasi setiap hari pukul 00:05 zona `Asia/Jakarta`.

**Penempatan:**
- Kiri: daftar endpoint dengan label publik/terautentikasi.
- Kanan: garis waktu sinkronisasi harian.
- Tambahkan catatan bahwa scheduler harus dijalankan oleh environment deployment agar jadwal benar-benar dieksekusi.

Jelaskan bahwa keberadaan API tidak berarti tersedia aplikasi mobile native; API dapat dipakai client yang sesuai.

### Slide 16 - Keamanan, Validasi, dan Pengujian

**Kontrol yang ada:**
- Guard dan middleware memisahkan anggota serta petugas.
- Anggota hanya melihat transaksi, denda, dan notifikasi miliknya.
- API privat menggunakan Sanctum.
- Upload bukti pembayaran divalidasi tipe dan ukuran.
- File privat disajikan melalui route aplikasi yang memerlukan role sesuai.
- Operasi penting memakai transaksi database dan penguncian baris untuk menghindari perubahan bersamaan yang tidak konsisten.
- Kegagalan log aktivitas membatalkan operasi terkait.

**Pengujian:**
- Mencakup autentikasi, peminjaman/pengembalian, denda/pembayaran, ebook, impor CSV, notifikasi, dan halaman petugas.
- Snapshot tes terakhir di workspace: **115 tes lulus, 832 assertion**. Jalankan ulang sebelum presentasi jika kode berubah.

**Penempatan:**
- Kiri: ikon shield dan 4-5 poin keamanan.
- Kanan: angka hasil tes dan kategori tes.
- Jangan menampilkan email, token, QRIS riil, bukti transfer, kredensial, atau data anggota nyata.

### Slide 17 - Demo dan Penutup

**Urutan demo singkat:**
1. Login sebagai anggota.
2. Cari buku dan buka detail.
3. Ajukan peminjaman.
4. Login sebagai petugas dan proses pengajuan.
5. Tampilkan proses pengembalian dan status denda.
6. Tampilkan pembayaran QRIS/bukti dan verifikasi petugas.
7. Tampilkan koleksi ebook dan halaman transaksi.

**Penempatan:**
- Kiri: daftar langkah demo.
- Kanan: QR code/tautan demo hanya jika lingkungan demo sudah aman dan dapat diakses.
- Penutup di bagian bawah: “Satu aplikasi untuk menghubungkan layanan anggota dan operasional petugas.”

Pilih maksimal 3-4 langkah jika waktu presentasi terbatas.

## Peta Fitur ke Lokasi di Aplikasi

| Pengguna | Fitur | Lokasi |
|---|---|---|
| Umum | Beranda | `/` |
| Anggota | Login, registrasi, login Google, lupa kata sandi | `/login`, `/register`, dan `/forgot-password` |
| Anggota | Dashboard, katalog, detail buku | `/anggota/dashboard`, `/anggota/katalog` |
| Anggota | Peminjaman dan riwayat | `/anggota/peminjaman`, `/anggota/riwayat` |
| Anggota | Pengajuan pengembalian | `/anggota/pengembalian` |
| Anggota | Tagihan dan pembayaran QRIS | `/anggota/denda` |
| Anggota | Ebook, unduhan, notifikasi, profil | `/anggota/ebook`, `/anggota/notifikasi`, `/anggota/profil` |
| Petugas | Login, registrasi, lupa kata sandi | `/petugas/login`, `/petugas/register`, `/petugas/forgot-password` |
| Petugas | Dashboard dan profil | `/petugas/dashboard`, `/petugas/profil` |
| Petugas | Buku, anggota, peminjaman, pengembalian | `/petugas/kelola-buku`, `/petugas/anggota`, `/petugas/peminjaman`, `/petugas/pengembalian` |
| Petugas | Denda, transaksi, ebook, log | `/petugas/denda`, `/petugas/transaksi`, `/petugas/ebook`, `/petugas/log-aktivitas` |
| Client API | Buku, login, akun aktif, logout, peminjaman | `/api/buku`, `/api/login`, `/api/me`, `/api/logout`, `/api/peminjaman` |

## Prompt Siap Salin ke AI Pembuat Presentasi

> Buat presentasi berbahasa Indonesia berdasarkan dokumen sumber ini. Buat 16:9, sekitar 15-17 slide, gaya profesional untuk presentasi proyek akademik, dengan warna biru/navy dan putih yang konsisten. Ikuti bagian “Susunan Slide dan Penempatan Elemen” secara berurutan. Setiap slide harus memiliki judul singkat, maksimal 3-5 poin ringkas, visual yang relevan, dan catatan presenter terpisah. Gunakan screenshot SILARAS hanya jika disediakan; jika tidak, gunakan mockup konseptual dan beri label mockup, jangan mengarang tampilan atau data. Jangan menambah klaim fitur, teknologi, angka, atau integrasi di luar dokumen sumber. Jangan tampilkan kredensial, data anggota, bukti pembayaran, atau QRIS nyata. Sisakan placeholder untuk nama presenter, kelas, dan institusi. Pastikan alur pinjam-kembali-denda, pembagian anggota/petugas, stack teknologi, arsitektur, database, API, keamanan, pengujian, dan prasyarat deployment dijelaskan dengan tepat.

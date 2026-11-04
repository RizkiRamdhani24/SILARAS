# STRUKTUR PEMBAGIAN TUGAS DAN BRANCH GIT — SILARAS

## 1. Pembagian Tugas Programmer

### 1. Rizki Ramdhani — Ketua Divisi Programmer

**Branch:** `feature/auth`

**Tanggung jawab:**

- Mengembangkan sistem autentikasi dan login.
- Mengelola role dan hak akses pengguna.
- Menangani struktur database dan migrasi.
- Mengembangkan core system.
- Mengintegrasikan modul antarf fitur.
- Melakukan review kode dan membantu penyelesaian konflik integrasi.

### 2. Dimas — Frontend Anggota

**Branch:** `feature/frontend-anggota`

**Tanggung jawab:**

- Mengembangkan halaman frontend anggota.
- Mengimplementasikan sidebar anggota sesuai desain UI/UX.
- Mengembangkan antarmuka fitur anggota.
- Mengintegrasikan frontend dengan backend yang tersedia.

### 3. Danar — Frontend Petugas

**Branch:** `feature/frontend-petugas`

**Tanggung jawab:**

- Mengembangkan halaman frontend petugas.
- Mengimplementasikan sidebar petugas sesuai desain UI/UX.
- Mengembangkan antarmuka validasi peminjaman.
- Mengembangkan antarmuka pengembalian buku.
- Mengintegrasikan frontend dengan endpoint backend yang tersedia.

### 4. Exel — Backend Buku dan Peminjaman

**Branch:** `feature/book-borrowing`

**Tanggung jawab:**

- Mengembangkan backend pengelolaan data buku.
- Mengembangkan proses peminjaman buku.
- Mengimplementasikan validasi peminjaman di sisi backend.
- Menyediakan endpoint API untuk frontend.
- Memastikan transaksi peminjaman terhubung dengan database.

### 5. Herlyan — Backend Pengembalian, Denda, dan Riwayat

**Branch:** `feature/return-history`

**Tanggung jawab:**

- Mengembangkan backend pengembalian buku.
- Mengimplementasikan perhitungan denda keterlambatan.
- Mengembangkan riwayat peminjaman dan pengembalian.
- Menyediakan endpoint API untuk frontend.
- Memastikan data transaksi dan denda tersimpan dengan benar.

## 2. Pemetaan Fitur dan Penanggung Jawab

| Fitur                          | Penanggung Jawab | Branch                     |
| ------------------------------ | ---------------- | -------------------------- |
| Authentication dan Login       | Rizki            | `feature/auth`             |
| Role dan Hak Akses             | Rizki            | `feature/auth`             |
| Database dan Migrasi           | Rizki            | `feature/auth`             |
| Core System dan Integrasi      | Rizki            | `feature/auth`             |
| Frontend Anggota               | Dimas            | `feature/frontend-anggota` |
| Sidebar Anggota                | Dimas            | `feature/frontend-anggota` |
| Frontend Petugas               | Danar            | `feature/frontend-petugas` |
| Sidebar Petugas                | Danar            | `feature/frontend-petugas` |
| Validasi Peminjaman — Frontend | Danar            | `feature/frontend-petugas` |
| Pengelolaan Buku — Backend     | Exel             | `feature/book-borrowing`   |
| Peminjaman — Backend           | Exel             | `feature/book-borrowing`   |
| Validasi Peminjaman — Backend  | Exel             | `feature/book-borrowing`   |
| Pengembalian — Frontend        | Danar            | `feature/frontend-petugas` |
| Pengembalian — Backend         | Herlyan          | `feature/return-history`   |
| Denda Keterlambatan            | Herlyan          | `feature/return-history`   |
| Riwayat Transaksi              | Herlyan          | `feature/return-history`   |

## 3. Struktur Branch Git

Repository menggunakan alur branch berikut:

- `main` — branch utama untuk kode yang stabil.
- `develop` — branch integrasi untuk menggabungkan dan menguji hasil pekerjaan programmer.
- `feature/auth` — pekerjaan autentikasi, database, dan core system.
- `feature/frontend-anggota` — pekerjaan frontend anggota.
- `feature/frontend-petugas` — pekerjaan frontend petugas.
- `feature/book-borrowing` — pekerjaan backend buku dan peminjaman.
- `feature/return-history` — pekerjaan backend pengembalian, denda, dan riwayat.

## 4. Alur Kerja Git

1. Programmer mengambil kode terbaru dari repository.
2. Programmer bekerja pada branch sesuai tanggung jawab masing-masing.
3. Perubahan disimpan melalui `commit` dan dikirim ke GitHub menggunakan `push`.
4. Programmer mengajukan Pull Request untuk menggabungkan pekerjaan ke `develop`.
5. Tim melakukan pemeriksaan dan pengujian integrasi.
6. Setelah stabil dan disetujui, perubahan digabungkan dari `develop` ke `main`.

## 5. Koordinasi Tim

- Programmer bertanggung jawab atas modul sesuai pembagian tugas.
- UI/UX menyediakan desain dan acuan antarmuka.
- System Analyst memastikan implementasi mengikuti kebutuhan sistem dan dokumen analisis.
- Ketua Divisi Programmer mengoordinasikan pekerjaan, memantau integrasi, dan membantu penyelesaian masalah teknis.

**Catatan:** Nama branch pada dokumen ini merupakan rancangan pembagian kerja. Pembuatan branch, sinkronisasi kode, dan penggabungan ke `develop` atau `main` harus dilakukan sesuai kondisi repository yang sebenarnya.

# 🅿️ ParkirPro Enterprise | Luxury Edition

---

## A. IDENTITAS PROYEK DAN KONEKSIONALITAS AKADEMIK
* **Mata Pelajaran:** Pemrograman Web dan Perangkat Bergerak
* **Judul Tugas / Project:** Pengembangan Sistem Informasi ParkirPro Enterprise (Luxury Edition)
* **Kode Soal:** KM25.4.1.1 (Paket 1 - Custom Enterprise Implementation)
* **Alokasi Waktu:** 23 Jam
* **Metode Pembelajaran:** Project-Based Learning yang diintegrasikan dengan teknik Asisten AI Prompt Engineering
* **Stack Teknologi:** PHP 8.2 (Native PDO), Bootstrap 5.3, Chart.js, Google Fonts (Plus Jakarta Sans & Space Grotesk), AOS Animation

---

## B. DESKRIPSI TUGAS DAN RUANG LINGKUP SISTEM
Sebagai seorang pengembang perangkat lunak profesional (Senior Web Developer), tugas utama dari proyek ini adalah membangun sebuah sistem manajemen parkir cerdas berbasis web yang berstandar enterprise. Aplikasi ini dikembangkan untuk mendigitalisasi proses pencatatan kendaraan masuk, penghitungan durasi dan tarif parkir secara otomatis, pengelolaan data master, hingga penyediaan laporan keuangan eksekutif. 

Pendekatan desain yang digunakan mengusung konsep Ultra-Luxury Dark/Light Glassmorphic Theme untuk memberikan pengalaman pengguna (UI/UX) yang modern, elegan, dan intuitif. Sistem ini dirancang dengan memisahkan hak akses menjadi tiga peran pengguna secara spesifik guna menjaga keamanan data dan alur kerja operasional.

---

## C. ALAT DAN BAHAN PENDUKUNG PENGEMBANGAN
Dalam proses perancangan, pembuatan, hingga pengujian kode sumber aplikasi ini, perangkat keras dan perangkat lunak yang digunakan meliputi:

1. **Perangkat Keras (Hardware):** Komputer jinjing (Laptop) dengan spesifikasi minimal prosesor setara Intel Core i3 / AMD Ryzen 3, kapasitas RAM 4 GB, serta ruang penyimpanan berbasis SSD/HDD yang memadai.
2. **Perangkat Lunak Server (Server Environment):** XAMPP yang di dalamnya mencakup web server Apache untuk menjalankan skrip PHP serta manajemen basis data MySQL (MariaDB).
3. **Perangkat Penyunting Kode (Text Editor):** Visual Studio Code yang dilengkapi dengan ekstensi pendukung seperti PHP Intelephense, GitLens, dan formatter kode agar penulisan sintaks tetap rapi.
4. **Perangkat Penjelajah Web (Browser):** Google Chrome atau Microsoft Edge versi terbaru untuk mengakses aplikasi lokal serta merender grafik interaktif berbasis JavaScript.
5. **Sistem Kontrol Versi (Version Control):** Git dan repositori GitHub untuk melakukan manajemen kode sumber serta sinkronisasi progres pengembangan secara berkala.

---

## D. STRUKTUR DIREKTORI DAN PENJELASAN MODUL PROGRAM
Struktur direktori proyek dirancang secara modular agar setiap file memiliki fungsi yang spesifik dan terisolasi dengan baik:

```text
parkir_project/
│
├── admin/                  # Modul Kontrol & Manajemen Utama Admin
│   ├── area.php            # Manajemen & Monitoring Kapasitas Area Parkir
│   ├── database.php        # Konfigurasi & Utilitas Pemeliharaan Database
│   ├── index.php           # Dashboard Utama Admin (Chart.js Tren & Doughnut)
│   ├── keluar.php          # Rekapitulasi Kendaraan Keluar & Perhitungan Tarif
│   ├── kendaraan.php       # Database & Filter Data Kendaraan Terdaftar
│   ├── log.php             # Sistem Log Aktivitas Pengguna (Audit Trail)
│   ├── masuk.php           # Modul Kendaraan Masuk & Kontrol Aksi Cepat
│   ├── tarif.php           # Pengaturan Tarif Progresif & Jenis Kendaraan
│   └── user.php            # Manajemen Akun & Hak Akses Pengguna Sistem
│
├── owner/                  # Modul Eksekutif / Pimpinan
│   └── index.php           # Laporan Eksekutif, Filter Tanggal, & Pendapatan Global
│
├── petugas/                # Modul Operasional Lapangan (Petugas)
│   ├── cetak_struk.php     # Modul Pencetakan Struk Parkir Digital
│   └── index.php           # Dashboard Shift Petugas & Status Kendaraan Aktif
│
├── config.php              # Konfigurasi Koneksi Database (PDO Singleton Aman)
├── index.php               # Halaman Pintu Masuk / Redirect Otomatis Sesi
├── login.php               # Halaman Autentikasi Keamanan (Multi-Role)
└── logout.php              # Modul Pembersihan Sesi (Session Destruction)
```

### Penjelasan Rinci Modul File:
* **Folder `admin/`**: Berisikan modul pusat kendali administrator yang mencakup file `area.php` untuk pengelolaan kapasitas lahan, `database.php` untuk pemeliharaan data, `index.php` sebagai dasbor analitik utama, `keluar.php` untuk memproses kendaraan keluar, `kendaraan.php` untuk manajemen data kendaraan, `log.php` untuk memantau rekam jejak aktivitas, `masuk.php` untuk manajemen kendaraan masuk, `tarif.php` untuk pengaturan biaya progresif, serta `user.php` untuk manajemen akun pengguna.
* **Folder `owner/`**: Berisikan file `index.php` yang berfungsi khusus sebagai panel laporan eksekutif dan rekapitulasi keuangan bagi pimpinan.
* **Folder `petugas/`**: Berisikan file `index.php` untuk operasional harian petugas lapangan serta `cetak_struk.php` untuk mencetak tiket digital.
* **File `config.php`**: Bertindak sebagai pusat koneksi database menggunakan objek PDO secara aman.
* **File `login.php` & `logout.php`**: Mengatur sistem autentikasi keamanan, pembuatan sesi, serta pembersihan sesi saat keluar aplikasi.

---

## E. SPESIFIKASI FITUR BERDASARKAN DASBOR PER ROLE

### 1. Panel Administrator (Admin Control Center)
* **Fungsi Utama:** Berfungsi sebagai pusat pengelolaan seluruh data master sistem.
* **Fitur & Dasbor:**
  * Dasbor admin menampilkan kartu statistik yang merangkum total pengguna, kendaraan terdaftar, area parkir aktif, serta skema tarif.
  * Terdapat grafik tren transaksi harian menggunakan **Chart.js** untuk memantau volume kendaraan serta akumulasi pendapatan harian secara real-time.
  * Diagram lingkaran (doughnut chart) keterisian slot area parkir secara real-time.
  * Akses penuh CRUD untuk Manajemen User, Tarif Parkir, Area Parkir, Data Kendaraan, Transaksi Masuk/Keluar, dan Audit Log Aktivitas.

### 2. Panel Petugas Lapangan (Petugas Operations)
* **Fungsi Utama:** Berfungsi untuk mempercepat pelayanan di gerbang parkir.
* **Fitur & Dasbor:**
  * Petugas dapat melihat daftar kendaraan yang sedang terparkir (menampilkan ID Parkir, Plat Nomor, Jenis Kendaraan, Area Parkir, dan Waktu Masuk).
  * Memproses kendaraan keluar secara langsung melalui tombol aksi cepat.
  * Mencetak struk tiket digital secara otomatis (`cetak_struk.php`).
  * Pemantauan riwayat transaksi yang terjadi selama shift hari berjalan.

### 3. Panel Pimpinan (Owner Executive Report)
* **Fungsi Utama:** Berfungsi untuk memantau performa finansial bisnis secara makro.
* **Fitur & Dasbor:**
  * Panel ini menyajikan metrik finansial berupa total pendapatan (Rp), jumlah transaksi selesai (keluar), dan kendaraan yang masih berada di dalam area parkir.
  * Fitur filter kustom berdasarkan rentang tanggal (*Dari Tanggal* sampai *Sampai Tanggal*).
  * Dilengkapi tombol navigasi *Filter*, *Reset*, dan *Cetak Laporan* rekapitulasi transaksi.

---

## F. LANGKAH KERJA DAN IMPLEMENTASI TEKNIS

### Langkah 1: Perancangan Basis Data dan Koneksi Menggunakan PDO
Pengembangan dimulai dengan merancang skema basis data relational yang terdiri dari tabel pengguna (`tb_user`), area parkir (`tb_area_parkir`), kendaraan (`tb_kendaraan`), transaksi (`tb_transaksi`), dan log aktivitas (`tb_log_aktivitas`). Koneksi ke database diimplementasikan pada file `config.php` menggunakan PHP Data Objects (PDO) dengan teknik prepared statements guna mencegah celah keamanan SQL Injection.

### Langkah 2: Pengembangan Antarmuka dan Logika Backend
Antarmuka dibangun menggunakan kerangka kerja Bootstrap 5.3 dengan tambahan efek kaca (*glassmorphism*) serta pustaka Chart.js untuk memvisualisasikan data analitik. Setiap modul dikoding secara terpisah ke dalam folder masing-masing role agar alur kerja sistem menjadi terstruktur dan mudah di-maintenance.

### Langkah 3: Pengujian Sistem dan Sinkronisasi Repositori GitHub
Tahap akhir melibatkan pengujian fungsionalitas login untuk ketiga role guna memastikan hak akses bekerja sesuai aturan. Setelah seluruh kode teruji dengan baik di server lokal XAMPP, kode sumber disinkronkan ke repositori GitHub menggunakan perintah Git:

```bash
git add .
git commit -m "Complete ParkirPro Enterprise Edition Documentation & Structure"
git push origin main
```

---

## G. EVALUASI DAN KESIMPULAN
* **Fitur Berjalan Baik:** Sistem autentikasi multi-role berbasis sesi terenkripsi, sinkronisasi grafik analitik real-time berbasis Chart.js, manajemen rekapitulasi transaksi berbasis rentang tanggal, pencetakan tiket digital, serta struktur antarmuka *Ultra-Luxury Glassmorphic* yang responsif.
* **Rencana Pengembangan Lanjutan:** Penambahan modul notifikasi otomatis via WhatsApp Gateway saat kendaraan masuk serta fitur ekspor laporan keuangan ke format PDF dan Excel untuk kebutuhan arsip manajemen.

---

<div align="center">
  <small><b>© 2026 ParkirPro Enterprise Edition. Crafted with precision by Putri.</b></small>
</div>
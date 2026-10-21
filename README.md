---

## 👥 E. SPESIFIKASI FITUR BERDASARKAN DASBOR PER ROLE
Aplikasi memfasilitasi tiga level pengguna dengan rincian hak akses, alur kerja, dan visualisasi dasbor sebagai berikut:

### 1. Panel Administrator (`admin/index.php`)
* **Fungsi Utama:** Bertindak sebagai pusat kendali utama (*Admin Control Center*) dan konfigurasi data master sistem.
* **Fitur & Dasbor Visual:**
  * **Kartu Ringkasan Metrik (*Stat Cards*):** Menampilkan informasi *real-time* mengenai Total Pengguna Sistem, Total Kendaraan Terdaftar, Jumlah Area Parkir Aktif, dan Skema Tarif Parkir.
  * **Grafik Tren Transaksi & Pendapatan (*Chart.js Line Chart*):** Memvisualisasikan volume lalu lintas kendaraan masuk/keluar serta fluktuasi pendapatan harian selama 7 hari terakhir secara transparan.
  * **Diagram Keterisian Slot (*Doughnut Chart*):** Memantau rasio distribusi kapasitas terpakai versus slot kosong per area lokasi parkir secara instan.
  * **Akses Pengelolaan (CRUD):** Kontrol penuh atas Manajemen Akun Pengguna, Konfigurasi Tarif Progresif, Manajemen Area Parkir, Master Data Kendaraan, Transaksi Masuk/Keluar, serta Pemantauan *Audit Log* Aktivitas Pengguna.

---

### 2. Panel Petugas Lapangan (`petugas/index.php`)
* **Fungsi Utama:** Mengakomodasi operasional gerbang parkir secara cepat, efisien, dan presisi pada shift kerja harian.
* **Fitur & Dasbor Visual:**
  * **Tabel Kendaraan Sedang Parkir:** Menampilkan entri aktif kendaraan lengkap dengan ID Parkir, Plat Nomor (*badge styled*), Jenis Kendaraan, Lokasi Area Parkir, dan Waktu Masuk (*timestamp*).
  * **Tombol Aksi Cepat (*Quick Action Buttons*):** 
    * `[Diproses Keluar]` : Memproses kalkulasi otomatis durasi dan total biaya parkir kendaraan saat keluar.
    * `[Struk Tiket]` : Mencetak struk bukti parkir digital secara langsung melalui modul `cetak_struk.php`.
  * **Tabel Riwayat Transaksi Shift:** Pemantauan historis pencatatan transaksi masuk dan keluar yang diproses oleh petugas pada hari berjalan.

---

### 3. Panel Pimpinan / Owner (`owner/index.php`)
* **Fungsi Utama:** Menyajikan laporan eksekutif dan analisis finansial makro bagi pemilik atau manajemen pimpinan.
* **Fitur & Dasbor Visual:**
  * **Indikator Kinerja Finansial (*Executive Financial Cards*):** Menampilkan informasi akumulasi **Total Pendapatan (Rp)**, jumlah **Transaksi Selesai (Keluar)**, serta volume **Kendaraan Sedang Parkir**.
  * **Penyaring Data Berbasis Tanggal (*Interactive Date Filter*):** Memungkinkan pimpinan menyaring data rekapitulasi berdasarkan rentang waktu spesifik (*Dari Tanggal* s/d *Sampai Tanggal*).
  * **Tabel Rekapitulasi Transaksi Lengkap:** Merinci riwayat transaksi lengkap dengan durasi parkir, total biaya, status transaksi (*Masuk/Selesai*), serta identitas petugas yang bertanggung jawab.
  * **Fungsi Ekspor & Cetak:** Dilengkapi tombol `[Cetak Laporan]` untuk mencetak dokumen pertanggungjawaban keuangan secara rapi.

---

## ⚙️ F. LANGKAH KERJA DAN IMPLEMENTASI TEKNIS

### Langkah 1: Perancangan Basis Data & Koneksi Aman (`config.php`)
1. **Perancangan Skema Relasional:** Membangun struktur tabel MySQL meliputi `tb_user`, `tb_area_parkir`, `tb_kendaraan`, `tb_transaksi`, dan `tb_log_aktivitas`.
2. **Keamanan Koneksi (PDO):** Mengimplementasikan ekstensi **PHP Data Objects (PDO)** menggunakan teknik *Prepared Statements* pada file `config.php` untuk mencegah celah keamanan *SQL Injection*.
3. **Enkripsi Kredensial:** Keamanan password akun dikelola menggunakan enkripsi hash algoritma SHA-256 / `password_hash()`.

```php
// Sample PDO Prepared Statement Implementation
$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();
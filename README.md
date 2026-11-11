# WasteLog 🌿
### Sistem Informasi Pencatatan dan Monitoring Pengelolaan Sampah Berbasis Web

> **Proyek Tugas Individu Mata Kuliah Aplikasi Teknologi Informasi**  
> Program Studi Kesehatan Lingkungan — Semester 5

---

## 📌 1. Tentang Aplikasi

**WasteLog** adalah aplikasi web komprehensif yang dikembangkan untuk mendigitalkan dan mengoptimalkan proses pencatatan, pemantauan, dan pelaporan pengelolaan sampah pada lingkungan kampus, Tempat Penampungan Sementara (TPS), maupun komunitas masyarakat.

Aplikasi ini dibangun menggunakan arsitektur **PHP Native** murni (tanpa framework) dengan standar keamanan industri, antarmuka modern responsif berbasis **Bootstrap 5**, grafik interaktif menggunakan **Chart.js**, serta basis data relasional **MySQL (InnoDB)** dengan transaksi atomik dan prepared statements.

---

## 🎯 2. Tujuan & Manfaat

1. **Efisiensi Pencatatan:** Mempermudah petugas dalam mendata setiap sampah yang masuk secara real-time.
2. **Kategorisasi Standar:** Mengelompokkan sampah berdasarkan jenis (Organik, Anorganik, Residu, dan B3).
3. **Kalkulasi & Status Otomatis:** Memantau berat sampah yang sudah dan belum dikelola secara matematis tanpa input status manual yang rentan kesalahan manusia.
4. **Dokumentasi Terintegrasi:** Mencatat seluruh proses pemilahan, pengomposan, daur ulang, dan pengangkutan.
5. **Transparansi & Pelaporan:** Menyediakan rekapitulasi data siap cetak (*print-friendly*) dan ekspor format CSV/Excel.
6. **Visualisasi Data Lingkungan:** Menyajikan tren bulanan, perbandingan rasio, dan komposisi kategori sampah melalui dashboard visual.

---

## 🛠️ 3. Teknologi yang Digunakan

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **Frontend** | HTML5, CSS3, JavaScript (ES6) | Antarmuka dinamis, responsif & bersih |
| **CSS Framework** | Bootstrap v5.3.2 | Grid responsif, modal, utility classes |
| **Ikon** | Bootstrap Icons & Font Awesome 6 | Simbol antarmuka informatif & konsisten |
| **Visualisasi Grafik** | Chart.js v4.4.1 | Doughnut Chart, Bar Chart, Grouped Bar |
| **Backend** | PHP Native (PHP 7.4 - 8.3+) | Tanpa framework pihak ketiga, arsitektur modular |
| **Database Driver** | PHP Data Objects (PDO) | Prepared Statements, Anti-SQL Injection |
| **Database** | MySQL / MariaDB (InnoDB) | Foreign Keys, Relational Constraints, Pessimistic Locking |
| **Server Lokal** | XAMPP (Apache & MySQL) / PHP Built-in | Siap dijalankan di localhost |

---

## 🎨 4. Konsep Desain & Skema Warna

Aplikasi mengusung tema ramah lingkungan yang modern, profesional, dan nyaman di mata:
- **Warna Utama (Primary):** Hijau Tua (`#166534`)
- **Warna Sekunder (Secondary/Accent):** Hijau Segar (`#22C55E`)
- **Latar Belakang:** Putih Bersih (`#ffffff`) dan Abu-Abu Lembut (`#f1f5f9`)
- **Status & Notifikasi:**
  - 🔵 **Informasi:** Biru Muda / Primary Soft
  - 🟡 **Dalam Proses / Peringatan:** Kuning Amber (`#ca8a04` / `#fefce8`)
  - 🔴 **Belum Dikelola / Bahaya:** Merah Coral (`#dc2626` / `#fee2e2`)
  - 🟢 **Selesai / Sukses:** Hijau Emerald (`#166534` / `#f0fdf4`)

---

## ✨ 5. Fitur Utama Sistem

### A. Autentikasi Administrator
- Login aman dengan validasi username dan password.
- Enkripsi kata sandi menggunakan standar industri `password_hash()` (Bcrypt).
- Verifikasi sandi dengan `password_verify()`.
- Regenerasi ID sesi (`session_regenerate_id(true)`) untuk mencegah *Session Fixation*.
- Pembatasan akses halaman internal (`includes/auth_check.php`).
- Logout aman dengan penghancuran sesi dan pembersihan cookie.

### B. Dashboard Interaktif
- **7 Kartu Statistik Utama:**
  1. Total Sampah Masuk (kg)
  2. Total Sampah Dikelola (kg)
  3. Total Sampah Belum Dikelola (kg)
  4. Persentase Pengelolaan (%)
  5. Jumlah Kategori Sampah
  6. Jumlah Lokasi Terdaftar
  7. Total Transaksi Sampah Masuk & Pengelolaan
- **3 Visualisasi Grafik Real-Time (Chart.js):**
  - *Doughnut Chart:* Distribusi berat sampah berdasarkan kategori.
  - *Bar Chart:* Tren sampah masuk per bulan (6 bulan terakhir).
  - *Grouped Bar Chart:* Perbandingan sampah masuk vs terkelola per bulan.
- **Tabel 10 Aktivitas Terbaru:** Pemantauan cepat sampah masuk terkini beserta status pengelolaannya.

### C. Manajemen Data Kategori Sampah
- Operasi CRUD (Create, Read, Update, Delete) kategori.
- Pencarian data instan dan navigasi halaman (*pagination*).
- **Integritas Relasional:** Kategori yang sedang digunakan pada data transaksi dilindungi secara ketat agar tidak dapat dihapus sembarangan.
- Kategori default: **Organik**, **Anorganik**, **Residu**, **B3**.

### D. Manajemen Data Lokasi
- Operasi CRUD pendataan titik asal sampah (contoh: Gedung A, Gedung B, Kantin, Laboratorium, Area Parkir, TPS Kampus).
- Pencarian cepat dan validasi relasi data sebelum penghapusan.

### E. Pencatatan Sampah Masuk (Fitur Inti)
- Formulir pencatatan: Tanggal masuk, kategori, lokasi, berat (kg), dan keterangan.
- Validasi numerik sisi server (berat harus angka positif > 0).
- Filter komprehensif: Kategori, lokasi, rentang tanggal awal-akhir, dan pencarian teks keterangan.
- Indikator sisa berat yang belum tertangani pada setiap baris data.
- Tombol shortcut aksi cepat **⚙️ Kelola Sampah** langsung dari baris tabel.

### F. Pengelolaan Sampah & Logika Status Otomatis
- Pilihan 5 jenis pengolahan:
  - **Pemilahan**
  - **Daur ulang**
  - **Pengomposan**
  - **Pengangkutan ke pengelola**
  - **Pengolahan lainnya**
- **Logika Perhitungan Status Otomatis:**
  - 🔴 **Belum Dikelola:** Berat dikelola = 0 kg.
  - 🟡 **Dalam Proses:** Sebagian berat sudah dikelola, masih terdapat sisa berat.
  - 🟢 **Selesai:** Seluruh berat sampah masuk telah tuntas dikelola (sisa = 0 kg).
- **Proteksi Transaksi Database:**
  - Menggunakan transaksi PDO (`beginTransaction`, `commit`, `rollBack`) dengan *pessimistic row locking* (`SELECT ... FOR UPDATE`).
  - Sistem secara tegas menolak input berat kelola yang melebihi sisa berat sampah yang ada.
  - Tombol bantuan instan **`[Kelola Seluruh Sisa (X kg) → Status Selesai]`** untuk menuntaskan sisa berat dengan 1 klik.

### G. Laporan Pengelolaan Sampah
- Filter fleksibel berdasarkan tanggal mulai, tanggal akhir, kategori, dan lokasi.
- Rekapitulasi 4 tabel terstruktur:
  1. Ringkasan performa terfilter (Total masuk, kelola, sisa, persentase).
  2. Rekapitulasi per Kategori.
  3. Rekapitulasi per Lokasi Asal.
  4. Rekapitulasi per Jenis Pengolahan.
  5. Tabel detail seluruh transaksi sampah masuk.
- **Cetak Laporan (`window.print()`):** Halaman memiliki style `@media print` khusus yang otomatis menyembunyikan sidebar, navbar, dan tombol navigasi sehingga hasil cetakan A4/PDF rapi dan formal.
- **Ekspor CSV:** Mengunduh file `.csv` dengan header UTF-8 BOM yang langsung terbaca rapi pada Microsoft Excel.

### H. Pengaturan Akun
- Melihat informasi pengguna administrator.
- Mengubah nama lengkap dan username (dengan pengecekan keunikan username agar tidak bentrok).
- Mengubah kata sandi dengan verifikasi wajib kata sandi lama.

---

## 📐 6. Logika Perhitungan Matematis

Aplikasi menjamin data selalu sinkron melalui rumus:

$$\text{Total Sampah Masuk} = \sum (\text{berat pada tabel } \texttt{sampah\_masuk})$$

$$\text{Total Sampah Dikelola} = \sum (\text{berat\_dikelola pada tabel } \texttt{pengelolaan})$$

$$\text{Total Belum Dikelola} = \text{Total Sampah Masuk} - \text{Total Sampah Dikelola}$$

$$\text{Sisa Berat per Sampah} = \text{Berat Sampah Masuk} - \sum (\text{Berat Kelola Terhubung})$$

$$\text{Persentase Pengelolaan} = \left( \frac{\text{Total Sampah Dikelola}}{\text{Total Sampah Masuk}} \right) \times 100\%$$

*(Jika Total Sampah Masuk adalah 0, persentase otomatis menampilkan 0%).*

---

## 🗂️ 7. Struktur Direktori Proyek

```
wastelog/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Stylesheet tema hijau, kartu statistik, & print style
│   ├── js/
│   │   └── script.js            # Sidebar toggle mobile, auto-dismiss alert, print handler
│   └── img/
│       └── logo.svg             # Logo vektor WasteLog
│
├── config/
│   └── koneksi.php              # Konfigurasi PDO MySQL, auto-detect BASE_URL, helper CSRF & format
│
├── includes/
│   ├── auth_check.php           # Middleware proteksi halaman login
│   ├── header.php               # CDN Bootstrap 5, Icons, Font Awesome, Chart.js
│   ├── navbar.php               # Top navbar sticky & profil dropdown
│   ├── sidebar.php              # Navigasi sidebar dinamis sesuai menu aktif
│   └── footer.php               # Footer aplikasi
│
├── auth/
│   ├── login.php                # Autentikasi login admin
│   └── logout.php               # Proses logout & penghapusan sesi
│
├── kategori/
│   ├── index.php                # List kategori & pencarian
│   ├── tambah.php               # Form tambah kategori
│   ├── edit.php                 # Form edit kategori
│   └── hapus.php                # Hapus kategori + validasi relasi
│
├── lokasi/
│   ├── index.php                # List lokasi & pencarian
│   ├── tambah.php               # Form tambah lokasi
│   ├── edit.php                 # Form edit lokasi
│   └── hapus.php                # Hapus lokasi + validasi relasi
│
├── sampah/
│   ├── index.php                # List sampah masuk, filter multi-kriteria, & status
│   ├── tambah.php               # Form input sampah masuk
│   ├── edit.php                 # Form edit sampah masuk
│   └── hapus.php                # Hapus sampah masuk + proteksi data pengelolaan
│
├── pengelolaan/
│   ├── index.php                # Riwayat proses pengelolaan & filter
│   ├── tambah.php               # Catat pengelolaan, hitung sisa, DB transaction
│   ├── edit.php                 # Form edit pengelolaan & validasi sisa maksimal
│   └── hapus.php                # Hapus pengelolaan (mengembalikan sisa otomatis)
│
├── laporan/
│   ├── index.php                # Rekapitulasi data & tampilan cetak (print-ready)
│   └── export.php               # Ekspor data laporan ke format CSV
│
├── pengaturan/
│   └── akun.php                 # Ganti profil & ganti kata sandi admin
│
├── database/
│   └── wastelog_db.sql          # File skema database & data simulasi
│
├── dashboard.php                # Dashboard visual utama
├── index.php                    # Router entry point aplikasi
└── README.md                    # Dokumentasi lengkap proyek
```

---

## 🚀 8. Panduan Instalasi & Penggunaan

### Prasyarat
- Komputer dengan sistem operasi Windows, macOS, atau Linux.
- Aplikasi **XAMPP** (disarankan versi dengan PHP 8.0 ke atas).

### Langkah 1: Penempatan Berkas
Pastikan folder `wastelog` berada di dalam direktori web server Anda:
- Contoh jika menggunakan XAMPP: `C:\xampp\htdocs\wastelog` atau `D:\xampp3\htdocs\wastelog`.

### Langkah 2: Jalankan Layanan Server
Buka aplikasi **XAMPP Control Panel**, lalu klik **Start** pada:
1. **Apache**
2. **MySQL**

### Langkah 3: Impor Basis Data
1. Buka browser web dan buka: `http://localhost/phpmyadmin`
2. Klik tab **Import** pada menu atas.
3. Klik tombol **Choose File** / **Pilih File**, arahkan ke file `database/wastelog_db.sql` di dalam folder proyek Anda.
4. Klik tombol **Import** di bagian bawah.
5. Database `wastelog_db` beserta 5 tabel dan data demonstrasinya akan terbuat otomatis.

### Langkah 4: Buka Aplikasi
Aplikasi dapat dijalankan melalui dua cara:

#### **Opsi A: Menggunakan Apache XAMPP (Standar)**
Buka browser dan akses alamat:
```
http://localhost/wastelog
```

#### **Opsi B: Menggunakan PHP Built-in Server**
Buka terminal/PowerShell di direktori proyek `wastelog`, lalu jalankan:
```powershell
php -S localhost:8000
```
Buka browser dan akses:
```
http://localhost:8000
```

---

### ☁️ Panduan Deploy ke Vercel (Online)

Aplikasi WasteLog sudah dilengkapi konfigurasi serverless (`vercel.json` dan `api/index.php`) sehingga dapat langsung dideploy ke **Vercel**.

> **Penting Mengenai Database di Vercel:**  
> Vercel adalah platform *serverless* (tidak menyediakan database lokal MySQL di dalamnya). Oleh karena itu, untuk online di Vercel, aplikasi membutuhkan database MySQL Cloud (dapat menggunakan layanan gratis seperti **Aiven**, **TiDB Cloud**, **Clever Cloud**, atau **Railway**).

#### **Langkah 1: Siapkan Database MySQL Cloud (Gratis)**
1. Buat database MySQL gratis di salah satu penyedia:
   - **Aiven for MySQL** (aiven.io) — Free Tier
   - **TiDB Cloud Serverless** (tidbcloud.com) — Free Tier
   - **Clever Cloud** (clever-cloud.com) — Free MySQL Add-on
2. Buka phpMyAdmin / tools query di cloud tersebut, lalu impor berkas `database/wastelog_db.sql`.
3. Catat kredensial koneksi: `Host`, `Port`, `Database Name`, `User`, dan `Password`.

#### **Langkah 2: Upload Proyek ke GitHub**
1. Inisialisasi Git di folder proyek:
   ```bash
   git init
   git add .
   git commit -m "Initial commit WasteLog"
   ```
2. Buat repository baru di [GitHub](https://github.com/new).
3. Hubungkan dan push kode Anda ke GitHub:
   ```bash
   git remote add origin https://github.com/username-anda/wastelog.git
   git branch -M main
   git push -u origin main
   ```

#### **Langkah 3: Deploy di Vercel**
1. Buka [Vercel](https://vercel.com) dan login menggunakan akun GitHub Anda.
2. Klik tombol **Add New...** -> **Project**.
3. Pilih repository `wastelog` yang telah di-push.
4. Pada bagian **Environment Variables**, tambahkan variabel database cloud Anda:
   - `DB_HOST`: *(Host MySQL Cloud Anda)*
   - `DB_PORT`: `3306` *(atau sesuai port cloud)*
   - `DB_NAME`: `wastelog_db`
   - `DB_USER`: *(Username MySQL Cloud)*
   - `DB_PASS`: *(Password MySQL Cloud)*
5. Klik **Deploy**! Vercel akan otomatis meng-compile dan memberikan URL publik aplikasi Anda (contoh: `https://wastelog.vercel.app`).

---

## 🔑 9. Akun Default Administrator

Gunakan akun berikut untuk masuk ke sistem:

- **Username:** `admin`
- **Password:** `admin123`

*(Anda dapat mengubah username dan password ini kapan saja melalui menu **Pengaturan Akun**).*

---

## 📊 10. Data Demonstrasi Bawaan

Database telah dilengkapi data simulasi awal yang realistis:
- **Kategori:** Organik, Anorganik, Residu, B3.
- **Lokasi:** Gedung A, Gedung B, Kantin, Laboratorium, Area Parkir.
- **Total Sampah Masuk:** 10 transaksi (Total: **218,00 kg**).
- **Total Pengelolaan:** 9 transaksi (Total: **120,00 kg**).
- **Sisa Belum Dikelola:** **98,00 kg**.
- **Persentase Pengelolaan:** **55,0%**.

---

## 🧪 11. Hasil Pengujian Sistem (17 Test Cases)

| No | Skenario Pengujian | Hasil Pengujian | Status |
|:---:|---|---|:---:|
| 1 | Login dengan kredensial benar | Berhasil masuk, session dibuat, redirect ke dashboard | **PASS** |
| 2 | Login dengan password salah | Ditolak dengan pesan kesalahan yang jelas | **PASS** |
| 3 | Tambah kategori sampah baru | Tersimpan dengan token CSRF valid | **PASS** |
| 4 | Edit kategori sampah | Data kategori terbarui di database | **PASS** |
| 5 | Hapus kategori yang belum terpakai | Berhasil dihapus dengan konfirmasi aman | **PASS** |
| 6 | Hapus kategori yang sudah terpakai | Ditolak oleh sistem untuk menjaga integritas relasi | **PASS** |
| 7 | Tambah data lokasi baru | Tersimpan dan langsung muncul pada pilihan transaksi | **PASS** |
| 8 | Input sampah masuk dengan validasi | Data tersimpan dengan status awal otomatis "Belum Dikelola" | **PASS** |
| 9 | Edit data sampah masuk | Berat tidak boleh diedit lebih kecil dari yang sudah dikelola | **PASS** |
| 10 | Hapus sampah yang sudah dikelola | Ditolak sistem (harus menghapus data pengelolaan terlebih dahulu) | **PASS** |
| 11 | Catat proses pengelolaan | Sisa berat berkurang, status otomatis menjadi "Dalam Proses" | **PASS** |
| 12 | Validasi kelola melebihi sisa | Sistem menolak input dan database melakukan rollback transaksi | **PASS** |
| 13 | Perubahan status ke "Selesai" | Saat sisa = 0 kg, status otomatis berubah menjadi "Selesai" (badge hijau) | **PASS** |
| 14 | Statistik dashboard real-time | Seluruh card dan grafik sinkron dengan kalkulasi database | **PASS** |
| 15 | Filter dan pencarian laporan | Tabel dan rekapitulasi menyesuaikan parameter filter terpilih | **PASS** |
| 16 | Cetak laporan dan ekspor CSV | Cetak bersih tanpa sidebar/navbar; CSV terbaca rapi di Excel | **PASS** |
| 17 | Proteksi akses tanpa login | Pengguna tanpa session otomatis dialihkan ke halaman login | **PASS** |

---

## 🛡️ 12. Aspek Keamanan Aplikasi

1. **Prepared Statements (PDO):** Mencegah serangan *SQL Injection*.
2. **Cross-Site Scripting (XSS) Prevention:** Output HTML di-escape menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF) Protection:** Token unik per sesi diverifikasi pada setiap request POST.
4. **Session Hijacking Mitigation:** Regenerasi ID sesi setelah otentikasi berhasil.
5. **Pessimistic Locking & Database Transactions:** Menggunakan `FOR UPDATE` untuk menjaga integritas perhitungan berat sampah di lingkungan multi-user.
6. **Bcrypt Password Hashing:** Tidak ada kata sandi yang disimpan dalam bentuk teks polos (*plaintext*).

---

## 📝 13. Lisensi & Pengembang

Aplikasi ini dikembangkan untuk keperluan akademik tugas mata kuliah **Aplikasi Teknologi Informasi** program studi **Kesehatan Lingkungan (Semester 5)**.

*WasteLog — Kelola Sampah Lebih Bijak, Pantau Lebih Mudah, Lingkungan Lebih Sehat.* 🌍♻️

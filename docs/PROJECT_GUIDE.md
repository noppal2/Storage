# Panduan Lengkap Source Code StorageQR / STOKLY

Dokumen ini menjelaskan seluruh file proyek `storage`, fungsi utamanya, hubungan antarfile, jalur request, struktur database, serta catatan perilaku dan risiko teknis.

## 1. Gambaran Sistem

Proyek ini adalah aplikasi inventaris/storage berbasis PHP native. Stack utamanya:

- PHP 8.x.
- MySQL/MariaDB melalui PDO.
- Bootstrap 5.3.3 melalui CDN.
- `html5-qrcode` melalui CDN untuk scanner kamera.
- QR image dibuat melalui API eksternal `api.qrserver.com`.
- Session PHP untuk login.
- Role: `admin`, `user`/Member, dan pseudo-role `guest`.

Fitur utama:

- Login, registrasi, logout, dan profil.
- Hak akses granular per member.
- CRUD barang, kategori, lokasi, dan unit fisik.
- Stok master dan unit fisik dengan QR code.
- Transaksi barang masuk/keluar.
- Scan QR barang dan pencatatan kunjungan ruangan.
- Riwayat, laporan HTML, dan laporan PDF.
- Dashboard dan activity feed.
- Layout desktop dan mobile.

## 2. Jalur Request Umum

Sebagian besar halaman mengikuti urutan berikut:

1. Browser meminta file PHP.
2. File memuat `bootstrap.php`.
3. Bootstrap memuat `config.php`, mengatur timezone/header/session, membuat koneksi PDO, dan menjalankan migrasi kompatibilitas database.
4. Helper autentikasi, permission, CSRF, URL, QR, dan format data tersedia.
5. Halaman memeriksa login/permission.
6. Untuk POST, halaman memvalidasi CSRF dan input, lalu menjalankan query.
7. Halaman memuat `partials/header.php` dan `partials/footer.php` jika memakai layout utama.
8. Output dikirim ke browser.

Jalur login:

```text
login.php
  -> bootstrap.php
  -> cek session
  -> POST: verifikasi CSRF
  -> cari login_attempts
  -> cari users aktif
  -> password_verify
  -> regenerate session ID
  -> simpan $_SESSION['user']
  -> redirect ke after_login atau index.php
```

Jalur transaksi:

```text
index.php?page=transaksi
  -> permission transaction_manage/admin
  -> beginTransaction
  -> SELECT barang FOR UPDATE
  -> validasi stok
  -> update stok master
  -> sinkronisasi barang_unit
  -> INSERT transaksi
  -> commit atau rollback
```

Jalur QR barang:

```text
units.php atau detail.php
  -> qr_image_url()
  -> QR berisi detail.php?kode=...
  -> detail.php mencari barang_unit dahulu
  -> fallback mencari barang master
  -> tampilkan informasi barang/unit
```

Jalur QR ruangan:

```text
QR ruangan
  -> room_visit.php?lokasi=...
  -> jika belum login, simpan after_login lalu redirect login
  -> validasi lokasi
  -> INSERT room_visits
  -> halaman sukses dan redirect
```

## 3. File Konfigurasi dan Fondasi

### `config.php`

Menyimpan konstanta koneksi database dan URL dasar:

- `DB_HOST`: default `localhost`.
- `DB_NAME`: default `storage_qr`.
- `DB_USER`: default `root`.
- `DB_PASS`: default kosong.
- `BASE_URL`: kosong agar URL dihitung dari request aktif.

File ini tidak menjalankan query dan tidak membuat output. Nilainya dibaca oleh `bootstrap.php`.

Catatan deployment: kredensial tersebut cocok untuk XAMPP lokal, tetapi tidak boleh dipakai sebagai konfigurasi produksi. `BASE_URL` sebaiknya ditetapkan eksplisit di balik reverse proxy atau domain tetap.

### `bootstrap.php`

Ini adalah pusat aplikasi.

#### Inisialisasi

- Memuat `config.php`.
- Menetapkan timezone `Asia/Jakarta`.
- Mengirim header `nosniff`, `SAMEORIGIN`, dan `Referrer-Policy: same-origin`.
- Mengaktifkan output buffering untuk mengganti branding `StorageQR` menjadi `STOKLY` pada sebagian request.
- Menyiapkan direktori `.sessions_runtime` dan session cookie.
- Menentukan flag `Secure` cookie berdasarkan HTTPS.
- Membuat `$pdo` dengan `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
- Mengatur timezone database ke `+07:00`.

#### Migrasi database runtime

Bootstrap memeriksa dan menambah struktur yang belum ada, antara lain:

- Kolom profil user: `email`, `telepon`, `bio`, `updated_at`.
- Tabel `user_permissions`.
- Tabel `login_attempts`.
- Tabel `room_visits` dan kolom kompatibilitas `identitas`/`keperluan`.
- Kolom barang: `status`, `tipe_barang`, `tanggal_expired`, `updated_at`.
- Tabel `barang_unit` beserta kolom tambahan.
- Enum status barang dan unit.
- Permission awal untuk user role `user`.

Bootstrap juga membaca barang lama dan membuat unit fisik sampai jumlah unit sama dengan `barang.stok`. Kode unit dibentuk seperti `KODE-001`.

#### Helper output dan URL

- `e($v)`: menjalankan `htmlspecialchars` untuk output HTML.
- `url($path)`: membentuk URL internal berdasarkan `BASE_URL` atau path script aktif.
- `public_url($path)`: membentuk URL lengkap berdasarkan protocol dan host.
- `qr_image_url($kode, $size)`: membuat URL QR untuk `detail.php`.
- `room_qr_image_url($locationId, $size)`: membuat URL QR untuk `room_visit.php`.

#### Helper autentikasi dan role

- `logged_in()`: true jika user bukan guest.
- `current_role()`: mengambil role session.
- `is_admin()`, `is_member()`: pemeriksaan role.
- `role_label()`: label tampilan role.
- `require_login()`: redirect ke login jika belum login.
- `admin_only()`: mengizinkan admin atau pengecualian permission tertentu untuk `units.php` dan transaksi.

#### Helper permission

- `permission_names()`: daftar permission dan labelnya.
- `has_direct_permission()`: query permission langsung user.
- `has_permission()`: memeriksa permission langsung dan permission yang mengimplikasikan akses lain.
- `permission_only()`: menghentikan akses jika permission tidak ada.
- `permission_any()`: mengizinkan jika salah satu permission tersedia.
- `flash()` dan `show_flash()`: pesan sementara berbasis session.
ant
Permission yang tersedia meliputi dashboard, barang, unit, scan, log ruangan, riwayat, laporan, serta pengelolaan barang/transaksi/kategori/lokasi/unit.

#### Helper keamanan dan data

- `is_post()`: pemeriksaan method POST.
- `csrf()`: membuat token CSRF session.
- `verify_csrf()`: mencocokkan token POST dengan token session.
- `location_name()`: menggabungkan gedung, ruang, dan rak.
- `expiry_status()`: mengubah tanggal expired menjadi label/class/status hari.
- `unit_status_from_barang_status()`: memetakan status master ke status unit.
- `sync_barang_units()`: menambah/menghapus unit dan mengisi data unit dari master.

Catatan teknis: migrasi DDL pada setiap request praktis untuk aplikasi lokal, tetapi menambah overhead. Direktori session berada di web root dan harus diblokir dari akses HTTP. `public_url()` mempercayai `HTTP_HOST`, sehingga deployment publik sebaiknya memakai `BASE_URL` tetap.

### `database.sql`

Schema awal database. Tabel utama:

- `users`: akun, role, password hash, status aktif, profil.
- `user_permissions`: permission per user.
- `kategori`: master kategori barang.
- `lokasi`: master gedung/ruang/rak.
- `barang`: master barang, stok, kondisi, status, kategori, lokasi.
- `barang_unit`: unit fisik individual dan statusnya.
- `transaksi`: barang masuk/keluar dan pelakunya.
- `room_visits`: kunjungan ruangan/scan.

Relasi penting:

- Barang ke kategori/lokasi: `ON DELETE SET NULL`.
- Unit ke barang: `ON DELETE CASCADE`.
- Unit ke lokasi: `ON DELETE SET NULL`.
- Transaksi ke barang/user.
- `room_visits` menyimpan ID opsional, tetapi schema awal tidak memasang semua foreign key.

Schema aktual bisa lebih baru daripada file ini karena `bootstrap.php` melakukan migrasi runtime. `login_attempts` terutama dibuat oleh bootstrap.

## 4. Instalasi dan Akun

### `install.php`

Alurnya:

1. Membuka koneksi server database.
2. Membuat database jika belum ada.
3. Membaca `database.sql`.
4. Memecah statement SQL berdasarkan `;`.
5. Menjalankan schema.
6. Membuat akun admin awal `admin` dengan password `admin123`.
7. Menampilkan link login.

Risiko operasional:

- File installer dapat dipanggil ulang jika tidak dihapus/dibatasi.
- Password default harus segera diganti.
- Exception database ditampilkan langsung.
- Pemisahan SQL dengan `explode(';')` bukan parser SQL penuh.

### `README.md`

Panduan singkat instalasi XAMPP, konfigurasi database, pemanggilan installer, akun admin awal, fitur aplikasi, dan kebutuhan HTTPS untuk kamera smartphone. Isinya bersifat onboarding, sedangkan dokumen ini menjelaskan arsitektur dan jalur internal.

## 5. Autentikasi, Member, dan Profile

### `login.php`

- Menolak user yang sudah login.
- Memvalidasi CSRF pada POST.
- Menormalisasi username menjadi lowercase.
- Mengecek percobaan gagal berdasarkan username + IP.
- Mengunci sekitar 15 menit setelah lima kegagalan.
- Mencari user aktif dengan prepared statement.
- Memeriksa password memakai `password_verify()`.
- Melakukan rehash bila perlu.
- Menghapus catatan kegagalan setelah sukses.
- Meregenerasi session ID.
- Menyimpan ID, nama, username, dan role ke `$_SESSION['user']`.
- Menggunakan `after_login` untuk kembali ke halaman awal yang diminta.

Tampilan berisi input username/password, alert error, toggle visibility password, dan link registrasi.

### `register.php`

- Menerima nama, username, dan password.
- Memastikan username belum dipakai.
- Memeriksa password minimal 8 karakter serta huruf besar, kecil, dan angka.
- Menyimpan password dengan `password_hash()`.
- Membuat akun role `user`.
- Memberi permission awal `dashboard_view`, `barang_view`, `units_view`, dan `scan_view`.

Tidak ada email verification atau approval admin. Exception database pada praktiknya diperlakukan sebagai kegagalan username.

### `logout.php`

Menghancurkan session lalu redirect ke `login.php`. Karena dipicu GET, logout dapat dipanggil dari link biasa tanpa CSRF.

### `members.php`

Modul admin untuk:

- Membuat akun.
- Mengedit nama, username, role, dan status aktif.
- Mereset password.
- Menghapus akun.
- Menyimpan permission user.

Proteksi memakai `admin_only()` dan CSRF. Admin tidak dapat menonaktifkan atau menurunkan role dirinya sendiri. User yang memiliki riwayat transaksi tidak dapat dihapus agar referensi transaksi tidak rusak.

### `permissions.php`

Modul admin untuk mencentang permission per user. Permission di-whitelist dengan `array_intersect()`. Admin secara logika otomatis memiliki semua permission, sehingga checkbox permission admin lebih bersifat data tampilan/administratif.

### `profile.php`

- Menampilkan profil user aktif.
- Mengubah nama, username, email, telepon, dan bio.
- Mengganti password.
- Memeriksa password lama sebelum perubahan password.
- Mengupdate data session setelah nama/username berubah.
- Menampilkan role dan tanggal pendaftaran.

Dilindungi `require_login()` dan CSRF. Username, email, telepon, panjang bio, dan kekuatan password divalidasi.

## 6. Dashboard, Barang, dan Unit

### `index.php`

Ini adalah router, controller POST, query dashboard, dan view utama.

#### Pemilihan halaman

Parameter halaman mengarahkan view ke dashboard, barang, transaksi, riwayat, scan, kunjungan, kategori, atau lokasi.

#### Dashboard

Mengambil statistik stok, transaksi, kategori, barang terbaru, dan aktivitas. Activity feed tambahan diambil browser dari `activity_feed.php`.

#### Barang

Mendukung tambah/edit/soft delete barang, termasuk:

- Nama dan kode.
- Kategori dan lokasi.
- Kondisi.
- Status.
- Tipe barang.
- Stok.
- Tanggal masuk dan expired.
- Spesifikasi/catatan sesuai form.

Barang dihapus secara soft delete dengan `aktif=0`. Pembuatan/perubahan stok memanggil `sync_barang_units()`.

#### Kategori dan lokasi

Mendukung insert/delete kategori dan lokasi. Lokasi dapat dipakai sebagai lokasi barang, lokasi unit, serta lokasi QR kunjungan ruangan.

#### Kunjungan

Menerima kode scan, mencari unit lebih dahulu lalu barang master, dan menulis ke `room_visits`. Jalur ini tidak mengurangi stok dan tidak mengubah status unit.

#### Transaksi

- Membuka database transaction.
- Mengunci row barang dengan `SELECT ... FOR UPDATE`.
- Memvalidasi stok untuk barang keluar.
- Memperbarui stok master.
- Membuat atau mengubah status unit fisik.
- Menulis record ke `transaksi`.
- Commit saat sukses atau rollback saat error.

Untuk barang keluar, unit berstatus `tersedia` dipilih lalu diubah menjadi `keluar`. Untuk barang masuk, unit tambahan dibuat.

#### Scanner

Menggunakan `Html5QrcodeScanner`, memprioritaskan kamera belakang, membatasi format QR, dan menyediakan pemindaian dari file foto. Kamera live memerlukan secure context/HTTPS. Hasil QR diarahkan ke detail barang.

Catatan: kode barang baru dihitung melalui `AUTO_INCREMENT` metadata; pada request bersamaan perlu diwaspadai collision. Validasi tanggal server-side juga sebaiknya diperketat.

### `detail.php`

Menerima parameter `kode` dan:

1. Mencari `barang_unit.kode_unit`.
2. Jika tidak ada, mencari `barang.kode`.
3. Menolak barang nonaktif.
4. Menampilkan detail barang/unit, lokasi, kondisi, status, expiry, catatan, dan statistik unit.
5. Menampilkan QR menuju halaman detail.

Query memakai prepared statement dan output memakai escaping. Halaman ini relatif publik untuk kebutuhan scan, walaupun menu scan/detail tetap dipengaruhi permission.

Catatan: query yang mengambil unit belum melakukan join lokasi khusus unit dengan lengkap, sehingga lokasi unit dapat fallback ke lokasi master atau tidak muncul sesuai harapan.

### `units.php`

Menampilkan daftar unit fisik dengan filter nama/kode/unit/catatan dan QR tiap unit. Field yang dapat diedit:

- Nama unit.
- Lokasi unit.
- Spesifikasi.
- Kondisi.
- Catatan.
- Tanggal expired.
- Status unit.

GET dikontrol melalui permission tampilan. POST memerlukan `unit_manage` dan CSRF. Update dibatasi berdasarkan ID unit dan barang.

Catatan UI: kontrol edit dirender eksplisit untuk admin, sehingga member yang punya `unit_manage` mungkin memiliki akses backend tetapi tidak melihat kontrol edit.

## 7. Aktivitas, Kunjungan, dan QR

### `activity_feed.php`

Endpoint JSON yang mengambil transaksi terbaru, join ke barang dan user, memformat waktu, mengurutkan aktivitas, lalu mengembalikan maksimal delapan item.

Dipanggil JavaScript header setiap sekitar lima detik. Endpoint ini tidak meminta login/permission, sehingga nama user, barang, jenis, jumlah, dan waktu aktivitas dapat dibaca publik.

### `room_visit.php`

Endpoint QR lokasi.

- Membaca ID lokasi dari GET/POST.
- Menyimpan `after_login` jika pengunjung belum login.
- Memvalidasi lokasi.
- Mengambil user aktif.
- Menulis `room_visits` dengan kode `ROOM-{id}`.
- Mengisi tujuan default `Pengambilan barang`.
- Menampilkan sukses lalu mengarahkan ulang.

Perilaku penting: GET yang berhasil langsung menulis database. Refresh/replay QR dapat mencatat duplikat dan tidak memakai CSRF. Desain yang lebih kuat adalah halaman konfirmasi lalu POST dengan token sekali pakai.

### `room_logs.php`

Menampilkan maksimal 100 kunjungan ruangan untuk admin atau user dengan `room_log_view`. Data mencakup waktu, nama, identitas, ruangan, dan keperluan. Output di-escape.

## 8. Riwayat dan Laporan

### `laporan.php`

Laporan HTML yang dilindungi `reports_view`.

- Mendukung periode harian dan bulanan.
- Join transaksi, barang, dan users.
- Menghitung total, barang masuk, dan barang keluar.
- Menampilkan tabel yang bisa dicetak browser.
- Menyediakan link PDF melalui `invoice_pdf.php`.
- Menampilkan jam/tanggal pada UI melalui JavaScript.

Validasi tanggal/periode masih perlu dibuat lebih ketat agar input invalid tidak masuk ke `strtotime()` dengan hasil tak terduga.

### `invoice_pdf.php`

Shim kompatibilitas. Isinya hanya memuat `invoice_pdf_v2.php`, sehingga URL lama tetap bekerja.

### `invoice_pdf_v2.php`

Generator PDF manual tanpa library eksternal.

Helper PDF:

- `pdf_clean()`: membersihkan teks untuk format PDF.
- `pdf_text()`: menulis teks pada posisi tertentu.
- `pdf_line()`: menggambar garis.
- `pdf_fill()`: menggambar area berwarna.
- `pdf_document()`: menyusun halaman, header, tabel, footer, dan output PDF.

PDF berformat landscape A4, berisi header STOKLY, ringkasan transaksi, tabel transaksi, pagination, footer, dan user pencetak. Endpoint dilindungi `reports_view`.

Keterbatasan: font Helvetica dan konversi encoding dapat kehilangan karakter non-Latin; tidak ada digital signature; implementasi PDF manual lebih sulit dirawat dibanding library khusus.

## 9. Layout dan Frontend

### `partials/header.php`

Menyediakan shell halaman:

- Judul halaman.
- Sidebar desktop.
- Navbar/offcanvas mobile.
- Menu berdasarkan permission.
- Dropdown akun.
- Flash message.
- Hook activity feed.
- Link profile dan elemen laporan.

Kontrol menu di frontend bukan pengganti pemeriksaan server-side. Endpoint tetap harus memeriksa permission.

### `partials/footer.php`

Menyediakan:

- Penutup layout.
- Bootstrap bundle CDN.
- Konfirmasi `[data-confirm]`.
- Dukungan scan foto/fallback.
- Toggle sidebar mobile.
- Enhancement dropdown dan tombol laporan.

Ketergantungan CDN berarti Bootstrap/scanner tidak tersedia jika CDN gagal atau akses jaringan dibatasi.

### `assets/style.css`

Stylesheet utama aplikasi. Cakupannya:

- Variabel warna, background, dan typography.
- Sidebar, topbar, card, tabel, form, badge.
- Responsive desktop/mobile.
- Halaman login.
- Scanner.
- Profile dan dropdown user.
- Panel laporan.
- Animasi scanner.
- Toggle password.

Gaya akhirnya memakai `Segoe UI`, Arial, dan Helvetica Neue. Banyak selector komponen didefinisikan ulang karena style ditambahkan bertahap; rule terakhir mengalahkan rule sebelumnya. Selector `:has()` memerlukan browser modern. Aturan reduced-motion scanner masih perlu diperbaiki karena saat ini tetap menjalankan animasi.

### `phone-access.html`

Panduan akses aplikasi dari HP melalui jaringan LAN.

- Menjelaskan agar HP dan komputer berada di Wi-Fi yang sama.
- Menautkan sertifikat `assets/storage-lan.crt`.
- Membuka scanner melalui HTTPS.
- Menyediakan instruksi Android dan iPhone/iPad.
- Membentuk URL memakai `location.hostname`.

Halaman ini mengasumsikan path aplikasi `/storage/` dan sertifikat sesuai hostname. Firewall, Apache, dan kepercayaan sertifikat tetap harus dikonfigurasi di mesin pengguna.

## 10. Diagram dan File Non-Runtime

### `flowchart-storageqr.drawio`

Diagram proses tingkat tinggi: pengunjung membuka website, memilih akun/guest, registrasi, login, validasi, percabangan admin/member/guest, dashboard, lalu logout.

Diagram belum menggambarkan permission granular, CSRF, transaksi stok, sinkronisasi unit, QR barang, QR ruangan, dan laporan PDF. Jadi diagram berguna untuk orientasi, tetapi bukan representasi lengkap runtime.

### `ui-dashboard.png`, `ui-login.png`, `ui-riwayat.png`

Screenshot referensi UI. Tidak dieksekusi oleh aplikasi dan tidak memengaruhi database atau routing.

### `assets/storage-lan.crt`

Sertifikat HTTPS LAN untuk kebutuhan akses kamera smartphone. Ini bukan source PHP, tetapi dipakai oleh `phone-access.html`.

### Direktori runtime/tooling

- `.git/`: metadata repository.
- `.sessions/`: session/runtime lama.
- `.sessions_runtime/`: lokasi session yang dipakai bootstrap.
- `.chrome-profile/` dan `.edge-profile/`: profil browser/tooling lokal.

Direktori tersebut bukan source aplikasi. `.sessions_runtime` tetap penting untuk keamanan karena berisi data session.

## 11. Peta Permission

| Permission | Area |
|---|---|
| `dashboard_view` | Dashboard |
| `barang_view` | Melihat barang |
| `units_view` | Melihat unit/QR |
| `scan_view` | Scanner QR |
| `room_log_view` | Log kunjungan ruangan |
| `history_view` | Riwayat transaksi |
| `reports_view` | Laporan HTML/PDF |
| `barang_manage` | Kelola barang |
| `transaction_manage` | Kelola transaksi |
| `category_manage` | Kelola kategori |
| `location_manage` | Kelola lokasi |
| `unit_manage` | Kelola detail unit |

Admin selalu lolos `has_permission()`. User biasa perlu permission langsung atau permission implisit yang didefinisikan di `bootstrap.php`.

## 12. Catatan Keamanan dan Perbaikan Prioritas

1. Hapus atau lindungi `install.php` setelah instalasi dan wajibkan penggantian password default.
2. Blokir akses HTTP ke `.sessions_runtime` dan hindari mode directory `0777` jika tidak diperlukan.
3. Tetapkan `BASE_URL`/host yang dipercaya untuk QR, bukan mempercayai `HTTP_HOST` mentah.
4. Lindungi atau autentikasi `activity_feed.php` jika aktivitas tidak boleh publik.
5. Ubah pencatatan `room_visit.php` dari GET langsung menjadi konfirmasi + POST CSRF/token anti-replay.
6. Tambahkan validasi server-side untuk tanggal transaksi dan periode laporan.
7. Perbaiki join lokasi unit di `detail.php`.
8. Selaraskan rendering kontrol edit `units.php` dengan permission `unit_manage`, bukan hanya role admin.
9. Pertimbangkan memindahkan migrasi schema dari setiap request ke proses deploy/installer.
10. Pertimbangkan menyimpan/generate QR secara lokal bila URL data tidak boleh dikirim ke pihak ketiga.
11. Tambahkan Content Security Policy dan pertimbangkan mengunci versi/hash CDN.
12. Perbaiki aturan `prefers-reduced-motion` agar benar-benar mematikan animasi scanner.

## 13. Ringkasan Tanggung Jawab File

| File | Tanggung jawab |
|---|---|
| `config.php` | Konfigurasi database dan URL |
| `bootstrap.php` | Fondasi aplikasi, migrasi, helper, auth, permission |
| `database.sql` | Schema awal |
| `install.php` | Instalasi database dan admin awal |
| `login.php` | Login dan rate limit |
| `register.php` | Registrasi member |
| `logout.php` | Logout |
| `members.php` | Manajemen akun |
| `permissions.php` | Manajemen permission |
| `profile.php` | Profil dan password |
| `index.php` | Router/dashboard/CRUD/transaksi/scanner |
| `detail.php` | Detail barang/unit dari QR |
| `units.php` | Daftar dan edit unit fisik |
| `activity_feed.php` | Feed aktivitas JSON |
| `room_visit.php` | Pencatatan kunjungan QR ruangan |
| `room_logs.php` | Daftar log kunjungan |
| `laporan.php` | Laporan HTML |
| `invoice_pdf.php` | Shim URL PDF lama |
| `invoice_pdf_v2.php` | Generator PDF |
| `partials/header.php` | Header, sidebar, menu, akun |
| `partials/footer.php` | Script/footer/layout behavior |
| `assets/style.css` | Tampilan dan responsive layout |
| `phone-access.html` | Panduan akses HP/LAN |
| `flowchart-storageqr.drawio` | Diagram alur tingkat tinggi |
| `README.md` | Panduan instalasi singkat |

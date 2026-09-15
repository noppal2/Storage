<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Jakarta');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'invoice_pdf.php') {
    ob_start(static fn($output) => str_replace(['StorageQR', 'Storage QR'], 'STOKLY', $output));
}

// Gunakan direktori baru agar sesi lama dengan ACL yang rusak tidak ikut terbaca.
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . '.sessions_runtime';
    if (!is_dir($sessionPath)) @mkdir($sessionPath, 0777, true);
    session_save_path($sessionPath);
    $secureSession = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secureSession, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
if (!isset($_SESSION['user'])) $_SESSION['user'] = ['id' => 0, 'nama' => 'Guest', 'role' => 'guest'];
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("SET time_zone = '+07:00'");
    // Kolom kontak bersifat opsional agar akun lama tetap kompatibel.
    foreach ([
        'email' => "VARCHAR(150) NULL AFTER username",
        'telepon' => "VARCHAR(30) NULL AFTER email",
        'bio' => "VARCHAR(255) NULL AFTER telepon",
        'updated_at' => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ] as $columnName => $definition) {
        if (!$pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($columnName))->fetch()) {
            $pdo->exec("ALTER TABLE users ADD $columnName $definition");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_permissions (user_id INT NOT NULL,permission VARCHAR(50) NOT NULL,PRIMARY KEY(user_id,permission),CONSTRAINT fk_permission_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) NOT NULL,ip_address VARCHAR(45) NOT NULL,failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,first_failed_at DATETIME NULL,locked_until DATETIME NULL,last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY login_attempt_identity (username,ip_address),KEY login_attempt_lock (locked_until)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS room_visits (id INT AUTO_INCREMENT PRIMARY KEY,nama VARCHAR(120) NOT NULL,ruangan VARCHAR(150) NOT NULL,kode_scan VARCHAR(100) NOT NULL,barang_id INT NULL,unit_id INT NULL,tanggal DATE NOT NULL,jam TIME NOT NULL,user_id INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,KEY room_visits_created (created_at)) ENGINE=InnoDB");
    $hasVisitIdentity = $pdo->query("SHOW COLUMNS FROM room_visits LIKE 'identitas'")->fetch();
    if (!$hasVisitIdentity) $pdo->exec("ALTER TABLE room_visits ADD identitas VARCHAR(120) NULL AFTER nama");
    $hasVisitPurpose = $pdo->query("SHOW COLUMNS FROM room_visits LIKE 'keperluan'")->fetch();
    if (!$hasVisitPurpose) $pdo->exec("ALTER TABLE room_visits ADD keperluan VARCHAR(255) NULL AFTER ruangan");
    $pdo->exec("INSERT IGNORE INTO user_permissions(user_id,permission) SELECT u.id,p.permission FROM users u JOIN (SELECT 'dashboard_view' permission UNION ALL SELECT 'barang_view' UNION ALL SELECT 'units_view') p ON u.role='user' LEFT JOIN user_permissions up ON up.user_id=u.id AND up.permission=p.permission WHERE up.user_id IS NULL");
    $hasStatus = $pdo->query("SHOW COLUMNS FROM barang LIKE 'status'")->fetch();
    if (!$hasStatus) {
        $pdo->exec("ALTER TABLE barang ADD status ENUM('Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan','Terpakai','Rusak') NOT NULL DEFAULT 'Tersedia' AFTER kondisi");
    } elseif (strpos((string)$hasStatus['Type'], "'Terpakai'") === false) {
        $pdo->exec("ALTER TABLE barang MODIFY status ENUM('Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan','Terpakai','Rusak') NOT NULL DEFAULT 'Tersedia'");
    }
    $hasItemType = $pdo->query("SHOW COLUMNS FROM barang LIKE 'tipe_barang'")->fetch();
    if (!$hasItemType) $pdo->exec("ALTER TABLE barang ADD tipe_barang VARCHAR(20) NOT NULL DEFAULT 'umum' AFTER status");
    $hasExpiry = $pdo->query("SHOW COLUMNS FROM barang LIKE 'tanggal_expired'")->fetch();
    if (!$hasExpiry) $pdo->exec("ALTER TABLE barang ADD tanggal_expired DATE NULL AFTER tanggal_masuk");
    $hasUpdatedAt = $pdo->query("SHOW COLUMNS FROM barang LIKE 'updated_at'")->fetch();
    if (!$hasUpdatedAt) {
        $pdo->exec("ALTER TABLE barang ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS barang_unit (id INT AUTO_INCREMENT PRIMARY KEY,barang_id INT NOT NULL,kode_unit VARCHAR(80) NOT NULL UNIQUE,nama_unit VARCHAR(150) NULL,lokasi_id INT NULL,spesifikasi TEXT NULL,kondisi ENUM('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',catatan TEXT NULL,tanggal_expired DATE NULL,status ENUM('tersedia','keluar','terpakai','rusak') NOT NULL DEFAULT 'tersedia',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_unit_barang FOREIGN KEY(barang_id) REFERENCES barang(id) ON DELETE CASCADE,CONSTRAINT fk_unit_lokasi FOREIGN KEY(lokasi_id) REFERENCES lokasi(id) ON DELETE SET NULL) ENGINE=InnoDB");
    foreach (["nama_unit VARCHAR(150) NULL AFTER kode_unit", "lokasi_id INT NULL AFTER nama_unit", "spesifikasi TEXT NULL AFTER lokasi_id", "kondisi ENUM('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik' AFTER spesifikasi", "catatan TEXT NULL AFTER kondisi", "tanggal_expired DATE NULL AFTER catatan"] as $column) {
        $columnName = strtok($column, ' ');
        if (!$pdo->query("SHOW COLUMNS FROM barang_unit LIKE '$columnName'")->fetch()) $pdo->exec("ALTER TABLE barang_unit ADD $column");
    }
    $pdo->exec("ALTER TABLE barang_unit MODIFY status ENUM('tersedia','keluar','terpakai','rusak') NOT NULL DEFAULT 'tersedia'");
    $legacyItems = $pdo->query('SELECT id,kode,stok FROM barang WHERE aktif=1')->fetchAll(PDO::FETCH_ASSOC);
    $unitInsert = $pdo->prepare('INSERT INTO barang_unit(barang_id,kode_unit) VALUES(?,?)');
    $unitCount = $pdo->prepare('SELECT COUNT(*) FROM barang_unit WHERE barang_id=?');
    foreach ($legacyItems as $item) {
        $unitCount->execute([$item['id']]);
        $existing = (int)$unitCount->fetchColumn();
        for ($number = $existing + 1; $number <= (int)$item['stok']; $number++) {
            $unitInsert->execute([$item['id'], $item['kode'].'-'.str_pad((string)$number, 3, '0', STR_PAD_LEFT)]);
        }
    }
} catch (PDOException $e) { die('Koneksi database gagal. Jalankan install.php dan cek config.php.'); }
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url($path = '') {
    if (BASE_URL !== '') return BASE_URL . '/' . ltrim($path, '/');
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/storage/index.php')), '/');
    return ($basePath === '.' ? '' : $basePath) . '/' . ltrim($path, '/');
}
function public_url($path = '') {
    if (BASE_URL !== '') return url($path);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}
function qr_image_url($kode, $size = 300) {
    $target = public_url('detail.php?kode=' . rawurlencode($kode));
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$size . 'x' . (int)$size . '&format=png&data=' . rawurlencode($target);
}
function room_qr_image_url($locationId, $size = 300) {
    $target = public_url('room_visit.php?lokasi=' . (int)$locationId);
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$size . 'x' . (int)$size . '&format=png&data=' . rawurlencode($target);
}
function logged_in() { return isset($_SESSION['user']) && $_SESSION['user']['role'] !== 'guest'; }
function password_is_strong($password) { return strlen($password) >= 8 && preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password) && preg_match('/[0-9]/', $password); }
function current_role() { return $_SESSION['user']['role'] ?? 'guest'; }
function is_admin() { return current_role() === 'admin'; }
function is_member() { return current_role() === 'user'; }
function role_label($role = null) { $role = $role ?? current_role(); return $role === 'admin' ? 'Admin' : ($role === 'user' ? 'Member' : 'Guest'); }
function require_login() { if (!logged_in()) { header('Location: '.url('login.php')); exit; } }
function admin_only() {
    require_login();
    if (is_admin()) return;
    $permission = null;
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'units.php') $permission = 'unit_manage';
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php' && ($_POST['action'] ?? '') === 'save_transaksi') $permission = 'transaction_manage';
    if ($permission && has_permission($permission)) return;
    flash('danger', 'Akses hanya untuk fitur yang diizinkan Admin.'); header('Location: '.url('index.php')); exit;
}
function permission_names() { return ['dashboard_view'=>'Dashboard','barang_view'=>'Lihat data barang','units_view'=>'Lihat QR/unit','scan_view'=>'Scan QR Code barang','room_log_view'=>'Lihat log aktivitas ruangan','history_view'=>'Lihat riwayat','reports_view'=>'Lihat/cetak laporan','barang_manage'=>'Kelola barang','transaction_manage'=>'Kelola transaksi','category_manage'=>'Kelola kategori','location_manage'=>'Kelola lokasi','unit_manage'=>'Kelola detail unit']; }
function has_permission($permission) {
    if (is_admin()) return true;
    if (!logged_in()) return false;
    $impliedPermissions = ['barang_view'=>['barang_manage'],'units_view'=>['unit_manage'],'history_view'=>['transaction_manage'],'dashboard_view'=>['barang_manage','transaction_manage','category_manage','location_manage','unit_manage']];
    foreach ($impliedPermissions[$permission] ?? [] as $implied) {
        if (has_direct_permission($implied)) return true;
    }
    return has_direct_permission($permission);
}
function has_direct_permission($permission) {
    if (is_admin()) return true;
    global $pdo;
    $s = $pdo->prepare('SELECT 1 FROM user_permissions WHERE user_id=? AND permission=?');
    $s->execute([$_SESSION['user']['id'], $permission]);
    return (bool)$s->fetchColumn();
}
function permission_only($permission) {
    require_login();
    if (!has_permission($permission)) { flash('danger', 'Anda belum mendapat izin untuk fitur ini.'); header('Location: '.url('index.php')); exit; }
}
function permission_any(...$permissions) {
    require_login();
    foreach ($permissions as $permission) if (has_permission($permission)) return;
    flash('danger', 'Anda belum mendapat izin untuk fitur ini.'); header('Location: '.url('index.php')); exit;
}
function flash($type, $message) { $_SESSION['flash'] = [$type, $message]; }
function show_flash() { if (!empty($_SESSION['flash'])) { [$t,$m]=$_SESSION['flash']; unset($_SESSION['flash']); echo '<div class="alert alert-'.e($t).' alert-dismissible fade show">'.e($m).'<button class="btn-close" data-bs-dismiss="alert"></button></div>'; } }
function is_post() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function csrf() { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Permintaan tidak valid.'); }
function location_name($x) { return trim(implode(' / ', array_filter([$x['gedung'] ?? '', $x['ruang'] ?? '', $x['rak'] ?? '']))); }
function expiry_status($date) {
    if (!$date) return null;
    try { $expiry = new DateTimeImmutable($date); } catch (Throwable $e) { return null; }
    $days = (int)(new DateTimeImmutable('today'))->diff($expiry)->format('%r%a');
    if ($days < 0) return ['label'=>'Kedaluwarsa', 'class'=>'danger', 'days'=>$days];
    if ($days <= 30) return ['label'=>'Segera kedaluwarsa', 'class'=>'warning', 'days'=>$days];
    return ['label'=>'Berlaku', 'class'=>'success', 'days'=>$days];
}
function sync_barang_units($barangId, $kode, $targetStock) {
    global $pdo;
    $master = $pdo->prepare('SELECT nama,lokasi_id,tanggal_expired FROM barang WHERE id=?');
    $master->execute([$barangId]);
    $masterData = $master->fetch(PDO::FETCH_ASSOC) ?: ['nama'=>'Barang', 'lokasi_id'=>null, 'tanggal_expired'=>null];
    $s = $pdo->prepare('SELECT COUNT(*) FROM barang_unit WHERE barang_id=?');
    $s->execute([$barangId]);
    $current = (int)$s->fetchColumn();
    if ($targetStock > $current) {
        $insert = $pdo->prepare('INSERT INTO barang_unit(barang_id,kode_unit,nama_unit,lokasi_id,tanggal_expired) VALUES(?,?,?,?,?)');
        for ($number = $current + 1; $number <= $targetStock; $number++) {
            $unitCode = $kode.'-'.str_pad((string)$number, 3, '0', STR_PAD_LEFT);
            $insert->execute([$barangId, $unitCode, $masterData['nama'].' '.$number, $masterData['lokasi_id'], $masterData['tanggal_expired']]);
        }
    } elseif ($targetStock < $current) {
        $remove = $pdo->prepare('SELECT id FROM barang_unit WHERE barang_id=? AND status="tersedia" ORDER BY id DESC LIMIT '.($current - $targetStock));
        $remove->execute([$barangId]);
        $ids = $remove->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) < $current - $targetStock) throw new Exception('Stok tidak dapat dikurangi karena sebagian unit sudah berstatus keluar atau rusak.');
        $delete = $pdo->prepare('DELETE FROM barang_unit WHERE id=?');
        foreach ($ids as $unitId) $delete->execute([$unitId]);
    }
    $fillEmpty = $pdo->prepare("UPDATE barang_unit SET nama_unit=COALESCE(NULLIF(nama_unit,''), CONCAT(?, ' ', SUBSTRING_INDEX(kode_unit, '-', -1))), lokasi_id=COALESCE(lokasi_id, ?), tanggal_expired=COALESCE(tanggal_expired, ?) WHERE barang_id=?");
    $fillEmpty->execute([$masterData['nama'], $masterData['lokasi_id'], $masterData['tanggal_expired'], $barangId]);
}

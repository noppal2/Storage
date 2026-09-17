<?php
require_once __DIR__.'/bootstrap.php';
// Menentukan halaman yang diminta dan memeriksa akses sebelum memuat data.
$page = $_GET['page'] ?? '';
if ($page === 'kunjungan') {
    header('Location: '.url('room_logs.php'));
    exit;
}
// URL utama menampilkan Dashboard agar guest dapat kembali dari Data Barang.
$page = $page ?: 'dashboard';
if (!logged_in() && !in_array($page, ['dashboard', 'barang'], true)) {
    header('Location: '.url('login.php'));
    exit;
}
$pagePermissions = ['dashboard' => 'dashboard_view','barang' => 'barang_view','transaksi' => 'transaction_manage','riwayat' => 'history_view','scan' => 'scan_view','kunjungan' => 'scan_view','kategori' => 'category_manage','lokasi' => 'location_manage'];
if (logged_in() && isset($pagePermissions[$page])) {
    permission_only($pagePermissions[$page]);
}
$categories = $pdo->query('SELECT * FROM kategori ORDER BY nama')->fetchAll(PDO::FETCH_ASSOC);
$locations = $pdo->query('SELECT * FROM lokasi ORDER BY gedung,ruang,rak')->fetchAll(PDO::FETCH_ASSOC);
function options($rows, $selected, $label)
{
    // Mengubah daftar data database menjadi pilihan select yang aman untuk HTML.
    foreach ($rows as $r) {
        echo '<option value="'.e($r['id']).'" '.($selected == $r['id'] ? 'selected' : '').'>'.e($label($r)).'</option>';
    }
}
// Semua perubahan database dilakukan di sini agar route tetap ringkas.
if (is_post()) {
    // Seluruh aksi perubahan data dipusatkan melalui parameter action pada formulir.
    if (!logged_in()) {
        header('Location: '.url('login.php'));
        exit;
    }
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_barang' || $action === 'delete_barang') {
        permission_only('barang_manage');
    }
    if ($action === 'save_kategori' || $action === 'delete_kategori') {
        permission_only('category_manage');
    }
    if ($action === 'save_lokasi' || $action === 'delete_lokasi') {
        permission_only('location_manage');
    }
    if ($action === 'save_kunjungan' || $action === 'consume_scan') {
        permission_only('scan_view');
    }
    if ($action === 'save_ruangan_kunjungan') {
        permission_only('location_manage');
    }
    try {
        if ($action === 'save_barang') {
            $id = (int)($_POST['id'] ?? 0);
            $nama = trim($_POST['nama']);
            $stok = (int)$_POST['stok'];
            $status = $_POST['status'] ?? 'Tersedia';
            $tipeBarang = $_POST['tipe_barang'] ?? 'umum';
            $tanggalExpired = $_POST['tanggal_expired'] ?: null;
            if (!$nama || $stok < 0) {
                throw new Exception('Nama barang dan stok valid wajib diisi.');
            }if (!in_array($status, ['Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan','Terpakai','Rusak'], true)) {
                throw new Exception('Status barang tidak valid.');
            }if (!in_array($tipeBarang, ['umum','habis_pakai','lisensi'], true)) {
                throw new Exception('Jenis barang tidak valid.');
            }if ($id) {
                $s = $pdo->prepare('SELECT kode FROM barang WHERE id=?');
                $s->execute([$id]);
                $kode = $s->fetchColumn();
                if (!$kode) {
                    throw new Exception('Barang tidak ditemukan.');
                }
            } else {
                $next = (int)$pdo->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang'")->fetchColumn();
                $kode = 'BRG-'.str_pad((string)$next, 6, '0', STR_PAD_LEFT);
            }$data = [$kode,$nama,$_POST['kategori_id'] ?: null,$stok,trim($_POST['satuan']),$_POST['lokasi_id'] ?: null,$_POST['kondisi'],$status,$tipeBarang,$_POST['tanggal_masuk'] ?: null,$tanggalExpired,trim($_POST['deskripsi']) ?: null];
            if ($id) {
                $data[] = $id;
                $pdo->prepare('UPDATE barang SET kode=?,nama=?,kategori_id=?,stok=?,satuan=?,lokasi_id=?,kondisi=?,status=?,tipe_barang=?,tanggal_masuk=?,tanggal_expired=?,deskripsi=? WHERE id=?')->execute($data);
                sync_barang_units($id, $kode, $stok);
                flash('success', 'Barang diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO barang(kode,nama,kategori_id,stok,satuan,lokasi_id,kondisi,status,tipe_barang,tanggal_masuk,tanggal_expired,deskripsi) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute($data);
                sync_barang_units((int)$pdo->lastInsertId(), $kode, $stok);
                flash('success', 'Barang '.$kode.' dan QR Code per unit berhasil dibuat.');
            }header('Location: '.url('index.php?page=barang'));
            exit;
        }
        if ($action === 'delete_barang') {
            $pdo->prepare('UPDATE barang SET aktif=0 WHERE id=?')->execute([(int)$_POST['id']]);
            flash('success', 'Barang dinonaktifkan.');
            header('Location: '.url('index.php?page=barang'));
            exit;
        }
        if ($action === 'save_kategori') {
            $n = trim($_POST['nama']);
            if (!$n) {
                throw new Exception('Nama kategori wajib diisi.');
            }$pdo->prepare('INSERT INTO kategori(nama,keterangan) VALUES(?,?)')->execute([$n,trim($_POST['keterangan'])]);
            flash('success', 'Kategori ditambahkan.');
            header('Location: '.url('index.php?page=kategori'));
            exit;
        }
        if ($action === 'delete_kategori') {
            $pdo->prepare('DELETE FROM kategori WHERE id=?')->execute([(int)$_POST['id']]);
            flash('success', 'Kategori dihapus.');
            header('Location: '.url('index.php?page=kategori'));
            exit;
        }
        if ($action === 'save_lokasi') {
            $g = trim($_POST['gedung']);
            if (!$g) {
                throw new Exception('Gedung wajib diisi.');
            }$pdo->prepare('INSERT INTO lokasi(gedung,ruang,rak,keterangan) VALUES(?,?,?,?)')->execute([$g,trim($_POST['ruang']),trim($_POST['rak']),trim($_POST['keterangan'])]);
            flash('success', 'Lokasi ditambahkan.');
            header('Location: '.url('index.php?page=lokasi'));
            exit;
        }
        if ($action === 'delete_lokasi') {
            $pdo->prepare('DELETE FROM lokasi WHERE id=?')->execute([(int)$_POST['id']]);
            flash('success', 'Lokasi dihapus.');
            header('Location: '.url('index.php?page=lokasi'));
            exit;
        }
        if ($action === 'save_ruangan_kunjungan') {
            $gedung = trim($_POST['gedung'] ?? '');
            $ruang = trim($_POST['ruang'] ?? '');
            if (!$gedung || !$ruang) {
                throw new Exception('Area/gedung dan nama ruangan wajib diisi.');
            }$pdo->prepare('INSERT INTO lokasi(gedung,ruang,rak,keterangan) VALUES(?,?,?,?)')->execute([$gedung,$ruang,'',trim($_POST['keterangan'] ?? '')]);
            flash('success', 'Ruangan berhasil ditambahkan.');
            header('Location: '.url('index.php?page=kunjungan'));
            exit;
        }
        if ($action === 'consume_scan') {
            $kode = trim($_POST['kode'] ?? '');
            if (!$kode) {
                throw new Exception('Kode QR barang kosong.');
            }$pdo->beginTransaction();
            $s = $pdo->prepare('SELECT bu.id unit_id,bu.kode_unit,b.id barang_id,b.kode,b.nama,b.stok,bu.status unit_status FROM barang_unit bu JOIN barang b ON b.id=bu.barang_id WHERE bu.kode_unit=? AND b.aktif=1 FOR UPDATE');
            $s->execute([$kode]);
            $found = $s->fetch(PDO::FETCH_ASSOC);
            if (!$found) {
                $s = $pdo->prepare('SELECT b.id barang_id,b.kode,b.nama,b.stok,bu.id unit_id,bu.kode_unit,bu.status unit_status FROM barang b JOIN barang_unit bu ON bu.barang_id=b.id WHERE b.kode=? AND b.aktif=1 AND bu.status="tersedia" ORDER BY bu.id LIMIT 1 FOR UPDATE');
                $s->execute([$kode]);
                $found = $s->fetch(PDO::FETCH_ASSOC);
            }if (!$found) {
                throw new Exception('QR/barcode tidak terdaftar atau semua unit sudah terpakai.');
            }if ($found['unit_status'] !== 'tersedia') {
                throw new Exception('Unit ini sudah berstatus '.ucfirst($found['unit_status']).'. Stok tidak dikurangi lagi.');
            }$pdo->prepare('UPDATE barang_unit SET status="terpakai" WHERE id=?')->execute([(int)$found['unit_id']]);
            $pdo->prepare('UPDATE barang SET stok=GREATEST(0,stok-1) WHERE id=?')->execute([(int)$found['barang_id']]);
            $remaining = $pdo->prepare('SELECT stok FROM barang WHERE id=?');
            $remaining->execute([(int)$found['barang_id']]);
            $remainingStock = (int)$remaining->fetchColumn();
            $pdo->prepare('INSERT INTO transaksi(barang_id,jenis,jumlah,tanggal,keterangan,user_id) VALUES(?,?,?,?,?,?)')->execute([(int)$found['barang_id'],'keluar',1,date('Y-m-d'),'Pengambilan melalui scan QR '.$found['kode_unit'],$_SESSION['user']['id']]);
            $pdo->commit();
            flash('success', 'Scan berhasil: '.$found['kode_unit'].' ditandai Terpakai. Stok berkurang 1, sisa stok '.$remainingStock.' '.$found['nama'].'.');
            header('Location: '.url('detail.php?kode='.rawurlencode($found['kode_unit'])));
            exit;
        }
        if ($action === 'save_kunjungan') {
            $nama = trim($_POST['nama'] ?? '');
            $ruangan = trim($_POST['ruangan'] ?? '');
            $kode = trim($_POST['kode_scan'] ?? '');
            if (!$nama || !$ruangan || !$kode) {
                throw new Exception('Nama, ruangan, dan QR barang wajib diisi.');
            }$barangId = null;
            $unitId = null;
            $s = $pdo->prepare('SELECT bu.id unit_id,b.id barang_id FROM barang_unit bu JOIN barang b ON b.id=bu.barang_id WHERE bu.kode_unit=? AND b.aktif=1');
            $s->execute([$kode]);
            $found = $s->fetch(PDO::FETCH_ASSOC);
            if (!$found) {
                $s = $pdo->prepare('SELECT id barang_id FROM barang WHERE kode=? AND aktif=1');
                $s->execute([$kode]);
                $found = $s->fetch(PDO::FETCH_ASSOC);
            }if (!$found) {
                throw new Exception('QR/barcode tidak terdaftar di Data Barang.');
            }$barangId = (int)($found['barang_id'] ?? $found['id']);
            $unitId = isset($found['unit_id']) ? (int)$found['unit_id'] : null;
            $pdo->prepare('INSERT INTO room_visits(nama,ruangan,kode_scan,barang_id,unit_id,tanggal,jam,user_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$nama,$ruangan,$kode,$barangId,$unitId,date('Y-m-d'),date('H:i:s'),$_SESSION['user']['id']]);
            flash('success', 'Scan berhasil dicatat dan dikirim ke aktivitas Dashboard Admin.');
            header('Location: '.url('index.php?page=kunjungan'));
            exit;
        }
        if ($action === 'save_transaksi') {
            admin_only();
            $id = (int)($_POST['barang_id'] ?? 0);
            $jumlah = (int)($_POST['jumlah'] ?? 0);
            $jenis = $_POST['jenis'] ?? '';
            $tanggal = $_POST['tanggal'] ?? '';
            $keterangan = trim($_POST['keterangan'] ?? '');
            $date = DateTime::createFromFormat('Y-m-d', $tanggal);
            if (!$id || $jumlah < 1 || !in_array($jenis, ['masuk','keluar'], true) || !$date || $date->format('Y-m-d') !== $tanggal) {
                throw new Exception('Data transaksi tidak valid.');
            }$pdo->beginTransaction();
            $s = $pdo->prepare('SELECT id,kode,stok FROM barang WHERE id=? AND aktif=1 FOR UPDATE');
            $s->execute([$id]);
            $b = $s->fetch();
            if (!$b) {
                throw new Exception('Barang tidak ditemukan.');
            }if ($jenis === 'keluar' && $b['stok'] < $jumlah) {
                throw new Exception('Stok tidak mencukupi. Stok tersedia: '.$b['stok']);
            }$newStock = $b['stok'] + ($jenis === 'masuk' ? $jumlah : -$jumlah);
            $pdo->prepare('UPDATE barang SET stok=? WHERE id=?')->execute([$newStock,$id]);
            if ($jenis === 'masuk') {
                sync_barang_units($id, $b['kode'], $newStock);
            } else {
                $units = $pdo->prepare('SELECT id FROM barang_unit WHERE barang_id=? AND status="tersedia" ORDER BY id LIMIT '.$jumlah);
                $units->execute([$id]);
                $unitIds = $units->fetchAll(PDO::FETCH_COLUMN);
                if (count($unitIds) !== $jumlah) {
                    throw new Exception('Unit tersedia tidak mencukupi.');
                }$mark = $pdo->prepare('UPDATE barang_unit SET status="keluar" WHERE id=?');
                foreach ($unitIds as $unitId) {
                    $mark->execute([$unitId]);
                }
            }$pdo->prepare('INSERT INTO transaksi(barang_id,jenis,jumlah,tanggal,keterangan,user_id) VALUES(?,?,?,?,?,?)')->execute([$id,$jenis,$jumlah,$tanggal,$keterangan,$_SESSION['user']['id']]);
            $pdo->commit();
            flash('success', 'Transaksi berhasil. Stok telah diperbarui.');
            header('Location: '.url('index.php?page=riwayat'));
            exit;
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }flash('danger', $e->getMessage());
        header('Location: '.url('index.php?page='.$page));
        exit;
    }
}
require __DIR__.'/partials/header.php';
if ($page === 'barang' && logged_in() && has_permission('barang_manage') && !is_admin()) {
    $memberEdit = null;
    if (!empty($_GET['edit'])) {
        $s = $pdo->prepare('SELECT * FROM barang WHERE id=? AND aktif=1');
        $s->execute([(int)$_GET['edit']]);
        $memberEdit = $s->fetch(PDO::FETCH_ASSOC);
    }
    echo '<div class="modal fade" id="memberBarangModal" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="post"><div class="modal-header"><h5>'.($memberEdit ? 'Edit' : 'Tambah').' Barang</h5><a class="btn-close" href="'.url('index.php?page=barang').'" aria-label="Tutup"></a></div><div class="modal-body"><input type="hidden" name="csrf" value="'.e(csrf()).'"><input type="hidden" name="action" value="save_barang"><input type="hidden" name="id" value="'.e($memberEdit['id'] ?? '').'"><div class="row g-3"><div class="col-md-8"><label class="form-label">Nama *</label><input class="form-control" name="nama" required value="'.e($memberEdit['nama'] ?? '').'"></div><div class="col-md-4"><label class="form-label">Stok *</label><input class="form-control" type="number" min="0" name="stok" required value="'.e($memberEdit['stok'] ?? 0).'"></div><div class="col-md-6"><label class="form-label">Kategori</label><select class="form-select" name="kategori_id"><option value="">- Pilih -</option>';
    options($categories, $memberEdit['kategori_id'] ?? '', fn ($x) => $x['nama']);
    echo '</select></div><div class="col-md-6"><label class="form-label">Satuan</label><input class="form-control" name="satuan" value="'.e($memberEdit['satuan'] ?? 'Unit').'"></div><div class="col-md-6"><label class="form-label">Lokasi</label><select class="form-select" name="lokasi_id"><option value="">- Pilih -</option>';
    options($locations, $memberEdit['lokasi_id'] ?? '', fn ($x) => location_name($x));
    echo '</select></div><div class="col-md-6"><label class="form-label">Jenis barang</label><select class="form-select" name="tipe_barang"><option value="umum" '.(($memberEdit['tipe_barang'] ?? 'umum') === 'umum' ? 'selected' : '').'>Barang umum</option><option value="habis_pakai" '.(($memberEdit['tipe_barang'] ?? '') === 'habis_pakai' ? 'selected' : '').'>Bahan habis pakai</option><option value="lisensi" '.(($memberEdit['tipe_barang'] ?? '') === 'lisensi' ? 'selected' : '').'>Lisensi</option></select></div><div class="col-md-6"><label class="form-label">Kondisi</label><select class="form-select" name="kondisi">';
    foreach (['Baik','Rusak Ringan','Rusak Berat'] as $condition) {
        echo '<option '.(($memberEdit['kondisi'] ?? 'Baik') === $condition ? 'selected' : '').'>'.$condition.'</option>';
    }
    echo '</select></div><div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status">';
    foreach (['Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan','Terpakai','Rusak'] as $status) {
        echo '<option '.(($memberEdit['status'] ?? 'Tersedia') === $status ? 'selected' : '').'>'.$status.'</option>';
    }
    echo '</select></div><div class="col-md-6"><label class="form-label">Tanggal masuk</label><input class="form-control" type="date" name="tanggal_masuk" value="'.e($memberEdit['tanggal_masuk'] ?? date('Y-m-d')).'"></div><div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="deskripsi">'.e($memberEdit['deskripsi'] ?? '').'</textarea></div></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div><script>window.addEventListener("DOMContentLoaded",()=>{const button=document.querySelector("[data-bs-target=\\"#barangModal\\"]");if(button)button.setAttribute("data-bs-target","#memberBarangModal");'.($memberEdit ? 'new bootstrap.Modal("#memberBarangModal").show();' : '').' });</script>';
}
if ($page === 'dashboard') {
    echo '<div class="text-secondary small mb-2">Waktu aktivitas terakhir diperbarui: '.e(date('d-m-Y H:i')).'</div>';
}
if ($page === 'dashboard') {
    if (!logged_in()) {
        echo '<div class="alert alert-info">Anda sedang melihat sebagai Guest. <a href="'.url('register.php').'">Daftar sebagai Member</a> untuk menggunakan fitur operasional.</div>';
    }
    $stats = ['Total barang' => $pdo->query('SELECT COUNT(*) FROM barang WHERE aktif=1')->fetchColumn(),'Total stok' => $pdo->query('SELECT COALESCE(SUM(stok),0) FROM barang WHERE aktif=1')->fetchColumn(),'Barang masuk' => $pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM transaksi WHERE jenis='masuk'")->fetchColumn(),'Barang keluar' => $pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM transaksi WHERE jenis='keluar'")->fetchColumn(),'Kategori' => $pdo->query('SELECT COUNT(*) FROM kategori')->fetchColumn()];
    echo '<div class="row g-3 mb-4">';
    foreach ($stats as $k => $v) {
        echo '<div class="col-6 col-lg"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="stat-icon">▣</span><div><small class="text-secondary">'.e($k).'</small><h3 class="mb-0">'.e($v).'</h3></div></div></div></div>';
    }echo '</div>';
    $latest = $pdo->query('SELECT b.*,k.nama kategori,l.gedung,l.ruang,l.rak FROM barang b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN lokasi l ON l.id=b.lokasi_id WHERE b.aktif=1 ORDER BY b.created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    $act = $pdo->query('SELECT t.*,b.kode,b.nama,u.nama user FROM transaksi t JOIN barang b ON b.id=t.barang_id JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC); ?>
 <div class="row g-4"><div class="col-lg-7"><div class="card"><div class="card-body"><h5>Barang terbaru</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Kode</th><th>Barang</th><th>Stok</th><th>Lokasi</th></tr></thead><tbody><?php foreach ($latest as $b):?><tr><td><?=e($b['kode'])?></td><td><?=e($b['nama'])?><br><small class="text-secondary"><?=e($b['kategori'] ?? '-')?></small></td><td><span class="badge text-bg-primary"><?=e($b['stok'].' '.$b['satuan'])?></span></td><td><?=e(location_name($b))?></td></tr><?php endforeach?></tbody></table></div></div></div></div><div class="col-lg-5 activity-feed-column"><div class="card activity-feed-card"><div class="card-body"><h5>Aktivitas storage</h5><?php foreach ($act as $a):?><div class="border-bottom py-2"><b><?=e($a['nama'])?></b> <span class="badge text-bg-<?= $a['jenis'] === 'masuk' ? 'success' : 'warning'?>"><?=e($a['jenis'])?></span><br><small class="text-secondary"><?=e($a['jumlah'].' unit oleh '.$a['user'].' · '.$a['tanggal'])?></small></div><?php endforeach?></div></div></div></div>
<?php if (is_admin() || has_permission('room_log_view')): $roomActivities = $pdo->query('SELECT nama,identitas,ruangan,tanggal,jam,created_at FROM room_visits ORDER BY created_at DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC); ?>
 <div class="card mt-4"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Aktivitas ruangan</h5><a class="btn btn-sm btn-outline-primary" href="<?=url('room_logs.php')?>">Lihat semua</a></div><?php foreach ($roomActivities as $visit): ?><div class="border-bottom py-2"><b><?=e($visit['nama'])?></b> <span class="badge text-bg-info"><?=e($visit['identitas'] ?: 'Akun')?></span><br><small class="text-secondary"><?=e($visit['ruangan'])?> · <?=e(date('d-m-Y', strtotime($visit['tanggal'])))?> <?=e($visit['jam'])?></small></div><?php endforeach; ?><?php if (!$roomActivities): ?><div class="text-secondary small py-2">Belum ada scan ruangan.</div><?php endif; ?></div></div>
<?php endif;
} elseif ($page === 'barang') {
    $q = trim($_GET['q'] ?? '');
    $cat = $_GET['kategori'] ?? [];
    if (!is_array($cat)) {
        $cat = [$cat];
    }$cat = array_values(array_filter(array_map('intval', $cat), static fn ($id) => $id > 0));
    $loc = $_GET['lokasi'] ?? '';
    $where = ['b.aktif=1'];
    $par = [];
    if ($q) {
        $term = "%$q%";
        $where[] = '(b.kode LIKE ? OR b.nama LIKE ? OR k.nama LIKE ? OR EXISTS (SELECT 1 FROM barang_unit buq WHERE buq.barang_id=b.id AND (buq.kode_unit LIKE ? OR buq.nama_unit LIKE ? OR buq.catatan LIKE ?)))';
        $par = [$term,$term,$term,$term,$term,$term];
    }if ($cat) {
        $where[] = 'b.kategori_id IN ('.implode(',', array_fill(0, count($cat), '?')).')';
        $par = array_merge($par, $cat);
    }if ($loc) {
        $where[] = 'b.lokasi_id=?';
        $par[] = $loc;
    }$s = $pdo->prepare('SELECT b.*,k.nama kategori,l.gedung,l.ruang,l.rak,(SELECT COUNT(*) FROM barang_unit bu_total WHERE bu_total.barang_id=b.id) total_unit,(SELECT COUNT(*) FROM barang_unit bu_used WHERE bu_used.barang_id=b.id AND bu_used.status="terpakai") terpakai_unit,(SELECT COUNT(*) FROM barang_unit bu_damaged WHERE bu_damaged.barang_id=b.id AND bu_damaged.status="rusak") rusak_unit,(SELECT COUNT(*) FROM barang_unit bu_out WHERE bu_out.barang_id=b.id AND bu_out.status="keluar") keluar_unit FROM barang b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN lokasi l ON l.id=b.lokasi_id WHERE '.implode(' AND ', $where).' ORDER BY b.created_at DESC');
    $s->execute($par);
    $items = $s->fetchAll(PDO::FETCH_ASSOC);
    $edit = null;
    if (!empty($_GET['edit'])) {
        $s = $pdo->prepare('SELECT * FROM barang WHERE id=?');
        $s->execute([(int)$_GET['edit']]);
        $edit = $s->fetch(PDO::FETCH_ASSOC);
    } ?>
 <div class="d-flex flex-wrap gap-2 justify-content-between mb-3"><form class="d-flex flex-wrap gap-2 align-items-end"><input type="hidden" name="page" value="barang"><input class="form-control" name="q" value="<?=e($q)?>" placeholder="Cari kode atau nama"><select class="form-select" name="kategori"><option value="">Semua kategori</option><?php options($categories, $cat[0] ?? '', fn ($x) => $x['nama'])?></select><select class="form-select" name="lokasi"><option value="">Semua lokasi</option><?php options($locations, $loc, fn ($x) => location_name($x))?></select><button class="btn btn-outline-primary">Filter</button></form><?php if ($_SESSION['user']['role'] === 'admin' || has_permission('barang_manage')):?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#barangModal">+ Tambah Barang</button><?php endif?></div>
 <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Kode Master</th><th>Nama</th><th>Kategori</th><th>Stok Unit</th><th>QR Unit</th><th>Lokasi</th><th></th></tr></thead><tbody><?php foreach ($items as $b): $detailLink = url('detail.php?kode='.urlencode($b['kode'])); ?><tr data-detail="<?=e($detailLink)?>" style="cursor:pointer;"><td><b><?=e($b['kode'])?></b></td><td><?=e($b['nama'])?></td><td><?=e($b['kategori'] ?? '-')?></td><td><?=e($b['stok'].' '.$b['satuan'])?></td><td><a class="btn btn-sm btn-outline-primary" href="<?=url('units.php?barang_id='.$b['id'])?>">Lihat <?=e($b['stok'])?> QR</a></td><td><?=e(location_name($b))?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?=e($detailLink)?>">Detail</a><?php if (is_admin() || has_permission('barang_manage')):?><a class="btn btn-sm btn-outline-secondary" href="<?=url('index.php?page=barang&edit='.$b['id'])?>">Edit</a><form class="d-inline" method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_barang"><input type="hidden" name="id" value="<?=e($b['id'])?>"><button data-confirm="Nonaktifkan barang ini?" class="btn btn-sm btn-outline-danger">Hapus</button></form><?php endif?></td></tr><?php endforeach?></tbody></table></div></div>
 <?php if ($_SESSION['user']['role'] === 'admin'):?><div class="modal fade" id="barangModal" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="post"><div class="modal-header"><h5><?= $edit ? 'Edit' : 'Tambah'?> Barang</h5><a class="btn-close" href="<?=url('index.php?page=barang')?>"></a></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_barang"><input type="hidden" name="id" value="<?=e($edit['id'] ?? '')?>"><div class="row g-3"><div class="col-md-4"><label class="form-label">Kode *</label><input class="form-control" name="kode" required value="<?=e($edit['kode'] ?? '')?>"></div><div class="col-md-8"><label class="form-label">Nama *</label><input class="form-control" name="nama" required value="<?=e($edit['nama'] ?? '')?>"></div><div class="col-md-6"><label class="form-label">Kategori</label><select class="form-select" name="kategori_id"><option value="">- Pilih -</option><?php options($categories, $edit['kategori_id'] ?? '', fn ($x) => $x['nama'])?></select></div><div class="col-md-3"><label class="form-label">Stok *</label><input class="form-control" type="number" min="0" name="stok" required value="<?=e($edit['stok'] ?? 0)?>"></div><div class="col-md-3"><label class="form-label">Satuan</label><input class="form-control" name="satuan" value="<?=e($edit['satuan'] ?? 'Unit')?>"></div><div class="col-md-6"><label class="form-label">Lokasi</label><select class="form-select" name="lokasi_id"><option value="">- Pilih -</option><?php options($locations, $edit['lokasi_id'] ?? '', fn ($x) => location_name($x))?></select></div><div class="col-md-3"><label class="form-label">Kondisi fisik</label><select class="form-select" name="kondisi"><?php foreach (['Baik','Rusak Ringan','Rusak Berat'] as $x):?><option <?=($edit['kondisi'] ?? 'Baik') === $x ? 'selected' : ''?>><?=$x?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label">Status penggunaan</label><select class="form-select" name="status"><?php foreach (['Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan'] as $x):?><option <?=($edit['status'] ?? 'Tersedia') === $x ? 'selected' : ''?>><?=$x?></option><?php endforeach?></select></div><div class="col-md-3"><label class="form-label">Tgl. masuk</label><input class="form-control" type="date" name="tanggal_masuk" value="<?=e($edit['tanggal_masuk'] ?? date('Y-m-d'))?>"></div><div class="col-12"><label class="form-label">Keterangan</label><textarea class="form-control" name="deskripsi"><?=e($edit['deskripsi'] ?? '')?></textarea></div></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div><?php if ($edit):?><script>window.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal('#barangModal').show())</script><?php endif?><?php endif;
} elseif ($page === 'kunjungan') {
    permission_only('scan_view');
    $visitRows = $pdo->query('SELECT rv.*,b.nama barang_nama FROM room_visits rv LEFT JOIN barang b ON b.id=rv.barang_id ORDER BY rv.created_at DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC); ?>
<div class="row g-4"><div class="col-lg-5"><div class="card"><div class="card-body"><h5>Catat scan pengambilan</h5><p class="text-secondary small">Isi nama dan ruangan, lalu scan QR barang sebelum barang dibawa keluar.</p><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_kunjungan"><div class="mb-3"><label class="form-label">Nama orang</label><input class="form-control" name="nama" required placeholder="Nama orang yang mengambil"></div><div class="mb-3"><label class="form-label">Ruangan</label><select class="form-select" name="ruangan" required><option value="">Pilih ruangan</option><?php foreach ($locations as $room):?><option value="<?=e(location_name($room))?>"><?=e(location_name($room))?></option><?php endforeach;?></select></div><div class="mb-3"><label class="form-label">Kode hasil scan</label><input id="visit-code" class="form-control" name="kode_scan" required placeholder="Scan QR atau ketik kode barang"><div id="visit-scan-result" class="form-text">Belum ada QR yang dibaca.</div></div><button class="btn btn-primary w-100">Simpan aktivitas scan</button></form></div></div><?php if (is_admin() || has_permission('location_manage')):?><div class="card mt-3"><div class="card-body"><h5>Tambah ruangan</h5><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_ruangan_kunjungan"><input class="form-control mb-2" name="gedung" placeholder="Area / gedung" required><input class="form-control mb-2" name="ruang" placeholder="Nama ruangan" required><input class="form-control mb-3" name="keterangan" placeholder="Keterangan (opsional)"><button class="btn btn-outline-primary w-100">Tambah ruangan</button></form></div></div><?php endif;?></div><div class="col-lg-7"><div class="card"><div class="card-body"><h5>Scan QR barang</h5><div id="visit-reader"></div><div id="visit-camera-message" class="small text-secondary mt-2">Izinkan kamera jika diminta browser.</div></div></div><div class="card mt-3"><div class="card-body"><h5>Aktivitas scan terbaru</h5><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama</th><th>Ruangan</th><th>Barang</th><th>Waktu</th></tr></thead><tbody><?php foreach ($visitRows as $visit):?><tr><td><?=e($visit['nama'])?></td><td><?=e($visit['ruangan'])?></td><td><?=e($visit['barang_nama'] ?: $visit['kode_scan'])?><br><small class="text-secondary"><?=e($visit['kode_scan'])?></small></td><td><?=e(date('d-m-Y H:i', strtotime($visit['created_at'])))?></td></tr><?php endforeach;?><?php if (!$visitRows):?><tr><td colspan="4" class="text-center text-secondary">Belum ada aktivitas scan.</td></tr><?php endif;?></tbody></table></div></div></div></div></div><script>(function(){const input=document.getElementById('visit-code'),message=document.getElementById('visit-scan-result'),reader=document.getElementById('visit-reader');function scanned(text){try{const u=new URL(text,location.href);input.value=u.searchParams.get('kode')||text;}catch(e){input.value=text;}message.textContent='QR terbaca: '+input.value;message.className='form-text text-success';}function start(){if(!window.isSecureContext){document.getElementById('visit-camera-message').textContent='Kamera membutuhkan HTTPS. Gunakan input kode secara manual jika kamera tidak tersedia.';return;}if(typeof Html5QrcodeScanner==='undefined')return;const scanner=new Html5QrcodeScanner('visit-reader',{fps:10,qrbox:220,formatsToSupport:[Html5QrcodeSupportedFormats.QR_CODE]},false);scanner.render(scanned,()=>{});}window.addEventListener('DOMContentLoaded',start);})();</script><script src="https://unpkg.com/html5-qrcode" onload="window.dispatchEvent(new Event('DOMContentLoaded'))" onerror="this.onerror=null;this.onload=function(){window.dispatchEvent(new Event('DOMContentLoaded'))};this.src='https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js'"></script>
<?php } elseif ($page === 'kategori') {
    permission_only('category_manage'); ?>
<div class="row g-4"><div class="col-lg-4"><div class="card"><div class="card-body"><h5>Tambah kategori</h5><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_kategori"><input class="form-control mb-2" name="nama" placeholder="Nama kategori" required><textarea class="form-control mb-3" name="keterangan" placeholder="Keterangan (opsional)"></textarea><button class="btn btn-primary">Simpan</button></form></div></div></div><div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nama</th><th>Keterangan</th><th></th></tr></thead><tbody><?php foreach ($categories as $x):?><tr><td><?=e($x['nama'])?></td><td><?=e($x['keterangan'])?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_kategori"><input type="hidden" name="id" value="<?=$x['id']?>"><button data-confirm="Hapus kategori ini?" class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach?></tbody></table></div></div></div></div>
<?php } elseif ($page === 'lokasi') {
    permission_only('location_manage'); ?>
<div class="row g-4"><div class="col-lg-4"><div class="card"><div class="card-body"><h5 class="mb-1">Tambah lokasi storage</h5><p class="small text-secondary mb-3">Isi berurutan agar lokasi barang mudah ditemukan kembali.</p><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_lokasi"><div class="mb-3"><label class="form-label">Area / gedung <span class="text-danger">*</span></label><input class="form-control" name="gedung" placeholder="Contoh: Gudang Utama" required><div class="form-text">Nama gedung atau area besar penyimpanan.</div></div><div class="mb-3"><label class="form-label">Ruang / zona</label><input class="form-control" name="ruang" placeholder="Contoh: Ruang A atau Zona Tinta"><div class="form-text">Boleh dikosongkan jika tidak ada pembagian ruang.</div></div><div class="mb-3"><label class="form-label">Rak / lemari</label><input class="form-control" name="rak" placeholder="Contoh: Rak A-01"><div class="form-text">Kode rak, lemari, atau nomor penyimpanan.</div></div><div class="mb-3"><label class="form-label">Petunjuk lokasi</label><textarea class="form-control" name="keterangan" rows="3" placeholder="Contoh: Sebelah pintu masuk, tingkat 2"></textarea></div><button class="btn btn-primary w-100">Simpan lokasi</button></form></div></div></div><div class="col-lg-8"><div class="card"><div class="card-body border-bottom"><h5 class="mb-1">Lokasi yang sudah disimpan</h5><small class="text-secondary">Gunakan keterangan ini saat memilih lokasi barang.</small></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Lokasi storage</th><th>Petunjuk tambahan</th><th class="text-end">Aksi</th></tr></thead><tbody><?php foreach ($locations as $x):?><tr><td><div class="fw-semibold"><?=e(location_name($x))?></div><div class="small text-secondary"><?php if ($x['gedung']):?><span class="badge text-bg-light border me-1">Area</span><?=e($x['gedung'])?><?php endif;?><?php if ($x['ruang']):?> <span class="badge text-bg-light border ms-1 me-1">Ruang</span><?=e($x['ruang'])?><?php endif;?><?php if ($x['rak']):?> <span class="badge text-bg-light border ms-1 me-1">Rak</span><?=e($x['rak'])?><?php endif;?></div></td><td><?= $x['keterangan'] ? '<span class="text-secondary">'.e($x['keterangan']).'</span>' : '<span class="text-secondary fst-italic">Belum ada petunjuk tambahan</span>' ?></td><td class="text-end"><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_lokasi"><input type="hidden" name="id" value="<?=$x['id']?>"><button data-confirm="Hapus lokasi ini?" class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach;?><?php if (!$locations):?><tr><td colspan="3" class="text-center text-secondary py-4">Belum ada lokasi storage. Tambahkan lokasi pertama di form sebelah kiri.</td></tr><?php endif;?></tbody></table></div></div></div></div>
<?php } elseif ($page === 'transaksi') {
    permission_only('transaction_manage');
    $items = $pdo->query('SELECT id,kode,nama,stok,satuan FROM barang WHERE aktif=1 ORDER BY nama')->fetchAll(PDO::FETCH_ASSOC); ?>
<div class="row justify-content-center"><div class="col-lg-7"><div class="card"><div class="card-body p-4"><h5 class="mb-3">Catat barang masuk atau keluar</h5><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_transaksi"><div class="mb-3"><label class="form-label">Barang</label><select class="form-select" name="barang_id" required><option value="">Pilih barang</option><?php foreach ($items as $b):?><option value="<?=$b['id']?>"><?=e($b['kode'].' — '.$b['nama'].' (stok: '.$b['stok'].' '.$b['satuan'].')')?></option><?php endforeach?></select></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Jenis transaksi</label><select class="form-select" name="jenis"><option value="masuk">Barang Masuk</option><option value="keluar">Barang Keluar</option></select></div><div class="col-md-6"><label class="form-label">Jumlah</label><input class="form-control" type="number" min="1" name="jumlah" required></div><div class="col-md-6"><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?=date('Y-m-d')?>" required></div><div class="col-md-6"><label class="form-label">Keterangan</label><input class="form-control" name="keterangan" placeholder="Opsional"></div></div><button class="btn btn-primary mt-4">Simpan Transaksi</button></form></div></div></div></div>
<div class="transaction-layout"><div class="transaction-form-column"><div class="card transaction-card"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h5 class="mb-1">Catat transaksi</h5><p class="text-secondary small mb-0">Perbarui stok barang masuk atau keluar.</p></div><span class="transaction-icon" aria-hidden="true">⇄</span></div><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save_transaksi"><div class="mb-3"><label class="form-label" for="transaction-item">Barang</label><select id="transaction-item" class="form-select" name="barang_id" required><option value="">Pilih barang</option><?php foreach ($items as $b):?><option value="<?=$b['id']?>" data-stock="<?=$b['stok']?>" data-unit="<?=e($b['satuan'])?>"><?=e($b['kode'].' — '.$b['nama'])?></option><?php endforeach?></select><div id="transaction-stock" class="form-text">Pilih barang untuk melihat stok saat ini.</div></div><div class="row g-3"><div class="col-sm-6"><label class="form-label" for="transaction-type">Jenis transaksi</label><select id="transaction-type" class="form-select" name="jenis"><option value="masuk">Barang Masuk</option><option value="keluar">Barang Keluar</option></select></div><div class="col-sm-6"><label class="form-label" for="transaction-quantity">Jumlah</label><input id="transaction-quantity" class="form-control" type="number" min="1" name="jumlah" required></div><div class="col-sm-6"><label class="form-label" for="transaction-date">Tanggal</label><input id="transaction-date" class="form-control" type="date" name="tanggal" value="<?=date('Y-m-d')?>" required></div><div class="col-sm-6"><label class="form-label" for="transaction-note">Keterangan <span class="text-secondary fw-normal">(opsional)</span></label><input id="transaction-note" class="form-control" name="keterangan" maxlength="255" placeholder="Contoh: Pengadaan atau pemakaian"></div></div><button class="btn btn-primary w-100 mt-4">Simpan transaksi</button></form></div></div></div><aside class="transaction-help-column"><div class="card transaction-help-card"><div class="card-body"><h6 class="mb-3">Ringkasan transaksi</h6><div class="transaction-rule"><span class="transaction-rule-dot bg-success"></span><div><b>Barang masuk</b><small>Stok dan unit tersedia bertambah.</small></div></div><div class="transaction-rule"><span class="transaction-rule-dot bg-warning"></span><div><b>Barang keluar</b><small>Stok berkurang dan unit ditandai keluar.</small></div></div><div class="alert alert-light border small mb-0">Pastikan jumlah barang keluar tidak melebihi stok yang tersedia.</div></div></div></aside></div><script>document.addEventListener("DOMContentLoaded",function(){const item=document.querySelector("#transaction-item"),stock=document.querySelector("#transaction-stock"),type=document.querySelector("#transaction-type"),quantity=document.querySelector("#transaction-quantity");if(!item||!stock)return;const update=function(){const option=item.options[item.selectedIndex],available=option?.dataset.stock;if(!available){stock.textContent="Pilih barang untuk melihat stok saat ini.";quantity?.removeAttribute("max");return;}stock.textContent="Stok saat ini: "+available+" "+(option.dataset.unit||"unit");if(type?.value==="keluar")quantity?.setAttribute("max",available);else quantity?.removeAttribute("max")};item.addEventListener("change",update);type?.addEventListener("change",update);update()});</script>
<?php } elseif ($page === 'riwayat') {
    $jenis = $_GET['jenis'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    $w = ['1=1'];
    $p = [];
    if ($jenis) {
        $w[] = 't.jenis=?';
        $p[] = $jenis;
    }if ($tanggal) {
        $w[] = 't.tanggal=?';
        $p[] = $tanggal;
    }$s = $pdo->prepare('SELECT t.*,b.kode,b.nama,u.nama user FROM transaksi t JOIN barang b ON b.id=t.barang_id JOIN users u ON u.id=t.user_id WHERE '.implode(' AND ', $w).' ORDER BY t.tanggal DESC,t.id DESC');
    $s->execute($p);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);?>
<div class="history-toolbar"><form class="history-filters"><input type="hidden" name="page" value="riwayat"><select class="form-select" name="jenis"><option value="">Semua jenis</option><option value="masuk" <?=$jenis === 'masuk' ? 'selected' : ''?>>Masuk</option><option value="keluar" <?=$jenis === 'keluar' ? 'selected' : ''?>>Keluar</option></select><input class="form-control" type="date" name="tanggal" value="<?=e($tanggal)?>"><button class="btn btn-outline-primary">Filter</button></form><div class="history-reports"><a target="_blank" class="btn btn-outline-dark" href="<?=url('laporan.php?periode=harian&tanggal='.urlencode($tanggal ?: date('Y-m-d')))?>">Laporan Harian</a><form action="<?=url('laporan.php')?>" target="_blank" class="history-month-form"><input type="hidden" name="periode" value="bulanan"><input class="form-control" type="month" name="bulan" value="<?=date('Y-m')?>"><button class="btn btn-primary">Bulanan</button></form></div></div><div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Kode</th><th>Barang</th><th>Jenis</th><th>Jumlah</th><th>User</th><th>Keterangan</th></tr></thead><tbody><?php foreach ($rows as $r):?><tr><td><?=e($r['tanggal'])?></td><td><?=e($r['kode'])?></td><td><?=e($r['nama'])?></td><td><span class="badge text-bg-<?=$r['jenis'] === 'masuk' ? 'success' : 'warning'?>"><?=e(ucfirst($r['jenis']))?></span></td><td><?=e($r['jumlah'])?></td><td><?=e($r['user'])?></td><td><?=e($r['keterangan'])?></td></tr><?php endforeach?></tbody></table></div></div>
<?php } elseif ($page === 'scan') { ?>
<div class="card scanner"><div class="card-body text-center p-4"><h5>Scan QR Code Barang</h5><p class="text-secondary">Gunakan kamera belakang dan arahkan QR tepat di dalam kotak.</p><div id="reader"></div><div id="scanResult" class="mt-3"></div></div></div><script>(function(){let qrScanner;const reader=document.querySelector('#reader'),result=document.querySelector('#scanResult');function message(type,text){result.innerHTML='<div class="alert alert-'+type+' text-start">'+text+'</div>';}window.ok=function(text){if(qrScanner){qrScanner.clear().catch(()=>{});}message('success','QR terbaca. Membuka detail…');setTimeout(()=>{try{const u=new URL(text,location.href),kode=u.searchParams.get('kode');location.href=kode?'<?=url('detail.php?kode=')?>'+encodeURIComponent(kode):u.href;}catch(e){location.href='<?=url('detail.php?kode=')?>'+encodeURIComponent(text);}},150);};function start(){if(!window.isSecureContext){reader.innerHTML='<div class="alert alert-warning text-start"><b>Kamera live diblokir browser.</b><br>Alamat ini harus HTTPS dengan sertifikat yang dipercaya perangkat. Instal/trust sertifikat LAN, izinkan kamera untuk situs ini, lalu muat ulang halaman.<br><small>Alternatif: gunakan tombol Ambil Foto QR di bawah.</small></div>';return;}if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){message('warning','Browser ini tidak menyediakan akses kamera. Gunakan browser terbaru atau tombol Ambil Foto QR.');return;}if(typeof Html5QrcodeScanner==='undefined'||typeof Html5QrcodeSupportedFormats==='undefined'){message('danger','Library scanner belum termuat. Periksa koneksi internet, lalu muat ulang halaman.');return;}const config={fps:15,disableFlip:false,rememberLastUsedCamera:true,showTorchButtonIfSupported:true,showZoomSliderIfSupported:true,aspectRatio:1.0,videoConstraints:{facingMode:{ideal:'environment'},width:{ideal:1280},height:{ideal:720}},experimentalFeatures:{useBarCodeDetectorIfSupported:true},formatsToSupport:[Html5QrcodeSupportedFormats.QR_CODE],qrbox:(width,height)=>{const size=Math.min(340,Math.max(220,Math.floor(Math.min(width,height)*.82)));return{width:size,height:size}}};try{qrScanner=new Html5QrcodeScanner('reader',config,false);qrScanner.render(window.ok,error=>{if(error&&/permission|not allowed|denied|notfound|no camera/i.test(String(error))){message('warning','Kamera tidak dapat dibuka. Izinkan kamera untuk situs ini, pastikan kamera tidak sedang dipakai aplikasi lain, lalu muat ulang.');}});}catch(error){message('danger','Scanner gagal dimulai. Muat ulang halaman dan coba lagi.');}}window.storageScannerStart=start;})();</script><script src="https://unpkg.com/html5-qrcode" type="text/javascript" onload="window.storageScannerStart()" onerror="this.onerror=null;this.onload=function(){window.storageScannerStart()};this.src='https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js'"></script>
<?php } else {
    echo '<div class="alert alert-warning">Halaman tidak ditemukan.</div>';
}
if ($page === 'lokasi') {
    echo '<div class="card mt-4"><div class="card-body"><h5>QR untuk akses ruangan</h5><p class="text-secondary small">Cetak atau unduh QR berikut. Saat dipindai, pengunjung mengisi data diri dan otomatis tercatat di Dashboard.</p><div class="row g-3">';
    foreach ($locations as $room) {
        $roomQr = room_qr_image_url($room['id'], 180);
        echo '<div class="col-12 col-md-6 col-xl-4"><div class="border rounded p-3 h-100 text-center"><img src="'.e($roomQr).'" width="150" height="150" alt="QR '.e(location_name($room)).'" class="img-fluid mb-2"><h6>'.e(location_name($room)).'</h6><a class="btn btn-sm btn-outline-primary" download="QR-Ruangan-'.e($room['id']).'.png" href="'.e($roomQr).'">Unduh QR</a></div></div>';
    }
    echo '</div></div></div>';
}
if ($page === 'barang') {
    $itemMeta = [];
    foreach ($items as $item) {
        $itemMeta[$item['kode']] = ['status' => $item['status'] ?? 'Tersedia', 'tipe' => $item['tipe_barang'] ?? 'umum', 'expired' => $item['tanggal_expired'] ?? null];
    }
    echo '<script>window.addEventListener("DOMContentLoaded",function(){const meta='.json_encode($itemMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP).';const types={umum:"Barang umum",habis_pakai:"Bahan habis pakai",lisensi:"Lisensi"};const status=document.querySelector("#barangModal select[name=status]");if(status){["Terpakai","Rusak"].forEach(value=>{if(![...status.options].some(option=>option.value===value))status.add(new Option(value,value));});const form=status.form,date=form.querySelector("[name=\"tanggal_masuk\"]"),typeValue='.json_encode($edit['tipe_barang'] ?? 'umum').',expiryValue='.json_encode($edit['tanggal_expired'] ?? '').';if(date&&!form.querySelector("[name=\"tipe_barang\"]")){date.parentElement.insertAdjacentHTML("beforebegin","<div class=\"col-md-3\"><label class=\"form-label\">Jenis barang</label><select class=\"form-select\" name=\"tipe_barang\"><option value=\"umum\">Barang umum</option><option value=\"habis_pakai\">Bahan habis pakai</option><option value=\"lisensi\">Lisensi</option></select></div><div class=\"col-md-3\"><label class=\"form-label\">Tgl. kedaluwarsa</label><input class=\"form-control\" type=\"date\" name=\"tanggal_expired\"></div>");form.querySelector("[name=\"tipe_barang\"]").value=typeValue;form.querySelector("[name=\"tanggal_expired\"]").value=expiryValue;}}document.querySelectorAll(".table tbody tr").forEach(row=>{const code=row.querySelector("td")?.textContent.trim(),item=meta[code];if(!item)return;const expiry=item.expired?new Date(item.expired+"T00:00:00"):null,days=expiry?Math.ceil((expiry-new Date(new Date().toDateString()))/86400000):null,expiryLabel=days===null?"":(days<0?"Kedaluwarsa":(days<=30?"Segera kedaluwarsa":"Berlaku")),expiryClass=days===null?"secondary":(days<0?"danger":(days<=30?"warning":"success"));const cell=row.cells[1];if(cell)cell.insertAdjacentHTML("beforeend","<br><small class=\"text-secondary\">"+(types[item.tipe]||"Barang umum")+" · Status: "+item.status+(expiryLabel?"<br><span class=\"badge text-bg-"+expiryClass+" mt-1\">"+expiryLabel+" · "+item.expired+"</span>":"")+"</small>");});});</script>';
    echo '<script>window.addEventListener("DOMContentLoaded",function(){const form=document.querySelector("#memberBarangModal form"),date=form&&form.querySelector("[name=\"tanggal_masuk\"]");if(date&&!form.querySelector("[name=\"tanggal_expired\"]")){date.parentElement.insertAdjacentHTML("afterend","<div class=\"col-md-6\"><label class=\"form-label\">Tanggal kedaluwarsa</label><input class=\"form-control\" type=\"date\" name=\"tanggal_expired\" title=\"Isi untuk tinta, bahan habis pakai, atau lisensi\"></div>");form.querySelector("[name=\"tanggal_expired\"]").value='.json_encode(isset($memberEdit) ? ($memberEdit['tanggal_expired'] ?? '') : '').';}});</script>';
}
if ($page === 'scan') {
    echo '<script>window.addEventListener("DOMContentLoaded",function(){const reader=document.getElementById("reader");if(!reader)return;const hideInternalError=()=>reader.querySelectorAll("*").forEach(function(node){if(/TypeError:|undefined is not an object|toString/.test(node.textContent||"")){node.style.display="none";}});hideInternalError();new MutationObserver(hideInternalError).observe(reader,{childList:true,subtree:true});});</script>';
}
if ($page === 'scan') {
    echo '<script>window.ok=function(text){try{const url=new URL(text,location.href),kode=url.searchParams.get("kode")||text;const form=document.createElement("form");form.method="post";form.action="'.e(url('index.php?page=scan')).'";form.innerHTML="<input type=\"hidden\" name=\"csrf\" value=\"'.e(csrf()).'\"><input type=\"hidden\" name=\"action\" value=\"consume_scan\"><input type=\"hidden\" name=\"kode\" value=\""+String(kode).replace(/[&<>\"\x27]/g,"")+"\">";document.body.appendChild(form);form.submit();}catch(error){window.location.href="'.e(url('detail.php?kode=')).'"+encodeURIComponent(text);}};</script>';
}
if ($page === 'barang' && is_admin()) {
    echo '<script>window.addEventListener("DOMContentLoaded",function(){const status=document.querySelector("#barangModal select[name=status]");if(status)status.value='.json_encode($edit['status'] ?? 'Tersedia').';});</script>';
}
if ($page === 'barang') {
    echo '<script>document.addEventListener("DOMContentLoaded", function(){ document.querySelectorAll("tr[data-detail]").forEach(function(row){ row.addEventListener("click", function(event){ if (event.target.closest("a, button, form, input, select, textarea")) return; window.location.href = row.dataset.detail; }); }); });</script>';
}
if ($page === 'barang') {
    $stockMeta = [];
    foreach ($items as $item) {
        $total = (int)($item['total_unit'] ?: $item['stok']);
        $used = (int)($item['terpakai_unit'] ?? 0);
        $damaged = (int)($item['rusak_unit'] ?? 0);
        $out = (int)($item['keluar_unit'] ?? 0);
        $stockMeta[$item['kode']] = ['available' => max(0, $total - $used - $damaged - $out),'total' => $total,'used' => $used,'damaged' => $damaged,'out' => $out];
    } echo '<script>window.addEventListener("DOMContentLoaded",function(){const meta='.json_encode($stockMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP).';document.querySelectorAll(".table tbody tr").forEach(function(row){const code=row.cells[0]?.textContent.trim(),item=meta[code],cell=row.cells[3];if(!item||!cell)return;cell.innerHTML="<span class=\"badge text-bg-primary\">"+item.available+"/"+item.total+" unit</span><br><small class=\"text-secondary\">Terpakai "+item.used+" · Rusak "+item.damaged+" · Hilang/Keluar "+item.out+"</small>";});});</script>';
}
if ($page === 'dashboard') {
    echo '<script>document.addEventListener("DOMContentLoaded",function(){const grid=document.querySelector(".row.g-4:has(> .activity-feed-column)"),room=grid?.nextElementSibling,latest=grid?.firstElementChild;if(!grid||!room||!latest||!room.classList.contains("card"))return;latest.appendChild(room);room.classList.remove("mt-4");room.style.width="100%";room.style.marginTop="1.5rem";});</script>';
}
if ($page === 'dashboard') {
    echo '<script>document.addEventListener("DOMContentLoaded",function(){const grid=document.querySelector(".row.g-4:has(> .activity-feed-column)"),table=grid?.querySelector("table");if(!table)return;table.querySelectorAll("tbody tr").forEach(function(row){const code=row.cells[0]?.textContent.trim();if(!code)return;row.tabIndex=0;row.style.cursor="pointer";const open=function(){location.href="'.e(url('detail.php?kode=')).'"+encodeURIComponent(code);};row.addEventListener("click",open);row.addEventListener("keydown",function(event){if(event.key==="Enter"||event.key===" "){event.preventDefault();open();}});});});</script>';
}
require __DIR__.'/partials/footer.php';

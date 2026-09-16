<?php
require_once __DIR__.'/bootstrap.php';
if (logged_in()) {
    permission_any('barang_view', 'units_view');
}
$kode = trim($_GET['kode'] ?? '');
$activityView = ($_GET['view'] ?? '') === 'activity';
$locations = $pdo->query('SELECT * FROM lokasi ORDER BY gedung,ruang,rak')->fetchAll(PDO::FETCH_ASSOC);
if (is_post()) {
    permission_only('unit_manage');
    verify_csrf();
    $unitId = (int)($_POST['unit_id'] ?? 0);
    $unitCode = trim($_POST['kode_unit'] ?? '');
    $status = $_POST['status'] ?? 'tersedia';
    $condition = $_POST['kondisi'] ?? 'Baik';
    $expired = trim($_POST['tanggal_expired'] ?? '') ?: null;
    if (!in_array($status, ['tersedia','keluar','terpakai','rusak'], true)) {
        throw new Exception('Status unit tidak valid.');
    }
    if (!in_array($condition, ['Baik','Rusak Ringan','Rusak Berat'], true)) {
        throw new Exception('Kondisi unit tidak valid.');
    }
    if ($expired !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expired)) {
        $expired = null;
    }
    $before = $pdo->prepare('SELECT id,barang_id,status FROM barang_unit WHERE kode_unit=?');
    $before->execute([$unitCode]);
    $unitBefore = $before->fetch(PDO::FETCH_ASSOC);
    if (!$unitBefore) {
        throw new Exception('Unit QR tidak ditemukan.');
    }
    $pdo->beginTransaction();
    $update = $pdo->prepare('UPDATE barang_unit SET nama_unit=?,lokasi_id=?,spesifikasi=?,kondisi=?,catatan=?,tanggal_expired=?,status=? WHERE kode_unit=?');
    $update->execute([trim($_POST['nama_unit'] ?? '') ?: null,$_POST['lokasi_id'] ?: null,trim($_POST['spesifikasi'] ?? '') ?: null,$condition,trim($_POST['catatan'] ?? '') ?: null,$expired,$status,$unitCode]);
    if ($unitBefore['status'] !== $status && (($unitBefore['status'] === 'tersedia' && $status === 'terpakai') || ($unitBefore['status'] === 'terpakai' && $status === 'tersedia'))) {
        $stockDelta = $status === 'terpakai' ? -1 : 1;
        $stock = $pdo->prepare('UPDATE barang SET stok=GREATEST(0, stok + ?) WHERE id=?');
        $stock->execute([$stockDelta, (int)$unitBefore['barang_id']]);
    }
    log_unit_status_change((int)$unitBefore['id'], (int)$unitBefore['barang_id'], $unitBefore['status'], $status, 'Perubahan status dari detail QR');
    $pdo->commit();
    flash('success', 'Data unit berhasil diperbarui.');
    header('Location: '.url('detail.php?kode='.rawurlencode($unitCode)));
    exit;
}
$s = $pdo->prepare('SELECT b.*,k.nama kategori,l.gedung,l.ruang,l.rak,l.keterangan lokasi_keterangan,bu.id unit_id,bu.kode_unit,bu.nama_unit,bu.lokasi_id unit_lokasi_id,bu.status unit_status,bu.spesifikasi unit_spesifikasi,bu.kondisi unit_kondisi,bu.catatan unit_catatan,bu.tanggal_expired unit_tanggal_expired,ul.gedung unit_gedung,ul.ruang unit_ruang,ul.rak unit_rak FROM barang_unit bu JOIN barang b ON b.id=bu.barang_id LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN lokasi l ON l.id=b.lokasi_id LEFT JOIN lokasi ul ON ul.id=bu.lokasi_id WHERE bu.kode_unit=? AND b.aktif=1');
$s->execute([$kode]);
$b = $s->fetch(PDO::FETCH_ASSOC);
if (!$b) {
    $s = $pdo->prepare('SELECT b.*,k.nama kategori,l.gedung,l.ruang,l.rak,l.keterangan lokasi_keterangan FROM barang b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN lokasi l ON l.id=b.lokasi_id WHERE b.kode=? AND b.aktif=1');
    $s->execute([$kode]);
    $b = $s->fetch(PDO::FETCH_ASSOC);
}
$displayCode = $b['kode_unit'] ?? ($b['kode'] ?? $kode);
$isUnit = !empty($b['kode_unit']);
if ($activityView) {
    $typeLabel = ['umum' => 'Barang umum','habis_pakai' => 'Bahan habis pakai','lisensi' => 'Lisensi'][$b['tipe_barang'] ?? 'umum'] ?? 'Barang umum';
    require __DIR__.'/partials/header.php';
    echo '<style>.sidebar,.topbar,.navbar{display:none!important}.main{margin-left:0!important;padding-top:0!important}.container-fluid{max-width:820px;margin:auto}.activity-type-card{background:#eaf2ff;border:1px solid #c7d7f0;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1rem}.activity-unit-card{background:#fff;border:1px solid #dbe5f5;border-radius:14px;box-shadow:0 10px 28px #153b7a14;padding:1.5rem}.activity-unit-card h5{margin-bottom:1.25rem}.activity-unit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.activity-unit-field{min-width:0;border:1px solid #e5edf9;border-radius:9px;padding:.75rem;background:#f8fbff}.activity-unit-field.wide{grid-column:span 3}.activity-unit-label{display:block;color:#5b6b88;font-size:.75rem;font-weight:700;text-transform:uppercase;margin-bottom:.3rem}.activity-unit-value{overflow-wrap:anywhere;line-height:1.5}@media(max-width:767px){.activity-unit-card{padding:1rem}.activity-unit-grid{grid-template-columns:1fr;gap:.75rem}.activity-unit-field.wide{grid-column:span 1}}</style>';
    if (!$b || !$isUnit) {
        echo '<div class="alert alert-danger">Detail unit tidak ditemukan.</div>';
    } else {
        echo '<div class="activity-type-card"><span class="activity-unit-label">Jenis barang</span><strong>'.e($typeLabel).'</strong></div><section class="activity-unit-card"><h5>Keterangan Lengkap Unit</h5><div class="activity-unit-grid"><div class="activity-unit-field"><span class="activity-unit-label">Kode unit</span><div class="activity-unit-value fw-semibold">'.e($displayCode).'</div></div><div class="activity-unit-field"><span class="activity-unit-label">Nama / label unit</span><div class="activity-unit-value fw-semibold">'.e($b['nama_unit'] ?: 'Belum diisi').'</div></div><div class="activity-unit-field"><span class="activity-unit-label">Status unit</span><div class="activity-unit-value">'.e(unit_status_label($b['unit_status'] ?? 'tersedia')).'</div></div><div class="activity-unit-field"><span class="activity-unit-label">Lokasi penyimpanan unit</span><div class="activity-unit-value">'.e(location_name(['gedung' => $b['unit_gedung'] ?? '','ruang' => $b['unit_ruang'] ?? '','rak' => $b['unit_rak'] ?? '']) ?: 'Mengikuti lokasi master').'</div></div><div class="activity-unit-field"><span class="activity-unit-label">Kondisi unit</span><div class="activity-unit-value">'.e($b['unit_kondisi'] ?? $b['kondisi']).'</div></div><div class="activity-unit-field wide"><span class="activity-unit-label">Spesifikasi unit</span><div class="activity-unit-value">'.nl2br(e($b['unit_spesifikasi'] ?: 'Belum diisi')).'</div></div><div class="activity-unit-field wide"><span class="activity-unit-label">Catatan khusus unit</span><div class="activity-unit-value">'.nl2br(e($b['unit_catatan'] ?: 'Belum diisi')).'</div></div></div></section>';
    }
    echo '<div class="activity-dashboard-back"><a class="btn btn-primary" href="'.e(url('index.php?page=dashboard')).'">Kembali ke Dashboard</a></div>';
    require __DIR__.'/partials/footer.php';
    exit;
}
if ($b) {
    $b['kode'] = $displayCode;
}$unitSummary = ['total' => 0,'expired' => 0,'has_expiry' => 0,'terpakai' => 0,'tersedia' => 0,'rusak' => 0,'keluar' => 0];
if ($b) {
    $summary = $pdo->prepare("SELECT COUNT(*) total,SUM(CASE WHEN bu.status='terpakai' THEN 1 ELSE 0 END) terpakai,SUM(CASE WHEN bu.status='tersedia' THEN 1 ELSE 0 END) tersedia,SUM(CASE WHEN bu.status='rusak' OR bu.kondisi IN ('Rusak Ringan','Rusak Berat') THEN 1 ELSE 0 END) rusak,SUM(CASE WHEN bu.status='keluar' THEN 1 ELSE 0 END) keluar,SUM(CASE WHEN COALESCE(bu.tanggal_expired,b.tanggal_expired) IS NOT NULL THEN 1 ELSE 0 END) has_expiry,SUM(CASE WHEN COALESCE(bu.tanggal_expired,b.tanggal_expired) IS NOT NULL AND COALESCE(bu.tanggal_expired,b.tanggal_expired)<CURDATE() THEN 1 ELSE 0 END) expired FROM barang_unit bu JOIN barang b ON b.id=bu.barang_id WHERE bu.barang_id=?");
    $summary->execute([(int)$b['id']]);
    $unitSummary = array_merge($unitSummary, array_map('intval', $summary->fetch(PDO::FETCH_ASSOC) ?: []));
}require __DIR__.'/partials/header.php';?>

<?php if ($b && $isUnit): ?><div class="alert alert-info mb-4"><h5 class="mb-3">Keterangan Lengkap Unit</h5><div class="row g-3"><div class="col-md-4"><small>Kode unit</small><div class="fw-semibold"><?=e($displayCode)?></div></div><div class="col-md-4"><small>Nama / label unit</small><div class="fw-semibold"><?=e($b['nama_unit'] ?: 'Belum diisi')?></div></div><div class="col-md-4"><small>Status unit</small><div><?=e(ucfirst($b['unit_status'] ?? 'tersedia'))?></div></div><div class="col-md-6"><small>Lokasi penyimpanan unit</small><div><?=e(location_name(['gedung' => $b['unit_gedung'] ?? '','ruang' => $b['unit_ruang'] ?? '','rak' => $b['unit_rak'] ?? '']) ?: 'Mengikuti lokasi master')?></div></div><div class="col-md-6"><small>Kondisi unit</small><div><?=e($b['unit_kondisi'] ?? $b['kondisi'])?></div></div><div class="col-12"><small>Spesifikasi unit</small><div><?=nl2br(e($b['unit_spesifikasi'] ?: 'Belum diisi'))?></div></div><div class="col-12"><small>Catatan khusus unit</small><div><?=nl2br(e($b['unit_catatan'] ?: 'Belum diisi'))?></div></div></div></div><?php if (has_permission('unit_manage')): ?><div class="card mb-4"><div class="card-body"><h5>Edit data unit</h5><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="unit_id" value="<?=e($b['id'])?>"><input type="hidden" name="kode_unit" value="<?=e($displayCode)?>"><div class="col-md-6"><label class="form-label">Nama / label unit</label><input class="form-control" name="nama_unit" maxlength="150" value="<?=e($b['nama_unit'] ?? '')?>"></div><div class="col-md-6"><label class="form-label">Lokasi unit</label><select class="form-select" name="lokasi_id"><option value="">Ikuti lokasi master</option><?php foreach ($locations as $location): ?><option value="<?=e($location['id'])?>" <?=((string)($b['unit_lokasi_id'] ?? '') === (string)$location['id']) ? 'selected' : ''?>><?=e(location_name($location))?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Status unit</label><select class="form-select" name="status"><?php foreach (['tersedia' => 'Tersedia','keluar' => 'Keluar','terpakai' => 'Terpakai','rusak' => 'Rusak'] as $statusValue => $statusLabel): ?><option value="<?=e($statusValue)?>" <?=($b['unit_status'] ?? 'tersedia') === $statusValue ? 'selected' : ''?>><?=e($statusLabel)?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Kondisi</label><select class="form-select" name="kondisi"><?php foreach (['Baik','Rusak Ringan','Rusak Berat'] as $condition): ?><option <?=($b['unit_kondisi'] ?? 'Baik') === $condition ? 'selected' : ''?>><?=e($condition)?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Tanggal kedaluwarsa</label><input class="form-control" type="date" name="tanggal_expired" value="<?=e($b['unit_tanggal_expired'] ?? '')?>"></div><div class="col-md-6"><label class="form-label">Spesifikasi unit</label><textarea class="form-control" name="spesifikasi" rows="3"><?=e($b['unit_spesifikasi'] ?? '')?></textarea></div><div class="col-md-6"><label class="form-label">Catatan khusus unit</label><textarea class="form-control" name="catatan" rows="3"><?=e($b['unit_catatan'] ?? '')?></textarea></div><div class="col-12"><button class="btn btn-primary">Simpan perubahan</button></div></form></div></div><?php endif; ?><?php endif;
if (!$b):?><div class="alert alert-danger">Data barang tidak ditemukan.</div><a class="btn btn-primary" href="<?=url('index.php?page=scan')?>">Scan ulang</a><?php else:$detail = url('detail.php?kode='.rawurlencode($displayCode));
    $qr = qr_image_url($displayCode, 300);?>
<div class="d-flex justify-content-between align-items-center mb-3"><a href="<?=url('index.php?page=barang')?>" class="btn btn-outline-secondary">← Kembali</a><button onclick="window.print()" class="btn btn-outline-primary">Cetak QR</button></div><div class="row g-4"><div class="col-lg-8"><div class="card"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><span class="badge text-bg-primary mb-2"><?=e($b['kode'])?></span><h3><?=e($b['nama'])?></h3></div><div class="text-end"><span class="badge text-bg-<?= ($b['unit_kondisi'] ?? $b['kondisi']) === 'Baik' ? 'success' : 'warning'?> h-25"><?=e($b['unit_kondisi'] ?? $b['kondisi'])?></span><br><span class="badge text-bg-info mt-2"><?=e($b['unit_status'] ?? $b['status'] ?? 'Tersedia')?></span></div></div><hr><div class="row g-3"><div class="col-md-6"><small class="text-secondary">Kategori</small><div><?=e($b['kategori'] ?? '-')?></div></div><div class="col-md-6"><small class="text-secondary">Identitas unit</small><div class="fw-bold"><?=e($displayCode)?></div></div><div class="col-md-6"><small class="text-secondary">Stok master</small><div><?=e($b['stok'].' '.$b['satuan'])?></div></div><div class="col-md-6"><small class="text-secondary">Lokasi unit</small><div><?=e(location_name(['gedung' => $b['unit_gedung'] ?? '','ruang' => $b['unit_ruang'] ?? '','rak' => $b['unit_rak'] ?? '']) ?: location_name($b) ?: '-')?></div></div><div class="col-md-6"><small class="text-secondary">Tanggal masuk</small><div><?=e($b['tanggal_masuk'] ?? '-')?></div></div><div class="col-md-6"><small class="text-secondary">Kedaluwarsa unit</small><div><?php $unitExpiry = expiry_status($b['unit_tanggal_expired'] ?? null); ?><?= $unitExpiry ? '<span class="badge text-bg-'.e($unitExpiry['class']).'">'.e($unitExpiry['label']).' · '.e(date('d-m-Y', strtotime($b['unit_tanggal_expired']))).'</span>' : 'Tidak diatur' ?></div></div><div class="col-12"><small class="text-secondary">Spesifikasi unit</small><div><?=nl2br(e($b['unit_spesifikasi'] ?? $b['deskripsi'] ?: '-'))?></div></div><div class="col-12"><small class="text-secondary">Catatan unit</small><div><?=nl2br(e($b['unit_catatan'] ?? 'Tidak ada catatan khusus.'))?></div></div></div></div></div></div><div class="col-lg-4"><div class="card text-center"><div class="card-body"><h5>QR Code Unit</h5><img class="img-fluid qr my-2" src="<?=e($qr)?>" alt="QR <?=e($b['kode'])?>"><p class="small text-secondary">QR ini mengarah ke identitas unit dan keterangannya.</p><a class="btn btn-outline-primary btn-sm" download="QR-<?=e($b['kode'])?>.png" href="<?=e($qr)?>">Unduh QR</a></div></div></div></div>
<?php endif;
echo '<script>document.addEventListener("DOMContentLoaded",function(){const summary=document.getElementById("detail-summary"),editTitle=[...document.querySelectorAll("h5")].find(function(title){return title.textContent.trim()==="Edit data unit";}),editCard=editTitle?.closest(".card"),unitTitle=[...document.querySelectorAll("h5")].find(function(title){return title.textContent.trim()==="Keterangan Lengkap Unit";}),statuses=[["tersedia","Tersedia"],["terpakai","Terpakai"],["rusak","Rusak"],["keluar","Hilang / Keluar"]];if(unitTitle)unitTitle.closest(".alert")?.remove();if(summary&&editCard){summary.after(editCard);editCard.classList.add("detail-edit-card");}document.querySelectorAll("select[name=status]").forEach(function(select){const selected=select.value;select.replaceChildren(...statuses.map(function(status){const option=new Option(status[1],status[0]);option.selected=status[0]===selected;return option;}));});});</script>';
require __DIR__.'/partials/footer.php';
?>
<?php if ($b): ?><div id="detail-summary" class="card mb-4"><div class="card-body"><h5 class="mb-3">Ringkasan barang</h5><div class="row g-2"><div class="col-6 col-md-2"><div class="border rounded p-2 h-100"><small class="text-secondary">Total unit</small><div class="fs-5 fw-bold"><?=e($unitSummary['total'])?></div></div></div><div class="col-6 col-md-2"><div class="border rounded p-2 h-100"><small class="text-secondary">Tersedia</small><div class="fs-5 fw-bold text-success"><?=e($unitSummary['tersedia'])?></div></div></div><div class="col-6 col-md-2"><div class="border rounded p-2 h-100"><small class="text-secondary">Terpakai</small><div class="fs-5 fw-bold text-info"><?=e($unitSummary['terpakai'])?></div></div></div><div class="col-6 col-md-2"><div class="border rounded p-2 h-100"><small class="text-secondary">Rusak</small><div class="fs-5 fw-bold text-danger"><?=e($unitSummary['rusak'])?></div></div></div><div class="col-6 col-md-2"><div class="border rounded p-2 h-100"><small class="text-secondary">Memiliki exp</small><div class="fs-5 fw-bold text-primary"><?=e($unitSummary['has_expiry'])?></div></div></div><div class="col-6 col-md-2"><div class="border rounded p-2 h-100"><small class="text-secondary">Kedaluwarsa</small><div class="fs-5 fw-bold text-warning"><?=e($unitSummary['expired'])?></div></div></div></div></div></div><?php endif; ?>
<script>document.addEventListener("DOMContentLoaded",function(){const summary=document.getElementById("detail-summary"),container=document.querySelector("main .container-fluid");if(summary&&container)container.appendChild(summary);});</script>
<?php if ($b): ?><script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("small").forEach(function(label){if(label.textContent.trim()!=="Stok master")return;const value=label.nextElementSibling,available=Math.max(0,<?=e($unitSummary['total'])?>-<?=e($unitSummary['terpakai'])?>-<?=e($unitSummary['rusak'])?>-<?=e($unitSummary['keluar'])?>);if(value)value.innerHTML=available+"/<?=e($unitSummary['total'] ?: $b['stok'])?> <?=e($b['satuan'])?> <small class=\"text-secondary\">(Terpakai <?=e($unitSummary['terpakai'])?> · Rusak <?=e($unitSummary['rusak'])?> · Hilang/Keluar <?=e($unitSummary['keluar'])?>)</small>";});});</script><?php endif; ?>
<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".badge").forEach(function(badge){if(["keluar","Keluar"].includes(badge.textContent.trim()))badge.textContent="Hilang / Keluar";});});</script>

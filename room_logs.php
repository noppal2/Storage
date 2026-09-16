<?php
require_once __DIR__.'/bootstrap.php';
permission_only('room_log_view');
$rows = $pdo->query('SELECT * FROM room_visits ORDER BY created_at DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
$_GET['page'] = 'room_logs';
require __DIR__.'/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-1">Log scan ruangan</h5><p class="text-secondary small mb-0">Data akun, ruangan, tanggal, dan jam tercatat otomatis saat QR dipindai.</p></div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Waktu</th><th>Nama</th><th>Identitas</th><th>Ruangan</th><th>Keperluan</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?=e(date('d-m-Y H:i', strtotime($row['created_at'])))?></td><td class="fw-semibold"><?=e($row['nama'])?></td><td><?=e($row['identitas'] ?: '-')?></td><td><?=e($row['ruangan'])?></td><td><?=e($row['keperluan'] ?: '-')?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada data pengunjung.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__.'/partials/footer.php';

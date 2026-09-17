<?php

require_once __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$rows = $pdo->query("SELECT * FROM (SELECT t.jenis,t.jumlah,t.tanggal,t.created_at,b.nama,u.nama AS user_name,b.kode AS detail_code,(SELECT bu.kode_unit FROM barang_unit bu WHERE bu.barang_id=t.barang_id ORDER BY bu.id LIMIT 1) AS unit_code,NULL AS new_status FROM transaksi t JOIN barang b ON b.id=t.barang_id JOIN users u ON u.id=t.user_id UNION ALL SELECT CONCAT('status_',usl.new_status) jenis,1 jumlah,DATE(usl.created_at) tanggal,usl.created_at,b.nama,u.nama AS user_name,b.kode AS detail_code,bu.kode_unit AS unit_code,usl.new_status FROM unit_status_logs usl JOIN barang b ON b.id=usl.barang_id JOIN barang_unit bu ON bu.id=usl.unit_id JOIN users u ON u.id=usl.user_id) activities ORDER BY created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
// Menyamakan bentuk transaksi dan perubahan status agar dapat ditampilkan sebagai satu feed.
$activities = array_map(static function ($row) {
    return [
        'nama' => $row['nama'],
        'jenis' => $row['jenis'],
        'jumlah' => (int)$row['jumlah'],
        'user' => $row['user_name'],
        'tanggal' => $row['tanggal'],
        'waktu' => date('d-m-Y H:i:s', strtotime($row['created_at'])),
        'kode' => $row['unit_code'] ?: $row['detail_code'],
        'status' => $row['new_status'],
    ];
}, $rows);
// Memastikan aktivitas terbaru selalu berada di urutan paling atas.
usort($activities, static fn ($a, $b) => strcmp($b['waktu'], $a['waktu']));
echo json_encode(['activities' => array_slice($activities, 0, 8)], JSON_UNESCAPED_UNICODE);

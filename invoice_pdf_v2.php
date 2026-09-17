<?php

require_once __DIR__ . '/bootstrap.php';
permission_only('reports_view');

$periode = $_GET['periode'] ?? 'harian';
if (!in_array($periode, ['harian', 'bulanan'], true)) {
    $periode = 'harian';
}
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$bulan = $_GET['bulan'] ?? date('Y-m');

if ($periode === 'harian') {
    $label = 'Laporan Harian';
    $rentang = date('d F Y', strtotime($tanggal));
    $sql = 'SELECT t.*, CONCAT(DATE_FORMAT(t.tanggal, "%Y-%m-%d"), " ", DATE_FORMAT(t.created_at, "%H:%i:%s")) AS tanggal, b.kode, b.nama, b.satuan, u.nama AS user
            FROM transaksi t JOIN barang b ON b.id=t.barang_id JOIN users u ON u.id=t.user_id
            WHERE t.tanggal=? ORDER BY t.id';
    $params = [$tanggal];
} else {
    $label = 'Laporan Bulanan';
    $rentang = date('F Y', strtotime($bulan . '-01'));
    $sql = 'SELECT t.*, CONCAT(DATE_FORMAT(t.tanggal, "%Y-%m-%d"), " ", DATE_FORMAT(t.created_at, "%H:%i:%s")) AS tanggal, b.kode, b.nama, b.satuan, u.nama AS user
            FROM transaksi t JOIN barang b ON b.id=t.barang_id JOIN users u ON u.id=t.user_id
            WHERE DATE_FORMAT(t.tanggal, "%Y-%m")=? ORDER BY t.tanggal,t.id';
    $params = [$bulan];
}
$statement = $pdo->prepare($sql);
$statement->execute($params);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
// Menghitung ringkasan transaksi untuk ditampilkan pada bagian laporan.
$masuk = 0;
$keluar = 0;
foreach ($rows as $row) {
    if ($row['jenis'] === 'masuk') {
        $masuk += (int) $row['jumlah'];
    } else {
        $keluar += (int) $row['jumlah'];
    }
}

function pdf_clean($text, $limit = 80)
{
    // Membersihkan teks dan mengonversinya ke encoding yang didukung PDF sederhana ini.
    $text = strip_tags((string) $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 3) . '...' : $text;
}
function pdf_text($x, $y, $text, $size = 9, $bold = false)
{
    // Membuat instruksi PDF untuk menulis satu potong teks pada koordinat tertentu.
    $font = $bold ? 'F2' : 'F1';
    $text = str_replace(['StorageQR', 'Storage QR'], 'STOKLY', (string) $text);
    $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], pdf_clean($text));
    return "0 g\nBT /$font $size Tf 1 0 0 1 $x $y Tm ($text) Tj ET\n";
}
function pdf_line($x1, $y1, $x2, $y2)
{
    // Membuat instruksi garis untuk pemisah atau tabel laporan.
    return "$x1 $y1 m $x2 $y2 l S\n";
}
function pdf_fill($r, $g, $b, $x, $y, $width, $height)
{
    // Membuat instruksi persegi berwarna untuk latar atau header laporan.
    return "$r $g $b rg\n$x $y $width $height re f\n";
}
function pdf_document($pages)
{
    // Merakit halaman dan objek PDF mentah beserta tabel referensi silang.
    $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>',3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
    $pageIds = [];
    $next = 5;
    foreach ($pages as $content) {
        $pageId = $next++;
        $streamId = $next++;
        $pageIds[] = $pageId;
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
        $objects[$streamId] = '<< /Length ' . strlen($content) . " >>\nstream\n$content\nendstream";
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn ($id) => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
    ksort($objects);
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "$id 0 obj\n$object\nendobj\n";
    }
    $xref = strlen($pdf);
    $maxId = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($maxId + 1) . "\n0000000000 65535 f \n";
    for ($id = 1; $id <= $maxId; $id++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$id] ?? 0) . "\n";
    }
    return $pdf . "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
}

$pages = [];
$newPage = function () use (&$pages, $label, $rentang, $masuk, $keluar, $rows) {
    $content = pdf_fill(.12, .22, .36, 40, 552, 762, 3);
    $content .= pdf_text(40, 575, 'STOKLY', 19, true) . pdf_text(40, 559, 'Manajemen Storage / Gudang', 8);
    $content .= pdf_text(650, 573, strtoupper($label), 10, true) . pdf_text(650, 558, 'Periode: ' . $rentang, 8);
    $content .= pdf_text(40, 520, 'Ringkasan transaksi', 10, true) . pdf_text(40, 505, 'Catatan pergerakan barang pada periode yang dipilih.', 8);
    $content .= pdf_fill(.94, .96, .97, 40, 455, 762, 35) . pdf_line(294, 455, 294, 490) . pdf_line(548, 455, 548, 490);
    $content .= pdf_text(58, 478, 'TOTAL TRANSAKSI', 7, true) . pdf_text(58, 463, count($rows), 15, true);
    $content .= pdf_text(312, 478, 'BARANG MASUK', 7, true) . pdf_text(312, 463, $masuk, 15, true);
    $content .= pdf_text(566, 478, 'BARANG KELUAR', 7, true) . pdf_text(566, 463, $keluar, 15, true);
    $content .= pdf_fill(.86, .90, .95, 40, 420, 762, 22);
    foreach ([[47,'#'],[70,'TANGGAL'],[172,'WAKTU INPUT'],[280,'KODE / BARANG'],[440,'JENIS'],[500,'JUMLAH'],[570,'USER'],[640,'KETERANGAN']] as $header) {
        $content .= pdf_text($header[0], 427, $header[1], 7, true);
    }
    $pages[] = ['content' => $content, 'y' => 397];
};
$newPage();
foreach ($rows as $index => $row) {
    $last = count($pages) - 1;
    if ($pages[$last]['y'] < 75) {
        $newPage();
        $last = count($pages) - 1;
    }
    $y = $pages[$last]['y'];
    if ($index % 2 === 0) {
        $pages[$last]['content'] .= pdf_fill(.975, .98, .985, 40, $y - 17, 762, 30);
    }
    $pages[$last]['content'] .= pdf_text(47, $y, $index + 1, 8) . pdf_text(70, $y, date('Y-m-d', strtotime($row['tanggal'])), 7);
    $pages[$last]['content'] .= pdf_text(172, $y, date('d-m-Y', strtotime($row['created_at'])), 7) . pdf_text(172, $y - 10, date('H:i:s', strtotime($row['created_at'])), 7);
    $pages[$last]['content'] .= pdf_text(280, $y, $row['kode'], 8, true) . pdf_text(280, $y - 10, $row['nama'], 7);
    $pages[$last]['content'] .= pdf_text(440, $y, ucfirst($row['jenis']), 8) . pdf_text(500, $y, $row['jumlah'] . ' ' . $row['satuan'], 8);
    $pages[$last]['content'] .= pdf_text(570, $y, $row['user'], 7) . pdf_text(640, $y, $row['keterangan'] ?: '-', 7);
    $pages[$last]['content'] .= pdf_line(40, $y - 17, 802, $y - 17);
    $pages[$last]['y'] -= 30;
}
foreach ($pages as $pageIndex => &$page) {
    $page['content'] .= pdf_line(40, 35, 802, 35);
    $page['content'] .= pdf_text(40, 39, 'Dicetak ' . date('d-m-Y H:i') . ' | Oleh: ' . $_SESSION['user']['nama'], 7);
    $page['content'] .= pdf_text(755, 39, 'Hal. ' . ($pageIndex + 1) . '/' . count($pages), 7);
}
unset($page);
$binary = pdf_document(array_column($pages, 'content'));
$filename = 'laporan-stok-' . ($periode === 'harian' ? $tanggal : $bulan) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($binary));
header('Cache-Control: private, max-age=0');
echo $binary;
exit;

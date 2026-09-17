<?php
require_once __DIR__.'/bootstrap.php';
// Memvalidasi lokasi QR lalu mencatat kunjungan ruangan dari formulir.
$locationId = (int)($_GET['lokasi'] ?? $_POST['lokasi_id'] ?? 0);
$loginTarget = url('room_visit.php?lokasi='.$locationId);
if (!logged_in()) {
    $_SESSION['after_login'] = $loginTarget;
    header('Location: '.url('login.php'));
    exit;
}
$s = $pdo->prepare('SELECT * FROM lokasi WHERE id=?');
$s->execute([$locationId]);
$room = $s->fetch(PDO::FETCH_ASSOC);
if (!$room) {
    http_response_code(404);
    $room = null;
}
$saved = false;
if ($room) {
    $account = $pdo->prepare('SELECT nama,username FROM users WHERE id=? AND aktif=1');
    $account->execute([(int)$_SESSION['user']['id']]);
    $account = $account->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        session_destroy();
        header('Location: '.url('login.php'));
        exit;
    }
    $scanDate = date('Y-m-d');
    $scanTime = date('H:i:s');
    $pdo->prepare('INSERT INTO room_visits(nama,identitas,ruangan,keperluan,kode_scan,barang_id,unit_id,tanggal,jam,user_id) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$account['nama'],$account['username'],location_name($room),'Pengambilan barang','ROOM-'.$locationId,null,null,$scanDate,$scanTime,(int)$_SESSION['user']['id']]);
    $saved = true;
}
require __DIR__.'/partials/header.php';
?>
<?php if (!$room): ?>
<div class="alert alert-danger">QR ruangan tidak valid atau ruangan sudah tidak tersedia.</div>
<?php elseif (!empty($saved)): ?>
<div class="row justify-content-center"><div class="col-lg-6"><div class="card text-center"><div class="card-body p-4"><div class="display-5 text-success mb-3">OK</div><h3>Scan berhasil dicatat</h3><p class="mb-1"><b><?=e($account['nama'])?></b> · <?=e($account['username'])?></p><p class="mb-1">Ruangan: <b><?=e(location_name($room))?></b></p><p class="text-secondary mb-3">Tanggal dan jam: <?=e(date('d-m-Y H:i:s'))?></p><p class="small text-secondary">Kembali ke Dashboard dalam <b id="redirect-countdown">5</b> detik.</p><a class="btn btn-primary" href="<?=url('index.php')?>">Kembali sekarang</a></div></div></div></div><script>(function(){let seconds=5;const countdown=document.getElementById('redirect-countdown'),target=<?=json_encode(url('index.php'))?>;const timer=window.setInterval(()=>{seconds-=1;if(countdown)countdown.textContent=seconds;if(seconds<=0){window.clearInterval(timer);window.location.href=target;}},1000);})();</script>
<?php endif;
require __DIR__.'/partials/footer.php';

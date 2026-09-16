<?php
require_once __DIR__.'/bootstrap.php';
echo '<script src="'.e(url('assets/password-toggle.js?v=2')).'"></script>';
if (logged_in()) {
    header('Location: '.url('index.php'));
    exit;
}
if (is_post()) {
    verify_csrf();
    $username = strtolower(trim($_POST['username'] ?? ''));
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attempt = $pdo->prepare('SELECT * FROM login_attempts WHERE username=? AND ip_address=?');
    $attempt->execute([$username, $ipAddress]);
    $attemptData = $attempt->fetch(PDO::FETCH_ASSOC);
    if ($attemptData && !empty($attemptData['locked_until']) && strtotime($attemptData['locked_until']) > time()) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi beberapa menit lagi.';
    } else {
        $s = $pdo->prepare('SELECT * FROM users WHERE username=? AND aktif=1');
        $s->execute([$username]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            $pdo->prepare('DELETE FROM login_attempts WHERE username=? AND ip_address=?')->execute([$username, $ipAddress]);
            if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $u['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id'], 'nama' => $u['nama'], 'username' => $u['username'], 'role' => $u['role']];
            $afterLogin = $_SESSION['after_login'] ?? null;
            unset($_SESSION['after_login']);
            header('Location: '.($afterLogin ?: url('index.php')));
            exit;
        }
        $now = time();
        if (!$attemptData || empty($attemptData['first_failed_at']) || strtotime($attemptData['first_failed_at']) < $now - 900) {
            $pdo->prepare('INSERT INTO login_attempts(username,ip_address,failed_attempts,first_failed_at,last_attempt_at) VALUES(?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE failed_attempts=1,first_failed_at=NOW(),locked_until=NULL,last_attempt_at=NOW()')->execute([$username, $ipAddress]);
        } else {
            $failedAttempts = (int)$attemptData['failed_attempts'] + 1;
            $lockedUntil = $failedAttempts >= 5 ? date('Y-m-d H:i:s', $now + 900) : null;
            $pdo->prepare('UPDATE login_attempts SET failed_attempts=?,locked_until=?,last_attempt_at=NOW() WHERE username=? AND ip_address=?')->execute([$failedAttempts, $lockedUntil, $username, $ipAddress]);
        }
        $error = 'Username atau password salah.';
    }
}
ob_start(static function (string $output): string {
    return str_replace('<hr><small class="text-secondary">Admin awal: <b>admin</b> / <b>admin123</b></small>', '', $output);
});
echo '<script>document.addEventListener("DOMContentLoaded",function(){const closed=document.querySelector("#toggle-login-password .eye-closed"),open=document.querySelector("#toggle-login-password .eye-open");if(closed)closed.innerHTML="<svg class=\"password-eye-icon\" viewBox=\"0 0 16 16\"><path d=\"M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5 8-5.5 8-5.5M1.17 8a13 13 0 0 1 1.66-2.04C4.12 4.67 5.88 3.5 8 3.5s3.88 1.17 5.17 2.46A13 13 0 0 1 14.83 8a13 13 0 0 1-1.66 2.04C11.88 11.33 10.12 12.5 8 12.5S4.12 11.33 2.83 10.04A13 13 0 0 1 1.17 8\"/><path d=\"M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5\"/></svg>";if(open)open.innerHTML="<svg class=\"password-eye-icon\" viewBox=\"0 0 16 16\"><path d=\"m13.36 11.24 2.49 2.49-.71.71-14-14-.71.71 2.23 2.23A9 9 0 0 1 8 1.5c5 0 8 5.5 8 5.5a14 14 0 0 1-2.64 4.24M5.8 3.68l1.02 1.02A2.5 2.5 0 0 1 10.3 8.18l1.8 1.8A12 12 0 0 0 14.83 7a13 13 0 0 0-1.66-2.04C11.88 3.67 10.12 2.5 8 2.5c-.78 0-1.52.16-2.2.42z\"/></svg>";});</script>';
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=url('assets/style.css')?>" rel="stylesheet"></head><body><div class="container"><div class="row justify-content-center align-items-center min-vh-100"><div class="col-md-5 col-lg-4"><div class="card border-0"><div class="card-body p-4 p-md-5"><div class="stat-icon mb-3">▣</div><h2 class="text-primary fw-bold">StorageQR</h2><p class="text-secondary mb-4">Masuk sebagai Admin atau Member.</p><?php if (!empty($error)):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required autofocus></div><div class="mb-4"><label class="form-label" for="login-password">Password</label><div class="input-group"><input id="login-password" type="password" class="form-control" name="password" required><button type="button" class="btn btn-outline-secondary" id="toggle-login-password" aria-label="Tampilkan password" title="Tampilkan password"><span class="eye-closed" aria-hidden="true">🙈</span><span class="eye-open d-none" aria-hidden="true">🐵</span></button></div></div><button class="btn btn-primary w-100">Masuk</button></form><a class="btn btn-outline-secondary w-100 mt-2" href="<?=url('index.php?page=dashboard')?>">Lanjut sebagai Guest</a><p class="text-center mt-3 mb-0">Belum punya akun? <a href="<?=url('register.php')?>">Daftar sebagai Member</a></p><hr><small class="text-secondary">Admin awal: <b>admin</b> / <b>admin123</b></small></div></div></div></div></div><script>const passwordInput=document.querySelector("#login-password"),passwordToggle=document.querySelector("#toggle-login-password");passwordToggle?.addEventListener("click",()=>{const show=passwordInput.type==="password";passwordInput.type=show?"text":"password";passwordToggle.setAttribute("aria-label",show?"Tampilkan password":"Sembunyikan password");passwordToggle.setAttribute("title",show?"Tampilkan password":"Sembunyikan password");passwordToggle.querySelector(".eye-closed")?.classList.toggle("d-none",show);passwordToggle.querySelector(".eye-open")?.classList.toggle("d-none",!show);});</script></body></html>

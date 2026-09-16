<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$userId = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare('SELECT id,nama,username,email,telepon,bio,role,created_at FROM users WHERE id=? AND aktif=1');
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) {
    session_destroy();
    header('Location: '.url('login.php'));
    exit;
}

$profileError = null;
$passwordError = null;
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_profile') {
            $nama = trim($_POST['nama'] ?? '');
            $username = strtolower(trim($_POST['username'] ?? ''));
            $email = trim($_POST['email'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            if (strlen($nama) < 3) {
                throw new Exception('Nama minimal 3 karakter.');
            }
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
                throw new Exception('Username hanya boleh huruf, angka, titik, garis bawah, atau minus.');
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email belum valid.');
            }
            if ($telepon !== '' && !preg_match('/^[0-9+() .-]{6,30}$/', $telepon)) {
                throw new Exception('Format nomor telepon belum valid.');
            }
            if (strlen($bio) > 255) {
                throw new Exception('Bio maksimal 255 karakter.');
            }
            $save = $pdo->prepare('UPDATE users SET nama=?,username=?,email=?,telepon=?,bio=? WHERE id=?');
            $save->execute([$nama, $username, $email ?: null, $telepon ?: null, $bio ?: null, $userId]);
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['username'] = $username;
            flash('success', 'Profil berhasil diperbarui.');
            header('Location: '.url('profile.php'));
            exit;
        }
        if ($action === 'update_password') {
            $current = $_POST['password_lama'] ?? '';
            $new = $_POST['password_baru'] ?? '';
            $confirm = $_POST['konfirmasi_password'] ?? '';
            $stored = $pdo->prepare('SELECT password FROM users WHERE id=?');
            $stored->execute([$userId]);
            if (!password_verify($current, (string)$stored->fetchColumn())) {
                throw new Exception('Password lama tidak sesuai.');
            }
            if (!password_is_strong($new)) {
                throw new Exception('Password baru minimal 8 karakter dan harus berisi huruf besar, huruf kecil, serta angka.');
            }
            if ($new !== $confirm) {
                throw new Exception('Konfirmasi password tidak sama.');
            }
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            flash('success', 'Password berhasil diganti.');
            header('Location: '.url('profile.php'));
            exit;
        }
    } catch (PDOException $e) {
        $profileError = 'Username tersebut sudah digunakan akun lain.';
    } catch (Exception $e) {
        if ($action === 'update_password') {
            $passwordError = $e->getMessage();
        } else {
            $profileError = $e->getMessage();
        }
    }
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: $profile;
}

$_GET['page'] = 'profil';
require __DIR__ . '/partials/header.php';
$initial = strtoupper(substr($profile['nama'], 0, 1));
?>
<div class="profile-shell">
  <div class="profile-hero mb-4"><div class="profile-avatar"><?=e($initial)?></div><div><span class="eyebrow">AKUN SAYA</span><h3 class="mb-1"><?=e($profile['nama'])?></h3><p class="mb-0 text-secondary">Kelola informasi akun dan keamanan login kamu.</p></div><span class="badge text-bg-primary ms-auto align-self-start"><?=e(role_label($profile['role']))?></span></div>
  <div class="row g-4">
    <div class="col-xl-7"><div class="card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-4"><div><h5 class="mb-1">Informasi profil</h5><p class="text-secondary small mb-0">Data ini digunakan untuk identitas akun di aplikasi.</p></div><span class="profile-card-icon">✦</span></div>
      <?php if ($profileError):?><div class="alert alert-danger"><?=e($profileError)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="update_profile"><div class="row g-3"><div class="col-md-6"><label class="form-label">Nama lengkap</label><input class="form-control" name="nama" required value="<?=e($profile['nama'])?>"></div><div class="col-md-6"><label class="form-label">Username</label><div class="input-group"><span class="input-group-text">@</span><input class="form-control" name="username" required value="<?=e($profile['username'])?>"></div></div><div class="col-md-6"><label class="form-label">Email <span class="text-secondary fw-normal">(opsional)</span></label><input class="form-control" type="email" name="email" value="<?=e($profile['email'] ?? '')?>" placeholder="nama@contoh.com"></div><div class="col-md-6"><label class="form-label">Nomor telepon <span class="text-secondary fw-normal">(opsional)</span></label><input class="form-control" name="telepon" value="<?=e($profile['telepon'] ?? '')?>" placeholder="08xxxxxxxxxx"></div><div class="col-12"><label class="form-label">Bio singkat <span class="text-secondary fw-normal">(opsional)</span></label><textarea class="form-control" name="bio" rows="3" maxlength="255" placeholder="Ceritakan sedikit tentang kamu..."><?=e($profile['bio'] ?? '')?></textarea></div></div><button class="btn btn-primary mt-4">Simpan perubahan</button></form>
    </div></div></div>
    <div class="col-xl-5"><div class="card mb-4"><div class="card-body p-4"><h5 class="mb-1">Ganti password</h5><p class="text-secondary small mb-4">Gunakan password kuat untuk menjaga akun tetap aman.</p><?php if ($passwordError):?><div class="alert alert-danger"><?=e($passwordError)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="update_password"><div class="mb-3"><label class="form-label">Password lama</label><input class="form-control" type="password" name="password_lama" required autocomplete="current-password"></div><div class="mb-3"><label class="form-label">Password baru</label><input class="form-control" type="password" name="password_baru" minlength="8" required autocomplete="new-password"></div><div class="mb-3"><label class="form-label">Konfirmasi password</label><input class="form-control" type="password" name="konfirmasi_password" minlength="8" required autocomplete="new-password"></div><button class="btn btn-outline-primary w-100">Perbarui password</button></form></div></div><div class="card profile-meta"><div class="card-body p-4"><h6 class="mb-3">Ringkasan akun</h6><div class="d-flex justify-content-between border-bottom py-2"><span class="text-secondary">Role</span><strong><?=e(role_label($profile['role']))?></strong></div><div class="d-flex justify-content-between py-2"><span class="text-secondary">Terdaftar</span><strong><?=e(date('d M Y', strtotime($profile['created_at'])))?></strong></div></div></div></div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.profile-shell input[type="password"]').forEach(input=>{
    const group=document.createElement('div');
    group.className='input-group';
    input.parentNode.insertBefore(group,input);
    group.appendChild(input);
    const toggle=document.createElement('button');
    toggle.type='button';
    toggle.className='btn btn-outline-secondary password-toggle';
    toggle.setAttribute('aria-label','Tampilkan password');
    toggle.setAttribute('title','Tampilkan password');
    toggle.innerHTML='<span class="eye-closed" aria-hidden="true"><svg class="password-eye-icon" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.17 8a13 13 0 0 1 1.66-2.04C4.12 4.67 5.88 3.5 8 3.5s3.88 1.17 5.17 2.46A13 13 0 0 1 14.83 8a13 13 0 0 1-1.66 2.04C11.88 11.33 10.12 12.5 8 12.5s-3.88-1.17-5.17-2.46A13 13 0 0 1 1.17 8"/><path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5"/></svg></span><span class="eye-open d-none" aria-hidden="true"><svg class="password-eye-icon" viewBox="0 0 16 16"><path d="m13.36 11.24 2.49 2.49-.71.71-14-14-.71.71 2.23 2.23A9 9 0 0 1 8 1.5c5 0 8 5.5 8 5.5a14 14 0 0 1-2.64 4.24M5.8 3.68l1.02 1.02A2.5 2.5 0 0 1 10.3 8.18l1.8 1.8A12 12 0 0 0 14.83 7a13 13 0 0 0-1.66-2.04C11.88 3.67 10.12 2.5 8 2.5c-.78 0-1.52.16-2.2.42zM8.18 10.48l1.18 1.18c-.44.11-.9.17-2.1-3.17l.72.72A12 12 0 0 0 1.17 6.33a13 13 0 0 0 1.66 2.04C4.12 9.66 5.88 10.83 8 10.83z"/></svg></span>';
    group.appendChild(toggle);
    toggle.addEventListener('click',()=>{
      const show=input.type==='password';
      input.type=show?'text':'password';
      toggle.setAttribute('aria-label',show?'Sembunyikan password':'Tampilkan password');
      toggle.setAttribute('title',show?'Sembunyikan password':'Tampilkan password');
      toggle.querySelector('.eye-closed').classList.toggle('d-none',show);
      toggle.querySelector('.eye-open').classList.toggle('d-none',!show);
    });
  });
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>

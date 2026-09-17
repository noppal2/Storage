<?php
require_once __DIR__.'/bootstrap.php';
echo '<script src="'.e(url('assets/password-toggle.js?v=4')).'"></script>';
if (logged_in()) {
    header('Location: '.url('index.php'));
    exit;
}
echo '<style id="register-form-style">body:has(input[name="konfirmasi"]){font-family:Georgia,"Times New Roman",serif;color:#172554}body:has(input[name="konfirmasi"]) .form-control{color:#212529;background:#fbfdff;border-color:#cbd9ef}body:has(input[name="konfirmasi"]) .form-control:focus{color:#212529;background:#fff;border-color:#60a5fa;box-shadow:0 0 0 .2rem #60a5fa33}body:has(input[name="konfirmasi"]) .form-control::placeholder{color:#5b6b88;opacity:1}body:has(input[name="konfirmasi"]) .password-toggle:hover,body:has(input[name="konfirmasi"]) .password-toggle:focus,body:has(input[name="konfirmasi"]) .password-toggle:active{color:#31547f;background:#fff;border-color:#c9daf4;box-shadow:0 0 0 .2rem #2563eb1c}</style>';
echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("form input[type=password]").forEach(function(input){if(input.closest(".input-group"))return;const group=document.createElement("div"),button=document.createElement("button");group.className="input-group";input.parentNode.insertBefore(group,input);group.appendChild(input);button.type="button";button.className="btn btn-outline-secondary password-toggle";button.setAttribute("aria-label","Tampilkan password");button.setAttribute("title","Tampilkan password");button.innerHTML="<span class=\"eye-closed\" aria-hidden=\"true\"><svg class=\"password-eye-icon\" viewBox=\"0 0 16 16\"><path d=\"M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.17 8a13 13 0 0 1 1.66-2.04C4.12 4.67 5.88 3.5 8 3.5s3.88 1.17 5.17 2.46A13 13 0 0 1 14.83 8a13 13 0 0 1-1.66 2.04C11.88 11.33 10.12 12.5 8 12.5s-3.88-1.17-5.17-2.46A13 13 0 0 1 1.17 8\"/><path d=\"M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5\"/></svg></span><span class=\"eye-open d-none\" aria-hidden=\"true\"><svg class=\"password-eye-icon\" viewBox=\"0 0 16 16\"><path d=\"m13.36 11.24 2.49 2.49-.71.71-14-14-.71.71 2.23 2.23A9 9 0 0 1 8 1.5c5 0 8 5.5 8 5.5a14 14 0 0 1-2.64 4.24M5.8 3.68l1.02 1.02A2.5 2.5 0 0 1 10.3 8.18l1.8 1.8A12 12 0 0 0 14.83 7a13 13 0 0 0-1.66-2.04C11.88 3.67 10.12 2.5 8 2.5c-.78 0-1.52.16-2.2.42zM8.18 10.48l1.18 1.18c-.44.11-.9.17-2.1-3.17l.72.72A12 12 0 0 0 1.17 6.33a13 13 0 0 0 1.66 2.04C4.12 9.66 5.88 10.83 8 10.83z\"/></svg></span>";group.appendChild(button);button.addEventListener("click",function(){const show=input.type==="password";input.type=show?"text":"password";button.setAttribute("aria-label",show?"Sembunyikan password":"Tampilkan password");button.setAttribute("title",show?"Sembunyikan password":"Tampilkan password");button.querySelector(".eye-closed")?.classList.toggle("d-none",show);button.querySelector(".eye-open")?.classList.toggle("d-none",!show);});});});</script>';
echo '<script>document.addEventListener("DOMContentLoaded",function(){const username=document.querySelector("form input[name=username]");if(!username)return;const fields=[["email","Email","email"],["telepon","Nomor telepon","text"],["bio","Bio singkat","textarea"]];fields.reverse().forEach(function(field){const wrapper=document.createElement("div");wrapper.className="mb-3";const label=document.createElement("label");label.className="form-label";label.textContent=field[1]+" (opsional)";const input=field[2]==="textarea"?document.createElement("textarea"):document.createElement("input");input.className="form-control";input.name=field[0];if(field[2]!=="textarea")input.type=field[2];if(field[2]==="textarea"){input.rows=2;input.maxLength=255;}wrapper.append(label,input);username.closest(".mb-3")?.after(wrapper);});});</script>';
if (is_post()) {
    // Memvalidasi data pendaftaran sebelum membuat akun member dan izin awalnya.
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    if (strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $error = 'Nama minimal 3 karakter dan username hanya boleh huruf, angka, titik, garis bawah, atau minus.';
    } elseif (!password_is_strong($password)) {
        $error = 'Password minimal 8 karakter dan harus berisi huruf besar, huruf kecil, serta angka.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email belum valid.';
    } elseif (strlen($bio) > 255) {
        $error = 'Bio maksimal 255 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        try {
            $s = $pdo->prepare('INSERT INTO users(nama,username,email,telepon,bio,password,role) VALUES(?,?,?,?,?, ?,"user")');
            $s->execute([$nama,$username,$email ?: null,$telepon ?: null,$bio ?: null,password_hash($password, PASSWORD_DEFAULT)]);
            $newId = (int)$pdo->lastInsertId();
            $permissionInsert = $pdo->prepare('INSERT INTO user_permissions(user_id,permission) VALUES(?,?)');
            foreach (['dashboard_view','barang_view','units_view','scan_view'] as $permission) {
                $permissionInsert->execute([$newId,$permission]);
            }flash('success', 'Pendaftaran berhasil. Silakan masuk sebagai Member.');
            header('Location: '.url('login.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Username sudah digunakan. Silakan pilih username lain.';
        }
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daftar Member | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container"><div class="row justify-content-center align-items-center min-vh-100"><div class="col-md-6 col-lg-5"><div class="card shadow-sm border-0"><div class="card-body p-4 p-md-5"><h2 class="text-primary fw-bold">Daftar Member</h2><p class="text-secondary">Buat akun untuk mengakses data storage.</p><?php if (!empty($error)):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><div class="mb-3"><label class="form-label">Nama lengkap</label><input class="form-control" name="nama" required value="<?=e($_POST['nama'] ?? '')?>"></div><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required value="<?=e($_POST['username'] ?? '')?>"></div><div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" minlength="8" required></div><div class="mb-4"><label class="form-label">Konfirmasi password</label><input type="password" class="form-control" name="konfirmasi" minlength="8" required></div><button class="btn btn-primary w-100">Daftar sebagai Member</button></form><p class="text-center mt-3 mb-0">Sudah punya akun? <a href="<?=url('login.php')?>">Masuk</a></p></div></div></div></div></div></body></html>

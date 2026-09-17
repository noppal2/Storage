<?php
require_once __DIR__.'/bootstrap.php';
echo '<script src="'.e(url('assets/password-toggle.js?v=4')).'"></script>';
echo '<script>document.addEventListener("DOMContentLoaded",function(){const first=document.getElementById("permission-dashboard_view");if(!first)return;const form=first.form,boxes=[...form.querySelectorAll("input[name=\"permissions[]\"]")];if(!boxes.length)return;const wrapper=document.createElement("div");wrapper.className="form-check mb-2";const all=document.createElement("input");all.type="checkbox";all.className="form-check-input";all.id="select-all-member-permissions";const label=document.createElement("label");label.className="form-check-label fw-semibold";label.htmlFor=all.id;label.textContent="Pilih semua izin";wrapper.append(all,label);first.closest(".border")?.before(wrapper);const sync=function(){all.checked=boxes.every(function(box){return box.checked});all.indeterminate=!all.checked&&boxes.some(function(box){return box.checked})};all.addEventListener("change",function(){boxes.forEach(function(box){box.checked=all.checked});sync()});boxes.forEach(function(box){box.addEventListener("change",sync)});sync()});</script>';
admin_only();

function save_user_permissions($userId, $role, $selected)
{
    // Mengganti seluruh izin pengguna agar perubahan formulir tersimpan konsisten.
    global $pdo;
    $pdo->prepare('DELETE FROM user_permissions WHERE user_id=?')->execute([$userId]);
    if ($role !== 'user') {
        return;
    }
    $selected = array_intersect(array_keys(permission_names()), $selected);
    $insert = $pdo->prepare('INSERT INTO user_permissions(user_id,permission) VALUES(?,?)');
    foreach ($selected as $permission) {
        $insert->execute([$userId, $permission]);
    }
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'create') {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            if (strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username) || !password_is_strong($password)) {
                throw new Exception('Lengkapi data dengan benar. Password minimal 8 karakter dan harus berisi huruf besar, huruf kecil, serta angka.');
            }
            if (!in_array($role, ['admin', 'user'], true)) {
                throw new Exception('Role tidak valid.');
            }
            $pdo->prepare('INSERT INTO users(nama,username,password,role) VALUES(?,?,?,?)')->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $id = (int)$pdo->lastInsertId();
            save_user_permissions($id, $role, $_POST['permissions'] ?? []);
            flash('success', 'Akun baru berhasil dibuat.');
        }
        if ($action === 'update') {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $aktif = isset($_POST['aktif']) ? 1 : 0;
            if (!$id || strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username) || !in_array($role, ['admin', 'user'], true)) {
                throw new Exception('Data akun tidak valid.');
            }
            if ($id === $_SESSION['user']['id'] && ($role !== 'admin' || !$aktif)) {
                throw new Exception('Admin tidak dapat menurunkan role atau menonaktifkan akun sendiri.');
            }
            $email = trim($_POST['email'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email belum valid.');
            }
            if (strlen($bio) > 255) {
                throw new Exception('Bio maksimal 255 karakter.');
            }
            $pdo->prepare('UPDATE users SET nama=?,username=?,email=?,telepon=?,bio=?,role=?,aktif=? WHERE id=?')->execute([$nama, $username, $email ?: null, $telepon ?: null, $bio ?: null, $role, $aktif, $id]);
            save_user_permissions($id, $role, $_POST['permissions'] ?? []);
            if ($id === $_SESSION['user']['id']) {
                $_SESSION['user']['nama'] = $nama;
            }
            flash('success', 'Akun dan izin berhasil diperbarui.');
        }
        if ($action === 'reset_password') {
            $password = $_POST['password'] ?? '';
            if (!$id || !password_is_strong($password)) {
                throw new Exception('Password minimal 8 karakter dan harus berisi huruf besar, huruf kecil, serta angka.');
            }
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            flash('success', 'Password berhasil direset.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    header('Location: '.url('members.php'));
    exit;
}

$users = $pdo->query('SELECT id,nama,username,email,telepon,bio,role,aktif,created_at FROM users ORDER BY role DESC,nama')->fetchAll(PDO::FETCH_ASSOC);
echo '<script>document.addEventListener("DOMContentLoaded",function(){const table=document.querySelector("table");if(!table)return;const users='.json_encode($users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP).',head=table.tHead?.rows[0],rows=[...table.tBodies[0]?.rows||[]];if(!head)return;["Email","Telepon","Bio"].forEach(function(label){const cell=document.createElement("th");cell.textContent=label;head.insertBefore(cell,head.lastElementChild);});rows.forEach(function(row,index){const user=users[index]||{};[user.email||"-",user.telepon||"-",user.bio||"-"] .forEach(function(value){const cell=document.createElement("td");cell.textContent=value;cell.className="small text-secondary";row.insertBefore(cell,row.lastElementChild);});});});</script>';
$edit = null;
$editPermissions = [];
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT id,nama,username,email,telepon,bio,role,aktif FROM users WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
    if ($edit) {
        $s = $pdo->prepare('SELECT permission FROM user_permissions WHERE user_id=?');
        $s->execute([$edit['id']]);
        $editPermissions = $s->fetchAll(PDO::FETCH_COLUMN);
        echo '<script>document.addEventListener("DOMContentLoaded",function(){const username=document.querySelector("form input[name=username]");if(!username)return;const fields=[{name:"email",label:"Email",type:"email",value:'.json_encode($edit['email'] ?? '').'},{name:"telepon",label:"Nomor telepon",type:"text",value:'.json_encode($edit['telepon'] ?? '').'},{name:"bio",label:"Bio",type:"textarea",value:'.json_encode($edit['bio'] ?? '').'}];fields.reverse().forEach(function(field){const wrapper=document.createElement("div");wrapper.className="mb-3";const label=document.createElement("label");label.className="form-label";label.textContent=field.label;const input=field.type==="textarea"?document.createElement("textarea"):document.createElement("input");input.className="form-control";input.name=field.name;input.value=field.value;if(field.type!=="textarea")input.type=field.type;if(field.type==="textarea"){input.rows=2;input.maxLength=255;}wrapper.append(label,input);username.closest(".mb-3")?.after(wrapper);});});</script>';
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kelola Member & Admin | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=url('assets/style.css')?>" rel="stylesheet"></head><body><main class="container py-4 py-md-5" style="max-width:1180px"><div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="mb-0">Kelola Member & Admin</h3><small class="text-secondary">Atur akun, role, dan izin fitur setiap Member.</small></div><a href="<?=url('index.php')?>" class="btn btn-outline-secondary">Dashboard</a></div><?php show_flash();?><div class="row g-4"><div class="col-lg-5"><div class="card"><div class="card-body"><h5><?= $edit ? 'Edit akun dan izin' : 'Tambah akun' ?></h5><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="<?=$edit ? 'update' : 'create'?>"><input type="hidden" name="id" value="<?=e($edit['id'] ?? '')?>"><div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" required value="<?=e($edit['nama'] ?? '')?>"></div><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required value="<?=e($edit['username'] ?? '')?>"></div><?php if (!$edit):?><div class="mb-3"><label class="form-label" for="member-password">Password</label><div class="input-group"><input id="member-password" class="form-control" type="password" name="password" minlength="6" required><button type="button" class="btn btn-outline-secondary password-toggle" data-password-toggle-for="member-password" aria-label="Tampilkan password" title="Tampilkan password"><span class="eye-closed" aria-hidden="true"><svg class="password-eye-icon" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.17 8a13 13 0 0 1 1.66-2.04C4.12 4.67 5.88 3.5 8 3.5s3.88 1.17 5.17 2.46A13 13 0 0 1 14.83 8a13 13 0 0 1-1.66 2.04C11.88 11.33 10.12 12.5 8 12.5s-3.88-1.17-5.17-2.46A13 13 0 0 1 1.17 8"/><path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5"/></svg></span><span class="eye-open d-none" aria-hidden="true"><svg class="password-eye-icon" viewBox="0 0 16 16"><path d="m13.36 11.24 2.49 2.49-.71.71-14-14-.71.71 2.23 2.23A9 9 0 0 1 8 1.5c5 0 8 5.5 8 5.5a14 14 0 0 1-2.64 4.24M5.8 3.68l1.02 1.02A2.5 2.5 0 0 1 10.3 8.18l1.8 1.8A12 12 0 0 0 14.83 7a13 13 0 0 0-1.66-2.04C11.88 3.67 10.12 2.5 8 2.5c-.78 0-1.52.16-2.2.42zM8.18 10.48l1.18 1.18c-.44.11-.9.17-2.1-3.17l.72.72A12 12 0 0 0 1.17 6.33a13 13 0 0 0 1.66 2.04C4.12 9.66 5.88 10.83 8 10.83z"/></svg></span></button></div></div><?php endif?><div class="mb-3"><label class="form-label">Role</label><select class="form-select" name="role"><option value="user" <?=($edit['role'] ?? 'user') === 'user' ? 'selected' : ''?>>Member</option><option value="admin" <?=($edit['role'] ?? '') === 'admin' ? 'selected' : ''?>>Admin</option></select></div><div class="mb-3"><label class="form-label">Izin Member</label><small class="d-block text-secondary mb-2">Admin otomatis memiliki semua akses.</small><div class="border rounded p-2"><?php foreach (permission_names() as $permission => $label):?><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="<?=$permission?>" id="permission-<?=$permission?>" <?=in_array($permission, $editPermissions, true) || (!$edit && in_array($permission, ['dashboard_view','barang_view','units_view'], true)) ? 'checked' : ''?>><label class="form-check-label" for="permission-<?=$permission?>"><?=e($label)?></label></div><?php endforeach;?></div></div><?php if ($edit):?><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="aktif" id="aktif" <?=$edit['aktif'] ? 'checked' : ''?>><label class="form-check-label" for="aktif">Akun aktif</label></div><?php endif?><button class="btn btn-primary w-100">Simpan Akun & Izin</button><?php if ($edit):?><a class="btn btn-light w-100 mt-2" href="<?=url('members.php')?>">Batal</a><?php endif?></form></div></div><?php if ($edit):?><div class="card mt-3"><div class="card-body"><h6>Reset password</h6><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?=$edit['id']?>"><div class="input-group mb-2"><input class="form-control" type="password" name="password" id="reset-member-password" minlength="6" placeholder="Password baru" required><button type="button" class="btn btn-outline-secondary password-toggle" data-password-toggle-for="reset-member-password" aria-label="Tampilkan password" title="Tampilkan password"><span class="eye-closed" aria-hidden="true"><svg class="password-eye-icon" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.17 8a13 13 0 0 1 1.66-2.04C4.12 4.67 5.88 3.5 8 3.5s3.88 1.17 5.17 2.46A13 13 0 0 1 14.83 8a13 13 0 0 1-1.66 2.04C11.88 11.33 10.12 12.5 8 12.5s-3.88-1.17-5.17-2.46A13 13 0 0 1 1.17 8"/><path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5"/></svg></span><span class="eye-open d-none" aria-hidden="true"><svg class="password-eye-icon" viewBox="0 0 16 16"><path d="m13.36 11.24 2.49 2.49-.71.71-14-14-.71.71 2.23 2.23A9 9 0 0 1 8 1.5c5 0 8 5.5 8 5.5a14 14 0 0 1-2.64 4.24M5.8 3.68l1.02 1.02A2.5 2.5 0 0 1 10.3 8.18l1.8 1.8A12 12 0 0 0 14.83 7a13 13 0 0 0-1.66-2.04C11.88 3.67 10.12 2.5 8 2.5c-.78 0-1.52.16-2.2.42zM8.18 10.48l1.18 1.18c-.44.11-.9.17-2.1-3.17l.72.72A12 12 0 0 0 1.17 6.33a13 13 0 0 0 1.66 2.04C4.12 9.66 5.88 10.83 8 10.83z"/></svg></span></button></div><button class="btn btn-outline-warning w-100">Reset Password</button></form></div></div><?php endif?></div><div class="col-lg-7"><div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Terdaftar</th><th></th></tr></thead><tbody><?php foreach ($users as $u):?><tr><td><?=e($u['nama'])?></td><td><?=e($u['username'])?></td><td><span class="badge text-bg-<?=$u['role'] === 'admin' ? 'primary' : 'secondary'?>"><?=$u['role'] === 'admin' ? 'Admin' : 'Member'?></span></td><td><span class="badge text-bg-<?=$u['aktif'] ? 'success' : 'danger'?>"><?=$u['aktif'] ? 'Aktif' : 'Nonaktif'?></span></td><td><?=e(date('d-m-Y', strtotime($u['created_at'])))?></td><td><a class="btn btn-sm btn-outline-primary" href="<?=url('members.php?edit='.$u['id'])?>">Kelola</a></td></tr><?php endforeach?></tbody></table></div></div></div></div></main><script>document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('[data-password-toggle-for]').forEach(function (button) { const input = document.getElementById(button.dataset.passwordToggleFor); if (!input) return; button.addEventListener('click', function () { const show = input.type === 'password'; input.type = show ? 'text' : 'password'; button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password'); button.setAttribute('title', show ? 'Sembunyikan password' : 'Tampilkan password'); button.querySelector('.eye-closed')?.classList.toggle('d-none', show); button.querySelector('.eye-open')?.classList.toggle('d-none', !show); }); }); });</script></body></html>

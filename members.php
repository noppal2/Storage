<?php
require_once __DIR__.'/bootstrap.php';
admin_only();

function save_user_permissions($userId, $role, $selected) {
    global $pdo;
    $pdo->prepare('DELETE FROM user_permissions WHERE user_id=?')->execute([$userId]);
    $selected = $role === 'admin'
        ? array_keys(permission_names())
        : array_intersect(array_keys(permission_names()), $selected);
    $insert = $pdo->prepare('INSERT INTO user_permissions(user_id,permission) VALUES(?,?)');
    foreach ($selected as $permission) $insert->execute([$userId, $permission]);
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
            if (strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username) || !password_is_strong($password)) throw new Exception('Lengkapi data dengan benar. Password minimal 8 karakter dan harus berisi huruf besar, huruf kecil, serta angka.');
            if (!in_array($role, ['admin', 'user'], true)) throw new Exception('Role tidak valid.');
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
            if (!$id || strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username) || !in_array($role, ['admin', 'user'], true)) throw new Exception('Data akun tidak valid.');
            if ($id === $_SESSION['user']['id'] && ($role !== 'admin' || !$aktif)) throw new Exception('Admin tidak dapat menurunkan role atau menonaktifkan akun sendiri.');
            $pdo->prepare('UPDATE users SET nama=?,username=?,role=?,aktif=? WHERE id=?')->execute([$nama, $username, $role, $aktif, $id]);
            save_user_permissions($id, $role, $_POST['permissions'] ?? []);
            if ($id === $_SESSION['user']['id']) $_SESSION['user']['nama'] = $nama;
            flash('success', 'Akun dan izin berhasil diperbarui.');
        }
        if ($action === 'delete') {
            if (!$id || $id === (int)$_SESSION['user']['id']) throw new Exception('Akun yang sedang digunakan tidak dapat dihapus.');
            $s = $pdo->prepare('SELECT id FROM users WHERE id=?');
            $s->execute([$id]);
            if (!$s->fetchColumn()) throw new Exception('Akun tidak ditemukan.');
            $s = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE user_id=?');
            $s->execute([$id]);
            if ((int)$s->fetchColumn() > 0) throw new Exception('Akun memiliki riwayat transaksi dan tidak dapat dihapus. Nonaktifkan akun jika sudah tidak digunakan.');
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            flash('success', 'Akun berhasil dihapus.');
        }
        if ($action === 'reset_password') {
            $password = $_POST['password'] ?? '';
            if (!$id || !password_is_strong($password)) throw new Exception('Password minimal 8 karakter dan harus berisi huruf besar, huruf kecil, serta angka.');
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            flash('success', 'Password berhasil direset.');
        }
    } catch (Throwable $e) { flash('danger', $e->getMessage()); }
    header('Location: '.url('members.php')); exit;
}

$users = $pdo->query('SELECT id,nama,username,role,aktif,created_at FROM users ORDER BY role DESC,nama')->fetchAll(PDO::FETCH_ASSOC);
$edit = null;
$editPermissions = [];
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT id,nama,username,role,aktif FROM users WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
    if ($edit) {
        $s = $pdo->prepare('SELECT permission FROM user_permissions WHERE user_id=?');
        $s->execute([$edit['id']]);
        $editPermissions = $s->fetchAll(PDO::FETCH_COLUMN);
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kelola Member & Admin | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=url('assets/style.css')?>" rel="stylesheet"></head><body><main class="container py-4 py-md-5" style="max-width:1180px"><div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="mb-0">Kelola Member & Admin</h3><small class="text-secondary">Atur akun, role, dan izin fitur setiap Member.</small></div><a href="<?=url('index.php')?>" class="btn btn-outline-secondary">Dashboard</a></div><?php show_flash();?><div class="row g-4"><div class="col-lg-5"><div class="card"><div class="card-body"><h5><?= $edit ? 'Edit akun dan izin' : 'Tambah akun' ?></h5><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="<?=$edit?'update':'create'?>"><input type="hidden" name="id" value="<?=e($edit['id']??'')?>"><div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" required value="<?=e($edit['nama']??'')?>"></div><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required value="<?=e($edit['username']??'')?>"></div><?php if(!$edit):?><div class="mb-3"><label class="form-label">Password</label><div class="input-group"><input id="create-password" class="form-control" type="password" name="password" minlength="6" required><button type="button" class="btn btn-outline-secondary" id="toggle-create-password" aria-label="Tampilkan password" title="Tampilkan password"><span class="eye-closed" aria-hidden="true">&#128584;</span><span class="eye-open d-none" aria-hidden="true">&#128053;</span></button></div></div><?php endif?><div class="mb-3"><label class="form-label">Role</label><select class="form-select" name="role" id="member-role"><option value="user" <?=($edit['role']??'user')==='user'?'selected':''?>>Member</option><option value="admin" <?=($edit['role']??'')==='admin'?'selected':''?>>Admin</option></select></div><?php if($edit):?><div class="mb-3"><label class="form-label">Izin Member</label><small class="d-block text-secondary mb-2">Admin otomatis memiliki semua akses.</small><div class="border rounded p-2"><div class="form-check border-bottom pb-2 mb-2"><input class="form-check-input" type="checkbox" id="permission-all"><label class="form-check-label fw-semibold" for="permission-all">Pilih semua izin</label></div><?php foreach(permission_names() as $permission=>$label):?><div class="form-check"><input class="form-check-input permission-item" type="checkbox" name="permissions[]" value="<?=$permission?>" id="permission-<?=$permission?>" <?=((($edit['role']??'')==='admin')||in_array($permission,$editPermissions,true)||(!$edit&&in_array($permission,['dashboard_view','barang_view','units_view'],true)))?'checked':''?>><label class="form-check-label" for="permission-<?=$permission?>"><?=e($label)?></label></div><?php endforeach;?></div></div><?php endif;?><?php if($edit):?><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="aktif" id="aktif" <?=$edit['aktif']?'checked':''?>><label class="form-check-label" for="aktif">Akun aktif</label></div><?php endif?><button class="btn btn-primary w-100">Simpan Akun & Izin</button><?php if($edit):?><a class="btn btn-light w-100 mt-2" href="<?=url('members.php')?>">Batal</a><?php endif?></form></div></div><?php if($edit):?><div class="card mt-3"><div class="card-body"><h6>Atur password baru</h6><p class="small text-secondary">Password lama tidak dapat ditampilkan karena tersimpan secara aman. Buat password baru jika akun lupa password.</p><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?=$edit['id']?>"><div class="input-group mb-2"><input id="reset-password" class="form-control" type="password" name="password" minlength="8" placeholder="Password baru" required><button type="button" class="btn btn-outline-secondary" id="toggle-reset-password" aria-label="Tampilkan password" title="Tampilkan password"><span class="eye-closed" aria-hidden="true">🙈</span><span class="eye-open d-none" aria-hidden="true">🐵</span></button></div><div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary flex-fill" id="generate-password">Buat password kuat</button><button class="btn btn-outline-warning flex-fill">Simpan password</button></div></form></div></div><?php endif?></div><div class="col-lg-7"><div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Terdaftar</th><th></th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?=e($u['nama'])?></td><td><?=e($u['username'])?></td><td><span class="badge text-bg-<?=$u['role']==='admin'?'primary':'secondary'?>"><?=$u['role']==='admin'?'Admin':'Member'?></span></td><td><span class="badge text-bg-<?=$u['aktif']?'success':'danger'?>"><?=$u['aktif']?'Aktif':'Nonaktif'?></span></td><td><?=e(date('d-m-Y',strtotime($u['created_at'])))?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?=url('members.php?edit='.$u['id'])?>">Kelola</a><form class="d-inline ms-1" method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($u['id'])?>"><button type="submit" onclick="return confirm('Hapus akun ini? Akun dengan riwayat transaksi tidak dapat dihapus.')" class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach?></tbody></table></div></div></div></div></main><script>const permissionAll=document.querySelector("#permission-all");const permissionItems=[...document.querySelectorAll(".permission-item")];if(permissionAll){function syncPermissionAll(){const checked=permissionItems.filter(item=>item.checked).length;permissionAll.checked=permissionItems.length>0&&checked===permissionItems.length;permissionAll.indeterminate=checked>0&&checked<permissionItems.length;}permissionAll.addEventListener("change",()=>{permissionItems.forEach(item=>item.checked=permissionAll.checked);syncPermissionAll();});permissionItems.forEach(item=>item.addEventListener("change",syncPermissionAll));syncPermissionAll();}const memberRole=document.querySelector("#member-role");if(memberRole&&permissionAll)memberRole.addEventListener("change",()=>{if(memberRole.value==="admin"){permissionItems.forEach(item=>item.checked=true);syncPermissionAll();}});</script><script>const createPassword=document.querySelector("#create-password"),toggleCreate=document.querySelector("#toggle-create-password"),resetPassword=document.querySelector("#reset-password"),toggleReset=document.querySelector("#toggle-reset-password"),generatePassword=document.querySelector("#generate-password");const bindPasswordToggle=(input,toggle)=>{toggle?.addEventListener("click",()=>{const show=input.type==="password";input.type=show?"text":"password";toggle.setAttribute("aria-label",show?"Tampilkan password":"Sembunyikan password");toggle.setAttribute("title",show?"Tampilkan password":"Sembunyikan password");toggle.querySelector(".eye-closed")?.classList.toggle("d-none",show);toggle.querySelector(".eye-open")?.classList.toggle("d-none",!show);});};bindPasswordToggle(createPassword,toggleCreate);bindPasswordToggle(resetPassword,toggleReset);generatePassword?.addEventListener("click",()=>{const lower="abcdefghijkmnopqrstuvwxyz",upper="ABCDEFGHJKLMNPQRSTUVWXYZ",numbers="23456789",symbols="@#$%";const pick=source=>source[Math.floor(Math.random()*source.length)];let value=pick(lower)+pick(upper)+pick(numbers)+pick(symbols);const all=lower+upper+numbers+symbols;while(value.length<12)value+=pick(all);resetPassword.value=value;resetPassword.type="text";toggleReset?.querySelector(".eye-open")?.classList.remove("d-none");toggleReset?.querySelector(".eye-closed")?.classList.add("d-none");});</script></body></html>

<script>document.querySelectorAll('#toggle-create-password,#toggle-reset-password').forEach(toggle=>toggle.addEventListener('click',()=>{const input=toggle.previousElementSibling;const visible=input?.type==='text';toggle.setAttribute('aria-label',visible?'Sembunyikan password':'Tampilkan password');toggle.setAttribute('title',visible?'Sembunyikan password':'Tampilkan password');}));</script>

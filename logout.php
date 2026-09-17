<?php

require_once __DIR__.'/bootstrap.php';
// Menghapus session aktif lalu mengembalikan pengguna ke halaman login.
session_destroy();
header('Location: '.url('login.php'));
exit;

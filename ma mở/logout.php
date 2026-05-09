<?php
// ============================================================
// logout.php — Đăng xuất người dùng
// ============================================================
session_start();
session_destroy();
header('Location: ?action=home');
exit;

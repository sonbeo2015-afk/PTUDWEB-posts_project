<?php
session_start();
require_once 'db.php';

// 1. Ghi log hành động đăng xuất nếu người dùng đang trong phiên
if (isset($_SESSION['user_id'])) {
    saveLog($conn, "LOGOUT", "Người dùng đã đăng xuất khỏi hệ thống", $_SESSION['user_id']);
}

// 2. Xóa sạch Session
session_unset();
session_destroy();

// 3. Đẩy về trang đăng nhập
header("Location: login.php");
exit;
?>
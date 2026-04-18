<?php
session_start();
require_once 'db.php';

// Bảo vệ trang: Chỉ cho vào nếu đã xác thực OTP thành công
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])) {
    header("Location: forgot_password.php");
    exit;
}

$message = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $message = "<div style='color:red;'>Mật khẩu không khớp!</div>";
    } else {
        // 1. Mã hóa mật khẩu mới
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

        // 2. Cập nhật vào bảng users
        $sql_update = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);

        if (mysqli_stmt_execute($stmt)) {
            // 3. Dọn dẹp: Xóa các yêu cầu reset cũ của email này
            $sql_delete = "DELETE FROM password_resets WHERE email = ?";
            $stmt_del = mysqli_prepare($conn, $sql_delete);
            mysqli_stmt_bind_param($stmt_del, "s", $email);
            mysqli_stmt_execute($stmt_del);

            // Ghi Log hành động quan trọng
            saveLog($conn, "RESET_PASSWORD", "Đã đổi mật khẩu qua OTP thành công", null);

            // 4. Xóa session và thông báo
            session_unset();
            session_destroy();
            echo "<script>alert('Đổi mật khẩu thành công! Hãy đăng nhập lại.'); window.location.href='login.php';</script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <style> body { font-family: Arial; padding: 50px; } .box { max-width: 400px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; } input { width: 90%; padding: 8px; margin: 10px 0; } button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; } </style>
</head>
<body>
    <div class="box">
        <h2>Đặt mật khẩu mới</h2>
        <p>Email: <b><?= $email ?></b></p>
        <?= $message ?>
        <form method="POST">
            <label>Mật khẩu mới:</label>
            <input type="password" name="new_password" required minlength="6">
            <label>Xác nhận mật khẩu mới:</label>
            <input type="password" name="confirm_password" required minlength="6">
            <button type="submit">Cập nhật mật khẩu</button>
        </form>
    </div>
</body>
</html>
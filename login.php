<?php
session_start();
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = trim($_POST['login_id']); // Có thể là Username hoặc Email
    $password = $_POST['password'];

    // Tìm user bằng Username HOẶC Email
    $sql = "SELECT id, username, email, password, role_id FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $login_id, $login_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role_id'] = $row['role_id'];

                saveLog($conn, "LOGIN", "Đăng nhập thành công", $row['id']);
                header("Location: index.php");
                exit; 
            } else {
                $message = "<div class='error'>Sai mật khẩu!</div>";
            }
        } else {
            $message = "<div class='error'>Tài khoản hoặc Email không tồn tại!</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <style> body { font-family: Arial; padding: 50px; } .box { max-width: 400px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; } input { width: 90%; padding: 8px; margin: 5px 0 15px 0; } button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; } .error { color: red; margin-bottom: 15px; } </style>
</head>
<body>
    <div class="box">
        <h2>Đăng Nhập</h2>
        <?= $message ?>
        <form method="POST">
            <label>Tên đăng nhập hoặc Email:</label><br>
            <input type="text" name="login_id" required>
            
            <label>Mật khẩu:</label><br>
            <input type="password" name="password" required>
            
            <button type="submit">Đăng nhập</button>
        </form>
        <p><a href="forgot_password.php">Quên mật khẩu?</a></p>
        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
    </div>
</body>
</html>
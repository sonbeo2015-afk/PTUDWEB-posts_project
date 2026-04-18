<?php
session_start();
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "<div class='error'>Lỗi: Mật khẩu xác nhận không khớp!</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);
            try {
                if (mysqli_stmt_execute($stmt)) {
                    $new_user_id = mysqli_insert_id($conn);
                    
                    // --- TỰ ĐỘNG TẠO PROFILE ---
                    $sql_profile = "INSERT INTO profiles (user_id) VALUES (?)";
                    $stmt_profile = mysqli_prepare($conn, $sql_profile);
                    mysqli_stmt_bind_param($stmt_profile, "i", $new_user_id);
                    mysqli_stmt_execute($stmt_profile);
                    
                    // Ghi Log
                    saveLog($conn, "REGISTER", "Tạo tài khoản: $username", $new_user_id);
                    
                    $message = "<div class='success'>Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a></div>";
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) { 
                    $message = "<div class='error'>Lỗi: Tên đăng nhập hoặc Email đã được sử dụng!</div>";
                } else {
                    $message = "<div class='error'>Lỗi hệ thống: " . $e->getMessage() . "</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <style> body { font-family: Arial; padding: 50px; } .box { max-width: 400px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; } input { width: 90%; padding: 8px; margin: 5px 0 15px 0; } button { padding: 10px 15px; background: #28a745; color: white; border: none; cursor: pointer; } .error { color: red; margin-bottom: 15px;} .success { color: green; margin-bottom: 15px;} </style>
</head>
<body>
    <div class="box">
        <h2>Đăng Ký Tài Khoản</h2>
        <?= $message ?>
        <form method="POST">
            <label>Tên đăng nhập:</label><br>
            <input type="text" name="username" required>
            
            <label>Email:</label><br>
            <input type="email" name="email" required>
            
            <label>Mật khẩu:</label><br>
            <input type="password" name="password" required>

            <label>Nhập lại mật khẩu:</label><br>
            <input type="password" name="confirm_password" required>
            
            <button type="submit">Đăng ký</button>
        </form>
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
</body>
</html>
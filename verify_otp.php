<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$message = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp_input = trim($_POST['otp']);

    // Kiểm tra OTP mới nhất, khớp email và chưa hết hạn (expires_at > NOW())
    $sql = "SELECT * FROM password_resets 
            WHERE email = ? AND otp_code = ? AND expires_at > NOW() 
            ORDER BY created_at DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $otp_input);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Xác thực thành công -> Đánh dấu vào session để được phép đổi pass
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit;
    } else {
        $message = "<div style='color:red;'>Mã OTP không đúng hoặc đã hết hạn!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận OTP</title>
    <style> body { font-family: Arial; padding: 50px; text-align: center; } .box { max-width: 400px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; } input { width: 80%; padding: 10px; font-size: 20px; text-align: center; letter-spacing: 5px; } button { margin-top: 15px; padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; } </style>
</head>
<body>
    <div class="box">
        <h2>Nhập mã OTP</h2>
        <p>Mã đã được gửi đến: <b><?= $email ?></b></p>
        <?= $message ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="XXXXXX" maxlength="6" required>
            <br>
            <button type="submit">Xác thực</button>
        </form>
    </div>
</body>
</html>
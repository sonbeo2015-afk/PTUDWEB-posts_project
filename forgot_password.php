<?php
session_start();
require_once 'db.php';

// Import các class của PHPMailer vào không gian tên
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Nạp thư viện PHPMailer (do Composer tải về)
require 'vendor/autoload.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Kiểm tra xem email có tồn tại không
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result) > 0) {
        // Tạo mã OTP 6 số
        $otp = rand(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Hết hạn sau 15p

        // Lưu vào bảng password_resets
        $sql_insert = "INSERT INTO password_resets (email, otp_code, expires_at) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "sss", $email, $otp, $expires_at);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            
            // ==========================================
            // THỰC THI GỬI EMAIL THẬT BẰNG PHPMAILER
            // ==========================================
            $mail = new PHPMailer(true);

            try {
                // 1. Cấu hình Server (Dùng Gmail SMTP)
                $mail->isSMTP();                                            
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                   
                $mail->Username   = 'sonbeo2015@gmail.com'; // ĐIỀN GMAIL CỦA BẠN VÀO ĐÂY
                $mail->Password   = 'lyam jbqn hmxw elrs'; // ĐIỀN MẬT KHẨU ỨNG DỤNG VÀO ĐÂY
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
                $mail->Port       = 587;                                    
                $mail->CharSet    = 'UTF-8'; // Hỗ trợ Tiếng Việt có dấu

                // 2. Thiết lập Người gửi & Người nhận
                $mail->setFrom('thay_bang_email_cua_ban@gmail.com', 'Hệ Thống Kiến Thức Web');
                $mail->addAddress($email); // Gửi đến email mà người dùng vừa nhập vào form

                // 3. Soạn nội dung Email
                $mail->isHTML(true);                                  
                $mail->Subject = 'Mã xác nhận khôi phục mật khẩu (OTP)';
                $mail->Body    = "
                    <h3>Chào bạn,</h3>
                    <p>Bạn đã yêu cầu khôi phục mật khẩu. Dưới đây là mã OTP của bạn:</p>
                    <h2 style='color:red;'>$otp</h2>
                    <p><i>Lưu ý: Mã này sẽ hết hạn sau 15 phút. Tuyệt đối không chia sẻ mã này cho bất kỳ ai!</i></p>
                ";

                // 4. Phát lệnh gửi
                $mail->send();
                
                // Hiển thị thông báo thành công và chuyển hướng sang trang nhập mã
                if ($mail->send()) {
                    // Lưu email vào session để dùng cho trang sau
                    $_SESSION['reset_email'] = $email; 
                    // Chuyển hướng ngay lập tức
                    header("Location: verify_otp.php");
                    exit;
                }

            } catch (Exception $e) {
                // Nếu gửi xịt, báo lỗi cụ thể để dễ debug
                $message = "<div class='error'>Không thể gửi email. Lỗi hệ thống: {$mail->ErrorInfo}</div>";
            }
        }
    } else {
        $message = "<div class='error'>Email này chưa được đăng ký trong hệ thống!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <style> body { font-family: Arial; padding: 50px; } .box { max-width: 400px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; } input { width: 90%; padding: 8px; margin: 5px 0 15px 0; } button { padding: 10px 15px; background: #dc3545; color: white; border: none; cursor: pointer; } .error { color: red; margin-bottom: 10px; } .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724; line-height: 1.5;} </style>
</head>
<body>
    <div class="box">
        <h2>Khôi Phục Mật Khẩu</h2>
        <?= $message ?>
        <form method="POST">
            <label>Nhập Email bạn đã đăng ký:</label><br>
            <input type="email" name="email" required placeholder="ví dụ: abc@gmail.com">
            <button type="submit">Gửi mã OTP</button>
        </form>
        <p><a href="login.php">Quay lại đăng nhập</a></p>
    </div>
</body>
</html>
<?php
session_start();
require_once 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// BẢO VỆ TUYỆT ĐỐI: Chỉ Admin (Role ID = 1) mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    die("<h2 style='color:red; text-align:center;'>CẢNH BÁO BẢO MẬT: TRUY CẬP TRÁI PHÉP!</h2>");
}

$message = '';

// ==========================================
// XỬ LÝ DUYỆT ĐƠN TÁC GIẢ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'handle_request') {
    $request_id = $_POST['request_id'];
    $req_user_id = $_POST['req_user_id'];
    $status_update = $_POST['status_update']; 

    $sql_email = "SELECT email, username FROM users WHERE id = ?";
    $stmt_email = mysqli_prepare($conn, $sql_email);
    mysqli_stmt_bind_param($stmt_email, "i", $req_user_id);
    mysqli_stmt_execute($stmt_email);
    $user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_email));
    $user_email = $user_info['email'];
    $user_name = $user_info['username'];

    $sql_req = "UPDATE author_requests SET status = ? WHERE id = ?";
    $stmt_req = mysqli_prepare($conn, $sql_req);
    mysqli_stmt_bind_param($stmt_req, "si", $status_update, $request_id);
    
    if (mysqli_stmt_execute($stmt_req)) {
        if ($status_update == 'approved') {
            mysqli_query($conn, "UPDATE users SET role_id = 2 WHERE id = $req_user_id");
            $mail_subject = "Chúc mừng! Đơn đăng ký Tác giả của bạn đã được duyệt";
            $mail_body = "<h3>Chào $user_name,</h3><p>Admin đã phê duyệt đơn của bạn. Bây giờ bạn đã là <b>Tác giả</b>.</p>";
        } else {
            $mail_subject = "Kết quả đơn đăng ký làm Tác giả";
            $mail_body = "<h3>Chào $user_name,</h3><p>Rất tiếc, đơn đăng ký của bạn chưa được phê duyệt lần này.</p>";
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sonbeo2015@gmail.com'; 
            $mail->Password   = 'lyam jbqn hmxw elrs'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('sonbeo2015@gmail.com', 'Admin Hệ Thống');
            $mail->addAddress($user_email);
            $mail->isHTML(true);
            $mail->Subject = $mail_subject;
            $mail->Body    = $mail_body;
            $mail->send();
            $message = "<div style='color:green; padding:10px; background:#d4edda; border-radius:5px;'>Đã xử lý đơn và gửi thông báo cho $user_email thành công!</div>";
        } catch (Exception $e) {
            $message = "<div style='color:orange;'>Cập nhật DB thành công nhưng lỗi gửi mail: {$mail->ErrorInfo}</div>";
        }
    }
}

// ==========================================
// TRUY VẤN DỮ LIỆU HIỂN THỊ
// ==========================================

// 1. Lấy danh sách ĐƠN ĐANG CHỜ DUYỆT (Dùng cho Section mới)
$sql_requests = "SELECT ar.*, u.username, u.email 
                 FROM author_requests ar 
                 JOIN users u ON ar.user_id = u.id 
                 WHERE ar.status = 'pending' 
                 ORDER BY ar.created_at DESC";
$result_requests = mysqli_query($conn, $sql_requests);

// 2. Lấy danh sách Users
$sql_users = "SELECT u.id, u.username, u.email, r.name AS role_name, u.role_id 
              FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.role_id ASC";
$result_users = mysqli_query($conn, $sql_users);

// 3. Lấy Logs
$sql_logs = "SELECT l.*, u.username FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50";
$result_logs = mysqli_query($conn, $sql_logs);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style> 
        body { font-family: Arial; background: #e9ecef; padding: 20px; } 
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 0 5px rgba(0,0,0,0.1); } 
        table { width: 100%; border-collapse: collapse; margin-top: 10px; } 
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; } 
        th { background: #343a40; color: white; } 
        .badge { background: #ffc107; color: #212529; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <a href="index.php">← Quay lại Trang chủ</a>
    <h1>Trang Quản Trị Hệ Thống</h1>
    <?= $message ?>

    <div class="box" style="border-top: 5px solid #ffc107;">
        <h2>📝 Đơn xin làm Tác giả đang chờ duyệt</h2>
        <?php if (mysqli_num_rows($result_requests) > 0): ?>
            <table>
                <tr>
                    <th>Ứng viên</th>
                    <th>Chủ đề mong muốn</th>
                    <th>Lý do đăng ký</th>
                    <th>Thời gian</th>
                    <th>Hành động</th>
                </tr>
                <?php while($req = mysqli_fetch_assoc($result_requests)): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($req['username']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($req['email']) ?></small>
                    </td>
                    <td><span class="badge"><?= htmlspecialchars($req['proposed_categories']) ?></span></td>
                    <td style="max-width: 300px; font-style: italic;">"<?= htmlspecialchars($req['reason']) ?>"</td>
                    <td><?= date('d/m/H:i', strtotime($req['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 5px;">
                            <input type="hidden" name="action" value="handle_request">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <input type="hidden" name="req_user_id" value="<?= $req['user_id'] ?>">
                            
                            <button type="submit" name="status_update" value="approved" class="btn-approve">Duyệt</button>
                            <button type="submit" name="status_update" value="rejected" class="btn-reject">Từ chối</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="color: #888;">Hiện tại không có đơn đăng ký nào đang chờ.</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>👥 Quản lý Phân quyền (Roles)</h2>
        <table>
            <tr>
                <th>ID</th> <th>Username</th> <th>Email</th> <th>Quyền hiện tại</th> <th>Hành động</th>
            </tr>
            <?php while($user = mysqli_fetch_assoc($result_users)): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><strong><?= strtoupper($user['role_name']) ?></strong></td>
                <td>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="target_user_id" value="<?= $user['id'] ?>">
                        <select name="new_role_id" style="padding: 5px;">
                            <option value="1" <?= $user['role_id']==1?'selected':'' ?>>Admin</option>
                            <option value="2" <?= $user['role_id']==2?'selected':'' ?>>Author</option>
                            <option value="3" <?= $user['role_id']==3?'selected':'' ?>>User</option>
                        </select>
                        <button type="submit" style="background: #17a2b8; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Đổi quyền</button>
                    </form>
                    <?php else: ?>
                        <small><em>(Tài khoản hiện tại)</em></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="box">
        <h2>📜 Nhật ký hoạt động (System Logs)</h2>
        <table style="font-size: 0.85em;">
            <tr>
                <th>Thời gian</th> <th>User</th> <th>Hành động</th> <th>Chi tiết</th> <th>IP</th>
            </tr>
            <?php while($log = mysqli_fetch_assoc($result_logs)): ?>
            <tr>
                <td><?= $log['created_at'] ?></td>
                <td><?= htmlspecialchars($log['username'] ?: 'Guest') ?></td>
                <td><strong><?= htmlspecialchars($log['action']) ?></strong></td>
                <td><?= htmlspecialchars($log['details']) ?></td>
                <td><?= htmlspecialchars($log['ip_address']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
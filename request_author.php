<?php
session_start();
require_once 'db.php';

// Chỉ cho phép User thường (role_id = 3) vào trang này
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] < 3) {
    die("<h2>Bạn không có quyền hoặc đã là tác giả rồi!</h2><a href='index.php'>Quay lại</a>");
}

$user_id = $_SESSION['user_id'];
$message = '';

// 1. Kiểm tra xem người này có đơn nào đang "pending" không
$sql_check = "SELECT id FROM author_requests WHERE user_id = ? AND status = 'pending'";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "i", $user_id);
mysqli_stmt_execute($stmt_check);
if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0) {
    $message = "<div class='info'>Đơn đăng ký của bạn đang được Admin chờ duyệt. Vui lòng kiên nhẫn nhé!</div>";
    $hide_form = true;
} else {
    $hide_form = false;
}

// 2. Xử lý khi User bấm gửi đơn
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$hide_form) {
    $reason = trim($_POST['reason']);
    // Nhận mảng các danh mục được check, nối lại thành 1 chuỗi (VD: "Lập trình, Thể thao")
    $categories_array = isset($_POST['categories']) ? $_POST['categories'] : [];
    $proposed_categories = implode(", ", $categories_array);

    if (empty($categories_array)) {
        $message = "<div class='error'>Vui lòng chọn ít nhất 1 chủ đề bạn muốn viết!</div>";
    } else {
        $sql_insert = "INSERT INTO author_requests (user_id, reason, proposed_categories) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "iss", $user_id, $reason, $proposed_categories);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            saveLog($conn, "AUTHOR_REQUEST", "Gửi yêu cầu nâng cấp Tác giả", $user_id);
            $message = "<div class='success'>Đã gửi yêu cầu thành công! Admin sẽ xem xét sớm cho bạn.</div>";
            $hide_form = true;
        }
    }
}

// Lấy danh sách Categories để hiển thị Checkbox (Loại trừ mục của Admin)
$sql_get_cats = "SELECT name FROM categories WHERE name != 'Quy định chung'";
$result_cats = mysqli_query($conn, $sql_get_cats);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký làm Tác giả</title>
    <style> body { font-family: Arial; background: #f4f4f4; padding: 20px; } .box { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); } textarea { width: 100%; padding: 10px; margin-bottom: 10px; box-sizing: border-box; } button { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; } .success { color: green; margin-bottom: 15px;} .error { color: red; margin-bottom: 15px;} .info { background: #e2e3e5; padding: 15px; border-radius: 5px; color: #383d41; } .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; } .checkbox-group label { background: #f8f9fa; padding: 8px 12px; border-radius: 5px; border: 1px solid #ddd; cursor: pointer; } </style>
</head>
<body>
    <div class="box">
        <a href="index.php">← Quay lại Trang chủ</a>
        <h2>Đăng Ký Trở Thành Tác Giả ✍️</h2>
        <p>Hãy cho chúng tôi biết bạn muốn đóng góp nội dung gì cho cộng đồng nhé!</p>
        
        <?= $message ?>

        <?php if (!$hide_form): ?>
        <form method="POST">
            <label><b>1. Bạn muốn viết về chủ đề nào? (Chọn nhiều)</b></label><br><br>
            <div class="checkbox-group">
                <?php while($cat = mysqli_fetch_assoc($result_cats)): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat['name']) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                <?php endwhile; ?>
            </div>

            <label><b>2. Tại sao bạn muốn trở thành tác giả? (Kinh nghiệm, mục tiêu...)</b></label>
            <textarea name="reason" rows="5" required placeholder="Mình là sinh viên IT, mình muốn chia sẻ kiến thức về lập trình web..."></textarea>
            
            <button type="submit">Gửi Yêu Cầu Đăng Ký</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
session_start();
require_once 'db.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// 2. Xử lý cập nhật thông tin khi bấm nút Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $birth_date = $_POST['birth_date'];
    $bio = trim($_POST['bio']);
    $avatar_name = $_POST['current_avatar']; // Giữ tên ảnh cũ mặc định

    // --- XỬ LÝ UPLOAD ẢNH (Nếu có chọn file mới) ---
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "uploads/";
        $file_extension = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        
        // Tạo tên file mới để không bị trùng (ví dụ: avatar_1_161875.jpg)
        $new_filename = "avatar_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        // Kiểm tra định dạng ảnh (chỉ cho phép jpg, png, jpeg, gif)
        $allow_types = array('jpg', 'png', 'jpeg', 'gif');
        if (in_array(strtolower($file_extension), $allow_types)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_name = $new_filename; // Cập nhật tên file mới để lưu vào DB
            } else {
                $message = "<div style='color:red;'>Lỗi: Không thể tải ảnh lên thư mục uploads!</div>";
            }
        } else {
            $message = "<div style='color:red;'>Lỗi: Chỉ chấp nhận định dạng ảnh JPG, PNG, JPEG, GIF.</div>";
        }
    }

    // --- CẬP NHẬT DATABASE ---
    $sql_update = "UPDATE profiles SET full_name = ?, birth_date = ?, bio = ?, avatar = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $birth_date, $bio, $avatar_name, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        saveLog($conn, "UPDATE_PROFILE", "Cập nhật thông tin cá nhân", $user_id);
        $message = "<div style='color:green;'>Cập nhật hồ sơ thành công!</div>";
    }
    mysqli_stmt_close($stmt);
}

// 3. Lấy thông tin hiện tại để hiển thị lên Form
$sql_get = "SELECT p.*, u.username, u.email 
            FROM profiles p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ?";
$stmt_get = mysqli_prepare($conn, $sql_get);
mysqli_stmt_bind_param($stmt_get, "i", $user_id);
mysqli_stmt_execute($stmt_get);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ của tôi</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .avatar-img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff; }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .nav { margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <a href="index.php">← Quay lại Trang chủ</a>
    </div>

    <h2>Hồ sơ cá nhân</h2>
    <?= $message ?>

    <form action="profile.php" method="POST" enctype="multipart/form-data">
        <div style="text-align: center;">
            <img src="uploads/<?= $profile['avatar'] ?>" class="avatar-img" alt="Avatar"><br><br>
            <label>Thay đổi ảnh đại diện:</label><br>
            <input type="file" name="avatar" accept="image/*">
            <input type="hidden" name="current_avatar" value="<?= $profile['avatar'] ?>">
        </div>

        <label>Tên đăng nhập (không thể sửa):</label>
        <input type="text" value="<?= $profile['username'] ?>" disabled>

        <label>Email:</label>
        <input type="text" value="<?= $profile['email'] ?>" disabled>

        <label>Họ và tên thật:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name']) ?>" placeholder="Nhập họ tên của bạn">

        <label>Ngày sinh:</label>
        <input type="date" name="birth_date" value="<?= $profile['birth_date'] ?>">

        <label>Giới thiệu bản thân:</label>
        <textarea name="bio" rows="4" placeholder="Viết vài dòng về bạn..."><?= htmlspecialchars($profile['bio']) ?></textarea>

        <button type="submit">Lưu thay đổi</button>
    </form>
</div>

</body>
</html>
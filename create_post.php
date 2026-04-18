<?php
session_start();
require_once 'db.php';

// BẢO VỆ: Chỉ Admin (1) và Author (2) được viết bài
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] == 3) {
    die("<h2>Bạn không có quyền truy cập trang này.</h2><a href='index.php'>Quay lại</a>");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category_id'];
    $author_id = $_SESSION['user_id'];

    $sql = "INSERT INTO posts (title, content, author_id, category_id) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssii", $title, $content, $author_id, $category_id);
        if (mysqli_stmt_execute($stmt)) {
            $post_id = mysqli_insert_id($conn);
            saveLog($conn, "CREATE_POST", "Đăng bài: $title", $author_id);
            header("Location: post_detail.php?id=$post_id");
            exit;
        } else {
            $message = "<p style='color:red;'>Lỗi: " . mysqli_error($conn) . "</p>";
        }
    }
}
// Lấy danh mục cho thẻ <select> dựa trên Quyền hạn
if ($_SESSION['role_id'] == 1) {
    // Admin thì lấy tất cả các danh mục
    $sql_get_cats = "SELECT * FROM categories";
} else {
    // Author (hoặc khác) thì loại bỏ mục "Quy định chung"
    $sql_get_cats = "SELECT * FROM categories WHERE name != 'Quy định chung'";
}
$result_cats = mysqli_query($conn, $sql_get_cats);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Viết Bài</title>
    <style> body { font-family: Arial; padding: 20px; background: #f4f4f4; } .box { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 5px; } input, select, textarea { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; } button { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; } </style>
</head>
<body>
    <div class="box">
        <h2>✍️ Viết Bài Mới</h2>
        <a href="index.php">← Quay lại trang chủ</a>
        <?= $message ?>
        <form method="POST">
            <input type="text" name="title" placeholder="Tiêu đề bài viết..." required>
            <select name="category_id" required>
                <option value="">-- Chọn danh mục --</option>
                <?php while($cat = mysqli_fetch_assoc($result_cats)): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <textarea name="content" rows="15" placeholder="Nội dung bài viết..." required></textarea>
            <button type="submit">Đăng bài</button>
        </form>
    </div>
</body>
</html>
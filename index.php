<?php
session_start();
require_once 'db.php';

// Xử lý lọc theo danh mục (nếu người dùng bấm vào menu)
$category_filter = "";
if (isset($_GET['cat_id']) && is_numeric($_GET['cat_id'])) {
    $cat_id = $_GET['cat_id'];
    $category_filter = "WHERE p.category_id = $cat_id";
}

// Lấy danh sách danh mục để làm Menu
$sql_cats = "SELECT * FROM categories";
$result_cats = mysqli_query($conn, $sql_cats);

// Lấy danh sách bài viết (Kết nối 3 bảng: posts, users, categories)
$sql_posts = "SELECT p.id, p.title, p.content, p.created_at, u.username AS author, c.name AS category 
              FROM posts p 
              LEFT JOIN users u ON p.author_id = u.id 
              LEFT JOIN categories c ON p.category_id = c.id 
              $category_filter 
              ORDER BY p.created_at DESC";
$result_posts = mysqli_query($conn, $sql_posts);

// Hàm bổ trợ để cắt chữ (Tránh cắt giữa chừng một từ)
function truncateString($str, $chars = 150) {
    if (mb_strlen($str) <= $chars) return $str;
    $shortened = mb_substr($str, 0, $chars);
    return mb_substr($shortened, 0, mb_strrpos($shortened, ' ')) . '...';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Chủ - Mini CMS</title>
    <style> 
        body { font-family: Arial; margin: 0; background: #f4f4f4; }
        .header { background: #343a40; color: white; padding: 15px; text-align: center; }
        .header a { color: #ffc107; text-decoration: none; font-weight: bold; margin: 0 10px; }
        .container { display: flex; padding: 20px; max-width: 1200px; margin: auto; }
        .sidebar { width: 25%; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        .sidebar a { display: block; padding: 10px; text-decoration: none; color: #333; border-bottom: 1px solid #ddd; }
        .sidebar a:hover { background: #f8f9fa; color: #007bff; }
        .main-content { width: 75%; padding-left: 20px; }
        .post-card { background: white; padding: 15px; margin-bottom: 15px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        .meta { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="header">
    <h1>Hệ Thống Quản Lý Kiến Thức</h1>
    <?php if (isset($_SESSION['user_id'])): ?>
        Chào, <?= htmlspecialchars($_SESSION['username']) ?>! (Quyền: <?= $_SESSION['role_id'] ?>) | 
        <a href="profile.php">Hồ sơ</a> |
        
        <?php if ($_SESSION['role_id'] == 3): ?> 
            <a href="request_author.php" style="color: #28a745; border: 1px solid #28a745; padding: 2px 5px; border-radius: 3px;">🚀 Đăng ký làm Tác giả</a> |
        <?php endif; ?>

        <?php if ($_SESSION['role_id'] <= 2): ?> <a href="create_post.php">✍️ Viết bài</a> | <?php endif; ?>
        <?php if ($_SESSION['role_id'] == 1): ?> <a href="admin.php">⚙️ Quản trị</a> | <?php endif; ?>
        <a href="logout.php" style="color: #dc3545;">Đăng xuất</a>
    <?php else: ?>
        <a href="login.php">Đăng nhập</a> | <a href="register.php">Đăng ký</a>
    <?php endif; ?>
</div>

    <div class="container">
        <div class="sidebar">
            <h3>Danh mục</h3>
            <a href="index.php">Tất cả bài viết</a>
            <?php while($cat = mysqli_fetch_assoc($result_cats)): ?>
                <a href="index.php?cat_id=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
            <?php endwhile; ?>
        </div>

        <div class="main-content">
    <?php if (mysqli_num_rows($result_posts) > 0): ?>
        <?php while($post = mysqli_fetch_assoc($result_posts)): ?>
            <div class="post-card">
                <h3><a href="post_detail.php?id=<?= $post['id'] ?>" style="text-decoration:none; color:#007bff;"><?= htmlspecialchars($post['title']) ?></a></h3>
                
                <p style="color: #444; font-size: 0.95em; line-height: 1.5;">
                    <?= htmlspecialchars(truncateString($post['content'], 200)) ?>
                </p>

                <p class="meta">
                    Chuyên mục: <b><?= $post['category'] ?></b> | 
                    Tác giả: <b><?= $post['author'] ?: 'Ẩn danh' ?></b> | 
                    Đăng lúc: <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                </p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Chưa có bài viết nào trong mục này.</p>
    <?php endif; ?>
</div>
    </div>
</body>
</html>
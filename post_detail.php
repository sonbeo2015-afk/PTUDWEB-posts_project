<?php
session_start();
require_once 'db.php';

if (!isset($_GET['id'])) { die("Không tìm thấy bài viết!"); }
$post_id = intval($_GET['id']);

// 1. Xử lý khi người dùng gửi bình luận
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['comment_content']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content)) {
        $sql_cmt = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt_cmt = mysqli_prepare($conn, $sql_cmt);
        mysqli_stmt_bind_param($stmt_cmt, "iis", $post_id, $user_id, $content);
        mysqli_stmt_execute($stmt_cmt);
        // Load lại trang để hiện bình luận mới
        header("Location: post_detail.php?id=$post_id");
        exit;
    }
}

// 2. Lấy dữ liệu bài viết
$sql_post = "SELECT p.*, u.username, c.name AS category 
             FROM posts p 
             LEFT JOIN users u ON p.author_id = u.id 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $sql_post);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$post) { die("Bài viết đã bị xóa hoặc không tồn tại."); }

// 3. Lấy danh sách bình luận của bài viết này
$sql_comments = "SELECT c.content, c.created_at, u.username, p.avatar 
                 FROM comments c 
                 JOIN users u ON c.user_id = u.id 
                 LEFT JOIN profiles p ON u.id = p.user_id 
                 WHERE c.post_id = ? ORDER BY c.created_at DESC";
$stmt_cmts = mysqli_prepare($conn, $sql_comments);
mysqli_stmt_bind_param($stmt_cmts, "i", $post_id);
mysqli_stmt_execute($stmt_cmts);
$comments = mysqli_stmt_get_result($stmt_cmts);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <style> body { font-family: Arial; background: #f4f4f4; padding: 20px; } .box { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); } .meta { color: gray; } .content { font-size: 1.1em; line-height: 1.6; margin-top: 20px; white-space: pre-wrap; } .comment-box { border-top: 2px solid #eee; margin-top: 40px; padding-top: 20px; } .comment { border-bottom: 1px dashed #ccc; padding: 10px 0; display: flex; gap: 15px;} .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; } textarea { width: 100%; padding: 10px; margin-bottom: 10px; } button { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; } </style>
</head>
<body>
    <div class="box">
        <a href="index.php">← Quay lại</a>
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <p class="meta">Chuyên mục: <?= $post['category'] ?> | Tác giả: <?= $post['username'] ?: 'Ẩn danh' ?> | <?= $post['created_at'] ?></p>
        
        <div class="content"><?= htmlspecialchars($post['content']) ?></div>

        <div class="comment-box">
            <h3>Bình luận (<?= mysqli_num_rows($comments) ?>)</h3>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST">
                    <textarea name="comment_content" rows="3" placeholder="Viết bình luận của bạn..." required></textarea>
                    <button type="submit">Gửi bình luận</button>
                </form>
            <?php else: ?>
                <p><i>Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</i></p>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <?php while($cmt = mysqli_fetch_assoc($comments)): ?>
                    <div class="comment">
                        <img src="uploads/<?= $cmt['avatar'] ?: 'default_avatar.jpg' ?>" class="avatar">
                        <div>
                            <b><?= htmlspecialchars($cmt['username']) ?></b> <small style="color:gray;"><?= $cmt['created_at'] ?></small>
                            <p style="margin: 5px 0 0 0;"><?= htmlspecialchars($cmt['content']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>
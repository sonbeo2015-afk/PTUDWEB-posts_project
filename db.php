<?php
// ==========================================
// THÔNG TIN KẾT NỐI DATABASE ONLINE (CLEVER CLOUD)
// ==========================================
$host     = 'bo1flxuzjxmxstu9lias-mysql.services.clever-cloud.com';
$dbname   = 'bo1flxuzjxmxstu9lias';
$username = 'ujxenpvjbkwly8cz';
$password = 'iABslZp5GmDtlfYV9xB0';
$port     = 3306; 

$conn = mysqli_connect($host, $username, $password, $dbname, $port);

if (!$conn) {
    die("Không thể kết nối tới Cơ sở dữ liệu: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

function saveLog($conn, $action, $details = null, $user_id = null) {
    // Lấy địa chỉ IP thực của người dùng đang thao tác
    $ip_address = $_SERVER['REMOTE_ADDR']; 
    
    // Dùng Prepared Statement của MySQLi để chống SQL Injection
    $sql = "INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        // "isss" quy định kiểu dữ liệu: i (integer) cho user_id, s (string) cho 3 tham số còn lại
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $action, $details, $ip_address);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Ghi âm thầm vào log lỗi của server nếu lệnh SQL bị sai
        error_log("Lỗi ghi System Log: " . mysqli_error($conn));
    }
}
?>
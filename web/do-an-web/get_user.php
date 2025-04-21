<?php
session_start();
require_once 'db/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Không có quyền truy cập']);
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Truy vấn thông tin người dùng
    $sql = "SELECT id, name, email, phone, address, role, is_active FROM users WHERE id = '$id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Trả về dữ liệu dạng JSON
        header('Content-Type: application/json');
        echo json_encode($user, JSON_UNESCAPED_UNICODE);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Không tìm thấy người dùng']);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Thiếu tham số ID']);
}
?> 
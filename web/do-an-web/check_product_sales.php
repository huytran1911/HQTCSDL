<?php
session_start();
require_once 'db/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing product ID']);
    exit();
}

$id = mysqli_real_escape_string($conn, $_POST['id']);

// Kiểm tra xem sản phẩm đã được bán chưa
$sql = "SELECT COUNT(*) as sold_count FROM order_items WHERE product_id = '$id'";
$result = mysqli_query($conn, $sql);
$sold_count = mysqli_fetch_assoc($result)['sold_count'];

header('Content-Type: application/json');
echo json_encode([
    'sold' => $sold_count > 0,
    'sold_count' => $sold_count
]); 
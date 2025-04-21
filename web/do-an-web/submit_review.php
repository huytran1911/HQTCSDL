<?php
session_start();
require_once 'db/database.php';


if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}


$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';
$user_id = $_SESSION['user']['id'];


if ($product_id <= 0 || $rating <= 0 || $rating > 5 || empty($review)) {
    header("Location: product.php?id=$product_id&review_error=1");
    exit();
}


$check_product = mysqli_query($conn, "SELECT id FROM products WHERE id = $product_id AND is_active = 1");
if (mysqli_num_rows($check_product) == 0) {
    header("Location: index.php");
    exit();
}


$check_review = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id");


$review = mysqli_real_escape_string($conn, $review);

if (mysqli_num_rows($check_review) > 0) {
  
    $update_query = "UPDATE reviews 
                    SET rating = $rating,
                        review = '$review',
                        created_at = CURRENT_TIMESTAMP 
                    WHERE user_id = $user_id 
                    AND product_id = $product_id";
    $success = mysqli_query($conn, $update_query);
} else {
   
    $insert_query = "INSERT INTO reviews (product_id, user_id, rating, review) 
                    VALUES ($product_id, $user_id, $rating, '$review')";
    $success = mysqli_query($conn, $insert_query);
}

if ($success) {
   
    header("Location: product.php?id=$product_id&review_success=1");
} else {
    header("Location: product.php?id=$product_id&review_error=1");
} 
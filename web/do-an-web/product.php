<?php
session_start();
require_once 'db/database.php';


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];


$product_query = "SELECT p.*, c.name as category_name, 
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN reviews r ON p.id = r.product_id
                 WHERE p.id = $product_id AND p.is_active = 1
                 GROUP BY p.id";
$product_result = mysqli_query($conn, $product_query);

// Check if product exists
if (mysqli_num_rows($product_result) == 0) {
    header("Location: index.php");
    exit();
}

$product = mysqli_fetch_assoc($product_result);

// Get products in same category (related products)
$related_query = "SELECT * FROM products 
                 WHERE category_id = {$product['category_id']} 
                 AND id != $product_id 
                 AND is_active = 1 
                 LIMIT 3";
$related_result = mysqli_query($conn, $related_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - Laptop Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="hello.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            padding-top: 56px;
        }
        .container {
            padding: 20px;
        }
        .product-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #0056b3;
        }
        .product-image {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }
        .description {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #666;
        }

        /* Thông số kỹ thuật */
        .specs-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .specs-section h3 {
            color: #0056b3;
            font-size: 1.4rem;
            margin-bottom: 20px;
        }
        .specs-table {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .spec-row {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            padding: 8px 0;
        }
        .spec-label {
            flex: 0 0 150px;
            font-weight: 600;
            color: #495057;
        }
        .spec-value {
            flex: 1;
            color: #666;
        }

        .btn-add-to-cart {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            font-size: 1.1rem;
        }
        .category-badge {
            background: #f8f9fa;
            color: #0d6efd;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .category-badge:hover {
            background: rgba(13, 110, 253, 0.2);
            color: #0d6efd;
        }
        .category-badge i {
            font-size: 0.8rem;
        }
        .stock-info {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .in-stock {
            color: #28a745;
        }
        .out-of-stock {
            color: #dc3545;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .quantity-control .btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-control input {
            width: 60px;
            text-align: center;
            margin: 0 10px;
        }

        /* Review Styles */
        .reviews-section {
            margin-top: 50px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .review-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating input {
            display: none;
        }

        .rating label {
            cursor: pointer;
            font-size: 30px;
            color: #ddd;
            margin: 0 2px;
        }

        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            color: #ffc107;
        }

        .review-item {
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .review-item .rating {
            font-size: 20px;
            color: #ffc107;
        }

        .review-item .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .review-item .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .review-item .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .review-item .review-text {
            color: #333;
            line-height: 1.6;
        }

        .product-header,
        .product-header::before {
            display: none;
        }

        .breadcrumb {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            margin-bottom: 20px;
            display: inline-flex;
        }

        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb-item.active {
            color: #fff;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.5);
        }

        .breadcrumb-item a:hover {
            color: #fff;
        }

        .user-profile {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .navbar {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link i {
            margin-right: 5px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form .form-control {
            border-radius: 20px;
            border: none;
            padding: 8px 20px;
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .search-form .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .search-form .btn {
            border-radius: 20px;
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
        }

        .search-form .btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .cart-count {
            position: relative;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
        }

        /* Điều chỉnh margin cho container chính */
        .main-container {
            margin-top: 20px;
            background: transparent;
            padding: 0;
        }

        /* Breadcrumb styles */
        .page-breadcrumb {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: inline-flex;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .page-breadcrumb .breadcrumb-item a {
            color: #0d6efd;
            text-decoration: none;
        }

        .page-breadcrumb .breadcrumb-item.active {
            color: #495057;
        }

        .page-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            color: #6c757d;
        }

        .page-breadcrumb .breadcrumb-item a:hover {
            color: #0a58ca;
        }

        .product-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .related-products {
            margin-top: 50px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .product-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .product-thumbnail {
            height: 200px;
            object-fit: contain;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .product-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 500;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .product-card .card-text {
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        .product-card .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        .col-md-4 {
            padding: 15px;
        }
    </style>
</head>
<body>
   
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-laptop"></i> Cửa hàng Laptop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="bi bi-grid"></i> Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> Giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><i class="bi bi-envelope"></i> Liên hệ</a>
                    </li>
                </ul>
                
                <form class="search-form d-flex me-3" action="search.php" method="GET">
                    <input class="form-control" type="search" placeholder="Tìm kiếm sản phẩm..." name="q">
                    <button class="btn" type="submit"><i class="bi bi-search"></i></button>
                </form>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3"></i>
                            <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if(isset($_SESSION['user'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user']['name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box"></i> Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb page-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $product['name']; ?></li>
            </ol>
        </nav>

        <div class="product-container">
            <div class="row">
                <div class="col-md-6">
                    <img src="images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image img-fluid">
                </div>
                <div class="col-md-6">
                    <h1 class="product-title"><?php echo $product['name']; ?></h1>
                    <span class="category-badge">
                        <i class="bi bi-tag"></i> <?php echo $product['category_name']; ?>
                    </span>
                    <div class="product-price">
                        <?php echo number_format($product['price'], 0, ',', '.'); ?> đ
                    </div>
                    
                    <div class="stock-info">
                        <?php if ($product['stock'] > 0): ?>
                            <div class="product-stock text-success mb-3">
                                <i class="bi bi-check-circle"></i> Còn hàng
                            </div>
                        <?php else: ?>
                            <div class="product-stock text-danger mb-3">
                                <i class="bi bi-exclamation-circle"></i> Hết hàng
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="description mb-4">
                        <?php echo nl2br($product['description']); ?>
                    </div>

                    <!-- Thông số kỹ thuật -->
                    <div class="specs-section mb-4">
                        <h3 class="mb-3">Thông số kỹ thuật</h3>
                        <div class="specs-table">
                            <?php
                            if (!empty($product['specs'])) {
                                $specs = json_decode($product['specs'], true);
                                if ($specs && is_array($specs)) {
                                    foreach ($specs as $key => $value) {
                                        echo '<div class="spec-row">';
                                        echo '<div class="spec-label">' . htmlspecialchars($key) . '</div>';
                                        echo '<div class="spec-value">' . htmlspecialchars($value) . '</div>';
                                        echo '</div>';
                                    }
                                }
                            } else {
                                echo '<div class="alert alert-info">Chưa có thông số kỹ thuật cho sản phẩm này</div>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if ($product['stock'] > 0): ?>
                        <form action="cart_process.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <label for="quantity" class="form-label">Số lượng:</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                </div>
                                <div class="flex-fill d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary w-100 mb-3" disabled>
                            <i class="bi bi-exclamation-circle"></i> Hết hàng
                        </button>
                    <?php endif; ?>

                    <!-- Product Reviews -->
                    <div class="reviews-section mt-5">
                        <h3 class="mb-4">Đánh giá từ khách hàng</h3>
                        
                        <?php if (isset($_GET['review_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Cảm ơn bạn đã đánh giá sản phẩm!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['review_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Review Form -->
                        <?php if (isset($_SESSION['user'])): ?>
                        <div class="review-form mb-4">
                            <form action="submit_review.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Đánh giá của bạn</label>
                                    <div class="rating mb-2">
                                        <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                                        <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                                        <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                                        <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                                        <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="review" class="form-label">Nhận xét của bạn</label>
                                    <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            Vui lòng <a href="login.php">đăng nhập</a> để viết đánh giá
                        </div>
                        <?php endif; ?>

                        
                        <div class="reviews-list">
                            <?php
                            
                            $reviews_query = "SELECT r.*, u.name as user_name 
                                            FROM reviews r 
                                            JOIN users u ON r.user_id = u.id 
                                            WHERE r.product_id = $product_id 
                                            ORDER BY r.created_at DESC";
                            $reviews_result = mysqli_query($conn, $reviews_query);

                            if (mysqli_num_rows($reviews_result) > 0):
                                while ($review = mysqli_fetch_assoc($reviews_result)):
                            ?>
                                <div class="review-item card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="user-avatar rounded-circle me-2 d-flex align-items-center justify-content-center bg-primary text-white" 
                                                 style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="rating text-warning mb-2">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo ($i <= $review['rating']) ? '★' : '☆';
                                            }
                                            ?>
                                        </div>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                    </div>
                                </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <div class="alert alert-info">
                                    Chưa có đánh giá nào cho sản phẩm này
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <?php if (mysqli_num_rows($related_result) > 0): ?>
            <div class="related-products mt-5">
                <h3 class="mb-4">Sản phẩm liên quan</h3>
                <div class="row">
                    <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                        <div class="col-md-4">
                            <div class="card product-card">
                                <img src="images/<?php echo $related['image']; ?>" class="card-img-top product-thumbnail" alt="<?php echo $related['name']; ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $related['name']; ?></h5>
                                    <p class="card-text text-truncate"><?php echo $related['description']; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price"><?php echo number_format($related['price'], 0, ',', '.'); ?> đ</span>
                                        <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-outline-primary">Chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Thông tin liên hệ</h5>
                    <p><i class="bi bi-geo-alt"></i> 123 Đường ABC, Quận X, TP.HCM</p>
                    <p><i class="bi bi-telephone"></i> 0123 456 789</p>
                    <p><i class="bi bi-envelope"></i> info@laptopshop.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Liên kết nhanh</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Trang chủ</a></li>
                        <li><a href="#" class="text-white">Sản phẩm</a></li>
                        <li><a href="#" class="text-white">Giới thiệu</a></li>
                        <li><a href="#" class="text-white">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Cửa hàng Laptop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function decreaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            if (quantityInput.value > 1) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
            }
        }
        
        function increaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const maxStock = <?php echo $product['stock']; ?>;
            const currentValue = parseInt(quantityInput.value);
            
            if (currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
            }
        }
    </script>
</body>
</html> 
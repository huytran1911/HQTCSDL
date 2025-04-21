<?php
session_start();
require_once 'db/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để xem lịch sử mua hàng';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Get user's orders with pagination
$orders_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $orders_per_page;

// Count total orders
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_orders = $count_row['total'];
$total_pages = ceil($total_orders / $orders_per_page);

// Get orders for current page
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $offset, $orders_per_page";
$orders_result = mysqli_query($conn, $orders_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử mua hàng</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="hello.css" rel="stylesheet">
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-laptop"></i> Cửa hàng Laptop
            </a>
            <button class="navbar-toggler" type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" 
                    aria-expanded="false" 
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-laptop"></i> Sản phẩm</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-info-circle"></i> Giới thiệu</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-telephone"></i> Liên hệ</a></li>
                    <?php if (isset($_SESSION['user'])) { ?>
                        <li class="nav-item"><a class="nav-link active" href="history.php"><i class="bi bi-clock-history"></i> Lịch sử mua hàng</a></li>
                    <?php } ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <span id="user-email">
                                <?php echo isset($_SESSION['user']) ? $_SESSION['user']['name'] : 'Chưa đăng nhập'; ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if (!isset($_SESSION['user'])) { ?>
                                <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Đăng Nhập</a></li>
                                <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus"></i> Đăng Ký</a></li>
                            <?php } else { ?>
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3"></i> 
                            <span id="cart-count">
                                <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container mt-5 pt-5">
        <h1 class="my-4"><i class="bi bi-clock-history"></i> Lịch sử mua hàng</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($orders_result) == 0): ?>
            <div class="alert alert-info">
                <h4 class="text-center">Bạn chưa có đơn hàng nào</h4>
                <p class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-cart-plus"></i> Mua sắm ngay
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Danh sách đơn hàng của bạn</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Ngày đặt</th>
                                    <th>Trạng thái</th>
                                    <th>Tổng tiền</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($order['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning text-dark';
                                                    $status_text = 'Chờ xác nhận';
                                                    break;
                                                case 'confirmed':
                                                    $status_class = 'bg-primary text-white';
                                                    $status_text = 'Đã xác nhận';
                                                    break;
                                                case 'delivered':
                                                    $status_class = 'bg-success text-white';
                                                    $status_text = 'Đã giao hàng';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger text-white';
                                                    $status_text = 'Đã hủy';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> đ</td>
                                        <td>
                                            <a href="order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mb-4">
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-cart-plus"></i> Tiếp tục mua sắm
                </a>
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
</body>
</html> 
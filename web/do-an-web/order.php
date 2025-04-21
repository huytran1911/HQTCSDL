<?php
session_start();
require_once 'db/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để xem đơn hàng';
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user']['id'];

// Get order details
$order_query = "SELECT o.*, u.name as user_name, u.email as user_email,
                pd.card_number, pd.card_holder 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN payment_details pd ON o.id = pd.order_id
                WHERE o.id = $order_id AND o.user_id = $user_id";
$order_result = mysqli_query($conn, $order_query);

// Check if order exists and belongs to the user
if (mysqli_num_rows($order_result) == 0) {
    $_SESSION['error'] = 'Đơn hàng không tồn tại hoặc bạn không có quyền xem';
    header('Location: history.php');
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, p.name as product_name, p.image as product_image 
               FROM order_items oi
               JOIN products p ON oi.product_id = p.id
               WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);

$order_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="hello.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top no-print">
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
                        <li class="nav-item"><a class="nav-link" href="history.php"><i class="bi bi-clock-history"></i> Lịch sử mua hàng</a></li>
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
                            <span id="cart-count">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container mt-5 pt-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-receipt"></i> Chi tiết đơn hàng #<?php echo $order_id; ?></h1>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-outline-primary" id="btnPrint">
                    <i class="bi bi-printer"></i> In hóa đơn
                </button>
                <a href="history.php" class="btn btn-outline-secondary" id="btnBack">
                    <i class="bi bi-arrow-left"></i> Quay lại lịch sử
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Thông tin khách hàng</h5>
                        <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
                        <p><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Thông tin đơn hàng</h5>
                        <p><strong>Mã đơn hàng:</strong> #<?php echo $order_id; ?></p>
                        <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <?php 
                            $status_text = '';
                            
                            switch ($order['status']) {
                                case 'pending':
                                    $status_text = 'Chờ xác nhận';
                                    break;
                                case 'confirmed':
                                    $status_text = 'Đã xác nhận';
                                    break;
                                case 'delivered':
                                    $status_text = 'Đã giao thành công';
                                    break;
                                case 'cancelled':
                                    $status_text = 'Đã hủy';
                                    break;
                                default:
                                    $status_text = 'Chờ xác nhận';
                            }
                            ?>
                            <span class="text-warning"><?php echo $status_text; ?></span>
                        </p>
                        <p><strong>Phương thức thanh toán:</strong></p>
                        <?php if ($order['payment_method'] == 'credit_card'): ?>
                            <div class="ms-3">
                                <p>Thanh toán bằng thẻ tín dụng/ghi nợ</p>
                                <?php if ($order['card_number']): ?>
                                    <p>Số thẻ: XXXX XXXX XXXX <?php echo htmlspecialchars($order['card_number']); ?></p>
                                    <p>Tên chủ thẻ: <?php echo htmlspecialchars($order['card_holder']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($order['payment_method'] == 'bank_transfer'): ?>
                            <div class="ms-3">
                                <p>Chuyển khoản ngân hàng</p>
                                <p>Ngân hàng: Vietcombank</p>
                                <p>Số tài khoản: 1234567890</p>
                                <p>Chủ tài khoản: CÔNG TY TNHH LAPTOP</p>
                            </div>
                        <?php elseif ($order['payment_method'] == 'momo'): ?>
                            <div class="ms-3">
                                <p>Ví điện tử MoMo</p>
                                <p>Số điện thoại: 0987654321</p>
                                <p>Tên tài khoản: LAPTOP SHOP</p>
                            </div>
                        <?php else: ?>
                            <div class="ms-3">
                                <p>Thanh toán khi nhận hàng (COD)</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h6 class="mt-4">Chi tiết sản phẩm</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="images/<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-thumbnail me-3" style="width: 60px;">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row justify-content-end mt-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between">
                            <span>Tạm tính:</span>
                            <span><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> đ</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Phí vận chuyển:</span>
                            <span>Miễn phí</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Tổng cộng:</span>
                            <span><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> đ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mb-4 no-print">
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-cart-plus"></i> Tiếp tục mua sắm
            </a>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-4 no-print">
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
        // Update cart count
        document.addEventListener("DOMContentLoaded", function() {
            // Get cart count from session
            const cartCount = <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>;
            document.getElementById('cart-count').textContent = cartCount;
        });
    </script>
</body>
</html> 
<?php
session_start();
require_once 'db/database.php';


$total_price = 0;
$removed_products = [];

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
  
    $updated_cart = [];
    
    foreach ($_SESSION['cart'] as $key => $item) {
       
        $product_id = $item['product_id'];
        $check_sql = "SELECT id, name, is_active FROM products WHERE id = $product_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $product = mysqli_fetch_assoc($check_result);
            
            if ($product['is_active'] == 1) {
                
                $updated_cart[$key] = $item;
                $total_price += $item['price'] * $item['quantity'];
            } else {
            
                $removed_products[] = $item['name'];
            }
        } else {
           
            $removed_products[] = $item['name'];
        }
    }
    
  
    $_SESSION['cart'] = $updated_cart;
    
   
    if (!empty($removed_products)) {
        $_SESSION['note'] = "Một số sản phẩm đã bị xóa khỏi giỏ hàng do không còn hiển thị: " . implode(", ", $removed_products);
    }
}


$user_address = '';
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $user_query = "SELECT address FROM users WHERE id = $user_id";
    $user_result = mysqli_query($conn, $user_query);
    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_data = mysqli_fetch_assoc($user_result);
        $user_address = $user_data['address'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng</title>
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
                        <a class="nav-link active" href="cart.php">
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
        <h1 class="my-4"><i class="bi bi-cart3"></i> Giỏ hàng của bạn</h1>
        
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
        
        <?php if (isset($_SESSION['note'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['note']; unset($_SESSION['note']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                <h4 class="text-center">Giỏ hàng của bạn đang trống</h4>
                <p class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Sản phẩm trong giỏ hàng</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Sản phẩm</th>
                                            <th scope="col">Giá</th>
                                            <th scope="col">Số lượng</th>
                                            <th scope="col">Thành tiền</th>
                                            <th scope="col">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="images/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail me-3" style="width: 80px;">
                                                        <a href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo $item['name']; ?></a>
                                                    </div>
                                                </td>
                                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <form action="cart_process.php" method="POST" class="d-flex align-items-center">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="cart_key" value="<?php echo $key; ?>">
                                                        <input type="number" class="form-control form-control-sm" style="width: 70px;" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" onchange="this.form.submit()">
                                                    </form>
                                                </td>
                                                <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <form action="cart_process.php" method="POST">
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="cart_key" value="<?php echo $key; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Tiếp tục mua sắm
                                </a>
                                <form action="cart_process.php" method="POST">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')">
                                        <i class="bi bi-trash"></i> Xóa giỏ hàng
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Tóm tắt đơn hàng</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tạm tính:</span>
                                <span><?php echo number_format($total_price, 0, ',', '.'); ?> đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Phí vận chuyển:</span>
                                <span>Miễn phí</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3 fw-bold">
                                <span>Tổng cộng:</span>
                                <span><?php echo number_format($total_price, 0, ',', '.'); ?> đ</span>
                            </div>
                            
                            <?php if (isset($_SESSION['user'])): ?>
                                <a href="checkout.php" class="btn btn-success w-100">
                                    <i class="bi bi-credit-card"></i> Tiến hành thanh toán
                                </a>
                            <?php else: ?>
                                <div class="alert alert-warning mt-3">
                                    <p class="mb-0">Vui lòng <a href="login.php">đăng nhập</a> để tiến hành thanh toán.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
</body>
</html> 
<?php
session_start();
require_once 'db/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để thanh toán';
    header('Location: login.php');
    exit();
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Giỏ hàng của bạn đang trống';
    header('Location: cart.php');
    exit();
}

// Calculate total price
$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// Get user details
$user_id = $_SESSION['user']['id'];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Process order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Xác định địa chỉ giao hàng dựa trên lựa chọn
    $shipping_address = '';
    if (isset($_POST['addressOption'])) {
        if ($_POST['addressOption'] == 'saved') {
            $shipping_address = $user['address'];
        } else {
            if (!isset($_POST['new_address']) || empty($_POST['new_address'])) {
                $_SESSION['error'] = 'Vui lòng nhập địa chỉ giao hàng mới';
                header('Location: checkout.php');
                exit();
            }
            $shipping_address = mysqli_real_escape_string($conn, $_POST['new_address']);
        }
    } else {
        $_SESSION['error'] = 'Vui lòng chọn địa chỉ giao hàng';
        header('Location: checkout.php');
        exit();
    }

    // Validate payment method
    if (!isset($_POST['payment_method'])) {
        $_SESSION['error'] = 'Vui lòng chọn phương thức thanh toán';
        header('Location: checkout.php');
        exit();
    }

    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $user_id = $_SESSION['user']['id'];
    $total_amount = 0;

    // Tính tổng tiền
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Bắt đầu transaction
    mysqli_begin_transaction($conn);

    try {
        // Thêm đơn hàng mới
        $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) 
                       VALUES (?, ?, ?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $order_query);
        mysqli_stmt_bind_param($stmt, "idss", $user_id, $total_amount, $shipping_address, $payment_method);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);

        // Lưu thông tin thanh toán chi tiết
        if ($payment_method == 'credit_card' && isset($_POST['card_number'])) {
            $card_number = substr($_POST['card_number'], -4); // Chỉ lưu 4 số cuối
            $card_holder = $_POST['card_name'];
            
            $payment_query = "INSERT INTO payment_details (order_id, payment_method, card_number, card_holder) 
                            VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $payment_query);
            mysqli_stmt_bind_param($stmt, "isss", $order_id, $payment_method, $card_number, $card_holder);
            mysqli_stmt_execute($stmt);
        }

        // Thêm chi tiết đơn hàng
        foreach ($_SESSION['cart'] as $item) {
            // Kiểm tra sản phẩm có tồn tại
            $check_product = "SELECT id, stock FROM products WHERE id = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $check_product);
            mysqli_stmt_bind_param($stmt, "i", $item['product_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($product = mysqli_fetch_assoc($result)) {
                $quantity = (int)$item['quantity'];
                $price = (float)$item['price'];
                
                // Kiểm tra số lượng tồn kho
                if ($product['stock'] >= $quantity) {
                    // Thêm chi tiết đơn hàng
                    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                 VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $item_query);
                    mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['product_id'], $quantity, $price);
                    mysqli_stmt_execute($stmt);
                    
                    // Cập nhật số lượng sản phẩm
                    $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_stock);
                    mysqli_stmt_bind_param($stmt, "ii", $quantity, $item['product_id']);
                    mysqli_stmt_execute($stmt);
                } else {
                    throw new Exception("Sản phẩm '{$item['name']}' không đủ số lượng trong kho");
                }
            } else {
                throw new Exception("Sản phẩm '{$item['name']}' không tồn tại");
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Xóa giỏ hàng
        unset($_SESSION['cart']);
        
        $_SESSION['success'] = 'Đặt hàng thành công!';
        header("Location: order.php?id=$order_id");
        exit();
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        mysqli_rollback($conn);
        
        $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        header('Location: checkout.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán</title>
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
        <h1 class="my-4"><i class="bi bi-credit-card"></i> Thanh toán</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Thông tin đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <form action="checkout.php" method="POST">
                            <div class="mb-4">
                                <h5>Thông tin khách hàng</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ tên:</label>
                                        <input type="text" class="form-control" value="<?php echo $user['name']; ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email:</label>
                                        <input type="email" class="form-control" value="<?php echo $user['email']; ?>" readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại:</label>
                                    <input type="text" class="form-control" value="<?php echo $user['phone']; ?>" readonly>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-4">
                                <h5>Địa chỉ giao hàng</h5>
                                
                                <?php if (!empty($user['address'])): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="addressOption" id="savedAddressOption" value="saved" checked>
                                        <label class="form-check-label" for="savedAddressOption">
                                            Giao hàng đến địa chỉ đã lưu
                                        </label>
                                        <div class="ms-4 mt-2 p-2 border rounded">
                                            <?php echo $user['address']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="addressOption" id="newAddressOption" value="new" <?php echo empty($user['address']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="newAddressOption">
                                        Giao hàng đến địa chỉ mới
                                    </label>
                                </div>
                                
                                <div id="newAddressForm" class="ms-4 mt-2" <?php echo !empty($user['address']) ? 'style="display: none;"' : ''; ?>>
                                    <div class="mb-3">
                                        <label for="new_address" class="form-label">Địa chỉ mới:</label>
                                        <textarea class="form-control" id="new_address" name="new_address" rows="3" placeholder="Nhập địa chỉ giao hàng mới"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-4">
                                <h5>Phương thức thanh toán</h5>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                                    <label class="form-check-label" for="cash">
                                        <i class="bi bi-cash"></i> Thanh toán khi nhận hàng (COD)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card">
                                    <label class="form-check-label" for="credit_card">
                                        <i class="bi bi-credit-card"></i> Thẻ tín dụng/ghi nợ
                                    </label>
                                    <div id="creditCardForm" class="ms-4 mt-2 p-3 border rounded" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Số thẻ</label>
                                            <input type="text" class="form-control" name="card_number" placeholder="XXXX XXXX XXXX XXXX">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Ngày hết hạn</label>
                                                <input type="text" class="form-control" name="card_expiry" placeholder="MM/YY">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Mã bảo mật (CVV)</label>
                                                <input type="text" class="form-control" name="card_cvv" placeholder="123">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tên chủ thẻ</label>
                                            <input type="text" class="form-control" name="card_name" placeholder="NGUYEN VAN A">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label class="form-check-label" for="bank_transfer">
                                        <i class="bi bi-bank"></i> Chuyển khoản ngân hàng
                                    </label>
                                    <div id="bankTransferInfo" class="ms-4 mt-2 p-3 border rounded" style="display: none;">
                                        <p class="mb-2"><strong>Thông tin tài khoản:</strong></p>
                                        <p class="mb-1">Ngân hàng: <strong>Vietcombank</strong></p>
                                        <p class="mb-1">Số tài khoản: <strong>1234567890</strong></p>
                                        <p class="mb-1">Chủ tài khoản: <strong>CÔNG TY TNHH LAPTOP</strong></p>
                                        <p class="mb-1">Nội dung chuyển khoản: <strong>Thanh toan don hang #<?php echo time(); ?></strong></p>
                                        <div class="alert alert-warning mt-2">
                                            <small>Vui lòng chuyển khoản trước khi đặt hàng và chụp màn hình giao dịch thành công để đối chiếu.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="momo" value="momo">
                                    <label class="form-check-label" for="momo">
                                        <i class="bi bi-wallet2"></i> Ví điện tử MoMo
                                    </label>
                                    <div id="momoInfo" class="ms-4 mt-2 p-3 border rounded" style="display: none;">
                                        <p class="mb-2"><strong>Thông tin thanh toán MoMo:</strong></p>
                                        <p class="mb-1">Số điện thoại: <strong>0987654321</strong></p>
                                        <p class="mb-1">Tên tài khoản: <strong>LAPTOP SHOP</strong></p>
                                        <p class="mb-1">Nội dung chuyển khoản: <strong>Thanh toan don hang #<?php echo time(); ?></strong></p>
                                        <div class="alert alert-warning mt-2">
                                            <small>Quét mã QR hoặc chuyển khoản trực tiếp qua số điện thoại và chụp màn hình giao dịch thành công để đối chiếu.</small>
                                        </div>
                                        <div class="text-center">
                                            <img src="images/qr-momo.png" alt="MoMo QR Code" class="img-fluid" style="max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Quay lại giỏ hàng
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Hoàn tất đặt hàng
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tóm tắt đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>SL</th>
                                        <th>Giá</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $item): ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
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
                    </div>
                </div>
            </div>
        </div>
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
        // Show/hide address form based on selection
        document.addEventListener('DOMContentLoaded', function() {
            const savedAddressOption = document.getElementById('savedAddressOption');
            const newAddressOption = document.getElementById('newAddressOption');
            const newAddressForm = document.getElementById('newAddressForm');

            if (savedAddressOption && newAddressOption) {
                savedAddressOption.addEventListener('change', function() {
                    if (this.checked) {
                        newAddressForm.style.display = 'none';
                    }
                });

                newAddressOption.addEventListener('change', function() {
                    if (this.checked) {
                        newAddressForm.style.display = 'block';
                    }
                });
            }

            // Handle payment method selection
            const cash = document.getElementById('cash');
            const creditCard = document.getElementById('credit_card');
            const bankTransfer = document.getElementById('bank_transfer');
            const momo = document.getElementById('momo');
            
            const creditCardForm = document.getElementById('creditCardForm');
            const bankTransferInfo = document.getElementById('bankTransferInfo');
            const momoInfo = document.getElementById('momoInfo');
            
            function hideAllPaymentForms() {
                creditCardForm.style.display = 'none';
                bankTransferInfo.style.display = 'none';
                momoInfo.style.display = 'none';
            }
            
            cash.addEventListener('change', function() {
                if (this.checked) {
                    hideAllPaymentForms();
                }
            });
            
            creditCard.addEventListener('change', function() {
                if (this.checked) {
                    hideAllPaymentForms();
                    creditCardForm.style.display = 'block';
                }
            });
            
            bankTransfer.addEventListener('change', function() {
                if (this.checked) {
                    hideAllPaymentForms();
                    bankTransferInfo.style.display = 'block';
                }
            });
            
            momo.addEventListener('change', function() {
                if (this.checked) {
                    hideAllPaymentForms();
                    momoInfo.style.display = 'block';
                }
            });

            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
                let isPaymentSelected = false;
                
                paymentMethods.forEach(function(method) {
                    if (method.checked) {
                        isPaymentSelected = true;
                        
                        // Validate credit card information
                        if (method.value === 'credit_card') {
                            const cardNumber = document.querySelector('input[name="card_number"]').value;
                            const cardExpiry = document.querySelector('input[name="card_expiry"]').value;
                            const cardCvv = document.querySelector('input[name="card_cvv"]').value;
                            const cardName = document.querySelector('input[name="card_name"]').value;
                            
                            if (!cardNumber || !cardExpiry || !cardCvv || !cardName) {
                                e.preventDefault();
                                alert('Vui lòng nhập đầy đủ thông tin thẻ tín dụng/ghi nợ');
                            }
                        }
                    }
                });
                
                if (!isPaymentSelected) {
                    e.preventDefault();
                    alert('Vui lòng chọn phương thức thanh toán');
                }
            });
        });
    </script>
</body>
</html> 
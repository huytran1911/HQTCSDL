<?php
session_start();
require_once 'db/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: admin-login.php');
    exit();
}

// Lấy thông tin admin
$admin = $_SESSION['user'];

// Xử lý các action từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category = mysqli_real_escape_string($conn, $_POST['category']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $image = mysqli_real_escape_string($conn, $_POST['image']);
            $specs = mysqli_real_escape_string($conn, $_POST['specs']);
            
            $sql = "INSERT INTO products (name, category, price, description, image, specs) VALUES ('$name', '$category', '$price', '$description', '$image', '$specs')";
            mysqli_query($conn, $sql);
            break;
            
        case 'update_product':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category = mysqli_real_escape_string($conn, $_POST['category']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $image = mysqli_real_escape_string($conn, $_POST['image']);
            $specs = mysqli_real_escape_string($conn, $_POST['specs']);
            
            $sql = "UPDATE products SET name = '$name', category = '$category', price = '$price', description = '$description', image = '$image', specs = '$specs' WHERE id = '$id'";
            mysqli_query($conn, $sql);
            break;
            
        case 'delete_product':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            $sql = "DELETE FROM products WHERE id = '$id'";
            mysqli_query($conn, $sql);
            break;
            
        case 'update_order_status':
            $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            
            $sql = "UPDATE orders SET status = '$status' WHERE id = '$order_id'";
            mysqli_query($conn, $sql);
            break;
            
        case 'update_user':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $address = mysqli_real_escape_string($conn, $_POST['address']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            $is_active = mysqli_real_escape_string($conn, $_POST['is_active']);
            $password = $_POST['password'] ?? '';
            
            if (!empty($password)) {
                // Nếu có cập nhật mật khẩu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', 
                        address = '$address', role = '$role', is_active = '$is_active', 
                        password = '$hashed_password' WHERE id = '$id'";
            } else {
                // Nếu không cập nhật mật khẩu
                $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', 
                        address = '$address', role = '$role', is_active = '$is_active' 
                        WHERE id = '$id'";
            }
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Cập nhật thông tin người dùng thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi sửa thông tin người dùng";
            }
            break;
            
        case 'delete_user':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            // Không cho phép xóa tài khoản admin
            $sql = "DELETE FROM users WHERE id = '$id' AND role != 'admin'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Xóa người dùng thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa người dùng";
            }
            break;
    }
    
    // Redirect để tránh form resubmission
    header('Location: admin.php');
    exit();
}

// Lấy thống kê
$stats = [];
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$stats['total_users'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products");
$stats['total_products'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
$stats['total_revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Lấy danh sách sản phẩm
$products = [];
$products_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $products_query);

if (mysqli_num_rows($products_result) > 0) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
}

// Xử lý lọc đơn hàng
$where_conditions = array();
$params = array();

// Lọc theo trạng thái
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where_conditions[] = "o.status = '$status'";
}

// Lọc theo phương thức thanh toán
if (isset($_GET['payment_method']) && !empty($_GET['payment_method'])) {
    $payment_method = mysqli_real_escape_string($conn, $_GET['payment_method']);
    $where_conditions[] = "o.payment_method = '$payment_method'";
}

// Lọc theo khoảng thời gian
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $where_conditions[] = "DATE(o.created_at) >= '$date_from'";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $where_conditions[] = "DATE(o.created_at) <= '$date_to'";
}

// Lọc theo địa chỉ giao hàng
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $location = mysqli_real_escape_string($conn, $_GET['location']);
    $where_conditions[] = "o.shipping_address LIKE '%$location%'";
}

// Lọc theo khoảng giá
if (isset($_GET['price_from']) && !empty($_GET['price_from'])) {
    $price_from = (float)$_GET['price_from'];
    $where_conditions[] = "o.total_amount >= $price_from";
}

if (isset($_GET['price_to']) && !empty($_GET['price_to'])) {
    $price_to = (float)$_GET['price_to'];
    $where_conditions[] = "o.total_amount <= $price_to";
}

// Tạo câu query với điều kiện lọc
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Query lấy đơn hàng với điều kiện lọc
$orders_query = "SELECT o.*, u.name 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                $where_clause 
                ORDER BY o.created_at DESC";

$orders_result = mysqli_query($conn, $orders_query);
$orders = array();
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}

// Lấy danh sách người dùng
$users = [];
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
    <!-- Load jQuery và Bootstrap JS libraries trước khi load trang -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_menu.php'; ?>
            
            <!-- Nội dung chính -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Dashboard Section -->
                <div id="dashboard-section">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Bảng điều khiển Quản trị</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary">Chia sẻ</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">Xuất</button>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                                <i class="bi bi-calendar"></i>
                                Tuần này
                            </button>
                        </div>
                    </div>
                    
                    <!-- Thống kê tổng quan -->
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Tổng đơn hàng</h5>
                                    <p class="card-text display-6"><?php echo $stats['total_orders']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Tổng người dùng</h5>
                                    <p class="card-text display-6"><?php echo $stats['total_users']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Tổng sản phẩm</h5>
                                    <p class="card-text display-6"><?php echo $stats['total_products']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Tổng doanh thu</h5>
                                    <p class="card-text display-6"><?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?>đ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quản lý sản phẩm -->
                <div id="product-management-section" style="display: none;">
                    <h2>Quản lý sản phẩm</h2>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary mb-3" onclick="window.location.href='add_product.php'">
                        <i class="bi bi-plus-circle"></i> Thêm sản phẩm mới
                    </button>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <img src="images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 50px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <?php if ($product['stock'] > 0): ?>
                                            <span class="badge bg-success">Còn hàng</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Hết hàng</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge bg-info">Đang hiển thị</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Đã ẩn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="admin_process.php" method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php
                                            // Kiểm tra xem sản phẩm đã được bán chưa
                                            $check_sql = "SELECT COUNT(*) as sold_count FROM order_items WHERE product_id = " . $product['id'];
                                            $check_result = mysqli_query($conn, $check_sql);
                                            $sold_count = mysqli_fetch_assoc($check_result)['sold_count'];
                                            
                                            if ($sold_count > 0): 
                                            ?>
                                            <form action="admin_process.php" method="POST" style="display: inline;" onsubmit="return confirm('CẢNH BÁO: Hành động này sẽ xóa vĩnh viễn sản phẩm và không thể khôi phục. Bạn có chắc chắn muốn tiếp tục?');">
                                                <input type="hidden" name="action" value="force_delete_product">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-dark">
                                                    <i class="bi bi-trash-fill"></i> Xóa vĩnh viễn
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quản lý đơn hàng -->
                <div id="order-management-section" style="display: none;">
                    <h2>Quản lý đơn hàng</h2>
                    
                    <!-- Form lọc đơn hàng -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Lọc đơn hàng</h5>
                        </div>
                        <div class="card-body">
                            <form action="admin.php" method="GET" class="row g-3">
                                <input type="hidden" name="section" value="order-management-section">
                                
                                <!-- Lọc theo trạng thái -->
                                <div class="col-md-3">
                                    <label for="order_status" class="form-label">Trạng thái đơn hàng</label>
                                    <select class="form-select" name="status" id="order_status">
                                        <option value="">Tất cả</option>
                                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?php echo isset($_GET['status']) && $_GET['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="delivered" <?php echo isset($_GET['status']) && $_GET['status'] == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                        <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                </div>
                                
                                <!-- Lọc theo thời gian -->
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Từ ngày</label>
                                    <input type="date" class="form-control" name="date_from" id="start_date" 
                                           value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">Đến ngày</label>
                                    <input type="date" class="form-control" name="date_to" id="end_date"
                                           value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                                </div>
                                
                                <!-- Lọc theo địa điểm -->
                                <div class="col-md-3">
                                    <label for="location" class="form-label">Địa điểm giao hàng</label>
                                    <input type="text" class="form-control" name="location" id="location" 
                                           placeholder="Nhập quận/huyện/thành phố"
                                           value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
                                </div>
                                
                                <!-- Nút lọc và reset -->
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Lọc
                                    </button>
                                    <a href="admin.php?section=order-management-section" class="btn btn-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Đặt lại
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Bảng đơn hàng -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Địa chỉ giao hàng</th>
                                    <th>Phương thức thanh toán</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                                    <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                    <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                                    <td><?php echo $order['payment_method'] == 'online' ? 'Trực tuyến' : 'Tiền mặt'; ?></td>
                                    <td>
                                        <form action="admin_process.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="get_order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quản lý người dùng -->
                <div id="user-management-section" style="display: none;">
                    <h2>Quản lý người dùng</h2>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i> Thêm người dùng mới
                    </button>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên người dùng</th>
                                    <th>Email</th>
                                    <th>Ngày đăng ký</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Sửa</a>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                        <form method="POST" action="admin_process.php" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_user_status">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                                <?php echo $user['is_active'] ? 'Khoá' : 'Mở khoá'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="admin_process.php" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Thêm người dùng mới -->
                <div class="modal fade" id="addUserModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Thêm người dùng mới</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="admin_process.php">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="add_user">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" name="phone">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea class="form-control" name="address" rows="2"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Vai trò</label>
                                        <select class="form-select" name="role" required>
                                            <option value="user">Người dùng</option>
                                            <option value="admin">Quản trị viên</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select class="form-select" name="is_active" required>
                                            <option value="1">Hoạt động</option>
                                            <option value="0">Khóa</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" class="btn btn-primary">Thêm người dùng</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Sửa người dùng -->
                <?php if (isset($_SESSION['show_edit_modal']) && isset($_SESSION['edit_user'])): 
                    $edit_user = $_SESSION['edit_user'];
                    // Xóa dữ liệu khỏi session sau khi đã lấy
                    unset($_SESSION['show_edit_modal']);
                    unset($_SESSION['edit_user']);
                ?>
                <div class="modal fade" id="editUserModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Sửa thông tin người dùng</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="admin_process.php">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_user['name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($edit_user['address'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Vai trò</label>
                                        <select class="form-select" name="role" required>
                                            <option value="user" <?php echo $edit_user['role'] === 'user' ? 'selected' : ''; ?>>Người dùng</option>
                                            <option value="admin" <?php echo $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <select class="form-select" name="is_active" required>
                                            <option value="1" <?php echo $edit_user['is_active'] === '1' ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="0" <?php echo $edit_user['is_active'] === '0' ? 'selected' : ''; ?>>Khóa</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu mới (để trống nếu không muốn thay đổi)</label>
                                        <input type="password" class="form-control" name="password">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Thêm button ẩn để mở modal -->
                <button id="openEditModalBtn" class="d-none" data-bs-toggle="modal" data-bs-target="#editUserModal"></button>

                <!-- Script để tự động nhấn nút mở modal -->
                <script>
                    window.onload = function() {
                        document.getElementById('openEditModalBtn').click();
                    };
                </script>
                <?php endif; ?>

                <!-- Thống kê -->
                <div id="statistics-section" style="display: none;">
                    <h2>Thống kê kinh doanh</h2>

                    <!-- Form thống kê top 5 khách hàng -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Top 5 khách hàng mua nhiều nhất</h5>
                        </div>
                        <div class="card-body">
                            <form action="admin.php" method="GET" class="row g-3">
                                <input type="hidden" name="section" value="statistics-section">
                                
                                <!-- Chọn khoảng thời gian -->
                                <div class="col-md-5">
                                    <label for="stats_start_date" class="form-label">Từ ngày</label>
                                    <input type="date" class="form-control" name="stats_start_date" id="stats_start_date" 
                                           value="<?php echo isset($_GET['stats_start_date']) ? $_GET['stats_start_date'] : ''; ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label for="stats_end_date" class="form-label">Đến ngày</label>
                                    <input type="date" class="form-control" name="stats_end_date" id="stats_end_date"
                                           value="<?php echo isset($_GET['stats_end_date']) ? $_GET['stats_end_date'] : ''; ?>" required>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Thống kê
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php
                    // Xử lý thống kê top 5 khách hàng
                    if (isset($_GET['stats_start_date']) && isset($_GET['stats_end_date'])) {
                        $start_date = mysqli_real_escape_string($conn, $_GET['stats_start_date']);
                        $end_date = mysqli_real_escape_string($conn, $_GET['stats_end_date']);
                        
                        // Query lấy top 5 khách hàng
                        $top_customers_query = "
                            SELECT 
                                u.id as user_id,
                                u.name,
                                u.email,
                                COUNT(DISTINCT o.id) as total_orders,
                                SUM(o.total_amount) as total_spent
                            FROM users u
                            JOIN orders o ON u.id = o.user_id
                            WHERE o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                            GROUP BY u.id
                            ORDER BY total_spent DESC
                            LIMIT 5
                        ";
                        $top_customers_result = mysqli_query($conn, $top_customers_query);
                        
                        if (mysqli_num_rows($top_customers_result) > 0) {
                            ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Kết quả thống kê từ <?php echo date('d/m/Y', strtotime($start_date)); ?> 
                                    đến <?php echo date('d/m/Y', strtotime($end_date)); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Xếp hạng</th>
                                                    <th>Khách hàng</th>
                                                    <th>Email</th>
                                                    <th>Số đơn hàng</th>
                                                    <th>Tổng tiền mua</th>
                                                    <th>Chi tiết đơn hàng</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $rank = 1;
                                                while ($customer = mysqli_fetch_assoc($top_customers_result)) {
                                                    // Lấy danh sách đơn hàng của khách hàng trong khoảng thời gian
                                                    $orders_query = "
                                                        SELECT id, created_at, total_amount, status
                                                        FROM orders 
                                                        WHERE user_id = {$customer['user_id']}
                                                        AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                                                        ORDER BY created_at DESC
                                                    ";
                                                    $orders_result = mysqli_query($conn, $orders_query);
                                                    ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <?php 
                                                            if ($rank == 1) echo '🥇';
                                                            else if ($rank == 2) echo '🥈';
                                                            else if ($rank == 3) echo '🥉';
                                                            else echo $rank;
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                        <td class="text-center"><?php echo $customer['total_orders']; ?></td>
                                                        <td class="text-end"><?php echo number_format($customer['total_spent'], 0, ',', '.'); ?>đ</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info" type="button" 
                                                                    data-bs-toggle="collapse" 
                                                                    data-bs-target="#orders<?php echo $customer['user_id']; ?>">
                                                                <i class="bi bi-list-ul"></i> Xem đơn hàng
                                                            </button>
                                                            <div class="collapse mt-2" id="orders<?php echo $customer['user_id']; ?>">
                                                                <div class="card card-body p-0">
                                                                    <table class="table table-sm mb-0">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th>Mã đơn</th>
                                                                                <th>Ngày đặt</th>
                                                                                <th>Trạng thái</th>
                                                                                <th>Tổng tiền</th>
                                                                                <th></th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php while ($order = mysqli_fetch_assoc($orders_result)) { ?>
                                                                                <tr>
                                                                                    <td>#<?php echo $order['id']; ?></td>
                                                                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                                                    <td>
                                                                                        <?php
                                                                                        $status_class = '';
                                                                                        $status_text = '';
                                                                                        switch ($order['status']) {
                                                                                            case 'pending':
                                                                                                $status_class = 'bg-warning';
                                                                                                $status_text = 'Chờ xác nhận';
                                                                                                break;
                                                                                            case 'confirmed':
                                                                                                $status_class = 'bg-info';
                                                                                                $status_text = 'Đã xác nhận';
                                                                                                break;
                                                                                            case 'delivered':
                                                                                                $status_class = 'bg-success';
                                                                                                $status_text = 'Đã giao';
                                                                                                break;
                                                                                            case 'cancelled':
                                                                                                $status_class = 'bg-danger';
                                                                                                $status_text = 'Đã hủy';
                                                                                                break;
                                                                                        }
                                                                                        ?>
                                                                                        <span class="badge <?php echo $status_class; ?>">
                                                                                            <?php echo $status_text; ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td class="text-end"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                                                                    <td class="text-center">
                                                                                        <a href="get_order_details.php?id=<?php echo $order['id']; ?>" 
                                                                                           class="btn btn-sm btn-outline-info">
                                                                                            <i class="bi bi-eye"></i>
                                                                                        </a>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php } ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $rank++;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php
                        } else {
                            echo '<div class="alert alert-info">Không có dữ liệu thống kê trong khoảng thời gian này.</div>';
                        }
                    }
                    ?>

                    <!-- Thống kê doanh thu và sản phẩm bán chạy -->
                    <?php
                    // Lấy dữ liệu doanh thu theo tháng
                    $start_date = isset($_GET['stats_start_date']) ? $_GET['stats_start_date'] : date('Y-m-01');
                    $end_date = isset($_GET['stats_end_date']) ? $_GET['stats_end_date'] : date('Y-m-t');

                    $revenue_query = "
                        SELECT 
                            DATE_FORMAT(created_at, '%m/%Y') as month,
                            SUM(total_amount) as revenue
                        FROM orders
                        WHERE status != 'cancelled'
                            AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                        GROUP BY DATE_FORMAT(created_at, '%m/%Y')
                        ORDER BY created_at ASC";
                    $revenue_result = mysqli_query($conn, $revenue_query);
                    
                    $months = [];
                    $revenues = [];
                    $total_revenue = 0;
                    while ($row = mysqli_fetch_assoc($revenue_result)) {
                        $months[] = $row['month'];
                        $revenues[] = $row['revenue'];
                        $total_revenue += $row['revenue'];
                    }

                    // Lấy dữ liệu sản phẩm bán chạy
                    $products_query = "
                        SELECT 
                            p.name,
                            p.price,
                            SUM(oi.quantity) as total_quantity,
                            SUM(oi.quantity * oi.price) as total_revenue
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        JOIN orders o ON oi.order_id = o.id
                        WHERE o.status != 'cancelled'
                            AND o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
                        GROUP BY p.id
                        ORDER BY total_quantity DESC
                        LIMIT 5";
                    $products_result = mysqli_query($conn, $products_query);
                    
                    $product_names = [];
                    $quantities = [];
                    $product_revenues = [];
                    while ($row = mysqli_fetch_assoc($products_result)) {
                        $product_names[] = $row['name'];
                        $quantities[] = $row['total_quantity'];
                        $product_revenues[] = $row['total_revenue'];
                    }
                    ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Doanh thu theo tháng</h5>
                                    <div class="mb-3">
                                        <strong>Tổng doanh thu: </strong>
                                        <?php echo number_format($total_revenue, 0, ',', '.'); ?>đ
                                    </div>
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Sản phẩm bán chạy</h5>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Sản phẩm</th>
                                                    <th class="text-end">Số lượng</th>
                                                    <th class="text-end">Doanh thu</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                mysqli_data_seek($products_result, 0);
                                                while ($product = mysqli_fetch_assoc($products_result)) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                                                    echo "<td class='text-end'>" . $product['total_quantity'] . "</td>";
                                                    echo "<td class='text-end'>" . number_format($product['total_revenue'], 0, ',', '.') . "đ</td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <canvas id="topProductsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Thêm sản phẩm -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="admin_process.php" method="POST" enctype="multipart/form-data" id="addProductForm">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category_id" required>
                                <?php
                                $cat_query = "SELECT * FROM categories";
                                $cat_result = mysqli_query($conn, $cat_query);
                                while ($category = mysqli_fetch_assoc($cat_result)) {
                                    echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giá</label>
                                <input type="number" class="form-control" name="price" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số lượng trong kho</label>
                                <input type="number" class="form-control" name="stock" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Thông số kỹ thuật</label>
                            <div id="specs-container">
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[CPU]" placeholder="CPU" required>
                                    </div>
                                </div>
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[RAM]" placeholder="RAM" required>
                                    </div>
                                </div>
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[Ổ cứng]" placeholder="Ổ cứng" required>
                                    </div>
                                </div>
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[Màn hình]" placeholder="Màn hình" required>
                                    </div>
                                </div>
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[Card đồ họa]" placeholder="Card đồ họa" required>
                                    </div>
                                </div>
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[Pin]" placeholder="Pin" required>
                                    </div>
                                </div>
                                <div class="spec-input-group mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="specs[Trọng lượng]" placeholder="Trọng lượng" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sửa sản phẩm -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa thông tin sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin_process.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="id" value="<?php echo isset($_SESSION['edit_product']) ? $_SESSION['edit_product']['id'] : ''; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" class="form-control" name="name" value="<?php echo isset($_SESSION['edit_product']) ? htmlspecialchars($_SESSION['edit_product']['name']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category_id" required>
                                <?php
                                $category_query = "SELECT * FROM categories WHERE is_active = 1";
                                $category_result = mysqli_query($conn, $category_query);
                                while ($category = mysqli_fetch_assoc($category_result)) {
                                    $selected = isset($_SESSION['edit_product']) && $_SESSION['edit_product']['category_id'] == $category['id'] ? 'selected' : '';
                                    echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Giá</label>
                            <input type="number" class="form-control" name="price" value="<?php echo isset($_SESSION['edit_product']) ? $_SESSION['edit_product']['price'] : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số lượng trong kho</label>
                            <input type="number" class="form-control" name="stock" value="<?php echo isset($_SESSION['edit_product']) ? $_SESSION['edit_product']['stock'] : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo isset($_SESSION['edit_product']) ? htmlspecialchars($_SESSION['edit_product']['description']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <?php if (isset($_SESSION['edit_product']) && !empty($_SESSION['edit_product']['image'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $_SESSION['edit_product']['image']; ?>" alt="Current image" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">Chỉ chọn ảnh mới nếu muốn thay đổi ảnh hiện tại</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Thông số kỹ thuật</label>
                            <div class="specs-form">
                                <?php
                                $specs = isset($_SESSION['edit_product']['specs']) ? json_decode($_SESSION['edit_product']['specs'], true) : [];
                                ?>
                                <!-- CPU -->
                                <div class="mb-3">
                                    <label class="form-label">CPU</label>
                                    <input type="text" class="form-control" name="specs[cpu]" value="<?php echo htmlspecialchars($specs['cpu'] ?? ''); ?>" placeholder="VD: Intel Core i5-1135G7">
                                </div>
                                
                                <!-- RAM -->
                                <div class="mb-3">
                                    <label class="form-label">RAM</label>
                                    <input type="text" class="form-control" name="specs[ram]" value="<?php echo htmlspecialchars($specs['ram'] ?? ''); ?>" placeholder="VD: 8GB DDR4 3200MHz">
                                </div>
                                
                                <!-- Ổ cứng -->
                                <div class="mb-3">
                                    <label class="form-label">Ổ cứng</label>
                                    <input type="text" class="form-control" name="specs[storage]" value="<?php echo htmlspecialchars($specs['storage'] ?? ''); ?>" placeholder="VD: 512GB NVMe PCIe SSD">
                                </div>
                                
                                <!-- Card đồ họa -->
                                <div class="mb-3">
                                    <label class="form-label">Card đồ họa</label>
                                    <input type="text" class="form-control" name="specs[gpu]" value="<?php echo htmlspecialchars($specs['gpu'] ?? ''); ?>" placeholder="VD: NVIDIA GeForce MX350 2GB GDDR5">
                                </div>
                                
                                <!-- Màn hình -->
                                <div class="mb-3">
                                    <label class="form-label">Màn hình</label>
                                    <input type="text" class="form-control" name="specs[display]" value="<?php echo htmlspecialchars($specs['display'] ?? ''); ?>" placeholder="VD: 14 inch FHD IPS (1920 x 1080)">
                                </div>
                                
                                <!-- Pin -->
                                <div class="mb-3">
                                    <label class="form-label">Pin</label>
                                    <input type="text" class="form-control" name="specs[battery]" value="<?php echo htmlspecialchars($specs['battery'] ?? ''); ?>" placeholder="VD: 3 Cell 45WHr">
                                </div>
                                
                                <!-- Cân nặng -->
                                <div class="mb-3">
                                    <label class="form-label">Cân nặng</label>
                                    <input type="text" class="form-control" name="specs[weight]" value="<?php echo htmlspecialchars($specs['weight'] ?? ''); ?>" placeholder="VD: 1.4 kg">
                                </div>
                                
                                <!-- Hệ điều hành -->
                                <div class="mb-3">
                                    <label class="form-label">Hệ điều hành</label>
                                    <input type="text" class="form-control" name="specs[os]" value="<?php echo htmlspecialchars($specs['os'] ?? ''); ?>" placeholder="VD: Windows 11 Home">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    // Hiển thị modal sửa sản phẩm nếu có dữ liệu trong session
    if (isset($_SESSION['show_edit_product_modal']) && isset($_SESSION['edit_product'])) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var editModal = new bootstrap.Modal(document.getElementById("editProductModal"));
                editModal.show();
            });
        </script>';
        unset($_SESSION['show_edit_product_modal']);
        unset($_SESSION['edit_product']);
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle sidebar trên mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        // Hàm sửa người dùng
        function editUser(id) {
            // Chuyển hướng đến trang admin_process.php với action=get_user_edit
            window.location.href = `admin_process.php?action=get_user_edit&id=${id}`;
        }
        
        // Hàm xóa người dùng
        function deleteUser(id) {
            if (confirm('Bạn có chắc muốn xóa người dùng này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_process.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Hàm sửa sản phẩm
        function editProduct(id) {
            window.location.href = `admin_process.php?action=get_product_edit&id=${id}`;
        }

        function confirmDelete(productId) {
            // Kiểm tra xem sản phẩm đã được bán chưa bằng AJAX
            $.ajax({
                url: 'check_product_sales.php',
                type: 'POST',
                data: { id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.sold) {
                        return confirm('Sản phẩm này đã được bán. Bạn chỉ có thể ẩn sản phẩm khỏi trang web. Tiếp tục?');
                    } else {
                        return confirm('Bạn có chắc chắn muốn xóa sản phẩm này? Hành động này không thể hoàn tác.');
                    }
                },
                error: function() {
                    return confirm('Không thể kiểm tra thông tin sản phẩm. Bạn có chắc chắn muốn tiếp tục?');
                }
            });
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Dữ liệu doanh thu theo tháng
            <?php
            $revenue_query = "
                SELECT 
                    DATE_FORMAT(created_at, '%m/%Y') as month,
                    SUM(total_amount) as revenue
                FROM orders
                WHERE status != 'cancelled'
                GROUP BY DATE_FORMAT(created_at, '%m/%Y')
                ORDER BY created_at ASC
                LIMIT 12";
            $revenue_result = mysqli_query($conn, $revenue_query);
            
            $months = [];
            $revenues = [];
            while ($row = mysqli_fetch_assoc($revenue_result)) {
                $months[] = $row['month'];
                $revenues[] = $row['revenue'];
            }
            ?>

            // Vẽ biểu đồ doanh thu
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: <?php echo json_encode($revenues); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });

            // Dữ liệu sản phẩm bán chạy
            <?php
            $products_query = "
                SELECT 
                    p.name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status != 'cancelled'
                GROUP BY p.id
                ORDER BY total_quantity DESC
                LIMIT 5";
            $products_result = mysqli_query($conn, $products_query);
            
            $product_names = [];
            $quantities = [];
            $product_revenues = [];
            while ($row = mysqli_fetch_assoc($products_result)) {
                $product_names[] = $row['name'];
                $quantities[] = $row['total_quantity'];
                $product_revenues[] = $row['total_revenue'];
            }
            ?>

            // Vẽ biểu đồ sản phẩm bán chạy
            const productsCtx = document.getElementById('topProductsChart').getContext('2d');
            new Chart(productsCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($product_names); ?>,
                    datasets: [{
                        data: <?php echo json_encode($quantities); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const quantity = context.parsed;
                                    const revenue = <?php echo json_encode($product_revenues); ?>[context.dataIndex];
                                    return [
                                        context.label,
                                        'Số lượng: ' + quantity + ' sản phẩm',
                                        'Doanh thu: ' + new Intl.NumberFormat('vi-VN', {
                                            style: 'currency',
                                            currency: 'VND'
                                        }).format(revenue)
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php
session_start();
require_once 'db/database.php';



$categories_query = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_query);


$products_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $products_per_page;

$where_conditions = array();
$params = array();

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = (int)$_GET['category'];
    $where_conditions[] = "p.category_id = $category_id";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $where_conditions[] = "p.name LIKE '%$search_term%'";
}

// Lọc theo giá
if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $min_price = (float)$_GET['min_price'];
    $where_conditions[] = "p.price >= $min_price";
}
if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $max_price = (float)$_GET['max_price'];
    $where_conditions[] = "p.price <= $max_price";
}


$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}


$count_query = "SELECT COUNT(p.id) as total 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                $where_clause" . (empty($where_clause) ? " WHERE p.is_active = 1" : " AND p.is_active = 1");
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $products_per_page);


$products_query = "SELECT p.id, p.name, p.description, p.price, p.image, p.specs, p.stock, c.name as category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  $where_clause" . (empty($where_clause) ? " WHERE p.is_active = 1" : " AND p.is_active = 1") . "
                  ORDER BY p.name 
                  LIMIT $offset, $products_per_page";
$products_result = mysqli_query($conn, $products_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa hàng Laptop</title>
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-laptop"></i> Danh mục
                        </a>
                        <ul class="dropdown-menu">
                            <?php while ($category = mysqli_fetch_assoc($categories_result)) { ?>
                                <li><a class="dropdown-item" href="index.php?category=<?php echo $category['id']; ?>"><?php echo $category['name']; ?></a></li>
                            <?php } ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-info-circle"></i> Giới thiệu</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-telephone"></i> Liên hệ</a></li>
                    <?php if (isset($_SESSION['user'])) { ?>
                        <li class="nav-item"><a class="nav-link" href="history.php"><i class="bi bi-clock-history"></i> Lịch sử mua hàng</a></li>
                    <?php } ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> 
                            <?php if (isset($_SESSION['user']) && isset($_SESSION['user']['name'])) { ?>
                                <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                            <?php } else { ?>
                                Chưa đăng nhập
                            <?php } ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if (!isset($_SESSION['user'])) { ?>
                                <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Đăng Nhập</a></li>
                                <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus"></i> Đăng Ký</a></li>
                            <?php } else { ?>
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Thông tin tài khoản</a></li>
                                <?php if ($_SESSION['user']['role'] === 'admin') { ?>
                                    <li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear"></i> Quản trị</a></li>
                                <?php } ?>
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

    <main class="container mt-5 pt-5">
       
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tìm kiếm </h5>
            </div>
            <div class="card-body">
                <form action="index.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select" name="category">
                                <option value="">Tất cả</option>
                                <?php 
                                mysqli_data_seek($categories_result, 0);
                                while ($category = mysqli_fetch_assoc($categories_result)) { 
                                    $selected = isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Giá từ</label>
                            <input type="number" class="form-control" name="min_price" 
                                   value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Giá đến</label>
                            <input type="number" class="form-control" name="max_price" 
                                   value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

      
        <h2 class="mb-4">
            <?php 
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $cat_query = "SELECT name FROM categories WHERE id = " . (int)$_GET['category'];
                $cat_result = mysqli_query($conn, $cat_query);
                $cat_name = mysqli_fetch_assoc($cat_result)['name'];
                echo 'Sản phẩm ' . $cat_name;
            } elseif (isset($_GET['search']) && !empty($_GET['search'])) {
                echo 'Kết quả tìm kiếm cho: ' . htmlspecialchars($_GET['search']);
            } else {
                echo 'Tất cả sản phẩm';
            }
            ?>
        </h2>

        <div class="product-list">
            <?php 
            if (mysqli_num_rows($products_result) > 0) {
                while ($product = mysqli_fetch_assoc($products_result)) { 
            ?>
                <div class="product">
                    <div class="product-image-container">
                        <img src="images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo $product['name']; ?></h3>
                        <div class="product-specs">
                            <?php 
                            if (!empty($product['specs'])) {
                                $specs = json_decode($product['specs'], true);
                                if ($specs) {
                                    $main_specs = array_slice($specs, 0, 3); // Lấy 3 thông số đầu tiên
                                    foreach ($main_specs as $key => $value) {
                                        echo "<div class='spec-item'><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</div>";
                                    }
                                }
                            } else {
                                echo "<div class='spec-item'>" . htmlspecialchars($product['description']) . "</div>";
                            }
                            ?>
                        </div>
                        <div class="product-price"><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</div>
                        <div class="product-actions">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">Chi tiết</a>
                            <?php if ($product['stock'] > 0) { ?>
                            <form action="cart_process.php" method="POST" style="flex: 1;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                </button>
                            </form>
                            <?php } else { ?>
                            <button type="button" class="btn btn-secondary w-100" disabled>
                                <i class="bi bi-exclamation-circle"></i> Hết hàng
                            </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo '<div class="col-12"><p class="alert alert-info">Không tìm thấy sản phẩm nào.</p></div>';
            }
            ?>
        </div>

        
        <?php if ($total_pages > 1) { ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1) { ?>
                    <a href="?page=<?php echo ($page - 1); ?><?php echo isset($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">«</a>
                <?php } ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                    <?php if ($i == $page) { ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php } else { ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"><?php echo $i; ?></a>
                    <?php } ?>
                <?php } ?>
                
                <?php if ($page < $total_pages) { ?>
                    <a href="?page=<?php echo ($page + 1); ?><?php echo isset($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">»</a>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </main>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Thông tin liên hệ</h5>
                    <p><i class="bi bi-geo-alt"></i> 30 đường huỳnh thị đồng, Quận 7, TP.HCM</p>
                    <p><i class="bi bi-telephone"></i> 0899885663</p>
                    <p><i class="bi bi-envelope"></i> buiminhquang246@gmail.com</p>
                </div>
                <div class="col-md-4">
                    <h5>ĐIỀU KHOẢN SỬ DỤNG</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Chính sách bảo mật thông tin</a></li>
                        <li><a href="#" class="text-white">Chính sách đặt hàng</a></li>
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
    <script src="hello.js"></script>
</body>
</html> 
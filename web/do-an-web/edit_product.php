<?php
session_start();
require_once 'db/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: admin-login.php');
    exit();
}

// Lấy thông tin sản phẩm cần sửa
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Thêm điều kiện IS NOT NULL để đảm bảo lấy được specs
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = '$id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && $product = mysqli_fetch_assoc($result)) {
        
        
        // Decode thông số kỹ thuật từ JSON
        $specs = !empty($product['specs']) ? json_decode($product['specs'], true) : [];
        
        
        
        // Nếu decode thất bại, thử parse từ string
        if (json_last_error() !== JSON_ERROR_NONE) {
           
            $specs_text = $product['specs'];
            $specs = [];
            if (preg_match_all('/"([^"]+)":"([^"]+)"/', $specs_text, $matches)) {
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $specs[$matches[1][$i]] = $matches[2][$i];
                }
            }
            error_log("Parsed specs from string: " . print_r($specs, true));
        }
    } else {
        $_SESSION['error_message'] = "Không tìm thấy sản phẩm";
        header('Location: admin.php?section=product-management-section');
        exit();
    }
} else {
    header('Location: admin.php?section=product-management-section');
    exit();
}


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sửa thông tin sản phẩm</h5>
                        <a href="admin.php?section=product-management-section" class="btn btn-secondary">Quay lại</a>
                    </div>
                    <div class="card-body">
                        <form action="admin_process.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_product">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Tên sản phẩm</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select class="form-select" name="category_id" required>
                                    <?php
                                    $category_query = "SELECT * FROM categories";
                                    $category_result = mysqli_query($conn, $category_query);
                                    while ($category = mysqli_fetch_assoc($category_result)) {
                                        $selected = ($category['id'] == $product['category_id']) ? 'selected' : '';
                                        echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Giá</label>
                                <input type="number" class="form-control" name="price" value="<?php echo $product['price']; ?>" required min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số lượng trong kho</label>
                                <input type="number" class="form-control" name="stock" value="<?php echo $product['stock']; ?>" required min="0">
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Hiển thị sản phẩm trên trang web
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hình ảnh hiện tại</label>
                                <?php if (!empty($product['image'])): ?>
                                    <div class="mb-2">
                                        <img src="images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Chỉ chọn ảnh mới nếu muốn thay đổi ảnh hiện tại</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Thông số kỹ thuật</label>
                                <div class="specs-container border rounded p-3">
                                    <!-- CPU -->
                                    <div class="mb-3">
                                        <label class="form-label">CPU</label>
                                        <input type="text" class="form-control" name="specs[CPU]" 
                                            value="<?php echo isset($specs['CPU']) ? htmlspecialchars($specs['CPU']) : ''; ?>">
                                    </div>
                                    
                                    <!-- RAM -->
                                    <div class="mb-3">
                                        <label class="form-label">RAM</label>
                                        <input type="text" class="form-control" name="specs[RAM]" 
                                            value="<?php echo isset($specs['RAM']) ? htmlspecialchars($specs['RAM']) : ''; ?>">
                                    </div>
                                    
                                    <!-- Ổ cứng -->
                                    <div class="mb-3">
                                        <label class="form-label">Ổ cứng</label>
                                        <input type="text" class="form-control" name="specs[Ổ cứng]" 
                                            value="<?php echo isset($specs['Ổ cứng']) ? htmlspecialchars($specs['Ổ cứng']) : ''; ?>">
                                    </div>
                                    
                                    <!-- Màn hình -->
                                    <div class="mb-3">
                                        <label class="form-label">Màn hình</label>
                                        <input type="text" class="form-control" name="specs[Màn hình]" 
                                            value="<?php echo isset($specs['Màn hình']) ? htmlspecialchars($specs['Màn hình']) : ''; ?>">
                                    </div>

                                    <!-- Card đồ họa -->
                                    <div class="mb-3">
                                        <label class="form-label">Card đồ họa</label>
                                        <input type="text" class="form-control" name="specs[Card đồ họa]" 
                                            value="<?php echo isset($specs['Card đồ họa']) ? htmlspecialchars($specs['Card đồ họa']) : ''; ?>">
                                    </div>
                                    
                                    <!-- Pin -->
                                    <div class="mb-3">
                                        <label class="form-label">Pin</label>
                                        <input type="text" class="form-control" name="specs[Pin]" 
                                            value="<?php echo isset($specs['Pin']) ? htmlspecialchars($specs['Pin']) : ''; ?>">
                                    </div>
                                    
                                    <!-- Trọng lượng -->
                                    <div class="mb-3">
                                        <label class="form-label">Trọng lượng</label>
                                        <input type="text" class="form-control" name="specs[Trọng lượng]" 
                                            value="<?php echo isset($specs['Trọng lượng']) ? htmlspecialchars($specs['Trọng lượng']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
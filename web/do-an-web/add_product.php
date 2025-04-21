<?php
session_start();
require_once 'db/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: admin-login.php');
    exit();
}

// Lấy danh sách danh mục
$categories = [];
$category_query = "SELECT * FROM categories";
$category_result = mysqli_query($conn, $category_query);
while ($category = mysqli_fetch_assoc($category_result)) {
    $categories[] = $category;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        #imagePreview {
            max-width: 300px;
            max-height: 300px;
            margin-top: 10px;
        }
        .preview-container {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Thêm sản phẩm mới</h5>
                        <a href="admin.php?section=product-management-section" class="btn btn-secondary">Quay lại</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <form action="admin_process.php" method="POST" enctype="multipart/form-data" id="addProductForm">
                            <input type="hidden" name="action" value="add_product">
                            
                            <div class="mb-3">
                                <label class="form-label">Tên sản phẩm</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Giá</label>
                                <input type="number" class="form-control" name="price" required min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số lượng trong kho</label>
                                <input type="number" class="form-control" name="stock" required min="0">
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Hiển thị sản phẩm trên trang web
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" name="description" rows="3" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hình ảnh</label>
                                <input type="file" class="form-control" name="image" accept="image/*" required id="imageInput">
                                <div class="preview-container" id="previewContainer">
                                    <img id="imagePreview" class="img-fluid" alt="Preview">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Thông số kỹ thuật</label>
                                <div class="specs-container border rounded p-3">
                                    <!-- CPU -->
                                    <div class="mb-3">
                                        <label class="form-label">CPU</label>
                                        <input type="text" class="form-control" name="specs[CPU]" placeholder="VD: Intel Core i5-1135G7">
                                    </div>
                                    
                                    <!-- RAM -->
                                    <div class="mb-3">
                                        <label class="form-label">RAM</label>
                                        <input type="text" class="form-control" name="specs[RAM]" placeholder="VD: 8GB DDR4 3200MHz">
                                    </div>
                                    
                                    <!-- Ổ cứng -->
                                    <div class="mb-3">
                                        <label class="form-label">Ổ cứng</label>
                                        <input type="text" class="form-control" name="specs[Ổ cứng]" placeholder="VD: 512GB NVMe PCIe SSD">
                                    </div>
                                    
                                    <!-- Card đồ họa -->
                                    <div class="mb-3">
                                        <label class="form-label">Card đồ họa</label>
                                        <input type="text" class="form-control" name="specs[Card đồ họa]" placeholder="VD: NVIDIA GeForce MX350 2GB GDDR5">
                                    </div>
                                    
                                    <!-- Màn hình -->
                                    <div class="mb-3">
                                        <label class="form-label">Màn hình</label>
                                        <input type="text" class="form-control" name="specs[Màn hình]" placeholder="VD: 14 inch FHD IPS (1920 x 1080)">
                                    </div>
                                    
                                    <!-- Pin -->
                                    <div class="mb-3">
                                        <label class="form-label">Pin</label>
                                        <input type="text" class="form-control" name="specs[Pin]" placeholder="VD: 3 Cell 45WHr">
                                    </div>
                                    
                                    <!-- Trọng lượng -->
                                    <div class="mb-3">
                                        <label class="form-label">Trọng lượng</label>
                                        <input type="text" class="form-control" name="specs[Trọng lượng]" placeholder="VD: 1.4 kg">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xem trước hình ảnh
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('previewContainer');
            const imagePreview = document.getElementById('imagePreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });

        // Validate form trước khi submit
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const imageInput = document.getElementById('imageInput');
            const file = imageInput.files[0];

            if (!file) {
                e.preventDefault();
                alert('Vui lòng chọn hình ảnh cho sản phẩm');
                return;
            }

            // Kiểm tra kích thước file (giới hạn 5MB)
            if (file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('Kích thước hình ảnh không được vượt quá 5MB');
                return;
            }

            // Kiểm tra định dạng file
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                e.preventDefault();
                alert('Chỉ chấp nhận file hình ảnh định dạng JPG, PNG hoặc GIF');
                return;
            }
        });
    </script>
</body>
</html> 
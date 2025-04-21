<?php
session_start();
require_once 'db/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: admin-login.php');
    exit();
}

// Xử lý các action từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            // Lấy dữ liệu từ form
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $stock = mysqli_real_escape_string($conn, $_POST['stock']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            
            // Xử lý upload hình ảnh
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = 'images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Tạo tên file ngẫu nhiên để tránh trùng lặp
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                // Kiểm tra và xử lý upload
                $upload_ok = true;
                $image_file_type = strtolower($file_extension);
                
                // Kiểm tra kích thước file (giới hạn 5MB)
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $_SESSION['error_message'] = "Kích thước file quá lớn. Giới hạn 5MB.";
                    $upload_ok = false;
                }
                
                // Kiểm tra định dạng file
                if (!in_array($image_file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $_SESSION['error_message'] = "Chỉ chấp nhận file JPG, JPEG, PNG & GIF.";
                    $upload_ok = false;
                }
                
                if ($upload_ok) {
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                        $image = $file_name; // Lưu tên file vào database
                    } else {
                        $_SESSION['error_message'] = "Có lỗi xảy ra khi upload file.";
                        header('Location: add_product.php');
                        exit();
                    }
                } else {
                    header('Location: add_product.php');
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Vui lòng chọn hình ảnh cho sản phẩm.";
                header('Location: add_product.php');
                exit();
            }
            
            // Xử lý thông số kỹ thuật
            $specs_array = [];
            if (isset($_POST['specs']) && is_array($_POST['specs'])) {
                foreach ($_POST['specs'] as $key => $value) {
                    if (!empty($key) && !empty($value)) {
                        $specs_array[$key] = $value;
                    }
                }
            }
            $specs_json = json_encode($specs_array, JSON_UNESCAPED_UNICODE);
            
            // Kiểm tra lỗi JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON Error: " . json_last_error_msg());
                $_SESSION['error_message'] = "Có lỗi xảy ra khi xử lý thông số kỹ thuật";
                header('Location: add_product.php');
                exit();
            }
            
            // Thêm sản phẩm mới với trạng thái active dựa trên số lượng tồn kho
            $sql = "INSERT INTO products (name, category_id, price, stock, description, image, specs, is_active, created_at) 
                    VALUES ('$name', '$category_id', '$price', '$stock', '$description', '$image', '$specs_json', 
                    IF($stock > 0, 1, 0), NOW())";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Thêm sản phẩm thành công";
                header('Location: admin.php?section=product-management-section');
            } else {
                // Nếu thêm thất bại, xóa file ảnh đã upload
                if (!empty($image) && file_exists($upload_dir . $image)) {
                    unlink($upload_dir . $image);
                }
                $_SESSION['error_message'] = "Có lỗi xảy ra khi thêm sản phẩm: " . mysqli_error($conn);
                header('Location: admin.php?section=product-management-section');
            }
            exit();
            break;
            
        case 'update_product':
            // Lấy dữ liệu từ form
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $stock = mysqli_real_escape_string($conn, $_POST['stock']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            
            // Xử lý upload hình ảnh mới nếu có
            $image_update = "";
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = 'uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Xóa hình ảnh cũ nếu có
                    $old_image_query = "SELECT image FROM products WHERE id = '$id'";
                    $old_image_result = mysqli_query($conn, $old_image_query);
                    if ($old_image_result && $old_image_row = mysqli_fetch_assoc($old_image_result)) {
                        $old_image = $old_image_row['image'];
                        if (!empty($old_image) && file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                    $image_update = ", image = '$target_file'";
                }
            }
            
            // Xử lý thông số kỹ thuật
            $specs_array = [];
            if (isset($_POST['specs']) && is_array($_POST['specs'])) {
                foreach ($_POST['specs'] as $key => $value) {
                    if (!empty($value)) {
                        $specs_array[$key] = $value;
                    }
                }
            }
            $specs_json = json_encode($specs_array, JSON_UNESCAPED_UNICODE);
            
            // Kiểm tra lỗi JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON Error: " . json_last_error_msg());
                $_SESSION['error_message'] = "Có lỗi xảy ra khi xử lý thông số kỹ thuật";
                header('Location: admin.php?section=product-management-section');
                exit();
            }
            
            $sql = "UPDATE products SET 
                    name = '$name',
                    category_id = '$category_id',
                    price = '$price',
                    stock = '$stock',
                    description = '$description',
                    specs = '$specs_json'
                    $image_update,
                    is_active = IF($stock >= 0, 1, 0)
                    WHERE id = '$id'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Cập nhật sản phẩm thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi cập nhật sản phẩm: " . mysqli_error($conn);
            }
            
            header('Location: admin.php?section=product-management-section');
            exit();
            break;
            
        case 'delete_product':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            
            // Kiểm tra xem sản phẩm đã được bán chưa
            $check_sql = "SELECT COUNT(*) as sold_count FROM order_items WHERE product_id = '$id'";
            $check_result = mysqli_query($conn, $check_sql);
            $sold_count = mysqli_fetch_assoc($check_result)['sold_count'];
            
            if ($sold_count > 0) {
                // Nếu sản phẩm đã được bán, chỉ ẩn sản phẩm
                $sql = "UPDATE products SET is_active = 0 WHERE id = '$id'";
                if (mysqli_query($conn, $sql)) {
                    $_SESSION['success_message'] = "Sản phẩm đã được bán nên chỉ có thể ẩn. Đã ẩn sản phẩm thành công.";
                } else {
                    $_SESSION['error_message'] = "Có lỗi xảy ra khi ẩn sản phẩm: " . mysqli_error($conn);
                }
            } else {
                // Nếu sản phẩm chưa được bán, xóa sản phẩm
                // Lấy thông tin hình ảnh trước khi xóa
                $img_sql = "SELECT image FROM products WHERE id = '$id'";
                $img_result = mysqli_query($conn, $img_sql);
                $product = mysqli_fetch_assoc($img_result);
                
                $sql = "DELETE FROM products WHERE id = '$id'";
                if (mysqli_query($conn, $sql)) {
                    // Xóa file hình ảnh nếu tồn tại
                    if (!empty($product['image'])) {
                        $image_path = 'images/' . $product['image'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    $_SESSION['success_message'] = "Xóa sản phẩm thành công";
                } else {
                    $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa sản phẩm: " . mysqli_error($conn);
                }
            }
            
            header('Location: admin.php?section=product-management-section');
            exit();
            break;

        case 'update_user':
            // Lấy dữ liệu từ form
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $address = mysqli_real_escape_string($conn, $_POST['address']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Xây dựng câu query cập nhật
            $sql = "UPDATE users SET 
                    name = '$name',
                    email = '$email',
                    phone = '$phone',
                    address = '$address',
                    role = '$role',
                    is_active = $is_active";

            // Nếu có mật khẩu mới thì cập nhật
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql .= ", password = '$password'";
            }

            $sql .= " WHERE id = '$id'";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Cập nhật thông tin người dùng thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi cập nhật người dùng: " . mysqli_error($conn);
            }

            header('Location: admin.php');
            exit();
            break;

        case 'delete_user':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            
            // Kiểm tra không cho xóa tài khoản admin cuối cùng
            $check_admin = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
            $admin_result = mysqli_query($conn, $check_admin);
            $admin_count = mysqli_fetch_assoc($admin_result)['admin_count'];
            
            $user_check = "SELECT role FROM users WHERE id = '$id'";
            $user_result = mysqli_query($conn, $user_check);
            $user_role = mysqli_fetch_assoc($user_result)['role'];
            
            if ($admin_count <= 1 && $user_role == 'admin') {
                $_SESSION['error_message'] = "Không thể xóa admin cuối cùng của hệ thống";
                header('Location: admin.php');
                exit();
            }
            
            // Thực hiện xóa user
            $sql = "DELETE FROM users WHERE id = '$id'";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Xóa người dùng thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa người dùng: " . mysqli_error($conn);
            }
            
            header('Location: admin.php');
            exit();
            break;

        case 'toggle_user_status':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            
            // Kiểm tra role của user
            $user_check = "SELECT role, is_active FROM users WHERE id = '$id'";
            $user_result = mysqli_query($conn, $user_check);
            $user = mysqli_fetch_assoc($user_result);
            
            // Không cho phép khoá tài khoản admin
            if ($user['role'] == 'admin') {
                $_SESSION['error_message'] = "Không thể khoá tài khoản admin";
                header('Location: admin.php');
                exit();
            }
            
            // Toggle trạng thái
            $new_status = $user['is_active'] ? 0 : 1;
            $sql = "UPDATE users SET is_active = $new_status WHERE id = '$id'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = $new_status ? "Đã mở khoá tài khoản người dùng" : "Đã khoá tài khoản người dùng";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi thay đổi trạng thái người dùng: " . mysqli_error($conn);
            }
            
            header('Location: admin.php');
            exit();
            break;

        case 'add_user':
            // Lấy dữ liệu từ form
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
            $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Kiểm tra email đã tồn tại chưa
            $check_email = "SELECT COUNT(*) as count FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $check_email);
            $count = mysqli_fetch_assoc($result)['count'];

            if ($count > 0) {
                $_SESSION['error_message'] = "Email đã tồn tại trong hệ thống";
                header('Location: admin.php');
                exit();
            }

            // Thêm user mới
            $sql = "INSERT INTO users (name, email, password, phone, address, role, is_active, created_at) 
                    VALUES ('$name', '$email', '$password', '$phone', '$address', '$role', $is_active, NOW())";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['success_message'] = "Thêm người dùng mới thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi thêm người dùng: " . mysqli_error($conn);
            }

            header('Location: admin.php');
            exit();
            break;

        case 'update_order_status':
            $order_id = (int)$_POST['order_id'];
            $status = $_POST['status'];
            
            // Kiểm tra trạng thái hợp lệ
            $valid_statuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                $_SESSION['error_message'] = "Trạng thái không hợp lệ";
                header('Location: admin.php?section=order-management-section');
                exit();
            }
            
            // Cập nhật trạng thái
            $sql = "UPDATE orders SET status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Cập nhật trạng thái đơn hàng thành công";
            } else {
                $_SESSION['error_message'] = "Có lỗi xảy ra khi cập nhật trạng thái đơn hàng: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($stmt);
            header('Location: admin.php?section=order-management-section');
            exit();
            break;

        case 'force_delete_product':
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            
            // Kiểm tra xem sản phẩm có bị ẩn không
            $check_sql = "SELECT is_active, image FROM products WHERE id = '$id'";
            $check_result = mysqli_query($conn, $check_sql);
            $product = mysqli_fetch_assoc($check_result);
            
            if ($product && $product['is_active'] == 0) {
                // Xóa các bản ghi liên quan trong reviews trước
                $delete_reviews = "DELETE FROM reviews WHERE product_id = '$id'";
                mysqli_query($conn, $delete_reviews);

                // Xóa các bản ghi liên quan trong order_items
                $delete_order_items = "DELETE FROM order_items WHERE product_id = '$id'";
                if (mysqli_query($conn, $delete_order_items)) {
                    // Sau đó xóa sản phẩm
                    $delete_product = "DELETE FROM products WHERE id = '$id'";
                    if (mysqli_query($conn, $delete_product)) {
                        // Xóa file ảnh nếu tồn tại
                        if (!empty($product['image'])) {
                            $image_path = 'images/' . $product['image'];
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                        $_SESSION['success_message'] = "Đã xóa vĩnh viễn sản phẩm thành công";
                    } else {
                        $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa sản phẩm: " . mysqli_error($conn);
                    }
                } else {
                    $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa dữ liệu đơn hàng: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error_message'] = "Chỉ có thể xóa vĩnh viễn sản phẩm đã bị ẩn";
            }
            
            header('Location: admin.php?section=product-management-section');
            exit();
            break;
    }
}

// Xử lý các action GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_product_edit':
            $id = mysqli_real_escape_string($conn, $_GET['id']);
            $sql = "SELECT * FROM products WHERE id = '$id'";
            $result = mysqli_query($conn, $sql);
            
            if ($result && $product = mysqli_fetch_assoc($result)) {
                $_SESSION['edit_product'] = $product;
                $_SESSION['show_edit_product_modal'] = true;
            }
            
            header('Location: admin.php?section=product-management-section');
            exit();
            break;
    }
}
?> 
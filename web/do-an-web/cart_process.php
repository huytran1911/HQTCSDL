<?php
session_start();
require_once 'db/database.php';


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
  
    if ($action == 'add') {
        if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
            $_SESSION['error'] = 'Thiếu thông tin sản phẩm';
            header('Location: index.php');
            exit();
        }
        
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
       
        if ($quantity <= 0) {
            $quantity = 1;
        }
        
      
        $product_query = "SELECT * FROM products WHERE id = $product_id AND is_active = 1";
        $product_result = mysqli_query($conn, $product_query);
        
        if (mysqli_num_rows($product_result) == 0) {
            $_SESSION['error'] = 'Sản phẩm không tồn tại hoặc đã bị ẩn bởi admin';
            header('Location: index.php');
            exit();
        }
        
        $product = mysqli_fetch_assoc($product_result);
        
 
        if ($product['stock'] < $quantity) {
            $_SESSION['error'] = 'Số lượng mua vượt quá số lượng còn lại';
            header('Location: product.php?id=' . $product_id);
            exit();
        }
        
       
        $found = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                // Update quantity
                $new_quantity = $item['quantity'] + $quantity;
                
            
                if ($new_quantity > $product['stock']) {
                    $new_quantity = $product['stock'];
                    $_SESSION['note'] = 'Đã cập nhật số lượng tối đa có thể mua';
                }
                
                $_SESSION['cart'][$key]['quantity'] = $new_quantity;
                $found = true;
                break;
            }
        }
        
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        
        $_SESSION['success'] = 'Đã thêm sản phẩm vào giỏ hàng';
        
       
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'cart.php';
        header("Location: $redirect");
        exit();
    }
    
   
    elseif ($action == 'update') {
        if (!isset($_POST['cart_key']) || !isset($_POST['quantity'])) {
            $_SESSION['error'] = 'Thiếu thông tin cập nhật';
            header('Location: cart.php');
            exit();
        }
        
        $cart_key = (int)$_POST['cart_key'];
        $quantity = (int)$_POST['quantity'];
        
       
        if (!isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['error'] = 'Sản phẩm không tồn tại trong giỏ hàng';
            header('Location: cart.php');
            exit();
        }
        
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$cart_key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $_SESSION['success'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
        } else {
          
            $product_id = $_SESSION['cart'][$cart_key]['product_id'];
            $product_query = "SELECT stock, is_active FROM products WHERE id = $product_id";
            $product_result = mysqli_query($conn, $product_query);
            
            if (mysqli_num_rows($product_result) == 0) {
           
                unset($_SESSION['cart'][$cart_key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); 
                $_SESSION['error'] = 'Sản phẩm không còn tồn tại trong hệ thống, đã bị xóa khỏi giỏ hàng';
                header('Location: cart.php');
                exit();
            }
            
            $product = mysqli_fetch_assoc($product_result);
            
            if ($product['is_active'] == 0) {
           
                unset($_SESSION['cart'][$cart_key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); 
                $_SESSION['error'] = 'Sản phẩm đã bị ẩn bởi admin, đã bị xóa khỏi giỏ hàng';
                header('Location: cart.php');
                exit();
            }
            
            if ($quantity > $product['stock']) {
                $quantity = $product['stock'];
                $_SESSION['note'] = 'Đã cập nhật số lượng tối đa có thể mua';
            }
            
            $_SESSION['cart'][$cart_key]['quantity'] = $quantity;
            $_SESSION['success'] = 'Đã cập nhật số lượng sản phẩm';
        }
        
        header('Location: cart.php');
        exit();
    }
    
  
    elseif ($action == 'remove') {
        if (!isset($_POST['cart_key'])) {
            $_SESSION['error'] = 'Thiếu thông tin xóa';
            header('Location: cart.php');
            exit();
        }
        
        $cart_key = (int)$_POST['cart_key'];
        
     
        if (!isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['error'] = 'Sản phẩm không tồn tại trong giỏ hàng';
            header('Location: cart.php');
            exit();
        }
        
       
        unset($_SESSION['cart'][$cart_key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); 
        $_SESSION['success'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
        
        header('Location: cart.php');
        exit();
    }
    
 
    elseif ($action == 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = 'Đã xóa toàn bộ giỏ hàng';
        
        header('Location: cart.php');
        exit();
    }
} else {
   
    header('Location: index.php');
    exit();
}
?> 
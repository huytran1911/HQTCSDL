


function updateQuantity(input, productId) {
    const quantity = parseInt(input.value);
    if (quantity <= 0) {
        alert('Số lượng phải lớn hơn 0');
        input.value = 1;
        return;
    }

   
    const form = input.closest('form');
    form.submit();
}


function confirmDelete(productId) {
    return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');
}


function confirmCancelOrder(orderId) {
    return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');
}


function validateRegisterForm() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (password !== confirmPassword) {
        alert('Mật khẩu xác nhận không khớp!');
        return false;
    }
    return true;
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}


function validateAdvancedSearch() {
    const minPrice = document.getElementById('min_price').value;
    const maxPrice = document.getElementById('max_price').value;
    
    if (minPrice && maxPrice && parseInt(minPrice) > parseInt(maxPrice)) {
        alert('Giá thấp nhất không thể lớn hơn giá cao nhất!');
        return false;
    }
    return true;
}


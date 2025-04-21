<?php
// Lấy section hiện tại từ URL hoặc mặc định là dashboard
$current_section = $_GET['section'] ?? 'dashboard-section';

$menu_items = [
    [
        'id' => 'dashboard-section',
        'icon' => 'bi-house-door',
        'text' => 'Bảng điều khiển'
    ],
    [
        'id' => 'user-management-section',
        'icon' => 'bi-people',
        'text' => 'Quản lý người dùng'
    ],
    [
        'id' => 'product-management-section',
        'icon' => 'bi-box-seam',
        'text' => 'Quản lý sản phẩm'
    ],
    [
        'id' => 'order-management-section',
        'icon' => 'bi-cart',
        'text' => 'Quản lý đơn hàng'
    ],
    [
        'id' => 'statistics-section',
        'icon' => 'bi-graph-up',
        'text' => 'Thống kê kinh doanh'
    ]
];
?>

<nav class="navbar-mobile d-md-none">
    <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <span class="navbar-brand">Quản trị</span>
</nav>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <?php foreach ($menu_items as $item): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $item['id'] === $current_section ? 'active' : ''; ?>" 
                   href="#<?php echo $item['id']; ?>" 
                   onclick="switchSection('<?php echo $item['id']; ?>')">
                    <i class="bi <?php echo $item['icon']; ?>"></i>
                    <?php echo $item['text']; ?>
                </a>
            </li>
            <?php endforeach; ?>
            
            <!-- Nút đăng xuất -->
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="admin_logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Đăng xuất
                </a>
            </li>
        </ul>
    </div>
</nav>

<script>
function switchSection(sectionId) {
    // Ẩn tất cả các section
    document.querySelectorAll('main > div[id$="-section"]').forEach(section => {
        section.style.display = 'none';
    });
    
    // Hiển thị section được chọn
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.style.display = 'block';
    }
    
    // Cập nhật trạng thái active cho menu
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelector(`a[href="#${sectionId}"]`).classList.add('active');
    
    // Cập nhật URL mà không reload trang
    window.history.pushState({section: sectionId}, '', `?section=${sectionId}`);
    
    // Nếu là section thống kê thì cập nhật biểu đồ
    if (sectionId === 'statistics-section' && typeof updateCharts === 'function') {
        updateCharts();
    }
    
    // Đóng sidebar trên mobile sau khi chọn
    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.remove('show');
    }
}

// Xử lý khi người dùng sử dụng nút back/forward của trình duyệt
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.section) {
        switchSection(event.state.section);
    }
});

// Khởi tạo section ban đầu
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section') || 'dashboard-section';
    switchSection(section);
});
</script> 
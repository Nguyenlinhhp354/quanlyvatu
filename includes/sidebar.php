<?php
// Truy vấn lấy id_vai_tro của người dùng đang đăng nhập
$role_id = 0;
if (isset($_SESSION['id_nguoi_dung'])) {
    $user_id_sidebar = $_SESSION['id_nguoi_dung'];
    // Biến $conn đã được gọi ở các file trang chính trước khi include sidebar
    $role_query = mysqli_query($conn, "SELECT id_vai_tro FROM nguoi_dung WHERE id_nguoi_dung='$user_id_sidebar'");
    if ($role_row = mysqli_fetch_assoc($role_query)) {
        $role_id = intval($role_row['id_vai_tro']);
    }
}
?>

<aside class="sidebar" id="thanhMenu">
    <ul class="menu-top">
        
        <?php if (in_array($role_id, [1])): ?>
            <li><a href="/qlht_index.php">Quản lý hệ thống</a></li>
        <?php endif; ?>
        
        <?php if (in_array($role_id, [1, 3, 6])): ?>
            <li><a href="/QLDM.php">Quản lý danh mục</a></li>
        <?php endif; ?>
        
        <?php if (in_array($role_id, [1, 2, 5, 6])): ?>
            <li><a href="/QLquytrinhdieuphoi.php">Quản lý quy trình</a></li>
        <?php endif; ?>
        
        <?php if (in_array($role_id, [1, 3, 5])): ?>
            <li><a href="/QLkho.php">Quản lý kho</a></li>
        <?php endif; ?>
        
        <?php if (in_array($role_id, [1, 2, 4])): ?>
            <li><a href="/bctk_index.php">Báo cáo - Thống kê</a></li>
        <?php endif; ?>

    </ul>

    <ul class="menu-bottom">
        <li><a href="/doi_mat_khau.php">Đổi mật khẩu</a></li>
        <li><a href="/logout.php" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">Đăng xuất</a></li>
    </ul>
</aside>
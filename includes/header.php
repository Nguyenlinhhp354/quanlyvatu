<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- ĐOẠN CODE BỔ SUNG: LẤY HỌ TÊN TỪ DATABASE ĐỂ TRUYỀN VÀO HEADER ---
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result_user = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result_user)) {
    $ho_ten = $row['ho_ten']; // Lấy họ tên gán vào biến $ho_ten
} else {
    $ho_ten = "Admin"; // Fallback dự phòng nếu có lỗi
}
?>
<header>
    <div class="header-left">
        <button class="btn-toggle" onclick="dongMoSidebar()">☰</button>
    </div>
    <div class="header-center">
        Quản lý vật tư Thịnh Tiến
    </div>
    <div class="header-right">
        Xin chào, <?php echo $ho_ten; ?>! <!-- Hiển thị tên người dùng lấy từ session -->
    </div>
</header>

<script>
    function dongMoSidebar() {
        var sidebar = document.getElementById("thanhMenu");
        sidebar.classList.toggle("an-di");
    }
</script>
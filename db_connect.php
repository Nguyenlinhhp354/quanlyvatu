<?php
// Thông tin kết nối từ cấu hình Hosting ProFreeHost
$servername = "sql313.ezyro.com";
$username   = "ezyro_41401130";

// LƯU Ý: Bạn cần thay đổi 2 biến dưới đây cho đúng với tài khoản của bạn
$password   = "1234567890"; // Đây là mật khẩu đăng nhập vào vPanel của ProFreeHost
$dbname     = "ezyro_41401130_quan_ly_vat_tu_thinhtien"; // Tên database bạn đã tạo trong phần MySQL Databases

// 1. Tạo kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. Thiết lập charset để hỗ trợ lưu/đọc tiếng Việt có dấu
if (!$conn->set_charset("utf8mb4")) {
    printf("Lỗi khi thiết lập charset utf8mb4: %s\n", $conn->error);
    exit();
}

// 3. Kiểm tra kết nối
if ($conn->connect_error) {
    // Nếu kết nối thất bại, dừng chương trình và in ra lỗi
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}

// Nếu kết nối thành công, file này sẽ im lặng (không in ra gì cả)
// Các file khác khi include file này sẽ sử dụng được biến $conn
?>
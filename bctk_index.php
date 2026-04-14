<?php
include 'db_connect.php'; // Kết nối đến database
session_start();

// Giả sử đây là đoạn bạn kiểm tra tài khoản và mật khẩu thành công
$tai_khoan_dung = true;

if ($_SESSION['loggedin'] == false) {
    // Phát thẻ bài (Tạo session xác nhận đã đăng nhập)
    $id_nguoi_dung = $_SESSION['id_nguoi_dung']; // Lấy ID người dùng từ session
    $sql = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
    // Chuyển hướng vào trang chủ
    $result = mysqli_query($conn, $sql);
    $ho_ten = $result['ho_ten']; // Lấy họ tên người dùng để hiển thị lên Header
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                
            </main>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    

</body>
</html>

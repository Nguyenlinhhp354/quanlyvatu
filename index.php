<?php
session_start();
include 'db_connect.php'; // Kết nối đến database


// Giả sử đây là đoạn bạn kiểm tra tài khoản và mật khẩu thành công
$tai_khoan_dung = true;
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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

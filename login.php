<?php
// login.php
include 'db_connect.php'; // Kết nối đến database
session_start();
if (isset($_POST)) {
    $tai_khoan = $_POST['username'];
    $mat_khau = $_POST['password'];
    $sql = "SELECT * FROM nguoi_dung WHERE tai_khoan='$tai_khoan' AND mat_khau='$mat_khau'";
    // Kiểm tra thông tin đăng nhập (ví dụ: username: admin, password: admin123)
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) === 1) {
        $_SESSION['loggedin'] = true;
        $_SESSION['id_nguoi_dung'] = $result['id_nguoi_dung']; // Lưu ID người dùng vào session để hiển thị lên Header
        header('Location: index.php');
        exit();
    } else {
        echo "<script>alert('Tên đăng nhập hoặc mật khẩu không đúng!');</script>";
    }
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống quản lý vật tư Thịnh Tiến</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Đăng nhập</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
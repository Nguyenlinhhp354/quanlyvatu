<?php
// login.php
session_start();
if (isset($_POST)) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kiểm tra thông tin đăng nhập (ví dụ: username: admin, password: admin123)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['loggedin'] = true;
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
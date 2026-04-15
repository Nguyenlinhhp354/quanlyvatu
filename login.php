<?php
// login.php
session_start();
include 'db_connect.php'; // Kết nối đến database

// Biến lưu trữ thông báo lỗi (nếu có)
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SỬ DỤNG mysqli_real_escape_string ĐỂ CHỐNG HACK (SQL Injection)
    $tai_khoan = mysqli_real_escape_string($conn, $_POST['username']);
    $mat_khau = mysqli_real_escape_string($conn, $_POST['password']);
    
    $sql = "SELECT * FROM nguoi_dung WHERE tai_khoan='$tai_khoan' AND mat_khau='$mat_khau'";
    $result = mysqli_query($conn, $sql);
    
    // Nếu tìm thấy đúng 1 tài khoản trùng khớp
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        $_SESSION['loggedin'] = true;
        $_SESSION['id_nguoi_dung'] = $row['id_nguoi_dung']; 
        
        header('Location: index.php');
        exit();
    } else {
        // Gán thông báo lỗi thay vì dùng alert()
        $error_msg = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản lý vật tư Thịnh Tiến</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    
    <style>
        /* Ghi đè CSS cấu trúc riêng cho trang đăng nhập */
        body {
            background: linear-gradient(135deg, #343a40 0%, #121416 100%); /* Nền gradient tối sang trọng */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        
        .login-wrapper {
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); /* Đổ bóng đậm giúp form nổi bật */
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-wrapper h2 {
            margin: 0 0 5px;
            color: #333;
            font-size: 26px;
        }

        .login-wrapper p.subtitle {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box; /* Ép input không tràn viền */
        }

        .form-group input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0,123,255,0.25);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #0056b3;
        }

        /* Khung thông báo lỗi báo đỏ */
        .error-alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <h2>Thịnh Tiến MM</h2>
        <p class="subtitle">Hệ thống Quản lý Vật tư</p>
        
        <?php if(!empty($error_msg)): ?>
            <div class="error-alert">
                ⚠ <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Tên tài khoản</label>
                <input type="text" id="username" name="username" placeholder="Nhập tài khoản..." required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." required>
            </div>
            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
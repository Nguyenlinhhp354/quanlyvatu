<?php
session_start();
include 'db_connect.php';
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
    <title>Quản lý hệ thống - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .menu-dashboard {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }
        .menu-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 40px 30px;
            width: 300px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        .menu-card h3 {
            margin: 0 0 15px 0; /* Đã điều chỉnh lại margin vì bỏ icon */
            font-size: 22px;
            color: #007bff; /* Thêm chút màu xanh vào chữ để tạo điểm nhấn nhẹ */
        }
        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <h2 style="margin-bottom: 30px;">Quản lý hệ thống</h2>
                <div class="menu-dashboard">
                    <a href="qlht_nguoi_dung.php" class="menu-card">
                        <h3>Quản lý Người dùng</h3>
                        <p>Thêm mới, sửa thông tin cá nhân, xóa và tìm kiếm người dùng trong hệ thống.</p>
                    </a>

                    <a href="qlht_phan_quyen.php" class="menu-card">
                        <h3>Phân quyền Hệ thống</h3>
                        <p>Thay đổi và cấp quyền truy cập, gán vai trò (Admin, Thủ kho, Kế toán...) cho tài khoản.</p>
                    </a>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>
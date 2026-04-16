<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result_user = mysqli_query($conn, $sql_user);

if ($row_user = mysqli_fetch_assoc($result_user)) {
    $ho_ten = $row_user['ho_ten'];
} else {
    $ho_ten = "Admin";
}

// Mảng danh mục Quản lý kho dựa trên sơ đồ 4.1 - 4.6
$cacdanhmuckho = [
    ["tieude" => "Quản lí phiếu nhập kho", "lienket" => "quanlyphieunhapkho.php"],
    ["tieude" => "Quản lí phiếu xuất kho", "lienket" => "quanlyphieuxuatkho.php"],
    ["tieude" => "Quản lí phiếu điều chuyển vật tư", "lienket" => "quanlyphieudieuchuyen.php"],
    ["tieude" => "Quản lí phiếu kiểm kê", "lienket" => "qlk_qlpkk.php"],
    ["tieude" => "Quản lí phiếu xử lý vật tư sự cố", "lienket" => "quanlyvattusuco.php"],
    ["tieude" => "Quản lí tồn kho", "lienket" => "qlk_qltk.php"]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý kho - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* CSS đồng bộ với trang Quản lý danh mục */
        .khung-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* 2 cột */
            gap: 20px;
            max-width: 1000px;
            margin: 20px auto;
        }
        .o-chuc-nang {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 120px;
            background: #ffffff;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            font-size: 18px;
            font-weight: 500;
            transition: all 0.2s;
            border-radius: 4px;
            text-align: center;
            padding: 10px;
        }
        .o-chuc-nang:hover {
            background: #f0f0f0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main style="padding: 20px;">
                <div style="margin-bottom: 30px;">
                    <h2 style="color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                        Quản lý kho
                    </h2>
                </div>

                <div class="khung-grid">
                    <?php foreach($cacdanhmuckho as $dm): ?>
                        <a href="<?php echo $dm['lienket']; ?>" class="o-chuc-nang">
                            <?php echo $dm['tieude']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </main>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>
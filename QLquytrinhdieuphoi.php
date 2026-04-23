<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// --- 1. KIỂM TRA ĐĂNG NHẬP ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result_user = mysqli_query($conn, $sql_user);
$ho_ten = ($row_user = mysqli_fetch_assoc($result_user)) ? $row_user['ho_ten'] : "Admin";

// --- 2. DANH SÁCH CHỨC NĂNG (Theo sơ đồ quy trình) ---
// Đã loại bỏ số 3.1, 3.2... để giống mẫu
$quytrinh = [
    ["tieude" => "Quản lý phiếu yêu cầu vật tư", "lienket" => "qlqt_phieu_yeu_cau.php"],
    ["tieude" => "Phê duyệt yêu cầu vật tư", "lienket" => "qlqt_phe_duyet.php"],
    ["tieude" => "Quản lý phiếu đề nghị mua hàng", "lienket" => "quanlyphieudenghi.php"],
    ["tieude" => "Theo dõi lịch trình cung ứng vật tư", "lienket" => "theodoicungung.php"],
    ["tieude" => "Quản lý phiếu hoàn trả vật tư", "lienket" => "quanlyphieuhoantra.php"],
    ["tieude" => "Quản lý dự án", "lienket" => "quanlyduan.php"]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quy trình và điều phối - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* --- 3. CSS TỐI GIẢN (GIỐNG QLDM.PHP) --- */
        .khung-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Chia 2 cột đều nhau */
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
            padding: 0 15px;
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
                <div style="margin-bottom: 20px;">
                    <h2 style="color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                        Quản lý quy trình và điều phối
                    </h2>
                </div>

                <div class="khung-grid">
                    <?php foreach($quytrinh as $qt): ?>
                        <a href="<?php echo $qt['lienket']; ?>" class="o-chuc-nang">
                            <?php echo $qt['tieude']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </main>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>
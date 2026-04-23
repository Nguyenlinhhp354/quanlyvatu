<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
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
    <title>Báo cáo Thống kê - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <h2 class="page-title">Báo cáo - Thống kê</h2>
                
                <div class="report-grid">
                    
                    <a href="bctk_nhap_xuat.php" class="report-card">
                        <h3>Thống kê Nhập - Xuất - Tồn Kho</h3>
                        <p>Xem tổng quan và chi tiết biến động số lượng vật tư trong kho.</p>
                    </a>
                    
                    <a href="bctk_suco.php" class="report-card">
                        <h3>Báo cáo chi tiết vật tư sự cố</h3>
                        <p>Theo dõi danh sách vật tư hỏng hóc, mất mát hoặc cần bảo hành.</p>
                    </a>
                    
                    <a href="bctk_chenhlechkiemke.php" class="report-card">
                        <h3>Báo cáo đối chiếu chênh lệch kiểm kê</h3>
                        <p>So sánh số liệu trên hệ thống phần mềm và thực tế ngoài kho.</p>
                    </a>
                    
                    <a href="#" class="report-card">
                        <h3>Thống kê chi phí tiêu hao vật tư</h3>
                        <p>Phân tích giá trị và chi phí tiêu hao theo phòng ban, dự án.</p>
                    </a>

                </div>
            </main>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

</body>
</html>
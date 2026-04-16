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
    <title>Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="page-title" style="margin: 0;">Thống kê Nhập - Xuất - Tồn Kho</h2>
                    <a href="bctk_index.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;">&laquo; Quay lại Báo cáo</a>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">STT</th>
                                <th width="15%" class="text-center">Mã vật tư</th>
                                <th width="30%" class="text-left">Tên vật tư</th>
                                <th width="10%" class="text-center">ĐVT</th>
                                <th width="10%" class="text-center">Tổng Nhập</th>
                                <th width="10%" class="text-center">Tổng Xuất</th>
                                <th width="20%" class="text-center">Tồn Kho Hiện Tại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Câu lệnh SQL "Thần thánh": Gom nhóm (SUM) số lượng nhập, xuất và trừ đi để ra Tồn Kho
                            $sql = "SELECT 
                                        vt.ma_vat_tu, 
                                        vt.ten_vat_tu, 
                                        dvt.ten_don_vi_tinh,
                                        IFNULL(nhap.tong_nhap, 0) AS tong_nhap,
                                        IFNULL(xuat.tong_xuat, 0) AS tong_xuat,
                                        (IFNULL(nhap.tong_nhap, 0) - IFNULL(xuat.tong_xuat, 0)) AS ton_kho
                                    FROM vat_tu vt
                                    LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                    
                                    -- Subquery 1: Tính tổng nhập của từng vật tư
                                    LEFT JOIN (
                                        SELECT id_vat_tu, SUM(so_luong) AS tong_nhap 
                                        FROM chi_tiet_nhap_kho 
                                        GROUP BY id_vat_tu
                                    ) nhap ON vt.id_vat_tu = nhap.id_vat_tu
                                    
                                    -- Subquery 2: Tính tổng xuất của từng vật tư
                                    LEFT JOIN (
                                        SELECT id_vat_tu, SUM(so_luong) AS tong_xuat 
                                        FROM chi_tiet_xuat_kho 
                                        GROUP BY id_vat_tu
                                    ) xuat ON vt.id_vat_tu = xuat.id_vat_tu
                                    
                                    ORDER BY vt.ten_vat_tu ASC";
                                    
                            $result = mysqli_query($conn, $sql);
                            $stt = 1;
                            
                            // Đổ dữ liệu ra bảng
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    
                                    // Xử lý màu sắc: Tồn kho <= 0 thì hiện Đỏ (Cảnh báo), còn hàng thì hiện Xanh
                                    $ton_kho = $row['ton_kho'];
                                    $color_style = ($ton_kho <= 0) ? "color: #dc3545; font-weight: bold;" : "color: #28a745; font-weight: bold;";
                                    
                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $stt++ . "</td>";
                                    echo "<td class='text-center'>" . $row['ma_vat_tu'] . "</td>";
                                    echo "<td class='text-left'>" . $row['ten_vat_tu'] . "</td>";
                                    echo "<td class='text-center'>" . $row['ten_don_vi_tinh'] . "</td>";
                                    // Dùng number_format để hiển thị số đẹp hơn (ví dụ: 10.000 thay vì 10000)
                                    echo "<td class='text-center'>" . number_format($row['tong_nhap'], 0, ',', '.') . "</td>";
                                    echo "<td class='text-center'>" . number_format($row['tong_xuat'], 0, ',', '.') . "</td>";
                                    echo "<td class='text-center' style='$color_style'>" . number_format($ton_kho, 0, ',', '.') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>Chưa có dữ liệu vật tư nào trong hệ thống.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>

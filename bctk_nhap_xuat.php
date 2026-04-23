<?php
session_start();
include 'db_connect.php'; 

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// =========================================================================
// 1. XỬ LÝ BIẾN LỌC (TÌM KIẾM VÀ NGÀY THÁNG)
// =========================================================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$tu_ngay = isset($_GET['tu_ngay']) ? trim($_GET['tu_ngay']) : "";
$den_ngay = isset($_GET['den_ngay']) ? trim($_GET['den_ngay']) : "";

$w_nhap = "1=1";
$w_xuat = "1=1";
$where_vt = "1=1";
$has_date_filter = false; 

// Ràng buộc thời gian an toàn
if ($tu_ngay != "") {
    $tu_ngay_db = mysqli_real_escape_string($conn, $tu_ngay . " 00:00:00");
    $w_nhap .= " AND p.ngay_nhap >= '$tu_ngay_db'";
    $w_xuat .= " AND p.ngay_xuat >= '$tu_ngay_db'";
    $has_date_filter = true;
}
if ($den_ngay != "") {
    $den_ngay_db = mysqli_real_escape_string($conn, $den_ngay . " 23:59:59");
    $w_nhap .= " AND p.ngay_nhap <= '$den_ngay_db'";
    $w_xuat .= " AND p.ngay_xuat <= '$den_ngay_db'";
    $has_date_filter = true;
}

// Ràng buộc tìm kiếm vật tư
if ($search != "") {
    $where_vt .= " AND (vt.ma_vat_tu LIKE '%$search%' OR vt.ten_vat_tu LIKE '%$search%')";
}

// =========================================================================
// 2. CÂU TRUY VẤN TỔNG HỢP (GỘP TỔNG NHẬP, TỔNG XUẤT, TỒN KHO)
// =========================================================================
$sql = "SELECT 
            vt.id_vat_tu, vt.ma_vat_tu, vt.ten_vat_tu, 
            dvt.ten_don_vi_tinh,
            IFNULL(nhap.tong_nhap, 0) AS tong_nhap,
            IFNULL(xuat.tong_xuat, 0) AS tong_xuat,
            IFNULL(tk.tong_ton, 0) AS ton_kho
        FROM vat_tu vt
        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
        
        -- Lấy Tổng Nhập trong kỳ
        LEFT JOIN (
            SELECT ct.id_vat_tu, SUM(ct.so_luong) AS tong_nhap 
            FROM chi_tiet_nhap_kho ct
            LEFT JOIN phieu_nhap_kho p ON ct.id_phieu_nhap = p.id_phieu_nhap
            WHERE $w_nhap
            GROUP BY ct.id_vat_tu
        ) nhap ON vt.id_vat_tu = nhap.id_vat_tu
        
        -- Lấy Tổng Xuất trong kỳ
        LEFT JOIN (
            SELECT ct.id_vat_tu, SUM(ct.so_luong) AS tong_xuat 
            FROM chi_tiet_xuat_kho ct
            LEFT JOIN phieu_xuat_kho p ON ct.id_phieu_xuat = p.id_phieu_xuat
            WHERE $w_xuat
            GROUP BY ct.id_vat_tu
        ) xuat ON vt.id_vat_tu = xuat.id_vat_tu
        
        -- Lấy Tồn kho thực tế hiện tại
        LEFT JOIN (
            SELECT id_vat_tu, SUM(so_luong_ton) AS tong_ton
            FROM ton_kho
            GROUP BY id_vat_tu
        ) tk ON vt.id_vat_tu = tk.id_vat_tu
        
        WHERE $where_vt
        ORDER BY vt.ten_vat_tu ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê Nhập Xuất Tồn - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-wrapper { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; align-items: center;}
        .filter-wrapper input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .filter-wrapper input:focus { border-color: #007bff; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-search:hover { background: #0056b3; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .btn-print { padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: auto; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 10px; border: 1px solid #e0e0e0; vertical-align: middle; }
        .data-table thead th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 13px; text-align: center; }

        .badge-danger { background-color: #dc3545; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }

        /* IN ẤN BÁO CÁO */
        @media print {
            body * { visibility: hidden; }
            .main-content, .main-content * { visibility: visible; }
            .main-content { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; }
            .filter-wrapper, .toolbar a, .btn-print { display: none !important; }
            .data-table { border: 1px solid #000; box-shadow: none; }
            .data-table th, .data-table td { border: 1px solid #000; color: #000; }
            .data-table thead th { background-color: #f2f2f2 !important; color: #000; -webkit-print-color-adjust: exact; }
            .page-title::before { content: "CÔNG TY CỔ PHẦN THỊNH TIẾN\n"; display: block; font-size: 14px; text-align: center; margin-bottom: 20px; font-weight: normal; }
            .page-title { text-align: center; margin-bottom: 30px !important; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>

                <div class="toolbar">
                    <h2 class="page-title" style="margin: 0; color: #007bff;">Thống kê Nhập - Xuất - Tồn Kho</h2>
                    <a href="bctk_index.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;">&laquo; Quay lại</a>
                </div>

                <form class="filter-wrapper" method="GET" action="bctk_nhap_xuat.php">
                    <input type="text" name="search" placeholder="Nhập mã hoặc tên vật tư..." value="<?=htmlspecialchars($search)?>" style="width: 250px;">
                    
                    <span style="font-weight: bold; color: #555;">Từ ngày:</span>
                    <input type="date" name="tu_ngay" value="<?=htmlspecialchars($tu_ngay)?>">
                    
                    <span style="font-weight: bold; color: #555;">Đến ngày:</span>
                    <input type="date" name="den_ngay" value="<?=htmlspecialchars($den_ngay)?>">

                    <button type="submit" class="btn-search">Lọc dữ liệu</button>
                    
                    <?php if($search != "" || $tu_ngay != "" || $den_ngay != ""): ?>
                        <a href="bctk_nhap_xuat.php" class="btn-clear">Xóa lọc</a>
                    <?php endif; ?>

                    <button type="button" class="btn-print" onclick="window.print()">🖨 Xuất Báo Cáo</button>
                </form>

                <div id="table-container" style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="5%">STT</th>
                                <th width="15%">MÃ VẬT TƯ</th>
                                <th width="30%">TÊN VẬT TƯ</th>
                                <th width="10%">ĐVT</th>
                                <th width="10%">TỔNG NHẬP</th>
                                <th width="10%">TỔNG XUẤT</th>
                                <th width="20%">TỒN KHO HIỆN TẠI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stt = 1;
                            $count_hien_thi = 0; // Biến đếm số dòng thực sự in ra màn hình
                            
                            // KIỂM TRA LỖI KẾT NỐI / SQL
                            if (!$result) {
                                echo "<tr><td colspan='7' class='text-center' style='padding:20px; color:red; font-weight:bold;'>Lỗi truy vấn dữ liệu: " . mysqli_error($conn) . "</td></tr>";
                            } 
                            // NẾU CÓ DỮ LIỆU
                            elseif (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    
                                    // BỘ LỌC PHP: Nếu người dùng đang lọc theo NGÀY, mà vật tư này KHÔNG có nhập/xuất trong ngày đó -> ẨN ĐI
                                    if ($has_date_filter && $row['tong_nhap'] == 0 && $row['tong_xuat'] == 0) {
                                        continue; 
                                    }
                                    
                                    $count_hien_thi++;
                                    
                                    $ma_vt = !empty($row['ma_vat_tu']) ? htmlspecialchars((string)$row['ma_vat_tu']) : "";
                                    $ten_vt = !empty($row['ten_vat_tu']) ? htmlspecialchars((string)$row['ten_vat_tu']) : "";
                                    $dvt = !empty($row['ten_don_vi_tinh']) ? htmlspecialchars((string)$row['ten_don_vi_tinh']) : "-";
                                    
                                    $ton_kho = $row['ton_kho'];
                                    $badge = ($ton_kho <= 0) ? "<span class='badge-danger'>Hết hàng / 0</span>" : "<span style='font-weight:bold; font-size:15px; color:#28a745;'>" . number_format($ton_kho, 0, ',', '.') . "</span>";
                                    
                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $stt++ . "</td>";
                                    echo "<td class='text-center'><strong>" . $ma_vt . "</strong></td>";
                                    echo "<td class='text-left'>" . $ten_vt . "</td>";
                                    echo "<td class='text-center'>" . $dvt . "</td>";
                                    
                                    // Tổng nhập
                                    $nhap_str = ($row['tong_nhap'] > 0) ? "<span style='color:#007bff; font-weight:bold;'>" . number_format($row['tong_nhap'], 0, ',', '.') . "</span>" : "-";
                                    echo "<td class='text-center'>" . $nhap_str . "</td>";
                                    
                                    // Tổng xuất
                                    $xuat_str = ($row['tong_xuat'] > 0) ? "<span style='color:#dc3545; font-weight:bold;'>" . number_format($row['tong_xuat'], 0, ',', '.') . "</span>" : "-";
                                    echo "<td class='text-center'>" . $xuat_str . "</td>";
                                    
                                    // Tồn kho hiện tại
                                    echo "<td class='text-center'>" . $badge . "</td>";
                                    echo "</tr>";
                                }
                            } 
                            
                            // Nếu chạy xong vòng lặp mà không in ra được dòng nào (do bị lọc PHP giấu hết)
                            if ($count_hien_thi == 0 && $result) {
                                echo "<tr><td colspan='7' class='text-center' style='padding:30px; color:#666;'>Không có dữ liệu phát sinh (Nhập/Xuất) nào phù hợp với khoảng thời gian bạn chọn.</td></tr>";
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
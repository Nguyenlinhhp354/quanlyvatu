<?php
session_start();
include 'db_connect.php';

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// KIỂM TRA BẢNG CÓ CỘT ton_thuc_te VÀ id_kho CHƯA (Bắt lỗi logic hệ thống)
$has_ton_thuc_te = false;
$has_id_kho = false;
$check_col1 = @mysqli_query($conn, "SHOW COLUMNS FROM chi_tiet_kiem_ke LIKE 'ton_thuc_te'");
if ($check_col1 && mysqli_num_rows($check_col1) > 0) $has_ton_thuc_te = true;
$check_col2 = @mysqli_query($conn, "SHOW COLUMNS FROM chi_tiet_kiem_ke LIKE 'id_kho'");
if ($check_col2 && mysqli_num_rows($check_col2) > 0) $has_id_kho = true;

if (!$has_ton_thuc_te) {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>🚨 LỖI HỆ THỐNG: Bảng chi_tiet_kiem_ke chưa có cột 'ton_thuc_te'. Vui lòng tạo cột này trong phpMyAdmin trước khi xem báo cáo!</h2>");
}

// ==========================================
// XỬ LÝ BIẾN LỌC
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$tu_ngay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : "";
$den_ngay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : "";
$trang_thai = isset($_GET['trang_thai']) ? $_GET['trang_thai'] : "all"; // all, lech, khop

$where = "1=1";
if ($tu_ngay != "") $where .= " AND DATE(p.ngay_lap) >= '$tu_ngay'";
if ($den_ngay != "") $where .= " AND DATE(p.ngay_lap) <= '$den_ngay'";
if ($search != "") $where .= " AND (vt.ma_vat_tu LIKE '%$search%' OR vt.ten_vat_tu LIKE '%$search%' OR p.so_phieu LIKE '%$search%')";

// CÂU TRUY VẤN
$sql_kho = $has_id_kho ? "LEFT JOIN kho k ON ct.id_kho = k.id_kho" : "LEFT JOIN kho k ON 1=0";
$col_kho = $has_id_kho ? "k.ten_kho" : "'Chưa phân kho' AS ten_kho";

// Chỉ lấy những dòng đã có tồn thực tế (Đã kiểm kê)
$sql = "SELECT 
            p.so_phieu, p.ngay_lap,
            vt.ma_vat_tu, vt.ten_vat_tu, IFNULL(vt.don_gia, 0) AS don_gia,
            dvt.ten_don_vi_tinh,
            $col_kho,
            ct.ton_he_thong, ct.ton_thuc_te
        FROM chi_tiet_kiem_ke ct
        JOIN phieu_kiem_ke p ON ct.id_phieu_kk = p.id_phieu_kk
        JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
        $sql_kho
        WHERE ct.ton_thuc_te IS NOT NULL AND $where
        ORDER BY p.ngay_lap DESC, vt.ten_vat_tu ASC";

$result = mysqli_query($conn, $sql);

// ==========================================
// TÍNH TOÁN DỮ LIỆU BẢNG
// ==========================================
$data_rows = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $diff = floatval($row['ton_thuc_te']) - floatval($row['ton_he_thong']);
        $don_gia = floatval($row['don_gia']);
        $gia_tri_lech = abs($diff) * $don_gia;
        
        $row['diff'] = $diff;
        $row['gia_tri_lech'] = $gia_tri_lech;
        
        // Lọc hiển thị bằng PHP theo Trạng thái
        if ($trang_thai == 'lech' && $diff == 0) continue;
        if ($trang_thai == 'khop' && $diff != 0) continue;
        
        $data_rows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Chênh Lệch Kiểm Kê - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-wrapper { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; align-items: center;}
        .filter-wrapper input, .filter-wrapper select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .btn-print { padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: auto; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; font-size: 13px;}
        .data-table th, .data-table td { padding: 12px 10px; border: 1px solid #e0e0e0; vertical-align: middle; }
        .data-table thead th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 12px; text-align: center; }
        
        @media print {
            body * { visibility: hidden; }
            .main-content, .main-content * { visibility: visible; }
            .main-content { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0;}
            .filter-wrapper, .toolbar a, .btn-print { display: none !important; }
            .data-table { border: 1px solid #000; box-shadow: none; }
            .data-table th, .data-table td { border: 1px solid #000; color: #000; }
            .data-table thead th { background-color: #f2f2f2 !important; color: #000; -webkit-print-color-adjust: exact; }
            .page-title::before { content: "CÔNG TY CỔ PHẦN THỊNH TIẾN\n\n"; display: block; font-size: 16px; text-align: center; margin-bottom: 5px; font-weight: bold; }
            .page-title { text-align: center; margin-bottom: 20px !important; font-size: 24px; color: #000 !important;}
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
                    <h2 class="page-title" style="margin: 0; color: #333;">BÁO CÁO CHÊNH LỆCH KIỂM KÊ</h2>
                    <a href="bctk_index.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 14px;">&laquo; Quay lại Báo cáo</a>
                </div>

                <form class="filter-wrapper" method="GET" action="bctk_chenhlechkiemke.php">
                    <input type="text" name="search" placeholder="Nhập mã, tên VT, Số phiếu..." value="<?=htmlspecialchars($search)?>" style="width: 200px;">
                    
                    <select name="trang_thai" style="font-weight: bold;">
                        <option value="all" <?=($trang_thai=='all')?'selected':''?>>-- Tất cả trạng thái --</option>
                        <option value="lech" <?=($trang_thai=='lech')?'selected':''?> style="color:red;">⚠ Chỉ xem mã bị LỆCH</option>
                        <option value="khop" <?=($trang_thai=='khop')?'selected':''?> style="color:green;">✔ Chỉ xem mã KHỚP</option>
                    </select>

                    <span style="font-weight: bold; color: #555;">Từ:</span>
                    <input type="date" name="tu_ngay" value="<?=htmlspecialchars($tu_ngay)?>">
                    
                    <span style="font-weight: bold; color: #555;">Đến:</span>
                    <input type="date" name="den_ngay" value="<?=htmlspecialchars($den_ngay)?>">

                    <button type="submit" class="btn-search">Lọc dữ liệu</button>
                    
                    <?php if($search != "" || $tu_ngay != "" || $den_ngay != "" || $trang_thai != "all"): ?>
                        <a href="bctk_chenhlechkiemke.php" class="btn-clear">Xóa lọc</a>
                    <?php endif; ?>

                    <button type="button" class="btn-print" onclick="window.print()">🖨 In Báo Cáo</button>
                </form>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="4%">STT</th>
                                <th width="10%">Ngày / Số Phiếu</th>
                                <th width="12%">Mã VT</th>
                                <th width="20%">Tên vật tư</th>
                                <th width="10%">Kiểm tại Kho</th>
                                <th width="8%">Tồn Máy</th>
                                <th width="8%">Thực Tế</th>
                                <th width="8%">Chênh Lệch</th>
                                <th width="12%">Đơn Giá</th>
                                <th width="10%">Giá trị Lệch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($data_rows) > 0) {
                                $stt = 1;
                                foreach ($data_rows as $r) {
                                    $ngay_lap = date('d/m/Y', strtotime($r['ngay_lap']));
                                    $kho = !empty($r['ten_kho']) ? $r['ten_kho'] : "Không rõ";
                                    
                                    // Xử lý màu sắc hiển thị cho sự chênh lệch
                                    $diff_str = "0";
                                    $gia_tri_str = "-";
                                    if ($r['diff'] < 0) {
                                        $diff_str = "<strong style='color:#dc3545; font-size:14px;'>" . $r['diff'] . "</strong>";
                                        $gia_tri_str = "<strong style='color:#dc3545;'>" . number_format($r['gia_tri_lech'], 0, ',', '.') . " đ</strong>";
                                    } elseif ($r['diff'] > 0) {
                                        $diff_str = "<strong style='color:#28a745; font-size:14px;'>+" . $r['diff'] . "</strong>";
                                        $gia_tri_str = "<strong style='color:#28a745;'>" . number_format($r['gia_tri_lech'], 0, ',', '.') . " đ</strong>";
                                    } else {
                                        $diff_str = "<strong style='color:#6c757d;'>Khớp</strong>";
                                    }

                                    echo "<tr>";
                                    echo "<td class='text-center'>{$stt}</td>";
                                    echo "<td class='text-center'>{$ngay_lap}<br><strong style='color:#007bff; font-size:11px;'>{$r['so_phieu']}</strong></td>";
                                    echo "<td class='text-center'><strong>{$r['ma_vat_tu']}</strong></td>";
                                    echo "<td class='text-left'>{$r['ten_vat_tu']} <span style='font-size:11px; color:#888;'>({$r['ten_don_vi_tinh']})</span></td>";
                                    echo "<td class='text-center'>{$kho}</td>";
                                    
                                    echo "<td class='text-center'>{$r['ton_he_thong']}</td>";
                                    echo "<td class='text-center' style='font-weight:bold; font-size:14px;'>{$r['ton_thuc_te']}</td>";
                                    echo "<td class='text-center'>{$diff_str}</td>";
                                    
                                    echo "<td class='text-right'>" . number_format($r['don_gia'], 0, ',', '.') . " đ</td>";
                                    echo "<td class='text-right'>{$gia_tri_str}</td>";
                                    echo "</tr>";
                                    
                                    $stt++;
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center' style='padding:40px; color:#666;'>Không có dữ liệu chênh lệch nào phù hợp với bộ lọc hiện tại.</td></tr>";
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
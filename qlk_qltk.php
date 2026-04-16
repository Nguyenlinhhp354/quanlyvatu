<?php
session_start();
include 'db_connect.php';

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// 1. LẤY THÔNG TIN NGƯỜI ĐĂNG NHẬP
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$ho_ten = ($row_user = mysqli_fetch_assoc(mysqli_query($conn, $sql_user))) ? $row_user['ho_ten'] : "Admin";

// 2. XỬ LÝ BIẾN TÌM KIẾM, LỌC VÀ SẮP XẾP TỪ URL (GET)
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$filter_kho = isset($_GET['kho']) ? mysqli_real_escape_string($conn, $_GET['kho']) : "";

$sort_col = isset($_GET['sort']) ? $_GET['sort'] : 'vt.id_vat_tu'; 
$sort_dir = isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'ASC' : 'DESC';
$next_dir = ($sort_dir == 'ASC') ? 'desc' : 'asc';

// Mảng chứa các cột được phép sắp xếp (để chống SQL Injection)
$allowed_sort_cols = ['vt.ma_vat_tu', 'vt.ten_vat_tu', 'k.ten_kho', 'lvt.ten_loai_vat_tu', 'hsx.ten_hang_san_xuat', 'ton_kho'];
if (!in_array($sort_col, $allowed_sort_cols)) { $sort_col = 'vt.id_vat_tu'; }

// 3. XÂY DỰNG CÂU LỆNH SQL ĐỘNG DỰA VÀO BỘ LỌC
$where_conditions = ["1=1"]; // Điều kiện mặc định luôn đúng

if ($search_query != "") {
    $where_conditions[] = "(vt.ma_vat_tu LIKE '%$search_query%' OR vt.ten_vat_tu LIKE '%$search_query%')";
}
if ($filter_kho != "") {
    $where_conditions[] = "vt.id_kho = '$filter_kho'";
}
$where_sql = implode(" AND ", $where_conditions);

// Câu truy vấn tính Tồn Kho và kết nối các bảng danh mục
$sql_main = "SELECT 
                vt.id_vat_tu, vt.ma_vat_tu, vt.ten_vat_tu, 
                k.ten_kho, 
                lvt.ten_loai_vat_tu, 
                hsx.ten_hang_san_xuat, 
                dvt.ten_don_vi_tinh,
                (IFNULL(nhap.tong_nhap, 0) - IFNULL(xuat.tong_xuat, 0)) AS ton_kho
            FROM vat_tu vt
            LEFT JOIN kho k ON vt.id_kho = k.id_kho
            LEFT JOIN loai_vat_tu lvt ON vt.id_loai_vat_tu = lvt.id_loai_vat_tu
            LEFT JOIN hang_san_xuat hsx ON vt.id_hsx = hsx.id_hsx
            LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
            
            LEFT JOIN (
                SELECT id_vat_tu, SUM(so_luong) AS tong_nhap FROM chi_tiet_nhap_kho GROUP BY id_vat_tu
            ) nhap ON vt.id_vat_tu = nhap.id_vat_tu
            
            LEFT JOIN (
                SELECT id_vat_tu, SUM(so_luong) AS tong_xuat FROM chi_tiet_xuat_kho GROUP BY id_vat_tu
            ) xuat ON vt.id_vat_tu = xuat.id_vat_tu
            
            WHERE $where_sql
            ORDER BY $sort_col $sort_dir";

$result_main = mysqli_query($conn, $sql_main);

// 4. LẤY DANH SÁCH KHO ĐỂ ĐỔ VÀO DROPDOWN LỌC
$sql_list_kho = "SELECT id_kho, ten_kho FROM kho ORDER BY ten_kho ASC";
$result_kho = mysqli_query($conn, $sql_list_kho);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tồn Kho - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;}
        .filter-form { display: flex; gap: 10px; align-items: center; background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-form input, .filter-form select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .filter-form input:focus, .filter-form select:focus { border-color: #007bff; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-search:hover { background: #0056b3; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .btn-clear:hover { background: #5a6268; }
        .sort-link { color: white; text-decoration: none; display: block; }
        .sort-link:hover { color: #ffc107; }
        .badge-danger { background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-success { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <h2 class="page-title">Quản lý Vật tư Tồn Kho</h2>

                <div class="toolbar">
                    <form class="filter-form" method="GET" action="qlk_qltk.php">
                        <input type="text" name="search" placeholder="Nhập mã hoặc tên vật tư..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                        
                        <select name="kho">
                            <option value="">-- Tất cả kho lưu trữ --</option>
                            <?php 
                                while($k = mysqli_fetch_assoc($result_kho)) {
                                    $selected = ($filter_kho == $k['id_kho']) ? 'selected' : '';
                                    echo "<option value='{$k['id_kho']}' $selected>{$k['ten_kho']}</option>";
                                }
                            ?>
                        </select>

                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_col); ?>">
                        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_dir); ?>">

                        <button type="submit" class="btn-search">Lọc / Tìm kiếm</button>
                        
                        <?php if($search_query != "" || $filter_kho != ""): ?>
                            <a href="qlk_qltk.php" class="btn-clear">Xóa bộ lọc</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="10%" class="text-center">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&kho=<?php echo urlencode($filter_kho); ?>&sort=vt.ma_vat_tu&dir=<?php echo $next_dir; ?>">
                                        Mã VT <?php if($sort_col=='vt.ma_vat_tu') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="20%" class="text-left">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&kho=<?php echo urlencode($filter_kho); ?>&sort=vt.ten_vat_tu&dir=<?php echo $next_dir; ?>">
                                        Tên Vật tư <?php if($sort_col=='vt.ten_vat_tu') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="15%" class="text-left">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&kho=<?php echo urlencode($filter_kho); ?>&sort=k.ten_kho&dir=<?php echo $next_dir; ?>">
                                        Lưu tại Kho <?php if($sort_col=='k.ten_kho') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="15%" class="text-left">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&kho=<?php echo urlencode($filter_kho); ?>&sort=lvt.ten_loai_vat_tu&dir=<?php echo $next_dir; ?>">
                                        Loại VT <?php if($sort_col=='lvt.ten_loai_vat_tu') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="15%" class="text-left">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&kho=<?php echo urlencode($filter_kho); ?>&sort=hsx.ten_hang_san_xuat&dir=<?php echo $next_dir; ?>">
                                        Hãng SX <?php if($sort_col=='hsx.ten_hang_san_xuat') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="10%" class="text-center">ĐVT</th>
                                <th width="15%" class="text-center">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&kho=<?php echo urlencode($filter_kho); ?>&sort=ton_kho&dir=<?php echo $next_dir; ?>">
                                        Số lượng tồn <?php if($sort_col=='ton_kho') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if(mysqli_num_rows($result_main) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_main)) {
                                        
                                        // Hiển thị nhãn Cảnh báo / Còn hàng
                                        $ton_kho = $row['ton_kho'];
                                        $badge = ($ton_kho <= 0) ? "<span class='badge-danger'>Hết hàng</span>" : "<span class='badge-success'>" . number_format($ton_kho, 0, ',', '.') . "</span>";

                                        // Fallback nếu thiếu dữ liệu danh mục (NULL)
                                        $ten_kho = $row['ten_kho'] ? htmlspecialchars($row['ten_kho']) : "<i>Chưa phân kho</i>";
                                        $loai_vt = $row['ten_loai_vat_tu'] ? htmlspecialchars($row['ten_loai_vat_tu']) : "<i>-</i>";
                                        $hang_sx = $row['ten_hang_san_xuat'] ? htmlspecialchars($row['ten_hang_san_xuat']) : "<i>-</i>";
                                        $dvt = $row['ten_don_vi_tinh'] ? htmlspecialchars($row['ten_don_vi_tinh']) : "-";

                                        echo "<tr>";
                                        echo "<td class='text-center'><strong>" . htmlspecialchars($row['ma_vat_tu']) . "</strong></td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['ten_vat_tu']) . "</td>";
                                        echo "<td class='text-left'>" . $ten_kho . "</td>";
                                        echo "<td class='text-left'>" . $loai_vt . "</td>";
                                        echo "<td class='text-left'>" . $hang_sx . "</td>";
                                        echo "<td class='text-center'>" . $dvt . "</td>";
                                        echo "<td class='text-center'>" . $badge . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center' style='padding: 20px;'>Không tìm thấy vật tư nào phù hợp với bộ lọc!</td></tr>";
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
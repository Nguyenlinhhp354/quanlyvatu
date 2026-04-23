<?php
session_start();
include 'db_connect.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. LẤY THÔNG TIN NGƯỜI DÙNG
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$user_result = mysqli_query($conn, $sql_user);
$user_data = mysqli_fetch_assoc($user_result);
$ho_ten = $user_data['ho_ten'] ?? 'Admin';

// 3. XỬ LÝ TÌM KIẾM
$keyword = '';
$where = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyword = trim($_POST['timkiem'] ?? '');
    $keyword_esc = mysqli_real_escape_string($conn, $keyword);

    if ($keyword_esc !== '') {
        $where = "WHERE so_phieu LIKE '%$keyword_esc%'"
            . " OR loai_su_kien LIKE '%$keyword_esc%'"
            . " OR kho_nguon LIKE '%$keyword_esc%'"
            . " OR doi_tac LIKE '%$keyword_esc%'"
            . " OR kho_dich LIKE '%$keyword_esc%'"
            . " OR ghi_chu LIKE '%$keyword_esc%'"
            . " OR trang_thai LIKE '%$keyword_esc%'";
    }
}

// 4. TRUY VẤN DỮ LIỆU GỘP (Nhập - Xuất - Điều chuyển)
$sql_lichtrinh = "
SELECT * FROM (
    SELECT 'Nhập kho' AS loai_su_kien, pnk.so_phieu, pnk.ngay_nhap AS ngay,
           k.ten_kho AS kho_nguon, n.ten_ncc AS doi_tac, NULL AS kho_dich,
           pnk.ghi_chu, 'Hoàn thành' AS trang_thai
    FROM phieu_nhap_kho pnk
    LEFT JOIN kho k ON pnk.id_kho = k.id_kho
    LEFT JOIN nha_cung_cap n ON pnk.id_ncc = n.id_ncc

    UNION ALL

    SELECT 'Xuất kho', pxk.so_phieu, pxk.ngay_xuat,
           k.ten_kho, d.ten_du_an, NULL,
           pxk.ly_do_xuat, 'Hoàn thành'
    FROM phieu_xuat_kho pxk
    LEFT JOIN kho k ON pxk.id_kho = k.id_kho
    LEFT JOIN du_an d ON pxk.id_du_an = d.id_du_an

    UNION ALL

    SELECT 'Điều chuyển', pdc.so_phieu, pdc.ngay_chuyen,
           kx.ten_kho, NULL, kn.ten_kho,
           pdc.ly_do, pdc.trang_thai
    FROM phieu_dieu_chuyen pdc
    LEFT JOIN kho kx ON pdc.id_kho_xuat = kx.id_kho
    LEFT JOIN kho kn ON pdc.id_kho_nhap = kn.id_kho
) AS lich_trinh
$where
ORDER BY ngay DESC
";

$lichtrinh_result = mysqli_query($conn, $sql_lichtrinh);

if (!$lichtrinh_result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi cung ứng - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .form-search { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .form-search input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; min-width: 240px; }
        .form-search button { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; background: #28a745; color: #fff; font-weight: bold; }
        .form-search button:hover { background: #218838; }
        
        .table-layout { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
        .table-layout th, .table-layout td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table-layout th { background: #343a40; color: #fff; }
        .table-layout tbody tr:nth-child(even) { background: #f9f9f9; }
        .table-layout tbody tr:hover { background: #f1f1f1; }
        
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .bg-nhap { background-color: #007bff; }
        .bg-xuat { background-color: #dc3545; }
        .bg-chuyen { background-color: #ffc107; color: #212529; }
        .empty-row { text-align: center; padding: 30px !important; color: #777; font-style: italic; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div style="padding: 20px;">
                <h2 style="margin-bottom: 10px;">📋 Theo dõi lịch trình cung ứng vật tư</h2>
                <p style="margin-bottom: 20px; color: #555;">Dữ liệu lịch trình dựa trên các phiếu Nhập, Xuất và Điều chuyển tại Thịnh Tiến MM.</p>

                <form method="POST" class="form-search">
                    <input type="text" name="timkiem" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tìm theo số phiếu, kho, nhà cung cấp, dự án...">
                    <button type="submit">🔍 Tìm kiếm</button>
                </form>

                <div style="margin-bottom: 16px; font-weight: 600; color: #333;">
                    Số bản ghi tìm thấy: <?php echo mysqli_num_rows($lichtrinh_result); ?>
                </div>

                <div style="overflow-x:auto; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <table class="table-layout">
                        <thead>
                            <tr>
                                <th width="5%">STT</th>
                                <th width="12%">Loại sự kiện</th>
                                <th width="15%">Số phiếu</th>
                                <th width="15%">Ngày thực hiện</th>
                                <th width="15%">Kho nguồn</th>
                                <th width="15%">Đích / Đối tác</th>
                                <th>Ghi chú / Lý do</th>
                                <th width="10%">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($lichtrinh_result) > 0): ?>
                                <?php 
                                $i = 1; 
                                while ($row = mysqli_fetch_assoc($lichtrinh_result)): 
                                    // Xác định class CSS cho từng loại sự kiện
                                    $bg_class = '';
                                    if($row['loai_su_kien'] == 'Nhập kho') $bg_class = 'bg-nhap';
                                    elseif($row['loai_su_kien'] == 'Xuất kho') $bg_class = 'bg-xuat';
                                    else $bg_class = 'bg-chuyen';
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><span class="badge <?php echo $bg_class; ?>"><?php echo htmlspecialchars($row['loai_su_kien']); ?></span></td>
                                        <td style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($row['so_phieu']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['ngay'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['kho_nguon']); ?></td>
                                        <td>
                                            <?php 
                                                // Hiển thị Kho đích nếu là điều chuyển, ngược lại hiển thị đối tác (NCC/Dự án)
                                                echo htmlspecialchars($row['kho_dich'] ?: $row['doi_tac'] ?: '-'); 
                                            ?>
                                        </td>
                                        <td style="font-size: 14px; color: #666;"><?php echo htmlspecialchars($row['ghi_chu']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($row['trang_thai'] ?: 'N/A'); ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-row">❌ Không tìm thấy lịch trình nào phù hợp với từ khóa "<?php echo htmlspecialchars($keyword); ?>".</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>
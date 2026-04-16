<?php
session_start();
include 'db_connect.php';

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$ho_ten = ($row_user = mysqli_fetch_assoc(mysqli_query($conn, $sql_user))) ? $row_user['ho_ten'] : "Admin";

// ==========================================
// 1. XỬ LÝ TẠO PHIẾU KIỂM KÊ MỚI
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_pkk') {
    $so_phieu = mysqli_real_escape_string($conn, $_POST['so_phieu']);
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    if (!isset($_POST['vat_tu_ids']) || empty($_POST['vat_tu_ids'])) {
        echo "<script>alert('Lỗi: Bạn phải chọn ít nhất 1 vật tư để tạo phiếu kiểm kê!'); window.location.href='qlk_qlpkk.php';</script>";
    } else {
        $sql_insert_phieu = "INSERT INTO phieu_kiem_ke (so_phieu, ghi_chu) VALUES ('$so_phieu', '$ghi_chu')";
        if (mysqli_query($conn, $sql_insert_phieu)) {
            $id_phieu_kk = mysqli_insert_id($conn); 
            
            foreach ($_POST['vat_tu_ids'] as $id_vat_tu) {
                $ton_he_thong = intval($_POST['ton_he_thong_' . $id_vat_tu]);
                $id_vt_clean = intval($id_vat_tu);
                $sql_insert_ct = "INSERT INTO chi_tiet_kiem_ke (id_phieu_kk, id_vat_tu, ton_he_thong) 
                                  VALUES ('$id_phieu_kk', '$id_vt_clean', '$ton_he_thong')";
                mysqli_query($conn, $sql_insert_ct);
            }
            echo "<script>alert('Tạo phiếu kiểm kê thành công!'); window.location.href='qlk_qlpkk.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể tạo phiếu kiểm kê!');</script>";
        }
    }
}

// ==========================================
// 2. XỬ LÝ XÓA PHIẾU KIỂM KÊ
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM chi_tiet_kiem_ke WHERE id_phieu_kk='$id_xoa'");
    if (mysqli_query($conn, "DELETE FROM phieu_kiem_ke WHERE id_phieu_kk='$id_xoa'")) {
        echo "<script>alert('Đã xóa phiếu kiểm kê!'); window.location.href='qlk_qlpkk.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể xóa phiếu!');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phiếu Kiểm Kê - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .pkk-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px; }
        .btn-toggle-form { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-bottom: 20px; display: inline-block;}
        .btn-toggle-form:hover { background-color: #218838; }
        input[type=checkbox] { transform: scale(1.5); cursor: pointer; }
        
        /* CÁC NÚT HÀNH ĐỘNG */
        .btn-view { background-color: #17a2b8; }
        .btn-view:hover { background-color: #138496; }
        .btn-print { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-print:hover { background-color: #0056b3; }
        .btn-back { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin-right: 10px;}

        /* CSS BẢNG LỊCH SỬ KIỂM KÊ MỚI */
        .history-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 15px;}
        .history-table th, .history-table td { border: 1px solid #dee2e6; padding: 12px 15px; vertical-align: middle;}
        .history-table thead th { background-color: #343a40; color: #ffffff; text-transform: uppercase; font-size: 13px; font-weight: bold; letter-spacing: 0.5px;}
        .history-table tbody tr:hover { background-color: #f8f9fa; }
        .badge-date { background: #e9ecef; padding: 6px 12px; border-radius: 20px; font-size: 13px; color: #495057; font-weight: 600; display: inline-block;}

        /* BẢN IN (PRINT CSS): GIẤU GIAO DIỆN WEB KHI IN */
        @media print {
            body * { visibility: hidden; }
            #khuVucInPhieu, #khuVucInPhieu * { visibility: visible; }
            #khuVucInPhieu { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px; background: white; box-shadow: none; border: none;}
            .no-print { display: none !important; }
            table { width: 100%; border-collapse: collapse; }
            table, th, td { border: 1px solid black !important; }
            th, td { padding: 8px !important; color: black !important;}
            th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        }
        
        .print-header { text-align: center; margin-bottom: 30px; }
        .print-header h2 { font-size: 24px; text-transform: uppercase; margin-bottom: 5px; }
        .print-info { margin-bottom: 20px; line-height: 1.6; font-size: 16px; }
        .print-table th, .print-table td { border: 1px solid #333; padding: 10px; }
        .print-signature { display: flex; justify-content: space-around; margin-top: 50px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                
                <?php 
                // ===================================================================
                // MÀN HÌNH 2: XEM CHI TIẾT & IN PHIẾU (Nếu có biến ?action=view trên URL)
                // ===================================================================
                if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])): 
                    $id_view = intval($_GET['id']);
                    
                    // Lấy thông tin phiếu cha
                    $sql_phieu = "SELECT * FROM phieu_kiem_ke WHERE id_phieu_kk = '$id_view'";
                    $phieu = mysqli_fetch_assoc(mysqli_query($conn, $sql_phieu));
                    
                    if (!$phieu) {
                        echo "<script>alert('Không tìm thấy phiếu kiểm kê!'); window.location.href='qlk_qlpkk.php';</script>";
                        exit;
                    }
                ?>
                    
                    <div class="no-print" style="margin-bottom: 20px;">
                        <a href="qlk_qlpkk.php" class="btn-back">&laquo; Quay lại</a>
                        <button onclick="window.print()" class="btn-print">🖨 In Phiếu Kiểm Kê</button>
                    </div>

                    <div id="khuVucInPhieu" class="pkk-section">
                        <div class="print-header">
                            <h2>PHIẾU KIỂM KÊ VẬT TƯ</h2>
                            <p>Số phiếu: <strong><?php echo $phieu['so_phieu']; ?></strong></p>
                        </div>

                        <div class="print-info">
                            <p><strong>Ngày lập phiếu:</strong> <?php echo date('d/m/Y H:i', strtotime($phieu['ngay_lap'])); ?></p>
                            <p><strong>Người lập:</strong> <?php echo $ho_ten; ?></p>
                            <p><strong>Ghi chú / Mục đích:</strong> <?php echo htmlspecialchars($phieu['ghi_chu']); ?></p>
                        </div>

                        <table class="print-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">STT</th>
                                    <th width="15%" class="text-center">Mã VT</th>
                                    <th width="25%" class="text-left">Tên vật tư</th>
                                    <th width="10%" class="text-center">ĐVT</th>
                                    <th width="15%" class="text-left">Kho</th>
                                    <th width="10%" class="text-center">Tồn Máy</th>
                                    <th width="10%" class="text-center">Tồn Thực Tế</th>
                                    <th width="10%" class="text-center">Chênh Lệch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Lấy chi tiết vật tư trong phiếu
                                $sql_chitiet = "SELECT ct.*, vt.ma_vat_tu, vt.ten_vat_tu, k.ten_kho, dvt.ten_don_vi_tinh 
                                                FROM chi_tiet_kiem_ke ct
                                                JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                LEFT JOIN kho k ON vt.id_kho = k.id_kho
                                                LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                WHERE ct.id_phieu_kk = '$id_view'
                                                ORDER BY vt.ten_vat_tu ASC";
                                $result_ct = mysqli_query($conn, $sql_chitiet);
                                $stt = 1;
                                
                                while ($item = mysqli_fetch_assoc($result_ct)) {
                                    echo "<tr>";
                                    echo "<td class='text-center'>{$stt}</td>";
                                    echo "<td class='text-center'>{$item['ma_vat_tu']}</td>";
                                    echo "<td class='text-left'>{$item['ten_vat_tu']}</td>";
                                    echo "<td class='text-center'>{$item['ten_don_vi_tinh']}</td>";
                                    echo "<td class='text-left'>{$item['ten_kho']}</td>";
                                    echo "<td class='text-center'><strong>{$item['ton_he_thong']}</strong></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "</tr>";
                                    $stt++;
                                }
                                ?>
                            </tbody>
                        </table>

                        <div class="print-signature">
                            <div>
                                <p>Người lập phiếu</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 12px;">(Ký, ghi rõ họ tên)</p>
                            </div>
                            <div>
                                <p>Thủ kho / Người kiểm kê</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 12px;">(Ký, ghi rõ họ tên)</p>
                            </div>
                            <div>
                                <p>Giám đốc / Kế toán trưởng</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 12px;">(Ký, duyệt)</p>
                            </div>
                        </div>
                    </div>

                <?php 
                // ===================================================================
                // MÀN HÌNH 1: DANH SÁCH & TẠO MỚI (Trạng thái bình thường)
                // ===================================================================
                else: 
                ?>
                    <h2 class="page-title">Quản lý Phiếu Kiểm Kê</h2>

                    <button class="btn-toggle-form" onclick="toggleFormTaoPhieu()">+ Lập Phiếu Kiểm Kê Mới</button>

                    <div id="khuVucTaoPhieu" class="pkk-section" style="display: none; border-top: 4px solid #28a745;">
                        <h3 style="margin-bottom: 15px; color: #28a745;">Tạo Phiếu Kiểm Kê Mới</h3>
                        <form method="POST" action="qlk_qlpkk.php">
                            <input type="hidden" name="action" value="create_pkk">
                            
                            <div class="form-grid">
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Số Phiếu PKK:</label>
                                    <input type="text" name="so_phieu" required placeholder="VD: PKK-2026-001" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ghi chú đợt kiểm kê:</label>
                                    <input type="text" name="ghi_chu" placeholder="VD: Kiểm kê kho tháng 4/2026..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                            </div>

                            <h4 style="margin-bottom: 10px;">Vui lòng tick chọn (✓) các vật tư cần kiểm kê dưới đây:</h4>
                            
                            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead style="position: sticky; top: 0; background: #343a40; color: white; z-index: 10;">
                                        <tr>
                                            <th width="5%" class="text-center" style="padding: 10px;">
                                                <input type="checkbox" id="checkAll" onclick="chonTatCa(this)">
                                            </th>
                                            <th width="15%" class="text-center">Mã VT</th>
                                            <th width="30%" class="text-left">Tên Vật tư</th>
                                            <th width="20%" class="text-left">Kho lưu trữ</th>
                                            <th width="15%" class="text-center">Tồn hệ thống</th>
                                            <th width="15%" class="text-center">ĐVT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Lấy Tồn kho
                                        $sql_ton_kho = "SELECT 
                                                            vt.id_vat_tu, vt.ma_vat_tu, vt.ten_vat_tu, 
                                                            k.ten_kho, dvt.ten_don_vi_tinh,
                                                            (IFNULL(nhap.tong_nhap, 0) - IFNULL(xuat.tong_xuat, 0)) AS ton_kho
                                                        FROM vat_tu vt
                                                        LEFT JOIN kho k ON vt.id_kho = k.id_kho
                                                        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                        LEFT JOIN (SELECT id_vat_tu, SUM(so_luong) AS tong_nhap FROM chi_tiet_nhap_kho GROUP BY id_vat_tu) nhap ON vt.id_vat_tu = nhap.id_vat_tu
                                                        LEFT JOIN (SELECT id_vat_tu, SUM(so_luong) AS tong_xuat FROM chi_tiet_xuat_kho GROUP BY id_vat_tu) xuat ON vt.id_vat_tu = xuat.id_vat_tu
                                                        ORDER BY vt.ten_vat_tu ASC";
                                                        
                                        $result_ton = mysqli_query($conn, $sql_ton_kho);
                                        while ($vt = mysqli_fetch_assoc($result_ton)) {
                                            $ton_kho = $vt['ton_kho'];
                                            echo "<tr style='border-bottom: 1px solid #eee;'>";
                                            echo "<td class='text-center' style='padding: 10px;'>
                                                    <input type='checkbox' name='vat_tu_ids[]' value='{$vt['id_vat_tu']}' class='chk-vattu'>
                                                    <input type='hidden' name='ton_he_thong_{$vt['id_vat_tu']}' value='$ton_kho'>
                                                  </td>";
                                            echo "<td class='text-center'>{$vt['ma_vat_tu']}</td>";
                                            echo "<td class='text-left'>{$vt['ten_vat_tu']}</td>";
                                            echo "<td class='text-left'>{$vt['ten_kho']}</td>";
                                            echo "<td class='text-center' style='font-weight:bold; color:#007bff;'>$ton_kho</td>";
                                            echo "<td class='text-center'>{$vt['ten_don_vi_tinh']}</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="text-align: right; margin-top: 15px;">
                                <button type="button" onclick="toggleFormTaoPhieu()" style="padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; margin-right: 10px; cursor: pointer;">Hủy bỏ</button>
                                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Lưu Phiếu Kiểm Kê</button>
                            </div>
                        </form>
                    </div>

                    <div class="pkk-section">
                        <h3 style="margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; color: #333;">
                            📋 Danh sách Lịch sử Phiếu Kiểm Kê
                        </h3>
                        <div style="overflow-x: auto;">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">ID</th>
                                        <th width="20%" class="text-left">Số Phiếu</th>
                                        <th width="20%" class="text-center">Ngày Lập</th>
                                        <th width="35%" class="text-left">Ghi chú</th>
                                        <th width="20%" class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_list = "SELECT * FROM phieu_kiem_ke ORDER BY id_phieu_kk DESC";
                                    $result_list = @mysqli_query($conn, $sql_list);
                                    
                                    if ($result_list && mysqli_num_rows($result_list) > 0) {
                                        while ($pkk = mysqli_fetch_assoc($result_list)) {
                                            $ngay_lap = date('d/m/Y H:i', strtotime($pkk['ngay_lap']));
                                            echo "<tr>";
                                            echo "<td class='text-center'>{$pkk['id_phieu_kk']}</td>";
                                            // Thiết kế Số Phiếu nổi bật
                                            echo "<td class='text-left'><span style='color: #007bff; font-weight: bold; font-size: 15px;'>{$pkk['so_phieu']}</span></td>";
                                            // Thiết kế Ngày lập dạng Badge (Huy hiệu)
                                            echo "<td class='text-center'><span class='badge-date'>🕒 {$ngay_lap}</span></td>";
                                            echo "<td class='text-left' style='color: #555;'>{$pkk['ghi_chu']}</td>";
                                            
                                            // Chỉnh lại giao diện các nút bấm Hành động
                                            echo "<td class='text-center'>
                                                    <div style='display: flex; justify-content: center; gap: 8px;'>
                                                        <a href='qlk_qlpkk.php?action=view&id={$pkk['id_phieu_kk']}' class='btn-action btn-view' style='color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px;'>👁 Xem / In</a>
                                                        <a href='qlk_qlpkk.php?action=delete&id={$pkk['id_phieu_kk']}' class='btn-action btn-delete' onclick='return confirm(\"Xóa phiếu kiểm kê này sẽ xóa luôn danh sách chi tiết bên trong. Bạn có chắc chắn không?\");' style='color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px;'>🗑 Xóa</a>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center' style='padding: 30px; color: #888;'>Chưa có phiếu kiểm kê nào được tạo.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        function toggleFormTaoPhieu() {
            var form = document.getElementById("khuVucTaoPhieu");
            if (form.style.display === "none" || form.style.display === "") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }

        function chonTatCa(source) {
            var checkboxes = document.querySelectorAll('.chk-vattu');
            for(var i=0, n=checkboxes.length; i<n; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</body>
</html>
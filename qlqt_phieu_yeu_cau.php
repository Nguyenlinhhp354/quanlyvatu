<?php
session_start();
include 'db_connect.php';

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$ho_ten = ($row_user = mysqli_fetch_assoc(mysqli_query($conn, $sql_user))) ? $row_user['ho_ten'] : "Admin";

// ==========================================
// 1. XỬ LÝ LƯU PHIẾU YÊU CẦU (THÊM / SỬA)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_pyc') {
    $is_edit = isset($_POST['edit_id']) && intval($_POST['edit_id']) > 0;
    $id_phieu_edit = $is_edit ? intval($_POST['edit_id']) : 0;
    
    $so_phieu = mysqli_real_escape_string($conn, $_POST['so_phieu']);
    $ngay_lap = mysqli_real_escape_string($conn, $_POST['ngay_lap']);
    $ly_do = mysqli_real_escape_string($conn, $_POST['ly_do']);
    $id_du_an = intval($_POST['id_du_an']); 
    
    if (!isset($_POST['item_keys']) || empty($_POST['item_keys'])) {
        echo "<script>alert('Lỗi: Bạn phải chọn ít nhất 1 vật tư để yêu cầu!'); window.history.back();</script>";
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        if ($is_edit) {
            // KIỂM TRA LUỒNG BẢO MẬT: Phải đảm bảo phiếu chưa duyệt mới được sửa
            $check_tt = mysqli_query($conn, "SELECT trang_thai FROM phieu_yeu_cau WHERE id_phieu_yc='$id_phieu_edit'");
            $row_tt = mysqli_fetch_assoc($check_tt);
            if ($row_tt['trang_thai'] == 1) {
                throw new Exception("Lỗi: Phiếu này đã được duyệt, bạn không thể sửa đổi!");
            }

            // Kiểm tra trùng số phiếu
            $check_sp = mysqli_query($conn, "SELECT id_phieu_yc FROM phieu_yeu_cau WHERE so_phieu='$so_phieu' AND id_phieu_yc != '$id_phieu_edit'");
            if(mysqli_num_rows($check_sp) > 0) throw new Exception("Số phiếu '$so_phieu' đã tồn tại!");

            $sql_update = "UPDATE phieu_yeu_cau SET so_phieu='$so_phieu', ngay_lap='$ngay_lap', id_du_an='$id_du_an', ly_do='$ly_do' WHERE id_phieu_yc='$id_phieu_edit'";
            if (!mysqli_query($conn, $sql_update)) throw new Exception("Lỗi cập nhật phiếu!");

            // Xóa chi tiết cũ để nạp lại
            mysqli_query($conn, "DELETE FROM chi_tiet_yeu_cau WHERE id_phieu_yc='$id_phieu_edit'");
            $id_phieu_yc = $id_phieu_edit;
            $msg_alert = "Cập nhật phiếu yêu cầu thành công!";
        } else {
            // Kiểm tra trùng số phiếu
            $check_sp = mysqli_query($conn, "SELECT id_phieu_yc FROM phieu_yeu_cau WHERE so_phieu='$so_phieu'");
            if(mysqli_num_rows($check_sp) > 0) throw new Exception("Số phiếu '$so_phieu' đã tồn tại!");

            // Mặc định tạo mới thì trang_thai = 0 (Chờ duyệt)
            $sql_insert = "INSERT INTO phieu_yeu_cau (so_phieu, ngay_lap, id_nguoi_lap, id_du_an, ly_do, trang_thai) 
                           VALUES ('$so_phieu', '$ngay_lap', '$id_nguoi_dung', '$id_du_an', '$ly_do', 0)";
            if (!mysqli_query($conn, $sql_insert)) throw new Exception("Lỗi tạo phiếu yêu cầu: " . mysqli_error($conn));
            $id_phieu_yc = mysqli_insert_id($conn);
            $msg_alert = "Tạo phiếu yêu cầu mới thành công!";
        }

        // Lưu chi tiết vật tư được chọn
        foreach ($_POST['item_keys'] as $id_vt_clean) {
            $id_vt_clean = intval($id_vt_clean);
            $sl_yeu_cau = floatval($_POST['sl_yc_' . $id_vt_clean]);
            
            // Bỏ qua nếu nhập số lượng = 0
            if ($sl_yeu_cau <= 0) continue;

            $sql_insert_ct = "INSERT INTO chi_tiet_yeu_cau (id_phieu_yc, id_vat_tu, so_luong_yeu_cau, so_luong_duyet) 
                              VALUES ('$id_phieu_yc', '$id_vt_clean', '$sl_yeu_cau', 0)";
            if(!mysqli_query($conn, $sql_insert_ct)) throw new Exception("Lỗi lưu chi tiết vật tư!");
        }

        mysqli_commit($conn);
        echo "<script>alert('$msg_alert'); window.location.href='qlqt_phieu_yeu_cau.php';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('" . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}

// ==========================================
// 2. XỬ LÝ XÓA PHIẾU YÊU CẦU
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    
    // KIỂM TRA BẢO MẬT TRƯỚC KHI XÓA
    $check_tt = mysqli_query($conn, "SELECT trang_thai FROM phieu_yeu_cau WHERE id_phieu_yc='$id_xoa'");
    $row_tt = mysqli_fetch_assoc($check_tt);
    
    if ($row_tt['trang_thai'] == 1) {
        echo "<script>alert('Từ chối: Không thể xóa Phiếu Yêu Cầu đã được duyệt!'); window.location.href='qlqt_phieu_yeu_cau.php';</script>";
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM chi_tiet_yeu_cau WHERE id_phieu_yc='$id_xoa'");
        mysqli_query($conn, "DELETE FROM phieu_yeu_cau WHERE id_phieu_yc='$id_xoa'");
        mysqli_commit($conn);
        echo "<script>alert('Đã xóa phiếu yêu cầu!'); window.location.href='qlqt_phieu_yeu_cau.php';</script>";
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Lỗi: Không thể xóa phiếu!'); window.location.href='qlqt_phieu_yeu_cau.php';</script>";
    }
    exit;
}

// SINH MÃ TỰ ĐỘNG
$today_str = date('Y-m-d');
$prefix = "PYC-" . $today_str . "-";
$sql_max = "SELECT so_phieu FROM phieu_yeu_cau WHERE so_phieu LIKE '$prefix%' ORDER BY id_phieu_yc DESC LIMIT 1";
$res_max = mysqli_query($conn, $sql_max);
$next_stt = 1;
if ($res_max && mysqli_num_rows($res_max) > 0) {
    $row_max = mysqli_fetch_assoc($res_max);
    $parts = explode('-', $row_max['so_phieu']);
    $next_stt = intval(end($parts)) + 1;
}
$auto_so_phieu = $prefix . $next_stt;

// BIẾN TÌM KIẾM
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$tu_ngay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : "";
$den_ngay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : "";
$trang_thai_loc = isset($_GET['trang_thai_loc']) ? $_GET['trang_thai_loc'] : "all";

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phiếu Yêu Cầu - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-wrapper { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; align-items: center;}
        .filter-wrapper input, .filter-wrapper select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        
        .pkk-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1.5fr 2fr; gap: 20px; margin-bottom: 20px; }
        .btn-toggle-form { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; display: inline-block; text-decoration: none;}
        input[type=checkbox] { transform: scale(1.5); cursor: pointer; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;}
        .data-table th, .data-table td { border: 1px solid #dee2e6; padding: 10px 12px; vertical-align: middle;}
        .data-table thead th { background-color: #343a40; color: #ffffff; text-transform: uppercase; font-size: 13px; font-weight: bold; position: sticky; top: 0;}
        .data-table tbody tr:hover { background-color: #f8f9fa; }
        
        .btn-view { background-color: #17a2b8; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; }
        .btn-edit { background-color: #ffc107; color:#212529; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; font-weight: bold;}
        .btn-delete { background-color: #dc3545; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; }
        .btn-disabled { background-color: #e9ecef; color:#6c757d; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; cursor: not-allowed; border: 1px solid #ced4da;}
        
        .btn-print { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-back { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin-right: 10px;}

        .modal-filter { background: #e9ecef; padding: 12px; border-radius: 6px; display: flex; gap: 15px; margin-bottom: 10px; align-items: center; border: 1px solid #ced4da; }
        .input-soluong { width: 120px; padding: 8px; border: 2px solid #28a745; border-radius: 4px; font-weight: bold; text-align: center; outline: none; }
        .badge-choduyet { background: #ffc107; color: #000; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-daduyet { background: #28a745; color: #fff; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }

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
                // MÀN HÌNH 3: XEM CHI TIẾT & IN PHIẾU
                // ===================================================================
                if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])): 
                    $id_view = intval($_GET['id']);
                    // JOIN thêm bảng du_an để lấy Tên dự án
                    $sql_phieu = "SELECT p.*, n.ho_ten AS nguoi_lap, da.ten_du_an 
                                  FROM phieu_yeu_cau p 
                                  LEFT JOIN nguoi_dung n ON p.id_nguoi_lap = n.id_nguoi_dung 
                                  LEFT JOIN du_an da ON p.id_du_an = da.id_du_an
                                  WHERE p.id_phieu_yc = '$id_view'";
                    $phieu = mysqli_fetch_assoc(mysqli_query($conn, $sql_phieu));
                    if (!$phieu) { echo "<script>alert('Không tìm thấy phiếu!'); window.location.href='qlqt_phieu_yeu_cau.php';</script>"; exit; }
                    
                    $trang_thai_text = ($phieu['trang_thai'] == 1) ? "<span class='badge-daduyet'>Đã duyệt</span>" : "<span class='badge-choduyet'>Chờ duyệt</span>";
                    $ten_du_an = !empty($phieu['ten_du_an']) ? $phieu['ten_du_an'] : "Chưa xác định";
                ?>
                    <div class="no-print" style="margin-bottom: 20px;">
                        <a href="qlqt_phieu_yeu_cau.php" class="btn-back">&laquo; Quay lại</a>
                        <button onclick="window.print()" class="btn-print">🖨 In Phiếu Yêu Cầu</button>
                    </div>

                    <div id="khuVucInPhieu" class="pkk-section">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="font-size: 24px; text-transform: uppercase; margin-bottom: 5px;">PHIẾU YÊU CẦU VẬT TƯ</h2>
                            <p>Số phiếu: <strong><?php echo $phieu['so_phieu']; ?></strong> | Trạng thái: <?php echo $trang_thai_text; ?></p>
                        </div>

                        <div style="margin-bottom: 20px; line-height: 1.6; font-size: 16px;">
                            <p><strong>Ngày lập phiếu:</strong> <?php echo date('d/m/Y H:i', strtotime($phieu['ngay_lap'])); ?></p>
                            <p><strong>Người lập:</strong> <?php echo htmlspecialchars($phieu['nguoi_lap']); ?></p>
                            <p><strong>Dự án / Công trình:</strong> <span style="color:#007bff; font-weight:bold;"><?php echo htmlspecialchars($ten_du_an); ?></span></p>
                            <p><strong>Lý do yêu cầu:</strong> <?php echo htmlspecialchars($phieu['ly_do']); ?></p>
                        </div>

                        <table class="data-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">STT</th>
                                    <th width="15%" class="text-center">Mã VT</th>
                                    <th width="35%" class="text-left">Tên vật tư</th>
                                    <th width="15%" class="text-center">ĐVT</th>
                                    <th width="15%" class="text-center">Số Lượng Yêu Cầu</th>
                                    <th width="15%" class="text-center">Số Lượng Duyệt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_chitiet = "SELECT ct.*, vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh
                                                FROM chi_tiet_yeu_cau ct
                                                JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                WHERE ct.id_phieu_yc = '$id_view'
                                                ORDER BY vt.ten_vat_tu ASC";
                                $result_ct = mysqli_query($conn, $sql_chitiet);
                                $stt = 1;
                                
                                while ($item = mysqli_fetch_assoc($result_ct)) {
                                    $sl_duyet = ($item['so_luong_duyet'] > 0) ? "<strong style='color:#28a745'>{$item['so_luong_duyet']}</strong>" : "-";
                                    echo "<tr>";
                                    echo "<td class='text-center'>{$stt}</td>";
                                    echo "<td class='text-center'><strong>{$item['ma_vat_tu']}</strong></td>";
                                    echo "<td class='text-left'>{$item['ten_vat_tu']}</td>";
                                    echo "<td class='text-center'>{$item['ten_don_vi_tinh']}</td>";
                                    echo "<td class='text-center'><strong style='color:#007bff; font-size: 15px;'>{$item['so_luong_yeu_cau']}</strong></td>";
                                    echo "<td class='text-center'>{$sl_duyet}</td>";
                                    echo "</tr>";
                                    $stt++;
                                }
                                ?>
                            </tbody>
                        </table>

                        <div style="display: flex; justify-content: space-around; margin-top: 50px; text-align: center; font-weight: bold;">
                            <div>
                                <p>Người lập phiếu</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, họ tên)</p>
                            </div>
                            <div>
                                <p>Phụ trách bộ phận</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, duyệt)</p>
                            </div>
                            <div>
                                <p>Ban Giám Đốc</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, duyệt)</p>
                            </div>
                        </div>
                    </div>


                <?php 
                // ===================================================================
                // MÀN HÌNH 2: TẠO MỚI / SỬA PHIẾU YÊU CẦU
                // ===================================================================
                elseif (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])):
                    $is_edit = ($_GET['action'] == 'edit');
                    $edit_id = 0;
                    
                    $val_so_phieu = $auto_so_phieu;
                    $val_ngay_lap = date('Y-m-d\TH:i');
                    $val_ly_do = "";
                    $val_id_du_an = "";
                    $title = "TẠO PHIẾU YÊU CẦU MỚI";

                    if ($is_edit && isset($_GET['id'])) {
                        $edit_id = intval($_GET['id']);
                        $phieu_edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM phieu_yeu_cau WHERE id_phieu_yc = '$edit_id'"));
                        
                        if ($phieu_edit) {
                            // Chặn cứng: Nếu đã duyệt (1) thì đá văng ra ngoài không cho vô trang sửa
                            if ($phieu_edit['trang_thai'] == 1) {
                                echo "<script>alert('Lỗi: Phiếu này đã được duyệt, bạn không thể chỉnh sửa!'); window.location.href='qlqt_phieu_yeu_cau.php';</script>";
                                exit;
                            }
                            
                            $val_so_phieu = $phieu_edit['so_phieu'];
                            $val_ngay_lap = date('Y-m-d\TH:i', strtotime($phieu_edit['ngay_lap']));
                            $val_ly_do = $phieu_edit['ly_do'];
                            $val_id_du_an = $phieu_edit['id_du_an'];
                            $title = "SỬA PHIẾU YÊU CẦU (ID: $edit_id)";
                        } else {
                            $is_edit = false;
                        }
                    }
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #333;"><?=$title?></h2>
                        <a href="qlqt_phieu_yeu_cau.php" class="btn-back">Hủy & Quay lại</a>
                    </div>

                    <div class="pkk-section">
                        <form method="POST" action="qlqt_phieu_yeu_cau.php">
                            <input type="hidden" name="action" value="save_pyc">
                            <input type="hidden" name="edit_id" value="<?=$edit_id?>">
                            
                            <div class="form-grid">
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Số Phiếu YC:</label>
                                    <input type="text" name="so_phieu" required value="<?=htmlspecialchars($val_so_phieu)?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ngày lập:</label>
                                    <input type="datetime-local" name="ngay_lap" required value="<?=$val_ngay_lap?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Dự án / Công trình:</label>
                                    <select name="id_du_an" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                        <option value="">-- Chọn dự án --</option>
                                        <?php
                                        // Truy vấn lấy danh sách dự án "Đang thi công" hoặc chính dự án của phiếu đang sửa
                                        $sql_da = "SELECT id_du_an, ten_du_an FROM du_an WHERE trang_thai = 'Đang thi công' ";
                                        if ($val_id_du_an != "") {
                                            $sql_da .= " OR id_du_an = '$val_id_du_an' "; // Đảm bảo lúc sửa vẫn giữ nguyên tên dự án cũ dù nó đã Đóng
                                        }
                                        $sql_da .= " ORDER BY ten_du_an ASC";
                                        
                                        $res_da = @mysqli_query($conn, $sql_da);
                                        if($res_da) {
                                            while($row_da = mysqli_fetch_assoc($res_da)) {
                                                $selected = ($val_id_du_an == $row_da['id_du_an']) ? "selected" : "";
                                                echo "<option value='{$row_da['id_du_an']}' {$selected}>{$row_da['ten_du_an']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Lý do yêu cầu:</label>
                                    <input type="text" name="ly_do" required placeholder="Ghi rõ mục đích sử dụng..." value="<?=htmlspecialchars($val_ly_do)?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                            </div>

                            <h4 style="margin-bottom: 10px;">Danh sách Vật tư (Tick chọn & Nhập số lượng cần yêu cầu):</h4>
                            
                            <div class="modal-filter">
                                <strong style="color: #495057;">🔍 Lọc nhanh vật tư:</strong>
                                <input type="text" id="filter_txt" onkeyup="filterVatTuModal()" placeholder="Tìm mã hoặc tên vật tư..." style="width: 300px;">
                            </div>
                            
                            <div style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6;">
                                <table class="data-table" style="margin-top: 0;" id="bangVatTuModal">
                                    <thead>
                                        <tr>
                                            <th width="5%" class="text-center">
                                                <input type="checkbox" id="checkAll" onclick="chonTatCa(this)" title="Chỉ chọn những dòng đang hiển thị">
                                            </th>
                                            <th width="15%" class="text-center">Mã VT</th>
                                            <th width="40%" class="text-left">Tên Vật tư</th>
                                            <th width="15%" class="text-center">ĐVT</th>
                                            <th width="25%" class="text-center">SỐ LƯỢNG YÊU CẦU</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Truy vấn toàn bộ vật tư, nối bảng chi_tiet_yeu_cau nếu đang Sửa
                                        $sql_vt = "SELECT vt.id_vat_tu, vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh, ct.so_luong_yeu_cau
                                                   FROM vat_tu vt
                                                   LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                   LEFT JOIN chi_tiet_yeu_cau ct ON vt.id_vat_tu = ct.id_vat_tu AND ct.id_phieu_yc = '$edit_id'
                                                   ORDER BY vt.ten_vat_tu ASC";
                                                        
                                        $result_vt = mysqli_query($conn, $sql_vt);
                                        while ($vt = mysqli_fetch_assoc($result_vt)) {
                                            $id_vt = $vt['id_vat_tu'];
                                            $is_checked = ($vt['so_luong_yeu_cau'] != null) ? "checked" : "";
                                            $sl_hien_thi = ($is_checked) ? floatval($vt['so_luong_yeu_cau']) : "";

                                            echo "<tr class='row-vt'>";
                                            echo "<td class='text-center'>
                                                    <input type='checkbox' name='item_keys[]' value='{$id_vt}' class='chk-vattu' $is_checked onchange='toggleInput(this, {$id_vt})'>
                                                  </td>";
                                            echo "<td class='text-center'><strong>{$vt['ma_vat_tu']}</strong></td>";
                                            echo "<td class='text-left'>{$vt['ten_vat_tu']}</td>";
                                            echo "<td class='text-center'>{$vt['ten_don_vi_tinh']}</td>";
                                            echo "<td class='text-center'>
                                                    <input type='number' step='0.01' min='0' name='sl_yc_{$id_vt}' id='input_{$id_vt}' class='input-soluong' value='{$sl_hien_thi}' placeholder='Nhập SL...' " . ($is_checked ? "" : "disabled") . ">
                                                  </td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="text-align: right; margin-top: 20px;">
                                <button type="submit" style="padding: 12px 25px; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px;">💾 GỬI YÊU CẦU</button>
                            </div>
                        </form>
                    </div>


                <?php 
                // ===================================================================
                // MÀN HÌNH 1: DANH SÁCH LỊCH SỬ PHIẾU YÊU CẦU (Mặc định)
                // ===================================================================
                else: 
                    // TẠO ĐIỀU KIỆN LỌC
                    $where = "1=1";
                    if ($search != "") $where .= " AND (p.so_phieu LIKE '%$search%' OR p.ly_do LIKE '%$search%')";
                    if ($tu_ngay != "") $where .= " AND DATE(p.ngay_lap) >= '$tu_ngay'";
                    if ($den_ngay != "") $where .= " AND DATE(p.ngay_lap) <= '$den_ngay'";
                    
                    if ($trang_thai_loc != "all") {
                        $st = intval($trang_thai_loc);
                        $where .= " AND p.trang_thai = '$st'";
                    }
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #333;">Quản lý Phiếu Yêu Cầu Vật Tư</h2>
                        <a href="?action=add" class="btn-toggle-form">+ Lập Phiếu Yêu Cầu Mới</a>
                    </div>

                    <form class="filter-wrapper" method="GET" action="qlqt_phieu_yeu_cau.php">
                        <input type="text" name="search" placeholder="Số phiếu, lý do..." value="<?=htmlspecialchars($search)?>" style="width: 200px;">
                        
                        <select name="trang_thai_loc" style="font-weight: bold;">
                            <option value="all" <?=($trang_thai_loc=='all')?'selected':''?>>- Tất cả trạng thái -</option>
                            <option value="0" <?=($trang_thai_loc=='0')?'selected':''?> style="color:#d39e00;">⌛ Chờ duyệt</option>
                            <option value="1" <?=($trang_thai_loc=='1')?'selected':''?> style="color:green;">✔ Đã duyệt</option>
                        </select>

                        <span style="font-weight: bold; color: #555;">Từ ngày:</span>
                        <input type="date" name="tu_ngay" value="<?=htmlspecialchars($tu_ngay)?>">
                        
                        <span style="font-weight: bold; color: #555;">Đến ngày:</span>
                        <input type="date" name="den_ngay" value="<?=htmlspecialchars($den_ngay)?>">

                        <button type="submit" class="btn-search">Lọc dữ liệu</button>
                        
                        <?php if($search != "" || $tu_ngay != "" || $den_ngay != "" || $trang_thai_loc != "all"): ?>
                            <a href="qlqt_phieu_yeu_cau.php" class="btn-clear">Xóa lọc</a>
                        <?php endif; ?>
                    </form>

                    <div class="pkk-section">
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th width="15%" class="text-left">Số Phiếu</th>
                                        <th width="15%" class="text-center">Ngày Lập</th>
                                        <th width="20%" class="text-left">Người / Dự án YC</th>
                                        <th width="20%" class="text-left">Lý do</th>
                                        <th width="10%" class="text-center">Trạng thái</th>
                                        <th width="20%" class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Bổ sung JOIN bảng du_an để lấy Tên dự án ra màn hình ngoài
                                    $sql_list = "SELECT p.*, n.ho_ten, da.ten_du_an 
                                                 FROM phieu_yeu_cau p
                                                 LEFT JOIN nguoi_dung n ON p.id_nguoi_lap = n.id_nguoi_dung
                                                 LEFT JOIN du_an da ON p.id_du_an = da.id_du_an
                                                 WHERE $where ORDER BY p.ngay_lap DESC, p.id_phieu_yc DESC";
                                    $result_list = @mysqli_query($conn, $sql_list);
                                    
                                    if ($result_list && mysqli_num_rows($result_list) > 0) {
                                        while ($pyc = mysqli_fetch_assoc($result_list)) {
                                            $ngay_lap = date('d/m/Y H:i', strtotime($pyc['ngay_lap']));
                                            $trang_thai = intval($pyc['trang_thai']);
                                            $ten_du_an_hienthi = !empty($pyc['ten_du_an']) ? $pyc['ten_du_an'] : "Chưa xác định";
                                            
                                            // Chuyển đổi trạng thái hiển thị
                                            $badge_status = ($trang_thai == 1) ? "<span class='badge-daduyet'>✔ Đã duyệt</span>" : "<span class='badge-choduyet'>⌛ Chờ duyệt</span>";
                                            
                                            echo "<tr>";
                                            echo "<td class='text-left'><strong style='color: #007bff; font-size: 15px;'>{$pyc['so_phieu']}</strong></td>";
                                            echo "<td class='text-center'><span style='background:#e9ecef; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:bold;'>{$ngay_lap}</span></td>";
                                            echo "<td class='text-left'><strong>{$pyc['ho_ten']}</strong><br><span style='font-size:12px; color:#666'>DA: {$ten_du_an_hienthi}</span></td>";
                                            echo "<td class='text-left' style='color: #555;'>{$pyc['ly_do']}</td>";
                                            echo "<td class='text-center'>{$badge_status}</td>";
                                            
                                            echo "<td class='text-center'>
                                                    <div style='display: flex; justify-content: center; gap: 8px;'>
                                                        <a href='?action=view&id={$pyc['id_phieu_yc']}' class='btn-view'>👁 Xem</a>";
                                            
                                            // LOGIC KHÓA CỨNG: Nếu đã duyệt (1) thì làm mờ nút Sửa/Xóa và chặn click
                                            if ($trang_thai == 1) {
                                                echo "<span class='btn-disabled' title='Phiếu đã duyệt không thể sửa'>✎ Sửa</span>";
                                                echo "<span class='btn-disabled' title='Phiếu đã duyệt không thể xóa'>🗑 Xóa</span>";
                                            } else {
                                                echo "<a href='?action=edit&id={$pyc['id_phieu_yc']}' class='btn-edit'>✎ Sửa</a>";
                                                echo "<a href='?action=delete&id={$pyc['id_phieu_yc']}' class='btn-delete' onclick='return confirm(\"Bạn có chắc chắn muốn xóa Phiếu Yêu Cầu này không?\");'>🗑 Xóa</a>";
                                            }

                                            echo "  </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center' style='padding: 30px; color: #888;'>Không tìm thấy phiếu yêu cầu nào phù hợp.</td></tr>";
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
        // Lọc vật tư trên Modal
        function filterVatTuModal() {
            var txt = document.getElementById("filter_txt").value.toLowerCase();
            var rows = document.querySelectorAll(".row-vt");

            rows.forEach(function(row) {
                var ma = row.children[1].innerText.toLowerCase();
                var ten = row.children[2].innerText.toLowerCase();
                if (txt === "" || ma.includes(txt) || ten.includes(txt)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        // Chọn tất cả các ô đang được lọc hiển thị
        function chonTatCa(source) {
            var checkboxes = document.querySelectorAll('.chk-vattu');
            for(var i=0, n=checkboxes.length; i<n; i++) {
                if (checkboxes[i].closest('tr').style.display !== 'none') {
                    checkboxes[i].checked = source.checked;
                    // Kích hoạt ô nhập số lượng tương ứng
                    let id = checkboxes[i].value;
                    let inputSl = document.getElementById('input_' + id);
                    if (inputSl) {
                        inputSl.disabled = !source.checked;
                        if(source.checked && inputSl.value === "") inputSl.value = 1; 
                        if(!source.checked) inputSl.value = "";
                    }
                }
            }
        }
        
        // Tự động mở/khóa ô nhập số lượng khi tick chọn vật tư
        function toggleInput(checkboxElem, idVt) {
            var inputElem = document.getElementById('input_' + idVt);
            if (checkboxElem.checked) {
                inputElem.disabled = false;
                inputElem.focus();
            } else {
                inputElem.disabled = true;
                inputElem.value = ""; // Xóa trắng khi bỏ chọn
            }
        }
    </script>
</body>
</html>
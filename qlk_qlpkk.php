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

// KIỂM TRA BẢNG CÓ id_kho KHÔNG
$has_id_kho = false;
$check_col = @mysqli_query($conn, "SHOW COLUMNS FROM chi_tiet_kiem_ke LIKE 'id_kho'");
if ($check_col && mysqli_num_rows($check_col) > 0) $has_id_kho = true;

// KIỂM TRA BẢNG CÓ ton_thuc_te KHÔNG (Do bạn vừa thêm)
$has_ton_thuc_te = false;
$check_col2 = @mysqli_query($conn, "SHOW COLUMNS FROM chi_tiet_kiem_ke LIKE 'ton_thuc_te'");
if ($check_col2 && mysqli_num_rows($check_col2) > 0) $has_ton_thuc_te = true;


// ==========================================
// 1. XỬ LÝ LƯU PHIẾU (THÊM MỚI VÀ SỬA)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_pkk') {
    $is_edit = isset($_POST['edit_id']) && intval($_POST['edit_id']) > 0;
    $id_phieu_edit = $is_edit ? intval($_POST['edit_id']) : 0;
    
    $so_phieu = mysqli_real_escape_string($conn, $_POST['so_phieu']);
    $ngay_lap = mysqli_real_escape_string($conn, $_POST['ngay_lap']);
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    if (!isset($_POST['item_keys']) || empty($_POST['item_keys'])) {
        echo "<script>alert('Lỗi: Bạn phải chọn ít nhất 1 vật tư để đưa vào phiếu kiểm kê!'); window.history.back();</script>";
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        if ($is_edit) {
            $check_sp = mysqli_query($conn, "SELECT id_phieu_kk FROM phieu_kiem_ke WHERE so_phieu='$so_phieu' AND id_phieu_kk != '$id_phieu_edit'");
            if(mysqli_num_rows($check_sp) > 0) throw new Exception("Số phiếu '$so_phieu' đã tồn tại!");

            mysqli_query($conn, "UPDATE phieu_kiem_ke SET so_phieu='$so_phieu', ngay_lap='$ngay_lap', ghi_chu='$ghi_chu' WHERE id_phieu_kk='$id_phieu_edit'");
            mysqli_query($conn, "DELETE FROM chi_tiet_kiem_ke WHERE id_phieu_kk='$id_phieu_edit'");
            $id_phieu_kk = $id_phieu_edit;
            $msg_alert = "Cập nhật phiếu kiểm kê thành công!";
        } else {
            $check_sp = mysqli_query($conn, "SELECT id_phieu_kk FROM phieu_kiem_ke WHERE so_phieu='$so_phieu'");
            if(mysqli_num_rows($check_sp) > 0) throw new Exception("Số phiếu '$so_phieu' đã tồn tại!");

            mysqli_query($conn, "INSERT INTO phieu_kiem_ke (so_phieu, ngay_lap, ghi_chu) VALUES ('$so_phieu', '$ngay_lap', '$ghi_chu')");
            $id_phieu_kk = mysqli_insert_id($conn);
            $msg_alert = "Tạo phiếu kiểm kê mới thành công!";
        }

        foreach ($_POST['item_keys'] as $val) {
            $parts = explode('_', $val);
            $id_vt_clean = intval($parts[0]);
            $id_kho_clean = isset($parts[1]) ? intval($parts[1]) : 0;
            $ton_he_thong = floatval($_POST['ton_he_thong_' . $val]);
            
            if ($has_id_kho) {
                $kho_val = ($id_kho_clean > 0) ? "'$id_kho_clean'" : "NULL";
                mysqli_query($conn, "INSERT INTO chi_tiet_kiem_ke (id_phieu_kk, id_vat_tu, id_kho, ton_he_thong) VALUES ('$id_phieu_kk', '$id_vt_clean', $kho_val, '$ton_he_thong')");
            } else {
                mysqli_query($conn, "INSERT INTO chi_tiet_kiem_ke (id_phieu_kk, id_vat_tu, ton_he_thong) VALUES ('$id_phieu_kk', '$id_vt_clean', '$ton_he_thong')");
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('$msg_alert'); window.location.href='qlk_qlpkk.php';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('LỖI: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}

// ==========================================
// 1.5. XỬ LÝ LƯU KẾT QUẢ KIỂM KÊ (SỐ THỰC TẾ)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_result') {
    $id_phieu_kk = intval($_POST['id_phieu_kk']);
    
    mysqli_begin_transaction($conn);
    try {
        if(isset($_POST['ton_thuc_te']) && is_array($_POST['ton_thuc_te'])) {
            foreach($_POST['ton_thuc_te'] as $key => $val_thuc_te) {
                $parts = explode('_', $key);
                $id_vt = intval($parts[0]);
                $id_kho = intval($parts[1]);
                
                // Nếu người dùng xóa trống ô nhập, lưu là NULL
                $thuc_te = ($val_thuc_te === '') ? "NULL" : floatval($val_thuc_te);
                
                if ($has_id_kho) {
                    $kho_cond = ($id_kho > 0) ? "id_kho='$id_kho'" : "(id_kho IS NULL OR id_kho=0)";
                } else {
                    $kho_cond = "1=1";
                }
                
                $sql_upd = "UPDATE chi_tiet_kiem_ke SET ton_thuc_te = $thuc_te WHERE id_phieu_kk='$id_phieu_kk' AND id_vat_tu='$id_vt' AND $kho_cond";
                if(!mysqli_query($conn, $sql_upd)) throw new Exception(mysqli_error($conn));
            }
        }
        mysqli_commit($conn);
        echo "<script>alert('Lưu kết quả đếm thực tế thành công!'); window.location.href='qlk_qlpkk.php';</script>";
        exit;
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Lỗi lưu kết quả: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}

// ==========================================
// 2. XỬ LÝ XÓA PHIẾU KIỂM KÊ
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM chi_tiet_kiem_ke WHERE id_phieu_kk='$id_xoa'");
        mysqli_query($conn, "DELETE FROM phieu_kiem_ke WHERE id_phieu_kk='$id_xoa'");
        mysqli_commit($conn);
        echo "<script>alert('Đã xóa phiếu kiểm kê!'); window.location.href='qlk_qlpkk.php';</script>";
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Lỗi: Không thể xóa phiếu!'); window.location.href='qlk_qlpkk.php';</script>";
    }
    exit;
}

// SINH MÃ TỰ ĐỘNG
$today_str = date('Y-m-d');
$prefix = "PKK-" . $today_str . "-";
$sql_max = "SELECT so_phieu FROM phieu_kiem_ke WHERE so_phieu LIKE '$prefix%' ORDER BY id_phieu_kk DESC LIMIT 1";
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

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phiếu Kiểm Kê - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-wrapper { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; align-items: center;}
        .filter-wrapper input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        
        .pkk-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 20px; margin-bottom: 20px; }
        .btn-toggle-form { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; display: inline-block; text-decoration: none;}
        input[type=checkbox] { transform: scale(1.5); cursor: pointer; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;}
        .data-table th, .data-table td { border: 1px solid #dee2e6; padding: 10px 12px; vertical-align: middle;}
        .data-table thead th { background-color: #343a40; color: #ffffff; text-transform: uppercase; font-size: 13px; font-weight: bold; position: sticky; top: 0;}
        .data-table tbody tr:hover { background-color: #f8f9fa; }
        
        .btn-view { background-color: #17a2b8; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; }
        .btn-edit { background-color: #ffc107; color:#212529; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; font-weight: bold;}
        .btn-delete { background-color: #dc3545; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; }
        .btn-input-result { background-color: #28a745; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; font-weight: bold; border: 1px solid #218838;}
        
        .btn-print { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-back { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin-right: 10px;}

        .modal-filter { background: #e9ecef; padding: 12px; border-radius: 6px; display: flex; gap: 15px; margin-bottom: 10px; align-items: center; border: 1px solid #ced4da; }
        .modal-filter input, .modal-filter select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; }
        
        .input-soluong { width: 100px; padding: 8px; border: 2px solid #007bff; border-radius: 4px; font-weight: bold; text-align: center; outline: none; }
        .input-soluong:focus { background-color: #e8f4ff; }

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
                // MÀN HÌNH 4: NHẬP KẾT QUẢ KIỂM KÊ (NEW)
                // ===================================================================
                if (isset($_GET['action']) && $_GET['action'] == 'input_result' && isset($_GET['id'])): 
                    $id_input = intval($_GET['id']);
                    $phieu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM phieu_kiem_ke WHERE id_phieu_kk = '$id_input'"));
                    if (!$phieu) { echo "<script>alert('Không tìm thấy phiếu!'); window.location.href='qlk_qlpkk.php';</script>"; exit; }
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #333;">NHẬP SỐ ĐẾM THỰC TẾ (PKK: <?=$phieu['so_phieu']?>)</h2>
                        <a href="qlk_qlpkk.php" class="btn-back">Hủy & Quay lại</a>
                    </div>

                    <div class="pkk-section">
                        <div style="background: #e8f4ff; border-left: 4px solid #007bff; padding: 15px; margin-bottom: 20px;">
                            <p style="margin: 0 0 5px 0;"><strong>Hướng dẫn:</strong> Điền số lượng bạn đếm được ngoài kho vào ô <b>"Tồn Thực Tế"</b>. Hệ thống sẽ tự động tính chênh lệch. Nếu dòng nào chưa đếm, hãy để trống.</p>
                        </div>
                        
                        <form method="POST" action="qlk_qlpkk.php">
                            <input type="hidden" name="action" value="save_result">
                            <input type="hidden" name="id_phieu_kk" value="<?=$id_input?>">
                            
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="15%" class="text-center">Mã VT</th>
                                        <th width="20%" class="text-left">Tên vật tư</th>
                                        <th width="15%" class="text-left">Kiểm tại Kho</th>
                                        <th width="10%" class="text-center">Tồn Máy</th>
                                        <th width="15%" class="text-center" style="background: #007bff;">✏️ TỒN THỰC TẾ</th>
                                        <th width="15%" class="text-center">KẾT QUẢ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $col_select = $has_ton_thuc_te ? "ct.ton_thuc_te," : "";
                                    
                                    if ($has_id_kho) {
                                        $sql_chitiet = "SELECT ct.id_vat_tu, ct.id_kho, ct.ton_he_thong, $col_select vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh, k.ten_kho
                                                        FROM chi_tiet_kiem_ke ct
                                                        JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                        LEFT JOIN kho k ON ct.id_kho = k.id_kho
                                                        WHERE ct.id_phieu_kk = '$id_input'
                                                        ORDER BY k.ten_kho ASC, vt.ten_vat_tu ASC";
                                    } else {
                                        $sql_chitiet = "SELECT ct.id_vat_tu, 0 as id_kho, ct.ton_he_thong, $col_select vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh, 'Chưa rõ' AS ten_kho
                                                        FROM chi_tiet_kiem_ke ct
                                                        JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                        WHERE ct.id_phieu_kk = '$id_input'
                                                        ORDER BY vt.ten_vat_tu ASC";
                                    }

                                    $result_ct = mysqli_query($conn, $sql_chitiet);
                                    $stt = 1;
                                    
                                    while ($item = mysqli_fetch_assoc($result_ct)) {
                                        $kho = !empty($item['ten_kho']) ? $item['ten_kho'] : "<i>Chưa phân kho</i>";
                                        $unique_key = $item['id_vat_tu'] . '_' . $item['id_kho'];
                                        $ton_he_thong = floatval($item['ton_he_thong']);
                                        
                                        $ton_thuc_te_val = ($has_ton_thuc_te && $item['ton_thuc_te'] !== null) ? $item['ton_thuc_te'] : "";

                                        echo "<tr>";
                                        echo "<td class='text-center'>{$stt}</td>";
                                        echo "<td class='text-center'><strong>{$item['ma_vat_tu']}</strong></td>";
                                        echo "<td class='text-left'>{$item['ten_vat_tu']}</td>";
                                        echo "<td class='text-left'>{$kho}</td>";
                                        echo "<td class='text-center' style='font-size: 15px;'><strong>{$ton_he_thong}</strong> {$item['ten_don_vi_tinh']}</td>";
                                        
                                        // Ô nhập liệu Live
                                        echo "<td class='text-center'>
                                                <input type='number' step='0.01' class='input-soluong' 
                                                       name='ton_thuc_te[{$unique_key}]' 
                                                       value='{$ton_thuc_te_val}' 
                                                       oninput='calcDiff(this, {$ton_he_thong}, \"diff_{$unique_key}\")'>
                                              </td>";
                                              
                                        // Chỗ hiển thị chênh lệch
                                        echo "<td class='text-center' id='diff_{$unique_key}' style='font-size: 14px;'></td>";
                                        echo "</tr>";
                                        
                                        // Gọi JS kích hoạt tính toán ngay khi load trang
                                        if ($ton_thuc_te_val !== "") {
                                            echo "<script>window.addEventListener('DOMContentLoaded', function() { 
                                                    let input = document.getElementsByName('ton_thuc_te[{$unique_key}]')[0];
                                                    calcDiff(input, {$ton_he_thong}, 'diff_{$unique_key}');
                                                  });</script>";
                                        }
                                        $stt++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div style="text-align: right; margin-top: 20px;">
                                <button type="submit" style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px;">💾 GHI NHẬN KẾT QUẢ</button>
                            </div>
                        </form>
                    </div>
                

                <?php 
                // ===================================================================
                // MÀN HÌNH 3: XEM CHI TIẾT & IN PHIẾU
                // ===================================================================
                elseif (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])): 
                    $id_view = intval($_GET['id']);
                    $phieu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM phieu_kiem_ke WHERE id_phieu_kk = '$id_view'"));
                    if (!$phieu) { echo "<script>alert('Không tìm thấy phiếu!'); window.location.href='qlk_qlpkk.php';</script>"; exit; }
                ?>
                    <div class="no-print" style="margin-bottom: 20px;">
                        <a href="qlk_qlpkk.php" class="btn-back">&laquo; Quay lại</a>
                        <button onclick="window.print()" class="btn-print">🖨 In Phiếu Kiểm Kê</button>
                    </div>

                    <div id="khuVucInPhieu" class="pkk-section">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="font-size: 24px; text-transform: uppercase; margin-bottom: 5px;">BÁO CÁO KẾT QUẢ KIỂM KÊ</h2>
                            <p>Số phiếu: <strong><?php echo $phieu['so_phieu']; ?></strong></p>
                        </div>

                        <div style="margin-bottom: 20px; line-height: 1.6; font-size: 16px;">
                            <p><strong>Ngày lập phiếu:</strong> <?php echo date('d/m/Y H:i', strtotime($phieu['ngay_lap'])); ?></p>
                            <p><strong>Ghi chú / Mục đích:</strong> <?php echo htmlspecialchars($phieu['ghi_chu']); ?></p>
                        </div>

                        <table class="data-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">STT</th>
                                    <th width="15%" class="text-center">Mã VT</th>
                                    <th width="25%" class="text-left">Tên vật tư</th>
                                    <th width="10%" class="text-center">ĐVT</th>
                                    <th width="15%" class="text-left">Kiểm tại Kho</th>
                                    <th width="10%" class="text-center">Tồn Máy</th>
                                    <th width="10%" class="text-center">Thực Tế</th>
                                    <th width="10%" class="text-center">Chênh Lệch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $col_select = $has_ton_thuc_te ? "ct.ton_thuc_te," : "";
                                if ($has_id_kho) {
                                    $sql_chitiet = "SELECT ct.*, $col_select vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh, k.ten_kho
                                                    FROM chi_tiet_kiem_ke ct
                                                    JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                    LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                    LEFT JOIN kho k ON ct.id_kho = k.id_kho
                                                    WHERE ct.id_phieu_kk = '$id_view'
                                                    ORDER BY k.ten_kho ASC, vt.ten_vat_tu ASC";
                                } else {
                                    $sql_chitiet = "SELECT ct.*, $col_select vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh, 
                                                           GROUP_CONCAT(DISTINCT k.ten_kho SEPARATOR ', ') AS ten_kho
                                                    FROM chi_tiet_kiem_ke ct
                                                    JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                    LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                    LEFT JOIN ton_kho tk ON vt.id_vat_tu = tk.id_vat_tu
                                                    LEFT JOIN kho k ON tk.id_kho = k.id_kho
                                                    WHERE ct.id_phieu_kk = '$id_view'
                                                    GROUP BY ct.id_vat_tu
                                                    ORDER BY vt.ten_vat_tu ASC";
                                }

                                $result_ct = mysqli_query($conn, $sql_chitiet);
                                $stt = 1;
                                
                                while ($item = mysqli_fetch_assoc($result_ct)) {
                                    $kho = !empty($item['ten_kho']) ? $item['ten_kho'] : "<i>Chưa phân kho</i>";
                                    echo "<tr>";
                                    echo "<td class='text-center'>{$stt}</td>";
                                    echo "<td class='text-center'><strong>{$item['ma_vat_tu']}</strong></td>";
                                    echo "<td class='text-left'>{$item['ten_vat_tu']}</td>";
                                    echo "<td class='text-center'>{$item['ten_don_vi_tinh']}</td>";
                                    echo "<td class='text-left'>{$kho}</td>";
                                    echo "<td class='text-center'><strong>{$item['ton_he_thong']}</strong></td>";
                                    
                                    if ($has_ton_thuc_te && $item['ton_thuc_te'] !== null) {
                                        echo "<td class='text-center' style='font-size:15px; font-weight:bold;'>{$item['ton_thuc_te']}</td>";
                                        $diff = $item['ton_thuc_te'] - $item['ton_he_thong'];
                                        if ($diff > 0) {
                                            echo "<td class='text-center' style='color:#28a745; font-weight:bold;'>+{$diff}</td>";
                                        } elseif ($diff < 0) {
                                            echo "<td class='text-center' style='color:#dc3545; font-weight:bold;'>{$diff}</td>";
                                        } else {
                                            echo "<td class='text-center' style='color:#007bff; font-weight:bold;'>Khớp</td>";
                                        }
                                    } else {
                                        echo "<td class='text-center' style='color:#ccc'>-</td>";
                                        echo "<td class='text-center' style='color:#ccc'>-</td>";
                                    }
                                    
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
                                <p>Thủ kho / Tổ kiểm kê</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, họ tên)</p>
                            </div>
                            <div>
                                <p>Kế toán trưởng</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, duyệt)</p>
                            </div>
                        </div>
                    </div>


                <?php 
                // ===================================================================
                // MÀN HÌNH 2: TẠO MỚI / SỬA PHIẾU
                // ===================================================================
                elseif (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])):
                    $is_edit = ($_GET['action'] == 'edit');
                    $edit_id = 0;
                    
                    $val_so_phieu = $auto_so_phieu;
                    $val_ngay_lap = date('Y-m-d\TH:i');
                    $val_ghi_chu = "";
                    $title = "TẠO PHIẾU KIỂM KÊ MỚI";

                    if ($is_edit && isset($_GET['id'])) {
                        $edit_id = intval($_GET['id']);
                        $phieu_edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM phieu_kiem_ke WHERE id_phieu_kk = '$edit_id'"));
                        if ($phieu_edit) {
                            $val_so_phieu = $phieu_edit['so_phieu'];
                            $val_ngay_lap = date('Y-m-d\TH:i', strtotime($phieu_edit['ngay_lap']));
                            $val_ghi_chu = $phieu_edit['ghi_chu'];
                            $title = "SỬA PHIẾU KIỂM KÊ (ID: $edit_id)";
                        } else {
                            $is_edit = false;
                        }
                    }
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #333;"><?=$title?></h2>
                        <a href="qlk_qlpkk.php" class="btn-back">Hủy & Quay lại</a>
                    </div>

                    <div class="pkk-section">
                        <form method="POST" action="qlk_qlpkk.php">
                            <input type="hidden" name="action" value="save_pkk">
                            <input type="hidden" name="edit_id" value="<?=$edit_id?>">
                            
                            <div class="form-grid">
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Số Phiếu PKK:</label>
                                    <input type="text" name="so_phieu" required value="<?=htmlspecialchars($val_so_phieu)?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ngày lập:</label>
                                    <input type="datetime-local" name="ngay_lap" required value="<?=$val_ngay_lap?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Ghi chú đợt kiểm kê:</label>
                                    <input type="text" name="ghi_chu" placeholder="Lý do..." value="<?=htmlspecialchars($val_ghi_chu)?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                            </div>

                            <h4 style="margin-bottom: 10px;">Danh mục Vật tư (Tick chọn để đưa vào danh sách kiểm kê):</h4>
                            
                            <div class="modal-filter">
                                <strong style="color: #495057;">🔍 Bộ lọc danh sách kiểm kê:</strong>
                                <input type="text" id="filter_txt" onkeyup="filterVatTuModal()" placeholder="Tìm mã hoặc tên vật tư..." style="width: 250px;">
                                <select id="filter_k" onchange="filterVatTuModal()">
                                    <option value="">-- Tất cả các kho --</option>
                                    <?php 
                                    $k_res = mysqli_query($conn, "SELECT ten_kho FROM kho ORDER BY ten_kho ASC");
                                    while ($k_row = mysqli_fetch_assoc($k_res)) {
                                        echo "<option value='{$k_row['ten_kho']}'>{$k_row['ten_kho']}</option>";
                                    }
                                    ?>
                                </select>
                                <span style="font-size: 12px; color: #6c757d; margin-left: auto;">(Lọc nhanh không cần tải lại trang)</span>
                            </div>
                            
                            <div style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6;">
                                <table class="data-table" style="margin-top: 0;" id="bangVatTuModal">
                                    <thead>
                                        <tr>
                                            <th width="5%" class="text-center">
                                                <input type="checkbox" id="checkAll" onclick="chonTatCa(this)" title="Chỉ chọn những dòng đang hiển thị">
                                            </th>
                                            <th width="15%" class="text-center">Mã VT</th>
                                            <th width="30%" class="text-left">Tên Vật tư</th>
                                            <th width="20%" class="text-left">Tồn tại Kho</th>
                                            <th width="15%" class="text-center">Tồn hệ thống</th>
                                            <th width="15%" class="text-center">ĐVT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql_vt = "SELECT 
                                                        vt.id_vat_tu, vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh,
                                                        tk.id_kho, IFNULL(tk.so_luong_ton, 0) AS ton_tong_cong,
                                                        k.ten_kho ";
                                        if ($has_id_kho) {
                                            $sql_vt .= ", ct.id_phieu_kk, ct.ton_he_thong AS saved_ton 
                                                        FROM vat_tu vt
                                                        LEFT JOIN ton_kho tk ON vt.id_vat_tu = tk.id_vat_tu
                                                        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                        LEFT JOIN kho k ON tk.id_kho = k.id_kho
                                                        LEFT JOIN chi_tiet_kiem_ke ct ON vt.id_vat_tu = ct.id_vat_tu AND IFNULL(tk.id_kho, 0) = IFNULL(ct.id_kho, 0) AND ct.id_phieu_kk = '$edit_id' ";
                                        } else {
                                            $sql_vt .= ", ct.id_phieu_kk, ct.ton_he_thong AS saved_ton 
                                                        FROM vat_tu vt
                                                        LEFT JOIN ton_kho tk ON vt.id_vat_tu = tk.id_vat_tu
                                                        LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                        LEFT JOIN kho k ON tk.id_kho = k.id_kho
                                                        LEFT JOIN chi_tiet_kiem_ke ct ON vt.id_vat_tu = ct.id_vat_tu AND ct.id_phieu_kk = '$edit_id' ";
                                        }
                                        $sql_vt .= "ORDER BY k.ten_kho ASC, vt.ten_vat_tu ASC";
                                                        
                                        $result_vt = mysqli_query($conn, $sql_vt);
                                        while ($vt = mysqli_fetch_assoc($result_vt)) {
                                            
                                            $id_kho_val = !empty($vt['id_kho']) ? $vt['id_kho'] : 0;
                                            $unique_key = $vt['id_vat_tu'] . '_' . $id_kho_val;
                                            
                                            $is_checked = ($vt['id_phieu_kk'] != null) ? "checked" : "";
                                            $ton_hien_thi = ($is_checked && $is_edit) ? $vt['saved_ton'] : $vt['ton_tong_cong'];
                                            $kho = !empty($vt['ten_kho']) ? $vt['ten_kho'] : "<span style='color:#ccc'>Chưa có trong kho</span>";

                                            echo "<tr class='row-vt'>";
                                            echo "<td class='text-center'>
                                                    <input type='checkbox' name='item_keys[]' value='{$unique_key}' class='chk-vattu' $is_checked>
                                                    <input type='hidden' name='ton_he_thong_{$unique_key}' value='$ton_hien_thi'>
                                                  </td>";
                                            echo "<td class='text-center'><strong>{$vt['ma_vat_tu']}</strong></td>";
                                            echo "<td class='text-left'>{$vt['ten_vat_tu']}</td>";
                                            echo "<td class='text-left'>{$kho}</td>";
                                            echo "<td class='text-center' style='font-weight:bold; color:#007bff;'>$ton_hien_thi</td>";
                                            echo "<td class='text-center'>{$vt['ten_don_vi_tinh']}</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="text-align: right; margin-top: 20px;">
                                <button type="submit" style="padding: 12px 25px; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px;">💾 LƯU PHIẾU KIỂM KÊ</button>
                            </div>
                        </form>
                    </div>


                <?php 
                // ===================================================================
                // MÀN HÌNH 1: DANH SÁCH LỊCH SỬ PHIẾU KIỂM KÊ (Mặc định)
                // ===================================================================
                else: 
                    // XỬ LÝ LỌC DỮ LIỆU BÊN NGOÀI
                    $where = "1=1";
                    if ($search != "") $where .= " AND (so_phieu LIKE '%$search%' OR ghi_chu LIKE '%$search%')";
                    if ($tu_ngay != "") $where .= " AND DATE(ngay_lap) >= '$tu_ngay'";
                    if ($den_ngay != "") $where .= " AND DATE(ngay_lap) <= '$den_ngay'";
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #007bff;">Quản lý Phiếu Kiểm Kê</h2>
                        <a href="?action=add" class="btn-toggle-form">+ Lập Phiếu Kiểm Kê Mới</a>
                    </div>

                    <form class="filter-wrapper" method="GET" action="qlk_qlpkk.php">
                        <input type="text" name="search" placeholder="Nhập số phiếu hoặc ghi chú..." value="<?=htmlspecialchars($search)?>" style="width: 250px;">
                        
                        <span style="font-weight: bold; color: #555;">Từ ngày:</span>
                        <input type="date" name="tu_ngay" value="<?=htmlspecialchars($tu_ngay)?>">
                        
                        <span style="font-weight: bold; color: #555;">Đến ngày:</span>
                        <input type="date" name="den_ngay" value="<?=htmlspecialchars($den_ngay)?>">

                        <button type="submit" class="btn-search">Lọc dữ liệu</button>
                        
                        <?php if($search != "" || $tu_ngay != "" || $den_ngay != ""): ?>
                            <a href="qlk_qlpkk.php" class="btn-clear">Xóa lọc</a>
                        <?php endif; ?>
                    </form>

                    <div class="pkk-section">
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">ID</th>
                                        <th width="15%" class="text-left">Số Phiếu</th>
                                        <th width="15%" class="text-center">Ngày Lập</th>
                                        <th width="30%" class="text-left">Ghi chú</th>
                                        <th width="35%" class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_list = "SELECT * FROM phieu_kiem_ke WHERE $where ORDER BY ngay_lap DESC, id_phieu_kk DESC";
                                    $result_list = @mysqli_query($conn, $sql_list);
                                    
                                    if ($result_list && mysqli_num_rows($result_list) > 0) {
                                        while ($pkk = mysqli_fetch_assoc($result_list)) {
                                            $ngay_lap = date('d/m/Y H:i', strtotime($pkk['ngay_lap']));
                                            echo "<tr>";
                                            echo "<td class='text-center'>{$pkk['id_phieu_kk']}</td>";
                                            echo "<td class='text-left'><span style='color: #007bff; font-weight: bold; font-size: 15px;'>{$pkk['so_phieu']}</span></td>";
                                            echo "<td class='text-center'><span style='background:#e9ecef; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:bold;'>{$ngay_lap}</span></td>";
                                            echo "<td class='text-left' style='color: #555;'>{$pkk['ghi_chu']}</td>";
                                            
                                            echo "<td class='text-center'>
                                                    <div style='display: flex; justify-content: center; gap: 8px;'>
                                                        <a href='?action=input_result&id={$pkk['id_phieu_kk']}' class='btn-input-result'>📝 Nhập KQ</a>
                                                        <a href='?action=view&id={$pkk['id_phieu_kk']}' class='btn-view'>👁 Xem/In</a>
                                                        <a href='?action=edit&id={$pkk['id_phieu_kk']}' class='btn-edit'>✎ Sửa</a>
                                                        <a href='?action=delete&id={$pkk['id_phieu_kk']}' class='btn-delete' onclick='return confirm(\"Xóa phiếu kiểm kê này sẽ xóa luôn danh sách chi tiết bên trong. Bạn có chắc chắn không?\");'>🗑 Xóa</a>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center' style='padding: 30px; color: #888;'>Không tìm thấy phiếu kiểm kê nào phù hợp.</td></tr>";
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
        // JS Tìm kiếm trực tiếp trong bảng Modal
        function filterVatTuModal() {
            var txt = document.getElementById("filter_txt").value.toLowerCase();
            var kho = document.getElementById("filter_k").value.toLowerCase();
            var rows = document.querySelectorAll(".row-vt");

            rows.forEach(function(row) {
                var ma = row.children[1].innerText.toLowerCase();
                var ten = row.children[2].innerText.toLowerCase();
                var tenKho = row.children[3].innerText.toLowerCase();

                var matchTxt = (txt === "" || ma.includes(txt) || ten.includes(txt));
                var matchKho = (kho === "" || tenKho === kho);

                if (matchTxt && matchKho) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        // JS Check All (Chỉ check những ô ĐANG HIỂN THỊ sau khi lọc)
        function chonTatCa(source) {
            var checkboxes = document.querySelectorAll('.chk-vattu');
            for(var i=0, n=checkboxes.length; i<n; i++) {
                if (checkboxes[i].closest('tr').style.display !== 'none') {
                    checkboxes[i].checked = source.checked;
                }
            }
        }
        
        // JS Tự động tính chênh lệch trong màn hình Nhập Kết Quả
        function calcDiff(inputElem, tonHeThong, diffId) {
            let val = parseFloat(inputElem.value);
            let diffTd = document.getElementById(diffId);
            
            if (isNaN(val)) {
                diffTd.innerHTML = "<span style='color:#ccc'>Chưa nhập</span>";
                return;
            }
            
            let diff = val - tonHeThong;
            if (diff > 0) {
                diffTd.innerHTML = "<strong style='color:#28a745'>+" + diff + " (Thừa)</strong>";
            } else if (diff < 0) {
                diffTd.innerHTML = "<strong style='color:#dc3545'>" + diff + " (Thiếu)</strong>";
            } else {
                diffTd.innerHTML = "<strong style='color:#007bff'>Khớp 100%</strong>";
            }
        }
    </script>
</body>
</html>
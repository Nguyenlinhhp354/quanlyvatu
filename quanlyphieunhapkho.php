<?php
session_start();
include 'db_connect.php'; 

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 

// =================================================================================
// 2. XỬ LÝ LƯU PHIẾU NHẬP (TẠO PHIẾU + TẠO VẬT TƯ MỚI + THÊM CHI TIẾT + CỘNG TỒN KHO)
// =================================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_phieu') {
    $so_phieu = mysqli_real_escape_string($conn, $_POST['so_phieu']);
    $ngay_nhap = $_POST['ngay_nhap'];
    $id_kho = intval($_POST['id_kho']);
    $id_ncc = intval($_POST['id_ncc']);
    $ghi_chu = mysqli_real_escape_string($conn, $_POST['ghi_chu']);
    
    // Bắt đầu Giao dịch an toàn (Transaction)
    mysqli_begin_transaction($conn);
    try {
        $check_sp = mysqli_query($conn, "SELECT id_phieu_nhap FROM phieu_nhap_kho WHERE so_phieu='$so_phieu'");
        if(mysqli_num_rows($check_sp) > 0) throw new Exception("Số phiếu nhập '$so_phieu' đã tồn tại! Vui lòng tải lại trang để hệ thống cấp số mới.");

        $sql_phieu = "INSERT INTO phieu_nhap_kho (so_phieu, ngay_nhap, id_kho, id_ncc, id_nguoi_lap, ghi_chu) 
                      VALUES ('$so_phieu', '$ngay_nhap', '$id_kho', '$id_ncc', '$id_nguoi_dung', '$ghi_chu')";
        if(!mysqli_query($conn, $sql_phieu)) throw new Exception("Không thể tạo phiếu nhập!");
        $new_phieu_id = mysqli_insert_id($conn);

        if(!isset($_POST['vt_type']) || empty($_POST['vt_type'])) throw new Exception("Phiếu nhập phải có ít nhất 1 vật tư!");

        $types = $_POST['vt_type'];
        $old_ids = $_POST['id_vat_tu_old'];
        $new_mas = $_POST['new_ma_vt'];
        $new_tens = $_POST['new_ten_vt'];
        $new_dvts = $_POST['new_id_dvt'];
        $so_luongs = $_POST['so_luong'];
        $don_gias = $_POST['don_gia'];

        for($i = 0; $i < count($types); $i++) {
            $sl = floatval($so_luongs[$i]);
            $gia = floatval($don_gias[$i]);
            if($sl <= 0) throw new Exception("Số lượng nhập phải lớn hơn 0!");

            $final_vt_id = 0;

            if($types[$i] == 'old') {
                $final_vt_id = intval($old_ids[$i]);
                if($final_vt_id == 0) throw new Exception("Vui lòng chọn một vật tư hợp lệ!");
            } else {
                $ma_moi = mysqli_real_escape_string($conn, trim($new_mas[$i]));
                $ten_moi = mysqli_real_escape_string($conn, trim($new_tens[$i]));
                $dvt_moi = intval($new_dvts[$i]);

                if(empty($ma_moi) || empty($ten_moi)) throw new Exception("Vui lòng nhập đủ Mã và Tên cho vật tư mới!");
                
                $check_ma = mysqli_query($conn, "SELECT id_vat_tu FROM vat_tu WHERE ma_vat_tu='$ma_moi'");
                if(mysqli_num_rows($check_ma) > 0) throw new Exception("Mã vật tư '$ma_moi' đã tồn tại, hãy chọn từ danh mục!");

                // Đã thay id_kho bằng don_gia cho bảng vat_tu
                $sql_vt = "INSERT INTO vat_tu (ma_vat_tu, ten_vat_tu, id_dvt, don_gia) VALUES ('$ma_moi', '$ten_moi', '$dvt_moi', '$gia')";
                if(!mysqli_query($conn, $sql_vt)) throw new Exception("Lỗi khi tạo mới vật tư '$ma_moi'!");
                $final_vt_id = mysqli_insert_id($conn);
            }

            $sql_ct = "INSERT INTO chi_tiet_nhap_kho (id_phieu_nhap, id_vat_tu, so_luong, don_gia) 
                       VALUES ('$new_phieu_id', '$final_vt_id', '$sl', '$gia')";
            if(!mysqli_query($conn, $sql_ct)) throw new Exception("Lỗi khi lưu chi tiết vật tư!");

            // Cập nhật tồn kho
            $check_ton = mysqli_query($conn, "SELECT so_luong_ton FROM ton_kho WHERE id_kho='$id_kho' AND id_vat_tu='$final_vt_id'");
            if(mysqli_num_rows($check_ton) > 0) {
                mysqli_query($conn, "UPDATE ton_kho SET so_luong_ton = so_luong_ton + $sl WHERE id_kho='$id_kho' AND id_vat_tu='$final_vt_id'");
            } else {
                mysqli_query($conn, "INSERT INTO ton_kho (id_kho, id_vat_tu, so_luong_ton) VALUES ('$id_kho', '$final_vt_id', '$sl')");
            }
        }

        mysqli_commit($conn); 
        echo "<script>alert('Tạo phiếu nhập và cập nhật tồn kho thành công!'); window.location.href='quanlyphieunhapkho.php';</script>";
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn); 
        $error_msg = $e->getMessage();
        echo "<script>alert('$error_msg'); window.location.href='quanlyphieunhapkho.php';</script>";
        exit();
    }
}

// =================================================================================
// 3. XỬ LÝ XÓA PHIẾU (TRỪ LẠI TỒN KHO)
// =================================================================================
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    
    mysqli_begin_transaction($conn);
    try {
        $p_res = mysqli_query($conn, "SELECT id_kho FROM phieu_nhap_kho WHERE id_phieu_nhap='$id_del'");
        if(mysqli_num_rows($p_res) == 0) throw new Exception("Không tìm thấy phiếu!");
        $id_kho_del = mysqli_fetch_assoc($p_res)['id_kho'];

        $ct_res = mysqli_query($conn, "SELECT id_vat_tu, so_luong FROM chi_tiet_nhap_kho WHERE id_phieu_nhap='$id_del'");
        while($ct = mysqli_fetch_assoc($ct_res)) {
            $vt_del = $ct['id_vat_tu'];
            $sl_del = $ct['so_luong'];
            mysqli_query($conn, "UPDATE ton_kho SET so_luong_ton = so_luong_ton - $sl_del WHERE id_kho='$id_kho_del' AND id_vat_tu='$vt_del'");
        }

        mysqli_query($conn, "DELETE FROM chi_tiet_nhap_kho WHERE id_phieu_nhap='$id_del'");
        mysqli_query($conn, "DELETE FROM phieu_nhap_kho WHERE id_phieu_nhap='$id_del'");

        mysqli_commit($conn);
        echo "<script>alert('Đã xóa phiếu và tự động trừ lại tồn kho!'); window.location.href='quanlyphieunhapkho.php';</script>";
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Lỗi khi xóa: ".$e->getMessage()."');</script>";
    }
}

// =================================================================================
// TẠO MÃ PHIẾU TỰ ĐỘNG & LẤY DANH SÁCH DROPDOWN
// =================================================================================
$today_str = date('Y-m-d');
$prefix_pnk = "PNK-" . $today_str . "-";

$sql_max_pnk = "SELECT so_phieu FROM phieu_nhap_kho WHERE so_phieu LIKE '$prefix_pnk%' ORDER BY id_phieu_nhap DESC LIMIT 1";
$res_max_pnk = mysqli_query($conn, $sql_max_pnk);

$next_stt = 1;
if ($res_max_pnk && mysqli_num_rows($res_max_pnk) > 0) {
    $row_max = mysqli_fetch_assoc($res_max_pnk);
    $parts = explode('-', $row_max['so_phieu']);
    $next_stt = intval(end($parts)) + 1;
}
$auto_so_phieu = $prefix_pnk . $next_stt;

$kho_res = mysqli_query($conn, "SELECT id_kho, ten_kho FROM kho ORDER BY ten_kho ASC");
$kho_list = []; while($k = mysqli_fetch_assoc($kho_res)) { $kho_list[] = $k; }

$dvt_res = mysqli_query($conn, "SELECT id_dvt, ten_don_vi_tinh FROM don_vi_tinh");
$dvt_list = []; while($d = mysqli_fetch_assoc($dvt_res)) { $dvt_list[] = $d; }

$vt_res = mysqli_query($conn, "SELECT id_vat_tu, ma_vat_tu, ten_vat_tu FROM vat_tu ORDER BY ten_vat_tu ASC");
$vt_list = []; while($v = mysqli_fetch_assoc($vt_res)) { $vt_list[] = $v; }

$ncc_list = [];
$ncc_res = @mysqli_query($conn, "SELECT id_ncc, ten_ncc FROM nha_cung_cap");
if($ncc_res) { while($n = mysqli_fetch_assoc($ncc_res)) { $ncc_list[] = $n; } }

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Phiếu nhập kho - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center;}
        .btn-add { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;}
        .btn-add:hover { background-color: #218838; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 10px; border: 1px solid #e0e0e0; vertical-align: middle; }
        .data-table thead th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 13px; }
        
        .modal-box-large { background: white; padding: 25px; border-radius: 8px; width: 900px; max-height: 90vh; overflow-y: auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;}
        
        .table-input { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table-input th { background: #e9ecef; padding: 10px; text-align: center; border: 1px solid #ccc; font-size: 13px;}
        .table-input td { padding: 8px; border: 1px solid #ccc; vertical-align: top;}
        .table-input input, .table-input select { width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
        
        .btn-remove-row { background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; }

        .btn-print { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-back { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin-right: 10px;}

        @media print {
            body * { visibility: hidden; }
            #khuVucInPhieu, #khuVucInPhieu * { visibility: visible; }
            #khuVucInPhieu { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0;}
            .no-print { display: none !important; }
            .print-table { border-collapse: collapse; width: 100%; margin-top: 15px;}
            .print-table th, .print-table td { border: 1px solid #000 !important; padding: 10px; color: #000; text-align: center;}
            .print-table th { background-color: #e9ecef !important; -webkit-print-color-adjust: exact; }
            .print-signature { display: flex; justify-content: space-around; margin-top: 40px; text-align: center; font-weight: bold; color: #000; }
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
                if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])): 
                    $id_view = intval($_GET['id']);
                    
                    $sql_phieu = "SELECT p.*, k.ten_kho, nd.ho_ten as nguoi_lap
                                  FROM phieu_nhap_kho p
                                  LEFT JOIN kho k ON p.id_kho = k.id_kho
                                  LEFT JOIN nguoi_dung nd ON p.id_nguoi_lap = nd.id_nguoi_dung
                                  WHERE p.id_phieu_nhap = '$id_view'";
                    $phieu = mysqli_fetch_assoc(mysqli_query($conn, $sql_phieu));
                    
                    if (!$phieu) {
                        echo "<script>alert('Không tìm thấy phiếu nhập này!'); window.location.href='quanlyphieunhapkho.php';</script>";
                        exit;
                    }

                    $ten_ncc = "Không có dữ liệu";
                    if ($phieu['id_ncc'] > 0) {
                        $ncc_check = @mysqli_query($conn, "SELECT ten_ncc FROM nha_cung_cap WHERE id_ncc='{$phieu['id_ncc']}'");
                        if ($ncc_check && mysqli_num_rows($ncc_check) > 0) {
                            $ten_ncc = mysqli_fetch_assoc($ncc_check)['ten_ncc'];
                        }
                    }
                ?>
                    <div class="no-print" style="margin-bottom: 20px;">
                        <a href="quanlyphieunhapkho.php" class="btn-back">&laquo; Quay lại</a>
                        <button onclick="window.print()" class="btn-print">🖨 In Phiếu Nhập</button>
                    </div>

                    <div id="khuVucInPhieu" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;">
                            <div>
                                <h3 style="margin:0; color:#000;">CÔNG TY CỔ PHẦN THỊNH TIẾN</h3>
                                <p style="margin:5px 0 0 0; font-size:14px; color:#333;">Giải pháp Vật tư hàng đầu</p>
                            </div>
                            <div style="text-align: right;">
                                <p style="margin:0; font-weight:bold; color:#000;">Mẫu số: 01-VT</p>
                                <p style="margin:5px 0 0 0; font-size:14px; color:#333;">Ban hành theo Thông tư số 200/2014/TT-BTC</p>
                            </div>
                        </div>

                        <div style="text-align: center; margin-bottom: 25px;">
                            <h2 style="margin: 0; font-size: 26px; text-transform: uppercase; color:#000;">Phiếu Nhập Kho</h2>
                            <p style="margin: 5px 0; font-size: 16px; color:#000;">Ngày nhập: <strong><?=date('d/m/Y', strtotime($phieu['ngay_nhap']))?></strong></p>
                            <p style="margin: 0; font-size: 14px; color:#000;">Số phiếu: <strong><?=$phieu['so_phieu']?></strong></p>
                        </div>

                        <div style="margin-bottom: 20px; font-size: 16px; line-height: 1.6; color:#000;">
                            <p style="margin:0;">- Họ tên người giao hàng / NCC: <strong><?=$ten_ncc?></strong></p>
                            <p style="margin:0;">- Nhập tại kho: <strong><?=$phieu['ten_kho']?></strong></p>
                            <p style="margin:0;">- Diễn giải / Ghi chú: <?=$phieu['ghi_chu']?></p>
                        </div>

                        <table class="print-table">
                            <thead>
                                <tr>
                                    <th width="5%">STT</th>
                                    <th width="15%">Mã vật tư</th>
                                    <th width="30%">Tên vật tư</th>
                                    <th width="10%">ĐVT</th>
                                    <th width="10%">Số lượng</th>
                                    <th width="15%">Đơn giá</th>
                                    <th width="15%">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_ct = "SELECT ct.*, vt.ma_vat_tu, vt.ten_vat_tu, d.ten_don_vi_tinh 
                                           FROM chi_tiet_nhap_kho ct 
                                           JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu 
                                           LEFT JOIN don_vi_tinh d ON vt.id_dvt = d.id_dvt 
                                           WHERE ct.id_phieu_nhap = '$id_view'";
                                $result_ct = mysqli_query($conn, $sql_ct);
                                $stt = 1;
                                $tong_tien = 0;
                                
                                while ($item = mysqli_fetch_assoc($result_ct)) {
                                    $thanh_tien = $item['so_luong'] * $item['don_gia'];
                                    $tong_tien += $thanh_tien;
                                    echo "<tr>";
                                    echo "<td>{$stt}</td>";
                                    echo "<td>{$item['ma_vat_tu']}</td>";
                                    echo "<td style='text-align:left;'>{$item['ten_vat_tu']}</td>";
                                    echo "<td>{$item['ten_don_vi_tinh']}</td>";
                                    echo "<td><strong>{$item['so_luong']}</strong></td>";
                                    echo "<td style='text-align:right;'>" . number_format($item['don_gia'], 0, ',', '.') . "</td>";
                                    echo "<td style='text-align:right; font-weight:bold;'>" . number_format($thanh_tien, 0, ',', '.') . " đ</td>";
                                    echo "</tr>";
                                    $stt++;
                                }
                                ?>
                                <tr>
                                    <td colspan="6" style="text-align:right; font-weight:bold; text-transform:uppercase; font-size:16px;">Cộng thành tiền:</td>
                                    <td style="text-align:right; font-weight:bold; color:#dc3545; font-size:16px;"><?=number_format($tong_tien, 0, ',', '.')?> đ</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="print-signature">
                            <div>
                                <p>Người lập phiếu</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, họ tên)</p>
                                <br><br><br>
                                <p><?=$phieu['nguoi_lap']?></p>
                            </div>
                            <div>
                                <p>Người giao hàng</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, họ tên)</p>
                            </div>
                            <div>
                                <p>Thủ kho</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, họ tên)</p>
                            </div>
                            <div>
                                <p>Kế toán trưởng</p>
                                <p style="font-weight: normal; font-style: italic; font-size: 13px;">(Ký, họ tên)</p>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #007bff;">Quản lý Phiếu Nhập Kho</h2>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <form method="GET" style="display: flex; gap: 5px;">
                            <input type="text" name="search" placeholder="Nhập số phiếu tìm kiếm..." value="<?=htmlspecialchars($search)?>" style="padding: 8px 12px; width: 250px; border: 1px solid #ccc; border-radius: 4px;">
                            <button type="submit" style="padding: 8px 15px; background:#007bff; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">Tìm kiếm</button>
                        </form>
                        <button class="btn-add" onclick="openAddModal()">+ Thêm Phiếu Nhập Mới</button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">ID</th>
                                    <th width="15%" class="text-left">Số Phiếu</th>
                                    <th width="15%" class="text-center">Ngày Nhập</th>
                                    <th width="20%" class="text-left">Kho Nhập</th>
                                    <th width="15%" class="text-left">Người Lập</th>
                                    <th width="20%" class="text-left">Ghi chú</th>
                                    <th width="10%" class="text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = "SELECT p.*, k.ten_kho, nd.ho_ten as nguoi_lap 
                                      FROM phieu_nhap_kho p 
                                      LEFT JOIN kho k ON p.id_kho = k.id_kho
                                      LEFT JOIN nguoi_dung nd ON p.id_nguoi_lap = nd.id_nguoi_dung";
                                if($search) $q .= " WHERE p.so_phieu LIKE '%$search%'";
                                $q .= " ORDER BY p.id_phieu_nhap DESC";
                                
                                $res = mysqli_query($conn, $q);
                                if (mysqli_num_rows($res) > 0) {
                                    while($r = mysqli_fetch_assoc($res)) {
                                        $ngay_nhap = date('d/m/Y H:i', strtotime($r['ngay_nhap']));
                                        echo "<tr>";
                                        echo "<td class='text-center'>".$r['id_phieu_nhap']."</td>";
                                        echo "<td class='text-left' style='font-weight:bold; color:#007bff;'>".$r['so_phieu']."</td>";
                                        echo "<td class='text-center'>".$ngay_nhap."</td>";
                                        echo "<td class='text-left'>".$r['ten_kho']."</td>";
                                        echo "<td class='text-left'>".$r['nguoi_lap']."</td>";
                                        echo "<td class='text-left'>".$r['ghi_chu']."</td>";
                                        echo "<td class='text-center'>
                                                <div style='display: flex; justify-content: center; gap: 8px;'>
                                                    <a href='quanlyphieunhapkho.php?action=view&id=".$r['id_phieu_nhap']."' class='btn-action btn-view' style='background:#17a2b8; color:white; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:13px;'>👁 Xem / In</a>
                                                    <a href='quanlyphieunhapkho.php?delete=".$r['id_phieu_nhap']."' class='btn-action btn-delete' style='padding:6px 12px; font-size:13px;' onclick='return confirm(\"CẢNH BÁO: Xóa phiếu nhập này sẽ tự động trừ số lượng vật tư tương ứng trong kho. Bạn có chắc chắn?\");'>🗑 Xóa</a>
                                                </div>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center' style='padding: 30px; color:#888;'>Không có dữ liệu phiếu nhập!</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div id="addModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-box-large">
            <h3 style="color: #28a745; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Lập Phiếu Nhập Kho</h3>
            <form method="POST" action="quanlyphieunhapkho.php" id="formNhapKho">
                <input type="hidden" name="action" value="add_phieu">
                
                <div class="form-grid">
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Số phiếu (Tự động):</label>
                        <input type="text" name="so_phieu" required value="<?=$auto_so_phieu?>" readonly style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #e9ecef; font-weight: bold; color: #dc3545; outline: none;">
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Ngày nhập:</label>
                        <input type="datetime-local" name="ngay_nhap" required value="<?=date('Y-m-d\TH:i')?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Kho nhập vào:</label>
                        <select name="id_kho" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <?php foreach($kho_list as $k): ?>
                                <option value="<?=$k['id_kho']?>"><?=$k['ten_kho']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Nhà cung cấp:</label>
                        <?php if(!empty($ncc_list)): ?>
                            <select name="id_ncc" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="0">-- Chọn nhà cung cấp --</option>
                                <?php foreach($ncc_list as $n): ?>
                                    <option value="<?=$n['id_ncc']?>"><?=$n['ten_ncc']?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="number" name="id_ncc" placeholder="ID nhà cung cấp (nếu có)" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <?php endif; ?>
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Ghi chú:</label>
                        <input type="text" name="ghi_chu" placeholder="Lý do nhập, thông tin xe hàng..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>

                <h4 style="margin-bottom: 10px;">Danh sách vật tư nhập</h4>
                <table class="table-input" id="tableVatTu">
                    <thead>
                        <tr>
                            <th width="15%">Loại</th>
                            <th width="45%">Thông tin Vật tư</th>
                            <th width="15%">Số lượng</th>
                            <th width="15%">Đơn giá (VNĐ)</th>
                            <th width="10%">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyVatTu">
                        <tr>
                            <td>
                                <select name="vt_type[]" onchange="toggleVtType(this)" style="background:#eef;">
                                    <option value="old">Đã có trong kho</option>
                                    <option value="new">Tạo mới</option>
                                </select>
                            </td>
                            <td>
                                <div class="vt-old">
                                    <select name="id_vat_tu_old[]">
                                        <option value="0">-- Chọn vật tư --</option>
                                        <?php foreach($vt_list as $vt): ?>
                                            <option value="<?=$vt['id_vat_tu']?>"><?=$vt['ma_vat_tu']?> - <?=$vt['ten_vat_tu']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="vt-new" style="display:none; gap: 5px; flex-direction:column;">
                                    <input type="text" name="new_ma_vt[]" placeholder="Nhập Mã vật tư (VD: THEP-D10)">
                                    <input type="text" name="new_ten_vt[]" placeholder="Nhập Tên vật tư">
                                    <select name="new_id_dvt[]">
                                        <?php foreach($dvt_list as $d): ?>
                                            <option value="<?=$d['id_dvt']?>"><?=$d['ten_don_vi_tinh']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td><input type="number" name="so_luong[]" min="1" step="0.01" required placeholder="0"></td>
                            <td><input type="number" name="don_gia[]" min="0" placeholder="0"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)">X</button></td>
                        </tr>
                    </tbody>
                </table>
                
                <button type="button" onclick="addRow()" style="padding: 8px 15px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px; font-weight:bold;">+ Thêm dòng vật tư</button>

                <div style="text-align: right; border-top: 1px solid #ccc; padding-top: 15px;">
                    <button type="button" onclick="closeAddModal()" style="padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Hủy bỏ</button>
                    <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Xác nhận Lưu Phiếu & Nhập Kho</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('addModal').style.display = 'flex'; }
        function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }

        function toggleVtType(selectObj) {
            var tr = selectObj.closest('tr');
            var oldDiv = tr.querySelector('.vt-old');
            var newDiv = tr.querySelector('.vt-new');
            
            if(selectObj.value === 'new') {
                oldDiv.style.display = 'none';
                newDiv.style.display = 'flex';
            } else {
                oldDiv.style.display = 'block';
                newDiv.style.display = 'none';
            }
        }

        function addRow() {
            var tbody = document.getElementById('tbodyVatTu');
            var firstRow = tbody.querySelector('tr');
            var newRow = firstRow.cloneNode(true);
            
            var inputs = newRow.querySelectorAll('input[type="text"], input[type="number"]');
            inputs.forEach(function(input) { input.value = ''; });
            
            var selects = newRow.querySelectorAll('select');
            selects.forEach(function(select) { select.selectedIndex = 0; });
            
            newRow.querySelector('.vt-old').style.display = 'block';
            newRow.querySelector('.vt-new').style.display = 'none';

            tbody.appendChild(newRow);
        }

        function removeRow(btn) {
            var tbody = document.getElementById('tbodyVatTu');
            if(tbody.querySelectorAll('tr').length > 1) {
                var tr = btn.closest('tr');
                tr.remove();
            } else {
                alert("Phiếu nhập phải có ít nhất 1 dòng vật tư!");
            }
        }
    </script>
</body>
</html>
<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung'];
$bang_phieu_xuat      = 'phieu_xuat_kho';
$bang_chi_tiet_xuat   = 'chi_tiet_xuat_kho';

// --- XỬ LÝ FLASH MESSAGE ---
$flash = '';
$flash_type = 'success';
if (!empty($_SESSION['xuat_flash'])) {
    $flash = $_SESSION['xuat_flash'];
    $flash_type = isset($_SESSION['xuat_flash_type']) ? $_SESSION['xuat_flash_type'] : 'success';
    unset($_SESSION['xuat_flash'], $_SESSION['xuat_flash_type']);
}

// =================================================================================
// 1. XỬ LÝ THÊM PHIẾU XUẤT (TRỪ TỒN KHO)
// =================================================================================
if (isset($_POST['btn_them'])) {
    $so_phieu = mysqli_real_escape_string($conn, trim($_POST['so_phieu']));
    $ngay_xuat = mysqli_real_escape_string($conn, str_replace('T', ' ', $_POST['ngay_xuat']));
    $id_kho = intval($_POST['id_kho']);
    $id_du_an = intval($_POST['id_du_an']);
    $ly_do_xuat = mysqli_real_escape_string($conn, $_POST['ly_do_xuat']);

    $id_vat_tu_arr = $_POST['id_vat_tu'];
    $so_luong_arr  = $_POST['so_luong_xuat'];

    mysqli_begin_transaction($conn);
    try {
        // Kiểm tra trùng số phiếu
        $chk = mysqli_query($conn, "SELECT id_phieu_xuat FROM `$bang_phieu_xuat` WHERE so_phieu='$so_phieu'");
        if (mysqli_num_rows($chk) > 0) throw new Exception("Số phiếu '$so_phieu' đã tồn tại!");

        // Lưu phiếu chính
        $sql_ins = "INSERT INTO `$bang_phieu_xuat` (so_phieu, ngay_xuat, id_kho, id_du_an, id_nguoi_lap, ly_do_xuat) 
                    VALUES ('$so_phieu', '$ngay_xuat', '$id_kho', '$id_du_an', '$id_nguoi_dung', '$ly_do_xuat')";
        if (!mysqli_query($conn, $sql_ins)) throw new Exception("Lỗi tạo phiếu: " . mysqli_error($conn));
        $id_phieu_moi = mysqli_insert_id($conn);

        // Lưu chi tiết & Trừ tồn
        for ($i = 0; $i < count($id_vat_tu_arr); $i++) {
            $vid = intval($id_vat_tu_arr[$i]);
            $sl  = intval($so_luong_arr[$i]);
            if ($vid <= 0 || $sl <= 0) continue;

            // Kiểm tra tồn thực tế
            $res_sl = mysqli_query($conn, "SELECT so_luong_ton FROM ton_kho WHERE id_kho='$id_kho' AND id_vat_tu='$vid'");
            $row_sl = mysqli_fetch_assoc($res_sl);
            if (!$row_sl || $row_sl['so_luong_ton'] < $sl) throw new Exception("Vật tư ID $vid không đủ tồn kho!");

            mysqli_query($conn, "INSERT INTO `$bang_chi_tiet_xuat` (id_phieu_xuat, id_vat_tu, so_luong) VALUES ('$id_phieu_moi', '$vid', '$sl')");
            mysqli_query($conn, "UPDATE ton_kho SET so_luong_ton = so_luong_ton - $sl WHERE id_kho='$id_kho' AND id_vat_tu='$vid'");
        }

        mysqli_commit($conn);
        $_SESSION['xuat_flash'] = "Thêm phiếu và trừ tồn thành công!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['xuat_flash'] = $e->getMessage();
        $_SESSION['xuat_flash_type'] = 'error';
    }
    header("Location: quanlyphieuxuatkho.php"); exit();
}

// =================================================================================
// 2. XỬ LÝ XÓA PHIẾU (HOÀN TỒN KHO)
// =================================================================================
if (isset($_GET['xoa'])) {
    $id_xoa = intval($_GET['xoa']);
    mysqli_begin_transaction($conn);
    try {
        $p_res = mysqli_query($conn, "SELECT id_kho FROM `$bang_phieu_xuat` WHERE id_phieu_xuat='$id_xoa'");
        $id_kho_xoa = mysqli_fetch_assoc($p_res)['id_kho'];

        $ct_res = mysqli_query($conn, "SELECT id_vat_tu, so_luong FROM `$bang_chi_tiet_xuat` WHERE id_phieu_xuat='$id_xoa'");
        while ($ct = mysqli_fetch_assoc($ct_res)) {
            $vid = $ct['id_vat_tu']; $sl = $ct['so_luong'];
            mysqli_query($conn, "UPDATE ton_kho SET so_luong_ton = so_luong_ton + $sl WHERE id_kho='$id_kho_xoa' AND id_vat_tu='$vid'");
        }
        mysqli_query($conn, "DELETE FROM `$bang_chi_tiet_xuat` WHERE id_phieu_xuat='$id_xoa'");
        mysqli_query($conn, "DELETE FROM `$bang_phieu_xuat` WHERE id_phieu_xuat='$id_xoa'");
        
        mysqli_commit($conn);
        $_SESSION['xuat_flash'] = "Đã xóa phiếu và hoàn tồn!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['xuat_flash'] = "Lỗi xóa: " . $e->getMessage();
        $_SESSION['xuat_flash_type'] = 'error';
    }
    header("Location: quanlyphieuxuatkho.php"); exit();
}

// --- DỮ LIỆU BỔ TRỢ ---
$auto_so_phieu = 'PXK-' . date('Ymd') . '-' . time();
$kho_list = mysqli_query($conn, "SELECT * FROM kho");
$da_list = mysqli_query($conn, "SELECT * FROM du_an");
$vt_list = mysqli_query($conn, "SELECT * FROM vat_tu");
$vt_options = "";
while($v = mysqli_fetch_assoc($vt_list)) {
    $vt_options .= "<option value='{$v['id_vat_tu']}'>{$v['ma_vat_tu']} - {$v['ten_vat_tu']}</option>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Phiếu xuất kho - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css?v=<?=time()?>">
    <style>
        .no-print { margin-bottom: 20px; }
        .data-table th { background: #343a40; color: white; text-align: center; }
        @media print {
            body * { visibility: hidden; }
            #khuVucInPhieu, #khuVucInPhieu * { visibility: visible; }
            #khuVucInPhieu { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; }
            .no-print { display: none !important; }
            .print-table { border: 1px solid #000 !important; width: 100%; border-collapse: collapse; }
            .print-table th, .print-table td { border: 1px solid #000 !important; padding: 8px; color: #000; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <main class="container-fluid py-3">

            <?php if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])): 
                $id_view = intval($_GET['id']);
                $sql = "SELECT px.*, k.ten_kho, da.ten_du_an, nd.ho_ten FROM `$bang_phieu_xuat` px 
                        LEFT JOIN kho k ON px.id_kho = k.id_kho 
                        LEFT JOIN du_an da ON px.id_du_an = da.id_du_an
                        LEFT JOIN nguoi_dung nd ON px.id_nguoi_lap = nd.id_nguoi_dung WHERE px.id_phieu_xuat = '$id_view'";
                $phieu = mysqli_fetch_assoc(mysqli_query($conn, $sql));
            ?>
                <div class="no-print">
                    <a href="quanlyphieuxuatkho.php" class="btn btn-secondary">&laquo; Quay lại</a>
                    <button onclick="window.print()" class="btn btn-primary">🖨 In Phiếu Xuất</button>
                </div>

                <div id="khuVucInPhieu" class="bg-white p-5 border shadow-sm">
                    <div class="d-flex justify-content-between border-bottom pb-3 mb-4">
                        <div><h3>CÔNG TY CP THỊNH TIẾN</h3><p><i>Giải pháp Vật tư hàng đầu</i></p></div>
                        <div class="text-right"><p><b>Mẫu số: 02-VT</b></p><p>Thông tư 200/2014/TT-BTC</p></div>
                    </div>
                    <div class="text-center mb-4">
                        <h2 class="font-weight-bold">PHIẾU XUẤT KHO</h2>
                        <p>Ngày: <?=date('d/m/Y', strtotime($phieu['ngay_xuat']))?> | Số: <b class="text-danger"><?=$phieu['so_phieu']?></b></p>
                    </div>
                    <div class="mb-4">
                        <p>- Xuất cho dự án: <b><?=$phieu['ten_du_an']?></b></p>
                        <p>- Kho xuất: <b><?=$phieu['ten_kho']?></b></p>
                        <p>- Lý do: <?=$phieu['ly_do_xuat']?></p>
                    </div>
                    <table class="print-table mb-5">
                        <thead><tr><th>STT</th><th>Mã vật tư</th><th>Tên vật tư</th><th>ĐVT</th><th>Số lượng</th></tr></thead>
                        <tbody>
                            <?php 
                            $stt=1; 
                            $res_ct = mysqli_query($conn, "SELECT ct.*, vt.ma_vat_tu, vt.ten_vat_tu, d.ten_don_vi_tinh FROM `$bang_chi_tiet_xuat` ct 
                                                           JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu 
                                                           LEFT JOIN don_vi_tinh d ON vt.id_dvt = d.id_dvt WHERE ct.id_phieu_xuat='$id_view'");
                            while($i = mysqli_fetch_assoc($res_ct)): ?>
                            <tr><td class="text-center"><?=$stt++?></td><td class="text-center"><?=$i['ma_vat_tu']?></td><td><?=$i['ten_vat_tu']?></td><td class="text-center"><?=$i['ten_don_vi_tinh']?></td><td class="text-center font-weight-bold"><?=$i['so_luong']?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-around text-center mt-5">
                        <div><p><b>Người lập</b></p><br><br><b><?=$phieu['ho_ten']?></b></div>
                        <div><p><b>Người nhận</b></p></div>
                        <div><p><b>Thủ kho</b></p></div>
                    </div>
                </div>

            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">Quản lý Phiếu Xuất Kho</h2>
                    <button class="btn btn-success font-weight-bold" data-toggle="modal" data-target="#modalThem">+ Thêm Phiếu Mới</button>
                </div>

                <?php if($flash) echo "<div class='alert alert-$flash_type'>$flash</div>"; ?>

                <table class="table table-hover bg-white border">
                    <thead><tr><th>STT</th><th>Số phiếu</th><th>Ngày xuất</th><th>Kho</th><th>Dự án</th><th>Thao tác</th></tr></thead>
                    <tbody>
                        <?php 
                        $res = mysqli_query($conn, "SELECT px.*, k.ten_kho, da.ten_du_an FROM `$bang_phieu_xuat` px 
                                                    LEFT JOIN kho k ON px.id_kho = k.id_kho 
                                                    LEFT JOIN du_an da ON px.id_du_an = da.id_du_an ORDER BY px.id_phieu_xuat DESC");
                        $s=1; while($r = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td class="text-center"><?=$s++?></td>
                            <td class="font-weight-bold text-primary"><?=$r['so_phieu']?></td>
                            <td><?=date('d/m/Y H:i', strtotime($r['ngay_xuat']))?></td>
                            <td><?=$r['ten_kho']?></td>
                            <td><?=$r['ten_du_an']?></td>
                            <td class="text-center">
                                <a href="?action=view&id=<?=$r['id_phieu_xuat']?>" class="btn btn-info btn-sm">👁 In</a>
                                <a href="?xoa=<?=$r['id_phieu_xuat']?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa phiếu sẽ hoàn lại tồn kho. Chắc chắn?')">🗑 Xóa</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            </main>
        </div>
    </div>

    <div class="modal fade" id="modalThem" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-success text-white"><h5 class="modal-title">Lập Phiếu Xuất Kho</h5><button class="close text-white" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-3"><label>Số phiếu</label><input type="text" name="so_phieu" class="form-control" value="<?=$auto_so_phieu?>" readonly></div>
                        <div class="form-group col-md-3"><label>Ngày xuất</label><input type="datetime-local" name="ngay_xuat" class="form-control" value="<?=date('Y-m-d\TH:i')?>"></div>
                        <div class="form-group col-md-3"><label>Kho xuất</label><select name="id_kho" class="form-control"><?php mysqli_data_seek($kho_list, 0); while($k = mysqli_fetch_assoc($kho_list)) echo "<option value='{$k['id_kho']}'>{$k['ten_kho']}</option>"; ?></select></div>
                        <div class="form-group col-md-3"><label>Dự án nhận</label><select name="id_du_an" class="form-control"><?php mysqli_data_seek($da_list, 0); while($da = mysqli_fetch_assoc($da_list)) echo "<option value='{$da['id_du_an']}'>{$da['ten_du_an']}</option>"; ?></select></div>
                        <div class="form-group col-md-12"><label>Lý do xuất</label><input type="text" name="ly_do_xuat" class="form-control"></div>
                    </div>
                    <table class="table table-bordered">
                        <thead class="bg-light"><tr><th>Vật tư</th><th width="20%">Số lượng</th><th width="10%">Xóa</th></tr></thead>
                        <tbody id="tbodyVatTu"><tr><td><select name="id_vat_tu[]" class="form-control"><?=$vt_options?></select></td><td><input type="number" name="so_luong_xuat[]" class="form-control" min="1" required></td><td><button type="button" class="btn btn-danger btn-block" onclick="this.closest('tr').remove()">X</button></td></tr></tbody>
                    </table>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="addRow()">+ Thêm vật tư</button>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="submit" name="btn_them" class="btn btn-success">Lưu & Xuất Kho</button></div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addRow() {
            var row = `<tr><td><select name="id_vat_tu[]" class="form-control"><?=$vt_options?></select></td><td><input type="number" name="so_luong_xuat[]" class="form-control" min="1" required></td><td><button type="button" class="btn btn-danger btn-block" onclick="this.closest('tr').remove()">X</button></td></tr>`;
            document.getElementById('tbodyVatTu').insertAdjacentHTML('beforeend', row);
        }
    </script>
</body>
</html>
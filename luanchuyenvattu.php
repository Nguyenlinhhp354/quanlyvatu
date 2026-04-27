<?php
session_start();
include 'htdocs/db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung'];
$msg = "";

// Xử lý tạo phiếu luân chuyển
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_transfer') {
    $so_phieu = $conn->real_escape_string($_POST['so_phieu']);
    $ngay_lap = $_POST['ngay_lap'];
    $id_kho_from = intval($_POST['id_kho_from']);
    $id_kho_to = intval($_POST['id_kho_to']);

    $ids = $_POST['id_vat_tu'] ?? [];
    $sls = $_POST['so_luong'] ?? [];

    if ($id_kho_from == $id_kho_to) {
        $msg = 'Kho xuất và kho nhận không được giống nhau.';
    } else if (count($ids) == 0) {
        $msg = 'Vui lòng chọn ít nhất một vật tư để luân chuyển.';
    } else {
        $conn->begin_transaction();
        try {
            // kiểm tra số phiếu chưa tồn tại
            $chk = $conn->query("SELECT * FROM phieu_xuat_kho WHERE so_phieu='".$so_phieu."'");
            if ($chk->num_rows > 0) throw new Exception('Số phiếu đã tồn tại, hãy tải lại trang.');

            // tạo phiếu xuất (kho nguồn)
            $ly_do = 'Luân chuyển sang kho ID: '.$id_kho_to;
            $nguoi_nhan = 'Luân chuyển tới kho '.$id_kho_to;
            $conn->query("INSERT INTO phieu_xuat_kho (so_phieu, ngay_xuat, id_kho, id_du_an, id_nguoi_lap, nguoi_nhan, ly_do_xuat) VALUES ('".$so_phieu."', '".$ngay_lap."', '$id_kho_from', NULL, '$id_nguoi_dung', '".$nguoi_nhan."', '".$ly_do."')");
            $id_phieu_xuat = $conn->insert_id;

            // tạo phiếu nhập (kho đích)
            $so_phieu_nhap = 'LCN-'.date('YmdHis');
            $conn->query("INSERT INTO phieu_nhap_kho (so_phieu, ngay_nhap, id_kho, id_ncc, id_nguoi_lap, ghi_chu) VALUES ('".$so_phieu_nhap."', '".$ngay_lap."', '$id_kho_to', NULL, '$id_nguoi_dung', 'Luân chuyển từ kho $id_kho_from')");
            $id_phieu_nhap = $conn->insert_id;

            for ($i = 0; $i < count($ids); $i++) {
                $vt = intval($ids[$i]);
                $sl = floatval($sls[$i]);
                if ($sl <= 0) throw new Exception('Số lượng phải lớn hơn 0');

                // kiểm tồn kho nguồn
                $res = $conn->query("SELECT so_luong_ton FROM ton_kho WHERE id_kho='$id_kho_from' AND id_vat_tu='$vt'");
                $row = $res->fetch_assoc();
                if (!$row) throw new Exception('Vật tư ID '.$vt.' không tồn tại trong kho nguồn');
                if ($row['so_luong_ton'] < $sl) throw new Exception('Không đủ tồn kho cho vật tư ID '.$vt);

                // lưu chi tiết xuất
                $conn->query("INSERT INTO chi_tiet_xuat_kho (id_phieu_xuat, id_vat_tu, so_luong) VALUES ('$id_phieu_xuat', '$vt', '$sl')");
                // trừ tồn kho nguồn
                $conn->query("UPDATE ton_kho SET so_luong_ton = so_luong_ton - $sl WHERE id_kho='$id_kho_from' AND id_vat_tu='$vt'");

                // lưu chi tiết nhập
                $conn->query("INSERT INTO chi_tiet_nhap_kho (id_phieu_nhap, id_vat_tu, so_luong, don_gia) VALUES ('$id_phieu_nhap', '$vt', '$sl', 0)");

                // cộng tồn kho đích (nếu chưa có thì tạo)
                $res2 = $conn->query("SELECT so_luong_ton FROM ton_kho WHERE id_kho='$id_kho_to' AND id_vat_tu='$vt'");
                if ($res2->num_rows > 0) {
                    $conn->query("UPDATE ton_kho SET so_luong_ton = so_luong_ton + $sl WHERE id_kho='$id_kho_to' AND id_vat_tu='$vt'");
                } else {
                    $conn->query("INSERT INTO ton_kho (id_kho, id_vat_tu, so_luong_ton) VALUES ('$id_kho_to', '$vt', '$sl')");
                }
            }

            $conn->commit();
            header('Location: luanchuyenvattu.php?success=1');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = $e->getMessage();
        }
    }
}

$kho_rs = $conn->query("SELECT * FROM kho");
$vattu_rs = $conn->query("SELECT * FROM vat_tu");

// Tạo số phiếu tự động
$today = date('Y-m-d');
$prefix = 'LC-' . $today . '-';
$res_last = $conn->query("SELECT so_phieu FROM phieu_xuat_kho WHERE so_phieu LIKE '$prefix%' ORDER BY id_phieu_xuat DESC LIMIT 1");
$next = 1;
if ($res_last && $res_last->num_rows > 0) {
    $row = $res_last->fetch_assoc();
    $parts = explode('-', $row['so_phieu']);
    $lastnum = intval(end($parts));
    $next = $lastnum + 1;
}
$auto_so = $prefix . $next;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Luân chuyển vật tư</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="wrapper d-flex">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content flex-grow-1 p-4 bg-light">
        <?php if($msg != ''){ echo "<div class='alert alert-danger'>$msg</div>"; } ?>

        <?php if(isset($_GET['success'])){ echo "<div class='alert alert-success'>Luân chuyển thành công.</div>"; } ?>

        <div class="bg-white p-3 border rounded">
            <h4>Phiếu luân chuyển vật tư</h4>
            <form method="post">
                <input type="hidden" name="action" value="add_transfer">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>Số phiếu</label>
                        <input type="text" name="so_phieu" class="form-control" value="<?php echo $auto_so;?>" required>
                    </div>
                    <div class="col-md-4">
                        <label>Ngày lập</label>
                        <input type="datetime-local" name="ngay_lap" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label>Người lập</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['ho_ten'] ?? ''); ?>" disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Kho xuất</label>
                        <select name="id_kho_from" class="form-control">
                            <?php foreach($kho_rs as $k){ echo "<option value='".$k['id_kho']."'>".$k['ten_kho']."</option>"; } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Kho nhận</label>
                        <select name="id_kho_to" class="form-control">
                            <?php mysqli_data_seek($kho_rs, 0); foreach($kho_rs as $k){ echo "<option value='".$k['id_kho']."'>".$k['ten_kho']."</option>"; } ?>
                        </select>
                    </div>
                </div>

                <h5>Danh sách vật tư</h5>
                <table class="table table-bordered" id="tblVT">
                    <thead>
                        <tr><th>Vật tư</th><th>Số lượng</th><th>Hành động</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="id_vat_tu[]" class="form-control">
                                    <?php foreach($vattu_rs as $v){ echo "<option value='".$v['id_vat_tu']."'>".$v['ten_vat_tu']."</option>"; } ?>
                                </select>
                            </td>
                            <td><input type="number" name="so_luong[]" class="form-control" value="1" min="0"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Xóa</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary mb-3" onclick="addRow()">+ Thêm vật tư</button>

                <div>
                    <button class="btn btn-success">Lưu luân chuyển</button>
                    <a href="QLkho.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addRow(){
    const tbl = document.getElementById('tblVT').getElementsByTagName('tbody')[0];
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><?php $tmp=''; foreach($vattu_rs as $v){ $tmp .= "<option value=\\'".$v['id_vat_tu']."\\'>".$v['ten_vat_tu']."</option>"; } echo "<select name=\\\"id_vat_tu[]\\\" class=\\\"form-control\\\">$tmp</select>"; ?></td><td><input type="number" name="so_luong[]" class="form-control" value="1" min="0"></td><td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Xóa</button></td>`;
    tbl.appendChild(tr);
}
function removeRow(btn){
    const tr = btn.closest('tr');
    tr.parentNode.removeChild(tr);
}
</script>
</body>
</html>

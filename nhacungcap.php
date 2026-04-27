<?php
include_once('db_connect.php');
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location:login.php');
    exit();
}

$msg = "";

// Xử lý Sửa
if (isset($_POST['hanh_dong']) && $_POST['hanh_dong'] == 'sua') {
    $id = $_POST['id_ncc'];
    $ten = $_POST['ten_ncc'];
    $dia_chi = $_POST['dia_chi'];
    $dien_thoai = $_POST['dien_thoai'];
    $sqlupdate = "UPDATE nha_cung_cap SET ten_ncc = '$ten', dia_chi = '$dia_chi', dien_thoai = '$dien_thoai' WHERE id_ncc = '$id'";
    if ($conn->query($sqlupdate)) {
        header('location:nhacungcap.php');
        exit();
    } else {
        $msg = "Lỗi: " . $conn->error;
    }
}

// Xử lý Xóa
else if (isset($_POST['hanh_dong']) && $_POST['hanh_dong'] == 'xoa') {
    $id = $_POST['id_ncc'];
    $sqlcheck = $conn->query("SELECT id_phieu_nhap FROM phieu_nhap_kho WHERE id_ncc = '$id'");
    if ($sqlcheck->num_rows > 0) {
        $msg = "Lỗi: Không cho phép xóa vì có liên quan tới dữ liệu bảng phiếu nhập kho";
    } else {
        $sqldelete = "DELETE FROM nha_cung_cap WHERE id_ncc = '$id'";
        if ($conn->query($sqldelete)) {
            header('location:nhacungcap.php');
            exit();
        } else {
            $msg = "Lỗi hệ thống: " . $conn->error;
        }
    }
}

// Xử lý Thêm mới
if (isset($_POST['submit'])) {
    $id = $_POST['id_ncc'];
    $ten = $_POST['ten_ncc'];
    $dia_chi = $_POST['dia_chi'];
    $dien_thoai = $_POST['dien_thoai'];
    
    $checkthem = $conn->query("SELECT * FROM nha_cung_cap WHERE id_ncc = '$id'");
    if ($checkthem->num_rows > 0) {
        $msg = "Lỗi: Mã nhà cung cấp này đã tồn tại!";
    } else {
        $insert = "INSERT INTO nha_cung_cap(id_ncc, ten_ncc, dia_chi, dien_thoai) VALUES('$id', '$ten', '$dia_chi', '$dien_thoai')";
        if ($conn->query($insert)) {
            header('location:nhacungcap.php');
            exit();
        } else {
            $msg = "Lỗi hệ thống: " . $conn->error;
        }
    }
}

// Xử lý Tìm kiếm
$timkiem = '';
if (isset($_POST['btn_timkiem'])) {
    $timkiem = $_POST['timkiem'];
    $search = "SELECT * FROM nha_cung_cap WHERE ten_ncc LIKE '%$timkiem%' OR id_ncc = '$timkiem'";
} else {
    $search = "SELECT * FROM nha_cung_cap";
}
$result = $conn->query($search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhà cung cấp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="wrapper d-flex">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content flex-grow-1 p-4 bg-light">
            <?php if ($msg !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Thông báo:</strong> <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <form method="post" class="d-flex w-75 gap-2">
                    <input type="text" name="timkiem" class="form-control" placeholder="Nhập tên hoặc id để tìm kiếm" value="<?php echo htmlspecialchars($timkiem); ?>">
                    <button name="btn_timkiem" class="btn btn-outline-dark text-nowrap">Tìm kiếm</button>
                </form>
                <button class="btn btn-success text-nowrap" onclick="moboxthem()">+ Thêm nhà cung cấp mới</button>
            </div>

            <div class="bg-white p-3 border rounded">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã NCC</th>
                            <th>Tên nhà cung cấp</th>
                            <th>Địa chỉ</th>
                            <th>Số điện thoại</th>
                            <th style="width: 150px; text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="align-middle"><?php echo $item['id_ncc'] ?></td>
                            <td class="align-middle"><?php echo $item['ten_ncc'] ?></td>
                            <td class="align-middle"><?php echo $item['dia_chi'] ?></td>
                            <td class="align-middle"><?php echo $item['dien_thoai'] ?></td>
                            <td class="text-center align-middle">
                                <button class="btn btn-outline-primary btn-sm me-1" onclick="moboxsua('<?php echo $item['id_ncc']; ?>', '<?php echo addslashes($item['ten_ncc']); ?>', '<?php echo addslashes($item['dia_chi']); ?>', '<?php echo $item['dien_thoai']; ?>')">Sửa</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="xoa('<?php echo $item['id_ncc']; ?>')">Xóa</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalbox" style="display:none; position: fixed; left: 0; right:0; top: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1050; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 450px; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <h4 id="modal_tieude" class="mb-4">Tiêu đề</h4>
            
            <form method="post" id="form_sua_xoa">
                <input type="hidden" name="hanh_dong" id="hanh_dong">
                <div class="mb-3">
                    <label class="form-label fw-bold">Mã nhà cung cấp</label>
                    <input type="text" name="id_ncc" id="id_ncc" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên nhà cung cấp</label>
                    <input type="text" name="ten_ncc" id="ten_ncc" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Địa chỉ</label>
                    <input type="text" name="dia_chi" id="dia_chi" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Điện thoại</label>
                    <input type="text" name="dien_thoai" id="dien_thoai" class="form-control" required>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary w-50">LƯU</button>
                    <button type="button" class="btn btn-secondary w-50" onclick="dongbox()">HỦY</button>
                </div>
            </form>

            <form method="post" id="form_them" style="display:none">
                <div class="mb-3">
                    <label class="form-label fw-bold">Mã nhà cung cấp</label>
                    <input type="text" name="id_ncc" id="id_ncc_them" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên nhà cung cấp</label>
                    <input type="text" name="ten_ncc" id="ten_ncc_them" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Địa chỉ</label>
                    <input type="text" name="dia_chi" id="dia_chi_them" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Điện thoại</label>
                    <input type="text" name="dien_thoai" id="dien_thoai_them" class="form-control" required>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <input type="submit" name="submit" value="Thêm mới" class="btn btn-success w-50">
                    <button type="button" class="btn btn-secondary w-50" onclick="dongbox()">HỦY</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function moboxsua(id, ten, diachi, dienthoai) {
            document.getElementById('modal_tieude').innerText = 'Sửa nhà cung cấp';
            document.getElementById('form_sua_xoa').style.display = 'block';
            document.getElementById('form_them').style.display = 'none';
            
            document.getElementById('hanh_dong').value = 'sua';
            document.getElementById('id_ncc').value = id;
            document.getElementById('ten_ncc').value = ten;
            document.getElementById('dia_chi').value = diachi;
            document.getElementById('dien_thoai').value = dienthoai;
            
            document.getElementById('modalbox').style.display = 'flex';
        }

        function moboxthem() {
            document.getElementById('modal_tieude').innerText = 'Thêm nhà cung cấp mới';
            document.getElementById('form_sua_xoa').style.display = 'none';
            document.getElementById('form_them').style.display = 'block';
            
            // Reset fields
            document.getElementById('id_ncc_them').value = '';
            document.getElementById('ten_ncc_them').value = '';
            document.getElementById('dia_chi_them').value = '';
            document.getElementById('dien_thoai_them').value = '';
            
            document.getElementById('modalbox').style.display = 'flex';
        }

        function dongbox() {
            document.getElementById('modalbox').style.display = 'none';
        }

        function xoa(id) {
            if (confirm("Bạn có chắc chắn muốn xóa nhà cung cấp này không?")) {
                document.getElementById('form_sua_xoa').style.display = 'block';
                document.getElementById('form_them').style.display = 'none';
                document.getElementById('hanh_dong').value = 'xoa';
                document.getElementById('id_ncc').value = id;
                document.getElementById('form_sua_xoa').submit();
            }
        }
    </script>
</body>
</html>
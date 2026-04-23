<?php
session_start();
include 'db_connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = isset($_SESSION['id_nguoi_dung']) ? $_SESSION['id_nguoi_dung'] : 0;
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result_user = mysqli_query($conn, $sql_user);
$ho_ten = 'Admin';
if ($result_user && $row_user = mysqli_fetch_assoc($result_user)) {
    $ho_ten = $row_user['ho_ten'];
}

// Xử lý thêm / sửa / xóa
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanh_dong = isset($_POST['hanh_dong']) ? $_POST['hanh_dong'] : '';
    $ma_ncc = isset($_POST['ma_ncc']) ? trim($_POST['ma_ncc']) : '';
    $ten_ncc = isset($_POST['ten_ncc']) ? trim($_POST['ten_ncc']) : '';
    $dia_chi = isset($_POST['dia_chi']) ? trim($_POST['dia_chi']) : '';
    $so_dien_thoai = isset($_POST['so_dien_thoai']) ? trim($_POST['so_dien_thoai']) : '';

    if ($hanh_dong === 'them') {
        if ($ma_ncc !== '' && $ten_ncc !== '') {
            $ma_ncc_esc = mysqli_real_escape_string($conn, $ma_ncc);
            $ten_ncc_esc = mysqli_real_escape_string($conn, $ten_ncc);
            $dia_chi_esc = mysqli_real_escape_string($conn, $dia_chi);
            $so_dien_thoai_esc = mysqli_real_escape_string($conn, $so_dien_thoai);
            $sql_insert = "INSERT INTO nha_cung_cap (id_ncc, ten_ncc, dia_chi, dien_thoai) VALUES ('$ma_ncc_esc', '$ten_ncc_esc', '$dia_chi_esc', '$so_dien_thoai_esc')";
            if (mysqli_query($conn, $sql_insert)) {
                $msg = 'Đã thêm nhà cung cấp mới.';
            } else {
                $msg = 'Lỗi khi thêm nhà cung cấp: ' . mysqli_error($conn);
            }
        } else {
            $msg = 'Mã và tên nhà cung cấp không được để trống.';
        }
    } elseif ($hanh_dong === 'sua') {
        if ($ma_ncc !== '' && $ten_ncc !== '') {
            $ma_ncc_esc = mysqli_real_escape_string($conn, $ma_ncc);
            $ten_ncc_esc = mysqli_real_escape_string($conn, $ten_ncc);
            $dia_chi_esc = mysqli_real_escape_string($conn, $dia_chi);
            $so_dien_thoai_esc = mysqli_real_escape_string($conn, $so_dien_thoai);
            $sql_update = "UPDATE nha_cung_cap SET ten_ncc='$ten_ncc_esc', dia_chi='$dia_chi_esc', dien_thoai='$so_dien_thoai_esc' WHERE id_ncc='$ma_ncc_esc'";
            if (mysqli_query($conn, $sql_update)) {
                $msg = 'Đã cập nhật thông tin nhà cung cấp.';
            } else {
                $msg = 'Lỗi khi cập nhật nhà cung cấp: ' . mysqli_error($conn);
            }
        } else {
            $msg = 'Mã và tên nhà cung cấp không được để trống.';
        }
    }
}

if (isset($_GET['xoa']) && $_GET['xoa'] !== '') {
    $xoa_id = mysqli_real_escape_string($conn, trim($_GET['xoa']));
    $sql_delete = "DELETE FROM nha_cung_cap WHERE id_ncc='$xoa_id'";
    if (mysqli_query($conn, $sql_delete)) {
        $msg = 'Đã xóa nhà cung cấp.';
    } else {
        $msg = 'Lỗi khi xóa nhà cung cấp: ' . mysqli_error($conn);
    }
}

$tukhoa = isset($_GET['tukhoa']) ? trim($_GET['tukhoa']) : '';
$where = '';
if ($tukhoa !== '') {
    $tukhoa_esc = mysqli_real_escape_string($conn, $tukhoa);
    $where = "WHERE id_ncc LIKE '%$tukhoa_esc%' OR ten_ncc LIKE '%$tukhoa_esc%' OR dia_chi LIKE '%$tukhoa_esc%'";
}

$sql_ncc = "SELECT * FROM nha_cung_cap $where ORDER BY ten_ncc";
$result_ncc = @mysqli_query($conn, $sql_ncc);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhà cung cấp - Quản lý danh mục</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .phan-trang { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .khung-trang { background: #f7f7f7; padding: 20px; min-height: 80vh; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
        th { background: #eee; }
        .nut { padding: 8px 14px; border: 1px solid #333; background: #fff; cursor: pointer; font-weight: bold; }
        .nut-them { background: #28a745; color: #fff; border: none; }
        .nut-sua { color: #0056b3; border-color: #0056b3; background: #fff; }
        .nut-xoa { color: #c82333; border-color: #c82333; background: #fff; }
        .lop-phu { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1000; padding: 20px; }
        .hop-thoai { background: #fff; max-width: 520px; margin: 40px auto; padding: 20px; border-radius: 6px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); }
        .hop-thoai label { display: block; font-weight: bold; margin-top: 14px; }
        .hop-thoai input, .hop-thoai textarea { width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .hop-thoai textarea { resize: vertical; min-height: 80px; }
        .hop-thoai .hanh-dong { display: flex; gap: 10px; margin-top: 20px; }
        .thong-bao { margin-bottom: 16px; padding: 12px; border-radius: 4px; background: #e8f5e9; color: #256029; border: 1px solid #c8e6c9; }
        .khung-timkiem { display: flex; gap: 10px; margin-bottom: 20px; }
        .khung-timkiem input { flex: 1; padding: 10px; border: 1px solid #333; border-radius: 4px; }
        .chuc-nang-loc { margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div style="padding: 12px 20px; color: #555;">Quản lý danh mục > Nhà cung cấp</div>

            <div class="khung-trang">
                <?php if ($msg !== ''): ?>
                    <div class="thong-bao"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>

                <div class="chuc-nang-loc">
                    <form method="GET" class="khung-timkiem">
                        <input type="text" name="tukhoa" placeholder="Tìm theo mã, tên hoặc địa chỉ..." value="<?php echo htmlspecialchars($tukhoa); ?>">
                        <button type="submit" class="nut">Tìm kiếm</button>
                        <button type="button" class="nut nut-them" onclick="moBoxThem()">+ Thêm nhà cung cấp</button>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width:100px;">Mã NCC</th>
                            <th>Tên nhà cung cấp</th>
                            <th>Địa chỉ</th>
                            <th>Số điện thoại</th>
                            <th style="width:160px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_ncc && mysqli_num_rows($result_ncc) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_ncc)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id_ncc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ten_ncc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dia_chi']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dien_thoai']); ?></td>
                                    <td>
                                        <button class="nut nut-sua" onclick="moBoxSua('<?php echo htmlspecialchars($row['id_ncc']); ?>', '<?php echo htmlspecialchars(addslashes($row['ten_ncc'])); ?>', '<?php echo htmlspecialchars(addslashes($row['dia_chi'])); ?>', '<?php echo htmlspecialchars(addslashes($row['dien_thoai'])); ?>')">Sửa</button>
                                        <button class="nut nut-xoa" onclick="xacNhanXoa('<?php echo htmlspecialchars($row['id_ncc']); ?>')">Xóa</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 18px;">Chưa có nhà cung cấp nào hoặc bảng chưa được tạo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalBox" class="lop-phu">
        <div class="hop-thoai">
            <h2 id="modalTitle" style="margin:0 0 16px;">Thêm nhà cung cấp</h2>
            <form method="POST" action="nhacungcap.php">
                <input type="hidden" name="hanh_dong" id="hanh_dong" value="them">
                <label>ID nhà cung cấp</label>
                <input type="number" min="1" name="ma_ncc" id="ma_ncc" placeholder="1" required>
                <label>Tên nhà cung cấp</label>
                <input type="text" name="ten_ncc" id="ten_ncc" placeholder="Tên nhà cung cấp" required>
                <label>Địa chỉ</label>
                <textarea name="dia_chi" id="dia_chi" placeholder="Địa chỉ liên hệ"></textarea>
                <label>Số điện thoại</label>
                <input type="text" name="so_dien_thoai" id="so_dien_thoai" placeholder="0912xxxxxx">

                <div class="hanh-dong">
                    <button type="submit" class="nut nut-them" style="flex:1;">Lưu lại</button>
                    <button type="button" class="nut" style="flex:1;" onclick="dongBox()">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function moBoxThem() {
            document.getElementById('modalTitle').innerText = 'Thêm nhà cung cấp';
            document.getElementById('hanh_dong').value = 'them';
            document.getElementById('ma_ncc').readOnly = false;
            document.getElementById('ma_ncc').value = '';
            document.getElementById('ten_ncc').value = '';
            document.getElementById('dia_chi').value = '';
            document.getElementById('so_dien_thoai').value = '';
            document.getElementById('modalBox').style.display = 'block';
        }

        function moBoxSua(ma, ten, diachi, sodt) {
            document.getElementById('modalTitle').innerText = 'Sửa thông tin nhà cung cấp';
            document.getElementById('hanh_dong').value = 'sua';
            document.getElementById('ma_ncc').value = ma;
            document.getElementById('ma_ncc').readOnly = true;
            document.getElementById('ten_ncc').value = ten;
            document.getElementById('dia_chi').value = diachi;
            document.getElementById('so_dien_thoai').value = sodt;
            document.getElementById('modalBox').style.display = 'block';
        }

        function dongBox() {
            document.getElementById('modalBox').style.display = 'none';
        }

        function xacNhanXoa(ma) {
            if (confirm('Bạn có chắc chắn muốn xóa nhà cung cấp ' + ma + ' không?')) {
                window.location.href = 'nhacungcap.php?xoa=' + encodeURIComponent(ma);
            }
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

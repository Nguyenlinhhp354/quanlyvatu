<?php
session_start();
include 'db_connect.php'; 

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$ho_ten = isset($_SESSION['ho_ten']) ? $_SESSION['ho_ten'] : "Admin";

// 2. TRUY VẤN DANH MỤC (Dùng @ để tránh lỗi 500 nếu bảng chưa tồn tại)
$truyvan_loai = @mysqli_query($conn, "SELECT * FROM chungloaivattu");
$truyvan_hang = @mysqli_query($conn, "SELECT * FROM hangsanxuat");
$truyvan_thongso = @mysqli_query($conn, "SELECT * FROM thongsokythuat");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Chi Tiết Vật Tư</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Bố cục chính chia 2 cột */
        .khung-trang { display: flex; gap: 20px; padding: 20px; background-color: #f4f4f4; min-height: 80vh; }
        .cot-trai { flex: 3; background: white; padding: 20px; border: 1px solid #333; }
        .cot-phai { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .khung-nho { background: white; padding: 15px; border: 1px solid #333; }
        
        /* Bảng dữ liệu */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        table, th, td { border: 1px solid #333; padding: 10px; text-align: center; }
        th { background: #eee; }

        /* Nút bấm */
        .nut { padding: 8px 15px; cursor: pointer; border: 1px solid #333; background: #fff; font-weight: bold; }
        .nut-them { background: #28a745; color: white; border: none; padding: 12px; margin-top: 10px; }
        .nut-sua { color: blue; border-color: blue; padding: 5px 10px; font-size: 12px; }
        .nut-xoa { color: red; border-color: red; padding: 5px 10px; font-size: 12px; }

        /* Lớp phủ Modal (Box thông tin) */
        .lop-phu { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .hop-thoai { background: white; width: 450px; margin: 50px auto; padding: 20px; border: 2px solid #333; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .hop-thoai label { font-weight: bold; display: block; margin-top: 10px; }
        .hop-thoai input, .hop-thoai select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; box-sizing: border-box; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div style="padding: 10px; color: #666;">Quản lý danh mục > Chi tiết vật tư</div>

            <div class="khung-trang">
                <div class="cot-trai">
                    <form action="" method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="tukhoa" placeholder="Tìm theo Mã hoặc Tên vật tư..." style="flex:1; padding: 8px; border: 1px solid #333;">
                        <button type="submit" class="nut">Tìm kiếm</button>
                    </form>

                    <table>
                        <thead>
                            <tr>
                                <th>Mã VT</th>
                                <th>Tên vật tư</th>
                                <th>Chủng loại</th>
                                <th>Hãng SX</th>
                                <th>Thông số (ĐVT)</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>VT001</td>
                                <td>Aptomat MCCB 3 pha</td>
                                <td>Thiết bị điện</td>
                                <td>Panasonic</td>
                                <td>100A (Cái)</td>
                                <td>
                                    <button class="nut nut-sua" onclick="moBoxSua('VT001', 'Aptomat MCCB 3 pha', '1', '1', '1')">Sửa</button>
                                    <button class="nut nut-xoa" onclick="xacNhanXoa('VT001')">Xóa</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="cot-phai">
                    <div class="khung-nho">
                        <p style="font-weight: bold; margin-bottom: 10px;">Lọc danh mục</p>
                        <select style="width: 100%; padding: 8px; border: 1px solid #333;">
                            <option value="">-- Tất cả chủng loại --</option>
                            <?php 
                            if($truyvan_loai) {
                                while($row = mysqli_fetch_assoc($truyvan_loai)) {
                                    echo "<option value='".$row['id']."'>".$row['ten_loai']."</option>";
                                }
                            }
                            ?>
                        </select>
                        <button class="nut" style="width: 100%; margin-top: 10px;">Lọc dữ liệu</button>
                    </div>

                    <button class="nut nut-them" onclick="moBoxThem()">+ THÊM VẬT TƯ MỚI</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modalBox" class="lop-phu">
        <div class="hop-thoai">
            <h3 id="modalTitle" style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Thêm vật tư</h3>
            <form action="xuly_chitietvattu.php" method="POST">
                <input type="hidden" name="hanh_dong" id="form_action" value="them">
                
                <label>Mã vật tư (Khóa chính):</label>
                <input type="text" name="ma_vt" id="inp_ma" placeholder="Ví dụ: VT001" required>
                
                <label>Tên vật tư:</label>
                <input type="text" name="ten_vt" id="inp_ten" required>
                
                <label>Chủng loại (Tham chiếu):</label>
                <select name="id_chungloai" id="inp_loai">
                    <?php 
                    if($truyvan_loai && mysqli_num_rows($truyvan_loai) > 0) {
                        mysqli_data_seek($truyvan_loai, 0);
                        while($row = mysqli_fetch_assoc($truyvan_loai)) echo "<option value='".$row['id']."'>".$row['ten_loai']."</option>";
                    } else { echo "<option value='0'>-- Chưa có dữ liệu --</option>"; }
                    ?>
                </select>

                <label>Hãng sản xuất:</label>
                <select name="id_hangsx" id="inp_hang">
                    <?php 
                    if($truyvan_hang && mysqli_num_rows($truyvan_hang) > 0) {
                        while($row = mysqli_fetch_assoc($truyvan_hang)) echo "<option value='".$row['id']."'>".$row['ten_hang']."</option>";
                    } else { echo "<option value='0'>-- Chưa có dữ liệu --</option>"; }
                    ?>
                </select>

                <label>Thông số & ĐVT:</label>
                <select name="id_thongso" id="inp_thongso">
                    <?php 
                    if($truyvan_thongso && mysqli_num_rows($truyvan_thongso) > 0) {
                        while($row = mysqli_fetch_assoc($truyvan_thongso)) echo "<option value='".$row['id']."'>".$row['mota']." (".$row['dvt'].")</option>";
                    } else { echo "<option value='0'>-- Chưa có dữ liệu --</option>"; }
                    ?>
                </select>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="nut" style="flex:1; background: #28a745; color: white; border:none;">Lưu lại</button>
                    <button type="button" class="nut" style="flex:1;" onclick="dongBox()">Hủy bỏ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Hàm mở box thêm mới
        function moBoxThem() {
            document.getElementById('modalTitle').innerText = "Thêm vật tư mới";
            document.getElementById('form_action').value = "them";
            document.getElementById('inp_ma').value = "";
            document.getElementById('inp_ma').readOnly = false; // Mở khóa ID
            document.getElementById('inp_ten').value = "";
            document.getElementById('modalBox').style.display = 'block';
        }

        // Hàm mở box sửa (điền sẵn dữ liệu)
        function moBoxSua(ma, ten, loai, hang, thongso) {
            document.getElementById('modalTitle').innerText = "Chỉnh sửa vật tư";
            document.getElementById('form_action').value = "sua";
            document.getElementById('inp_ma').value = ma;
            document.getElementById('inp_ma').readOnly = true; // Khóa ID không cho sửa
            document.getElementById('inp_ten').value = ten;
            document.getElementById('inp_loai').value = loai;
            document.getElementById('inp_hang').value = hang;
            document.getElementById('inp_thongso').value = thongso;
            document.getElementById('modalBox').style.display = 'block';
        }

        function dongBox() { document.getElementById('modalBox').style.display = 'none'; }
        
        function xacNhanXoa(id) {
            if(confirm("Bạn có chắc chắn muốn xóa vật tư mã " + id + " không?")) {
                window.location.href = "xuly_chitietvattu.php?xoa=" + id;
            }
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
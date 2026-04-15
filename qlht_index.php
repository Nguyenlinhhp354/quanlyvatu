<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// XỬ LÝ CẬP NHẬT DỮ LIỆU KHI NGƯỜI DÙNG BẤM LƯU TỪ FORM SỬA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $id_sua = $_POST['edit_id'];
    $tai_khoan_sua = $_POST['edit_taikhoan'];
    $mat_khau_sua = $_POST['edit_matkhau'];
    $ho_ten_sua = $_POST['edit_hoten'];
    $vai_tro_sua = $_POST['edit_vaitro'];

    // Lệnh UPDATE dữ liệu vào Database
    $sql_update = "UPDATE nguoi_dung SET tai_khoan='$tai_khoan_sua', mat_khau='$mat_khau_sua', ho_ten='$ho_ten_sua', id_vai_tro='$vai_tro_sua' WHERE id_nguoi_dung='$id_sua'";
    
    if (mysqli_query($conn, $sql_update)) {
        echo "<script>alert('Cập nhật dữ liệu thành công!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể cập nhật!');</script>";
    }
}

// Giả sử đây là đoạn bạn kiểm tra tài khoản và mật khẩu thành công
$tai_khoan_dung = true;
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result = mysqli_query($conn, $sql);

// BẮT BUỘC dùng mysqli_fetch_assoc để lấy dữ liệu dạng mảng
if ($row = mysqli_fetch_assoc($result)) {
    $ho_ten = $row['ho_ten']; // Lấy họ tên gán vào biến $ho_ten
} else {
    $ho_ten = "Admin"; // Fallback dự phòng nếu có lỗi
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <div class="selection-function">
                    <div class="selection active"><a href="them_nguoi_dung.php">Thêm người dùng</a></div>
                    <div class="selection"><a href="phan_quyen.php">Phân quyền</a></div>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">ID</th>
                                <th width="15%" class="text-left">Tên tài khoản</th>
                                <th width="15%" class="text-left">Mật khẩu</th>
                                <th width="25%" class="text-left">Họ tên</th>
                                <th width="20%" class="text-left">Vai trò</th>
                                <th width="20%" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Câu truy vấn JOIN để lấy tên vai trò từ bảng vai_tro
                                // Sửa dòng này
                                $sql = "SELECT nguoi_dung.id_nguoi_dung, tai_khoan, mat_khau, ho_ten, nguoi_dung.id_vai_tro, ten_vai_tro 
                                        FROM nguoi_dung 
                                        JOIN vai_tro v ON nguoi_dung.id_vai_tro = v.id_vai_tro";
                                $result = mysqli_query($conn, $sql);
                                
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $row['id_nguoi_dung'] . "</td>";
                                    echo "<td class='text-left'>" . $row['tai_khoan'] . "</td>";
                                    echo "<td class='text-left'>" . $row['mat_khau'] . "</td>";
                                    echo "<td class='text-left'>" . $row['ho_ten'] . "</td>";
                                    echo "<td class='text-left'>" . $row['ten_vai_tro'] . "</td>";
                                    // Thêm cột hành động để bảng cân đối
                                    echo "<td class='text-center'>
                                        <a href='javascript:void(0)' class='btn-action btn-edit' 
                                        onclick='moModalSua({$row['id_nguoi_dung']}, \"{$row['tai_khoan']}\", \"{$row['mat_khau']}\", \"{$row['ho_ten']}\", {$row['id_vai_tro']})'>Sửa</a>
                                        <a href='#' class='btn-action btn-delete'>Xóa</a>
                                    </td>";
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                        <div id="editModal" class="modal-overlay">
                            <div class="modal-box">
                                <h3>Sửa thông tin người dùng</h3>
                                <form method="POST" action="qlht_index.php" onsubmit="return confirm('Bạn có chắc chắn muốn lưu các thay đổi này?');">
                                    <input type="hidden" name="action" value="edit_user">
                                    <input type="hidden" name="edit_id" id="modal_id"> 
                                    
                                    <div class="form-group">
                                        <label>Tên tài khoản:</label>
                                        <input type="text" name="edit_taikhoan" id="modal_taikhoan" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Mật khẩu:</label>
                                        <input type="text" name="edit_matkhau" id="modal_matkhau" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Họ tên:</label>
                                        <input type="text" name="edit_hoten" id="modal_hoten" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Vai trò:</label>
                                        <select name="edit_vaitro" id="modal_vaitro">
                                            <option value="1">Admin</option>
                                            <option value="2">Giám đốc</option>
                                            <option value="3">Thủ kho</option>
                                            <option value="4">Kế toán</option>
                                            <option value="5">Chỉ huy trưởng</option>
                                        </select>
                                    </div>
                                    
                                    <div class="modal-footer">
                                        <button type="button" class="btn-cancel" onclick="dongModal()">Hủy bỏ</button>
                                        <button type="submit" class="btn-save">Xác nhận Lưu</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            // Hàm mở modal và nhét dữ liệu từ bảng vào trong form
                            function moModalSua(id, taikhoan, matkhau, hoten, id_vaitro) {
                                document.getElementById("modal_id").value = id;
                                document.getElementById("modal_taikhoan").value = taikhoan;
                                document.getElementById("modal_matkhau").value = matkhau;
                                document.getElementById("modal_hoten").value = hoten;
                                document.getElementById("modal_vaitro").value = id_vaitro;
                                
                                // Hiện Modal lên
                                document.getElementById("editModal").style.display = "flex";
                            }

                            // Hàm đóng Modal
                            function dongModal() {
                                document.getElementById("editModal").style.display = "none";
                            }
                        </script>
                    </table>
                </div>
            </main>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>

<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 1. XỬ LÝ THÊM NGƯỜI DÙNG MỚI
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $tai_khoan_moi = mysqli_real_escape_string($conn, $_POST['add_taikhoan']);
    $mat_khau_moi = mysqli_real_escape_string($conn, $_POST['add_matkhau']);
    $ho_ten_moi = mysqli_real_escape_string($conn, $_POST['add_hoten']);
    $vai_tro_moi = $_POST['add_vaitro'];

    // Kiểm tra xem tài khoản đã tồn tại chưa
    $check_sql = "SELECT * FROM nguoi_dung WHERE tai_khoan='$tai_khoan_moi'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Lỗi: Tên tài khoản đã tồn tại trong hệ thống!'); window.location.href='qlht_index.php';</script>";
    } else {
        $sql_insert = "INSERT INTO nguoi_dung (tai_khoan, mat_khau, ho_ten, id_vai_tro) VALUES ('$tai_khoan_moi', '$mat_khau_moi', '$ho_ten_moi', '$vai_tro_moi')";
        if (mysqli_query($conn, $sql_insert)) {
            echo "<script>alert('Thêm người dùng thành công!'); window.location.href='qlht_index.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể thêm người dùng!');</script>";
        }
    }
}

// ==========================================
// 2. XỬ LÝ CẬP NHẬT (SỬA) THÔNG TIN CÁ NHÂN (KHÔNG ĐỔI QUYỀN)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $id_sua = $_POST['edit_id'];
    $tai_khoan_sua = mysqli_real_escape_string($conn, $_POST['edit_taikhoan']);
    $mat_khau_sua = mysqli_real_escape_string($conn, $_POST['edit_matkhau']);
    $ho_ten_sua = mysqli_real_escape_string($conn, $_POST['edit_hoten']);
    
    // Câu lệnh SQL ĐÃ BỎ trường id_vai_tro, do đó Modal Sửa không thể đổi quyền
    $sql_update = "UPDATE nguoi_dung SET tai_khoan='$tai_khoan_sua', mat_khau='$mat_khau_sua', ho_ten='$ho_ten_sua' WHERE id_nguoi_dung='$id_sua'";
    
    if (mysqli_query($conn, $sql_update)) {
        echo "<script>alert('Cập nhật thông tin thành công!'); window.location.href='qlht_index.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể cập nhật!');</script>";
    }
}

// ==========================================
// 3. XỬ LÝ CẤP QUYỀN (CHỈ ĐỔI VAI TRÒ)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_role') {
    $id_cap_quyen = $_POST['user_id'];
    $vai_tro_moi = $_POST['new_role'];

    // Không cho phép tự thay đổi quyền bản thân
    if ($id_cap_quyen == $_SESSION['id_nguoi_dung']) {
        echo "<script>alert('Cảnh báo: Bạn không thể tự thay đổi quyền hạn của tài khoản đang đăng nhập!'); window.location.href='qlht_index.php';</script>";
    } else {
        $sql_update_role = "UPDATE nguoi_dung SET id_vai_tro='$vai_tro_moi' WHERE id_nguoi_dung='$id_cap_quyen'";
        if (mysqli_query($conn, $sql_update_role)) {
            echo "<script>alert('Cập nhật quyền hạn thành công!'); window.location.href='qlht_index.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể cập nhật quyền!');</script>";
        }
    }
}

// ==========================================
// 4. XỬ LÝ XÓA NGƯỜI DÙNG
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    
    // Không cho phép tự xóa chính mình
    if($id_xoa == $_SESSION['id_nguoi_dung']) {
        echo "<script>alert('Lỗi: Bạn không thể tự xóa tài khoản đang đăng nhập!'); window.location.href='qlht_index.php';</script>";
    } else {
        $sql_delete = "DELETE FROM nguoi_dung WHERE id_nguoi_dung='$id_xoa'";
        if (mysqli_query($conn, $sql_delete)) {
            echo "<script>alert('Đã xóa người dùng!'); window.location.href='qlht_index.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể xóa!');</script>";
        }
    }
}

// Lấy thông tin người đăng nhập cho Header (tùy chọn vì header.php có thể đã tự gọi)
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_header = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result_header = mysqli_query($conn, $sql_header);
if ($row_header = mysqli_fetch_assoc($result_header)) {
    $ho_ten = $row_header['ho_ten']; 
} else {
    $ho_ten = "Admin"; 
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Hệ thống - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <div class="selection-function">
                    <div class="selection active" style="cursor: pointer;" onclick="moModalThem()">
                        <a>+ Thêm người dùng</a>
                    </div>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">ID</th>
                                <th width="15%" class="text-left">Tên tài khoản</th>
                                <th width="10%" class="text-left">Mật khẩu</th>
                                <th width="20%" class="text-left">Họ tên</th>
                                <th width="15%" class="text-left">Vai trò</th>
                                <th width="35%" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $sql = "SELECT nguoi_dung.id_nguoi_dung, tai_khoan, mat_khau, ho_ten, nguoi_dung.id_vai_tro, ten_vai_tro 
                                        FROM nguoi_dung 
                                        JOIN vai_tro v ON nguoi_dung.id_vai_tro = v.id_vai_tro
                                        ORDER BY id_nguoi_dung DESC";
                                $result = mysqli_query($conn, $sql);
                                
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $row['id_nguoi_dung'] . "</td>";
                                    echo "<td class='text-left'>" . $row['tai_khoan'] . "</td>";
                                    echo "<td class='text-left'>" . $row['mat_khau'] . "</td>";
                                    echo "<td class='text-left'>" . $row['ho_ten'] . "</td>";
                                    echo "<td class='text-left'>" . $row['ten_vai_tro'] . "</td>";
                                    
                                    // 3 NÚT: SỬA | CẤP QUYỀN | XÓA
                                    echo "<td class='text-center'>
                                        <a href='javascript:void(0)' class='btn-action btn-edit' 
                                        onclick='moModalSua({$row['id_nguoi_dung']}, \"{$row['tai_khoan']}\", \"{$row['mat_khau']}\", \"{$row['ho_ten']}\")'>Sửa</a>
                                        
                                        <a href='javascript:void(0)' class='btn-action' style='background-color: #ffc107; color: #333;' 
                                        onclick='moModalPhanQuyen({$row['id_nguoi_dung']}, \"{$row['ho_ten']}\", {$row['id_vai_tro']})'>Cấp quyền</a>
                                        
                                        <a href='qlht_index.php?action=delete&id={$row['id_nguoi_dung']}' onclick='return confirm(\"Bạn có chắc chắn muốn XÓA người dùng này không?\");' class='btn-action btn-delete'>Xóa</a>
                                    </td>";
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div id="addModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Thêm người dùng mới</h3>
            <form method="POST" action="qlht_index.php">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Tên tài khoản:</label>
                    <input type="text" name="add_taikhoan" required placeholder="Nhập tài khoản...">
                </div>
                
                <div class="form-group">
                    <label>Mật khẩu:</label>
                    <input type="text" name="add_matkhau" required placeholder="Nhập mật khẩu...">
                </div>
                
                <div class="form-group">
                    <label>Họ tên:</label>
                    <input type="text" name="add_hoten" required placeholder="Nhập họ và tên...">
                </div>
                
                <div class="form-group">
                    <label>Phân quyền (Vai trò):</label>
                    <select name="add_vaitro" required>
                        <option value="1">Admin</option>
                        <option value="2">Giám đốc</option>
                        <option value="3">Thủ kho</option>
                        <option value="4">Kế toán</option>
                        <option value="5">Chỉ huy trưởng</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="dongModalThem()">Hủy bỏ</button>
                    <button type="submit" class="btn-save">Thêm Mới</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Sửa thông tin cơ bản</h3>
            <form method="POST" action="qlht_index.php" onsubmit="return confirm('Bạn có chắc chắn muốn lưu thay đổi thông tin này?');">
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
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="dongModalSua()">Hủy bỏ</button>
                    <button type="submit" class="btn-save">Lưu Thông Tin</button>
                </div>
            </form>
        </div>
    </div>

    <div id="roleModal" class="modal-overlay">
        <div class="modal-box" style="width: 350px;">
            <h3>Cấp quyền người dùng</h3>
            <form method="POST" action="qlht_index.php">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" id="modal_role_id"> 
                
                <div class="form-group">
                    <label>Nhân viên:</label>
                    <input type="text" id="modal_role_name" readonly style="background-color: #e9ecef;">
                </div>
                
                <div class="form-group">
                    <label>Chọn quyền hạn mới:</label>
                    <select name="new_role" id="modal_role_select" required>
                        <option value="1">Admin</option>
                        <option value="2">Giám đốc</option>
                        <option value="3">Thủ kho</option>
                        <option value="4">Kế toán</option>
                        <option value="5">Chỉ huy trưởng</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="dongModalPhanQuyen()">Hủy</button>
                    <button type="submit" class="btn-save">Lưu Quyền Hạn</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Thêm
        function moModalThem() {
            document.getElementById("addModal").style.display = "flex";
        }
        function dongModalThem() {
            document.getElementById("addModal").style.display = "none";
        }

        // Modal Sửa Thông Tin
        function moModalSua(id, taikhoan, matkhau, hoten) {
            document.getElementById("modal_id").value = id;
            document.getElementById("modal_taikhoan").value = taikhoan;
            document.getElementById("modal_matkhau").value = matkhau;
            document.getElementById("modal_hoten").value = hoten;
            
            document.getElementById("editModal").style.display = "flex";
        }
        function dongModalSua() {
            document.getElementById("editModal").style.display = "none";
        }

        // Modal Cấp Quyền
        function moModalPhanQuyen(id, hoten, id_vaitro) {
            document.getElementById("modal_role_id").value = id;
            document.getElementById("modal_role_name").value = hoten;
            document.getElementById("modal_role_select").value = id_vaitro;
            
            document.getElementById("roleModal").style.display = "flex";
        }
        function dongModalPhanQuyen() {
            document.getElementById("roleModal").style.display = "none";
        }
    </script>
</body>
</html>
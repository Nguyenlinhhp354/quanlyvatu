<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 1. XỬ LÝ THÊM NGƯỜI DÙNG MỚI (Có bắt lỗi)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $tai_khoan_moi = mysqli_real_escape_string($conn, trim($_POST['add_taikhoan']));
    $mat_khau_moi = mysqli_real_escape_string($conn, trim($_POST['add_matkhau']));
    $ho_ten_moi = mysqli_real_escape_string($conn, trim($_POST['add_hoten']));
    $vai_tro_moi = intval($_POST['add_vaitro']);

    // Validate dữ liệu
    if(empty($tai_khoan_moi) || empty($mat_khau_moi) || empty($ho_ten_moi)) {
        echo "<script>alert('Lỗi: Không được để trống thông tin!'); window.location.href='qlht_nguoi_dung.php';</script>";
        exit();
    }
    if(strlen($tai_khoan_moi) < 4) {
        echo "<script>alert('Lỗi: Tên tài khoản phải có ít nhất 4 ký tự!'); window.location.href='qlht_nguoi_dung.php';</script>";
        exit();
    }

    $check_sql = "SELECT * FROM nguoi_dung WHERE tai_khoan='$tai_khoan_moi'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Lỗi: Tên tài khoản đã tồn tại trong hệ thống!'); window.location.href='qlht_nguoi_dung.php';</script>";
    } else {
        $sql_insert = "INSERT INTO nguoi_dung (tai_khoan, mat_khau, ho_ten, id_vai_tro) VALUES ('$tai_khoan_moi', '$mat_khau_moi', '$ho_ten_moi', '$vai_tro_moi')";
        if (mysqli_query($conn, $sql_insert)) {
            echo "<script>alert('Thêm người dùng thành công!'); window.location.href='qlht_nguoi_dung.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể thêm người dùng!');</script>";
        }
    }
}

// ==========================================
// 2. XỬ LÝ CẬP NHẬT (SỬA) THÔNG TIN
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $id_sua = intval($_POST['edit_id']);
    $tai_khoan_sua = mysqli_real_escape_string($conn, trim($_POST['edit_taikhoan']));
    $mat_khau_sua = mysqli_real_escape_string($conn, trim($_POST['edit_matkhau']));
    $ho_ten_sua = mysqli_real_escape_string($conn, trim($_POST['edit_hoten']));
    
    if(empty($tai_khoan_sua) || empty($mat_khau_sua) || empty($ho_ten_sua)) {
        echo "<script>alert('Lỗi: Không được để trống thông tin khi sửa!'); window.location.href='qlht_nguoi_dung.php';</script>";
        exit();
    }

    // Kiểm tra xem tài khoản sửa có bị trùng với người khác không
    $check_sql = "SELECT * FROM nguoi_dung WHERE tai_khoan='$tai_khoan_sua' AND id_nguoi_dung != '$id_sua'";
    if(mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
        echo "<script>alert('Lỗi: Tên tài khoản bị trùng lặp với người khác!'); window.location.href='qlht_nguoi_dung.php';</script>";
        exit();
    }

    $sql_update = "UPDATE nguoi_dung SET tai_khoan='$tai_khoan_sua', mat_khau='$mat_khau_sua', ho_ten='$ho_ten_sua' WHERE id_nguoi_dung='$id_sua'";
    if (mysqli_query($conn, $sql_update)) {
        echo "<script>alert('Cập nhật thông tin thành công!'); window.location.href='qlht_nguoi_dung.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể cập nhật!');</script>";
    }
}

// ==========================================
// 3. XỬ LÝ XÓA NGƯỜI DÙNG
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    if($id_xoa == $_SESSION['id_nguoi_dung']) {
        echo "<script>alert('Lỗi: Bạn không thể tự xóa tài khoản đang đăng nhập!'); window.location.href='qlht_nguoi_dung.php';</script>";
    } else {
        $sql_delete = "DELETE FROM nguoi_dung WHERE id_nguoi_dung='$id_xoa'";
        if (mysqli_query($conn, $sql_delete)) {
            echo "<script>alert('Đã xóa người dùng!'); window.location.href='qlht_nguoi_dung.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể xóa vì người dùng này đã có dữ liệu giao dịch!');</script>";
        }
    }
}

// ==========================================
// 4. XỬ LÝ TÌM KIẾM
// ==========================================
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$where_conditions = ["1=1"];
if ($search_query != "") {
    $where_conditions[] = "(tai_khoan LIKE '%$search_query%' OR ho_ten LIKE '%$search_query%')";
}
$where_sql = implode(" AND ", $where_conditions);

// Lấy danh sách Vai trò cho lúc Thêm mới
$sql_roles = "SELECT id_vai_tro, ten_vai_tro FROM vai_tro ORDER BY id_vai_tro ASC";
$result_roles = mysqli_query($conn, $sql_roles);
$roles_array = [];
while($r = mysqli_fetch_assoc($result_roles)){
    $roles_array[] = $r;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .filter-wrapper { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;}
        .filter-form { display: flex; gap: 10px; align-items: center; background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-form input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <div class="filter-wrapper">
                    <div class="selection-function" style="margin: 0;">
                        <div class="selection active" style="cursor: pointer;" onclick="moModalThem()">
                            <a>+ Thêm người dùng</a>
                        </div>
                    </div>

                    <form class="filter-form" method="GET" action="qlht_nguoi_dung.php">
                        <input type="text" name="search" placeholder="Nhập tài khoản, họ tên..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                        <button type="submit" class="btn-search">Tìm kiếm</button>
                        <?php if($search_query != ""): ?>
                            <a href="qlht_nguoi_dung.php" class="btn-clear">Xóa lọc</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">ID</th>
                                <th width="20%" class="text-left">Tên tài khoản</th>
                                <th width="20%" class="text-left">Mật khẩu</th>
                                <th width="35%" class="text-left">Họ tên</th>
                                <th width="20%" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $sql = "SELECT id_nguoi_dung, tai_khoan, mat_khau, ho_ten FROM nguoi_dung WHERE $where_sql ORDER BY id_nguoi_dung DESC";
                                $result = mysqli_query($conn, $sql);
                                
                                if(mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td class='text-center'>" . $row['id_nguoi_dung'] . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['tai_khoan']) . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['mat_khau']) . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['ho_ten']) . "</td>";
                                        echo "<td class='text-center'>
                                            <a href='javascript:void(0)' class='btn-action btn-edit' 
                                            onclick='moModalSua({$row['id_nguoi_dung']}, \"{$row['tai_khoan']}\", \"{$row['mat_khau']}\", \"{$row['ho_ten']}\")'>Sửa</a>
                                            
                                            <a href='qlht_nguoi_dung.php?action=delete&id={$row['id_nguoi_dung']}' onclick='return confirm(\"Bạn có chắc chắn muốn XÓA người dùng này không?\");' class='btn-action btn-delete'>Xóa</a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center' style='padding: 20px;'>Không tìm thấy người dùng!</td></tr>";
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
            <form method="POST" action="qlht_nguoi_dung.php">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Tên tài khoản (*):</label>
                    <input type="text" name="add_taikhoan" required placeholder="Nhập tài khoản (tối thiểu 4 ký tự)...">
                </div>
                <div class="form-group">
                    <label>Mật khẩu (*):</label>
                    <input type="text" name="add_matkhau" required placeholder="Nhập mật khẩu...">
                </div>
                <div class="form-group">
                    <label>Họ tên (*):</label>
                    <input type="text" name="add_hoten" required placeholder="Nhập họ và tên...">
                </div>
                <div class="form-group">
                    <label>Vai trò ban đầu:</label>
                    <select name="add_vaitro" required>
                        <?php foreach($roles_array as $role) { echo "<option value='{$role['id_vai_tro']}'>{$role['ten_vai_tro']}</option>"; } ?>
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
            <h3>Sửa thông tin cá nhân</h3>
            <form method="POST" action="qlht_nguoi_dung.php">
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

    <script>
        function moModalThem() { document.getElementById("addModal").style.display = "flex"; }
        function dongModalThem() { document.getElementById("addModal").style.display = "none"; }
        function moModalSua(id, taikhoan, matkhau, hoten) {
            document.getElementById("modal_id").value = id;
            document.getElementById("modal_taikhoan").value = taikhoan;
            document.getElementById("modal_matkhau").value = matkhau;
            document.getElementById("modal_hoten").value = hoten;
            document.getElementById("editModal").style.display = "flex";
        }
        function dongModalSua() { document.getElementById("editModal").style.display = "none"; }
    </script>
</body>
</html>
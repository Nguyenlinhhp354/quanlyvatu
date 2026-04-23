<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 1. XỬ LÝ CẤP QUYỀN (CHỈ ĐỔI VAI TRÒ)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_role') {
    $id_cap_quyen = intval($_POST['user_id']);
    $vai_tro_moi = intval($_POST['new_role']);

    if ($id_cap_quyen == $_SESSION['id_nguoi_dung']) {
        echo "<script>alert('Cảnh báo: Bạn không thể tự hạ/thay đổi quyền hạn của chính mình!'); window.location.href='qlht_phan_quyen.php';</script>";
    } else {
        $sql_update_role = "UPDATE nguoi_dung SET id_vai_tro='$vai_tro_moi' WHERE id_nguoi_dung='$id_cap_quyen'";
        if (mysqli_query($conn, $sql_update_role)) {
            echo "<script>alert('Cập nhật quyền hạn thành công!'); window.location.href='qlht_phan_quyen.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể cập nhật quyền!');</script>";
        }
    }
}

// ==========================================
// 2. LỌC VÀ TÌM KIẾM (Chỉ lọc role và tên)
// ==========================================
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$filter_role = isset($_GET['role']) ? mysqli_real_escape_string($conn, trim($_GET['role'])) : "";

$where_conditions = ["1=1"];
if ($search_query != "") {
    $where_conditions[] = "(tai_khoan LIKE '%$search_query%' OR ho_ten LIKE '%$search_query%')";
}
if ($filter_role != "") {
    $where_conditions[] = "nguoi_dung.id_vai_tro = '$filter_role'";
}
$where_sql = implode(" AND ", $where_conditions);

// Lấy danh sách Vai trò cho Select/Option
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
    <title>Phân quyền hệ thống - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .filter-wrapper { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .filter-form { display: flex; gap: 10px; align-items: center; background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-form input, .filter-form select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
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
                    <form class="filter-form" method="GET" action="qlht_phan_quyen.php">
                        <input type="text" name="search" placeholder="Nhập tên tài khoản..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 200px;">
                        <select name="role">
                            <option value="">-- Tất cả vai trò --</option>
                            <?php 
                                foreach($roles_array as $role) {
                                    $selected = ($filter_role == $role['id_vai_tro']) ? 'selected' : '';
                                    echo "<option value='{$role['id_vai_tro']}' $selected>{$role['ten_vai_tro']}</option>";
                                }
                            ?>
                        </select>
                        <button type="submit" class="btn-search">Lọc dữ liệu</button>
                        <?php if($search_query != "" || $filter_role != ""): ?>
                            <a href="qlht_phan_quyen.php" class="btn-clear">Xóa lọc</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="10%" class="text-center">ID</th>
                                <th width="20%" class="text-left">Tên tài khoản</th>
                                <th width="30%" class="text-left">Họ và tên</th>
                                <th width="20%" class="text-center">Vai trò hiện tại</th>
                                <th width="20%" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $sql = "SELECT nguoi_dung.id_nguoi_dung, tai_khoan, ho_ten, nguoi_dung.id_vai_tro, ten_vai_tro 
                                        FROM nguoi_dung 
                                        JOIN vai_tro v ON nguoi_dung.id_vai_tro = v.id_vai_tro
                                        WHERE $where_sql ORDER BY id_nguoi_dung DESC";
                                $result = mysqli_query($conn, $sql);
                                
                                if(mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $role_style = ($row['id_vai_tro'] == 1) ? "color: red; font-weight: bold;" : "color: #007bff; font-weight: bold;";

                                        echo "<tr>";
                                        echo "<td class='text-center'>" . $row['id_nguoi_dung'] . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['tai_khoan']) . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['ho_ten']) . "</td>";
                                        echo "<td class='text-center' style='$role_style'>" . $row['ten_vai_tro'] . "</td>";
                                        
                                        echo "<td class='text-center'>
                                            <a href='javascript:void(0)' class='btn-action' style='background-color: #ffc107; color: #333; font-weight: bold;' 
                                            onclick='moModalPhanQuyen({$row['id_nguoi_dung']}, \"{$row['ho_ten']}\", {$row['id_vai_tro']})'>⚙️ Đổi quyền</a>
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

    <div id="roleModal" class="modal-overlay">
        <div class="modal-box" style="width: 350px;">
            <h3>Cấp quyền truy cập</h3>
            <form method="POST" action="qlht_phan_quyen.php">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" id="modal_role_id"> 
                
                <div class="form-group">
                    <label>Nhân viên:</label>
                    <input type="text" id="modal_role_name" readonly style="background-color: #e9ecef;">
                </div>
                <div class="form-group">
                    <label>Chọn quyền hạn mới:</label>
                    <select name="new_role" id="modal_role_select" required>
                        <?php 
                            foreach($roles_array as $role) {
                                echo "<option value='{$role['id_vai_tro']}'>{$role['ten_vai_tro']}</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="dongModalPhanQuyen()">Hủy bỏ</button>
                    <button type="submit" class="btn-save">Lưu Quyền Hạn</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
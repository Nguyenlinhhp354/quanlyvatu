<?php
session_start();
include 'db_connect.php'; // Kết nối đến database

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 1. XỬ LÝ THÊM KHO MỚI
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_kho') {
    $ten_kho = mysqli_real_escape_string($conn, trim($_POST['ten_kho']));
    $dia_chi = mysqli_real_escape_string($conn, trim($_POST['dia_chi']));

    // Bắt lỗi: Kiểm tra trùng tên kho
    $check_sql = "SELECT * FROM kho WHERE ten_kho='$ten_kho'";
    if(mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
        echo "<script>alert('Lỗi: Tên kho này đã tồn tại!'); window.location.href='qldm_kho.php';</script>";
    } else {
        $sql_insert = "INSERT INTO kho (ten_kho, dia_chi) VALUES ('$ten_kho', '$dia_chi')";
        if (mysqli_query($conn, $sql_insert)) {
            echo "<script>alert('Thêm kho thành công!'); window.location.href='qldm_kho.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể thêm kho!');</script>";
        }
    }
}

// ==========================================
// 2. XỬ LÝ SỬA THÔNG TIN KHO
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_kho') {
    $id_kho = $_POST['edit_id'];
    $ten_kho = mysqli_real_escape_string($conn, trim($_POST['edit_ten_kho']));
    $dia_chi = mysqli_real_escape_string($conn, trim($_POST['edit_dia_chi']));

    // Bắt lỗi: Trùng tên với kho KHÁC
    $check_sql = "SELECT * FROM kho WHERE ten_kho='$ten_kho' AND id_kho != '$id_kho'";
    if(mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
        echo "<script>alert('Lỗi: Tên kho bị trùng lặp với một kho khác!'); window.location.href='qldm_kho.php';</script>";
    } else {
        $sql_update = "UPDATE kho SET ten_kho='$ten_kho', dia_chi='$dia_chi' WHERE id_kho='$id_kho'";
        if (mysqli_query($conn, $sql_update)) {
            echo "<script>alert('Cập nhật kho thành công!'); window.location.href='qldm_kho.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể cập nhật!');</script>";
        }
    }
}

// ==========================================
// 3. XỬ LÝ XÓA KHO
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    $sql_delete = "DELETE FROM kho WHERE id_kho='$id_xoa'";
    
    if (mysqli_query($conn, $sql_delete)) {
        echo "<script>alert('Đã xóa kho thành công!'); window.location.href='qldm_kho.php';</script>";
    } else {
        // Bắt lỗi khóa ngoại: Lỗi 1451 xảy ra khi bảng khác (vat_tu, phieu_nhap) đang dùng id_kho này
        if (mysqli_errno($conn) == 1451) {
            echo "<script>alert('LỖI NGHIÊM TRỌNG: Không thể xóa kho này vì kho đang chứa vật tư hoặc đã có lịch sử nhập/xuất!'); window.location.href='qldm_kho.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể xóa kho!'); window.location.href='qldm_kho.php';</script>";
        }
    }
}

// ==========================================
// 4. XỬ LÝ TÌM KIẾM VÀ SẮP XẾP
// ==========================================
$search_query = "";
$search_sql = "";
if (isset($_GET['search']) && trim($_GET['search']) != "") {
    $search_query = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_sql = " WHERE ten_kho LIKE '%$search_query%' OR dia_chi LIKE '%$search_query%' ";
}

$sort_col = isset($_GET['sort']) ? $_GET['sort'] : 'id_kho'; // Mặc định sắp xếp theo ID
$sort_dir = isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'ASC' : 'DESC'; // Mặc định giảm dần
$next_dir = ($sort_dir == 'ASC') ? 'desc' : 'asc'; // Biến đổi chiều cho lần click tiếp theo

// Chỉ cho phép sắp xếp các cột này (Bảo mật SQL Injection)
if (!in_array($sort_col, ['id_kho', 'ten_kho', 'dia_chi'])) { $sort_col = 'id_kho'; }

// Lấy thông tin người đăng nhập cho Header
$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$ho_ten = ($row_user = mysqli_fetch_assoc(mysqli_query($conn, $sql_user))) ? $row_user['ho_ten'] : "Admin";

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Danh mục Kho - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* CSS cho thanh tìm kiếm */
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; width: 250px;}
        .search-box button { padding: 8px 15px; background: #343a40; color: white; border: none; border-radius: 4px; cursor: pointer;}
        .search-box button:hover { background: #23272b; }
        /* CSS cho thẻ a sắp xếp */
        .sort-link { color: white; text-decoration: none; }
        .sort-link:hover { text-decoration: underline; color: #ffc107; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <h2 class="page-title">Quản lý Danh mục Kho</h2>

                <div class="toolbar">
                    <div class="selection-function" style="margin-bottom: 0;">
                        <div class="selection active" style="cursor: pointer;" onclick="moModalThem()">
                            <a>+ Thêm Kho Mới</a>
                        </div>
                    </div>

                    <form class="search-box" method="GET" action="qldm_kho.php">
                        <input type="text" name="search" placeholder="Nhập tên kho hoặc địa chỉ..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">Tìm kiếm</button>
                        <?php if($search_query != ""): ?>
                            <a href="qldm_kho.php" style="padding: 8px 10px; background:#dc3545; color:white; text-decoration:none; border-radius:4px;">Xóa lọc</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div id="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="10%" class="text-center">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&sort=id_kho&dir=<?php echo $next_dir; ?>">
                                        ID <?php if($sort_col=='id_kho') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="30%" class="text-left">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&sort=ten_kho&dir=<?php echo $next_dir; ?>">
                                        Tên Kho <?php if($sort_col=='ten_kho') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="40%" class="text-left">
                                    <a class="sort-link" href="?search=<?php echo urlencode($search_query); ?>&sort=dia_chi&dir=<?php echo $next_dir; ?>">
                                        Địa chỉ <?php if($sort_col=='dia_chi') echo ($sort_dir=='ASC') ? '↑' : '↓'; ?>
                                    </a>
                                </th>
                                <th width="20%" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Câu truy vấn kết hợp Tìm kiếm và Sắp xếp
                                $sql = "SELECT * FROM kho $search_sql ORDER BY $sort_col $sort_dir";
                                $result = mysqli_query($conn, $sql);
                                
                                if(mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td class='text-center'>" . $row['id_kho'] . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['ten_kho']) . "</td>";
                                        echo "<td class='text-left'>" . htmlspecialchars($row['dia_chi']) . "</td>";
                                        echo "<td class='text-center'>
                                            <a href='javascript:void(0)' class='btn-action btn-edit' 
                                            onclick='moModalSua({$row['id_kho']}, \"" . htmlspecialchars($row['ten_kho'], ENT_QUOTES) . "\", \"" . htmlspecialchars($row['dia_chi'], ENT_QUOTES) . "\")'>Sửa</a>
                                            
                                            <a href='qldm_kho.php?action=delete&id={$row['id_kho']}' onclick='return confirm(\"Bạn có chắc chắn muốn XÓA kho này không?\");' class='btn-action btn-delete'>Xóa</a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>Không tìm thấy dữ liệu kho nào!</td></tr>";
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
        <div class="modal-box" style="width: 450px;">
            <h3>Thêm Kho Mới</h3>
            <form method="POST" action="qldm_kho.php">
                <input type="hidden" name="action" value="add_kho">
                
                <div class="form-group">
                    <label>Tên kho: <span style="color:red;">*</span></label>
                    <input type="text" name="ten_kho" required placeholder="VD: Kho Vật Tư Điện Nước...">
                </div>
                
                <div class="form-group">
                    <label>Địa chỉ kho:</label>
                    <textarea name="dia_chi" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Nhập địa chỉ chi tiết..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="dongModalThem()">Hủy</button>
                    <button type="submit" class="btn-save">Thêm Mới</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-box" style="width: 450px;">
            <h3>Sửa Thông Tin Kho</h3>
            <form method="POST" action="qldm_kho.php">
                <input type="hidden" name="action" value="edit_kho">
                <input type="hidden" name="edit_id" id="modal_id"> 
                
                <div class="form-group">
                    <label>Tên kho: <span style="color:red;">*</span></label>
                    <input type="text" name="edit_ten_kho" id="modal_ten_kho" required>
                </div>
                
                <div class="form-group">
                    <label>Địa chỉ kho:</label>
                    <textarea name="edit_dia_chi" id="modal_dia_chi" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="dongModalSua()">Hủy</button>
                    <button type="submit" class="btn-save">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function moModalThem() {
            document.getElementById("addModal").style.display = "flex";
        }
        function dongModalThem() {
            document.getElementById("addModal").style.display = "none";
        }

        function moModalSua(id, ten, diachi) {
            document.getElementById("modal_id").value = id;
            document.getElementById("modal_ten_kho").value = ten;
            document.getElementById("modal_dia_chi").value = diachi;
            document.getElementById("editModal").style.display = "flex";
        }
        function dongModalSua() {
            document.getElementById("editModal").style.display = "none";
        }
    </script>
</body>
</html>
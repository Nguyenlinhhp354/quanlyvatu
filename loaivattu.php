<?php
session_start();
include 'db_connect.php'; 

// --- 1. XỬ LÝ DỮ LIỆU ---
$msg = ""; $msg_type = "";
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_new = trim(mysqli_real_escape_string($conn, $_POST['id_loai']));
    $ten = trim(mysqli_real_escape_string($conn, $_POST['ten_loai']));

    // Bắt lỗi không để trống
    if (empty($id_new) || empty($ten)) {
        $msg = "Lỗi: Không được để trống Mã ID hoặc Tên!"; $msg_type = "error";
    } else {
        if ($action == 'add_loai') {
            $check = mysqli_query($conn, "SELECT id_loai_vat_tu FROM loai_vat_tu WHERE id_loai_vat_tu='$id_new' OR ten_loai_vat_tu='$ten'");
            if (mysqli_num_rows($check) > 0) {
                $msg = "Lỗi: Mã ID hoặc Tên đã tồn tại!"; $msg_type = "error";
            } else {
                mysqli_query($conn, "INSERT INTO loai_vat_tu (id_loai_vat_tu, ten_loai_vat_tu) VALUES ('$id_new', '$ten')");
                $msg = "Thêm thành công!"; $msg_type = "success";
            }
        } 
        elseif ($action == 'edit_loai') {
            $id_old = mysqli_real_escape_string($conn, $_POST['edit_id_old']);
            // Bắt lỗi trùng ID hoặc Tên với các dòng khác khi sửa
            $check = mysqli_query($conn, "SELECT id_loai_vat_tu FROM loai_vat_tu WHERE (id_loai_vat_tu='$id_new' OR ten_loai_vat_tu='$ten') AND id_loai_vat_tu != '$id_old'");
            if (mysqli_num_rows($check) > 0) {
                $msg = "Lỗi: ID hoặc Tên mới bị trùng với dữ liệu có sẵn!"; $msg_type = "error";
            } else {
                mysqli_query($conn, "UPDATE loai_vat_tu SET id_loai_vat_tu='$id_new', ten_loai_vat_tu='$ten' WHERE id_loai_vat_tu='$id_old'");
                $msg = "Cập nhật thành công!"; $msg_type = "success";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id_del = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM loai_vat_tu WHERE id_loai_vat_tu='$id_del'");
    $msg = "Đã xóa thành công!"; $msg_type = "success";
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chủng loại vật tư - Thịnh Tiến</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Layout cấu trúc ngang */
        .wrapper { display: flex; align-items: flex-start; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; box-sizing: border-box; background: #f4f7f6; }
        
        /* Thông báo Modal */
        .msg-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; }
        .msg-box { background: white; padding: 25px; border-radius: 5px; text-align: center; min-width: 350px; border: 2px solid #333; }
        .btn-msg { padding: 10px 25px; cursor: pointer; border: 1px solid #ccc; font-weight: bold; margin-top: 15px; border-radius: 4px; }
        .success-c { color: #28a745; } .error-c { color: #dc3545; }
        
        /* Style Table chuẩn */
        table { width: 100%; border-collapse: collapse; background: white; }
        thead { background: #343a40; color: white; }
        th, td { padding: 12px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <main>
                <div style="margin-bottom: 20px;">
                    <a href="quanlydanhmuc.php" style="font-weight:bold; color:blue; text-decoration:none;">Quản lí danh mục</a> &gt; Chủng loại vật tư
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <form method="GET" style="display: flex; gap: 5px;">
                        <input type="text" name="search" placeholder="Tìm ID hoặc Tên..." value="<?=htmlspecialchars($search)?>" style="padding: 8px; width: 250px; border: 1px solid #ccc;">
                        <button type="submit" class="btn-action">Tìm kiếm</button>
                    </form>
                    <button class="btn-action" style="background:#007bff; color:white; border:none; padding:10px 20px; border-radius:5px;" onclick="openModal('add')">Thêm chủng loại</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th width="15%">ID LOẠI</th>
                            <th width="55%" style="text-align: left;">TÊN CHỦNG LOẠI</th>
                            <th width="30%">THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = "SELECT * FROM loai_vat_tu";
                        if($search) $q .= " WHERE id_loai_vat_tu LIKE '%$search%' OR ten_loai_vat_tu LIKE '%$search%'";
                        $res = mysqli_query($conn, $q);
                        while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td align="center"><?=$r['id_loai_vat_tu']?></td>
                                <td><?=$r['ten_loai_vat_tu']?></td>
                                <td align="center">
                                    <button onclick="openModal('edit', '<?=$r['id_loai_vat_tu']?>', '<?=$r['ten_loai_vat_tu']?>')" style="background:#28a745; color:white; border:none; padding:5px 12px; border-radius:4px; cursor:pointer;">Sửa</button>
                                    <button onclick="askDel('<?=$r['id_loai_vat_tu']?>')" style="background:#dc3545; color:white; border:none; padding:5px 12px; border-radius:4px; cursor:pointer; margin-left:5px;">Xóa</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <div id="dataModal" class="msg-overlay">
        <div class="msg-box" style="text-align: left; width: 400px;">
            <h3 id="m_title" style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Thông tin</h3>
            <form method="POST" id="mainForm">
                <input type="hidden" name="action" id="m_action">
                <input type="hidden" name="edit_id_old" id="m_id_old">
                <div style="margin-top:10px;">
                    <label><b>Mã ID:</b></label>
                    <input type="text" name="id_loai" id="m_id_in" required style="width:100%; padding:10px; margin-top:5px; box-sizing: border-box;">
                </div>
                <div style="margin-top:15px;">
                    <label><b>Tên chủng loại:</b></label>
                    <input type="text" name="ten_loai" id="m_ten_in" required style="width:100%; padding:10px; margin-top:5px; box-sizing: border-box;">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn-msg" onclick="closeModal()">Đóng</button>
                    <button type="submit" class="btn-msg" style="background:#007bff; color:white; border:none;">Xác nhận Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div id="msgOverlay" class="msg-overlay">
        <div class="msg-box">
            <h4 id="msgT" style="margin-top:0; font-size: 20px;"></h4>
            <p id="msgB" style="font-size: 16px; margin: 15px 0;"></p>
            <div id="msgBtns"></div>
        </div>
    </div>

    <script>
        // Modal xử lý Form
        function openModal(mode, id = '', ten = '') {
            document.getElementById('m_title').innerText = mode == 'add' ? 'Thêm mới chủng loại' : 'Chỉnh sửa chủng loại';
            document.getElementById('m_action').value = mode == 'add' ? 'add_loai' : 'edit_loai';
            document.getElementById('m_id_old').value = id;
            document.getElementById('m_id_in').value = id;
            document.getElementById('m_ten_in').value = ten;
            document.getElementById('dataModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('dataModal').style.display = 'none'; }

        // Popup thông báo trung tâm
        function showPop(title, body, type = 'success', isConfirm = false, id = '') {
            document.getElementById('msgT').innerText = title;
            document.getElementById('msgT').className = type === 'success' ? 'success-c' : 'error-c';
            document.getElementById('msgB').innerText = body;
            const b = document.getElementById('msgBtns');
            if(isConfirm) {
                b.innerHTML = `<button class="btn-msg" style="background:#dc3545; color:white; border:none;" onclick="location.href='?delete=${id}'">Xác nhận xóa</button>
                               <button class="btn-msg" onclick="document.getElementById('msgOverlay').style.display='none'">Hủy bỏ</button>`;
            } else {
                b.innerHTML = `<button class="btn-msg" onclick="location.href='loaivattu.php'">Đóng</button>`;
            }
            document.getElementById('msgOverlay').style.display = 'flex';
        }

        function askDel(id) { showPop('Xác nhận xóa', 'Bạn chắc chắn muốn xóa vĩnh viễn mã: ' + id + '?', 'error', true, id); }

        <?php if($msg): ?>
            showPop('<?=$msg_type=='success'?'Thành công':'Thông báo'?>', '<?=$msg?>', '<?=$msg_type?>');
        <?php endif; ?>
    </script>
</body>
</html>
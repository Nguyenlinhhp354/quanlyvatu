<?php
session_start();
include 'db_connect.php'; 

// =========================================================
// KHU VỰC 1: KIỂM TRA ĐĂNG NHẬP & KHỞI TẠO
// =========================================================
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit(); }
$msg = ""; $msg_type = "";

// =========================================================
// KHU VỰC 2: XỬ LÝ CSDL (THÊM / SỬA / XÓA)
// =========================================================

// --- CHỨC NĂNG XÓA ---
if (isset($_GET['delete'])) {
    $id_del = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM du_an WHERE id_du_an='$id_del'");
    $msg = "Đã xóa dự án thành công!"; $msg_type = "success";
}

// --- CHỨC NĂNG THÊM & SỬA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_new = trim(mysqli_real_escape_string($conn, $_POST['id_du_an']));
    $ten    = trim(mysqli_real_escape_string($conn, $_POST['ten_du_an']));
    $dia_diem = trim(mysqli_real_escape_string($conn, $_POST['dia_diem']));
    $trang_thai = trim(mysqli_real_escape_string($conn, $_POST['trang_thai']));

    if (empty($id_new) || empty($ten)) {
        $msg = "Lỗi: Mã ID và Tên dự án không được để trống!"; 
        $msg_type = "error";
    } else {
        if ($action == 'add') {
            $check = mysqli_query($conn, "SELECT id_du_an FROM du_an WHERE id_du_an='$id_new' OR ten_du_an='$ten'");
            if (mysqli_num_rows($check) > 0) {
                $msg = "Lỗi: ID hoặc Tên dự án này đã tồn tại!"; $msg_type = "error";
            } else {
                $sql = "INSERT INTO du_an (id_du_an, ten_du_an, dia_diem, trang_thai) VALUES ('$id_new', '$ten', '$dia_diem', '$trang_thai')";
                mysqli_query($conn, $sql);
                $msg = "Thêm dự án thành công!"; $msg_type = "success";
            }
        } 
        elseif ($action == 'edit') {
            $id_old = mysqli_real_escape_string($conn, $_POST['id_old']);
            $check = mysqli_query($conn, "SELECT id_du_an FROM du_an WHERE (id_du_an='$id_new' OR ten_du_an='$ten') AND id_du_an != '$id_old'");
            if (mysqli_num_rows($check) > 0) {
                $msg = "Lỗi: ID hoặc Tên mới bị trùng với dự án khác!"; $msg_type = "error";
            } else {
                $sql = "UPDATE du_an SET id_du_an='$id_new', ten_du_an='$ten', dia_diem='$dia_diem', trang_thai='$trang_thai' WHERE id_du_an='$id_old'";
                mysqli_query($conn, $sql);
                $msg = "Cập nhật thành công!"; $msg_type = "success";
            }
        }
    }
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý dự án - Thịnh Tiến</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">

    <style>
        .wrapper { display: flex; align-items: flex-start; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; box-sizing: border-box; background: #f4f7f6; }
        
        /* Tiêu đề trang đơn giản theo yêu cầu */
        .page-title { 
            color: #007bff; 
            font-size: 22px; 
            font-weight: bold; 
            margin-bottom: 25px; 
        }

        table { width: 100%; border-collapse: collapse; background: white; margin-top: 10px; }
        thead { background: #343a40; color: white; }
        th, td { padding: 12px; border: 1px solid #dee2e6; text-align: left; }
        
        .msg-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .msg-box { background: white; padding: 20px; border-radius: 5px; min-width: 350px; border: 1px solid #333; }
        .btn-msg { padding: 8px 20px; cursor: pointer; border: 1px solid #ccc; font-weight: bold; margin-top: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <div class="page-title">Quản lý dự án</div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <form method="GET" style="display: flex; gap: 5px;" autocomplete="off">
                        <input type="text" name="search" placeholder="Tìm id hoặc tên..." 
                               value="<?=htmlspecialchars($search)?>" 
                               style="padding: 8px; width: 250px; border: 1px solid #ccc;" 
                               autocomplete="off">
                        <button type="submit" style="background: black; color: white; border: none; padding: 5px 15px; cursor: pointer;">Tìm kiếm</button>
                    </form>
                    <button style="background:#007bff; color:white; border:none; padding:10px 20px; border-radius:5px; cursor: pointer;" onclick="openModal('add')">Thêm dự án</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th width="10%">ID</th>
                            <th width="30%">TÊN DỰ ÁN</th>
                            <th width="30%">ĐỊA ĐIỂM</th>
                            <th width="15%">TRẠNG THÁI</th>
                            <th width="15%" style="text-align: center;">THAO TÁC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = "SELECT * FROM du_an";
                        if($search) $q .= " WHERE id_du_an LIKE '%$search%' OR ten_du_an LIKE '%$search%'";
                        $res = mysqli_query($conn, $q);
                        while($r = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td><?=$r['id_du_an']?></td>
                                <td><?=$r['ten_du_an']?></td>
                                <td><?=$r['dia_diem']?></td>
                                <td><?=$r['trang_thai']?></td>
                                <td align="center">
                                    <button onclick="openModal('edit', '<?=$r['id_du_an']?>', '<?=$r['ten_du_an']?>', '<?=$r['dia_diem']?>', '<?=$r['trang_thai']?>')" style="background:#28a745; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Sửa</button>
                                    <button onclick="askDel('<?=$r['id_du_an']?>')" style="background:#dc3545; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Xóa</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <div id="dataModal" class="msg-overlay">
        <div class="msg-box">
            <h3 id="m_title" style="margin-top:0;">Thông tin dự án</h3>
            <form method="POST">
                <input type="hidden" name="action" id="m_action">
                <input type="hidden" name="id_old" id="m_id_old">
                
                <label><b>ID Dự án:</b></label>
                <input type="text" name="id_du_an" id="m_id" required style="width:100%; padding:8px; margin: 5px 0 10px;">
                
                <label><b>Tên dự án:</b></label>
                <input type="text" name="ten_du_an" id="m_ten" required style="width:100%; padding:8px; margin: 5px 0 10px;">
                
                <label><b>Địa điểm:</b></label>
                <input type="text" name="dia_diem" id="m_dia" style="width:100%; padding:8px; margin: 5px 0 10px;">
                
                <label><b>Trạng thái:</b></label>
                <select name="trang_thai" id="m_trangthai" style="width:100%; padding:8px; margin: 5px 0 10px;">
                    <option value="Đang thi công">Đang thi công</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Tạm dừng">Tạm dừng</option>
                </select>

                <div style="text-align: right; margin-top: 15px;">
                    <button type="button" class="btn-msg" onclick="document.getElementById('dataModal').style.display='none'">Hủy</button>
                    <button type="submit" class="btn-msg" style="background:#007bff; color:white; border:none;">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div id="msgOverlay" class="msg-overlay">
        <div class="msg-box" style="text-align: center;">
            <h4 id="msgT"></h4>
            <p id="msgB"></p>
            <div id="msgBtns"></div>
        </div>
    </div>

    <script>
        function openModal(mode, id='', ten='', dia='', tt='Đang thi công') {
            document.getElementById('m_title').innerText = (mode == 'add') ? 'Thêm mới dự án' : 'Sửa dự án';
            document.getElementById('m_action').value = mode;
            document.getElementById('m_id_old').value = id;
            document.getElementById('m_id').value = id;
            document.getElementById('m_ten').value = ten;
            document.getElementById('m_dia').value = dia;
            document.getElementById('m_trangthai').value = tt;
            document.getElementById('dataModal').style.display = 'flex';
        }

        function showPop(title, body, type = 'success', isConfirm = false, id = '') {
            document.getElementById('msgT').innerText = title;
            document.getElementById('msgB').innerText = body;
            const b = document.getElementById('msgBtns');
            if(isConfirm) {
                b.innerHTML = `<button class="btn-msg" style="background:#dc3545; color:white; border:none;" onclick="location.href='?delete=${id}'">Xóa ngay</button>
                               <button class="btn-msg" onclick="document.getElementById('msgOverlay').style.display='none'">Hủy</button>`;
            } else {
                b.innerHTML = `<button class="btn-msg" onclick="location.href='quanlyduan.php'">Đóng</button>`;
            }
            document.getElementById('msgOverlay').style.display = 'flex';
        }

        function askDel(id) { showPop('Xác nhận', 'Bạn có chắc muốn xóa vĩnh viễn dự án ID ' + id + '?', 'error', true, id); }

        <?php if($msg): ?>
            showPop('<?=$msg_type=='success'?'Thành công':'Lỗi'?>', '<?=$msg?>', '<?=$msg_type?>');
        <?php endif; ?>
    </script>
</body>
</html>
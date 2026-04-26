<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$target_dir_vattu = "images/vattu/";
$msg = ""; $msg_type = "";

/* ==========================================================================
   1. XỬ LÝ DATABASE (THÊM - SỬA - XÓA)
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $ma_vt = mysqli_real_escape_string($conn, trim($_POST['ma_vat_tu']));
    $ten_vt = mysqli_real_escape_string($conn, trim($_POST['ten_vat_tu']));
    $id_dvt = intval($_POST['id_dvt']);
    $id_loai = !empty($_POST['id_loai']) ? intval($_POST['id_loai']) : "NULL";
    $id_hsx = !empty($_POST['id_hsx']) ? intval($_POST['id_hsx']) : "NULL";
    $don_gia = !empty($_POST['don_gia']) ? floatval($_POST['don_gia']) : 0;

    if ($action == 'add_vt') {
        $check = mysqli_query($conn, "SELECT id_vat_tu FROM vat_tu WHERE ma_vat_tu='$ma_vt'");
        if(mysqli_num_rows($check) > 0) {
            $msg = "Lỗi: Mã vật tư đã tồn tại!"; $msg_type = "error";
        } else {
            $sql = "INSERT INTO vat_tu (ma_vat_tu, ten_vat_tu, id_dvt, id_loai_vat_tu, id_hsx, don_gia) 
                    VALUES ('$ma_vt', '$ten_vt', '$id_dvt', $id_loai, $id_hsx, '$don_gia')";
            if(mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                if (isset($_FILES['anh_vat_tu']) && $_FILES['anh_vat_tu']['error'] == 0) {
                    $file_name = $new_id . '.' . pathinfo($_FILES['anh_vat_tu']['name'], PATHINFO_EXTENSION);
                    move_uploaded_file($_FILES['anh_vat_tu']['tmp_name'], $target_dir_vattu . $file_name);
                    mysqli_query($conn, "UPDATE vat_tu SET anh_vat_tu='$file_name' WHERE id_vat_tu='$new_id'");
                }
                $msg = "Thêm vật tư thành công!"; $msg_type = "success";
            }
        }
    } elseif ($action == 'edit_vt') {
        $id_edit = intval($_POST['edit_id']);
        $sql = "UPDATE vat_tu SET ma_vat_tu='$ma_vt', ten_vat_tu='$ten_vt', id_dvt='$id_dvt', 
                id_loai_vat_tu=$id_loai, id_hsx=$id_hsx, don_gia='$don_gia' WHERE id_vat_tu='$id_edit'";
        if(mysqli_query($conn, $sql)) {
            if (isset($_FILES['anh_vat_tu']) && $_FILES['anh_vat_tu']['error'] == 0) {
                $file_name = $id_edit . '.' . pathinfo($_FILES['anh_vat_tu']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['anh_vat_tu']['tmp_name'], $target_dir_vattu . $file_name);
                mysqli_query($conn, "UPDATE vat_tu SET anh_vat_tu='$file_name' WHERE id_vat_tu='$id_edit'");
            }
            $msg = "Cập nhật thành công!"; $msg_type = "success";
        }
    }
}

if (isset($_GET['delete'])) {
    $id_xoa = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM vat_tu WHERE id_vat_tu='$id_xoa'");
    $msg = "Đã xóa vật tư!"; $msg_type = "success";
}

/* ==========================================================================
   2. TRUY VẤN DỮ LIỆU & BỘ LỌC
   ========================================================================== */
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$f_loai = isset($_GET['f_loai']) ? mysqli_real_escape_string($conn, $_GET['f_loai']) : "";
$f_hsx = isset($_GET['f_hsx']) ? mysqli_real_escape_string($conn, $_GET['f_hsx']) : "";
$is_filtered = ($search != "" || $f_loai != "" || $f_hsx != "");

$where = ["1=1"];
if ($search != "") $where[] = "(vt.ma_vat_tu LIKE '%$search%' OR vt.ten_vat_tu LIKE '%$search%')";
if ($f_loai != "") $where[] = "vt.id_loai_vat_tu = '$f_loai'";
if ($f_hsx != "") $where[] = "vt.id_hsx = '$f_hsx'";
$where_sql = implode(" AND ", $where);

$arr_dvt = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM don_vi_tinh ORDER BY ten_don_vi_tinh ASC"), MYSQLI_ASSOC);
$arr_loai = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM loai_vat_tu ORDER BY ten_loai_vat_tu ASC"), MYSQLI_ASSOC);
$arr_hsx = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM hang_san_xuat ORDER BY ten_hang_san_xuat ASC"), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý vật tư - Thịnh Tiến</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .page-title { color: #007bff; font-size: 22px; font-weight: bold; margin-bottom: 25px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .filter-group { display: flex; gap: 8px; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #ddd; align-items: center; }
        .filter-group input, .filter-group select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; }
        .data-table th { background: #343a40; color: white; padding: 12px; border: 1px solid #dee2e6; font-size: 14px; }
        .data-table td { padding: 10px; border: 1px solid #dee2e6; font-size: 14px; }
        .img-thumbnail { width: 40px; height: 40px; object-fit: contain; border: 1px solid #eee; cursor: pointer; }

        .btn-add { background: #28a745; color: white; padding: 10px 18px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-search { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-back { background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; font-size: 13px; }
        .btn-act { padding: 5px 10px; border: none; border-radius: 3px; color: white; cursor: pointer; font-size: 12px; font-weight: bold; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-box { background: white; padding: 25px; border-radius: 8px; width: 600px; }
        
        #msgOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; }
        #msgBox { background:white; padding:30px; border-radius:10px; width:400px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.2); }
        .success-c { color: #28a745; font-size: 22px; margin-bottom: 10px; font-weight: bold; }
        .error-c { color: #dc3545; font-size: 22px; margin-bottom: 10px; font-weight: bold; }
        .btn-msg { margin-top: 20px; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <main style="padding: 20px;">
                <div class="page-title">Quản lý Chi tiết Vật tư</div>

                <div class="toolbar">
                    <form class="filter-group" method="GET" onsubmit="return validateSearch()">
                        <input type="text" id="inputSearch" name="search" placeholder="Mã hoặc tên..." value="<?=htmlspecialchars($search)?>">
                        <select id="selectLoai" name="f_loai">
                            <option value="">-- Loại VT --</option>
                            <?php foreach($arr_loai as $l) echo "<option value='{$l['id_loai_vat_tu']}' ".($f_loai==$l['id_loai_vat_tu']?'selected':'').">{$l['ten_loai_vat_tu']}</option>"; ?>
                        </select>
                        <select id="selectHsx" name="f_hsx">
                            <option value="">-- Hãng SX --</option>
                            <?php foreach($arr_hsx as $h) echo "<option value='{$h['id_hsx']}' ".($f_hsx==$h['id_hsx']?'selected':'').">{$h['ten_hang_san_xuat']}</option>"; ?>
                        </select>
                        <button type="submit" class="btn-search">Tìm kiếm</button>
                        <?php if($is_filtered): ?>
                            <a href="qldm_chitietvattu.php" class="btn-back">Quay lại danh sách</a>
                        <?php endif; ?>
                    </form>
                    <button class="btn-add" onclick="moModal('add')">+ Thêm vật tư mới</button>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Mã VT</th>
                            <th>Tên Vật tư</th>
                            <th>ĐVT</th>
                            <th>Loại vật tư</th> <th>Hãng sản xuất</th> <th>Giá</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT vt.*, d.ten_don_vi_tinh, l.ten_loai_vat_tu, h.ten_hang_san_xuat 
                                FROM vat_tu vt 
                                JOIN don_vi_tinh d ON vt.id_dvt = d.id_dvt 
                                LEFT JOIN loai_vat_tu l ON vt.id_loai_vat_tu = l.id_loai_vat_tu
                                LEFT JOIN hang_san_xuat h ON vt.id_hsx = h.id_hsx
                                WHERE $where_sql ORDER BY vt.id_vat_tu DESC";
                        $res = mysqli_query($conn, $sql);
                        while($r = mysqli_fetch_assoc($res)):
                            $img = (!empty($r['anh_vat_tu'])) ? $target_dir_vattu . $r['anh_vat_tu'] : '';
                        ?>
                        <tr>
                            <td align="center"><?=$r['id_vat_tu']?></td>
                            <td align="center"><?php if($img) echo "<img src='$img' class='img-thumbnail' onclick='zoom(this.src)'>"; ?></td>
                            <td align="center"><b><?=$r['ma_vat_tu']?></b></td>
                            <td><?=$r['ten_vat_tu']?></td>
                            <td align="center"><?=$r['ten_don_vi_tinh']?></td>
                            <td><?=$r['ten_loai_vat_tu']?></td> <td><?=$r['ten_hang_san_xuat']?></td> <td align="right" style="color:green; font-weight:bold;"><?=number_format($r['don_gia'],0,',','.')?></td>
                            <td align="center">
                                <button class="btn-act" style="background:#ffc107; color:#222;" onclick='moModal("edit", <?=json_encode($r)?>)'>Sửa</button>
                                <button class="btn-act" style="background:#dc3545;" onclick="askDel(<?=$r['id_vat_tu']?>, '<?=$r['ma_vat_tu']?>')">Xóa</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div id="dataModal" class="modal-overlay">
        <div class="modal-box">
            <h3 id="modalTitle">Thông tin vật tư</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="mAction">
                <input type="hidden" name="edit_id" id="mId">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div><label>Mã VT *</label><input type="text" name="ma_vat_tu" id="mMa" required style="width:100%; padding:8px;"></div>
                    <div><label>Tên VT *</label><input type="text" name="ten_vat_tu" id="mTen" required style="width:100%; padding:8px;"></div>
                    <div><label>ĐVT *</label>
                        <select name="id_dvt" id="mDvt" style="width:100%; padding:8px;">
                            <?php foreach($arr_dvt as $d) echo "<option value='{$d['id_dvt']}'>{$d['ten_don_vi_tinh']}</option>"; ?>
                        </select>
                    </div>
                    <div><label>Giá</label><input type="number" name="don_gia" id="mGia" style="width:100%; padding:8px;"></div>
                    <div><label>Loại VT</label>
                        <select name="id_loai" id="mLoai" style="width:100%; padding:8px;">
                            <option value="">-- Chọn loại --</option>
                            <?php foreach($arr_loai as $l) echo "<option value='{$l['id_loai_vat_tu']}'>{$l['ten_loai_vat_tu']}</option>"; ?>
                        </select>
                    </div>
                    <div><label>Hãng SX</label>
                        <select name="id_hsx" id="mHsx" style="width:100%; padding:8px;">
                            <option value="">-- Chọn hãng --</option>
                            <?php foreach($arr_hsx as $h) echo "<option value='{$h['id_hsx']}'>{$h['ten_hang_san_xuat']}</option>"; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-top:10px;"><label>Ảnh vật tư</label><input type="file" name="anh_vat_tu" style="margin-top:5px;"></div>
                <div style="text-align:right; margin-top:20px;">
                    <button type="button" onclick="document.getElementById('dataModal').style.display='none'" style="padding:10px 20px;">Hủy bỏ</button>
                    <button type="submit" class="btn-add">Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>

    <div id="msgOverlay">
        <div id="msgBox">
            <div id="msgT"></div>
            <div id="msgB" style="margin-bottom:15px; color:#555;"></div>
            <div id="msgBtns"></div>
        </div>
    </div>

    <div id="zOverlay" class="modal-overlay" onclick="this.style.display='none'"><img id="zImg" src="" style="max-width:90%; border:5px solid #fff;"></div>

    <script>
        function showPop(title, body, type = 'success', isConfirm = false, id = '') {
            document.getElementById('msgT').innerText = title;
            document.getElementById('msgT').className = (type === 'success' ? 'success-c' : 'error-c');
            document.getElementById('msgB').innerText = body;
            const b = document.getElementById('msgBtns');
            if(isConfirm) {
                b.innerHTML = `<button class="btn-msg" style="background:#dc3545; color:white; border:none; margin-right:10px;" onclick="location.href='?delete=${id}'">Xác nhận xóa</button>
                               <button class="btn-msg" onclick="document.getElementById('msgOverlay').style.display='none'">Hủy</button>`;
            } else {
                b.innerHTML = `<button class="btn-msg" style="background:#333; color:white; border:none;" onclick="document.getElementById('msgOverlay').style.display='none'">Đóng</button>`;
            }
            document.getElementById('msgOverlay').style.display = 'flex';
        }

        function validateSearch() {
            if (!document.getElementById("inputSearch").value.trim() && 
                !document.getElementById("selectLoai").value && 
                !document.getElementById("selectHsx").value) {
                showPop('Thông báo', 'Vui lòng nhập từ khóa hoặc chọn bộ lọc!', 'error');
                return false;
            }
            return true;
        }

        function askDel(id, ma) { showPop('Xác nhận xóa', 'Bạn chắc chắn muốn xóa vật tư mã: ' + ma + '?', 'error', true, id); }

        function moModal(mode, data = null) {
            document.getElementById('mAction').value = (mode === 'add' ? 'add_vt' : 'edit_vt');
            document.getElementById('modalTitle').innerText = (mode === 'add' ? 'Thêm vật tư mới' : 'Cập nhật vật tư');
            if (data) {
                document.getElementById('mId').value = data.id_vat_tu;
                document.getElementById('mMa').value = data.ma_vat_tu;
                document.getElementById('mTen').value = data.ten_vat_tu;
                document.getElementById('mDvt').value = data.id_dvt;
                document.getElementById('mGia').value = data.don_gia;
                document.getElementById('mLoai').value = data.id_loai_vat_tu || "";
                document.getElementById('mHsx').value = data.id_hsx || "";
            } else {
                document.getElementById('mMa').value = ""; document.getElementById('mTen').value = ""; 
                document.getElementById('mGia').value = ""; document.getElementById('mLoai').value = ""; document.getElementById('mHsx').value = "";
            }
            document.getElementById('dataModal').style.display = 'flex';
        }

        function zoom(src) { document.getElementById('zImg').src = src; document.getElementById('zOverlay').style.display = 'flex'; }

        <?php if($msg): ?>
            showPop('<?=$msg_type=='success'?'Thành công':'Lỗi'?>', '<?=$msg?>', '<?=$msg_type?>');
        <?php endif; ?>
    </script>
</body>
</html>
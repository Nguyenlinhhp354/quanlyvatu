<?php
session_start();
include 'db_connect.php';

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$target_dir_vattu = "images/vattu/";

// ==========================================
// 1. XỬ LÝ THÊM VẬT TƯ MỚI
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_vt') {
    $ma_vt = mysqli_real_escape_string($conn, trim($_POST['ma_vat_tu']));
    $ten_vt = mysqli_real_escape_string($conn, trim($_POST['ten_vat_tu']));
    $id_dvt = intval($_POST['id_dvt']);
    
    // Xử lý các trường cho phép NULL
    $id_loai = !empty($_POST['id_loai']) ? intval($_POST['id_loai']) : "NULL";
    $id_hsx = !empty($_POST['id_hsx']) ? intval($_POST['id_hsx']) : "NULL";
    $don_gia = !empty($_POST['don_gia']) ? floatval($_POST['don_gia']) : 0;
    $mo_ta = !empty($_POST['mo_ta']) ? "'" . mysqli_real_escape_string($conn, trim($_POST['mo_ta'])) . "'" : "NULL";

    // Bắt lỗi trùng mã vật tư
    $check_sql = "SELECT id_vat_tu FROM vat_tu WHERE ma_vat_tu='$ma_vt'";
    if(mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
        echo "<script>alert('Lỗi: Mã vật tư này đã tồn tại!'); window.location.href='qldm_chitietvattu.php';</script>";
    } else {
        $sql_insert = "INSERT INTO vat_tu (ma_vat_tu, ten_vat_tu, id_dvt, id_loai_vat_tu, mo_ta, id_hsx, don_gia) 
                       VALUES ('$ma_vt', '$ten_vt', '$id_dvt', $id_loai, $mo_ta, $id_hsx, '$don_gia')";
        
        if (mysqli_query($conn, $sql_insert)) {
            $new_id = mysqli_insert_id($conn);
            
            // XỬ LÝ UPLOAD ẢNH
            if (isset($_FILES['anh_vat_tu']) && $_FILES['anh_vat_tu']['error'] == 0) {
                $ext = pathinfo($_FILES['anh_vat_tu']['name'], PATHINFO_EXTENSION);
                $file_name = $new_id . '.' . $ext;
                if (move_uploaded_file($_FILES['anh_vat_tu']['tmp_name'], $target_dir_vattu . $file_name)) {
                    mysqli_query($conn, "UPDATE vat_tu SET anh_vat_tu='$file_name' WHERE id_vat_tu='$new_id'");
                }
            }
            echo "<script>alert('Thêm vật tư thành công!'); window.location.href='qldm_chitietvattu.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể thêm vật tư!');</script>";
        }
    }
}

// ==========================================
// 2. XỬ LÝ SỬA VẬT TƯ
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_vt') {
    $id_vt = intval($_POST['edit_id']);
    $ma_vt = mysqli_real_escape_string($conn, trim($_POST['edit_ma_vat_tu']));
    $ten_vt = mysqli_real_escape_string($conn, trim($_POST['edit_ten_vat_tu']));
    $id_dvt = intval($_POST['edit_id_dvt']);
    
    $id_loai = !empty($_POST['edit_id_loai']) ? intval($_POST['edit_id_loai']) : "NULL";
    $id_hsx = !empty($_POST['edit_id_hsx']) ? intval($_POST['edit_id_hsx']) : "NULL";
    $don_gia = !empty($_POST['edit_don_gia']) ? floatval($_POST['edit_don_gia']) : 0;
    $mo_ta = !empty($_POST['edit_mo_ta']) ? "'" . mysqli_real_escape_string($conn, trim($_POST['edit_mo_ta'])) . "'" : "NULL";

    // Kiểm tra trùng mã (Loại trừ chính nó)
    $check_sql = "SELECT id_vat_tu FROM vat_tu WHERE ma_vat_tu='$ma_vt' AND id_vat_tu != '$id_vt'";
    if(mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
        echo "<script>alert('Lỗi: Mã vật tư bị trùng với một vật tư khác!'); window.location.href='qldm_chitietvattu.php';</script>";
    } else {
        $sql_update = "UPDATE vat_tu SET 
                        ma_vat_tu='$ma_vt', ten_vat_tu='$ten_vt', id_dvt='$id_dvt', 
                        id_loai_vat_tu=$id_loai, mo_ta=$mo_ta, id_hsx=$id_hsx, don_gia='$don_gia' 
                       WHERE id_vat_tu='$id_vt'";
        
        if (mysqli_query($conn, $sql_update)) {
            // XỬ LÝ NẾU UPLOAD ẢNH MỚI
            if (isset($_FILES['edit_anh_vat_tu']) && $_FILES['edit_anh_vat_tu']['error'] == 0) {
                $old_img_query = mysqli_query($conn, "SELECT anh_vat_tu FROM vat_tu WHERE id_vat_tu='$id_vt'");
                $old_img = mysqli_fetch_assoc($old_img_query)['anh_vat_tu'];
                
                if (!empty($old_img) && file_exists($target_dir_vattu . $old_img)) {
                    unlink($target_dir_vattu . $old_img); // Xóa ảnh cũ
                }
                
                $ext = pathinfo($_FILES['edit_anh_vat_tu']['name'], PATHINFO_EXTENSION);
                $file_name = $id_vt . '.' . $ext;
                if (move_uploaded_file($_FILES['edit_anh_vat_tu']['tmp_name'], $target_dir_vattu . $file_name)) {
                    mysqli_query($conn, "UPDATE vat_tu SET anh_vat_tu='$file_name' WHERE id_vat_tu='$id_vt'");
                }
            }
            echo "<script>alert('Cập nhật vật tư thành công!'); window.location.href='qldm_chitietvattu.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể cập nhật!');</script>";
        }
    }
}

// ==========================================
// 3. XỬ LÝ XÓA VẬT TƯ
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    
    // Lấy tên ảnh để xóa file
    $img_query = mysqli_query($conn, "SELECT anh_vat_tu FROM vat_tu WHERE id_vat_tu='$id_xoa'");
    $img_name = mysqli_fetch_assoc($img_query)['anh_vat_tu'];

    if (mysqli_query($conn, "DELETE FROM vat_tu WHERE id_vat_tu='$id_xoa'")) {
        // Xóa file ảnh thành công
        if (!empty($img_name) && file_exists($target_dir_vattu . $img_name)) {
            unlink($target_dir_vattu . $img_name);
        }
        echo "<script>alert('Đã xóa vật tư thành công!'); window.location.href='qldm_chitietvattu.php';</script>";
    } else {
        // Bắt lỗi khóa ngoại (VD: Vật tư đã tồn tại trong kho hoặc có phiếu nhập/xuất)
        if (mysqli_errno($conn) == 1451) {
            echo "<script>alert('LỖI: Không thể xóa vật tư này vì nó đang tồn tại trong hệ thống (Tồn kho, Phiếu kiểm kê,...). Hãy xóa dữ liệu liên quan trước!'); window.location.href='qldm_chitietvattu.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể xóa vật tư này!'); window.location.href='qldm_chitietvattu.php';</script>";
        }
    }
}

// ==========================================
// 4. BỘ LỌC TÌM KIẾM
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$f_dvt = isset($_GET['f_dvt']) ? mysqli_real_escape_string($conn, $_GET['f_dvt']) : "";
$f_loai = isset($_GET['f_loai']) ? mysqli_real_escape_string($conn, $_GET['f_loai']) : "";
$f_hsx = isset($_GET['f_hsx']) ? mysqli_real_escape_string($conn, $_GET['f_hsx']) : "";

$where = ["1=1"];
if ($search != "") $where[] = "(vt.ma_vat_tu LIKE '%$search%' OR vt.ten_vat_tu LIKE '%$search%')";
if ($f_dvt != "") $where[] = "vt.id_dvt = '$f_dvt'";
if ($f_loai != "") $where[] = "vt.id_loai_vat_tu = '$f_loai'";
if ($f_hsx != "") $where[] = "vt.id_hsx = '$f_hsx'";
$where_sql = implode(" AND ", $where);

// LẤY DỮ LIỆU ĐỔ VÀO DROPDOWN
$list_dvt = mysqli_query($conn, "SELECT * FROM don_vi_tinh ORDER BY ten_don_vi_tinh ASC");
$list_loai = mysqli_query($conn, "SELECT * FROM loai_vat_tu ORDER BY ten_loai_vat_tu ASC");
$list_hsx = mysqli_query($conn, "SELECT * FROM hang_san_xuat ORDER BY ten_hang_san_xuat ASC");

// Chuyển mảng để tái sử dụng ở Modal Sửa
$arr_dvt = []; while($row = mysqli_fetch_assoc($list_dvt)) $arr_dvt[] = $row;
$arr_loai = []; while($row = mysqli_fetch_assoc($list_loai)) $arr_loai[] = $row;
$arr_hsx = []; while($row = mysqli_fetch_assoc($list_hsx)) $arr_hsx[] = $row;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục Chi tiết Vật tư - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-add { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;}
        .btn-add:hover { background-color: #218838; }
        
        .filter-wrapper { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px;}
        .filter-wrapper input, .filter-wrapper select { padding: 8px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 10px; border: 1px solid #e0e0e0; vertical-align: middle; }
        .data-table thead th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 13px; text-align: center;}
        
        .img-thumbnail { width: 60px; height: 60px; object-fit: contain; border-radius: 4px; border: 1px solid #ccc; background: #f8f9fa;}
        .img-thumbnail:hover { transform: scale(1.5); transition: 0.2s; box-shadow: 0 4px 8px rgba(0,0,0,0.2); cursor: pointer;}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <div class="toolbar">
                    <h2 class="page-title" style="margin: 0; color: #007bff;">Quản lý Chi tiết Vật tư</h2>
                    <button class="btn-add" onclick="moModalThem()">+ Thêm Vật tư mới</button>
                </div>

                <form class="filter-wrapper" method="GET" action="qldm_chitietvattu.php">
                    <input type="text" name="search" placeholder="Nhập mã hoặc tên vật tư..." value="<?=htmlspecialchars($search)?>" style="width: 220px;">
                    
                    <select name="f_dvt">
                        <option value="">-- Lọc theo ĐVT --</option>
                        <?php foreach($arr_dvt as $d) echo "<option value='{$d['id_dvt']}' ".($f_dvt==$d['id_dvt']?'selected':'').">{$d['ten_don_vi_tinh']}</option>"; ?>
                    </select>

                    <select name="f_loai">
                        <option value="">-- Lọc theo Loại VT --</option>
                        <?php foreach($arr_loai as $l) echo "<option value='{$l['id_loai_vat_tu']}' ".($f_loai==$l['id_loai_vat_tu']?'selected':'').">{$l['ten_loai_vat_tu']}</option>"; ?>
                    </select>

                    <select name="f_hsx">
                        <option value="">-- Lọc theo Hãng SX --</option>
                        <?php foreach($arr_hsx as $h) echo "<option value='{$h['id_hsx']}' ".($f_hsx==$h['id_hsx']?'selected':'').">{$h['ten_hang_san_xuat']}</option>"; ?>
                    </select>

                    <button type="submit" class="btn-search">Lọc kết quả</button>
                    <?php if($search!="" || $f_dvt!="" || $f_loai!="" || $f_hsx!=""): ?>
                        <a href="qldm_chitietvattu.php" class="btn-clear">Xóa lọc</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="8%">Hình ảnh</th>
                                <th width="12%">Mã VT</th>
                                <th width="20%">Tên Vật tư</th>
                                <th width="8%">ĐVT</th>
                                <th width="15%">Phân loại</th>
                                <th width="12%">Hãng SX</th>
                                <th width="10%">Đơn giá (đ)</th>
                                <th width="10%">Hành động</th>
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
                            $result = mysqli_query($conn, $sql);

                            if(mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $gia = ($row['don_gia'] > 0) ? number_format($row['don_gia'], 0, ',', '.') : "-";
                                    $loai = $row['ten_loai_vat_tu'] ? $row['ten_loai_vat_tu'] : "<i>(Chưa phân loại)</i>";
                                    $hang = $row['ten_hang_san_xuat'] ? $row['ten_hang_san_xuat'] : "<i>(Không)</i>";
                                    
                                    echo "<tr>";
                                    echo "<td class='text-center'>{$row['id_vat_tu']}</td>";
                                    
                                    echo "<td class='text-center'>";
                                    if(!empty($row['anh_vat_tu']) && file_exists($target_dir_vattu . $row['anh_vat_tu'])) {
                                        echo "<a href='{$target_dir_vattu}{$row['anh_vat_tu']}' target='_blank'><img src='{$target_dir_vattu}{$row['anh_vat_tu']}' class='img-thumbnail'></a>";
                                    } else {
                                        echo "<span style='color:#ccc; font-size:11px;'>No Image</span>";
                                    }
                                    echo "</td>";

                                    echo "<td class='text-center' style='font-weight:bold;'>{$row['ma_vat_tu']}</td>";
                                    echo "<td>{$row['ten_vat_tu']}";
                                    if(!empty($row['mo_ta'])) echo "<br><small style='color:#888;'>{$row['mo_ta']}</small>";
                                    echo "</td>";
                                    
                                    echo "<td class='text-center'>{$row['ten_don_vi_tinh']}</td>";
                                    echo "<td>{$loai}</td>";
                                    echo "<td class='text-center'>{$hang}</td>";
                                    echo "<td class='text-right' style='color:#28a745; font-weight:bold;'>{$gia}</td>";
                                    
                                    // PREPARE DATA FOR EDIT MODAL
                                    $imgPath = (!empty($row['anh_vat_tu'])) ? $target_dir_vattu . $row['anh_vat_tu'] : '';
                                    $mota_clean = htmlspecialchars($row['mo_ta'], ENT_QUOTES);
                                    
                                    echo "<td class='text-center'>
                                            <a href='javascript:void(0)' class='btn-action btn-edit' style='padding:5px 10px; margin-bottom:5px; display:inline-block;' 
                                            onclick='moModalSua({$row['id_vat_tu']}, \"{$row['ma_vat_tu']}\", \"".htmlspecialchars($row['ten_vat_tu'], ENT_QUOTES)."\", {$row['id_dvt']}, \"{$row['id_loai_vat_tu']}\", \"{$row['id_hsx']}\", \"{$row['don_gia']}\", \"{$mota_clean}\", \"{$imgPath}\")'>Sửa</a>
                                            
                                            <a href='qldm_chitietvattu.php?action=delete&id={$row['id_vat_tu']}' class='btn-action btn-delete' style='padding:5px 10px; display:inline-block;'
                                            onclick='return confirm(\"Xóa vật tư này sẽ xóa luôn hình ảnh đính kèm (nếu có). Bạn có chắc chắn?\");'>Xóa</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center' style='padding: 20px;'>Không tìm thấy vật tư nào!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div id="addModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-box" style="background: white; padding: 25px; border-radius: 8px; width: 600px; max-height: 90vh; overflow-y: auto;">
            <h3 style="color: #28a745; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Thêm Vật tư mới</h3>
            <form method="POST" action="qldm_chitietvattu.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_vt">
                
                <div class="form-grid">
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Mã vật tư: <span style="color:red">*</span></label>
                        <input type="text" name="ma_vat_tu" required placeholder="VD: THEP-D10" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Tên vật tư: <span style="color:red">*</span></label>
                        <input type="text" name="ten_vat_tu" required placeholder="VD: Thép cuộn D10" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Đơn vị tính: <span style="color:red">*</span></label>
                        <select name="id_dvt" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <?php foreach($arr_dvt as $d) echo "<option value='{$d['id_dvt']}'>{$d['ten_don_vi_tinh']}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Đơn giá chuẩn (VNĐ):</label>
                        <input type="number" name="don_gia" min="0" placeholder="0" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Loại vật tư (Tùy chọn):</label>
                        <select name="id_loai" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Chọn loại --</option>
                            <?php foreach($arr_loai as $l) echo "<option value='{$l['id_loai_vat_tu']}'>{$l['ten_loai_vat_tu']}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Hãng sản xuất (Tùy chọn):</label>
                        <select name="id_hsx" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Chọn hãng --</option>
                            <?php foreach($arr_hsx as $h) echo "<option value='{$h['id_hsx']}'>{$h['ten_hang_san_xuat']}</option>"; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Mô tả thêm:</label>
                    <textarea name="mo_ta" rows="2" placeholder="Thông số kỹ thuật, quy cách..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing:border-box;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Hình ảnh vật tư (Tùy chọn):</label>
                    <input type="file" name="anh_vat_tu" accept="image/*" style="width: 100%; padding: 5px; border: 1px dashed #ccc; border-radius: 4px; box-sizing:border-box;">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="dongModalThem()" style="padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Hủy bỏ</button>
                    <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Lưu Vật Tư</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-box" style="background: white; padding: 25px; border-radius: 8px; width: 600px; max-height: 90vh; overflow-y: auto;">
            <h3 style="color: #007bff; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Cập nhật Thông tin Vật tư</h3>
            <form method="POST" action="qldm_chitietvattu.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_vt">
                <input type="hidden" name="edit_id" id="modal_edit_id"> 
                
                <div class="form-grid">
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Mã vật tư: <span style="color:red">*</span></label>
                        <input type="text" name="edit_ma_vat_tu" id="modal_ma" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Tên vật tư: <span style="color:red">*</span></label>
                        <input type="text" name="edit_ten_vat_tu" id="modal_ten" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Đơn vị tính: <span style="color:red">*</span></label>
                        <select name="edit_id_dvt" id="modal_dvt" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <?php foreach($arr_dvt as $d) echo "<option value='{$d['id_dvt']}'>{$d['ten_don_vi_tinh']}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Đơn giá chuẩn (VNĐ):</label>
                        <input type="number" name="edit_don_gia" id="modal_gia" min="0" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Loại vật tư:</label>
                        <select name="edit_id_loai" id="modal_loai" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Trống --</option>
                            <?php foreach($arr_loai as $l) echo "<option value='{$l['id_loai_vat_tu']}'>{$l['ten_loai_vat_tu']}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Hãng sản xuất:</label>
                        <select name="edit_id_hsx" id="modal_hsx" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- Trống --</option>
                            <?php foreach($arr_hsx as $h) echo "<option value='{$h['id_hsx']}'>{$h['ten_hang_san_xuat']}</option>"; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Mô tả thêm:</label>
                    <textarea name="edit_mo_ta" id="modal_mota" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing:border-box;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Đổi Hình ảnh mới:</label>
                    <div id="modal_current_img_wrapper" style="margin-bottom: 10px; display: none; display:flex; align-items:center; gap: 10px;">
                        <img id="modal_current_img" src="" style="width: 60px; height: 60px; object-fit: contain; border: 1px solid #ccc; border-radius: 4px; background:#f8f9fa;">
                        <span style="font-size: 12px; color: #666;">(Ảnh hiện tại. Bỏ trống ô dưới nếu muốn giữ nguyên)</span>
                    </div>
                    <input type="file" name="edit_anh_vat_tu" accept="image/*" style="width: 100%; padding: 5px; border: 1px dashed #ccc; border-radius: 4px; box-sizing:border-box;">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="dongModalSua()" style="padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Hủy bỏ</button>
                    <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Lưu Thay Đổi</button>
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

        function moModalSua(id, ma, ten, dvt, loai, hsx, gia, mota, imgPath) {
            document.getElementById("modal_edit_id").value = id;
            document.getElementById("modal_ma").value = ma;
            document.getElementById("modal_ten").value = ten;
            document.getElementById("modal_dvt").value = dvt;
            document.getElementById("modal_loai").value = loai;
            document.getElementById("modal_hsx").value = hsx;
            document.getElementById("modal_gia").value = gia;
            document.getElementById("modal_mota").value = mota;
            
            var imgWrapper = document.getElementById("modal_current_img_wrapper");
            var imgTag = document.getElementById("modal_current_img");
            
            if(imgPath !== "") {
                imgTag.src = imgPath;
                imgWrapper.style.display = "flex";
            } else {
                imgWrapper.style.display = "none";
            }
            
            document.getElementById("editModal").style.display = "flex";
        }
        function dongModalSua() {
            document.getElementById("editModal").style.display = "none";
        }
    </script>
</body>
</html>
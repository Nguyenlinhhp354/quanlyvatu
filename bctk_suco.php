<?php
session_start();
include 'db_connect.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 
$sql_user = "SELECT ho_ten FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$ho_ten = ($row_user = mysqli_fetch_assoc(mysqli_query($conn, $sql_user))) ? $row_user['ho_ten'] : "Admin";

$target_dir_suco = "images/suco/";

// ==========================================
// 2. XỬ LÝ THÊM BÁO CÁO SỰ CỐ
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_suco') {
    $id_vat_tu = intval($_POST['id_vat_tu']);
    $id_kho = intval($_POST['id_kho']);
    $sl_hao_hut = intval($_POST['so_luong_hao_hut']);
    $nguyen_nhan = mysqli_real_escape_string($conn, trim($_POST['nguyen_nhan']));

    if ($sl_hao_hut <= 0) {
        echo "<script>alert('Lỗi: Số lượng hao hụt phải lớn hơn 0!'); window.location.href='bctk_suco.php';</script>";
    } else {
        $sql_insert = "INSERT INTO bao_cao_su_co (id_vat_tu, id_kho, so_luong_hao_hut, nguyen_nhan, trang_thai_duyet, id_nguoi_lap, ngay_lap) 
                       VALUES ('$id_vat_tu', '$id_kho', '$sl_hao_hut', '$nguyen_nhan', 'Chờ duyệt', '$id_nguoi_dung', NOW())";
        
        if (mysqli_query($conn, $sql_insert)) {
            $new_id = mysqli_insert_id($conn); 
            
            if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
                $ext = pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION);
                $file_name = $new_id . '.' . $ext;
                if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_dir_suco . $file_name)) {
                    mysqli_query($conn, "UPDATE bao_cao_su_co SET hinh_anh='$file_name' WHERE id_bao_cao='$new_id'");
                }
            }
            echo "<script>alert('Tạo báo cáo sự cố thành công!'); window.location.href='bctk_suco.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể lưu báo cáo!');</script>";
        }
    }
}

// ==========================================
// 3. XỬ LÝ SỬA BÁO CÁO
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_suco') {
    $id_bao_cao = intval($_POST['edit_id']);
    $sl_hao_hut = intval($_POST['edit_sl_hao_hut']);
    $nguyen_nhan = mysqli_real_escape_string($conn, trim($_POST['edit_nguyen_nhan']));

    $check_sql = "SELECT trang_thai_duyet, hinh_anh FROM bao_cao_su_co WHERE id_bao_cao='$id_bao_cao'";
    $check_res = mysqli_fetch_assoc(mysqli_query($conn, $check_sql));

    if ($check_res['trang_thai_duyet'] != 'Chờ duyệt') {
        echo "<script>alert('Cảnh báo: Không thể sửa báo cáo đã được xử lý!'); window.location.href='bctk_suco.php';</script>";
    } else {
        $sql_update = "UPDATE bao_cao_su_co SET so_luong_hao_hut='$sl_hao_hut', nguyen_nhan='$nguyen_nhan' WHERE id_bao_cao='$id_bao_cao'";
        
        if (mysqli_query($conn, $sql_update)) {
            if (isset($_FILES['edit_hinh_anh']) && $_FILES['edit_hinh_anh']['error'] == 0) {
                if (!empty($check_res['hinh_anh']) && file_exists($target_dir_suco . $check_res['hinh_anh'])) {
                    unlink($target_dir_suco . $check_res['hinh_anh']);
                }
                
                $ext = pathinfo($_FILES['edit_hinh_anh']['name'], PATHINFO_EXTENSION);
                $file_name = $id_bao_cao . '.' . $ext;
                
                if (move_uploaded_file($_FILES['edit_hinh_anh']['tmp_name'], $target_dir_suco . $file_name)) {
                    mysqli_query($conn, "UPDATE bao_cao_su_co SET hinh_anh='$file_name' WHERE id_bao_cao='$id_bao_cao'");
                }
            }
            echo "<script>alert('Cập nhật báo cáo thành công!'); window.location.href='bctk_suco.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể cập nhật!');</script>";
        }
    }
}

// ==========================================
// 4. XỬ LÝ XÓA BÁO CÁO
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    
    $check_sql = "SELECT trang_thai_duyet, hinh_anh FROM bao_cao_su_co WHERE id_bao_cao='$id_xoa'";
    $check_res = mysqli_fetch_assoc(mysqli_query($conn, $check_sql));

    if ($check_res['trang_thai_duyet'] != 'Chờ duyệt') {
        echo "<script>alert('Cảnh báo: Không thể xóa báo cáo đã được duyệt/từ chối!'); window.location.href='bctk_suco.php';</script>";
    } else {
        if (!empty($check_res['hinh_anh']) && file_exists($target_dir_suco . $check_res['hinh_anh'])) {
            unlink($target_dir_suco . $check_res['hinh_anh']);
        }
        
        if (mysqli_query($conn, "DELETE FROM bao_cao_su_co WHERE id_bao_cao='$id_xoa'")) {
            echo "<script>alert('Đã xóa báo cáo sự cố và hình ảnh đính kèm!'); window.location.href='bctk_suco.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể xóa báo cáo này!');</script>";
        }
    }
}

// ==========================================
// 5. XỬ LÝ LỌC & TÌM KIẾM
// ==========================================
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, trim($_GET['status'])) : "";

$where_conditions = ["1=1"];

if ($search_query != "") {
    // Tìm theo mã vật tư, tên vật tư hoặc nguyên nhân
    $where_conditions[] = "(vt.ma_vat_tu LIKE '%$search_query%' OR vt.ten_vat_tu LIKE '%$search_query%' OR bc.nguyen_nhan LIKE '%$search_query%')";
}
if ($filter_status != "") {
    $where_conditions[] = "bc.trang_thai_duyet = '$filter_status'";
}
$where_sql = implode(" AND ", $where_conditions);

// LẤY DỮ LIỆU ĐỔ VÀO DROPDOWN CHO FORM THÊM
$list_vattu = mysqli_query($conn, "SELECT id_vat_tu, ma_vat_tu, ten_vat_tu FROM vat_tu ORDER BY ten_vat_tu ASC");
$list_kho = mysqli_query($conn, "SELECT id_kho, ten_kho FROM kho ORDER BY ten_kho ASC");

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo Sự cố - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .btn-add { background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none;}
        .btn-add:hover { background-color: #c82333; }
        
        .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 10px; border: 1px solid #e0e0e0; vertical-align: middle; }
        .data-table thead th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 13px; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; display: inline-block;}
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-approved { background-color: #28a745; }
        .badge-rejected { background-color: #dc3545; }
        
        .badge-date { background: #e9ecef; padding: 5px 10px; border-radius: 20px; font-size: 12px; color: #495057; font-weight: 600; display: inline-block; white-space: nowrap;}
        
        .img-thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc; cursor: pointer;}
        .img-thumbnail:hover { transform: scale(1.5); transition: 0.2s; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }

        /* Form Filter */
        .filter-wrapper { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;}
        .filter-form { display: flex; gap: 10px; align-items: center; background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .filter-form input, .filter-form select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .filter-form input:focus, .filter-form select:focus { border-color: #007bff; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                <div class="toolbar">
                    <h2 class="page-title" style="margin: 0; color: #dc3545;">Báo cáo chi tiết vật tư sự cố</h2>
                    <a href="bctk_index.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">&laquo; Quay lại Báo cáo</a>
                </div>

                <div class="filter-wrapper">
                    <button class="btn-add" onclick="moModalThem()" style="margin: 0;">+ Ghi nhận Sự cố / Hỏng hóc</button>

                    <form class="filter-form" method="GET" action="bctk_suco.php">
                        <input type="text" name="search" placeholder="Nhập mã, tên vật tư, nguyên nhân..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                        
                        <select name="status">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="Chờ duyệt" <?php if($filter_status == 'Chờ duyệt') echo 'selected'; ?>>Chờ duyệt</option>
                            <option value="Đã duyệt" <?php if($filter_status == 'Đã duyệt') echo 'selected'; ?>>Đã duyệt</option>
                            <option value="Từ chối" <?php if($filter_status == 'Từ chối') echo 'selected'; ?>>Từ chối</option>
                        </select>

                        <button type="submit" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Lọc</button>
                        
                        <?php if($search_query != "" || $filter_status != ""): ?>
                            <a href="bctk_suco.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Xóa bộ lọc</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">ID</th>
                                <th width="8%" class="text-center">Hình ảnh</th>
                                <th width="15%" class="text-left">Vật tư</th>
                                <th width="12%" class="text-left">Nơi phát hiện</th>
                                <th width="12%" class="text-center">Ngày lập</th>
                                <th width="8%" class="text-center">SL Lỗi</th>
                                <th width="15%" class="text-left">Nguyên nhân</th>
                                <th width="10%" class="text-center">Trạng thái</th>
                                <th width="15%" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Truy vấn có nối thêm điều kiện WHERE ($where_sql)
                            $sql = "SELECT bc.*, vt.ma_vat_tu, vt.ten_vat_tu, k.ten_kho, nd.ho_ten as nguoi_lap
                                    FROM bao_cao_su_co bc
                                    LEFT JOIN vat_tu vt ON bc.id_vat_tu = vt.id_vat_tu
                                    LEFT JOIN kho k ON bc.id_kho = k.id_kho
                                    LEFT JOIN nguoi_dung nd ON bc.id_nguoi_lap = nd.id_nguoi_dung
                                    WHERE $where_sql
                                    ORDER BY bc.id_bao_cao DESC";
                            $result = mysqli_query($conn, $sql);

                            if(mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $status = $row['trang_thai_duyet'];
                                    $badge_class = 'badge-pending';
                                    if($status == 'Đã duyệt') $badge_class = 'badge-approved';
                                    elseif($status == 'Từ chối') $badge_class = 'badge-rejected';

                                    $ngay_lap = isset($row['ngay_lap']) ? date('d/m/Y H:i', strtotime($row['ngay_lap'])) : 'Chưa có';

                                    echo "<tr>";
                                    echo "<td class='text-center'>{$row['id_bao_cao']}</td>";
                                    
                                    echo "<td class='text-center'>";
                                    if(!empty($row['hinh_anh']) && file_exists($target_dir_suco . $row['hinh_anh'])) {
                                        echo "<a href='{$target_dir_suco}{$row['hinh_anh']}' target='_blank'><img src='{$target_dir_suco}{$row['hinh_anh']}' class='img-thumbnail'></a>";
                                    } else {
                                        echo "<span style='color:#999; font-size:11px;'>Không có</span>";
                                    }
                                    echo "</td>";

                                    echo "<td class='text-left'><strong>{$row['ma_vat_tu']}</strong><br>{$row['ten_vat_tu']}</td>";
                                    echo "<td class='text-left'>{$row['ten_kho']}</td>";
                                    
                                    echo "<td class='text-center'><span class='badge-date'>🕒 {$ngay_lap}</span><br><small style='color:#888;'>bởi {$row['nguoi_lap']}</small></td>";
                                    
                                    echo "<td class='text-center' style='color:#dc3545; font-weight:bold;'>{$row['so_luong_hao_hut']}</td>";
                                    echo "<td class='text-left'>" . htmlspecialchars($row['nguyen_nhan']) . "</td>";
                                    echo "<td class='text-center'><span class='badge {$badge_class}'>{$status}</span></td>";
                                    
                                    echo "<td class='text-center'>";
                                    if ($status == 'Chờ duyệt') {
                                        $imgPath = (!empty($row['hinh_anh'])) ? $target_dir_suco . $row['hinh_anh'] : '';
                                        
                                        echo "<div style='display:flex; justify-content:center; gap:5px;'>
                                                <a href='javascript:void(0)' class='btn-action btn-edit' style='padding:5px 8px; font-size:12px;' 
                                                onclick='moModalSua({$row['id_bao_cao']}, \"{$row['ten_vat_tu']} ({$row['ma_vat_tu']})\", \"{$row['ten_kho']}\", {$row['so_luong_hao_hut']}, \"" . htmlspecialchars($row['nguyen_nhan'], ENT_QUOTES) . "\", \"{$imgPath}\")'>Sửa</a>
                                                
                                                <a href='bctk_suco.php?action=delete&id={$row['id_bao_cao']}' class='btn-action btn-delete' style='padding:5px 8px; font-size:12px;'
                                                onclick='return confirm(\"Xóa báo cáo này hệ thống cũng sẽ xóa luôn ảnh đính kèm. Bạn có chắc chắn?\");'>Xóa</a>
                                              </div>";
                                    } else {
                                        echo "<span style='font-size:12px; color:#888; background:#eee; padding: 3px 8px; border-radius:4px;'>🔒 Đã khóa</span>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center' style='padding: 20px;'>Không tìm thấy báo cáo nào khớp với điều kiện tìm kiếm!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div id="addModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-box" style="background: white; padding: 25px; border-radius: 8px; width: 450px; max-height: 90vh; overflow-y: auto;">
            <h3 style="color: #dc3545; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Lập báo cáo sự cố mới</h3>
            <form method="POST" action="bctk_suco.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_suco">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Vật tư gặp sự cố:</label>
                    <select name="id_vat_tu" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Chọn vật tư --</option>
                        <?php while($vt = mysqli_fetch_assoc($list_vattu)): ?>
                            <option value="<?php echo $vt['id_vat_tu']; ?>"><?php echo $vt['ma_vat_tu'] . ' - ' . $vt['ten_vat_tu']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Nơi phát hiện (Kho):</label>
                    <select name="id_kho" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Chọn kho --</option>
                        <?php 
                        mysqli_data_seek($list_kho, 0);
                        while($k = mysqli_fetch_assoc($list_kho)): 
                        ?>
                            <option value="<?php echo $k['id_kho']; ?>"><?php echo $k['ten_kho']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Số lượng hao hụt/hư hỏng:</label>
                    <input type="number" name="so_luong_hao_hut" min="1" required placeholder="Nhập số lượng..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Nguyên nhân:</label>
                    <textarea name="nguyen_nhan" rows="3" required placeholder="Mô tả chi tiết tình trạng hoặc lý do..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Hình ảnh minh chứng (Tùy chọn):</label>
                    <input type="file" name="hinh_anh" accept="image/*" style="width: 100%; padding: 5px; border: 1px dashed #ccc; border-radius: 4px;">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="dongModalThem()" style="padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Hủy bỏ</button>
                    <button type="submit" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Lưu Báo Cáo</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-box" style="background: white; padding: 25px; border-radius: 8px; width: 450px; max-height: 90vh; overflow-y: auto;">
            <h3 style="color: #007bff; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Cập nhật báo cáo sự cố</h3>
            <form method="POST" action="bctk_suco.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_suco">
                <input type="hidden" name="edit_id" id="modal_edit_id"> 
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Vật tư & Kho (Không cho sửa):</label>
                    <input type="text" id="modal_readonly_info" readonly style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #e9ecef;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Số lượng hao hụt/hư hỏng:</label>
                    <input type="number" name="edit_sl_hao_hut" id="modal_edit_sl" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Nguyên nhân cập nhật:</label>
                    <textarea name="edit_nguyen_nhan" id="modal_edit_nn" rows="3" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Đổi hình ảnh minh chứng mới:</label>
                    <div id="modal_current_img_wrapper" style="margin-bottom: 10px; display: none;">
                        <span style="font-size: 13px; color: #666;">Ảnh hiện tại:</span><br>
                        <img id="modal_current_img" src="" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
                    </div>
                    <input type="file" name="edit_hinh_anh" accept="image/*" style="width: 100%; padding: 5px; border: 1px dashed #ccc; border-radius: 4px;">
                    <small style="color: #666;">(Bỏ trống nếu muốn giữ nguyên ảnh cũ)</small>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="dongModalSua()" style="padding: 10px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Hủy</button>
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

        function moModalSua(id, vattu_name, kho_name, sl, nguyen_nhan, imgPath) {
            document.getElementById("modal_edit_id").value = id;
            document.getElementById("modal_readonly_info").value = vattu_name + " (tại " + kho_name + ")";
            document.getElementById("modal_edit_sl").value = sl;
            document.getElementById("modal_edit_nn").value = nguyen_nhan;
            
            var imgWrapper = document.getElementById("modal_current_img_wrapper");
            var imgTag = document.getElementById("modal_current_img");
            
            if(imgPath !== "") {
                imgTag.src = imgPath;
                imgWrapper.style.display = "block";
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
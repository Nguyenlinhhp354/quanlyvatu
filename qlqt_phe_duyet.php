<?php
session_start();
include 'db_connect.php';

// KHI CHƯA ĐĂNG NHẬP: Chuyển hướng về login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung']; 

// ==========================================
// KIỂM TRA QUYỀN GIÁM ĐỐC
// ==========================================
$is_director = false;
$sql_user = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung='$id_nguoi_dung'";
$result_user = @mysqli_query($conn, $sql_user);
if ($result_user && mysqli_num_rows($result_user) > 0) {
    $row_user = mysqli_fetch_assoc($result_user);
    $ho_ten = $row_user['ho_ten'];
    
    // Lấy chức vụ (nếu có) và tên để kiểm tra
    $chuc_vu = isset($row_user['chuc_vu']) ? mb_strtolower($row_user['chuc_vu'], 'UTF-8') : '';
    $ten_kiem_tra = mb_strtolower($ho_ten, 'UTF-8');
    
    // ĐIỀU KIỆN LÀ GIÁM ĐỐC
    if (strpos($chuc_vu, 'giám đốc') !== false || strpos($chuc_vu, 'admin') !== false || 
        strpos($ten_kiem_tra, 'giám đốc') !== false || strpos($ten_kiem_tra, 'admin') !== false || 
        $id_nguoi_dung == 1) {
        $is_director = true;
    }
} else {
    $ho_ten = "User";
}

// ==========================================
// XỬ LÝ LƯU KẾT QUẢ PHÊ DUYỆT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_duyet') {
    if (!$is_director) {
        echo "<script>alert('Lỗi bảo mật: Chỉ Giám đốc mới có quyền thực hiện thao tác này!'); window.history.back();</script>";
        exit;
    }

    $id_phieu_yc = intval($_POST['id_phieu_yc']);
    $trang_thai_moi = intval($_POST['trang_thai_moi']); // 1: Duyệt, 2: Từ chối
    $ghi_chu_duyet = isset($_POST['ghi_chu_duyet']) ? mysqli_real_escape_string($conn, $_POST['ghi_chu_duyet']) : "";

    mysqli_begin_transaction($conn);
    try {
        // Cập nhật trạng thái phiếu và người duyệt
        $sql_upd_phieu = "UPDATE phieu_yeu_cau 
                          SET trang_thai='$trang_thai_moi', nguoi_duyet='$id_nguoi_dung' 
                          WHERE id_phieu_yc='$id_phieu_yc'";
        if (!mysqli_query($conn, $sql_upd_phieu)) throw new Exception("Lỗi cập nhật trạng thái phiếu!");

        // Nếu GIÁM ĐỐC DUYỆT (Trạng thái = 1) -> Lưu lại số lượng được duyệt cho từng vật tư
        if ($trang_thai_moi == 1 && isset($_POST['sl_duyet'])) {
            foreach ($_POST['sl_duyet'] as $id_vt => $sl) {
                $id_vt_clean = intval($id_vt);
                $sl_clean = floatval($sl);
                
                $sql_upd_ct = "UPDATE chi_tiet_yeu_cau 
                               SET so_luong_duyet='$sl_clean' 
                               WHERE id_phieu_yc='$id_phieu_yc' AND id_vat_tu='$id_vt_clean'";
                mysqli_query($conn, $sql_upd_ct);
            }
            $msg = "Đã PHÊ DUYỆT phiếu yêu cầu thành công!";
        } else {
            // Nếu TỪ CHỐI (Trạng thái = 2) -> Set tất cả số lượng duyệt = 0
            mysqli_query($conn, "UPDATE chi_tiet_yeu_cau SET so_luong_duyet=0 WHERE id_phieu_yc='$id_phieu_yc'");
            $msg = "Đã TỪ CHỐI phiếu yêu cầu!";
        }

        mysqli_commit($conn);
        echo "<script>alert('$msg'); window.location.href='qlqt_phe_duyet.php';</script>";
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('LỖI: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}

// BIẾN TÌM KIẾM
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : "";
$trang_thai_loc = isset($_GET['trang_thai_loc']) ? $_GET['trang_thai_loc'] : "0"; // Mặc định hiển thị phiếu "Chờ duyệt"

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phê duyệt Phiếu Yêu Cầu - Thịnh Tiến MM</title>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <style>
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-wrapper { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; align-items: center;}
        .filter-wrapper input, .filter-wrapper select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; outline: none; }
        .btn-search { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-clear { padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        
        .pkk-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;}
        .data-table th, .data-table td { border: 1px solid #dee2e6; padding: 10px 12px; vertical-align: middle;}
        .data-table thead th { background-color: #343a40; color: #ffffff; text-transform: uppercase; font-size: 13px; font-weight: bold; position: sticky; top: 0;}
        .data-table tbody tr:hover { background-color: #f8f9fa; }
        
        .btn-view { background-color: #17a2b8; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; font-weight: bold;}
        
        /* ĐÃ ĐỔI MÀU NÚT DUYỆT SANG XANH NƯỚC BIỂN (#007bff) */
        .btn-approve { background-color: #007bff; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; font-weight: bold; cursor: pointer; border: none;}
        
        .btn-reject { background-color: #dc3545; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size: 13px; font-weight: bold; cursor: pointer; border: none;}
        .btn-back { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }

        .badge-choduyet { background: #ffc107; color: #000; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-daduyet { background: #28a745; color: #fff; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-tuchoi { background: #dc3545; color: #fff; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        
        .input-soluong { width: 120px; padding: 8px; border: 2px solid #007bff; border-radius: 4px; font-weight: bold; text-align: center; outline: none; background: #e8f4ff; color: #000;}
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <main>
                
                <?php 
                // ===================================================================
                // MÀN HÌNH 2: XEM CHI TIẾT & TIẾN HÀNH DUYỆT (DÀNH CHO GIÁM ĐỐC)
                // ===================================================================
                if (isset($_GET['action']) && in_array($_GET['action'], ['view', 'duyet']) && isset($_GET['id'])): 
                    $id_phieu = intval($_GET['id']);
                    $is_duyet_mode = ($_GET['action'] == 'duyet' && $is_director);

                    $sql_phieu = "SELECT p.*, n.ho_ten AS nguoi_lap, da.ten_du_an, nd.ho_ten AS ten_nguoi_duyet
                                  FROM phieu_yeu_cau p 
                                  LEFT JOIN nguoi_dung n ON p.id_nguoi_lap = n.id_nguoi_dung 
                                  LEFT JOIN du_an da ON p.id_du_an = da.id_du_an
                                  LEFT JOIN nguoi_dung nd ON p.nguoi_duyet = nd.id_nguoi_dung
                                  WHERE p.id_phieu_yc = '$id_phieu'";
                    $phieu = mysqli_fetch_assoc(mysqli_query($conn, $sql_phieu));
                    
                    if (!$phieu) { echo "<script>alert('Không tìm thấy phiếu!'); window.location.href='qlqt_phe_duyet.php';</script>"; exit; }
                    if ($is_duyet_mode && $phieu['trang_thai'] != 0) {
                        echo "<script>alert('Phiếu này đã được xử lý từ trước!'); window.location.href='qlqt_phe_duyet.php';</script>"; exit;
                    }

                    // Text trạng thái
                    if ($phieu['trang_thai'] == 1) $trang_thai_text = "<span class='badge-daduyet'>Đã duyệt</span>";
                    elseif ($phieu['trang_thai'] == 2) $trang_thai_text = "<span class='badge-tuchoi'>Đã từ chối</span>";
                    else $trang_thai_text = "<span class='badge-choduyet'>Chờ duyệt</span>";
                    
                    $ten_du_an = !empty($phieu['ten_du_an']) ? $phieu['ten_du_an'] : "Chưa xác định";
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #333;">CHI TIẾT PHIẾU YÊU CẦU</h2>
                        <a href="qlqt_phe_duyet.php" class="btn-back">&laquo; Quay lại</a>
                    </div>

                    <?php if (!$is_director): ?>
                        <div style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                            <b>⚠️ THÔNG BÁO BẢO MẬT:</b> Tài khoản của bạn không có chức vụ "Giám đốc". Bạn chỉ có quyền Xem thông tin chi tiết của phiếu yêu cầu này, không có quyền Phê duyệt.
                        </div>
                    <?php endif; ?>

                    <div class="pkk-section">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 15px;">
                                <div><span style="color:#666">Số phiếu:</span> <strong style="color:#007bff;"><?=$phieu['so_phieu']?></strong></div>
                                <div><span style="color:#666">Trạng thái:</span> <?=$trang_thai_text?></div>
                                <div><span style="color:#666">Ngày lập:</span> <strong><?=date('d/m/Y H:i', strtotime($phieu['ngay_lap']))?></strong></div>
                                <div><span style="color:#666">Người lập:</span> <strong><?=$phieu['nguoi_lap']?></strong></div>
                                <div><span style="color:#666">Dự án:</span> <strong style="color:#28a745;"><?=$ten_du_an?></strong></div>
                                <div><span style="color:#666">Lý do:</span> <strong><?=$phieu['ly_do']?></strong></div>
                                <?php if($phieu['trang_thai'] != 0): ?>
                                    <div style="grid-column: span 2; border-top: 1px dashed #ccc; padding-top: 10px; margin-top: 5px;">
                                        <span style="color:#666">Người duyệt:</span> <strong><?=$phieu['ten_nguoi_duyet'] ?? 'Không rõ'?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" action="qlqt_phe_duyet.php">
                            <input type="hidden" name="action" value="save_duyet">
                            <input type="hidden" name="id_phieu_yc" value="<?=$id_phieu?>">

                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="15%" class="text-center">Mã VT</th>
                                        <th width="40%" class="text-left">Tên vật tư</th>
                                        <th width="10%" class="text-center">ĐVT</th>
                                        <th width="15%" class="text-center">SL YÊU CẦU</th>
                                        <?php if ($is_duyet_mode || $phieu['trang_thai'] == 1): ?>
                                            <th width="15%" class="text-center" style="background:#007bff;">SL DUYỆT</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_chitiet = "SELECT ct.*, vt.ma_vat_tu, vt.ten_vat_tu, dvt.ten_don_vi_tinh
                                                    FROM chi_tiet_yeu_cau ct
                                                    JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
                                                    LEFT JOIN don_vi_tinh dvt ON vt.id_dvt = dvt.id_dvt
                                                    WHERE ct.id_phieu_yc = '$id_phieu'
                                                    ORDER BY vt.ten_vat_tu ASC";
                                    $result_ct = mysqli_query($conn, $sql_chitiet);
                                    $stt = 1;
                                    
                                    while ($item = mysqli_fetch_assoc($result_ct)) {
                                        $id_vt = $item['id_vat_tu'];
                                        $sl_yc = floatval($item['so_luong_yeu_cau']);
                                        $sl_duyet = floatval($item['so_luong_duyet']);

                                        echo "<tr>";
                                        echo "<td class='text-center'>{$stt}</td>";
                                        echo "<td class='text-center'><strong>{$item['ma_vat_tu']}</strong></td>";
                                        echo "<td class='text-left'>{$item['ten_vat_tu']}</td>";
                                        echo "<td class='text-center'>{$item['ten_don_vi_tinh']}</td>";
                                        echo "<td class='text-center'><strong style='font-size: 15px;'>{$sl_yc}</strong></td>";
                                        
                                        if ($is_duyet_mode) {
                                            // Cho phép Giám đốc sửa số lượng duyệt
                                            echo "<td class='text-center'>
                                                    <input type='number' step='0.01' min='0' name='sl_duyet[{$id_vt}]' class='input-soluong' value='{$sl_yc}'>
                                                  </td>";
                                        } elseif ($phieu['trang_thai'] == 1) {
                                            // Chỉ xem (Phiếu đã duyệt)
                                            echo "<td class='text-center'><strong style='color:#007bff; font-size:15px;'>{$sl_duyet}</strong></td>";
                                        }

                                        echo "</tr>";
                                        $stt++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                            
                            <?php if ($is_duyet_mode): ?>
                                <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; border-top: 2px solid #eee; padding-top: 20px;">
                                    <button type="submit" name="trang_thai_moi" value="2" class="btn-reject" onclick="return confirm('Bạn từ chối toàn bộ phiếu yêu cầu này?');">TỪ CHỐI DUYỆT</button>
                                    <button type="submit" name="trang_thai_moi" value="1" class="btn-approve" onclick="return confirm('Xác nhận Phê duyệt và chốt số lượng vật tư?');">PHÊ DUYỆT YÊU CẦU</button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                <?php 
                // ===================================================================
                // MÀN HÌNH 1: DANH SÁCH PHIẾU ĐỂ DUYỆT (Mặc định)
                // ===================================================================
                else: 
                    // TẠO ĐIỀU KIỆN LỌC
                    $where = "1=1";
                    if ($search != "") $where .= " AND (p.so_phieu LIKE '%$search%' OR da.ten_du_an LIKE '%$search%')";
                    if ($trang_thai_loc != "all") {
                        $st = intval($trang_thai_loc);
                        $where .= " AND p.trang_thai = '$st'";
                    }
                ?>
                    <div class="toolbar">
                        <h2 class="page-title" style="margin: 0; color: #333;">Dành cho Giám Đốc: Phê Duyệt Vật Tư</h2>
                        <span style="background: #17a2b8; color: white; padding: 5px 15px; border-radius: 15px; font-size: 13px; font-weight: bold;">
                            Vai trò hiện tại: <?=$is_director ? 'Ban Giám Đốc' : '👤 Nhân viên (Chỉ xem)'?>
                        </span>
                    </div>

                    <form class="filter-wrapper" method="GET" action="qlqt_phe_duyet.php">
                        <input type="text" name="search" placeholder="Nhập Số phiếu, tên Dự án..." value="<?=htmlspecialchars($search)?>" style="width: 250px;">
                        
                        <span style="font-weight: bold; color: #555;">Trạng thái:</span>
                        <select name="trang_thai_loc" style="font-weight: bold;">
                            <option value="all" <?=($trang_thai_loc=='all')?'selected':''?>>- Tất cả -</option>
                            <option value="0" <?=($trang_thai_loc=='0')?'selected':''?> style="color:#d39e00;">Cần phê duyệt ngay</option>
                            <option value="1" <?=($trang_thai_loc=='1')?'selected':''?> style="color:green;">Lịch sử đã duyệt</option>
                            <option value="2" <?=($trang_thai_loc=='2')?'selected':''?> style="color:red;">Đã từ chối</option>
                        </select>

                        <button type="submit" class="btn-search">Lọc danh sách</button>
                    </form>

                    <div class="pkk-section">
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th width="15%" class="text-left">Số Phiếu</th>
                                        <th width="15%" class="text-center">Ngày Lập</th>
                                        <th width="25%" class="text-left">Dự án yêu cầu</th>
                                        <th width="20%" class="text-left">Lý do</th>
                                        <th width="10%" class="text-center">Trạng thái</th>
                                        <th width="15%" class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_list = "SELECT p.*, n.ho_ten, da.ten_du_an 
                                                 FROM phieu_yeu_cau p
                                                 LEFT JOIN nguoi_dung n ON p.id_nguoi_lap = n.id_nguoi_dung
                                                 LEFT JOIN du_an da ON p.id_du_an = da.id_du_an
                                                 WHERE $where ORDER BY p.trang_thai ASC, p.ngay_lap DESC"; 
                                    $result_list = @mysqli_query($conn, $sql_list);
                                    
                                    if ($result_list && mysqli_num_rows($result_list) > 0) {
                                        while ($pyc = mysqli_fetch_assoc($result_list)) {
                                            $ngay_lap = date('d/m/Y H:i', strtotime($pyc['ngay_lap']));
                                            $trang_thai = intval($pyc['trang_thai']);
                                            $ten_du_an_hienthi = !empty($pyc['ten_du_an']) ? $pyc['ten_du_an'] : "Chưa xác định";
                                            
                                            // Chuyển đổi trạng thái hiển thị
                                            if ($trang_thai == 1) $badge_status = "<span class='badge-daduyet'>Đã duyệt</span>";
                                            elseif ($trang_thai == 2) $badge_status = "<span class='badge-tuchoi'>Từ chối</span>";
                                            else $badge_status = "<span class='badge-choduyet'>Chờ duyệt</span>";
                                            
                                            echo "<tr>";
                                            echo "<td class='text-left'><strong style='color: #007bff; font-size: 15px;'>{$pyc['so_phieu']}</strong></td>";
                                            echo "<td class='text-center'>{$ngay_lap}</td>";
                                            echo "<td class='text-left'><strong style='color:#28a745'>{$ten_du_an_hienthi}</strong><br><span style='font-size:12px; color:#666'>Bởi: {$pyc['ho_ten']}</span></td>";
                                            echo "<td class='text-left' style='color: #555;'>{$pyc['ly_do']}</td>";
                                            echo "<td class='text-center'>{$badge_status}</td>";
                                            
                                            echo "<td class='text-center'>";
                                            // LOGIC NÚT BẤM DÀNH RIÊNG CHO GIÁM ĐỐC
                                            if ($trang_thai == 0 && $is_director) {
                                                echo "<a href='?action=duyet&id={$pyc['id_phieu_yc']}' class='btn-approve'>TIẾN HÀNH DUYỆT</a>";
                                            } else {
                                                echo "<a href='?action=view&id={$pyc['id_phieu_yc']}' class='btn-view'>👁 Xem chi tiết</a>";
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center' style='padding: 30px; color: #888;'>Không có dữ liệu phiếu yêu cầu.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>
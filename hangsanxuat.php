<?php
include_once 'db_connect.php'; 
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// === GIỮ NGUYÊN 100% LOGIC XỬ LÝ CỦA BẠN ===

// Xử lý Sửa
if(isset($_POST['hanh_dong']) && $_POST['hanh_dong'] == 'sua'){
    $id_hsx = $_POST['id_hsx'];
    $ten = $_POST['ten_hang_san_xuat'];
    $sqlupdate = "update hang_san_xuat set ten_hang_san_xuat='$ten' where id_hsx = '$id_hsx'";
    if($conn->query($sqlupdate)){
        header('location:hangsanxuat.php');
        exit();
    }
    else{
        echo "Lỗi: " . $conn->error;
    }
}
// Xử lý Xóa
else if(isset($_POST['hanh_dong']) && $_POST['hanh_dong'] == 'xoa'){
    $id_hsx = $_POST['id_hsx'];
    $delete = "delete from hang_san_xuat where id_hsx = '$id_hsx'";
    if($conn->query($delete)){
        header('location:hangsanxuat.php');
        exit();
    }
    else{
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý Tìm kiếm
$timkiem = '';
if(isset($_POST['btn_timkiem'])){
    $timkiem = $_POST['timkiem'];
    $sqltruyvan = "select * from hang_san_xuat where ten_hang_san_xuat like '%$timkiem%' or id_hsx like '%$timkiem%'";
}
else{
    $sqltruyvan = "select * from hang_san_xuat ";
}
$result = $conn->query($sqltruyvan);

// Xử lý Thêm mới
if(!empty($_POST['submit'])){
    $id_hsx = $_POST['id_hsx'];
    $ten_hang_san_xuat = $_POST['ten_hang_san_xuat'];
    $check = $conn->query("select * from hang_san_xuat where id_hsx = '$id_hsx'");
    if($check->num_rows > 0){
		$msg = "Lỗi: Mã hãng sản xuất này đã tồn tại!";
    }
    else{
    $sql = "insert into hang_san_xuat values('$id_hsx', '$ten_hang_san_xuat')";
    if($conn->query($sql)){
        header('location:hangsanxuat.php');
        exit();
    }
    else{
        echo "Lỗi: " . $conn->error;
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> <title>Hãng sản xuất</title>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="wrapper d-flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1 p-4 bg-light">
            <?php if (isset($msg) && $msg !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Thông báo:</strong> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form method="post" class="d-flex w-75 gap-2">    
                    <input type="text" name="timkiem" class="form-control" placeholder="Tìm kiếm theo mã, tên..." value="<?php echo htmlspecialchars($timkiem); ?>">
                    <button name="btn_timkiem" class="btn btn-outline-dark text-nowrap">Tìm kiếm</button>
                </form>
                <button class="btn btn-success text-nowrap" onclick="moboxthem()">+ Thêm hãng sản xuất</button>
            </div>

            <div class="bg-white p-3 border rounded">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;">ID hãng sản xuất</th>
                            <th>Tên hãng sản xuất</th>
                            <th style="width: 150px; text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $item): ?>
                        <tr>
                            <td class="align-middle"><?php echo $item['id_hsx'] ?></td>
                            <td class="align-middle"><?php echo $item['ten_hang_san_xuat'] ?></td>
                            <td class="text-center align-middle">
                                <button class="btn btn-outline-primary btn-sm me-1" onclick="moboxsua('<?php echo $item['id_hsx']; ?>', '<?php echo $item['ten_hang_san_xuat']; ?>')">Sửa</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="xoa('<?php echo $item['id_hsx']; ?>')">Xóa</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div id="modalbox" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 400px; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            
            <h4 id="tieude_modal" class="mb-4">Tiêu đề Modal</h4>
            
            <form method="POST" id="form_sua_xoa">
                <input type="hidden" name="hanh_dong" id="hanh_dong">
                <div class="mb-3">
                    <label class="form-label fw-bold">ID Hãng sản xuất</label>
                    <input type="text" name="id_hsx" id="id_hsx" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên hãng sản xuất</label>
                    <input type="text" name="ten_hang_san_xuat" id="ten_hang_san_xuat" class="form-control" required>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary w-50">LƯU</button>
                    <button type="button" class="btn btn-secondary w-50" onclick="dongbox()">HỦY</button>
                </div>
            </form>

            <form method="post" id="form_them" style="display:none;">
                <div class="mb-3">
                    <label class="form-label fw-bold">ID hãng sản xuất</label>
                    <input type="text" name="id_hsx" id="id_hsx_them" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên hãng sản xuất</label>
                    <input type="text" name="ten_hang_san_xuat" id="ten_hang_san_xuat_them" class="form-control" required>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <input type="submit" name="submit" value="Thêm mới" class="btn btn-success w-50">
                    <button type="button" class="btn btn-secondary w-50" onclick="dongbox()">HỦY</button>
                </div>
            </form>

        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function moboxsua(id, ten){
            document.getElementById('tieude_modal').innerText = 'Sửa hãng sản xuất';
            // Hiện form sửa, ẩn form thêm
            document.getElementById('form_sua_xoa').style.display = 'block';
            document.getElementById('form_them').style.display = 'none';

            document.getElementById('hanh_dong').value = 'sua';
            document.getElementById('id_hsx').value = id;
            document.getElementById('ten_hang_san_xuat').value = ten;
            
            document.getElementById('modalbox').style.display = 'flex';
        }

        function moboxthem(){
            document.getElementById('tieude_modal').innerText = 'Thêm hãng sản xuất';
            // Hiện form thêm, ẩn form sửa
            document.getElementById('form_sua_xoa').style.display = 'none';
            document.getElementById('form_them').style.display = 'block';

            // Xóa rỗng ô nhập liệu
            document.getElementById('id_hsx_them').value = '';
            document.getElementById('ten_hang_san_xuat_them').value = '';

            document.getElementById('modalbox').style.display = 'flex';
        }

        function dongbox(){
            document.getElementById('modalbox').style.display = 'none';
        }

        function xoa(id){
            if(confirm("Bạn có muốn xóa nhà cung cấp này đi không ?")){
                // Mượn form_sua_xoa để submit lệnh xóa
                document.getElementById('form_sua_xoa').style.display = 'block';
                document.getElementById('form_them').style.display = 'none';

                document.getElementById('hanh_dong').value = 'xoa';
                document.getElementById('id_hsx').value = id;
                document.getElementById('form_sua_xoa').submit();
            }
        }
    </script>
</body>
</html>
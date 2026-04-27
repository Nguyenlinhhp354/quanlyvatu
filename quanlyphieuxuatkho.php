<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    header("location:login.php");
    exit();
}

$msg = "";

if(isset($_POST['hanh_dong']) && $_POST['hanh_dong']=="them"){

    $so_phieu = $_POST['so_phieu'];
    $ngay_xuat = $_POST['ngay_xuat'];
    $id_kho = $_POST['id_kho'];
    $id_du_an = $_POST['id_du_an'];
    $id_nguoi_lap = $_POST['id_nguoi_lap'];
    $nguoi_nhan = $_POST['nguoi_nhan'];
    $ly_do = $_POST['ly_do'];

    $id_vat_tu = $_POST['id_vat_tu'];
    $so_luong = $_POST['so_luong'];

    $check = $conn->query("SELECT * FROM phieu_xuat_kho WHERE so_phieu='$so_phieu'");

    if($check->num_rows > 0){
        $msg = "Số phiếu đã tồn tại!";
    }else{
        $conn->begin_transaction();
        try{
            $conn->query("
                INSERT INTO phieu_xuat_kho (so_phieu, ngay_xuat, id_kho, id_du_an, id_nguoi_lap, nguoi_nhan, ly_do_xuat)
                VALUES ('$so_phieu', '$ngay_xuat', '$id_kho', '$id_du_an', '$id_nguoi_lap', '$nguoi_nhan', '$ly_do')
            ");

            $id_phieu = $conn->insert_id;

            for($i=0;$i<count($id_vat_tu);$i++){
                $vt = $id_vat_tu[$i];
                $sl = $so_luong[$i];

                $ton = $conn->query("SELECT so_luong_ton FROM ton_kho WHERE id_kho='$id_kho' AND id_vat_tu='$vt'");
                $row = $ton->fetch_assoc();
                if(!$row){
                    throw new Exception("Vật tư này chưa tồn tại trong kho");
                }

                if($row['so_luong_ton'] < $sl){
                    throw new Exception("Không đủ tồn kho cho vật tư mã: $vt");
                }

                $conn->query("INSERT INTO chi_tiet_xuat_kho VALUES('$id_phieu','$vt','$sl')");

                $conn->query("UPDATE ton_kho SET so_luong_ton = so_luong_ton - $sl WHERE id_kho='$id_kho' AND id_vat_tu='$vt'");
            }

            $conn->commit();
            header("location:quanlyphieuxuatkho.php");
            exit();
        }catch(Exception $e){
            $conn->rollback();
            $msg = $e->getMessage();
        }
    }
}

else if(isset($_POST['hanh_dong']) && $_POST['hanh_dong']=="sua"){
    $id_phieu = $_POST['id_phieu'];
    $so_phieu = $_POST['so_phieu'];
    $ngay_xuat = $_POST['ngay_xuat'];
    $nguoi_nhan = $_POST['nguoi_nhan'];
    $ly_do = $_POST['ly_do'];

    $conn->query("
        UPDATE phieu_xuat_kho
        SET so_phieu = '$so_phieu',
            ngay_xuat = '$ngay_xuat',
            nguoi_nhan = '$nguoi_nhan',
            ly_do_xuat = '$ly_do'
        WHERE id_phieu_xuat = '$id_phieu'
    ");

    header("location:quanlyphieuxuatkho.php");
    exit();
}

else if(isset($_POST['hanh_dong']) && $_POST['hanh_dong']=="xoa"){
    $id_phieu = $_POST['id_phieu'];
    $id_kho = $_POST['id_kho'];

    $ct = $conn->query("SELECT * FROM chi_tiet_xuat_kho WHERE id_phieu_xuat='$id_phieu'");
    while($row = $ct->fetch_assoc()){
        $conn->query("UPDATE ton_kho SET so_luong_ton = so_luong_ton + ".$row['so_luong']." WHERE id_kho='$id_kho' AND id_vat_tu='".$row['id_vat_tu']."'");
    }

    $conn->query("DELETE FROM chi_tiet_xuat_kho WHERE id_phieu_xuat='$id_phieu'");
    $conn->query("DELETE FROM phieu_xuat_kho WHERE id_phieu_xuat='$id_phieu'");

    header("location:quanlyphieuxuatkho.php");
    exit();
}

$timkiem = "";
if(isset($_POST['btn_timkiem'])){
    $timkiem = $_POST['timkiem'];
    $sql = "SELECT * FROM phieu_xuat_kho WHERE so_phieu LIKE '%$timkiem%'";
} else {
    $sql = "SELECT * FROM phieu_xuat_kho";
}

$result = $conn->query($sql);
$kho = $conn->query("SELECT * FROM kho");
$duan = $conn->query("SELECT * FROM du_an");
$nguoidung = $conn->query("SELECT * FROM nguoi_dung");
$vattu = $conn->query("SELECT * FROM vat_tu");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phiếu xuất kho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="wrapper d-flex">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content flex-grow-1 p-4 bg-light">

        <?php if($msg!=""){ echo "<div class='alert alert-danger'>$msg</div>"; } ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form method="post" class="d-flex gap-2 w-50">
                <input type="text" name="timkiem" class="form-control" placeholder="Tìm số phiếu..." value="<?php echo $timkiem ?>">
                <button name="btn_timkiem" class="btn btn-dark">Tìm kiếm</button>
            </form>
            <button class="btn btn-success" onclick="moboxthem()">+ Thêm phiếu xuất</button>
        </div>

        <div class="bg-white p-3 border rounded">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Số phiếu</th>
                        <th>Ngày xuất</th>
                        <th>Lý do</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($result as $item){ 
                    $ngay_sua = date('Y-m-d\TH:i', strtotime($item['ngay_xuat']));
                ?>
                    <tr>
                        <td><?php echo $item['so_phieu'] ?></td>
                        <td><?php echo $item['ngay_xuat'] ?></td>
                        <td><?php echo $item['ly_do_xuat'] ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm"
                                onclick="moboxsua('<?php echo $item['id_phieu_xuat']?>', '<?php echo $item['so_phieu']?>', '<?php echo $ngay_sua ?>', '<?php echo $item['ly_do_xuat']?>', '<?php echo $item['nguoi_nhan']?>')">
                                Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="xoa('<?php echo $item['id_phieu_xuat']?>', '<?php echo $item['id_kho']?>')">
                                Xóa
                            </button>
                             <a href="inphieuxuat.php?id=<?php echo $item['id_phieu_xuat']?>"
                                target="_blank" class="btn btn-secondary btn-sm"> In </a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalbox" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="bg-white p-4 rounded" style="width:500px; max-height: 90vh; overflow-y: auto;">
        <h4 id="tieude_modal">Phiếu xuất</h4>
        <form method="post">
            <input type="hidden" name="hanh_dong" id="hanh_dong">
            <input type="hidden" name="id_phieu" id="id_phieu">

            <div class="mb-2">
                <label>Số phiếu</label>
                <input type="text" name="so_phieu" id="so_phieu" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Ngày xuất</label>
                <input type="datetime-local" name="ngay_xuat" id="ngay_xuat" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Lý do</label>
                <input type="text" name="ly_do" id="ly_do" class="form-control">
            </div>
            <div class="mb-2">
                <label>Người nhận</label>
                <input type="text" name="nguoi_nhan" id="nguoi_nhan" class="form-control">
            </div>

            <div id="vung_chon_them">
                <div class="mb-2">
                    <label>Kho</label>
                    <select name="id_kho" class="form-control">
                        <?php foreach($kho as $k){ echo "<option value='".$k['id_kho']."'>".$k['ten_kho']."</option>"; } ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Dự án</label>
                    <select name="id_du_an" class="form-control">
                        <?php foreach($duan as $d){ echo "<option value='".$d['id_du_an']."'>".$d['ten_du_an']."</option>"; } ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Người lập</label>
                    <select name="id_nguoi_lap" class="form-control">
                        <?php foreach($nguoidung as $n){ echo "<option value='".$n['id_nguoi_dung']."'>".$n['ho_ten']."</option>"; } ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Vật tư</label>
                    <select name="id_vat_tu[]" class="form-control">
                        <?php foreach($vattu as $v){ echo "<option value='".$v['id_vat_tu']."'>".$v['ten_vat_tu']."</option>"; } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Số lượng</label>
                    <input type="number" name="so_luong[]" class="form-control">
                </div>
            </div>

            <button class="btn btn-success">Lưu</button>
            <button type="button" class="btn btn-secondary" onclick="dongbox()">Hủy</button>
        </form>
    </div>
</div>

<script>
function moboxthem(){
    document.querySelector("#modalbox form").reset();
    document.getElementById("modalbox").style.display="flex";
    document.getElementById("tieude_modal").innerText="Thêm phiếu xuất";
    document.getElementById("hanh_dong").value="them";
    document.getElementById("vung_chon_them").style.display="block"; 
}

function moboxsua(id, sophieu, ngay, lydo, nguoinhan){
    document.getElementById("modalbox").style.display="flex";
    document.getElementById("tieude_modal").innerText="Sửa phiếu xuất";
    document.getElementById("hanh_dong").value="sua";
    document.getElementById("vung_chon_them").style.display="none"; 

    document.getElementById("id_phieu").value = id;
    document.getElementById("so_phieu").value = sophieu;
    document.getElementById("ngay_xuat").value = ngay;
    document.getElementById("ly_do").value = lydo;
    document.getElementById("nguoi_nhan").value = nguoinhan;
}

function dongbox(){
    document.getElementById("modalbox").style.display="none";
}

function xoa(id, idkho){
    if(confirm("Bạn có muốn xóa phiếu này không?")){
        let form = document.createElement("form");
        form.method = "post";
        form.innerHTML = `<input name="hanh_dong" value="xoa"><input name="id_phieu" value="${id}"><input name="id_kho" value="${idkho}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>
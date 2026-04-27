<?php
session_start();
include_once 'db_connect.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit();
}

$id_nguoi_dung = $_SESSION['id_nguoi_dung'];
$msg = "";



if(isset($_POST['action']) && $_POST['action'] == 'add_transfer'){

    $so_phieu = $_POST['so_phieu'];
    $ngay_lap = $_POST['ngay_lap'];
    $id_kho_from = $_POST['id_kho_from'];
    $id_kho_to = $_POST['id_kho_to'];

    $ds_vat_tu = $_POST['id_vat_tu'];
    $ds_so_luong = $_POST['so_luong'];

    if($id_kho_from == $id_kho_to){
        $msg = "Kho xuất và kho nhận không được giống nhau!";
    }
    else{

        $conn->begin_transaction();

        try{

            
            $check_phieu = $conn->query("
                SELECT * FROM phieu_xuat_kho 
                WHERE so_phieu='$so_phieu'
            ");

            if($check_phieu->num_rows > 0){
                throw new Exception("Số phiếu đã tồn tại!");
            }


           
            $conn->query("
                INSERT INTO phieu_xuat_kho(
                    so_phieu,
                    ngay_xuat,
                    id_kho,
                    id_nguoi_lap,
                    nguoi_nhan,
                    ly_do_xuat
                )
                VALUES(
                    '$so_phieu',
                    '$ngay_lap',
                    '$id_kho_from',
                    '$id_nguoi_dung',
                    'Kho $id_kho_to',
                    'Luân chuyển vật tư'
                )
            ");

            $id_phieu_xuat = $conn->insert_id;


            
            $so_phieu_nhap = "PNLC-" . time();

            $conn->query("
                INSERT INTO phieu_nhap_kho(
                    so_phieu,
                    ngay_nhap,
                    id_kho,
                    id_nguoi_lap,
                    ghi_chu
                )
                VALUES(
                    '$so_phieu_nhap',
                    '$ngay_lap',
                    '$id_kho_to',
                    '$id_nguoi_dung',
                    'Nhận từ kho $id_kho_from'
                )
            ");

            $id_phieu_nhap = $conn->insert_id;


           
            for($i=0; $i<count($ds_vat_tu); $i++){

                $id_vat_tu = $ds_vat_tu[$i];
                $so_luong = $ds_so_luong[$i];

                if($so_luong <= 0){
                    continue;
                }


                $check_ton = $conn->query("
                    SELECT * FROM ton_kho
                    WHERE id_kho='$id_kho_from'
                    AND id_vat_tu='$id_vat_tu'
                ");

                $ton = $check_ton->fetch_assoc();

                if(!$ton){
                    throw new Exception("Vật tư ID $id_vat_tu chưa có trong kho nguồn");
                }

                if($ton['so_luong_ton'] < $so_luong){
                    throw new Exception("Vật tư ID $id_vat_tu không đủ tồn kho");
                }


                
                $conn->query("
                    INSERT INTO chi_tiet_xuat_kho(
                        id_phieu_xuat,
                        id_vat_tu,
                        so_luong
                    )
                    VALUES(
                        '$id_phieu_xuat',
                        '$id_vat_tu',
                        '$so_luong'
                    )
                ");


                $conn->query("
                    UPDATE ton_kho
                    SET so_luong_ton = so_luong_ton - $so_luong
                    WHERE id_kho='$id_kho_from'
                    AND id_vat_tu='$id_vat_tu'
                ");


               
                $conn->query("
                    INSERT INTO chi_tiet_nhap_kho(
                        id_phieu_nhap,
                        id_vat_tu,
                        so_luong,
                        don_gia
                    )
                    VALUES(
                        '$id_phieu_nhap',
                        '$id_vat_tu',
                        '$so_luong',
                        0
                    )
                ");


                $check_kho_to = $conn->query("
                    SELECT * FROM ton_kho
                    WHERE id_kho='$id_kho_to'
                    AND id_vat_tu='$id_vat_tu'
                ");


                if($check_kho_to->num_rows > 0){

                    $conn->query("
                        UPDATE ton_kho
                        SET so_luong_ton = so_luong_ton + $so_luong
                        WHERE id_kho='$id_kho_to'
                        AND id_vat_tu='$id_vat_tu'
                    ");

                }else{

                    $conn->query("
                        INSERT INTO ton_kho(
                            id_kho,
                            id_vat_tu,
                            so_luong_ton
                        )
                        VALUES(
                            '$id_kho_to',
                            '$id_vat_tu',
                            '$so_luong'
                        )
                    ");
                }
            }

            $conn->commit();

            header("Location: luanchuyenvattu.php?success=1");
            exit();

        }catch(Exception $e){

            $conn->rollback();
            $msg = $e->getMessage();
        }
    }
}


$ds_kho = [];
$sql_kho = $conn->query("SELECT * FROM kho");

while($row = $sql_kho->fetch_assoc()){
    $ds_kho[] = $row;
}



$ds_vattu = [];
$sql_vattu = $conn->query("SELECT * FROM vat_tu");

while($row = $sql_vattu->fetch_assoc()){
    $ds_vattu[] = $row;
}


$auto_so = "LC-" . date("Ymd") . "-" . rand(1000,9999);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Luân chuyển vật tư</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="wrapper d-flex">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1 p-4 bg-light">

        <?php if($msg != ""){ ?>
            <div class="alert alert-danger">
                <?php echo $msg; ?>
            </div>
        <?php } ?>

        <?php if(isset($_GET['success'])){ ?>
            <div class="alert alert-success">
                Luân chuyển thành công
            </div>
        <?php } ?>

        <div class="bg-white p-4 rounded shadow">

            <h4 class="mb-4">Phiếu luân chuyển vật tư</h4>

            <form method="POST">

                <input type="hidden" name="action" value="add_transfer">

                <div class="row mb-3">

                    <div class="col-md-4">
                        <label>Số phiếu</label>
                        <input type="text"
                               name="so_phieu"
                               class="form-control"
                               value="<?php echo $auto_so; ?>">
                    </div>

                    <div class="col-md-4">
                        <label>Ngày lập</label>
                        <input type="datetime-local"
                               name="ngay_lap"
                               class="form-control"
                               value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>

                    <div class="col-md-4">
                        <label>Kho xuất</label>
                        <select name="id_kho_from" class="form-select">
                            <?php foreach($ds_kho as $k){ ?>
                                <option value="<?php echo $k['id_kho']; ?>">
                                    <?php echo $k['ten_kho']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-4 mt-3">
                        <label>Kho nhận</label>
                        <select name="id_kho_to" class="form-select">
                            <?php foreach($ds_kho as $k){ ?>
                                <option value="<?php echo $k['id_kho']; ?>">
                                    <?php echo $k['ten_kho']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                </div>


                <h5>Danh sách vật tư</h5>

                <table class="table table-bordered" id="tblVT">
                    <thead>
                        <tr>
                            <th>Vật tư</th>
                            <th>Số lượng</th>
                            <th>Xóa</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                <select name="id_vat_tu[]" class="form-select">
                                    <?php foreach($ds_vattu as $vt){ ?>
                                        <option value="<?php echo $vt['id_vat_tu']; ?>">
                                            <?php echo $vt['ten_vat_tu']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </td>

                            <td>
                                <input type="number"
                                       name="so_luong[]"
                                       class="form-control"
                                       value="1"
                                       min="1">
                            </td>

                            <td>
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        onclick="removeRow(this)">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="button"
                        class="btn btn-primary mb-3"
                        onclick="addRow()">
                    + Thêm vật tư
                </button>

                <br>

                <button class="btn btn-success">
                    Lưu luân chuyển
                </button>

            </form>

        </div>
    </div>
</div>


<script>
function addRow(){
    let table = document.querySelector("#tblVT tbody");
    let newRow = table.rows[0].cloneNode(true);

    newRow.querySelector("input").value = 1;

    table.appendChild(newRow);
}

function removeRow(btn){
    let table = document.querySelector("#tblVT tbody");

    if(table.rows.length > 1){
        btn.closest("tr").remove();
    }else{
        alert("Phải có ít nhất 1 vật tư");
    }
}
</script>

</body>
</html>
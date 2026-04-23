<?php
include_once("db_connect.php");
session_start();
if(!isset($_SESSION["LOGGIN"]) && $_SESSION("LOGGIN") != true){
    header('location:login.php');
    exit();
}

if(isset($_POST['hanhdong']) && $_POST['hanhdong'] =='sua'){
    $id_hsx = $_POST['id_hsx'];
    $ten = $_POST['ten_hang_san_xuat'];
    $insert = "update from hang_san_xuat set ten_hang_san_xuat = '$ten' where id_hsx = '$id_hsx'";
    if($conn->query($insert)){
        header('location:hangsanxuat.php');
        exit();
    }
    else{
        echo "Loi: " . $conn->error;
    }
}
else if(isset($_POST['hanhdong']) && $_POST['hanhdong'] == 'xoa'){
    $id_hsx = $_POST['id_hsx'];
    $ten = $_POST['ten_hang_san_xuat'];
    $delete = "delete from hang_san_xuat where id_hsx = '$id_hsx'";
    if($conn->query($delete)){
        header('location:hangsanxuat.php');
        exit();
    }
    else{
        echo "Loi: " . $conn->error;
    }
}
$timkiem = '';
    if(isset($_POST['btn_timkiem'])){
        $timkiem = $_POST['timkiem'];
        $sqltimkiem = "select * from hang_san_xuat where timkiem like '%$ten_hang_san_xuat%' or timkiem = '$id_hsx'";
    }
    else{
        $sqltimkiem = "select * from hang_san_xuat";
    }
    $result = $conn->query($sqltimkiem);
    if(isset($_POST['submit'])){
        $id_hsx = $_POST['id_hsx'];
        $ten = $_POST['ten_hang_san_xuat'];
        $check = $conn->query("select * from hang_san_xuat where id_hsx = '$id_hsx'");
        if($check->num_rows >0){
            $msg = "looix, da ton tai du lieu";
        }
        else{
            $sql= "insert hang_san_xuat values('$id_hsx', '$ten_hang_san_xuat')";
            if($conn->query($sql)){
                header('location:hangsanxuat.php');
                exit();
            }
            else{
                echo "loi: " . $conn->error;
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
    <title>hang san xuat</title>
</head>
<body>
    <?php include 'includes/header.php' ?>


    <div class="wrapper d-flex">
        <?php include 'includes/sidebar.php'?>
        <div class="main-content flex-grow-1 p-4 bg-light">
            <?php if (isset($msg) && $msg !== ''): ?>
                <div class="alert alert-danger alert-dismissible face show" role="alert">
                    <strong>thong bao</strong> <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form method="post" class="d-flex w-75 gap-2">
                    <input type="text" name="timkiem" class="form-control" placeholder="Tifm kiem theo ma, ten,.." value= "<?php echo htmlspecialchars($timkiem); ?>">
                    <button class="btn_timkiem" class="btn btn-outline-dark text-nowrap">TIM KIEM</button>
                </form>
                <button class="btn btn-success text-nowrap" onclick="moboxthem()">+ Thêm hãng sản xuất</button>
            </div>
            <div class="bg-white p-3 border rounded">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 200px"> ID hãng sản xuât</th>
                            <th>Tên hãng sản xuất</th>
                            <th stype="width: 150px; text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
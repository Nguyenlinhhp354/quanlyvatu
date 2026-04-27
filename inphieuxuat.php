<?php
include 'db_connect.php';
$id = $_GET['id'];
$sqlPhieu = "
SELECT px.*, k.ten_kho, d.ten_du_an, n.ho_ten
FROM phieu_xuat_kho px
JOIN kho k ON px.id_kho = k.id_kho
JOIN du_an d ON px.id_du_an = d.id_du_an
JOIN nguoi_dung n ON px.id_nguoi_lap = n.id_nguoi_dung
WHERE px.id_phieu_xuat = '$id'
";
$phieu = $conn->query($sqlPhieu)->fetch_assoc();
$sqlCT = "
SELECT ct.*, vt.ten_vat_tu
FROM chi_tiet_xuat_kho ct
JOIN vat_tu vt ON ct.id_vat_tu = vt.id_vat_tu
WHERE ct.id_phieu_xuat = '$id'
";
$chitiet = $conn->query($sqlCT);
?>
<!DOCTYPE html>
<html>
<head>
    <title>In phiếu xuất</title>
    <style>
        body{
            font-family: Arial;
            margin:40px;
        }

        table{
            width:100%;
            border-collapse: collapse;
            margin-top:20px;
        }

        table, th, td{
            border:1px solid black;
        }

        th, td{
            padding:8px;
            text-align:center;
        }

        .title{
            text-align:center;
            font-size:22px;
            font-weight:bold;
        }
    </style>
</head>
<body>

<div class="title">
    PHIẾU XUẤT KHO
</div>

<p><b>Số phiếu:</b> <?php echo $phieu['so_phieu']; ?></p>
<p><b>Ngày xuất:</b> <?php echo $phieu['ngay_xuat']; ?></p>
<p><b>Kho:</b> <?php echo $phieu['ten_kho']; ?></p>
<p><b>Dự án:</b> <?php echo $phieu['ten_du_an']; ?></p>
<p><b>Người lập:</b> <?php echo $phieu['ho_ten']; ?></p>
<p><b>Người nhận:</b> <?php echo $phieu['nguoi_nhan']; ?></p>
<p><b>Lý do:</b> <?php echo $phieu['ly_do_xuat']; ?></p>

<table>
    <tr>
        <th>STT</th>
        <th>Tên vật tư</th>
        <th>Số lượng</th>
    </tr>

    <?php
    $stt = 1;
    while($row = $chitiet->fetch_assoc()){
    ?>
        <tr>
            <td><?php echo $stt++; ?></td>
            <td><?php echo $row['ten_vat_tu']; ?></td>
            <td><?php echo $row['so_luong']; ?></td>
        </tr>
    <?php } ?>
</table>

<br><br>

<div style="display:flex; justify-content:space-between;">
    <div>
        Người lập phiếu
        <br><br><br>
        (Ký tên)
    </div>

    <div>
        Người nhận
        <br><br><br>
        (Ký tên)
    </div>
</div>

<script>
    window.print();
</script>
</body>
</html>
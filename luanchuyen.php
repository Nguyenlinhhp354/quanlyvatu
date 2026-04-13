<?php

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Luân chuyển - Thịnh Tiến</title>
    <style>
        /* Giữ nguyên phần CSS cũ của bạn */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { display: flex; flex-direction: column; height: 100vh; }

        header {
            display: flex;
            align-items: center;
            background-color: #343a40;
            color: white;
            height: 60px;
            padding: 0 20px;
        }
        
        .header-left { flex: 1; display: flex; justify-content: flex-start; }
        .header-center { flex: 1; display: flex; justify-content: center; font-size: 22px; font-weight: bold; }
        .header-right { flex: 1; display: flex; justify-content: flex-end; }

        .btn-toggle {
            background: transparent; border: none; color: white;
            font-size: 24px; cursor: pointer;
        }

        .wrapper { display: flex; flex: 1; overflow: hidden; }

        .sidebar {
            width: 250px;
            background-color: #212529;
            color: white;
            transition: margin-left 0.3s ease;
        }
        .sidebar.an-di { margin-left: -250px; }
        
        .sidebar ul { list-style: none; padding: 20px 0; }
        .sidebar ul li a { color: white; text-decoration: none; padding: 15px 20px; display: block; }
        .sidebar ul li a:hover { background: #494e53; }

        .main-content {
            flex: 1; display: flex; flex-direction: column; background: #f4f6f9;
        }
        main { flex: 1; padding: 20px; overflow-y: auto; }
        footer { background: white; text-align: center; padding: 15px; border-top: 1px solid #ccc; }

        /* Thêm một chút style cho bảng dữ liệu */
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn-add { background: #28a745; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <button class="btn-toggle" onclick="dongMoSidebar()">☰</button>
        </div>
        <div class="header-center">
            Hệ Thống Thịnh Tiến
        </div>
        <div class="header-right">
            Xin chào, Vũ Xuân Cường
        </div>
    </header>

    <div class="wrapper">
        <aside class="sidebar" id="thanhMenu">
            <ul>
                <li><a href="index.php">Tổng quan</a></li>
                <li><a href="#">Quản lý Nhập kho</a></li>
                <li><a href="xuatkho.php">Quản lý Xuất kho</a></li>
                <li><a href="luanchuyen.php" style="background: #494e53;">Quản lý Luân chuyển</a></li>
                <li><a href="#">Báo cáo</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <main>
                <h2>Giao diện Quản lý Luân chuyển</h2>
                <br>
                <button class="btn-add">+ Tạo phiếu luân chuyển mới</button>

                <table>
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Ngày luân chuyển</th>
                            <th>Từ kho</th>
                            <th>Đến kho</th>
                            <th>Người phụ trách</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>LC-001</td>
                            <td>13/04/2026</td>
                            <td>Kho A</td>
                            <td>Kho B</td>
                            <td>Vũ Xuân Cường</td>
                            <td><a href="#">Sửa</a> | <a href="#">In</a></td>
                        </tr>
                    </tbody>
                </table>
            </main>
            
            <footer>
                
            </footer>
        </div>
    </div>

    <script>
        function dongMoSidebar() {
            var sidebar = document.getElementById("thanhMenu");
            sidebar.classList.toggle("an-di");
        }
    </script>

</body>
</html>
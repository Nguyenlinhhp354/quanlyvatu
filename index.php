<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giao Diện My System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { display: flex; flex-direction: column; height: 100vh; }

        /* HEADER: Chia 3 phần đều nhau */
        header {
            display: flex;
            align-items: center;
            background-color: #343a40;
            color: white;
            height: 60px;
            padding: 0 20px;
        }
        
        /* Căn chỉnh bên trong 3 phần của Header */
        .header-left { flex: 1; display: flex; justify-content: flex-start; }
        .header-center { flex: 1; display: flex; justify-content: center; font-size: 22px; font-weight: bold; }
        .header-right { flex: 1; display: flex; justify-content: flex-end; }

        .btn-toggle {
            background: transparent; border: none; color: white;
            font-size: 24px; cursor: pointer;
        }

        /* KHUNG CHỨA */
        .wrapper { display: flex; flex: 1; overflow: hidden; }

        /* SIDEBAR */
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

        /* NỘI DUNG CHÍNH */
        .main-content {
            flex: 1; display: flex; flex-direction: column; background: #f4f6f9;
        }
        main { flex: 1; padding: 20px; }
        footer { background: white; text-align: center; padding: 15px; border-top: 1px solid #ccc; }
    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <button class="btn-toggle" onclick="dongMoSidebar()">☰</button>
        </div>
        <div class="header-center">
            My System
        </div>
        <div class="header-right">
            Xin chào, Admin
        </div>
    </header>

    <div class="wrapper">
        <aside class="sidebar" id="thanhMenu">
    <ul>
        <li><a href="#">Tổng quan (Dashboard)</a></li>
        <li><a href="#">Quản lý Danh mục & Nhập kho</a></li>
        <li><a href="xuatkho.php" style="background: #494e53;">Quản lý Xuất kho</a></li>
        <li><a href="luanchuyen.php">Quản lý Luân chuyển</a></li>
        <li><a href="#">Kiểm kê & Báo cáo</a></li>
    </ul>
</aside>

        <div class="main-content">
            <main>
                <h2>Trang chủ My System</h2>
                <br>
                <p>Nút ba gạch đã ở bên trái, My System nằm ở giữa và Admin nằm bên phải.</p>
            </main>
            
            <footer>
                &copy; 2026 Bản quyền website
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

<header>
    <div class="header-left">
        <button class="btn-toggle" onclick="dongMoSidebar()">☰</button>
    </div>
    <div class="header-center">
        Quản lý vật tư Thịnh Tiến
    </div>
    <div class="header-right">
        Xin chào, <?php echo $ho_ten; ?>! <!-- Hiển thị tên người dùng lấy từ session -->
    </div>
</header>

<script>
    function dongMoSidebar() {
        var sidebar = document.getElementById("thanhMenu");
        sidebar.classList.toggle("an-di");
    }
</script>
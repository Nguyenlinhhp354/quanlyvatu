<?php
session_start();

// Xóa tất cả các biến session
session_unset();

// Hủy bỏ phiên làm việc
session_destroy();

// Chuyển hướng về trang đăng nhập
header("Location: login.php");
exit();
?>
<?php
// Bắt đầu session
session_start();

// Xóa tất cả dữ liệu session
$_SESSION = array();

// Nếu muốn xóa luôn session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển hướng về trang đăng nhập (hoặc trang chủ)
header("Location: /InfiDorm/");
exit();
?>

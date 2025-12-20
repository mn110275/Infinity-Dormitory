<?php
session_start();
require '../database_connection.php'; // dùng chung database

// Kiểm tra phân quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý InfiDorm >//<</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
  <header class="nav">
    <a href="home.php" class="active"> Trang chủ</a>
    <a href="dashboard.php">Quản lý</a>
    <a href="../change_password.php">Đổi mật khẩu</a>
    <a href="../logout.php" style="margin-left:auto">Đăng xuất</a>
  </header>

  <main class="container">
    <h1>Chào mừng đến với Infinity Dormitory (InfiDorm)</h1>
    <p>Phiên bản demo: lưu tạm dữ liệu trên trình duyệt (localStorage).</p>

    <!-- Cần thêm card: "Quản lý sinh viên đã đăng ký" + "Quản lý cơ sở vật chất" + "Quản lý đóng tiền" -->
    <section class="cards">
      <div class="card">
        <h3>Đăng ký ở KTX</h3>
        <p>Đăng ký khi bạn mới muốn ở KTX.</p>
        <a class="btn" href="../register/register.html">Đến trang đăng ký</a>
      </div>

    </section>
  </main>

  <script src="assets/js/main.js"></script>
</body>
</html>

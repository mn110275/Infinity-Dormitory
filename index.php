<?php
session_start();

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'student':
            header("Location: student/home.php");
            exit;
        case 'manager':
            header("Location: manager/dashboard.php");
            exit;
        case 'admin':
            header("Location: admin/dashboard.php");
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Welcome to InfiDorm >//<</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <header class="nav">
    <a href="index.php" class="active">Trang chủ</a>
    <a href="register/register.html">Đăng ký KTX</a>
    <a href="student/login.php">Đăng nhập Sinh viên</a>
    <a href="manager/login.php">Đăng nhập Quản lý</a>
  </header>

  <main class="container">
    <h1>Chào mừng đến với Infinity Dormitory (InfiDorm)</h1>

    <section class="cards">
      <div class="card">
        <h3>Đăng ký ở KTX</h3>
        <p>Chào mừng các thành viên mới của InfiDorm!</p>
        <p>(Xem mẫu hợp đồng tại <a href="https://drive.google.com/drive/folders/1WF1MWU1eVwYm8Jv1-opbD116Nu3GuSan?usp=sharing">đây</a>)</p>
        <a class="btn" href="register/register.html">Đến trang đăng ký</a>
      </div>

      <div class="card">
        <h3>Đăng nhập Sinh viên</h3>
        <p>Chào mừng các cư dân của InfiDorm!</p>
        <a class="btn" href="student/login.php">Đăng nhập Sinh viên</a>
      </div>

      <div class="card">
        <h3>Quản lý</h3>
        <p>Dành cho quản lý của InfiDorm.</p>
        <a class="btn" href="manager/login.php">Đăng nhập Quản lý</a>
      </div>
    </section>
  </main>

  <script src="assets/js/main.js"></script>
</body>
</html>
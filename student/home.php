<?php
session_start();
require_once __DIR__ . '/../auth/require_role.php';
requireRole('student');

// Lấy thông tin từ session để dùng bên dưới (ghi ra cái Chào mừng + tên [Nguyễn Văn A]!)
$student_name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang chủ Sinh viên</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
  <!-- Header là cái thanh ở đầu ấy, có chữ trang chủ các thứ -->
  <header class="nav">
    <div style="display:flex; gap:12px;">
      <a href="home.php" class="active">Trang chủ</a>
      <a href="dashboard.html">Quản lý thông tin</a>
    </div>
    <div style="margin-left:auto; display:flex; gap:12px; align-items:center;">
      <a href="../logout.php">Đăng xuất</a>
      <a href="../change_password.php">Đổi mật khẩu</a>
      <a href="profile.php">Chào mừng <?php echo htmlspecialchars($student_name); ?>!</a>
    </div>
  </header>

  <main class="container">
    <h1>Xin chào, <?php echo htmlspecialchars($student_name); ?>!</h1>
    <p>Đây là trang chính của sinh viên sau khi đăng nhập thành công.</p>

    <section class="cards">
      <div class="card">
        <h3>Quản lý dịch vụ KTX</h3>
        <p>Ở đây sinh viên có thể quản lý dịch vụ: đặt phòng, thanh toán, đăng ký tiện ích,…</p>
        <a class="btn" href="#">Đi tới quản lý dịch vụ</a>
      </div>
    </section>
  </main>
</body>
</html>

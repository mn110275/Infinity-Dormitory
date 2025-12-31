<?php
session_start();
require_once __DIR__ . '/../auth/require_role.php';
requireRole('student');

$student_name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Trang chủ Sinh viên</title>
  <link rel="stylesheet" href="../css/public.css" />
</head>
<body>
  <header class="nav">
    <div style="display:flex; gap:12px;">
      <a href="home.php" class="active">Trang chủ</a>
      <a href="dashboard.php">Quản lý thông tin</a>
    </div>
    <div style="margin-left:auto; display:flex; gap:12px; align-items:center;">
      <a href="../logout.php" style="margin-left:auto">Đăng xuất</a>
      <p>Chào mừng <?php echo htmlspecialchars($student_name); ?>!</p>
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

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
    <a href="home.php" style="font-style: italic; font-size: 30px;">Infinity Dormitory</a>
    <p style="font-size: 20px; font-style: italic">Chào mừng <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</p>
    <a href="dashboard.php" style="margin-left:auto">Danh mục quản lý</a>
    <a href="../logout.php">Đăng xuất</a>
  </header>

  <main class="container">
    <h1>Xin chào, <?php echo htmlspecialchars($student_name); ?>!</h1>

  </main>
</body>
</html>

<?php
  session_start();
  if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
      header('Location: login.php');
      exit;
  }
  $menuItems = [
    'students'      => [
      'title' => 'Sinh viên',
      'desc' => 'Tổng quan',
      'js' => 'js/students.js',
      'css' => 'css/students.css'
    ],
    'facilities'      => [
      'title' => 'Cơ sở vật chất',
      'desc' => 'Quản lý CSVC',
      'js' => 'js/facilities.js',
      'css' => 'css/facilities.css'
    ],
    'revenue'       => [
      'title' => 'Thu phí',
      'desc' => 'Điện, nước và các khoản thu khác',
      'js' => 'js/revenue.js',
      'css' => 'css/revenue.css'
    ],
    'notifications' => [
      'title' => 'Thông báo',
      'desc' => 'Gửi tin nhắn đến sinh viên',
      'js' => 'js/notifications.js',
      'css' => 'css/notifications.css'
    ],
    'profile'       => [
      'title' => 'Hồ sơ cá nhân',
      'desc' => 'Quản lý thông tin tài khoản',
      'js' => 'js/profile.js',
      'css' => 'css/profile.css'
    ]
  ];
  
  $view = $_GET['view'] ?? 'students';
  $view = array_key_exists($view, $menuItems) ? $view : 'students';
  $current = $menuItems[$view];
?>

<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title>Quản lý InfiDorm - <?= $current['title'] ?></title>
    <link rel="stylesheet" href="../css/public.css" />
    <?php if (isset($current['css']))
      echo "<link rel='stylesheet' href='{$current['css']}'>";
    ?>
  </head>
  <body>
    <header class="nav">
      <a href="home.php" style="font-style: italic; font-size: 30px;">Infinity Dormitory</a>
      <p style="font-size: 20px; font-style: italic">Chào mừng <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</p>
      <a href="dashboard.php" class="active" style="margin-left:auto">Danh mục quản lý</a>
      <a href="../logout.php">Đăng xuất</a>
    </header>
    <main class="container">
      <aside class="sidebar">
        <div class="sidebar-header">
          <h2>Danh mục quản lý KTX</h2>
        </div>
        <nav class="sidebar-list">
          <?php foreach ($menuItems as $key => $item): ?>
          <a href="?view=<?= $key ?>" class="sidebar-item <?= $view === $key ? 'active' : '' ?>">
            <div class="item-title"><?= $item['title'] ?></div>
            <div class="item-desc"><?= $item['desc'] ?></div>
          </a>
          <?php endforeach; ?>
        </nav>
      </aside>
      <section class="content-area">
          <?php include "views/{$view}.php"; ?>
      </section>
    </main>
    <?php
      if (isset($current['js']))
        echo "<script src='{$current['js']}'></script>"; ?>
  </body>
</html>
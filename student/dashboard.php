<?php
  session_start();
  require_once __DIR__ . '/../auth/require_role.php';
  requireRole('student');
  
  $menuItems = [
      'profile' => [
          'title' => 'Hồ sơ cá nhân',
          'desc'  => 'Thông tin & Mật khẩu',
          'js'    => 'js/profile.js',
          'css'   => 'css/profile.css'
      ],
      'my_room' => [
          'title' => 'Phòng của tôi',
          'desc'  => 'Thành viên & Thiết bị',
          'js'    => 'js/my_room.js',
          'css'   => 'css/my_room.css'
      ],
      'bills' => [
          'title' => 'Các khoản phí',
          'desc'  => 'Hóa đơn điện, nước',
          'js'    => 'js/bills.js',
          'css'   => 'css/bills.css'
      ],
      'notifications' => [
          'title' => 'Thông báo',
          'desc'  => 'Tin tức từ quản lý',
          'js'    => 'js/notifications.js',
          'css'   => 'css/notifications.css'
      ],
      'requests' => [
          'title' => 'Hỗ trợ',
          'desc'  => 'Gửi yêu cầu sửa chữa',
          'js'    => 'js/requests.js',
          'css'   => 'css/requests.css'
      ]
  ];
  
  $view = $_GET['view'] ?? 'profile';
  $view = array_key_exists($view, $menuItems) ? $view : 'profile';
  $current = $menuItems[$view];
?>

<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>InfiDorm - <?= $current['title'] ?></title>
    <link rel="stylesheet" href="../css/public.css" />
    <?php if (isset($current['css'])): ?>
    <link rel="stylesheet" href="<?= $current['css'] ?>" />
    <?php endif; ?>
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
          <h3>Danh mục</h3>
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
          echo "<script src='{$current['js']}'></script>";
      ?>
  </body>
</html>
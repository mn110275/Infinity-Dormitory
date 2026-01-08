<?php
session_start();
require_once __DIR__ . '/../auth/require_role.php';
requireRole('admin');

$menuItems = [
    'student' => [
        'title' => 'Sinh viên',
        'desc' => 'Danh sách sinh viên',
        'js' => 'js/student.js',
        'css' => ['css/modal-info.css', 'css/student.css'],
        'view' => 'students.php'
    ],
    'application' => [
        'title' => 'Đơn đăng ký',
        'desc' => 'Xét duyệt sinh viên',
        'js' => 'js/application.js',
        'css' => ['css/modal-info.css', 'css/application.css'],
        'view' => 'applications.php'
    ],
    'manager' => [
        'title' => 'Quản lý',
        'desc' => 'Tài khoản quản lý',
        'js' => 'js/manager.js',
        'css' => null,
        'view' => 'managers.php'
    ],
    'unit' => [
        'title' => 'Đơn giá',
        'desc' => 'Điện, nước của các tòa',
        'js' => 'js/unit.js',
        'css' => 'css/unit.css', 
        'view' => 'unit.php'
    ],
    'semester' => [
        'title' => 'Học kỳ',
        'desc' => 'Thời hạn của kỳ học',
        'js' => 'js/semester.js',
        'css' => 'css/semester.css', 
        'view' => 'semester.php'
    ],
];

$tab = $_GET['tab'] ?? 'student';

if (!array_key_exists($tab, $menuItems)) {
    $tab = 'student';
}

$current = $menuItems[$tab];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin InfiDorm - <?= $current['title'] ?></title>
  <link rel="stylesheet" href="../css/public.css" />
  <link rel="stylesheet" href="css/admin.css" />
  <link rel="stylesheet" href="css/modal.css" />
  <?php 
  if (!empty($current['css'])): 
      $cssList = is_array($current['css']) ? $current['css'] : [$current['css']];
      foreach ($cssList as $cssFile): 
  ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>" />
  <?php 
      endforeach; 
  endif; 
  ?>
</head>

<body>

<header class="nav">
  <a href="home.php" style="font-style: italic; font-size: 30px;">Infinity Dormitory</a>
  <p style="font-size: 20px; font-style: italic">Chào mừng <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</p>
  <a href="dashboard.php" class="active" style="margin-left:auto">Admin Dashboard</a>
  <a href="../logout.php">Đăng xuất</a>
</header>

<main class="container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <h3>Danh mục quản lý KTX</h3>
      <small>Admin</small>
    </div>

    <nav class="sidebar-list">
      <?php foreach ($menuItems as $key => $item): ?>
          <a href="dashboard.php?tab=<?= urlencode($key) ?>" class="sidebar-item <?= $tab === $key ? 'active' : '' ?>">
              <div>
                  <div class="item-title"><?= htmlspecialchars($item['title']) ?></div>
                  <div class="item-desc"><?= htmlspecialchars($item['desc']) ?></div>
              </div>
          </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <section class="content-area">
    <div class="content-inner">
      <?php include "views/" . $current['view']; ?>
    </div>
  </section>
</main>

<!-- JS -->
 <?php if (!empty($current['js'])): ?>
    <script src="<?= htmlspecialchars($current['js']) ?>"></script>
<?php endif; ?>

</body>
</html>
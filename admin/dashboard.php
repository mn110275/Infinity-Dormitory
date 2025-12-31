<?php
session_start();
require_once __DIR__ . '/../auth/require_role.php';
requireRole('admin');

$tab = $_GET['tab'] ?? 'student';
$allowedTabs = ['student', 'application', 'manager', 'unit'];
if (!in_array($tab, $allowedTabs)) {
    $tab = 'student';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin InfiDorm</title>
  <link rel="stylesheet" href="../css/public.css" />
  <link rel="stylesheet" href="../css/manager.css" />
  <link rel="stylesheet" href="css/admin.css" />
  <link rel="stylesheet" href="css/modal.css" />
</head>

<body>

<header class="nav">
  <a href="home.php">Trang chủ</a>
  <a href="dashboard.php" class="active">Admin</a>
</header>

<main class="admin-container">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h3>Danh mục quản lý KTX</h3>
      <small>Admin</small>
    </div>

    <nav class="sidebar-list">
      <a href="dashboard.php?tab=student"
         class="sidebar-item <?= $tab === 'student' ? 'active' : '' ?>">
        <div>
          <div class="item-title">Sinh viên</div>
          <div class="item-desc">Danh sách sinh viên</div>
        </div>
      </a>

      <a href="dashboard.php?tab=application"
         class="sidebar-item <?= $tab === 'application' ? 'active' : '' ?>">
        <div>
          <div class="item-title">Đơn đăng ký</div>
          <div class="item-desc">Xét duyệt sinh viên</div>
        </div>
      </a>

      <a href="dashboard.php?tab=manager"
         class="sidebar-item <?= $tab === 'manager' ? 'active' : '' ?>">
        <div>
          <div class="item-title">Quản lý</div>
          <div class="item-desc">Tài khoản quản lý</div>
        </div>
      </a>

      <a href="dashboard.php?tab=unit"
         class="sidebar-item <?= $tab === 'unit' ? 'active' : '' ?>">
        <div>
          <div class="item-title">Quản lý đơn giá</div>
          <div class="item-desc">Điện, nước của các tòa</div>
        </div>
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="../logout.php" class="btn ghost">Đăng xuất</a>
    </div>
  </aside>

  <!-- CONTENT -->
  <section class="content-area">
    <div class="content-inner">

      <?php
        switch ($tab) {
          case 'application':
            include 'views/applications.php';
            break;

          case 'manager':
            include 'views/managers.php';
            break;

          case 'unit':
            include 'views/unit.php';
            break;

          default:
            include 'views/students.php';
        }
      ?>

    </div>
  </section>

</main>

<!-- JS -->
<script src="js/application.js"></script>
<script src="js/manager.js"></script>
<script src="js/unit.js"></script>

</body>
</html>
<?php
// manager/dashboard.php - Main shell
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['block'])) {
    die('Lỗi: Không xác định được tòa quản lý. Vui lòng đăng nhập lại.');
}

$view = $_GET['view'] ?? 'students';
$allowedViews = ['students', 'facility', 'revenue', 'notifications', 'profile'];

if (!in_array($view, $allowedViews)) {
    $view = 'students';
}

$viewFile = "views/{$view}.php";
if (!file_exists($viewFile)) {
    die("View file not found: {$viewFile}");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý InfiDorm</title>
  <link rel="stylesheet" href="../css/manager.css" />
</head>
<body>
  <header class="nav">
    <a href="home.php">Trang chủ</a>
    <a href="dashboard.php" class="active">Quản lý</a>
    <span style="margin-left: auto; color: #64748b;">
      <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?> - Tòa <?= htmlspecialchars($_SESSION['block'] ?? 'N/A') ?>
    </span>
  </header>

  <main class="admin-container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Danh mục quản lý KTX</h3>
        <small id="sidebar-sub">Admin</small>
      </div>

      <nav id="sidebarList" class="sidebar-list">
        <a href="?view=students" class="sidebar-item <?= $view === 'students' ? 'active' : '' ?>">
          <div>
            <div class="item-title">Danh sách sinh viên</div>
            <div class="item-desc">Xem danh sách từng phòng</div>
          </div>
        </a>
        <a href="?view=facility" class="sidebar-item <?= $view === 'facility' ? 'active' : '' ?>">
          <div>
            <div class="item-title">Cơ sở vật chất</div>
            <div class="item-desc">Quản lý thiết bị</div>
          </div>
        </a>
        <a href="?view=revenue" class="sidebar-item <?= $view === 'revenue' ? 'active' : '' ?>">
          <div>
            <div class="item-title">Quản lý thu phí</div>
            <div class="item-desc">Điện, nước, chi phí khác</div>
          </div>
        </a>
        <a href="?view=notifications" class="sidebar-item <?= $view === 'notifications' ? 'active' : '' ?>">
          <div>
            <div class="item-title">Gửi thông báo</div>
            <div class="item-desc">Thông báo đến sinh viên</div>
          </div>
        </a>
        <a href="?view=profile" class="sidebar-item <?= $view === 'profile' ? 'active' : '' ?>">
          <div>
            <div class="item-title">Thông tin cá nhân</div>
            <div class="item-desc">Xem và chỉnh sửa hồ sơ</div>
          </div>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button id="logoutAdmin" class="btn ghost">Đăng xuất</button>
      </div>
    </aside>

    <section id="adminContent" class="content-area">
      <div id="contentInner" class="content-inner">
        <?php include $viewFile; ?>
      </div>
    </section>

    <!-- Viewer panel (for facility) -->
    <?php if ($view === 'facility'): ?>
    <aside id="viewer" class="viewer" aria-hidden="true">
      <button id="viewerClose" class="viewer-close" title="Đóng">✕</button>
      <div class="viewer-body">
        <h3 id="viewerTitle">Phòng - Vật dụng</h3>
        <div id="viewerMain" class="viewer-main">
          <img id="viewerMainImg" src="" alt="Ảnh vật dụng" />
        </div>
        <div id="viewerThumbs" class="viewer-thumbs"></div>
        <div id="viewerLinks" class="viewer-links"></div>
      </div>
    </aside>
    <?php endif; ?>
  </main>

  <script>
    // Logout button
    document.getElementById('logoutAdmin')?.addEventListener('click', () => {
      location.href = '../index.html';
    });
  </script>

  <?php
  // Load view-specific JS
  $jsFiles = [
    'students' => 'js/student_list.js',
    'facility' => 'js/facility.js',
    'revenue' => 'js/revenue.js',
    'notifications' => 'js/notifications.js',
    'profile' => 'js/profile.js'
  ];
  
  if (isset($jsFiles[$view]) && file_exists($jsFiles[$view])) {
    echo "<script src='{$jsFiles[$view]}'></script>";
  }
  ?>
</body>
</html>
<?php
// student/dashboard.php - Main shell cho Sinh viên
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$view = $_GET['view'] ?? 'profile';
$allowedViews = ['profile', 'my_room', 'bills', 'requests', 'notifications'];
if (!in_array($view, $allowedViews)) {
    $view = 'profile';
}
$viewFile = "views/{$view}.php";

if (!file_exists($viewFile)) {
    if ($view === 'profile') {
        die("Lỗi: File view hồ sơ (views/profile.php) không tồn tại.");
    } else {
        $view = 'profile';
        $viewFile = "views/profile.php";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sinh viên | InfiDorm</title>
    <link rel="stylesheet" href="../css/manager.css" />
    <link rel="stylesheet" href="../css/student.css" />
</head>
<body>
    <header class="nav">
        <a href="home.php">Trang chủ</a>
        <a href="dashboard.php" class="active">Quản lý thông tin</a>
        <span style="margin-left: auto; color: #64748b;">
            SV: <?= htmlspecialchars($_SESSION['name'] ?? 'Sinh viên') ?> 
            <?php if(isset($_SESSION['room_id'])): ?>
                - Phòng <?= htmlspecialchars($_SESSION['room_id']) ?>
            <?php endif; ?>
        </span>
    </header>

    <main class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Danh mục quản lý</h3>
            </div>

            <nav id="sidebarList" class="sidebar-list">
                <a href="?view=profile" class="sidebar-item <?= $view === 'profile' ? 'active' : '' ?>">
                    <div>
                        <div class="item-title">Hồ sơ cá nhân</div>
                        <div class="item-desc">Xem và chỉnh sửa thông tin, mật khẩu</div>
                    </div>
                </a>
                
                <a href="?view=my_room" class="sidebar-item <?= $view === 'my_room' ? 'active' : '' ?>">
                    <div>
                        <div class="item-title">Phòng của tôi</div>
                        <div class="item-desc">Thông tin phòng sinh viên</div>
                    </div>
                </a>
                
                <a href="?view=bills" class="sidebar-item <?= $view === 'bills' ? 'active' : '' ?>">
                    <div>
                        <div class="item-title">Các khoản phí</div>
                        <div class="item-desc">Xem các khoản phí của sinh viên</div>
                    </div>
                </a>

                <a href="?view=notifications" class="sidebar-item <?= $view === 'notifications' ? 'active' : '' ?>">
                    <div>
                        <div class="item-title">Thông báo</div>
                        <div class="item-desc">Xem các thông báo mới</div>
                    </div>
                </a>

                <a href="?view=requests" class="sidebar-item <?= $view === 'requests' ? 'active' : '' ?>">
                    <div>
                        <div class="item-title">Gửi yêu cầu hỗ trợ</div>
                    </div>
                </a>
            </nav>
        </aside>

        <section id="adminContent" class="content-area">
            <div id="contentInner" class="content-inner">
                <?php include $viewFile; ?>
            </div>
        </section>
    </main>

    <script>
        // Xử lý nút đăng xuất
        document.getElementById('logoutStudent')?.addEventListener('click', () => {
            if(confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                location.href = '../logout.php'; // Hoặc đường dẫn logout của bạn
            }
        });
    </script>

    <?php
    // Load file JS tương ứng với từng view nếu cần
    $jsFiles = [
        'profile' => 'js/profile.js',
        'my_room' => 'js/my_room.js',
        'requests' => 'js/requests.js',
        'notifications' => 'js/notifications.js'
    ];
    
    if (isset($jsFiles[$view]) && file_exists($jsFiles[$view])) {
        echo "<script src='{$jsFiles[$view]}'></script>";
    }
    ?>
</body>
</html>
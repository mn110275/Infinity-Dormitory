<?php
session_start();
require '../database_connection.php';
require_once __DIR__ . '/../auth/require_role.php';
requireRole('manager');

// Đảm bảo lấy đúng mã tòa từ session
$managerBlock = $_SESSION['managerBlock'] ?? $_SESSION['block'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InfiDorm | Home</title>
  <link rel="stylesheet" href="../css/public.css" />
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    /* Ép độ đậm cho Google Sans khi vào Dashboard */
    h1, h3, .badge, strong, b, .nav a {
        font-weight: 700 !important;
    }

    /* Hero Section */
    .hero-banner {
        height: 350px;
        background: linear-gradient(rgba(30, 41, 59, 0.7), rgba(30, 41, 59, 0.7)), 
                    url('https://images.unsplash.com/photo-1555854877-bab0e564b8d5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
    }
    .hero-content h1 { font-size: 42px; margin: 0; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
    .hero-content p { font-size: 20px; opacity: 0.9; margin-top: 10px; font-weight: 400 !important; }

    /* Layout 2 cột */
    .menu-container {
        max-width: 1000px; /* Thu nhỏ chiều rộng để 2 cột trông cân đối hơn */
        margin: -50px auto 50px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Ép 2 cột */
        gap: 30px;
    }

    .menu-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border);
    }
    .menu-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 35px rgba(102, 126, 234, 0.2);
    }
    .card-img {
        height: 200px;
        background-size: cover;
        background-position: center;
    }
    .card-body { padding: 30px; }
    .card-body h3 { margin: 0; font-size: 24px; color: var(--text); }
    .card-body p { color: #64748b; margin-top: 10px; line-height: 1.6; font-weight: 400 !important; }
    
    .badge {
        display: inline-block;
        padding: 6px 14px;
        background: #f1f5f9;
        color: var(--primary);
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 12px;
    }

    @media (max-width: 768px) {
        .menu-container { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <header class="nav">
    <a href="home.php" style="font-style: italic; font-size: 30px;">Infinity Dormitory</a>
    <p style="font-size: 20px; font-style: italic">Chào mừng <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</p>
    <a href="dashboard.php" style="margin-left:auto">Danh mục quản lý</a>
    <a href="../logout.php">Đăng xuất</a>
  </header>

  <section class="hero-banner">
    <div class="hero-content">
        <p>Hệ thống quản lý ký túc xá</p>
        <h1>Tòa nhà <?= htmlspecialchars($managerBlock) ?></h1>
    </div>
  </section>

  <main class="menu-container">
    <a href="dashboard.php?view=profile" class="menu-card">
        <div class="card-img" style="background-image: url('https://images.unsplash.com/photo-1523240715639-99a8086f734d?auto=format&fit=crop&w=600&q=80');"></div>
        <div class="card-body">
            <span class="badge">Tài khoản</span>
            <h3>Thông tin cá nhân</h3>
            <p>Xem và cập nhật thông tin liên hệ, số điện thoại, địa chỉ và quản lý hồ sơ quản lý của bạn.</p>
        </div>
    </a>

    <a href="dashboard.php" class="menu-card">
        <div class="card-img" style="background-image: url('https://images.unsplash.com/photo-1454165833767-0274b27f28a0?auto=format&fit=crop&w=600&q=80');"></div>
        <div class="card-body">
            <span class="badge">Hệ thống</span>
            <h3>Danh mục quản lý</h3>
            <p>Truy cập trung tâm điều khiển để quản lý hợp đồng, thông báo, sinh viên và cơ sở vật chất tòa nhà.</p>
        </div>
    </a>
  </main>
</body>
</html>
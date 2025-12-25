<?php
session_start();
require '../database_connection.php';

// Kiểm tra phân quyền hiện tại
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: student_login.php?error=unauthorized");
    exit;
}

// Lấy thông tin từ session để dùng bên dưới (ghi ra cái Chào mừng + tên [Nguyễn Văn A]!)
$student_name = $_SESSION['name'];

// Lấy thông tin cơ bản của sinh viên từ database
$user_id = $_SESSION['user_id'];
$student_info = null;

$sql = "
    SELECT s.STD_ID, s.STD_NAME, s.STD_PHONE, u.EMAIL
    FROM STUDENT s
    JOIN USERS u ON s.USER_ID = u.USER_ID
    WHERE s.USER_ID = '$user_id'
";

$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) == 1) {
    $student_info = mysqli_fetch_assoc($result);
}
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
      <a href="dashboard.php">Quản lý thông tin</a>
    </div>
    <div style="margin-left:auto; display:flex; gap:12px; align-items:center;">
      <a href="../logout.php">Đăng xuất</a>
      <a href="../change_password.php">Đổi mật khẩu</a>
      <a href="profile.php">Chào mừng <?php echo htmlspecialchars($student_name); ?>!</a>
    </div>
  </header>

  <!-- <main class="container">
    <h1>Xin chào, <?php echo htmlspecialchars($student_name); ?>!</h1>
    <p>Đây là trang chính của sinh viên sau khi đăng nhập thành công.</p>

    <section class="cards">
      <div class="card">
        <h3>Quản lý dịch vụ KTX</h3>
        <p>Ở đây sinh viên có thể quản lý dịch vụ: đặt phòng, thanh toán, đăng ký tiện ích,…</p>
        <a class="btn" href="#">Đi tới quản lý dịch vụ</a>
      </div>
    </section>
  </main> -->

  <main class="container">
    <!-- Hàng 1: Quick Actions & Announcements -->
    <div class="cards">
      <section id="quick-actions" class="card">
        <h1>Quick Actions</h1>
        <div class="actions">
          <button class="btn">Báo hỏng CSVC</button>
          <button class="btn">Thanh toán</button>
        </div>
      </section>

      <section id="announcements" class="card">
        <h1>Thông báo mới nhất</h1>
        <p>Không có thông báo mới</p>
      </section>

      
    </div>

    <!-- Hàng 2: Finance & Profile -->
    <div class="cards" style="margin-top: 20px;">
      <section id="finance" class="card">
        <h1>Trạng thái tài chính</h1>
        <p>Công nợ tháng hiện tại: <strong>0 VND</strong></p>
      </section>

      <section id="profile" class="card">
        <h1>Thông tin sinh viên</h1>
        <?php if ($student_info): ?>
          <div style="margin-top: 1rem;">
            <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student_info['STD_NAME']); ?></p>
            <p><strong>MSSV:</strong> <?php echo htmlspecialchars($student_info['STD_ID']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student_info['EMAIL']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo !empty($student_info['STD_PHONE']) ? htmlspecialchars($student_info['STD_PHONE']) : 'Chưa cập nhật'; ?></p>
          </div>
        <?php else: ?>
          <p>Không thể tải thông tin sinh viên.</p>
        <?php endif; ?>
      </section>

      
    </div>

    <!-- Placeholder CSVC nếu sau này cần -->
    <section id="facilities" hidden></section>
  </main>


</body>
</html>

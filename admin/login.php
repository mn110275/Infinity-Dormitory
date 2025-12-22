<?php
session_start();
require '../database_connection.php';
require '../auth/auth_core.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = loginWithRole($conn, $email, $password, 'admin');

    if (!$user) {
        $error = "Email hoặc mật khẩu không đúng";
    } else {
        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['role']    = $user['USER_ROLE'];
        $_SESSION['email']   = $email;

        header("Location: dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng nhập Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
  <header class="nav">
    <a href="../index.php">Trang chủ</a>
    <a href="../register/register.html">Đăng ký KTX</a>
    <a href="../student/login.php">Đăng nhập Sinh viên</a>
    <a href="../student/login.php">Đăng nhập Quản lý</a>
    <a href="login.php" class="active">Đăng nhập Admin</a>
  </header>

  <main class="container">
    <h1>Đăng nhập Admin InfiDorm</h1>
    <form action="login.php" method="POST" class="form">
      <label for="email">Email đăng nhập</label>
      <input type="email" id="email" name="email" required value="<?php if(isset($email)) echo htmlspecialchars($email); ?>">

      <label for="password">Mật khẩu</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" class="btn">Đăng nhập</button>
    </form>

    <!-- Hiển thị lỗi -->
    <?php if($error): ?>
      <p class="message"><?php echo $error; ?></p>
    <?php endif; ?>

    <p class="note">Tài khoản mẫu: <strong>admin@infidorm.com</strong> / <strong>123456</strong></p>
  </main>
</body>
</html>

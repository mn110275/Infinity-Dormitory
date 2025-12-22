<?php
session_start();
require '../database_connection.php'; // dùng chung database_connection.php

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "
        SELECT * FROM STUDENT s
        JOIN USERS u ON s.USER_ID = u.USER_ID
        WHERE u.EMAIL = '$email' AND u.PASS = '$password'
    ";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
      $user = mysqli_fetch_assoc($result);

      $_SESSION['role'] = $user['USER_ROLE'];
      $_SESSION['user_id'] = $user['USER_ID'];
      $_SESSION['email'] = $user['EMAIL'];
      $_SESSION['name'] = $user['STD_NAME'];

      header("Location: home.php");
      exit;
    } else {
        $error = "Sai email hoặc mật khẩu sinh viên!";
    }

}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng nhập Sinh viên</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
  <header class="nav">
    <a href="../index.php">Trang chủ</a>
    <a href="../register/register.html">Đăng ký KTX</a>
    <a href="login.php" class="active">Đăng nhập Sinh viên</a>
    <a href="../manager/login.php">Đăng nhập Quản lý</a>
  </header>

  <main class="container">
    <h1>Đăng nhập Sinh viên KTX</h1>

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

    <p class="note">Tài khoản mẫu: <strong>SV001</strong> / <strong>123</strong></p>
  </main>
</body>
</html>
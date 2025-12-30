<?php
session_start();
require '../database_connection.php'; 
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

      $_SESSION['role'] = 'student';
      $_SESSION['user_id'] = $user['USER_ID'];
      $_SESSION['email'] = $user['EMAIL'];
      $_SESSION['name'] = $user['STD_NAME'];

      header("Location: home.php");
      exit;
    } else {
        $error = "Sai email hoặc mật khẩu, vui lòng thử lại.";
    }

}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Đăng nhập Sinh viên</title>
  <link rel="stylesheet" href="../css/public.css" />
  <link rel="stylesheet" href="../css/login.css" />
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

      <button type="submit" class="btn btn-pro">Đăng nhập</button>
    </form>

    <?php if($error): ?>
      <p class="message error"><?php echo $error; ?></p>
    <?php endif; ?>

    <p class="note">Tài khoản mẫu: <strong>s1@infidorm.com</strong> / <strong>123456</strong></p>
  </main>
</body>
</html>
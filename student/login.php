<?php
session_start();
require '../database_connection.php'; 
require '../auth/auth_core.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = loginWithRole($conn, $email, $password, 'student');

    if (!$user) {
        $error = "Sai email hoặc mật khẩu, vui lòng thử lại.";
    } else {
      $sql = "SELECT S.*, C.BLOCK_ID
                FROM STUDENT S
                JOIN CONTRACT C ON S.STD_ID = C.STD_ID
                WHERE USER_ID = ?
                LIMIT 1";
      
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $user['USER_ID']);
      $stmt->execute();

      $result = $stmt->get_result();
      if ($result->num_rows !== 1) {
          $error = "Không tìm thấy thông tin sinh viên";
      } else {
          $student = $result->fetch_assoc();

          $_SESSION['role'] = $user['USER_ROLE'];
          $_SESSION['user_id'] = $user['USER_ID'];
          $_SESSION['email'] = $email;
          $_SESSION['name'] = $student['STD_NAME'];
          $_SESSION['block'] = $student['BLOCK_ID'];

          header("Location: home.php");
          exit;
      } 
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

    <p class="note">Tài khoản mẫu: <strong>s3@infidorm.com</strong> / <strong>123456</strong></p>
  </main>
</body>
</html>
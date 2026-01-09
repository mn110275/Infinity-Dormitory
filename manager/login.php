<?php
session_start();
require '../database_connection.php';
require '../auth/auth_core.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = loginWithRole($conn, $email, $password, 'manager');

    if (!$user) {
        $error = "Sai email hoặc mật khẩu quản lý!";
    } else {
        $sql = "SELECT *
                FROM MANAGER
                WHERE USER_ID = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['USER_ID']);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows !== 1) {
            $error = "Không tìm thấy thông tin quản lý";
        } else {
            $manager = $result->fetch_assoc();

            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['role']    = $user['USER_ROLE']; 
            $_SESSION['email']   = $email;
            $_SESSION['name']    = $manager['MNG_NAME'];
            $_SESSION['block']   = $manager['MNG_BLOCK'];

            header("Location: dashboard.php");
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
  <title>Đăng nhập Quản lý</title>
  <link rel="stylesheet" href="../css/public.css" />
  <link rel="stylesheet" href="../css/login.css" />
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
  <header class="nav">
    <a href="home.php" style="font-style: italic; font-size: 30px;">Infinity Dormitory</a>
  </header>

  <main class="container">
    <h1>Đăng nhập Quản lý KTX</h1>
    <form action="login.php" method="POST" class="form">
      <label for="email">Email đăng nhập</label>
      <input type="email" id="email" name="email" required value="<?php if(isset($email)) echo htmlspecialchars($email); ?>">

      <label for="password">Mật khẩu</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" class="btn btn-pro">Đăng nhập</button>
    </form>

    <?php if($error): ?>
      <p class="message"><?php echo $error; ?></p>
    <?php endif; ?>

    <p class="note">Tài khoản mẫu: <strong>manager@infidorm.com</strong> / <strong>123456</strong></p>
  </main>
</body>
</html>
<?php
http_response_code(403);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>403 - Không có quyền truy cập</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    body, html {
      height: 100%;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      background: var(--bg);
      color: var(--text);
      font-family: Arial, Helvetica, sans-serif;
      text-align: center;
    }
    .error-container {
      background: white;
      padding: 32px 24px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
      max-width: 400px;
      width: 100%;
    }
    h1 {
      font-size: 6rem;
      margin-bottom: 0.5rem;
      color: var(--danger);
      font-weight: 700;
    }
    p {
      font-size: 1.25rem;
      margin-bottom: 1.5rem;
      color: var(--text);
    }
    .btn {
      width: auto;
      font-weight: 700;
      padding: 12px 24px;
      font-size: 1rem;
    }
  </style>
</head>
<body>
  <div class="error-container">
    <h1>403</h1>
    <p>Xin lỗi, bạn không có quyền truy cập trang này.</p>
    <a href="<?php
      switch ($userRole) {
        case 'admin': echo '../admin/dashboard.php'; break;
        case 'manager': echo '../manager/dashboard.php'; break;
        case 'student': echo '../student/home.php'; break;
        default: echo '/index.php'; break;
      }
    ?>" class="btn btn-primary">Về trang chính</a>
  </div>
</body>
</html>

<?php
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>404 - Trang không tồn tại</title>
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
      color: #6b7280; /* màu text-muted */
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
    <h1>404</h1>
    <p>Trang bạn đang tìm không tồn tại hoặc đã bị xóa.</p>
    <a href="../index.php" class="btn btn-primary">Quay về trang chủ</a>
  </div>
</body>
</html>

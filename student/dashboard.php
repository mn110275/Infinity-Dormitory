<?php
session_start();
require '../database_connection.php';

// Kiểm tra phân quyền
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php?error=unauthorized");
    exit;
}

// Lấy thông tin sinh viên từ database
$user_id = $_SESSION['user_id'];
$student = null;
$error = '';

$sql = "
    SELECT * FROM STUDENT
    WHERE USER_ID = '$user_id'
";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) == 1) {
    $student = mysqli_fetch_assoc($result);
} else {
    $error = "Không tìm thấy thông tin sinh viên.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý thông tin sinh viên</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .info-section {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    .info-item {
      display: flex;
      flex-direction: column;
    }
    .info-label {
      font-weight: bold;
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
    }
    .info-value {
      color: #333;
      font-size: 1rem;
    }
    .student-image {
      width: 150px;
      height: 150px;
      border-radius: 8px;
      object-fit: cover;
      margin: 1rem 0;
      border: 3px solid #ddd;
    }
    .error-message {
      background: #fee;
      color: #c33;
      padding: 1rem;
      border-radius: 4px;
      margin: 1rem 0;
    }
    .actions {
      margin-top: 2rem;
      display: flex;
      gap: 1rem;
    }
  </style>
</head>
<body>
  <header class="nav">
    <a href="home.php">Trang chủ</a>
    <a href="dashboard.php" class="active">Quản lý thông tin</a>
  </header>

  <main class="container">
    <h1>Thông tin chi tiết: </h1>

    <?php if ($error): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($student): ?>
      <div class="info-section">
        <?php if (!empty($student['STD_IMG'])): ?>
          <img src="../<?php echo htmlspecialchars($student['STD_IMG']); ?>" 
               alt="Ảnh sinh viên" 
               class="student-image" />
        <?php endif; ?>

        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Mã sinh viên:</span>
            <span class="info-value"><?php echo htmlspecialchars($student['STD_ID']); ?></span>
          </div>

          <div class="info-item">
            <span class="info-label">Họ và tên:</span>
            <span class="info-value"><?php echo htmlspecialchars($student['STD_NAME']); ?></span>
          </div>

          <div class="info-item">
            <span class="info-label">Ngày sinh:</span>
            <span class="info-value">
              <?php 
                if (!empty($student['STD_DOB'])) {
                  echo date('d/m/Y', strtotime($student['STD_DOB']));
                } else {
                  echo 'Chưa cập nhật';
                }
              ?>
            </span>
          </div>

          <div class="info-item">
            <span class="info-label">Giới tính:</span>
            <span class="info-value"><?php echo htmlspecialchars($student['STD_GD']); ?></span>
          </div>

          <div class="info-item">
            <span class="info-label">Số điện thoại:</span>
            <span class="info-value">
              <?php echo !empty($student['STD_PHONE']) ? htmlspecialchars($student['STD_PHONE']) : 'Chưa cập nhật'; ?>
            </span>
          </div>

          <div class="info-item">
            <span class="info-label">Địa chỉ:</span>
            <span class="info-value"><?php echo htmlspecialchars($student['STD_ADR']); ?></span>
          </div>

          <div class="info-item">
            <span class="info-label">Tòa nhà:</span>
            <span class="info-value">
              <?php echo !empty($student['BLOCK_ID']) ? htmlspecialchars($student['BLOCK_ID']) : 'Chưa phân bổ'; ?>
            </span>
          </div>

          <div class="info-item">
            <span class="info-label">Phòng:</span>
            <span class="info-value">
              <?php echo !empty($student['ROOM_ID']) ? htmlspecialchars($student['ROOM_ID']) : 'Chưa phân bổ'; ?>
            </span>
          </div>

          <div class="info-item">
            <span class="info-label">Ngày bắt đầu:</span>
            <span class="info-value">
              <?php 
                if (!empty($student['STARTDATE'])) {
                  echo date('d/m/Y', strtotime($student['STARTDATE']));
                } else {
                  echo 'Chưa cập nhật';
                }
              ?>
            </span>
          </div>

          <div class="info-item">
            <span class="info-label">Ngày kết thúc:</span>
            <span class="info-value">
              <?php 
                if (!empty($student['ENDDATE'])) {
                  echo date('d/m/Y', strtotime($student['ENDDATE']));
                } else {
                  echo 'Chưa cập nhật';
                }
              ?>
            </span>
          </div>

          <div class="info-item">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="actions">
      <a href="home.php" class="btn">Quay lại</a>
      <a href="../logout.php" class="btn">Đăng xuất</a>
    </div>
  </main>

</body>
</html>

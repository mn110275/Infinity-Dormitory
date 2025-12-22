<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$idDon  = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$idDon || !$action) {
  echo json_encode(['success' => false, 'message' => 'Missing parameters']);
  exit;
}

if ($action === 'accept') {
  $idDon = $_POST['id'] ?? null;
  $studentId = $_POST['mssv'] ?? null;
  $name = $_POST['ho_ten'] ?? null;
  $dob = $_POST['ngay_sinh'] ?? null;
  $phone = $_POST['contact_sv'] ?? null;
  $address = $_POST['address_sv'] ?? null;
  $gender = $_POST['gioi_tinh'] ?? null;
  $roomId = $_POST['id_room'] ?? null;

  // Kiểm tra đầy đủ dữ liệu
  if (!$idDon || !$studentId || !$name || !$dob || !$phone || !$address || !$gender || !$roomId) {
    echo json_encode(['success' => false, 'message' => 'Missing student info']);
    exit;
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    throw new Exception('Ngày sinh không hợp lệ');
  }

  mysqli_begin_transaction($conn);

  try {
    // 1. Lấy email từ đơn đăng ký 
    $stmt = mysqli_prepare($conn,
        "SELECT REG_EMAIL FROM REGIFORM 
          WHERE REG_ID = ? AND REG_STATUS = 'Chưa xử lý'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idDon);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    if (!$row) throw new Exception('Đơn không hợp lệ');

    $email = $row['REG_EMAIL'];

    // 2. Tạo USERS
    $defaultPassword = 123456;
    $hashedPass = password_hash($defaultPassword, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn,
        "INSERT INTO USERS (EMAIL, PASS, USER_ROLE) VALUES (?, ?, 'student')"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $email, $hashedPass);
    mysqli_stmt_execute($stmt);

    $userId = mysqli_insert_id($conn);

    // 3. Lấy BLOCK_ID từ ROOM 
    $stmt = mysqli_prepare($conn,
        "SELECT BLOCK_ID FROM ROOM WHERE ROOM_ID = ?"
    );
    mysqli_stmt_bind_param($stmt, 's', $roomId);
    mysqli_stmt_execute($stmt);
    $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$room) throw new Exception('Phòng không tồn tại');

    // 4. Tạo STUDENT
    $stmt = mysqli_prepare($conn,
        "INSERT INTO STUDENT
        (STD_ID, STD_NAME, STD_PHONE, STD_ADR, STD_GD, STD_DOB, USER_ID, ROOM_ID, BLOCK_ID)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $stmt,
        'ssssssiss',
        $studentId, $name, $phone, $address, $gender,
        $dob, $userId, $roomId, $room['BLOCK_ID']
    );
    mysqli_stmt_execute($stmt);

    // 6. Cập nhật trạng thái đơn
    $stmt = mysqli_prepare($conn,
        "UPDATE REGIFORM SET REG_STATUS = 'Đã chấp nhận' WHERE REG_ID = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idDon);
    mysqli_stmt_execute($stmt);

    mysqli_commit($conn);
    echo json_encode(['success' => true]);

  } catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// reject
if ($action === 'reject') {
    $stmt = mysqli_prepare($conn,
        "UPDATE REGIFORM SET REG_STATUS = 'Đã từ chối'
         WHERE REG_ID = ? AND REG_STATUS = 'Chưa xử lý'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idDon);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

// undo
if ($action === 'undo') {
    $stmt = mysqli_prepare($conn,
        "UPDATE REGIFORM SET REG_STATUS = 'Chưa xử lý'
         WHERE REG_ID = ? AND REG_STATUS = 'Đã từ chối'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idDon);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

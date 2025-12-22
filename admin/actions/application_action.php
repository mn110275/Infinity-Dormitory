<?php
require_once __DIR__ . '/../../database_connection.php';

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
  $mssv = $_POST['mssv'] ?? null;
  $hoTen = $_POST['ho_ten'] ?? null;
  $contact = $_POST['contact_sv'] ?? null;
  $address = $_POST['address_sv'] ?? null;
  $gioiTinh = $_POST['gioi_tinh'] ?? null;
  $idRoom = $_POST['id_room'] ?? null;

  // Kiểm tra đầy đủ dữ liệu
  if (!$idDon || !$mssv || !$hoTen || !$contact || !$address || !$gioiTinh || !$idRoom) {
    echo json_encode(['success' => false, 'message' => 'Missing student info']);
    exit;
  }

  mysqli_begin_transaction($conn);

  try {
    // 1. Kiểm tra phòng còn chỗ
    $sqlCheckRoom = "SELECT CAPACITY, OCCUPIED_SLOT FROM PHONG WHERE ID_ROOM = ?";
    $stmtCheckRoom = mysqli_prepare($conn, $sqlCheckRoom);
    mysqli_stmt_bind_param($stmtCheckRoom, 'i', $idRoom);
    mysqli_stmt_execute($stmtCheckRoom);
    $resRoom = mysqli_stmt_get_result($stmtCheckRoom);
    $roomData = mysqli_fetch_assoc($resRoom);

    if (!$roomData) {
        throw new Exception('Phòng không tồn tại');
    }
    if ($roomData['OCCUPIED_SLOT'] >= $roomData['CAPACITY']) {
        throw new Exception('Phòng đã đầy, vui lòng chọn phòng khác');
    }

    // 2. Lấy email từ đơn đăng ký (để tạo NGUOIDUNG)
    $sqlGetEmail = "SELECT EMAIL_NDK FROM DONDANGKY WHERE ID_DON = ? AND STATUS_DON = 'Chưa xử lý'";
    $stmtGetEmail = mysqli_prepare($conn, $sqlGetEmail);
    mysqli_stmt_bind_param($stmtGetEmail, 'i', $idDon);
    mysqli_stmt_execute($stmtGetEmail);
    $res = mysqli_stmt_get_result($stmtGetEmail);
    $row = mysqli_fetch_assoc($res);
    if (!$row) throw new Exception('Đơn không tồn tại hoặc đã xử lý');

    $email = $row['EMAIL_NDK'];

    // 3. Tạo NGUOIDUNG
    $sqlInsertUser = "INSERT INTO NGUOIDUNG (NAME, EMAIL, PASSWORD_ND, ROLE) VALUES (?, ?, '', 'student')";
    $stmtInsertUser = mysqli_prepare($conn, $sqlInsertUser);
    mysqli_stmt_bind_param($stmtInsertUser, 'ss', $hoTen, $email);
    if (!mysqli_stmt_execute($stmtInsertUser)) throw new Exception('Lỗi tạo người dùng');

    $idUser = mysqli_insert_id($conn);

    // 4. Tạo SINHVIEN
    $sqlInsertSv = "INSERT INTO SINHVIEN (MSSV, HO_TEN_SV, CONTACT_SV, ADDRESS_SV, GIOI_TINH, ID_USER, ID_ROOM)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtInsertSv = mysqli_prepare($conn, $sqlInsertSv);
    mysqli_stmt_bind_param($stmtInsertSv, 'sssssii', $mssv, $hoTen, $contact, $address, $gioiTinh, $idUser, $idRoom);
    if (!mysqli_stmt_execute($stmtInsertSv)) throw new Exception('Lỗi tạo sinh viên');

    // 5. Cập nhật số lượng sinh viên trong phòng
    $sqlUpdateRoom = "UPDATE PHONG SET OCCUPIED_SLOT = OCCUPIED_SLOT + 1 WHERE ID_ROOM = ?";
    $stmtUpdateRoom = mysqli_prepare($conn, $sqlUpdateRoom);
    mysqli_stmt_bind_param($stmtUpdateRoom, 'i', $idRoom);
    if (!mysqli_stmt_execute($stmtUpdateRoom)) throw new Exception('Lỗi cập nhật số lượng sinh viên trong phòng');

    // 6. Cập nhật trạng thái đơn
    $sqlUpdateDon = "UPDATE DONDANGKY SET STATUS_DON = 'Đã chấp nhận' WHERE ID_DON = ?";
    $stmtUpdateDon = mysqli_prepare($conn, $sqlUpdateDon);
    mysqli_stmt_bind_param($stmtUpdateDon, 'i', $idDon);
    if (!mysqli_stmt_execute($stmtUpdateDon)) throw new Exception('Lỗi cập nhật đơn');

    mysqli_commit($conn);

    echo json_encode(['success' => true]);
    exit;

  } catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
  }
} elseif ($action === 'reject') {
  $sql = "
    UPDATE DONDANGKY
    SET STATUS_DON = 'Đã từ chối'
    WHERE ID_DON = ? AND STATUS_DON = 'Chưa xử lý'
  ";
} elseif ($action === 'undo') {
  $sql = "
    UPDATE DONDANGKY
    SET STATUS_DON = 'Chưa xử lý'
    WHERE ID_DON = ? AND STATUS_DON = 'Đã từ chối'
  ";
} else {
  echo json_encode(['success' => false, 'message' => 'Invalid action']);
  exit;
}

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Prepare failed']);
  exit;
}

mysqli_stmt_bind_param($stmt, 'i', $idDon);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) === 0) {
  echo json_encode([
    'success' => false,
    'message' => 'Đơn không tồn tại hoặc trạng thái không hợp lệ'
  ]);
  exit;
}

echo json_encode(['success' => true]);

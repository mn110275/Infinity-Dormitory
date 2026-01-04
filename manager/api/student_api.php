<?php
// manager/api/student_api.php
session_start();
require_once '../../database_connection.php'; // Điều chỉnh đường dẫn cho đúng file kết nối của bạn
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || $input['action'] !== 'update_student') {
  echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
  exit;
}

$std_id = $input['std_id'];
$name = $input['name'];
$gd = $input['gd'];
$dob = $input['dob'];
$phone = $input['phone'];
$adr = $input['adr'];

try {
  $sql = "UPDATE STUDENT SET 
  STD_NAME = ? ,
    STD_GD = ? ,
    STD_DOB = ? ,
    STD_PHONE = ? ,
    STD_ADR = ?
    WHERE STD_ID = ? ";

  $stmt = mysqli_prepare($conn, $sql);
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $gd, $dob, $phone, $adr, $std_id);

    if (mysqli_stmt_execute($stmt)) {
      echo json_encode(['status' => 'success']);
    } else {
      echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
  }
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e -> getMessage()]);
}

mysqli_close($conn);
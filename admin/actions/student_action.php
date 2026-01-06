<?php
session_start();
require_once '../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
  echo json_encode(['status' => 'error', 'message' => 'Thiếu action']);
  exit;
}

try {
  if ($input['action'] !== 'update_student') {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
    exit;
  }
  
  $std_id = $input['std_id'];
  $name = $input['name'];
  $gd = $input['gd'];
  $dob = $input['dob'];
  $phone = $input['phone'];
  $adr = $input['adr'];

  $newBlock = $input['block_id'] ?? null;
  $newRoom  = $input['room_id'] ?? null;

  mysqli_begin_transaction($conn);

  try {
    $stmt = mysqli_prepare($conn,
      "UPDATE STUDENT
      SET
        STD_NAME  = ?,
        STD_GD    = ?,
        STD_DOB   = ?,
        STD_PHONE = ?,
        STD_ADR   = ?
      WHERE STD_ID = ?
    ");

    mysqli_stmt_bind_param($stmt, "ssssss", $name, $gd, $dob, $phone, $adr, $std_id);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn,
      "SELECT BLOCK_ID, ROOM_ID FROM STUDENT WHERE STD_ID = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $std_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current = mysqli_fetch_assoc($result);
    if (!empty($newBlock) && !empty($newRoom) && 
      ($newBlock !== $current['BLOCK_ID'] || $newRoom  !== $current['ROOM_ID'])) {
      $stmt = mysqli_prepare($conn,
        "UPDATE STUDENT
        SET BLOCK_ID = ?, ROOM_ID = ?
        WHERE STD_ID = ?"
      );

      mysqli_stmt_bind_param($stmt, "sss",
        $newBlock, $newRoom, $std_id
      );
      mysqli_stmt_execute($stmt);
    }

    mysqli_commit($conn);

    echo json_encode(['status' => 'success']);
    exit;
  } catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);

    // Bắt lỗi trigger SIGNAL
    if ($e->getSqlState() === '45000') {
      echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
      ]);
    } else {
      echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi cập nhật sinh viên'
      ]);
    }
    exit;
  }
  echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e -> getMessage()]);
}

mysqli_close($conn);
<?php
// manager/notification_api.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$userId = $_SESSION['user_id'];

try
{
  $input = json_decode(file_get_contents('php://input'), true);
  $action = $input['action'] ?? '';

  mysqli_set_charset($conn, "utf8mb4");

  $mngQuery = "SELECT MNG_ID FROM MANAGER WHERE USER_ID = ?";
  $stmt = mysqli_prepare($conn, $mngQuery);
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if (!$row = mysqli_fetch_assoc($result))
  {
    throw new Exception('Không tìm thấy quản lý');
  }

  $managerId = $row['MNG_ID'];

  if ($action === 'revoke')
  {
    $notiId = $input['noti_id'] ?? 0;

    if (!$notiId)
    {
      throw new Exception('Thiếu thông tin thông báo');
    }

    // Verify notification belongs to this manager
    $checkQuery = "SELECT MNG_ID FROM NOTI WHERE NOTI_ID = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "i", $notiId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result))
    {
      if ($row['MNG_ID'] !== $managerId)
      {
        throw new Exception('Bạn không có quyền thu hồi thông báo này');
      }

      $deleteQuery = "DELETE FROM NOTI WHERE NOTI_ID = ?";
      $stmt = mysqli_prepare($conn, $deleteQuery);
      mysqli_stmt_bind_param($stmt, "i", $notiId);

      if (mysqli_stmt_execute($stmt))
      {
        echo json_encode([
          'success' => true,
          'message' => 'Đã thu hồi thông báo'
        ]);
      }
      else
      {
        throw new Exception('Không thể xóa thông báo');
      }
    }
    else
    {
      throw new Exception('Không tìm thấy thông báo');
    }

  }
  else
  {
    throw new Exception('Action không hợp lệ');
  }

}
catch (Exception $e)
{
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'error' => $e -> getMessage()
  ]);
}

if (isset($conn))
{
  mysqli_close($conn);
} 
?>
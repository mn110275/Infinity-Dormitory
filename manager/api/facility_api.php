<?php
// manager/api/facility_api.php
session_start();
header('Content-Type: application/json');
require_once '../../database_connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager')
{
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

// Lấy dữ liệu từ JSON (khi DELETE/UPDATE) hoặc $_POST (khi UPLOAD)
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $data['action'] ?? '';

if ($action === 'add_photo')
{
  if (!isset($_FILES['file']) || !isset($data['fclt_id']))
  {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
  }
  else
  {
    $id = $data['fclt_id'];
    $targetDir = "../../uploads/facilities/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time().
    "_".basename($_FILES["file"]["name"]);
    $targetFile = $targetDir.$fileName;
    $dbPath = "uploads/facilities/".$fileName;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile))
    {
      $stmt = $conn -> prepare("SELECT FCLT_IMG FROM FACILITY WHERE FCLT_ID = ?");
      $stmt -> bind_param("i", $id);
      $stmt -> execute();
      $currentImg = $stmt -> get_result() -> fetch_assoc()['FCLT_IMG'];

      $newImgString = $currentImg ? $currentImg.
      "|".$dbPath: $dbPath;

      $update = $conn -> prepare("UPDATE FACILITY SET FCLT_IMG = ? WHERE FCLT_ID = ?");
      $update -> bind_param("si", $newImgString, $id);
      $update -> execute();

      echo json_encode(['status' => 'success', 'path' => $dbPath]);
    }
  }
}

elseif($action === 'update_photos')
{
  $id = $data['fclt_id'];
  $images = $data['images']; // Chuỗi đã được join('|') bên JS
  $stmt = $conn -> prepare("UPDATE FACILITY SET FCLT_IMG = ? WHERE FCLT_ID = ?");
  $stmt -> bind_param("si", $images, $id);
  echo json_encode(['status' => $stmt -> execute() ? 'success' : 'error']);
}

elseif($action === 'delete_entity')
{
  $id = $data['fclt_id'];
  $stmt = $conn -> prepare("DELETE FROM FACILITY WHERE FCLT_ID = ?");
  $stmt -> bind_param("i", $id);
  echo json_encode(['status' => $stmt -> execute() ? 'success' : 'error']);
}

elseif($action === 'add_new_entity')
{
  $roomId = $data['room_id'];
  $itemType = $data['item_type'];
  $blockId = $_SESSION['block'];

  $stmt = $conn -> prepare("INSERT INTO FACILITY (ROOM_ID, BLOCK_ID, FCLT_TYPE, FCLT_STATUS) VALUES (?, ?, ?, 'Bình thường')");
  $stmt -> bind_param("sss", $roomId, $blockId, $itemType);

  if ($stmt -> execute())
  {
    echo json_encode(['status' => 'success', 'new_id' => $conn -> insert_id]);
  }
}

elseif($action === 'update_status_only')
{
  $id = $data['fclt_id'];
  $status = $data['status'];
  $stmt = $conn -> prepare("UPDATE FACILITY SET FCLT_STATUS = ? WHERE FCLT_ID = ?");
  $stmt -> bind_param("si", $status, $id);
  if ($stmt -> execute())
  {
    echo json_encode(['status' => 'success']);
  }
  else
  {
    echo json_encode(['status' => 'error']);
  }
}

elseif($action === 'update_full_info')
{
  $id = $data['fclt_id'];
  $status = $data['status'];
  $note = $data['note'];

  $stmt = $conn -> prepare("UPDATE FACILITY SET FCLT_STATUS = ?, FCLT_NOTE = ? WHERE FCLT_ID = ?");
  $stmt -> bind_param("ssi", $status, $note, $id);

  if ($stmt -> execute())
  {
    echo json_encode(['status' => 'success']);
  }
  else
  {
    echo json_encode(['status' => 'error', 'message' => $conn -> error]);
  }
}

elseif($action === 'add_new_row_type')
{
  $item_type = $data['item_type'];
  $room_id = $data['room_id'];
  $block_id = $_SESSION['block'];

  $checkRoom = "SELECT ROOM_ID FROM ROOM WHERE ROOM_ID = ? AND BLOCK_ID = ?";
  $stmtCheck = $conn -> prepare($checkRoom);
  $stmtCheck -> bind_param("ss", $room_id, $block_id);
  $stmtCheck -> execute();
  $resRoom = $stmtCheck -> get_result();

  if ($resRoom -> num_rows > 0)
  {
    $sql = "INSERT INTO FACILITY (BLOCK_ID, ROOM_ID, FCLT_TYPE, FCLT_STATUS, FCLT_NOTE) 
    VALUES( ? , ? , ? , 'Mới', 'Khởi tạo theo yêu cầu')
    ";
    $stmt = $conn -> prepare($sql);
    $stmt -> bind_param("sss", $block_id, $room_id, $item_type);

    if ($stmt -> execute())
    {
      echo json_encode(['status' => 'success']);
    }
    else
    {
      echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi chèn dữ liệu.']);
    }
  }
  else
  {
    echo json_encode(['status' => 'error', 'message' => "Phòng $room_id không thuộc Tòa $block_id!"]);
  }
}

else
{
  echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
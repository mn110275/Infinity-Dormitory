<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'add_block') {
        $block_id = mysqli_real_escape_string($conn, trim($_POST['block_id']));
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if (empty($block_id)) {
            echo json_encode(['success' => false, 'message' => 'Tên tòa không được để trống.']);
            exit;
        }

        $checkQuery = "SELECT BLOCK_ID FROM BLOCK WHERE BLOCK_ID = '$block_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            echo json_encode(['success' => false, 'message' => 'Tòa này đã tồn tại trên hệ thống.']);
            exit;
        }

        $query = "INSERT INTO BLOCK (BLOCK_ID, BLOCK_STATUS) VALUES ('$block_id', '$status')";
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'update_block_status') {
        $block_id = mysqli_real_escape_string($conn, $_POST['block_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $force = $_POST['force'] ?? 'false';

        mysqli_begin_transaction($conn);

        try {
            if ($status === 'Closed') {
                if ($force === 'true') {
                    $queryTerminated = "UPDATE CONTRACT 
                                        SET STATUS = 'Terminated'
                                        WHERE BLOCK_ID = '$block_id' AND STATUS = 'Active'";
                    if (!mysqli_query($conn, $queryTerminated)) {
                        throw new Exception("Lỗi chấm dứt hợp đồng: " . mysqli_error($conn));
                    }
                } else {
                    $checkQuery = "SELECT COUNT(*) as total FROM CONTRACT WHERE BLOCK_ID = '$block_id' AND STATUS = 'Active'";
                    $checkRes = mysqli_query($conn, $checkQuery);
                    $count = mysqli_fetch_assoc($checkRes)['total'];
                    if ($count > 0) {
                        throw new Exception("vẫn còn sinh viên"); 
                    }
                }
            }

            $queryBlock = "UPDATE BLOCK SET BLOCK_STATUS = '$status' WHERE BLOCK_ID = '$block_id'";
            if (!mysqli_query($conn, $queryBlock)) {
                throw new Exception("Lỗi cập nhật trạng thái tòa: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            if ($e->getMessage() === "vẫn còn sinh viên") {
                echo json_encode([
                    'success' => false, 
                    'requires_force' => true, 
                    'message' => "Tòa vẫn còn sinh viên đang ở. Tiếp tục sẽ chấm dứt toàn bộ hợp đồng hiện tại để đóng tòa!"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }

    if ($action === 'update_room_status') {
        $block_id = mysqli_real_escape_string($conn, $_POST['block_id']);
        $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $force = $_POST['force'] ?? 'false';
        
        mysqli_begin_transaction($conn);

        try {
            if ($status === 'Closed') {
                if ($force === 'true') {
                    $queryTerminated = "UPDATE CONTRACT 
                                        SET STATUS = 'Terminated'
                                        WHERE BLOCK_ID = '$block_id' AND ROOM_ID = '$room_id' AND STATUS = 'Active'";
                    if (!mysqli_query($conn, $queryTerminated)) {
                        throw new Exception("Lỗi chấm dứt hợp đồng: " . mysqli_error($conn));
                    }
                } else {
                    $checkQuery = "SELECT COUNT(*) as total FROM CONTRACT 
                                   WHERE BLOCK_ID = '$block_id' AND ROOM_ID = '$room_id' AND STATUS = 'Active'";
                    $checkRes = mysqli_query($conn, $checkQuery);
                    $count = mysqli_fetch_assoc($checkRes)['total'];

                    if ($count > 0) {
                        throw new Exception("phòng vẫn còn sinh viên");
                    }
                }
            }

            $queryRoom = "UPDATE ROOM SET ROOM_STATUS = '$status' 
                          WHERE BLOCK_ID = '$block_id' AND ROOM_ID = '$room_id'";
            
            if (!mysqli_query($conn, $queryRoom)) {
                throw new Exception("Lỗi SQL: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            if ($e->getMessage() === "phòng vẫn còn sinh viên") {
                echo json_encode([
                    'success' => false, 
                    'requires_force' => true, 
                    'message' => "Phòng vẫn còn sinh viên đang ở. Tiếp tục sẽ chấm dứt hợp đồng của họ để đóng phòng!"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        exit;
    }

    if ($action === 'add_room') {
        $block_id = mysqli_real_escape_string($conn, $_POST['block_id']);
        $room_id = mysqli_real_escape_string($conn, trim($_POST['room_id']));
        $capacity = (int)$_POST['capacity'];
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $check = mysqli_query($conn, "SELECT ROOM_ID FROM ROOM WHERE BLOCK_ID = '$block_id' AND ROOM_ID = '$room_id'");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => "Phòng $room_id đã tồn tại ở Tòa $block_id."]);
            exit;
        }

        $query = "INSERT INTO ROOM (ROOM_ID, BLOCK_ID, CAPACITY, OCCUPIED, GENDER, ROOM_STATUS) 
                VALUES ('$room_id', '$block_id', $capacity, 0, '$gender', '$status')";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }
} 

if ($method === 'GET') {
    if ($action === 'get_occupants') {
        $block_id = mysqli_real_escape_string($conn, $_GET['block_id']);
        $room_id = mysqli_real_escape_string($conn, $_GET['room_id']);
        
        $query = "SELECT s.STD_ID, s.STD_NAME 
                  FROM STUDENT s
                  JOIN CONTRACT c ON s.STD_ID = c.STD_ID
                  WHERE c.BLOCK_ID = '$block_id' 
                  AND c.ROOM_ID = '$room_id' 
                  AND c.STATUS = 'Active'";
        
        $result = mysqli_query($conn, $query);
        $occupants = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $occupants[] = $row;
        }
        echo json_encode($occupants);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action or method.']);
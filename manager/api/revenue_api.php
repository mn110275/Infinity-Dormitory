<?php
// manager/api/revenue_api.php - Handle revenue management
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$managerBlock = $_SESSION['block'];

try {
    mysqli_set_charset($conn, "utf8mb4");

    // Get request data
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        handleGet($conn, $managerBlock);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        handlePost($conn, $managerBlock, $input);
    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    mysqli_close($conn);
}

// ============ HANDLE GET - Load existing revenue data ============
function handleGet($conn, $managerBlock) {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $block = $_GET['block'] ?? $managerBlock;
    
    // Validate block matches manager's block
    if ($block !== $managerBlock) {
        throw new Exception('Bạn chỉ có thể xem dữ liệu tòa ' . $managerBlock);
    }

    // Get unit prices for this period
    $unitQuery = "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?";
    $stmt = mysqli_prepare($conn, $unitQuery);
    mysqli_stmt_bind_param($stmt, "ii", $year, $month);
    mysqli_stmt_execute($stmt);
    $unitResult = mysqli_stmt_get_result($stmt);
    
    $unitPrices = null;
    if ($row = mysqli_fetch_assoc($unitResult)) {
        $unitPrices = [
            'elec' => (int)$row['ELEC'],
            'water' => (int)$row['WATER']
        ];
    } else {
        throw new Exception('Chưa có đơn giá cho tháng ' . $month . '/' . $year);
    }

    // Get rooms of this block
    $roomQuery = "SELECT ROOM_ID FROM ROOM WHERE BLOCK_ID = ? ORDER BY ROOM_ID";
    $stmt = mysqli_prepare($conn, $roomQuery);
    mysqli_stmt_bind_param($stmt, "s", $block);
    mysqli_stmt_execute($stmt);
    $roomResult = mysqli_stmt_get_result($stmt);
    
    $rooms = [];
    while ($row = mysqli_fetch_assoc($roomResult)) {
        $rooms[] = $row['ROOM_ID'];
    }

    if (empty($rooms)) {
        throw new Exception('Không tìm thấy phòng nào');
    }

    // Get revenue data for this period
    $revenueData = [];
    
    $placeholders = str_repeat('?,', count($rooms) - 1) . '?';
    $revenueQuery = "SELECT ROOM_ID, ELEC, WATER, OTHER, NOTE 
                     FROM REVENUE 
                     WHERE BLOCK_ID = ? AND REV_YEAR = ? AND REV_MONTH = ? 
                     AND ROOM_ID IN ($placeholders)";
    
    $stmt = mysqli_prepare($conn, $revenueQuery);
    
    // Bind parameters
    $types = 'sii' . str_repeat('s', count($rooms));
    $params = array_merge([$block, $year, $month], $rooms);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $revenueData[$row['ROOM_ID']] = [
            'elec' => (int)$row['ELEC'],
            'water' => (int)$row['WATER'],
            'other' => (int)$row['OTHER'],
            'note' => $row['NOTE'] ?? ''
        ];
    }

    // Fill in empty data for rooms without records
    foreach ($rooms as $room) {
        if (!isset($revenueData[$room])) {
            $revenueData[$room] = [
                'elec' => 0,
                'water' => 0,
                'other' => 0,
                'note' => ''
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $revenueData,
        'unitPrices' => $unitPrices,
        'period' => [
            'year' => (int)$year,
            'month' => (int)$month
        ],
        'rooms' => $rooms
    ]);
}

// ============ HANDLE POST - Save revenue data ============
function handlePost($conn, $managerBlock, $input) {
    $action = $input['action'] ?? '';

    if ($action === 'save') {
        handleSave($conn, $managerBlock, $input);
    } else {
        throw new Exception('Action không hợp lệ');
    }
}

function handleSave($conn, $managerBlock, $input) {
    $year = $input['year'] ?? 0;
    $month = $input['month'] ?? 0;
    $block = $input['block'] ?? '';
    $data = $input['data'] ?? [];
    $sendNotification = $input['sendNotification'] ?? false;

    // Validate
    if (!$year || !$month || !$block) {
        throw new Exception('Thiếu thông tin năm/tháng/tòa');
    }

    if ($block !== $managerBlock) {
        throw new Exception('Bạn chỉ có thể lưu dữ liệu tòa ' . $managerBlock);
    }

    if (empty($data)) {
        throw new Exception('Không có dữ liệu để lưu');
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete existing records for this period
        $deleteQuery = "DELETE FROM REVENUE 
                        WHERE BLOCK_ID = ? AND REV_YEAR = ? AND REV_MONTH = ?";
        $stmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($stmt, "sii", $block, $year, $month);
        mysqli_stmt_execute($stmt);

        // Insert new records
        $insertQuery = "INSERT INTO REVENUE 
                        (REV_YEAR, REV_MONTH, BLOCK_ID, ROOM_ID, ELEC, WATER, OTHER, NOTE) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insertQuery);

        foreach ($data as $roomId => $roomData) {
            $elec = (int)($roomData['elec'] ?? 0);
            $water = (int)($roomData['water'] ?? 0);
            $other = (int)($roomData['other'] ?? 0);
            $note = $roomData['note'] ?? '';

            mysqli_stmt_bind_param(
                $stmt, 
                "iissiiis",
                $year,
                $month,
                $block,
                $roomId,
                $elec,
                $water,
                $other,
                $note
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Lỗi khi lưu dữ liệu phòng ' . $roomId);
            }
        }

        // If sendNotification is true, create notification
        if ($sendNotification) {
            // Get manager ID
            $mngQuery = "SELECT MNG_ID FROM MANAGER 
                         WHERE USER_ID = ? AND MNG_BLOCK = ?";
            $stmt = mysqli_prepare($conn, $mngQuery);
            mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $block);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $managerId = $row['MNG_ID'];
                
                // Create notification
                $title = "Thông báo thu phí tháng {$month}/{$year}";
                $content = "Hóa đơn điện nước tháng {$month}/{$year} đã được cập nhật. Vui lòng kiểm tra và thanh toán đúng hạn.";
                
                $notiQuery = "INSERT INTO NOTI (TITLE, CONTENT, NOTI_DATE, MNG_ID) 
                              VALUES (?, ?, NOW(), ?)";
                $stmt = mysqli_prepare($conn, $notiQuery);
                mysqli_stmt_bind_param($stmt, "sss", $title, $content, $managerId);
                mysqli_stmt_execute($stmt);
            }
        }

        // Commit transaction
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => $sendNotification 
                ? 'Đã lưu và gửi thông báo thành công' 
                : 'Đã lưu tạm hóa đơn',
            'notification_sent' => $sendNotification
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}
?>
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
    mysqli_begin_transaction($conn);

    $semRes = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_STATUS = 'Active' LIMIT 1");
    $currentSem = mysqli_fetch_assoc($semRes);
    if (!$currentSem) throw new Exception('Chưa thiết lập học kỳ hiện tại');
    $semId = $currentSem['SEM_ID'];

    switch ($input['action']) {
        case 'delete_student':
            $std_id = $input['std_id'];
            
            $stmt = mysqli_prepare($conn,
              " UPDATE STUDENT 
                SET IS_ACTIVE = 0
                WHERE STD_ID = ?
            ");
            mysqli_stmt_bind_param($stmt, "s", $std_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn, 
                "UPDATE CONTRACT 
                SET STATUS = 'Terminated' 
                WHERE STD_ID = ? AND SEM_ID = ? AND STATUS = 'Active'"
            );
            mysqli_stmt_bind_param($stmt, "ss", $std_id, $semId);
            mysqli_stmt_execute($stmt);

            $resNext = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_STATUS = 'Upcoming' LIMIT 1");
            $nextSem = mysqli_fetch_assoc($resNext);
            
            if ($nextSem) {
                $nextSemId = $nextSem['SEM_ID'];
                $stmtDeleteUpcoming = mysqli_prepare($conn, 
                    "DELETE FROM CONTRACT 
                    WHERE STD_ID = ? AND SEM_ID = ? AND STATUS = 'Upcoming'"
                );
                mysqli_stmt_bind_param($stmtDeleteUpcoming, "ss", $std_id, $nextSemId);
                mysqli_stmt_execute($stmtDeleteUpcoming);
            }
            break;

        case 'update_student':
            $std_id = $input['std_id'];
            $name = $input['name'];
            $gd = $input['gd'];
            $dob = $input['dob'];
            $phone = $input['phone'];
            $adr = $input['adr'];
            $newBlock = $input['block_id'] ?? null;
            $newRoom  = $input['room_id'] ?? null;

            // 1. Cập nhật thông tin cơ bản
            $stmt = mysqli_prepare($conn, "
                UPDATE STUDENT
                SET STD_NAME = ?, STD_GD = ?, STD_DOB = ?, STD_PHONE = ?, STD_ADR = ?
                WHERE STD_ID = ?
            ");
            mysqli_stmt_bind_param($stmt, "ssssss", $name, $gd, $dob, $phone, $adr, $std_id);
            mysqli_stmt_execute($stmt);

            // 2. Kiểm tra và cập nhật phòng nếu có thay đổi
            if (!empty($newBlock) && !empty($newRoom)) {
                $stmt = mysqli_prepare($conn, "SELECT BLOCK_ID, ROOM_ID FROM CONTRACT WHERE STD_ID = ? AND SEM_ID = ? AND STATUS = 'Active'");
                mysqli_stmt_bind_param($stmt, "ss", $std_id, $semId);
                mysqli_stmt_execute($stmt);
                $contract = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if ($contract) {
                    if ($newBlock !== $contract['BLOCK_ID'] || $newRoom !== $contract['ROOM_ID']) {
                        $stmt = mysqli_prepare($conn, "UPDATE CONTRACT SET BLOCK_ID = ?, ROOM_ID = ? WHERE STD_ID = ? AND SEM_ID = ? AND STATUS = 'Active'");
                        mysqli_stmt_bind_param($stmt, "ssss", $newBlock, $newRoom, $std_id, $semId);
                        mysqli_stmt_execute($stmt);
                    }
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO CONTRACT (STD_ID, SEM_ID, BLOCK_ID, ROOM_ID, STATUS) VALUES (?, ?, ?, ?, 'Active')");
                    mysqli_stmt_bind_param($stmt, "ssss", $std_id, $semId, $newBlock, $newRoom);
                    mysqli_stmt_execute($stmt);
                }
            }
            break;
        
        case 'bulk_mark_renewal':
            if (!isset($input['list']) || !is_array($input['list'])) {
                throw new Exception('Dữ liệu gia hạn không hợp lệ');
            }

            // A. Tìm xem có học kỳ nào đang đợi không
            $resNext = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_STATUS = 'Upcoming' LIMIT 1");
            $nextSem = mysqli_fetch_assoc($resNext);
            $nextSemId = $nextSem ? $nextSem['SEM_ID'] : null;

            foreach ($input['list'] as $item) {
                $is_marked = (int)$item['is_marked'];
                $std_id = $item['std_id'];  

                if ($nextSemId) {
                    if ($is_marked === 1) {
                        // Đồng bộ sang kỳ tới
                        $syncQuery = "INSERT IGNORE INTO CONTRACT (STD_ID, SEM_ID, BLOCK_ID, ROOM_ID, STATUS)
                                      SELECT STD_ID, '$nextSemId', BLOCK_ID, ROOM_ID, 'Upcoming'
                                      FROM CONTRACT 
                                      WHERE STD_ID = '$std_id' AND SEM_ID = '$semId' AND STATUS = 'Active'";
                        mysqli_query($conn, $syncQuery);
                    } else {
                        // Nếu bỏ tick, xóa bản ghi Upcoming tương ứng
                        mysqli_query($conn, "DELETE FROM CONTRACT WHERE STD_ID = '$std_id' AND SEM_ID = '$nextSemId' AND STATUS = 'Upcoming'");
                    }
                }
            }
            break;

        default:
            throw new Exception('Hành động không hợp lệ');
    }

    mysqli_commit($conn);
    echo json_encode(['status' => 'success']);

} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    $message = ($e->getSqlState() === '45000') ? $e->getMessage() : 'Lỗi cơ sở dữ liệu hệ thống';
    echo json_encode(['status' => 'error', 'message' => $message]);
} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) mysqli_close($conn);
}
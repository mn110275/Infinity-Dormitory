<?php
session_start();
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

// 1. XỬ LÝ GET REQUEST (Lấy lịch sử học kỳ - Trả về HTML)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_history') {
    $semId = mysqli_real_escape_string($conn, $_GET['sem_id']);
    
    $query = "SELECT s.STD_ID, s.STD_NAME, c.BLOCK_ID, c.ROOM_ID, c.STATUS
              FROM CONTRACT c
              JOIN STUDENT s ON c.STD_ID = s.STD_ID
              WHERE c.SEM_ID = ?
              ORDER BY c.BLOCK_ID, c.ROOM_ID";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $semId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo '<table style="width:100%; border-collapse: collapse; font-size: 14px;">';
        echo '<thead style="background:#f9fafb;">
                <tr>
                    <th style="padding:15px; font-size: 16px; text-align:center; border-bottom:2px solid #edf2f7; color:#4a5568;">Phòng</th>
                    <th style="padding:15px; font-size: 16px; text-align:center; border-bottom:2px solid #edf2f7; color:#4a5568;">MSSV</th>
                    <th style="padding:15px; font-size: 16px; text-align:center; border-bottom:2px solid #edf2f7; color:#4a5568;">Họ tên</th>
                    <th style="padding:15px; font-size: 16px; text-align:center; border-bottom:2px solid #edf2f7; color:#4a5568;">Trạng thái</th>
                </tr>
              </thead><tbody>';
        while ($row = mysqli_fetch_assoc($result)) {
            $statusRaw = $row['STATUS'];

            $statusMap = [
                'Active'     => ['label' => 'Đang ở', 'color' => '#10b981'],     // Xanh lá
                'Upcoming'   => ['label' => 'Chờ kích hoạt', 'color' => '#3b82f6'], // Xanh dương
                'Expired'    => ['label' => 'Hết hạn', 'color' => '#718096'],    // Xám
                'Terminated' => ['label' => 'Đã chấm dứt sớm', 'color' => '#ef4444']  // Đỏ
            ];

            $label = $statusMap[$statusRaw]['label'] ?? $statusRaw;
            $color = $statusMap[$statusRaw]['color'] ?? '#000';

            echo "<tr>
                    <td style='padding:12px 15px; font-size: 16px; text-align:center; border-bottom:1px solid #edf2f7; font-weight:600;'>{$row['BLOCK_ID']}{$row['ROOM_ID']}</td>
                    <td style='padding:12px 15px; font-size: 16px; text-align:center; border-bottom:1px solid #edf2f7;'>{$row['STD_ID']}</td>
                    <td style='padding:12px 15px; font-size: 16px; text-align:center; border-bottom:1px solid #edf2f7;'>{$row['STD_NAME']}</td>
                    <td style='padding:12px 15px; font-size: 16px; text-align:center; border-bottom:1px solid #edf2f7;'>
                        <span style='color:{$color}; font-weight:bold; font-size:16px; text-transform:uppercase;'>{$label}</span>
                    </td>
                  </tr>";
        }
        echo '</tbody></table>';
    } else {
        echo '<div style="text-align:center; font-size: 16px; color:#94a3b8; padding:40px;">Học kỳ này chưa có danh sách sinh viên hoặc hợp đồng.</div>';
    }
    exit;
}

// 2. XỬ LÝ POST REQUEST (JSON API)
header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

try {
    switch ($input['action']) {
        
        // CASE 1: LẬP LỊCH CHUYỂN TIẾP (ROLLOVER)
        case 'semester_rollover':
            mysqli_begin_transaction($conn);

            $newSemId = mysqli_real_escape_string($conn, $input['name']);
            $startDate = mysqli_real_escape_string($conn, $input['start']);
            $endDate = mysqli_real_escape_string($conn, $input['end']);

            // Kiểm tra trùng mã học kỳ
            $checkDup = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_ID = '$newSemId'");
            if (mysqli_num_rows($checkDup) > 0) {
                throw new Exception("Mã học kỳ '$newSemId' đã tồn tại trong hệ thống.");
            }

            // A. Tạo học kỳ mới ở trạng thái CHỜ 
            $stmt = mysqli_prepare($conn, "INSERT INTO SEMESTER (SEM_ID, STARTDATE, ENDDATE, SEM_STATUS) VALUES (?, ?, ?, 'Upcoming')");
            mysqli_stmt_bind_param($stmt, "sss", $newSemId, $startDate, $endDate);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Không thể tạo học kỳ mới.");
            }

            // B. Lấy kỳ hiện tại để copy danh sách gia hạn
            $res = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_STATUS = 'Active' LIMIT 1");
            $oldSem = mysqli_fetch_assoc($res);
            $oldSemId = $oldSem ? $oldSem['SEM_ID'] : null;

            if ($oldSemId) {
                $copyQuery = "INSERT INTO CONTRACT (STD_ID, SEM_ID, BLOCK_ID, ROOM_ID, STATUS)
                            SELECT STD_ID, '$newSemId', BLOCK_ID, ROOM_ID, 'Upcoming'
                            FROM CONTRACT 
                            WHERE SEM_ID = '$oldSemId' AND STATUS = 'Active'";        
                
                if (!mysqli_query($conn, $copyQuery)) {
                    throw new Exception("Lỗi khi chuyển danh sách sinh viên gia hạn.");
                }
            }

            mysqli_commit($conn);
            echo json_encode(['status' => 'success']);
            break;

        // CASE 2: HỦY LỊCH HỌC KỲ SẮP TỚI
        case 'delete_upcoming':
            mysqli_begin_transaction($conn);
            $semId = mysqli_real_escape_string($conn, $input['sem_id']);

            $check = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_ID = '$semId' AND SEM_STATUS = 'Upcoming'");
            if (mysqli_num_rows($check) === 0) {
                throw new Exception("Không thể xóa học kỳ đã kích hoạt/lưu trữ hoặc không tồn tại.");
            }

            mysqli_query($conn, "DELETE FROM CONTRACT WHERE SEM_ID = '$semId' AND STATUS = 'Upcoming'");
            mysqli_query($conn, "DELETE FROM SEMESTER WHERE SEM_ID = '$semId'");

            mysqli_commit($conn);
            echo json_encode(['status' => 'success']);
            break;
        
        case 'edit_upcoming':
            $semId = mysqli_real_escape_string($conn, $input['sem_id']);
            $newEnd = mysqli_real_escape_string($conn, $input['new_end']);

            $updateQuery = 
                "UPDATE SEMESTER 
                SET ENDDATE = '$newEnd' 
                WHERE SEM_ID = '$semId' AND SEM_STATUS = 'Upcoming'";
            if (mysqli_query($conn, $updateQuery)) {
                echo json_encode(['status' => 'success']);
            } else {
                throw new Exception("Không thể cập nhật ngày kết thúc.");
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Hành động không được hỗ trợ']);
            break;
    }

} catch (Exception $e) {
    if ($conn) mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
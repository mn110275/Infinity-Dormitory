<?php
// manager/profile_api.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../database_connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'change_password') {
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            throw new Exception('Vui lòng nhập đầy đủ thông tin');
        }

        if (strlen($newPassword) < 6) {
            throw new Exception('Mật khẩu mới phải có ít nhất 6 ký tự');
        }

        mysqli_set_charset($conn, "utf8mb4");

        // Verify current password
        $checkQuery = "SELECT PASS FROM USERS WHERE USER_ID = ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['PASS'] !== $currentPassword) {
                throw new Exception('Mật khẩu hiện tại không đúng');
            }

            $updateQuery = "UPDATE USERS SET PASS = ? WHERE USER_ID = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "si", $newPassword, $userId);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đổi mật khẩu thành công'
                ]);
            } else {
                throw new Exception('Không thể cập nhật mật khẩu');
            }
        } else {
            throw new Exception('Không tìm thấy tài khoản');
        }
    } else {
        throw new Exception('Action không hợp lệ');
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
?>
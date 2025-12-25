<?php
// student/api/requests_api.php
session_start();
header('Content-Type: application/json');
require_once '../../database_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $prId = $input['pr_id'] ?? 0;

    if ($action === 'revoke') {
        // Kiểm tra quyền: Báo cáo phải thuộc về sinh viên đang đăng nhập
        $query = "DELETE p FROM PROBLEM p 
                  JOIN STUDENT s ON p.STD_ID = s.STD_ID 
                  WHERE p.PR_ID = ? AND s.USER_ID = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $prId, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Không tìm thấy báo cáo hoặc bạn không có quyền thu hồi');
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
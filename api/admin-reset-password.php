<?php
// api/admin-reset-password.php - Admin reset password cho user về mặc định
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../database_connection.php';

// Kiểm tra quyền admin (bạn cần implement logic này)
// $is_admin = $_SESSION['is_admin'] ?? false;
// if (!$is_admin) {
//     echo json_encode(['success' => false, 'error' => 'Không có quyền']);
//     exit;
// }

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    $new_password = $data['new_password'] ?? '123456'; // Password mặc định
    
    if (!$user_id) {
        throw new Exception('Thiếu user_id');
    }
    
    // Hash password mới
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $query = "UPDATE NGUOIDUNG SET PASSWORD_ND = ? WHERE ID_USER = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Lấy thông tin user để trả về
        $get_user = "SELECT ID_USER, NAME, EMAIL FROM NGUOIDUNG WHERE ID_USER = ?";
        $stmt2 = mysqli_prepare($conn, $get_user);
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);
        $user = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reset password thành công',
            'user' => $user,
            'new_password' => $new_password
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception(mysqli_error($conn));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
?>
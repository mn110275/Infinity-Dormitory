<?php
session_start();
// Tắt mọi thông báo lỗi hiển thị dạng HTML để không làm hỏng JSON
error_reporting(0); 
header('Content-Type: application/json');

try {
    require_once '../../database_connection.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['pr_ids'] ?? [];

    // Debug 1: Kiểm tra Session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Lỗi: Bạn chưa đăng nhập hoặc Session bị mất');
    }

    // Debug 2: Kiểm tra dữ liệu gửi lên
    if (empty($ids)) {
        throw new Exception('Lỗi: JavaScript chưa gửi được danh sách ID lên API');
    }

    // Lấy Block của Manager
    $stmtMng = mysqli_prepare($conn, "SELECT MNG_BLOCK FROM MANAGER WHERE USER_ID = ?");
    mysqli_stmt_bind_param($stmtMng, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmtMng);
    $resMng = mysqli_stmt_get_result($stmtMng);
    $manager = mysqli_fetch_assoc($resMng);
    
    if (!$manager) {
        throw new Exception('Lỗi: Tài khoản này không có trong bảng MANAGER');
    }
    
    $myBlock = $manager['MNG_BLOCK'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Debug 3: Kiểm tra SQL
    $query = "DELETE FROM PROBLEM WHERE PR_ID IN ($placeholders) AND BLOCK_ID = ?";
    $stmtDel = mysqli_prepare($conn, $query);
    
    $types = str_repeat('i', count($ids)) . 's';
    $params = [...array_map('intval', $ids), $myBlock];
    
    mysqli_stmt_bind_param($stmtDel, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmtDel)) {
        throw new Exception('Lỗi SQL: ' . mysqli_error($conn));
    }

    echo json_encode([
        'success' => true, 
        'affected' => mysqli_stmt_affected_rows($stmtDel),
        'debug_info' => ['user' => $_SESSION['user_id'], 'block' => $myBlock, 'ids_received' => $ids]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
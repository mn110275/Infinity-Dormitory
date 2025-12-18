<?php
// api/get-students.php - API riêng cho danh sách sinh viên
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Include file kết nối database có sẵn
require_once '../database_connection.php';

try {
    if (!$conn) {
        throw new Exception('Không có kết nối database');
    }

    mysqli_set_charset($conn, "utf8mb4");
    
    // Query lấy danh sách sinh viên với thông tin phòng
    $query = "SELECT 
                s.MSSV,
                s.HO_TEN_SV,
                s.CONTACT_SV,
                s.ADDRESS_SV,
                s.SEMESTER_DK,
                s.IMAGE_SV,
                p.ROOM_NAME,
                t.TEN_TOA
              FROM SINHVIEN s
              INNER JOIN PHONG p ON s.ID_ROOM = p.ID_ROOM
              LEFT JOIN TOA t ON p.ID_TOA = t.ID_TOA
              ORDER BY t.TEN_TOA, p.ROOM_NAME, s.HO_TEN_SV";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Lỗi query: ' . mysqli_error($conn));
    }
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = [
            'mssv' => $row['MSSV'] ?: '',
            'name' => $row['HO_TEN_SV'] ?: '',
            'contact' => $row['CONTACT_SV'] ?: '',
            'address' => $row['ADDRESS_SV'] ?: '',
            'semester' => $row['SEMESTER_DK'] ?: '',
            'image' => $row['IMAGE_SV'] ?: '',
            'room' => $row['ROOM_NAME'] ?: '',
            'building' => $row['TEN_TOA'] ?: 'N/A'
        ];
    }
    
    // Trả về JSON
    echo json_encode([
        'success' => true,
        'data' => $students,
        'total' => count($students),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// Đóng kết nối
if (isset($conn)) {
    mysqli_close($conn);
}
?>
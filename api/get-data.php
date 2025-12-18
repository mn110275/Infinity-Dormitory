<?php
// api/get-data.php - Lấy dữ liệu từ database
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Include file kết nối database có sẵn
require_once '../database_connection.php';

try {
    if (!$conn) {
        throw new Exception('Không có kết nối database');
    }

    mysqli_set_charset($conn, "utf8mb4");
    
    // Lấy danh sách phòng
    $roomQuery = "SELECT 
                    p.ID_ROOM,
                    p.ROOM_NAME,
                    p.CAPACITY,
                    p.OCCUPIED_SLOT,
                    t.TEN_TOA
                  FROM PHONG p
                  LEFT JOIN TOA t ON p.ID_TOA = t.ID_TOA
                  ORDER BY t.TEN_TOA, p.ROOM_NAME";
    
    $roomResult = mysqli_query($conn, $roomQuery);
    
    if (!$roomResult) {
        throw new Exception('Lỗi query phòng: ' . mysqli_error($conn));
    }
    
    $rooms = [];
    while ($row = mysqli_fetch_assoc($roomResult)) {
        $rooms[$row['ID_ROOM']] = [
            'id' => (int)$row['ID_ROOM'],
            'number' => $row['ROOM_NAME'],
            'building' => $row['TEN_TOA'] ?: 'N/A',
            'capacity' => (int)($row['CAPACITY'] ?: 0),
            'occupied' => (int)($row['OCCUPIED_SLOT'] ?: 0),
            'students' => [],
            'students_detail' => [],
            'items' => [],
            'images' => []
        ];
    }
    
    // Lấy chi tiết sinh viên theo phòng
    $studentQuery = "SELECT 
                        s.ID_ROOM,
                        s.MSSV,
                        s.HO_TEN_SV,
                        s.CONTACT_SV,
                        s.ADDRESS_SV,
                        s.SEMESTER_DK,
                        s.IMAGE_SV
                     FROM SINHVIEN s
                     ORDER BY s.ID_ROOM, s.HO_TEN_SV";
    
    $studentResult = mysqli_query($conn, $studentQuery);
    
    if (!$studentResult) {
        throw new Exception('Lỗi query sinh viên: ' . mysqli_error($conn));
    }
    
    while ($student = mysqli_fetch_assoc($studentResult)) {
        $roomId = (int)$student['ID_ROOM'];
        
        if (isset($rooms[$roomId])) {
            // Thêm tên vào mảng students (để tương thích code cũ)
            $rooms[$roomId]['students'][] = $student['HO_TEN_SV'];
            
            // Thêm chi tiết đầy đủ
            $rooms[$roomId]['students_detail'][] = [
                'MSSV' => $student['MSSV'] ?: '',
                'HO_TEN_SV' => $student['HO_TEN_SV'] ?: '',
                'CONTACT_SV' => $student['CONTACT_SV'] ?: '',
                'ADDRESS_SV' => $student['ADDRESS_SV'] ?: '',
                'SEMESTER_DK' => $student['SEMESTER_DK'] ?: '',
                'IMAGE_SV' => $student['IMAGE_SV'] ?: ''
            ];
        }
    }
    
    // Lấy thiết bị/cơ sở vật chất
    $equipQuery = "SELECT 
                    c.ID_ROOM,
                    c.LOAI_CSVC,
                    COUNT(*) as quantity,
                    c.STATUS_CSVC,
                    GROUP_CONCAT(DISTINCT c.IMAGE_CSVC SEPARATOR '|') as images
                   FROM COSOVATCHAT c
                   WHERE c.IMAGE_CSVC IS NOT NULL AND c.IMAGE_CSVC != ''
                   GROUP BY c.ID_ROOM, c.LOAI_CSVC, c.STATUS_CSVC
                   
                   UNION ALL
                   
                   SELECT 
                    c.ID_ROOM,
                    c.LOAI_CSVC,
                    COUNT(*) as quantity,
                    c.STATUS_CSVC,
                    NULL as images
                   FROM COSOVATCHAT c
                   WHERE c.IMAGE_CSVC IS NULL OR c.IMAGE_CSVC = ''
                   GROUP BY c.ID_ROOM, c.LOAI_CSVC, c.STATUS_CSVC";
    
    $equipResult = mysqli_query($conn, $equipQuery);
    
    if (!$equipResult) {
        throw new Exception('Lỗi query thiết bị: ' . mysqli_error($conn));
    }
    
    // Thêm thiết bị vào phòng
    while ($eq = mysqli_fetch_assoc($equipResult)) {
        $roomId = (int)$eq['ID_ROOM'];
        
        if (isset($rooms[$roomId])) {
            $itemName = $eq['LOAI_CSVC'];
            
            // Cộng dồn số lượng
            if (isset($rooms[$roomId]['items'][$itemName])) {
                $rooms[$roomId]['items'][$itemName] += (int)$eq['quantity'];
            } else {
                $rooms[$roomId]['items'][$itemName] = (int)$eq['quantity'];
            }
            
            // Thêm ảnh nếu có
            if (!empty($eq['images'])) {
                $imgs = array_filter(explode('|', $eq['images']));
                if (count($imgs) > 0) {
                    if (!isset($rooms[$roomId]['images'][$itemName])) {
                        $rooms[$roomId]['images'][$itemName] = [];
                    }
                    $rooms[$roomId]['images'][$itemName] = array_merge(
                        $rooms[$roomId]['images'][$itemName], 
                        $imgs
                    );
                }
            }
        }
    }
    
    // Chuyển associative array thành indexed array
    $roomsData = array_values($rooms);
    
    // Trả về JSON
    echo json_encode([
        'success' => true,
        'rooms' => $roomsData,
        'total_rooms' => count($roomsData),
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
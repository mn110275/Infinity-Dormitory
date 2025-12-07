<?php
// api/get-data.php - Lấy dữ liệu từ database
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Include file kết nối database có sẵn
require_once '../database_connection.php';

try {
    // Kiểm tra kết nối
    if (!$conn) {
        throw new Exception('Không có kết nối database');
    }
    
    // Set charset UTF-8
    mysqli_set_charset($conn, "utf8mb4");
    
    // Lấy danh sách phòng với sinh viên
    $roomQuery = "SELECT 
                    p.ID_ROOM,
                    p.ROOM_NAME,
                    p.CAPACITY,
                    p.OCCUPIED_SLOT,
                    t.TEN_TOA,
                    GROUP_CONCAT(DISTINCT s.HO_TEN_SV ORDER BY s.HO_TEN_SV SEPARATOR '|') as students
                  FROM PHONG p
                  LEFT JOIN TOA t ON p.ID_TOA = t.ID_TOA
                  LEFT JOIN SINHVIEN s ON p.ID_ROOM = s.ID_ROOM
                  GROUP BY p.ID_ROOM, p.ROOM_NAME, p.CAPACITY, p.OCCUPIED_SLOT, t.TEN_TOA
                  ORDER BY t.TEN_TOA, p.ROOM_NAME";
    
    $roomResult = mysqli_query($conn, $roomQuery);
    
    if (!$roomResult) {
        throw new Exception('Lỗi query phòng: ' . mysqli_error($conn));
    }
    
    $rooms = [];
    while ($row = mysqli_fetch_assoc($roomResult)) {
        $rooms[] = $row;
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
    
    $equipment = [];
    while ($row = mysqli_fetch_assoc($equipResult)) {
        $equipment[] = $row;
    }
    
    // Tổ chức dữ liệu
    $roomsData = [];
    foreach ($rooms as $room) {
        $roomsData[] = [
            'id' => (int)$room['ID_ROOM'],
            'number' => $room['ROOM_NAME'],
            'building' => $room['TEN_TOA'] ?: 'N/A',
            'capacity' => (int)($room['CAPACITY'] ?: 0),
            'occupied' => (int)($room['OCCUPIED_SLOT'] ?: 0),
            'students' => $room['students'] ? explode('|', $room['students']) : [],
            'items' => [],
            'images' => []
        ];
    }
    
    // Thêm thiết bị vào phòng
    foreach ($equipment as $eq) {
        $roomId = (int)$eq['ID_ROOM'];
        
        // Tìm index của phòng
        $roomIndex = -1;
        foreach ($roomsData as $idx => $room) {
            if ($room['id'] === $roomId) {
                $roomIndex = $idx;
                break;
            }
        }
        
        if ($roomIndex !== -1) {
            $itemName = $eq['LOAI_CSVC'];
            
            // Cộng dồn số lượng
            if (isset($roomsData[$roomIndex]['items'][$itemName])) {
                $roomsData[$roomIndex]['items'][$itemName] += (int)$eq['quantity'];
            } else {
                $roomsData[$roomIndex]['items'][$itemName] = (int)$eq['quantity'];
            }
            
            // Thêm ảnh nếu có
            if (!empty($eq['images'])) {
                $imgs = array_filter(explode('|', $eq['images']));
                if (count($imgs) > 0) {
                    if (!isset($roomsData[$roomIndex]['images'][$itemName])) {
                        $roomsData[$roomIndex]['images'][$itemName] = [];
                    }
                    $roomsData[$roomIndex]['images'][$itemName] = array_merge(
                        $roomsData[$roomIndex]['images'][$itemName], 
                        $imgs
                    );
                }
            }
        }
    }
    
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
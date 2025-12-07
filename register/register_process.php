<?php
session_start();
require '../database_connection.php'; // kết nối chung

// Biến trả về cho JS nếu cần JSON
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $mssv  = mysqli_real_escape_string($conn, $_POST['mssv']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    if (!$name || !$mssv || !$email) {
        $response['message'] = 'Vui lòng điền đủ thông tin bắt buộc';
    } else {
        // Chèn vào DB, mặc định STATUS_DON = 'Chưa xử lý', ID_QL = NULL
        $sql = "INSERT INTO DONDANGKY (HO_TEN_NDK, MSSV_NDK, SDT_NDK, EMAIL_NDK, STATUS_DON, ID_QL)
                VALUES ('$name','$mssv','$phone','$email','Chưa xử lý',NULL)";
        if (mysqli_query($conn, $sql)) {
            $response['success'] = true;
            $response['message'] = 'Đăng ký thành công!';
        } else {
            $response['message'] = 'Lỗi khi ghi dữ liệu: ' . mysqli_error($conn);
        }
    }
} else {
    $response['message'] = 'Phải gửi POST';
}

// Nếu JS muốn JSON, dùng:
header('Content-Type: application/json');
echo json_encode($response);

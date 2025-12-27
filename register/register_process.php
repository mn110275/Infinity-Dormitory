<?php
session_start();
require '../database_connection.php';

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
        $sql = "INSERT INTO REGIFORM (REG_NAME, REG_STD_ID, REG_PHONE, REG_EMAIL)
                VALUES ('$name','$mssv','$phone','$email')";
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

header('Content-Type: application/json');
echo json_encode($response);

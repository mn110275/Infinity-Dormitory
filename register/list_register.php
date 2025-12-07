<?php
session_start();
require '../database_connection.php';

// Mọi quản lý đều có thể xem tất cả đơn
$sql = "SELECT * FROM DONDANGKY ORDER BY ID_DON DESC";
$res = mysqli_query($conn, $sql);
$rows = [];
while($row = mysqli_fetch_assoc($res)){
    $rows[] = $row;
}
header('Content-Type: application/json');
echo json_encode($rows);
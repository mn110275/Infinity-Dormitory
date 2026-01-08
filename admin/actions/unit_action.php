<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

header('Content-Type: application/json');

$action = $_POST['form_action'] ?? '';
$year   = (int)($_POST['year'] ?? 0);
$month  = (int)($_POST['month'] ?? 0);

if ($action === 'add') {
    $elec = (int)$_POST['elec'];
    $water = (int)$_POST['water'];
    $stmt = mysqli_prepare($conn, "INSERT INTO UNIT (UYEAR, UMONTH, ELEC, WATER) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiii", $year, $month, $elec, $water);
    echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
    exit;
}

if ($action === 'update') {
    $res = mysqli_query($conn, "SELECT ELEC, WATER FROM UNIT WHERE UYEAR=$year AND UMONTH=$month");
    $old = mysqli_fetch_assoc($res);
    $elec  = isset($_POST['elec']) ? (int)$_POST['elec'] : $old['ELEC'];
    $water = isset($_POST['water']) ? (int)$_POST['water'] : $old['WATER'];

    $stmt = mysqli_prepare($conn, "UPDATE UNIT SET ELEC=?, WATER=? WHERE UYEAR=? AND UMONTH=?");
    mysqli_stmt_bind_param($stmt, "iiii", $elec, $water, $year, $month);
    echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
    exit;
}
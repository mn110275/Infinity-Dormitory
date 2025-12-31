<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

mysqli_set_charset($conn, 'utf8mb4');

$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

/* =========================================================
   GET: LOAD UNIT DATA (NÚT XEM)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get') {
    $year  = isset($_GET['year'])  ? (int)$_GET['year']  : 0;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

    if ($year <= 0 || $month < 1 || $month > 12) {
        echo json_encode([
            'electric_unit' => null,
            'water_unit'    => null,
            'status'        => 'invalid'
        ]);
        exit;
    }

    if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
        $timeStatus = 'past';
    } else {
        $timeStatus = 'current_or_future';
    }

    $status = ($timeStatus === 'past') ? 'past' : 'forecast';

    $stmt = mysqli_prepare(
        $conn,
        "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $year, $month);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $unit = mysqli_fetch_assoc($res);

    if ($unit) {
        $status = ($timeStatus === 'past') ? 'past' : 'real';

        echo json_encode([
            'electric_unit' => (int)$unit['ELEC'],
            'water_unit'    => (int)$unit['WATER'],
            'status'        => $status
        ]);
    } else {
        $prev = mysqli_prepare(
            $conn,
            "SELECT ELEC, WATER FROM UNIT WHERE (UYEAR < ? OR (UYEAR = ? AND UMONTH < ?))
            ORDER BY UYEAR DESC, UMONTH DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($prev, "iii", $year, $year, $month);
        mysqli_stmt_execute($prev);
        $prevRes = mysqli_stmt_get_result($prev);
        $prevUnit = mysqli_fetch_assoc($prevRes);

        $prevElec = $prevUnit ? (int)$prevUnit['ELEC'] : null;
        $prevWater = $prevUnit ? (int)$prevUnit['WATER'] : null;

        echo json_encode([
            'electric_unit' => $prevElec,
            'water_unit'    => $prevWater,
            'status'        => $status
        ]);
    }
    exit;
}


/* =========================================================
   POST: SAVE / UPDATE UNIT
========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    // Lấy dữ liệu
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    $month = isset($_POST['month']) ? (int)$_POST['month'] : 0;
    $elecRaw = $_POST['elec'] ?? null;
    $waterRaw = $_POST['water'] ?? null;

    // Validate
    if ($year <= 0 || $month < 1 || $month > 12) {
        echo json_encode(['success' => false, 'message' => 'Năm hoặc tháng không hợp lệ']);
        exit;
    }
    if ($elecRaw === null || $elecRaw === '' || !ctype_digit((string)$elecRaw) || (int)$elecRaw < 0) {
        echo json_encode(['success' => false, 'message' => 'Đơn giá điện không hợp lệ']);
        exit;
    }
    if ($waterRaw === null || $waterRaw === '' || !ctype_digit((string)$waterRaw) || (int)$waterRaw < 0) {
        echo json_encode(['success' => false, 'message' => 'Đơn giá nước không hợp lệ']);
        exit;
    }

    // Kiểm tra tồn tại bản ghi
    $stmtCheck = mysqli_prepare($conn, "SELECT 1 FROM UNIT WHERE UYEAR = ? AND UMONTH = ?");
    mysqli_stmt_bind_param($stmtCheck, "ii", $year, $month);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_num_rows($resCheck) > 0) {
        echo json_encode(['success' => false, 'message' => 'Đơn giá tháng này đã tồn tại']);
        exit;
    }

    // Insert mới
    $elec = (int)$elecRaw;
    $water = (int)$waterRaw;

    $stmtInsert = mysqli_prepare($conn,
        "INSERT INTO UNIT (UYEAR, UMONTH, ELEC, WATER) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmtInsert, "iiii", $year, $month, $elec, $water);
    $ok = mysqli_stmt_execute($stmtInsert);

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu dữ liệu']);
    }
    exit;
}

/* ================== INPUT ================== */
$year  = isset($_POST['year'])  ? (int)$_POST['year']  : 0;
$month = isset($_POST['month']) ? (int)$_POST['month'] : 0;

$elecRaw  = $_POST['elec']  ?? null;
$waterRaw = $_POST['water'] ?? null;

/* ================== VALIDATE ================== */
if ($year <= 0 || $month < 1 || $month > 12) {
    echo json_encode([
        'success' => false,
        'message' => 'Tháng hoặc năm không hợp lệ'
    ]);
    exit;
}

/* --- không cho sửa quá khứ --- */
$isEditable =
    ($year > $currentYear) ||
    ($year == $currentYear && $month >= $currentMonth);

if (!$isEditable) {
    echo json_encode([
        'success' => false,
        'message' => 'Không được chỉnh đơn giá của tháng đã qua'
    ]);
    exit;
}

$hasElec  = ($elecRaw !== null && $elecRaw !== '');
$hasWater = ($waterRaw !== null && $waterRaw !== '');

if (!$hasElec && !$hasWater) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có dữ liệu cần cập nhật'
    ]);
    exit;
}

if ($hasElec && (!ctype_digit((string)$elecRaw) || (int)$elecRaw < 0)) {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn giá điện không hợp lệ'
    ]);
    exit;
}

if ($hasWater && (!ctype_digit((string)$waterRaw) || (int)$waterRaw < 0)) {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn giá nước không hợp lệ'
    ]);
    exit;
}

$elec  = $hasElec  ? (int)$elecRaw  : null;
$water = $hasWater ? (int)$waterRaw : null;

/* ================== UPSERT ================== */
try {
    $check = mysqli_prepare(
        $conn,
        "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?"
    );
    mysqli_stmt_bind_param($check, "ii", $year, $month);
    mysqli_stmt_execute($check);
    $res = mysqli_stmt_get_result($check);
    $row = mysqli_fetch_assoc($res);

    if ($row) {
        $newElec  = $hasElec  ? $elec  : $row['ELEC'];
        $newWater = $hasWater ? $water : $row['WATER'];

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE UNIT
             SET ELEC = ?, WATER = ?
             WHERE UYEAR = ? AND UMONTH = ?"
        );
        mysqli_stmt_bind_param($stmt, "iiii",
            $newElec, $newWater, $year, $month
        );
    } else {
        $prev = mysqli_prepare(
            $conn,
            "SELECT ELEC, WATER
            FROM UNIT
            WHERE (UYEAR < ? OR (UYEAR = ? AND UMONTH < ?))
            ORDER BY UYEAR DESC, UMONTH DESC
            LIMIT 1"
        );
        mysqli_stmt_bind_param($prev, "iii", $year, $year, $month);
        mysqli_stmt_execute($prev);
        $prevRes = mysqli_stmt_get_result($prev);
        $prevUnit = mysqli_fetch_assoc($prevRes);

        $finalElec  = $hasElec  ? $elec  : ($prevUnit['ELEC']  ?? 0);
        $finalWater = $hasWater ? $water : ($prevUnit['WATER'] ?? 0);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO UNIT (UYEAR, UMONTH, ELEC, WATER)
            VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iiii",
            $year, $month, $finalElec, $finalWater
        );
    }

    mysqli_stmt_execute($stmt);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống'
    ]);
}

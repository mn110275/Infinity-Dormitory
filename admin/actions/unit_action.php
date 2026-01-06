<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

mysqli_set_charset($conn, 'utf8mb4');

$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

/* =========================================================
   CHỈ CHO PHÉP POST
========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$action = $_POST['form_action'] ?? '';

if ($action === 'add') {
    $year  = (int)($_POST['year']  ?? 0);
    $month = (int)($_POST['month'] ?? 0);
    $elec  = $_POST['elec']  ?? null;
    $water = $_POST['water'] ?? null;

    // Validate
    if ($year <= 0 || $month < 1 || $month > 12) {
        echo json_encode(['success' => false, 'message' => 'Năm hoặc tháng không hợp lệ']);
        exit;
    }
    if (!ctype_digit((string)$elec) || (int)$elec < 0) {
        echo json_encode(['success' => false, 'message' => 'Đơn giá điện không hợp lệ']);
        exit;
    }
    if (!ctype_digit((string)$water) || (int)$water < 0) {
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

    $stmtInsert = mysqli_prepare($conn,
        "INSERT INTO UNIT (UYEAR, UMONTH, ELEC, WATER) VALUES (?, ?, ?, ?)"
    );

    $elec  = (int)$elec;
    $water = (int)$water;   
    mysqli_stmt_bind_param($stmtInsert, "iiii", $year, $month, $elec, $water);
    $ok = mysqli_stmt_execute($stmtInsert);

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu dữ liệu']);
    }
    exit;
}

/* =========================================================
   UPDATE UNIT
========================================================= */
$year  = (int)($_POST['year']  ?? 0);
$month = (int)($_POST['month'] ?? 0);

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

$hasElec  = isset($_POST['elec']);
$hasWater = isset($_POST['water']);

if (!$hasElec && !$hasWater) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có dữ liệu cần cập nhật'
    ]);
    exit;
}

if ($hasElec && (!ctype_digit((string)$_POST['elec']) || (int)$_POST['elec'] < 0)) {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn giá điện không hợp lệ'
    ]);
    exit;
}

if ($hasWater && (!ctype_digit((string)$_POST['water']) || (int)$_POST['water'] < 0)) {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn giá nước không hợp lệ'
    ]);
    exit;
}

/* ================== UPDATE ================== */
$check = mysqli_prepare(
    $conn,
    "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?"
);
mysqli_stmt_bind_param($check, "ii", $year, $month);
mysqli_stmt_execute($check);
$res = mysqli_stmt_get_result($check);
$row = mysqli_fetch_assoc($res);

if (!$row) {
echo json_encode([
    'success' => false,
    'message' => 'Chưa có đơn giá để chỉnh sửa'
]);
exit;
}

$newElec  = $hasElec  ? (int)$_POST['elec']  : (int)$row['ELEC'];
$newWater = $hasWater ? (int)$_POST['water'] : (int)$row['WATER'];

$stmt = mysqli_prepare($conn,
    "UPDATE UNIT
    SET ELEC = ?, WATER = ?
    WHERE UYEAR = ? AND UMONTH = ?"
);
mysqli_stmt_bind_param($stmt, "iiii",
    $newElec, $newWater, $year, $month
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi cập nhật dữ liệu'
    ]);
}

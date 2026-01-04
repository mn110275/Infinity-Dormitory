<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';

requireRole('admin');
header('Content-Type: application/json');

/* =========================
   GET: CHI TIẾT MANAGER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID']);
        exit;
    }

    $sql = "
        SELECT 
            m.MNG_ID,
            m.MNG_NAME,
            DATE_FORMAT(m.MNG_DOB, '%Y-%m-%d') AS MNG_DOB,
            m.MNG_GD,
            m.MNG_PHONE,
            m.MNG_ADR,
            m.MNG_BLOCK,
            u.EMAIL AS MNG_EMAIL
        FROM MANAGER m
        JOIN USERS u ON m.USER_ID = u.USER_ID
        WHERE m.MNG_ID = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    echo json_encode(
        $data
            ? ['success' => true, 'manager' => $data]
            : ['success' => false, 'message' => 'Không tìm thấy']
    );
    exit;
}

/* =========================
   POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* =========================
       ADD MANAGER
    ========================= */
    if ($action === 'add') {

        $name  = $_POST['mng_name'] ?? null;
        $dob   = $_POST['mng_dob'] ?? null;
        $gd    = $_POST['mng_gd'] ?? null;
        $phone = $_POST['mng_phone'] ?? null;
        $adr   = $_POST['mng_adr'] ?? null;
        $block = $_POST['mng_block'] ?? null;
        $email = $_POST['mng_email'] ?? null;

        if (empty($name) || empty($email) || empty($block)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            exit;
        }

        mysqli_begin_transaction($conn);

        try {
            /* 1. Tạo USERS */
            $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn,
                "INSERT INTO USERS (EMAIL, PASS, USER_ROLE)
                 VALUES (?, ?, 'manager')"
            );
            mysqli_stmt_bind_param($stmt, 'ss', $email, $defaultPass);
            mysqli_stmt_execute($stmt);

            $userId = mysqli_insert_id($conn);

            /* 2. Sinh MNG_ID */
            $res = mysqli_query($conn,
                "SELECT MNG_ID FROM MANAGER ORDER BY MNG_ID DESC LIMIT 1"
            );
            if ($row = mysqli_fetch_assoc($res)) {
                $num = intval(substr($row['MNG_ID'], 1)) + 1;
            } else {
                $num = 1;
            }
            $mngId = 'M' . str_pad($num, 2, '0', STR_PAD_LEFT);

            /* 3. Tạo MANAGER */
            $stmt = mysqli_prepare($conn,
                "INSERT INTO MANAGER
                (MNG_ID, MNG_NAME, MNG_DOB, MNG_GD, MNG_PHONE, MNG_ADR, MNG_BLOCK, USER_ID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssi',
                $mngId, $name, $dob, $gd, $phone, $adr, $block, $userId
            );
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /* =========================
       UPDATE
    ========================= */
    if ($action === 'update') {

        $id    = $_POST['id'] ?? null;
        $name  = $_POST['mng_name'] ?? null;
        $dob   = $_POST['mng_dob'] ?? null;
        $gd    = $_POST['mng_gd'] ?? null;
        $phone = $_POST['mng_phone'] ?? null;
        $adr   = $_POST['mng_adr'] ?? null;
        $block = $_POST['mng_block'] ?? null;
        $email = $_POST['mng_email'] ?? null;

        if (empty($id) || empty($name) || empty($email) || empty($block)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            exit;
        }

        mysqli_begin_transaction($conn);

        try {
            /* 1. Lấy USER_ID từ MANAGER */
            $stmt = mysqli_prepare($conn,
                "SELECT USER_ID FROM MANAGER WHERE MNG_ID = ?"
            );
            mysqli_stmt_bind_param($stmt, 's', $id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$row) {
                throw new Exception('Không tìm thấy manager');
            }

            $userId = $row['USER_ID'];

            /* 2. Update USERS.EMAIL */
            $stmt = mysqli_prepare($conn,
                "UPDATE USERS SET EMAIL = ? WHERE USER_ID = ?"
            );
            mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
            mysqli_stmt_execute($stmt);

            /* 3. Update MANAGER */
            $stmt = mysqli_prepare($conn,
                "UPDATE MANAGER SET
                    MNG_NAME = ?,
                    MNG_DOB = ?,
                    MNG_GD = ?,
                    MNG_PHONE = ?,
                    MNG_ADR = ?,
                    MNG_BLOCK = ?
                WHERE MNG_ID = ?"
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssssss',
                $name,
                $dob,
                $gd,
                $phone,
                $adr,
                $block,
                $id
            );
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /* =========================
       DELETE
    ========================= */
    if ($action === 'delete') {

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID']);
            exit;
        }

        mysqli_begin_transaction($conn);
        try {
            /* lấy USER_ID */
            $stmt = mysqli_prepare($conn,
                "SELECT USER_ID FROM MANAGER WHERE MNG_ID = ?"
            );
            mysqli_stmt_bind_param($stmt, 's', $id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$row) throw new Exception('Không tồn tại');

            $userId = $row['USER_ID'];

            /* xóa manager */
            $stmt = mysqli_prepare($conn,
                "DELETE FROM MANAGER WHERE MNG_ID = ?"
            );
            mysqli_stmt_bind_param($stmt, 's', $id);
            mysqli_stmt_execute($stmt);

            /* xóa user */
            $stmt = mysqli_prepare($conn,
                "DELETE FROM USERS WHERE USER_ID = ?"
            );
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

header('Content-Type: application/json');

function sendError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  sendError('Method not allowed');
}

$idDon  = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$idDon || !$action) {
  sendError('Missing parameters');
}

if ($action === 'accept') {
    try {
        $studentId = $_POST['mssv'] ?? null;
        $name      = $_POST['ho_ten'] ?? null;
        $dob       = $_POST['ngay_sinh'] ?? null;
        $phone     = $_POST['contact_sv'] ?? null;
        $address   = $_POST['address_sv'] ?? null;
        $gender    = $_POST['gioi_tinh'] ?? null;
        $roomId    = $_POST['id_room'] ?? null;
        $blockId   = $_POST['id_block'] ?? null; 

        if (!$studentId || !$name || !$dob || !$roomId || !$blockId) {
            throw new Exception('Vui lòng điền đầy đủ thông tin và chọn phòng');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            throw new Exception('Ngày sinh không đúng định dạng YYYY-MM-DD');
        }

        mysqli_begin_transaction($conn);

        // 0. Lấy Kỳ học hiện tại
        $semRes = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE IS_CURRENT = 1 LIMIT 1");
        $currentSem = mysqli_fetch_assoc($semRes);
        if (!$currentSem) throw new Exception('Chưa thiết lập học kỳ hiện tại trong hệ thống');
        $semId = $currentSem['SEM_ID'];

        // 1. Kiểm tra đơn và lấy email
        $stmt = mysqli_prepare($conn, 
          "SELECT REG_EMAIL 
          FROM REGIFORM 
          WHERE REG_ID = ? AND REG_STATUS = 'Chưa xử lý'"
        );
        mysqli_stmt_bind_param($stmt, 'i', $idDon);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        if (!$row) throw new Exception('Đơn đăng ký không tồn tại');
        $email = $row['REG_EMAIL'];

        // 2. Kiểm tra tài khoản 
        $stmt = mysqli_prepare($conn, 
          "SELECT USER_ID 
          FROM USERS 
          WHERE EMAIL = ?"
        );
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $existingUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($existingUser) {
            $userId = $existingUser['USER_ID'];
            // Cập nhật SV cũ
            $stmt = mysqli_prepare($conn, 
                "UPDATE STUDENT 
                SET STD_NAME=?, STD_PHONE=?, STD_ADR=?, STD_GD=?, STD_DOB=?, IS_ACTIVE=1 
                WHERE USER_ID=?"
            );
            mysqli_stmt_bind_param($stmt, 'sssssi', $name, $phone, $address, $gender, $dob, $userId);
        } else {
            // Tạo User mới
            $hashedPass = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 
              "INSERT INTO USERS (EMAIL, PASS, USER_ROLE) VALUES (?, ?, 'student')");
            mysqli_stmt_bind_param($stmt, 'ss', $email, $hashedPass);
            mysqli_stmt_execute($stmt);
            $userId = mysqli_insert_id($conn);

            // Tạo Student mới
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO STUDENT (STD_ID, STD_NAME, STD_PHONE, STD_ADR, STD_GD, STD_DOB, USER_ID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssssi', $studentId, $name, $phone, $address, $gender, $dob, $userId);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi khi lưu thông tin sinh viên: " . mysqli_error($conn));
        }

        // 3. Xử lý Hợp đồng cho kỳ hiện tại
        // Kiểm tra xem đã có hợp đồng Active nào chưa 
        $stmt = mysqli_prepare($conn, 
            "SELECT * FROM CONTRACT 
            WHERE STD_ID = ? AND SEM_ID = ?"
        );
        mysqli_stmt_bind_param($stmt, 'si', $studentId, $semId);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            // Nếu đã có hợp đồng kỳ này, cập nhật lại phòng
            $stmt = mysqli_prepare($conn, 
                "UPDATE CONTRACT 
                SET BLOCK_ID = ?, ROOM_ID = ?, STATUS = 'Active' 
                WHERE STD_ID = ? AND SEM_ID = ?");
            mysqli_stmt_bind_param($stmt, 'sssi', $blockId, $roomId, $studentId, $semId);
        } else {
            // Nếu chưa có, tạo mới hợp đồng
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO CONTRACT (STD_ID, SEM_ID, BLOCK_ID, ROOM_ID, STATUS) 
                VALUES (?, ?, ?, ?, 'Active')"
            );
            mysqli_stmt_bind_param($stmt, 'siss', $studentId, $semId, $blockId, $roomId);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi phòng/hợp đồng: " . mysqli_error($conn));
        }

        // 4. Cập nhật trạng thái đơn
        $stmt = mysqli_prepare($conn, 
          "UPDATE REGIFORM 
          SET REG_STATUS = 'Đã chấp nhận' 
          WHERE REG_ID = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $idDon);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        sendError($e->getMessage());
    }
    exit;
}

// reject
if ($action === 'reject') {
    $stmt = mysqli_prepare($conn,
        "UPDATE REGIFORM SET REG_STATUS = 'Đã từ chối'
         WHERE REG_ID = ? AND REG_STATUS = 'Chưa xử lý'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idDon);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

// undo
if ($action === 'undo') {
    $stmt = mysqli_prepare($conn,
        "UPDATE REGIFORM SET REG_STATUS = 'Chưa xử lý'
         WHERE REG_ID = ? AND REG_STATUS = 'Đã từ chối'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $idDon);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true]);
    exit;
}

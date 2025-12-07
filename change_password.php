<?php
// change-password.php - Trang đổi mật khẩu cho user
session_start();
require_once 'database_connection.php';

// Giả sử user đã đăng nhập, lấy ID từ session
// Bạn cần thêm logic login thực tế
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("Vui lòng đăng nhập trước!");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu mới không khớp!";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    } else {
        // Lấy password hiện tại từ DB
        $query = "SELECT PASSWORD_ND FROM NGUOIDUNG WHERE ID_USER = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if (!$user) {
            $error = "Không tìm thấy user!";
        } elseif (!password_verify($current_password, $user['PASSWORD_ND'])) {
            $error = "Mật khẩu hiện tại không đúng!";
        } else {
            // Đổi password
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE NGUOIDUNG SET PASSWORD_ND = ? WHERE ID_USER = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $new_hashed, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = "Đổi mật khẩu thành công!";
            } else {
                $error = "Lỗi: " . mysqli_error($conn);
            }
        }
    }
}

// Lấy thông tin user
$query = "SELECT NAME, EMAIL FROM NGUOIDUNG WHERE ID_USER = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_info = mysqli_fetch_assoc($result);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu - InfiDorm</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .user-info {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .user-info p {
            margin: 5px 0;
            color: #475569;
        }
        .user-info strong {
            color: #1e293b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .btn:active {
            transform: translateY(0);
        }
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 14px;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Đổi mật khẩu</h1>
        
        <div class="user-info">
            <p><strong>Tên:</strong> <?php echo htmlspecialchars($user_info['NAME']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['EMAIL']); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message success">✅ <?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="current_password">Mật khẩu hiện tại</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit" class="btn">Đổi mật khẩu</button>
        </form>

        <div class="password-requirements">
            <strong>Yêu cầu mật khẩu:</strong>
            <ul style="margin: 8px 0 0 20px;">
                <li>Tối thiểu 6 ký tự</li>
                <li>Nên kết hợp chữ, số và ký tự đặc biệt</li>
            </ul>
        </div>

        <div class="back-link">
            <a href="dashboard.php">← Quay lại trang chủ</a>
        </div>
    </div>
</body>
</html>
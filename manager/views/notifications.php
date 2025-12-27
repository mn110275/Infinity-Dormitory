<?php
// manager/views/notifications.php
require_once '../database_connection.php';

$managerId = null;
$managerBlock = $_SESSION['block'];
$notifications = [];
$rooms = [];
$success = false;
$error = null;

// Get manager ID
try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");
        
        $query = "SELECT MNG_ID FROM MANAGER WHERE USER_ID = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $managerId = $row['MNG_ID'];
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

try {
    if ($conn) {
        $roomQuery = "SELECT ROOM_ID FROM ROOM WHERE BLOCK_ID = ? ORDER BY ROOM_ID";
        $stmt = mysqli_prepare($conn, $roomQuery);
        mysqli_stmt_bind_param($stmt, "s", $managerBlock);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $rooms[] = $row['ROOM_ID'];
        }
    }
} catch (Exception $e) {
    // Ignore
}

// Handle DELETE notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_notification') {
    try {
        $notiId = $_POST['noti_id'] ?? '';
        
        if (empty($notiId)) {
            throw new Exception('Không xác định được thông báo');
        }

        $deleteQuery = "DELETE FROM NOTI WHERE NOTI_ID = ? AND MNG_ID = ?";
        $stmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($stmt, "is", $notiId, $managerId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to clear POST data
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=notifications');
            exit;
        } else {
            throw new Exception('Không thể xóa thông báo');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    try {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $targetType = $_POST['target_type'] ?? 'all';
        $targetRooms = $_POST['target_rooms'] ?? [];
        
        if (!$managerId) {
            throw new Exception('Không xác định được quản lý');
        }

        if ($targetType === 'room' && empty($targetRooms)) {
            throw new Exception('Vui lòng chọn ít nhất một phòng');
        }

        $insertQuery = "INSERT INTO NOTI (TITLE, CONTENT, NOTI_DATE, MNG_ID) 
                        VALUES (?, ?, NOW(), ?)";
        
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "sss", $title, $content, $managerId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to prevent form resubmission (Post-Redirect-Get pattern)
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=notifications');
            exit;
        } else {
            throw new Exception('Không thể gửi thông báo');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
}

try {
    if ($conn && $managerId) {
        $notiQuery = "SELECT NOTI_ID, TITLE, CONTENT, NOTI_DATE 
                      FROM NOTI 
                      WHERE MNG_ID = ? 
                      ORDER BY NOTI_DATE DESC 
                      LIMIT 10";
        
        $stmt = mysqli_prepare($conn, $notiQuery);
        mysqli_stmt_bind_param($stmt, "s", $managerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
    }
} catch (Exception $e) {
    // Ignore
}
?>

<div class="notifications-container">
  <h2>Gửi thông báo đến sinh viên</h2>

  <div class="notification-layout">
    <div class="notification-form-section">
      <div class="card">
        <form method="POST" id="notificationForm">
          <input type="hidden" name="action" value="send_notification">
          
          <div class="form-group">
            <label for="title" color="red">Tiêu đề thông báo *</label>
            <input type="text" name="title" id="title" class="form-control" 
                   required maxlength="255" placeholder="Nhập tiêu đề thông báo...">
          </div>

          <div class="form-group">
            <label for="content">Nội dung thông báo *</label>
            <textarea name="content" id="content" class="form-control" 
                      rows="6" required maxlength="500" 
                      placeholder="Nhập nội dung chi tiết... (Tối đa 500 ký tự)"></textarea>
          </div>

          <div class="form-group">
            <label>Đối tượng nhận</label>
            <div class="target-options">
              <label class="radio-option">
                <input type="radio" name="target_type" value="all" checked>
                <span>Toàn bộ sinh viên tòa <?= htmlspecialchars($managerBlock) ?></span>
              </label>
              <label class="radio-option">
                <input type="radio" name="target_type" value="room">
                <span>Chọn phòng cụ thể</span>
              </label>
            </div>
          </div>

          <div class="form-group" id="roomSelectionGroup" style="display: none;">
            <label>Chọn phòng</label>
            <div class="room-checkboxes">
              <?php foreach ($rooms as $room): ?>
                <label class="checkbox-option">
                  <input type="checkbox" name="target_rooms[]" value="<?= htmlspecialchars($room) ?>">
                  <span><?= htmlspecialchars($room) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if (!empty($rooms)): ?>
            <button type="button" class="btn btn-link" id="selectAllRooms">
              Chọn tất cả
            </button>
            <?php endif; ?>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('notificationForm').reset()">
              Xóa form
            </button>
            <button type="submit" class="btn btn-primary">
              Gửi thông báo
            </button>
          </div>
        </form>
      </div>
      
      <?php if ($success): ?>
        <div class="alert alert-success">
          Đã gửi thông báo thành công.
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error">
          Lỗi: <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right: Recent notifications -->
    <div class="notification-history-section">
      <div class="card">
        <h3>Lịch sử thông báo gần đây</h3>
        <?php if (empty($notifications)): ?>
          <div class="empty-state-small">
            <p>Chưa có thông báo nào</p>
          </div>
        <?php else: ?>
          <div class="notification-list">
            <?php foreach ($notifications as $noti): ?>
              <div class="notification-item">
                <div class="noti-header">
                  <strong><?= htmlspecialchars($noti['TITLE']) ?></strong>
                  <span class="noti-date">
                    <?= date('d/m/Y H:i', strtotime($noti['NOTI_DATE'])) ?>
                  </span>
                </div>
                <div class="noti-content">
                  <?= nl2br(htmlspecialchars($noti['CONTENT'])) ?>
                </div>
                <div class="noti-actions">
                  <form method="POST" style="display: inline;" onsubmit="return confirm('Xác nhận xóa thông báo này?')">
                    <input type="hidden" name="action" value="delete_notification">
                    <input type="hidden" name="noti_id" value="<?= htmlspecialchars($noti['NOTI_ID']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                      Xóa
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="../js/notifications.js"></script>
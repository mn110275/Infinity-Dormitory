<?php
// student/views/notifications.php
require_once '../database_connection.php';

$userId = $_SESSION['user_id'];
$notifications = [];
$error = null;
$studentBlock = "N/A";

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        // Truy vấn lấy thông báo dựa trên Block của sinh viên
        // Cấu trúc: STUDENT -> ROOM -> BLOCK -> MANAGER -> NOTI
        $query = "SELECT n.NOTI_ID, n.TITLE, n.CONTENT, n.NOTI_DATE, m.MNG_NAME, r.BLOCK_ID
                  FROM NOTI n
                  JOIN MANAGER m ON n.MNG_ID = m.MNG_ID
                  JOIN ROOM r ON r.BLOCK_ID = m.MNG_BLOCK
                  JOIN STUDENT s ON s.ROOM_ID = r.ROOM_ID
                  WHERE s.USER_ID = ?
                  GROUP BY n.NOTI_ID
                  ORDER BY n.NOTI_DATE DESC";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
            $studentBlock = $row['BLOCK_ID'];
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="notifications-container">
    <div class="noti-header-main">
        <h2>Thông báo tòa <?= htmlspecialchars($studentBlock) ?></h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">Lỗi: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="noti-list">
        <?php if (empty($notifications)): ?>
            <div class="no-data">
                <p>Hiện chưa có thông báo nào dành cho bạn.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $noti): ?>
                <div class="noti-card" data-id="<?= $noti['NOTI_ID'] ?>">
                    <div class="noti-sidebar-line"></div>
                    
                    <div class="noti-main">
                        <div class="noti-header">
                            <span class="noti-tag">Tin mới</span>
                            <span class="noti-time">
                                📅 <?= date('d/m/Y H:i', strtotime($noti['NOTI_DATE'])) ?>
                            </span>
                        </div>
                        
                        <h3 class="noti-title"><?= htmlspecialchars($noti['TITLE']) ?></h3>
                        
                        <div class="noti-body">
                            <?= nl2br(htmlspecialchars($noti['CONTENT'])) ?>
                        </div>
                        
                        <div class="noti-footer">
                            <span>Người gửi: <strong><?= htmlspecialchars($noti['MNG_NAME']) ?></strong></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
// student/views/my_room.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$roomInfo = null;
$roommates = [];
$error = null;

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");
        
        // Lấy thông tin phòng hiện tại của sinh viên
        $roomQuery = "SELECT s.BLOCK_ID, s.ROOM_ID, r.CAPACITY, r.OCCUPIED, r.GENDER
                      FROM STUDENT s
                      LEFT JOIN ROOM r ON s.BLOCK_ID = r.BLOCK_ID AND s.ROOM_ID = r.ROOM_ID
                      WHERE s.USER_ID = ?";
        
        $stmt = mysqli_prepare($conn, $roomQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $roomInfo = $row;
            
            // Lấy danh sách tất cả sinh viên trong phòng (bao gồm cả sinh viên đang xét)
            if (!empty($row['BLOCK_ID']) && !empty($row['ROOM_ID'])) {
                $roommatesQuery = "SELECT STD_ID, STD_NAME, STD_DOB, STD_GD, STD_PHONE, STD_IMG, USER_ID
                                   FROM STUDENT
                                   WHERE BLOCK_ID = ? AND ROOM_ID = ?
                                   ORDER BY STD_NAME";
                
                $stmt2 = mysqli_prepare($conn, $roommatesQuery);
                mysqli_stmt_bind_param($stmt2, "ss", $row['BLOCK_ID'], $row['ROOM_ID']);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                
                while ($roommate = mysqli_fetch_assoc($result2)) {
                    $roommates[] = $roommate;
                }
            }
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="my-room-container">
    <h2>Phòng của tôi</h2>

    <?php if ($error): ?>
        <div class="alert alert-error">Lỗi: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($roomInfo && !empty($roomInfo['BLOCK_ID']) && !empty($roomInfo['ROOM_ID'])): ?>
        <div class="room-info-card">
            <h3>Phòng <?= htmlspecialchars($roomInfo['ROOM_ID']) ?> - Tòa <?= htmlspecialchars($roomInfo['BLOCK_ID']) ?></h3>
            <div class="room-details">
                <div class="detail-item">
                    <strong>Giới tính:</strong>
                    <span><?= htmlspecialchars($roomInfo['GENDER'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-item">
                    <strong>Sức chứa:</strong>
                    <span><?= htmlspecialchars($roomInfo['CAPACITY'] ?? 'N/A') ?> người</span>
                </div>
                <div class="detail-item">
                    <strong>Đã ở:</strong>
                    <span><?= htmlspecialchars($roomInfo['OCCUPIED'] ?? '0') ?> người</span>
                </div>
            </div>
        </div>

        <!-- Danh sách sinh viên trong phòng -->
        <div class="roommates-section">
            <h3>Danh sách sinh viên trong phòng</h3>
            
            <?php if (empty($roommates)): ?>
                <div class="no-data">
                    <p>Phòng hiện chưa có sinh viên nào.</p>
                </div>
            <?php else: ?>
                <div class="roommates-list">
                    <?php foreach ($roommates as $index => $roommate): ?>
                        <?php 
                            $isCurrentUser = ($roommate['USER_ID'] == $userId);
                        ?>
                        <div class="roommate-card <?= $isCurrentUser ? 'current-user' : '' ?>">
                            <?php if ($isCurrentUser): ?>
                                <div class="current-user-badge">Bạn</div>
                            <?php endif; ?>
                            
                            <div class="roommate-avatar">
                                <?php if (!empty($roommate['STD_IMG'])): ?>
                                    <img src="../<?= htmlspecialchars($roommate['STD_IMG']) ?>" 
                                         alt="<?= htmlspecialchars($roommate['STD_NAME']) ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <span><?= strtoupper(substr($roommate['STD_NAME'], 0, 2)) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="roommate-info">
                                <h4><?= htmlspecialchars($roommate['STD_NAME']) ?></h4>
                                <div class="roommate-details">
                                    <p><strong>MSSV:</strong> <?= htmlspecialchars($roommate['STD_ID']) ?></p>
                                    <p><strong>Giới tính:</strong> <?= htmlspecialchars($roommate['STD_GD']) ?></p>
                                    <p><strong>Ngày sinh:</strong> <?= date('d/m/Y', strtotime($roommate['STD_DOB'])) ?></p>
                                    <?php if (!empty($roommate['STD_PHONE'])): ?>
                                        <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($roommate['STD_PHONE']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="no-data">
            <p>Bạn chưa được phân bổ phòng. Vui lòng liên hệ quản lý để được sắp xếp phòng.</p>
        </div>
    <?php endif; ?>
</div>


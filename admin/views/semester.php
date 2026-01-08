<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$currentSem = null;
$upcomingSem = null;
$semList = [];
$stats = ['renew' => 0, 'leave' => 0, 'total' => 0];

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        $semQuery = "SELECT * FROM SEMESTER ORDER BY STARTDATE DESC";
        $semResult = mysqli_query($conn, $semQuery);

        while ($row = mysqli_fetch_assoc($semResult)) {
            if ($row['SEM_STATUS'] === 'Active') {
                $currentSem = $row;
            }
            if ($row['SEM_STATUS'] === 'Upcoming') {
                $upcomingSem = $row;
            }
            $semList[] = $row;
        }

        if ($currentSem) {
            $cId = mysqli_real_escape_string($conn, $currentSem['SEM_ID']);
            $nextSemId = $upcomingSem ? mysqli_real_escape_string($conn, $upcomingSem['SEM_ID']) : 'NONE';
            $statsQuery = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN EXISTS (
                                SELECT 1 FROM CONTRACT c2 
                                WHERE c2.STD_ID = c.STD_ID 
                                AND c2.SEM_ID = '$nextSemId' 
                                AND c2.STATUS = 'Upcoming'
                            ) THEN 1 ELSE 0 END) as renew
                            FROM CONTRACT c
                            WHERE c.SEM_ID = '$cId' AND c.STATUS = 'Active'";
            $statsResult = mysqli_query($conn, $statsQuery);
            $rowStats = mysqli_fetch_assoc($statsResult);

            $total = (int)($rowStats['total'] ?? 0);
            $renew = (int)($rowStats['renew'] ?? 0);

            $stats = [
                'total'     => $total,
                'renew'     => $renew,
                'leave'     => $total - $renew 
            ];

            $suggestedStart = date('Y-m-d', strtotime($currentSem['ENDDATE'] . ' +1 day'));
        } else {
            $suggestedStart = date('Y-m-d');
        }
    }
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>


    <div class="semester-header">
        <h2 class="title">Cấu hình Học kỳ</h2>
        <div class="current-date">
            <span class="label">Hôm nay</span>
            <span class="value"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <div class="semester-grid">
        
        <div class="card cur-sem shadow-sm ">
            <div class="status-banner">
                <span class="badge">Học kỳ hiện tại</span>
                <?php if ($currentSem): ?>
                    <div class="sem-display-id"><?= htmlspecialchars($currentSem['SEM_ID']) ?></div>
                    <div class="sem-date-range">
                        Từ <?= date('d/m/Y', strtotime($currentSem['STARTDATE'])) ?> 
                        đến <?= date('d/m/Y', strtotime($currentSem['ENDDATE'])) ?>
                    </div>
                <?php else: ?>
                    <div class="sem-display-id">Không có dữ liệu</div>
                <?php endif; ?>
            </div>
            
            <div class="stats-section">
                <h4 class="section-subtitle">Thống kê cư trú</h4>
                <div class="stats-grid">
                    <div class="stat-box info">
                        <span class="stat-value"><?= $stats['total'] ?></span>
                        <span class="stat-label">Tổng sinh viên</span>
                    </div>
                    <div class="stat-box success">
                        <span class="stat-value"><?= $stats['renew'] ?></span>
                        <span class="stat-label">Đã gia hạn</span>
                    </div>
                    <div class="stat-box danger">
                        <span class="stat-value"><?= $stats['leave'] ?></span>
                        <span class="stat-label">Sẽ rời đi</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card next-sem shadow-sm">
            <?php if ($upcomingSem): ?>
                <div class="upcoming-info">
                    <h3 class="section-title">Thông tin kỳ học kế tiếp</h3>
                    
                    <div class="details-list">
                        <div class="detail-item">
                            <span>Mã học kỳ dự kiến:</span>
                            <strong><?= htmlspecialchars($upcomingSem['SEM_ID']) ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Ngày bắt đầu:</span>
                            <strong class="text-primary"><?= date('d/m/Y', strtotime($upcomingSem['STARTDATE'])) ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Ngày kết thúc:</span>
                            <strong><?= date('d/m/Y', strtotime($upcomingSem['ENDDATE'])) ?></strong>
                        </div>
                    </div>

                    <div class="actions-group">
                        <button onclick="SemesterAdmin.editUpcoming('<?= $upcomingSem['SEM_ID'] ?>', '<?= $upcomingSem['ENDDATE'] ?>')" class="btn">Điều chỉnh ngày</button>
                        <button onclick="SemesterAdmin.deleteUpcoming('<?= $upcomingSem['SEM_ID'] ?>')" class="btn btn-danger">Hủy bỏ kế hoạch</button>
                    </div>
                </div>

            <?php else: ?>
                <h3 class="section-title">Lập kế hoạch chuyển tiếp</h3>
                <p class="section-desc">Khởi tạo kỳ học mới để thực hiện việc tự động gia hạn hợp đồng và cập nhật danh sách cư trú.</p>
                
                <form id="formRollover" onsubmit="SemesterAdmin.startRollover(event)">
                    <div class="form-group mb-3">
                        <label class="form-label">Mã học kỳ mới</label>
                        <input type="text" id="new_sem_name" class="form-control" placeholder="Ví dụ: 20252" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" id="new_sem_start" value="<?= $suggestedStart ?>" readonly class="form-control readonly-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngày kết thúc</label>
                            <input type="date" id="new_sem_end" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pro w-full">Lưu & Đặt lịch chuyển tiếp</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mt-30">
        <div class="history-header">
            <h2>Lịch sử dữ liệu theo kỳ</h2>

            <div class="header-controls">
                <button id="btnExportExcel" class="btn btn-success" style="display: none;" onclick="SemesterAdmin.exportToExcel()">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
                <select class="form-control select-sem" onchange="SemesterAdmin.loadHistory(this.value)">
                    <option value="">-- Chọn học kỳ --</option>
                    <?php foreach ($semList as $s): ?>
                        <option value="<?= $s['SEM_ID'] ?>">
                            <?= htmlspecialchars($s['SEM_ID']) ?> 
                            <?php 
                                if ($s['SEM_STATUS'] === 'Active') echo ' (Hiện tại)';
                                elseif ($s['SEM_STATUS'] === 'Upcoming') echo ' (Dự kiến)';
                                else echo ' (Đã lưu trữ)';
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div id="historyContainer" class="history-content">
            <div class="empty-state">
                Vui lòng chọn một học kỳ để hiển thị danh sách sinh viên tương ứng.
            </div>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
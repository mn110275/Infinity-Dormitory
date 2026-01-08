<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$applications = [];
$appError = null;

$status_classes = [
    'Chưa xử lý' => 'chua-xu-ly',
    'Đã chấp nhận' => 'da-chap-nhan',
    'Đã từ chối' => 'da-tu-choi'
];

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");
        $sql = "SELECT * FROM REGIFORM ORDER BY 
                CASE REG_STATUS WHEN 'Chưa xử lý' THEN 1 WHEN 'Đã từ chối' THEN 2 WHEN 'Đã chấp nhận' THEN 3 ELSE 4 END, 
                CREATED_AT DESC";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) { $applications[] = $row; }

        $blocks = [];
        $resB = mysqli_query($conn, "SELECT b.BLOCK_ID, SUM(r.CAPACITY - r.OCCUPIED) as available FROM BLOCK b LEFT JOIN ROOM r ON r.BLOCK_ID = b.BLOCK_ID GROUP BY b.BLOCK_ID");
        while($r = mysqli_fetch_assoc($resB)) { $blocks[] = $r; }

        $rooms = [];
        $resR = mysqli_query($conn, "SELECT ROOM_ID, BLOCK_ID, GENDER, CAPACITY, OCCUPIED FROM ROOM");
        while($r = mysqli_fetch_assoc($resR)) { $rooms[] = $r; }
    }
} catch (Exception $e) { $appError = $e->getMessage(); }
?>

<script>
    window.ALL_APPLICATIONS = <?= json_encode($applications) ?>;
    window.ALL_BLOCKS = <?= json_encode($blocks) ?>;
    window.ALL_ROOMS = <?= json_encode($rooms) ?>;
</script>

<h2>Danh sách đơn đăng ký</h2>

<div class="view-toggles" style="margin: 20px 0; display: flex; gap: 15px;">
    <button class="btn btn-primary toggle-view active" data-status="chua-xu-ly" onclick="ApplicationAdmin.switchView('chua-xu-ly', this)">
        Đơn chờ xử lý (<?= count(array_filter($applications, fn($a) => $a['REG_STATUS'] === 'Chưa xử lý')) ?>)
    </button>
    <button class="btn btn-outline toggle-view" data-status="da-chap-nhan" onclick="ApplicationAdmin.switchView('da-chap-nhan', this)">
        Đã chấp nhận
    </button>
    <button class="btn btn-outline toggle-view" data-status="da-tu-choi" onclick="ApplicationAdmin.switchView('da-tu-choi', this)">
        Đã từ chối
    </button>
</div>

<?php if ($appError): ?>
    <div class="alert alert-error">Lỗi: <?= htmlspecialchars($appError) ?></div>
<?php else: ?>
    <div class="student-table-wrapper">
        <table class="table student-table" id="applicationTable">
            <thead>
                <tr>
                    <th style="width:80px"><div class="th-content" data-col="0"><span>ID</span><span class="sort-icon">⇅</span></div><input class="col-search" data-col="0" placeholder="ID..."></th>
                    <th><div class="th-content" data-col="1"><span>Họ tên</span><span class="sort-icon">⇅</span></div><input class="col-search" data-col="1" placeholder="Tên..."></th>
                    <th style="width:120px"><div class="th-content" data-col="2"><span>MSSV</span><span class="sort-icon">⇅</span></div><input class="col-search" data-col="2" placeholder="MSSV..."></th>
                    <th style="width:140px"><div class="th-content" data-col="5"><span>Trạng thái</span><span class="sort-icon">⇅</span></div><input class="col-search" data-col="5" placeholder="Lọc..."></th>
                    <th style="width:160px"><div class="th-content" data-col="6"><span>Ngày gửi</span><span class="sort-icon">⇅</span></div><input class="col-search" data-col="6" placeholder="Ngày..."></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $a): ?>
                <tr class="app-row status-<?= $status_classes[$a['REG_STATUS']] ?? 'unknown' ?>" 
                    data-id="<?= $a['REG_ID'] ?>" style="cursor:pointer;">
                    <td><?= $a['REG_ID'] ?></td>
                    <td><?= htmlspecialchars($a['REG_NAME']) ?></td>
                    <td><?= htmlspecialchars($a['REG_STD_ID']) ?></td>
                    <td>
                        <span class="room-badge status-<?= $status_classes[$a['REG_STATUS']] ?? 'unknown' ?>">
                            <?= $a['REG_STATUS'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($a['CREATED_AT'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="table-info">Tổng: <?= count($applications) ?> đơn đăng ký</div>
<?php endif; ?>
<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$managers = [];
$appError = null;

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        $sql = "
            SELECT
                M.MNG_ID,
                M.MNG_NAME,
                DATE_FORMAT(M.MNG_DOB, '%Y-%m-%d') AS MNG_DOB,
                M.MNG_GD,
                M.MNG_PHONE,
                M.MNG_ADR,
                M.MNG_BLOCK,
                B.BLOCK_ID,
                U.EMAIL AS MNG_EMAIL 
            FROM MANAGER M
            LEFT JOIN BLOCK B ON M.MNG_BLOCK = B.BLOCK_ID
            LEFT JOIN USERS U ON M.USER_ID = U.USER_ID
            ORDER BY M.MNG_ID ASC
        ";

        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $managers[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $appError = $e->getMessage();
}

$blocks = [];
if ($conn) {
    $blockRes = mysqli_query($conn, "SELECT BLOCK_ID FROM BLOCK ORDER BY BLOCK_ID");
    if ($blockRes) {
        while ($row = mysqli_fetch_assoc($blockRes)) {
            $blocks[] = $row['BLOCK_ID'];
        }
    }
}
?>

<script>
    window.ALL_BLOCKS = <?= json_encode($blocks) ?>;
    window.ALL_MANAGERS = <?= json_encode($managers) ?>;
</script>

<div class="view-header">
  <h2>Danh sách quản lý</h2>

  <button id="btnAddManager" class="btn btn-action">
    + THÊM QUẢN LÝ MỚI
  </button>
</div>

<?php if ($appError): ?>
    <div class="alert alert-error">Lỗi: <?= htmlspecialchars($appError) ?></div>
<?php else: ?>
    <div class="student-table-wrapper">
        <table class="table student-table" id="managerTable">
            <thead>
                <tr>
                    <th style="width:80px">
                        <div class="th-content" data-col="0"><span>ID</span><span class="sort-icon">⇅</span></div>
                        <input class="col-search" data-col="0" placeholder="ID...">
                    </th>
                    <th>
                        <div class="th-content" data-col="1"><span>Họ tên</span><span class="sort-icon">⇅</span></div>
                        <input class="col-search" data-col="1" placeholder="Tên...">
                    </th>
                    <th style="width:100px">
                        <div class="th-content" data-col="2"><span>Giới tính</span><span class="sort-icon">⇅</span></div>
                        <input class="col-search" data-col="2" placeholder="GĐ...">
                    </th>
                    <th>
                        <div class="th-content" data-col="3"><span>Email</span><span class="sort-icon">⇅</span></div>
                        <input class="col-search" data-col="3" placeholder="Email...">
                    </th>
                    <th style="width:100px">
                        <div class="th-content" data-col="4"><span>Quản lý tòa</span><span class="sort-icon">⇅</span></div>
                        <input class="col-search" data-col="4" placeholder="Tòa...">
                    </th>
                    <th style="width:160px">
                        <div class="th-content no-sort">
                            <span>Hành động</span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($managers as $m): ?>
                <tr class="manager-row" data-id="<?= htmlspecialchars($m['MNG_ID']) ?>">
                    <td><?= htmlspecialchars($m['MNG_ID']) ?></td>
                    <td><?= htmlspecialchars($m['MNG_NAME']) ?></td>
                    <td><?= htmlspecialchars($m['MNG_GD']) ?></td>
                    <td><?= htmlspecialchars($m['MNG_EMAIL']) ?></td>
                    <td><span class="room-badge">Tòa <?= htmlspecialchars($m['MNG_BLOCK']) ?></span></td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <button class="btn btn-primary btn-sm btn-edit" data-id="<?= $m['MNG_ID'] ?>">Sửa</button>
                            <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $m['MNG_ID'] ?>">Xóa</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="table-info">Tổng: <?= count($managers) ?> nhân sự</div>
<?php endif; ?>
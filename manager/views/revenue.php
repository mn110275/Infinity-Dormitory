<?php
// manager/views/revenue.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$managerBlock = $_SESSION['block'];
$currentYear = date('Y');
$currentMonth = (int)date('m');

$rooms = [];
try {
  if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
    $roomQuery = "SELECT r.ROOM_ID FROM ROOM r WHERE r.BLOCK_ID = ? ORDER BY r.ROOM_ID";
    $stmt = mysqli_prepare($conn, $roomQuery);
    mysqli_stmt_bind_param($stmt, "s", $managerBlock);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
      $rooms[] = $row['ROOM_ID'];
    }
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}
?>

<h2>Quản lý thu phí</h2>

<div class="revenue-controls">
  <div class="control-box">
    <h3>Xem hóa đơn cũ</h3>
    <div class="form-group">
      <label>Chọn thời gian:</label>
      <div class="date-inputs">
        <select id="searchMonth" class="form-control">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m == $currentMonth ? 'selected' : '' ?>>Tháng <?= $m ?></option>
          <?php endfor; ?>
        </select>
        <select id="searchYear" class="form-control">
          <?php for ($y = $currentYear; $y >= 2023; $y--): ?>
            <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <button id="btnSearch" class="btn btn-primary">Tìm kiếm</button>
      </div>
    </div>
  </div>

  <div class="control-box">
    <h3>Tạo hóa đơn mới</h3>
    <div class="form-group">
      <label>Chọn thời gian:</label>
      <div class="date-inputs">
        <select id="newMonth" class="form-control">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m == $currentMonth ? 'selected' : '' ?>>Tháng <?= $m ?></option>
          <?php endfor; ?>
        </select>
        <select id="newYear" class="form-control">
          <?php for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
        <button id="btnCreateNew" class="btn btn-success">Tạo mới</button>
      </div>
    </div>
  </div>
</div>

<div id="revenueTableContainer" class="revenue-table-container">
  <div class="empty-state">
    <p>Chọn "Tìm kiếm" hoặc "Tạo mới" để bắt đầu</p>
  </div>
</div>

<script>
  window.REVENUE_DATA = {
    block: <?= json_encode($managerBlock) ?>,
    rooms: <?= json_encode($rooms) ?>,
    unitPrices: null // Sẽ được cập nhật khi Fetch API
  };
</script>
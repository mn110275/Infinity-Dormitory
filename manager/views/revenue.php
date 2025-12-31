<?php
// manager/views/revenue.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$managerBlock = $_SESSION['block'];
$currentYear = date('Y');
$currentMonth = date('m');

$rooms = [];
try {
  if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");

    $roomQuery = "SELECT r.ROOM_ID FROM ROOM r 
                      WHERE r.BLOCK_ID = ? 
                      ORDER BY r.ROOM_ID";

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

$unitPrices = null;
try {
  if ($conn) {
    $unitQuery = "SELECT ELEC, WATER FROM UNIT 
                      WHERE UYEAR = ? AND UMONTH = ?
                      LIMIT 1";

    $stmt = mysqli_prepare($conn, $unitQuery);
    mysqli_stmt_bind_param($stmt, "ii", $currentYear, $currentMonth);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
      $unitPrices = $row;
    }
  }
} catch (Exception $e) {
}
?>


<h2>Quản lý thu phí - Tòa <?= htmlspecialchars($managerBlock) ?></h2>

<div class="revenue-controls">
  <div class="control-box">
    <h3>Xem hóa đơn cũ</h3>
    <div class="form-group">
      <label>Chọn thời gian:</label>
      <div class="date-inputs">
        <select id="searchMonth" class="form-control">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m == $currentMonth ? 'selected' : '' ?>>
              Tháng <?= $m ?>
            </option>
          <?php endfor; ?>
        </select>
        <select id="searchYear" class="form-control">
          <?php for ($y = $currentYear; $y >= $currentYear - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>>
              <?= $y ?>
            </option>
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
            <option value="<?= $m ?>" <?= $m == $currentMonth ? 'selected' : '' ?>>
              Tháng <?= $m ?>
            </option>
          <?php endfor; ?>
        </select>
        <select id="newYear" class="form-control">
          <?php for ($y = $currentYear; $y <= $currentYear + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>>
              <?= $y ?>
            </option>
          <?php endfor; ?>
        </select>
        <button id="btnCreateNew" class="btn btn-success">Tạo mới</button>
      </div>
    </div>
  </div>
</div>

<?php if ($unitPrices): ?>
  <div class="unit-prices">
    <strong>Đơn giá tháng <?= $currentMonth ?>/<?= $currentYear ?>:</strong>
    <span>Điện: <?= number_format($unitPrices['ELEC']) ?> VNĐ/kWh</span>
    <span>Nước: <?= number_format($unitPrices['WATER']) ?> VNĐ/m³</span>
  </div>
<?php else: ?>
  <div class="alert alert-warning">
    Chưa có đơn giá cho tháng <?= $currentMonth ?>/<?= $currentYear ?>. Vui lòng liên hệ admin để cập nhật.
  </div>
<?php endif; ?>

<div id="revenueTableContainer" class="revenue-table-container">
  <div class="empty-state">
    <p>Chọn "Xem hóa đơn cũ" hoặc "Tạo hóa đơn mới" để bắt đầu</p>
  </div>
</div>


<script>
  window.REVENUE_DATA = {
    block: <?= json_encode($managerBlock) ?>,
    rooms: <?= json_encode($rooms) ?>,
    unitPrices: <?= json_encode($unitPrices) ?>,
    currentYear: <?= $currentYear ?>,
    currentMonth: <?= $currentMonth ?>
  };
</script>
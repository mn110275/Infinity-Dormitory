<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

$monthsByYear = [];

$res = mysqli_query($conn, "SELECT UYEAR, UMONTH FROM UNIT ORDER BY UYEAR DESC, UMONTH DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $y = (int)$row['UYEAR'];
    $m = (int)$row['UMONTH'];
    $monthsByYear[$y][] = $m;
}

foreach ($monthsByYear as $y => $months) {
    $monthsByYear[$y] = array_values(array_unique($months));
    sort($monthsByYear[$y]);
}

$year  = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;

$unit = null;
$exists = false;

$stmt = mysqli_prepare($conn,
  "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $year, $month);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
  $unit = $row;
  $exists = true;
}

$isFutureOrCurrent =
    ($year > $currentYear) ||
    ($year == $currentYear && $month >= $currentMonth);
?>

<div class="unit-container">

<h2>Quản lý đơn giá điện - nước</h2>

<div class="unit-header">
  <div class="control-box">
    <h3>Chọn tháng</h3>

    <form id="unitDateForm" method="get" class="form-group">
      <input type="hidden" name="tab" value="unit">

      <label>Thời gian:</label>

      <div class="date-inputs">
        <select name="month" class="form-control">
          <?php
            $months = $monthsByYear[$year] ?? [];
            foreach ($months as $m):
          ?>
            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
              Tháng <?= $m ?>
            </option>
          <?php endforeach; ?>
        </select> 

        <select name="year" class="form-control">
          <?php foreach (array_keys($monthsByYear) as $y): ?>
            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
              <?= $y ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button type="submit" id="btnViewUnit" class="btn btn-primary">
          Xem
        </button>
      </div> 
    </form>
  </div>
  
  <div class="control-box" <?= $exists ? 'style="display:none;"' : '' ?>>
    <?php if (!$exists): ?>
      <div class="alert alert-warning">
        Tháng <?= $month ?> năm <?= $year ?> chưa có đơn giá.
      </div>

      <button id="btnAddUnit" class="btn-action">
        TẠO ĐƠN GIÁ THÁNG <?= $month ?> / <?= $year ?>
      </button>
      
      <div class="unit-create-form" style="display:none;">
        <h4>Thêm đơn giá tháng <?= $month ?> / <?= $year ?></h4>

        <form id="formAddUnit" method="post" action="actions/unit_action.php">
          <input type="hidden" name="year" value="<?= $year ?>">
          <input type="hidden" name="month" value="<?= $month ?>">
          <input type="hidden" name="form_action" value="add">

          <div class="form-group">
            <label for="elecInput" class="form-label">Đơn giá điện (đ/kWh)</label>
            <input type="number" min="0" class="form-control" id="elecInput" name="elec" required>
          </div>

          <div class="form-group">
            <label for="waterInput" class="form-label">Đơn giá nước (đ/m³)</label>
            <input type="number" min="0" class="form-control" id="waterInput" name="water" required>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-pro btn-unit-edit">Lưu</button>
            <button type="button" class="btn btn-secondary btn-unit-edit" id="cancelAddUnit">Hủy</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($exists): ?>
  <div class="student-table-wrapper">
    <table class="table student-table">
      <thead>
        <tr>
          <th style="width:200px">
            <div class="th-content no-sort"><span>Tên</span></div>
          </th>
          <th style="width:400px">
            <div class="th-content no-sort"><span>Đơn giá</span></div>
          </th>
          <th>
            <div class="th-content no-sort"><span>Hành động</span></div>
          </th>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td>Điện (đ/kWh)</td>
          <td class="unit-value" data-unit="elec">
            <?= $unit ? number_format($unit['ELEC'], 0, ',', '.') : '—' ?>
          </td>
          <td class="action-cell">
            <?php if ($isFutureOrCurrent): ?>
              <button class="btn btn-primary btn-unit-edit" data-type="elec">
                Sửa
              </button>
            <?php else: ?>
              <button class="btn btn-secondary" disabled style="opacity:0.5;cursor:not-allowed">
                Không thể sửa
              </button>
            <?php endif; ?>
          </td>
        </tr>

        <tr>
          <td>Nước (đ/m³)</td>
          <td class="unit-value" data-unit="water">
            <?= $unit ? number_format($unit['WATER'], 0, ',', '.') : '—' ?>
          </td>
          <td class="action-cell">
            <?php if ($isFutureOrCurrent): ?>
              <button
                class="btn btn-primary btn-unit-edit" data-type="water">
                Sửa
              </button>
            <?php else: ?>
              <button class="btn btn-secondary" disabled style="opacity:0.5;cursor:not-allowed">
                Không thể sửa
              </button>
            <?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<script>
  window.monthsByYear = <?= json_encode($monthsByYear) ?>;
  window.currentMonth = <?= (int)$currentMonth ?>;
</script>
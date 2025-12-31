<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

// Kiểm tra xem bảng UNIT có dữ liệu không
$resCount = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM UNIT");
$rowCount = mysqli_fetch_assoc($resCount);
$isEmpty = ($rowCount['cnt'] == 0);

$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

/* ================== LOAD UNIT DATA ================== */
$realMonthsByYear = [];
$res = mysqli_query($conn, "SELECT UYEAR, UMONTH FROM UNIT");
while ($row = mysqli_fetch_assoc($res)) {
    $realMonthsByYear[(int)$row['UYEAR']][] = (int)$row['UMONTH'];
}

foreach ($realMonthsByYear as $y => $months) {
    $realMonthsByYear[$y] = array_values(array_unique($months));
    sort($realMonthsByYear[$y]);
}

/* ================== BUILD PROJECTED 3 MONTHS ================== */
$projectedMonthsByYear = [];

for ($i = 1; $i <= 3; $i++) {
    $ts = strtotime("+$i month", strtotime("$currentYear-$currentMonth-01"));
    $y  = (int)date('Y', $ts);
    $m  = (int)date('m', $ts);

    if (!isset($realMonthsByYear[$y]) || !in_array($m, $realMonthsByYear[$y])) {
        $projectedMonthsByYear[$y][] = $m;
    }
}

/* ================== MERGE YEARS ================== */
$availableYears = array_unique(array_merge(
    array_keys($realMonthsByYear),
    array_keys($projectedMonthsByYear),
    [$currentYear]
));
sort($availableYears);

/* ================== YEAR ================== */
$year = isset($_GET['year']) && in_array((int)$_GET['year'], $availableYears)
    ? (int)$_GET['year']
    : $currentYear;

/* ================== MONTH LIST ================== */
$realMonths      = $realMonthsByYear[$year] ?? [];
$projectedMonths = $projectedMonthsByYear[$year] ?? [];

$allMonths = array_merge($realMonths, $projectedMonths);
sort($allMonths);

/* ================== MONTH ================== */
if (isset($_GET['month']) && in_array((int)$_GET['month'], $allMonths)) {
    $month = (int)$_GET['month'];
} else {
    if ($year == $currentYear && in_array($currentMonth, $realMonths)) {
        $month = $currentMonth;
    } elseif (!empty($realMonths)) {
        $month = end($realMonths); // tháng quá khứ gần nhất
    } else {
        $month = null;
    }
}

/* ================== PROJECTED FLAG ================== */
$isProjected = $month !== null && !in_array($month, $realMonths);

/* ================== UNIT ================== */
$unit = null;
if ($month !== null && !$isProjected) {
    $stmt = mysqli_prepare($conn,
        "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $year, $month);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $unit = mysqli_fetch_assoc($res) ?: null;
}

/* ================== EDITABLE ================== */
$isFutureOrCurrent =
    ($year > $currentYear) ||
    ($year == $currentYear && $month >= $currentMonth);
?>

<div class="unit-container">

<h2>Quản lý đơn giá điện - nước</h2>

<?php if ($isEmpty): ?>
  <div class="alert alert-warning">
    Hệ thống chưa có dữ liệu đơn giá nào. Vui lòng thêm đơn giá cho tháng <?= $currentMonth ?> năm <?= $currentYear ?>.
  </div>

  <button id="btnAddFirstUnit" class="btn btn-primary">
    Thêm đơn giá tháng <?= $currentMonth ?> năm <?= $currentYear ?>
  </button>

  <div id="addFirstUnitForm" style="margin-top:20px; display:none; max-width:400px;">
    <h4>Thêm đơn giá tháng <?= $currentMonth ?> năm <?= $currentYear ?></h4>
    <form id="formAddUnit" method="post" action="actions/unit_action.php">
      <input type="hidden" name="year" value="<?= $currentYear ?>">
      <input type="hidden" name="month" value="<?= $currentMonth ?>">
      <input type="hidden" name="action" value="add">

      <div class="mb-3">
        <label for="elecInput" class="form-label">Đơn giá điện (đ/kWh)</label>
        <input type="number" min="0" class="form-control" id="elecInput" name="elec" required>
      </div>

      <div class="mb-3">
        <label for="waterInput" class="form-label">Đơn giá nước (đ/m³)</label>
        <input type="number" min="0" class="form-control" id="waterInput" name="water" required>
      </div>

      <button type="submit" class="btn btn-success">Lưu</button>
      <button type="button" id="btnCancelAdd" class="btn btn-secondary">Hủy</button>
    </form>
  </div>

  <script>
    const btnAddFirstUnit = document.getElementById('btnAddFirstUnit');
    const addForm = document.getElementById('addFirstUnitForm');
    const btnCancelAdd = document.getElementById('btnCancelAdd');

    btnAddFirstUnit.addEventListener('click', () => {
      addForm.style.display = 'block';
      btnAddFirstUnit.style.display = 'none';
    });

    btnCancelAdd.addEventListener('click', () => {
      addForm.style.display = 'none';
      btnAddFirstUnit.style.display = 'inline-block';
    });
  </script>

<?php else: ?>
<!-- CHỌN THÁNG -->
  <form method="get" class="date-inputs">
    <input type="hidden" name="tab" value="unit">

    <select name="month">
      <?php foreach ($realMonths as $m): ?>
        <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
          Tháng <?= $m ?>
        </option>
      <?php endforeach; ?>

      <?php foreach ($projectedMonths as $m): ?>
        <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
          Tháng <?= $m ?> (Dự tính)
        </option>
      <?php endforeach; ?>
    </select>

    <select name="year">
      <?php foreach ($availableYears as $y): ?>
        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
          <?= $y ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" id="btnViewUnit" class="btn btn-primary">
      Xem
    </button>
  </form>

  <hr>

  <div class="alert alert-info unit-note d-none">
    Đây là <strong>đơn giá dự tính</strong>. Nếu không chỉnh sửa, hệ thống sẽ tự ghi nhận khi đến thời điểm.
  </div>

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
        <!-- ĐIỆN -->
        <tr>
          <td>Điện (đ/kWh)</td>
          <td class="unit-value" data-unit="elec"><?= $unit ? number_format($unit['ELEC'], 0, ',', '.') : '—' ?></td>
          <td class="action-cell">
            <?php if ($isFutureOrCurrent): ?>
              <button class="btn btn-warning btn-unit-edit" data-type="elec">
                Sửa
              </button>
            <?php else: ?>
              <button class="btn btn-secondary" disabled style="opacity:0.5;cursor:not-allowed">
                Quá khứ
              </button>
            <?php endif; ?>
          </td>
        </tr>

        <!-- NƯỚC -->
        <tr>
          <td>Nước (đ/m³)</td>
          <td class="unit-value" data-unit="water"><?= $unit ? number_format($unit['WATER'], 0, ',', '.') : '—' ?></td>
          <td class="action-cell">
            <?php if ($isFutureOrCurrent): ?>
              <button
                class="btn btn-warning btn-unit-edit"
                data-type="water">
                Sửa
              </button>
            <?php else: ?>
              <span class="status-text">Quá khứ</span>
            <?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <script>
  const realMonthsByYear = <?= json_encode($realMonthsByYear) ?>;
  const projectedMonthsByYear = <?= json_encode($projectedMonthsByYear) ?>;
  </script>
<?php endif; ?>
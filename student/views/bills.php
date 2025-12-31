<?php
// student/views/bills.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$student = null;
$bill = null;
$unitPrices = null;
$error = null;
$availableMonths = [];
$currentMonth = date('n'); // 1-12
$currentYear = date('Y');

// Lấy tháng/năm từ GET parameter, mặc định là tháng hiện tại
$selectedMonth = $currentMonth;
$selectedYear = $currentYear;

if (isset($_GET['month'])) {
  $monthParam = $_GET['month'];
  // Kiểm tra nếu là format "month-year"
  if (strpos($monthParam, '-') !== false) {
    list($month, $year) = explode('-', $monthParam);
    $selectedMonth = (int) $month;
    $selectedYear = (int) $year;
  } else {
    $selectedMonth = (int) $monthParam;
    $selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : $currentYear;
  }
}

// Validate tháng/năm
if ($selectedMonth < 1 || $selectedMonth > 12) {
  $selectedMonth = $currentMonth;
}
if ($selectedYear < 2000 || $selectedYear > 2100) {
  $selectedYear = $currentYear;
}

try {
  if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");

    // Lấy thông tin phòng của sinh viên
    $studentQuery = "SELECT STD_ID, STD_NAME, BLOCK_ID, ROOM_ID 
    FROM STUDENT
    WHERE USER_ID = ? ";

    $stmt = mysqli_prepare($conn, $studentQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
      $student = $row;

      // Lấy danh sách các tháng/năm có dữ liệu hóa đơn
      if (!empty($student['BLOCK_ID']) && !empty($student['ROOM_ID'])) {
        $availableMonthsQuery = "SELECT DISTINCT REV_YEAR, REV_MONTH 
        FROM REVENUE
        WHERE BLOCK_ID = ? AND ROOM_ID = ?
          ORDER BY REV_YEAR DESC, REV_MONTH DESC ";

        $stmt4 = mysqli_prepare($conn, $availableMonthsQuery);
        mysqli_stmt_bind_param($stmt4, "ss", $student['BLOCK_ID'], $student['ROOM_ID']);
        mysqli_stmt_execute($stmt4);
        $result4 = mysqli_stmt_get_result($stmt4);

        while ($monthRow = mysqli_fetch_assoc($result4)) {
          $availableMonths[] = $monthRow;
        }

        // Nếu không có GET parameter và tháng hiện tại không có trong danh sách, chọn tháng đầu tiên
        if (!isset($_GET['month']) && !empty($availableMonths)) {
          $firstMonth = $availableMonths[0];
          $selectedMonth = $firstMonth['REV_MONTH'];
          $selectedYear = $firstMonth['REV_YEAR'];
        }

        // Lấy đơn giá điện, nước của tháng được chọn
        $unitQuery = "SELECT ELEC, WATER 
        FROM UNIT
        WHERE UYEAR = ? AND UMONTH = ? ";

        $stmt2 = mysqli_prepare($conn, $unitQuery);
        mysqli_stmt_bind_param($stmt2, "ii", $selectedYear, $selectedMonth);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);

        if ($unitRow = mysqli_fetch_assoc($result2)) {
          $unitPrices = $unitRow;
        }

        // Lấy hóa đơn tiền phòng của tháng được chọn
        $billQuery = "SELECT REV_ID, REV_YEAR, REV_MONTH, BLOCK_ID, ROOM_ID, 
        ELEC, WATER, OTHER, NOTE
        FROM REVENUE
        WHERE BLOCK_ID = ? AND ROOM_ID = ?
          AND REV_YEAR = ? AND REV_MONTH = ? ";

        $stmt3 = mysqli_prepare($conn, $billQuery);
        mysqli_stmt_bind_param($stmt3, "ssii",
          $student['BLOCK_ID'],
          $student['ROOM_ID'],
          $selectedYear,
          $selectedMonth
        );
        mysqli_stmt_execute($stmt3);
        $result3 = mysqli_stmt_get_result($stmt3);

        if ($billRow = mysqli_fetch_assoc($result3)) {
          $bill = $billRow;
        }
      }
    }
  }
} catch (Exception $e) {
  $error = $e -> getMessage();
}

// Tính toán tổng tiền
$totalAmount = 0;
$electricityCost = 0;
$waterCost = 0;

if ($bill && $unitPrices) {
  $electricityCost = $bill['ELEC'] * $unitPrices['ELEC'];
  $waterCost = $bill['WATER'] * $unitPrices['WATER'];
  $totalAmount = $electricityCost + $waterCost + ($bill['OTHER'] ?? 0);
}

// Hàm format số tiền
function formatCurrency($amount) {
  return number_format($amount, 0, ',', '.').' đ';
}

// Tên tháng tiếng Việt
$monthNames = [
  1 => 'Tháng Một', 2 => 'Tháng Hai', 3 => 'Tháng Ba', 4 => 'Tháng Tư',
  5 => 'Tháng Năm', 6 => 'Tháng Sáu', 7 => 'Tháng Bảy', 8 => 'Tháng Tám',
  9 => 'Tháng Chín', 10 => 'Tháng Mười', 11 => 'Tháng Mười Một', 12 => 'Tháng Mười Hai'
]; 
?>

<div class="bills-container">
  <div class="bills-header">
    <h2>Hóa đơn tiền phòng</h2>
    <?php if ($student && !empty($student['BLOCK_ID']) && !empty($student['ROOM_ID']) && !empty($availableMonths)): ?>
    <form method="GET" class="month-selector-form" id="monthForm">
      <input type="hidden" name="view" value="bills">
      <div class="month-selector">
        <label for="month-select">Chọn tháng/năm:</label>
        <select name="month" id="month-select" class="form-select" onchange="this.form.submit()">
          <?php foreach ($availableMonths as $monthData): 
            $year = $monthData['REV_YEAR'];
            $month = $monthData['REV_MONTH'];
            $isSelected = ($year == $selectedYear && $month == $selectedMonth);
            $displayText = $monthNames[$month] . ' ' . $year;
            if ($year == $currentYear && $month == $currentMonth) {
                $displayText .= ' (Hiện tại)';
            }
            $value = $month . '-' . $year;
            ?>
          <option value="<?= $value ?>" <?= $isSelected ? 'selected' : '' ?>>
            <?= $displayText ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
    <?php endif; ?>
  </div>
  <?php if ($error): ?>
  <div class="alert alert-error">Lỗi: <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!$student || empty($student['BLOCK_ID']) || empty($student['ROOM_ID'])): ?>
  <div class="no-data">
    <p>Bạn chưa được phân bổ phòng. Vui lòng liên hệ quản lý để được sắp xếp phòng.</p>
  </div>
  <?php elseif (empty($availableMonths)): ?>
  <div class="no-data">
    <p>Chưa có hóa đơn nào. Vui lòng liên hệ quản lý để biết thêm thông tin.</p>
  </div>
  <?php elseif (!$bill): ?>
  <div class="no-data">
    <p>Chưa có hóa đơn cho tháng <?= $selectedMonth ?>/<?= $selectedYear ?>. Vui lòng liên hệ quản lý để biết thêm thông tin.</p>
  </div>
  <?php else: ?>
  <div class="bill-card">
    <div class="bill-header">
      <div class="bill-title">
        <h3>Hóa đơn tháng <?= $selectedMonth ?>/<?= $selectedYear ?></h3>
        <p class="bill-subtitle">Phòng <?= htmlspecialchars($student['ROOM_ID']) ?> - Tòa <?= htmlspecialchars($student['BLOCK_ID']) ?></p>
      </div>
      <div class="bill-id">
        <span>Mã hóa đơn: #REV-<?= $bill['REV_ID'] ?></span>
      </div>
    </div>
    <div class="bill-content">
      <!-- Thông tin đơn giá -->
      <?php if ($unitPrices): ?>
      <div class="unit-prices-section">
        <h4>Đơn giá</h4>
        <div class="unit-prices-grid">
          <div class="unit-price-item">
            <span class="label">Điện:</span>
            <span class="value"><?= formatCurrency($unitPrices['ELEC']) ?>/số</span>
          </div>
          <div class="unit-price-item">
            <span class="label">Nước:</span>
            <span class="value"><?= formatCurrency($unitPrices['WATER']) ?>/khối</span>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <!-- Chi tiết hóa đơn -->
      <div class="bill-details-section">
        <h4>Chi tiết hóa đơn</h4>
        <div class="bill-details-table">
          <div class="bill-row">
            <div class="bill-cell label-cell">
              <strong>Tiền điện</strong>
              <span class="detail-info">
              <?= number_format($bill['ELEC'], 0, ',', '.') ?> số × 
              <?= formatCurrency($unitPrices['ELEC'] ?? 0) ?>
              </span>
            </div>
            <div class="bill-cell amount-cell">
              <?= formatCurrency($electricityCost) ?>
            </div>
          </div>
          <div class="bill-row">
            <div class="bill-cell label-cell">
              <strong>Tiền nước</strong>
              <span class="detail-info">
              <?= number_format($bill['WATER'], 0, ',', '.') ?> khối × 
              <?= formatCurrency($unitPrices['WATER'] ?? 0) ?>
              </span>
            </div>
            <div class="bill-cell amount-cell">
              <?= formatCurrency($waterCost) ?>
            </div>
          </div>
          <?php if (!empty($bill['OTHER']) && $bill['OTHER'] > 0): ?>
          <div class="bill-row">
            <div class="bill-cell label-cell">
              <strong>Chi phí khác</strong>
              <?php if (!empty($bill['NOTE'])): ?>
              <span class="detail-info"><?= htmlspecialchars($bill['NOTE']) ?></span>
              <?php endif; ?>
            </div>
            <div class="bill-cell amount-cell">
              <?= formatCurrency($bill['OTHER']) ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="bill-row total-row">
            <div class="bill-cell label-cell">
              <strong>Tổng cộng</strong>
            </div>
            <div class="bill-cell amount-cell total-amount">
              <?= formatCurrency($totalAmount) ?>
            </div>
          </div>
        </div>
      </div>
      <?php if (!empty($bill['NOTE']) && empty($bill['OTHER'])): ?>
      <div class="bill-note">
        <strong>Ghi chú:</strong> <?= htmlspecialchars($bill['NOTE']) ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="bill-footer">
      <div class="bill-info">
        <p><strong>Sinh viên:</strong> <?= htmlspecialchars($student['STD_NAME']) ?></p>
        <p><strong>MSSV:</strong> <?= htmlspecialchars($student['STD_ID']) ?></p>
      </div>
      <div class="bill-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">
        In hóa đơn
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
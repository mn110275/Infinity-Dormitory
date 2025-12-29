<?php
// student/views/bills.php
require_once '../database_connection.php';

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
        $selectedMonth = (int)$month;
        $selectedYear = (int)$year;
    } else {
        $selectedMonth = (int)$monthParam;
        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
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
                        WHERE USER_ID = ?";
        
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
                                        ORDER BY REV_YEAR DESC, REV_MONTH DESC";
                
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
                             WHERE UYEAR = ? AND UMONTH = ?";
                
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
                                    AND REV_YEAR = ? AND REV_MONTH = ?";
                
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
    $error = $e->getMessage();
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
    return number_format($amount, 0, ',', '.') . ' đ';
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

<style>
.bills-container {
    padding: 2rem;
}

.bills-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.bills-header h2 {
    margin: 0;
    color: #1e293b;
    font-size: 1.75rem;
}

.month-selector-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.month-selector {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.month-selector label {
    font-weight: 500;
    color: #475569;
    font-size: 0.875rem;
    white-space: nowrap;
}

.form-select {
    padding: 0.625rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 0.875rem;
    background: white;
    color: #1e293b;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 200px;
}

.form-select:hover {
    border-color: #3b82f6;
}

.form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.bill-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 900px;
    margin: 0 auto;
}

.bill-header {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
}

.bill-title h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 600;
}

.bill-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.bill-id {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.bill-content {
    padding: 2rem;
}

.unit-prices-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.unit-prices-section h4 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-size: 1.125rem;
}

.unit-prices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.unit-price-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 4px;
}

.unit-price-item .label {
    color: #64748b;
    font-weight: 500;
}

.unit-price-item .value {
    color: #1e293b;
    font-weight: 600;
}

.bill-details-section h4 {
    margin: 0 0 1.5rem 0;
    color: #1e293b;
    font-size: 1.125rem;
}

.bill-details-table {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.bill-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.bill-row:last-child {
    border-bottom: none;
}

.bill-row.total-row {
    border-top: 2px solid #3b82f6;
    border-bottom: 2px solid #3b82f6;
    margin-top: 0.5rem;
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
    background: #f8fafc;
}

.bill-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.label-cell {
    flex: 1;
}

.label-cell strong {
    color: #1e293b;
    font-size: 1rem;
}

.detail-info {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: normal;
}

.amount-cell {
    text-align: right;
    min-width: 150px;
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.total-amount {
    font-size: 1.5rem;
    color: #3b82f6;
}

.bill-note {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 4px;
    color: #92400e;
}

.bill-note strong {
    color: #78350f;
}

.bill-footer {
    background: #f8fafc;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    border-top: 1px solid #e2e8f0;
}

.bill-info p {
    margin: 0.25rem 0;
    color: #475569;
    font-size: 0.875rem;
}

.bill-info strong {
    color: #1e293b;
}

.bill-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
}

.no-data {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.no-data p {
    color: #64748b;
    font-size: 1rem;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

@media print {
    .bill-actions {
        display: none;
    }
    
    .bills-container {
        padding: 0;
    }
    
    .bill-card {
        box-shadow: none;
    }
}

@media (max-width: 768px) {
    .bill-header {
        flex-direction: column;
    }
    
    .bill-footer {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .bill-actions {
        width: 100%;
    }
    
    .btn {
        width: 100%;
    }
    
    .amount-cell {
        min-width: 120px;
        font-size: 0.875rem;
    }
    
    .total-amount {
        font-size: 1.25rem;
    }
}
</style>


<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

$year  = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;

// Kiểm tra tồn tại
$unit = null;
$exists = false;
$stmt = mysqli_prepare($conn, "SELECT ELEC, WATER FROM UNIT WHERE UYEAR = ? AND UMONTH = ?");
mysqli_stmt_bind_param($stmt, "ii", $year, $month);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) { $unit = $row; $exists = true; }

$isFutureOrCurrent = ($year > $currentYear) || ($year == $currentYear && $month >= $currentMonth);
?>

<link rel="stylesheet" href="css/unit.css">

    <h2>Quản lý đơn giá điện - nước</h2>
  <div class="wrapper">
    <div class="control-box">
        <form id="unitDateForm" method="get" class="form-group">
            <input type="hidden" name="tab" value="unit">
            <div class="date-inputs">
                <label>Chọn tháng:</label>
                <select name="month" class="form-control">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select> 
                <select name="year" class="form-control">
                    <?php for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary">Xem</button>
            </div> 
        </form>

        <?php if (!$exists): ?>
            <div id="no-data-alert" style="margin-top: 20px;">
                <div class="alert alert-warning">
                    Tháng <?= $month ?>/<?= $year ?> chưa có đơn giá. Bạn có muốn tạo không?
                </div>
                <button id="btnShowAddForm" class="btn btn-pro">TẠO MỚI</button>
            </div>

            <div id="unit-create-section" style="display:none; margin-top: 20px; border-top: 1px solid #eee; pt: 20px;">
                <h4>Thêm đơn giá mới</h4>
                <form id="formAddUnit">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <div class="date-inputs">
                        <input type="number" name="elec" class="form-control" placeholder="Giá điện" required>
                        <input type="number" name="water" class="form-control" placeholder="Giá nước" required>
                        <button type="submit" class="btn btn-pro">Lưu</button>
                        <button type="button" id="btnCancelAdd" class="btn btn-secondary">Hủy</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
  </div>
    <?php if ($exists): ?>
        <div class="wrapper">
            <table class="table student-table">
                <thead>
                    <tr>
                        <th style="text-align: center; font-size: 16px; width: 200px;">Tên loại</th>
                        <th style="text-align: center; font-size: 16px; width: 400px;">Đơn giá</th>
                        <th style="text-align: center; font-size: 16px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Điện (đ/kWh)</td>
                        <td class="unit-value" data-unit="elec"><?= number_format($unit['ELEC'], 0, ',', '.') ?></td>
                        <td class="action-cell">
                            <?php if ($isFutureOrCurrent): ?>
                                <button class="btn btn-primary btn-unit-edit" onclick="Unit.startEdit('elec', this)">Sửa</button>
                            <?php else: ?>
                                <span style="color:#999">🔒 Đã khóa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Nước (đ/m³)</td>
                        <td class="unit-value" data-unit="water"><?= number_format($unit['WATER'], 0, ',', '.') ?></td>
                        <td class="action-cell">
                            <?php if ($isFutureOrCurrent): ?>
                                <button class="btn btn-primary btn-unit-edit" onclick="Unit.startEdit('water', this)">Sửa</button>
                            <?php else: ?>
                                <span style="color:#999">🔒 Đã khóa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<script src="js/unit.js"></script>
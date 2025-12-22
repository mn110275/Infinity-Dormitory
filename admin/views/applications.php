<?php
require_once __DIR__ . '/../../database_connection.php';

$applications = [];
$appError = null;

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        $sql = "
            SELECT 
                REG_ID,
                REG_NAME,
                REG_STD_ID,
                REG_PHONE,
                REG_EMAIL,
                REG_STATUS,
                CREATED_AT
            FROM REGIFORM
            ORDER BY 
              CASE REG_STATUS
                  WHEN 'Chưa xử lý' THEN 1
                  WHEN 'Đã chấp nhận' THEN 2
                  WHEN 'Đã từ chối' THEN 3
                  ELSE 4
              END,
              CREATED_AT DESC
        ";

        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $applications[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $appError = $e->getMessage();
}
?>

<h2>Danh sách đơn đăng ký vào KTX</h2>

<?php if ($appError): ?>
  <div class="alert alert-error">Lỗi: <?= htmlspecialchars($appError) ?></div>

<?php elseif (empty($applications)): ?>
  <div class="alert alert-info">Chưa có đơn đăng ký nào.</div>

<?php else: ?>

<div class="student-table-wrapper">
  <table class="table student-table" id="applicationTable">
    <thead>
      <tr>
        <th style="width:80px">
          <div class="th-content" data-col="0">
            <span>ID</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="0" placeholder="ID...">
        </th>

        <th>
          <div class="th-content" data-col="1">
            <span>Họ tên</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="1" placeholder="Tên...">
        </th>

        <th style="width:120px">
          <div class="th-content" data-col="2">
            <span>MSSV</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="2" placeholder="MSSV...">
        </th>

        <th style="width:130px">
          <div class="th-content" data-col="3">
            <span>SĐT</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="3" placeholder="SĐT...">
        </th>

        <th>
          <div class="th-content" data-col="4">
            <span>Email</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="4" placeholder="Email...">
        </th>

        <th style="width:140px">
          <div class="th-content" data-col="5">
            <span>Trạng thái</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="5" placeholder="Trạng thái...">
        </th>

        <th style="width:160px">
          <div class="th-content" data-col="6">
            <span>Thời điểm tạo</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="6" placeholder="Ngày...">
        </th>
        <th style="width:160px">
          <div class="th-content no-sort">
            <span>Hành động</span>
          </div>
        </th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($applications as $a): ?>
      <tr class="
        <?= $a['REG_STATUS'] === 'Đã từ chối' ? 'row-rejected' : '' ?>
        <?= $a['REG_STATUS'] === 'Đã chấp nhận' ? 'row-approved' : '' ?>
      ">
        <td><?= $a['REG_ID'] ?></td>
        <td><?= htmlspecialchars($a['REG_NAME']) ?></td>
        <td><?= htmlspecialchars($a['REG_STD_ID']) ?></td>
        <td><?= htmlspecialchars($a['REG_PHONE']) ?></td>
        <td><?= htmlspecialchars($a['REG_EMAIL']) ?></td>
        <td>
          <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $a['REG_STATUS'])) ?>">
            <?= $a['REG_STATUS'] ?>
          </span>
        </td>
        <td><?= date('d/m/Y H:i', strtotime($a['CREATED_AT'])) ?></td>
        
        <td class="action-cell">
          <?php if ($a['REG_STATUS'] === 'Chưa xử lý'): ?>
            <button 
              class="btn btn-success btn-accept"
              data-id="<?= $a['REG_ID'] ?>">
              Chấp nhận
            </button>
            <button
              class="btn btn-danger btn-reject"
              data-id="<?= $a['REG_ID'] ?>">
              Từ chối
            </button>

          <?php elseif ($a['REG_ID'] === 'Đã từ chối'): ?>
            <button class="btn btn-warning btn-undo"
                    data-id="<?= $a['REG_ID'] ?>">
              Hoàn tác
            </button>

          <?php elseif ($a['REG_STATUS'] === 'Đã chấp nhận'): ?>
            <span class="status-text accepted">Đã chấp nhận</span>

          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="table-info">
  Tổng: <?= count($applications) ?> đơn đăng ký
</div>

<?php endif; ?>


<!-- Popup modal cho chấp nhận đơn -->

<?php
// Lấy danh sách phòng có sẵn để hiển thị dropdown phòng trong popup
$rooms = [];
if ($conn) {
    $roomResult = mysqli_query($conn, "SELECT ROOM_ID, GENDER, CAPACITY, OCCUPIED FROM ROOM");
    if ($roomResult) {
        while ($row = mysqli_fetch_assoc($roomResult)) {
            $rooms[] = $row;
        }
    }
}
?>

<div id="acceptModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close-button" id="closeAcceptModal" title="Đóng">&times;</span>
    <h2>Chấp nhận đơn đăng ký</h2>
    <form id="acceptForm" autocomplete="off">
      <input type="hidden" id="application_id" name="application_id">

      <div class="form-group">
        <label for="mssv">MSSV:</label>
        <input type="text" id="mssv" name="mssv" required placeholder="Nhập MSSV">
      </div>

      <div class="form-group">
        <label for="ho_ten">Họ tên sinh viên:</label>
        <input type="text" id="ho_ten" name="ho_ten" required placeholder="Nhập họ tên">
      </div>

      <div class="form-group">
        <label for="ngay_sinh">Ngày sinh:</label>
        <input type="date" id="ngay_sinh" name="ngay_sinh" required>
      </div>

      <div class="form-group">
        <label for="contact_sv">Số điện thoại:</label>
        <input type="tel" id="contact_sv" name="contact_sv" required placeholder="Nhập số điện thoại">
      </div>

      <div class="form-group">
        <label for="address_sv">Địa chỉ:</label>
        <input type="text" id="address_sv" name="address_sv" required placeholder="Nhập địa chỉ">
      </div>

      <div class="form-group">
        <label for="gioi_tinh">Giới tính:</label>
        <select id="gioi_tinh" name="gioi_tinh" required>
          <option value="" disabled selected>Chọn giới tính</option>
          <option value="Nam">Nam</option>
          <option value="Nữ">Nữ</option>
        </select>
      </div>

      <div class="form-group">
        <label for="id_room">Phòng:</label>
        <select id="id_room" name="id_room" required>
          <option value="" disabled selected>-- Chọn phòng --</option>
          <?php foreach ($rooms as $room): ?>
            <option value="<?= $room['ROOM_ID'] ?>" data-gender="<?= $room['GENDER'] ?>">
              <?= htmlspecialchars($room['ROOM_ID']) ?> (<?= htmlspecialchars($room['GENDER']) ?>) - <?= $room['OCCUPIED'] ?>/<?= $room['CAPACITY'] ?> chỗ
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group btn-group">
        <button type="submit" class="btn-submit">Xác nhận</button>
        <button type="button" id="cancelAccept" class="btn-cancel">Hủy</button>
      </div>
    </form>
  </div>
</div>


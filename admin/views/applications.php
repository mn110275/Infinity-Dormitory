<?php
require_once __DIR__ . '/../../database_connection.php';

$applications = [];
$appError = null;

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        $sql = "
            SELECT 
                ID_DON,
                HO_TEN_NDK,
                MSSV_NDK,
                SDT_NDK,
                EMAIL_NDK,
                STATUS_DON,
                THOI_DIEM_TAO
            FROM DONDANGKY
            ORDER BY THOI_DIEM_TAO DESC
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
        <?= $a['STATUS_DON'] === 'Đã từ chối' ? 'row-rejected' : '' ?>
        <?= $a['STATUS_DON'] === 'Đã chấp nhận' ? 'row-approved' : '' ?>
      ">
        <td><?= $a['ID_DON'] ?></td>
        <td><?= htmlspecialchars($a['HO_TEN_NDK']) ?></td>
        <td><?= htmlspecialchars($a['MSSV_NDK']) ?></td>
        <td><?= htmlspecialchars($a['SDT_NDK']) ?></td>
        <td><?= htmlspecialchars($a['EMAIL_NDK']) ?></td>
        <td>
          <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $a['STATUS_DON'])) ?>">
            <?= $a['STATUS_DON'] ?>
          </span>
        </td>
        <td><?= date('d/m/Y H:i', strtotime($a['THOI_DIEM_TAO'])) ?></td>
        
        <td class="action-cell">
          <?php if ($a['STATUS_DON'] === 'Chưa xử lý'): ?>
            <button 
              class="btn btn-success btn-accept"
              data-id="<?= $a['ID_DON'] ?>">
              Chấp nhận
            </button>
            <button
              class="btn btn-danger btn-reject"
              data-id="<?= $a['ID_DON'] ?>">
              Từ chối
            </button>

          <?php elseif ($a['STATUS_DON'] === 'Đã từ chối'): ?>
            <button class="btn btn-warning btn-undo"
                    data-id="<?= $a['ID_DON'] ?>">
              Hoàn tác
            </button>

          <?php elseif ($a['STATUS_DON'] === 'Đã chấp nhận'): ?>
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
    $roomResult = mysqli_query($conn, "SELECT ID_ROOM, ROOM_NAME, GIOI_TINH, CAPACITY, OCCUPIED_SLOT FROM PHONG");
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
            <option value="<?= $room['ID_ROOM'] ?>" data-gender="<?= $room['GIOI_TINH'] ?>">
              <?= htmlspecialchars($room['ROOM_NAME']) ?> (<?= htmlspecialchars($room['GIOI_TINH']) ?>) - <?= $room['OCCUPIED_SLOT'] ?>/<?= $room['CAPACITY'] ?> chỗ
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


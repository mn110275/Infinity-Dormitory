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
?>

<h2>Danh sách quản lý</h2>
<button id="btnAddManager" class="btn btn-primary" style="margin-bottom:15px;">Thêm quản lý mới</button>

<?php if ($appError): ?>
  <div class="alert alert-error">Lỗi: <?= htmlspecialchars($appError) ?></div>


<?php elseif (empty($managers)): ?>
<div class="alert alert-info">Chưa có quản lý nào.</div>

<?php else: ?>
<div class="student-table-wrapper">
  <table class="table student-table" id="managerTable">
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
            <span>Giới tính</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="2" placeholder="Giới tính...">
        </th>

        <th>
          <div class="th-content" data-col="3">
            <span>Email</span><span class="sort-icon">⇅</span>
          </div>
          <input class="col-search" data-col="3" placeholder="Email...">
        </th>

        <th style="width:130px">
          <div class="th-content" data-col="4">
            <span>Tòa</span><span class="sort-icon">⇅</span>
          </div>
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
      <tr data-id="<?= htmlspecialchars($m['MNG_ID']) ?>">
        <td><?= htmlspecialchars($m['MNG_ID']) ?></td>
        <td><?= htmlspecialchars($m['MNG_NAME']) ?></td>
        <td><?= htmlspecialchars($m['MNG_GD']) ?></td>
        <td><?= htmlspecialchars($m['MNG_EMAIL']) ?></td>
        <td><?= htmlspecialchars($m['MNG_BLOCK']) ?></td>
        <td>
          <button class="btn btn-sm btn-edit" data-id="<?= htmlspecialchars($m['MNG_ID']) ?>">Sửa</button>
          <button class="btn btn-sm btn-danger btn-delete" data-id="<?= htmlspecialchars($m['MNG_ID']) ?>">Xóa</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="table-info">
  Tổng: <?= count($managers) ?> quản lý
</div>

<?php endif; ?>


<!-- Modal thêm/sửa quản lý -->
<?php
// Lấy danh sách block để dropdown chọn block khi thêm/sửa
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

<div id="managerModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close-button" id="closeManagerModal" title="Đóng">&times;</span>
    <h2 id="modalTitle">Thêm quản lý mới</h2>
    <form id="managerForm" autocomplete="off">
      <input type="hidden" name="action" id="form_action" value="add">
      <input type="hidden" id="manager_id" name="id">

      <div class="form-group">
        <label for="mng_name">Họ tên:</label>
        <input type="text" id="mng_name" name="mng_name" required placeholder="Nhập họ tên">
      </div>

      <div class="form-group">
        <label for="mng_email">Email:</label>
        <input
          type="email"
          id="mng_email"
          name="mng_email"
          required
          placeholder="Nhập email đăng nhập"
        >
      </div>

      <div class="form-group">
        <label for="mng_dob">Ngày sinh:</label>
        <input type="date" id="mng_dob" name="mng_dob" required>
      </div>

      <div class="form-group">
        <label for="mng_gd">Giới tính:</label>
        <select id="mng_gd" name="mng_gd" required>
          <option value="" disabled selected>Chọn giới tính</option>
          <option value="Nam">Nam</option>
          <option value="Nữ">Nữ</option>
        </select>
      </div>

      <div class="form-group">
        <label for="mng_phone">Số điện thoại:</label>
        <input type="tel" id="mng_phone" name="mng_phone" placeholder="Nhập số điện thoại">
      </div>

      <div class="form-group">
        <label for="mng_adr">Địa chỉ:</label>
        <input type="text" id="mng_adr" name="mng_adr" placeholder="Nhập địa chỉ">
      </div>

      <div class="form-group">
        <label for="mng_block">Tòa:</label>
        <select id="mng_block" name="mng_block" required>
          <option value="" disabled selected>Chọn tòa</option>
          <?php foreach ($blocks as $blockId): ?>
            <option value="<?= htmlspecialchars($blockId) ?>"><?= htmlspecialchars($blockId) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group btn-group">
        <button type="submit" class="btn-submit">Lưu</button>
        <button type="button" id="cancelManager" class="btn-cancel">Hủy</button>
      </div>
    </form>
  </div>
</div>
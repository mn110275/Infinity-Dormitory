<?php
// manager/views/students.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$students = [];
$managerBlock = $_SESSION['block'];

try
{
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");
        
        $studentQuery = "SELECT * FROM STUDENT S JOIN CONTRACT C ON S.STD_ID = C.STD_ID WHERE C.STATUS = 'Active' AND BLOCK_ID = ?";
        
        $stmt = mysqli_prepare($conn, $studentQuery);
        mysqli_stmt_bind_param($stmt, "s", $managerBlock);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $students[] = $row;
            }
        }
    }
} 
catch (Exception $e) {
  $studentError = $e->getMessage();
}
?>

<script>
  window.ALL_STUDENTS = <?= json_encode($students) ?>;
</script>

<?php if (isset($studentError)): ?>
  <div class="alert alert-error">Lỗi: <?= htmlspecialchars($studentError) ?></div>
<?php elseif (empty($students)): ?>
  <div class="alert alert-info">Chưa có sinh viên nào trong hệ thống.</div>
<?php else: ?>
  <h2>Danh sách phòng & sinh viên</h2>
  <div class="student-table-wrapper">
    <table class="table student-table" id="studentTable">
      <thead>
        <tr>
          <th style="width: 100px">
            <div class="th-content" data-col="0">
              <span>Phòng</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="0" placeholder="Tìm phòng...">
          </th>
          <th style="width: 300px">
            <div class="th-content" data-col="1">
              <span>Họ tên sinh viên</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="1" placeholder="Tìm tên...">
          </th>
          <th style="width: 120px">
            <div class="th-content" data-col="2">
              <span>MSSV</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="2" placeholder="Tìm MSSV...">
          </th>
          <th style="width: 150px">
            <div class="th-content" data-col="3">
              <span>Ngày sinh</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="3" placeholder="Tìm ngày...">
          </th>
          <th style="width: 150px">
            <div class="th-content" data-col="4">
              <span>Điện thoại</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="4" placeholder="Tìm SĐT...">
          </th>
          <th>
            <div class="th-content" data-col="5">
              <span>Địa chỉ</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="5" placeholder="Tìm địa chỉ...">
          </th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr class="student-row" 
            data-id="<?= htmlspecialchars($s['STD_ID']) ?>" 
            data-room="<?= htmlspecialchars($s['ROOM_ID']) ?>" 
            style="cursor: pointer;">
          <td><span class="room-badge"><?= htmlspecialchars($s['ROOM_ID']) ?></span></td>
          <td><?= htmlspecialchars($s['STD_NAME']) ?></td>
          <td><?= htmlspecialchars($s['STD_ID']) ?></td>
          <td><?= date('d/m/Y', strtotime($s['STD_DOB'])) ?></td>
          <td><?= htmlspecialchars($s['STD_PHONE']) ?></td>
          <td><?= htmlspecialchars($s['STD_ADR']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-info">Tổng: <?= count($students) ?> sinh viên</div>
<?php endif; ?>
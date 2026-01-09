<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$students = [];

try
{
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        $nextSemRes = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_STATUS = 'Upcoming' LIMIT 1");
        $nextSemRow = mysqli_fetch_assoc($nextSemRes);
        $nextSemId = $nextSemRow ? $nextSemRow['SEM_ID'] : 'NONE';

        $canRenew = ($nextSemId !== 'NONE');
        
        $studentQuery = "SELECT DISTINCT
                              s.*, 
                              c.ROOM_ID, 
                              c.BLOCK_ID, 
                              sem.STARTDATE,
                              (SELECT COUNT(*) FROM CONTRACT c2 
                              WHERE c2.STD_ID = s.STD_ID 
                              AND c2.SEM_ID = '$nextSemId' 
                              AND c2.STATUS = 'Upcoming') as is_renewed
                          FROM STUDENT s
                          INNER JOIN CONTRACT c ON s.STD_ID = c.STD_ID
                          INNER JOIN SEMESTER sem ON c.SEM_ID = sem.SEM_ID
                          WHERE sem.SEM_STATUS = 'Active' 
                            AND c.STATUS = 'Active'
                            AND s.IS_ACTIVE = 1
                          ORDER BY c.BLOCK_ID, c.ROOM_ID, s.STD_NAME";
        
        $stmt = mysqli_prepare($conn, $studentQuery);
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

$blocks = [];

$blockQuery = "
  SELECT 
      b.BLOCK_ID,
      SUM(r.CAPACITY - r.OCCUPIED) AS available
  FROM BLOCK b
  LEFT JOIN ROOM r ON r.BLOCK_ID = b.BLOCK_ID
  GROUP BY b.BLOCK_ID
";

$result = mysqli_query($conn, $blockQuery);
while ($row = mysqli_fetch_assoc($result)) {
    $blocks[] = $row;
}

$rooms = [];

$roomQuery = "
  SELECT 
      ROOM_ID,
      BLOCK_ID,
      GENDER,
      CAPACITY,
      OCCUPIED,
      (CAPACITY - OCCUPIED) AS available
  FROM ROOM;
";

$result = mysqli_query($conn, $roomQuery);
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}
?>

<script>
  window.ALL_STUDENTS = <?= json_encode($students) ?>;
  window.ALL_BLOCKS = <?= json_encode($blocks, JSON_UNESCAPED_UNICODE) ?>;
  window.ALL_ROOMS  = <?= json_encode($rooms, JSON_UNESCAPED_UNICODE) ?>;
  window.CAN_RENEW = <?= json_encode($canRenew) ?>;
</script>

<h2>Danh sách sinh viên đang ở ký túc xá</h2>
<?php if (isset($studentError)): ?>
  <div class="alert alert-error">Lỗi: <?= htmlspecialchars($studentError) ?></div>
<?php elseif (empty($students)): ?>
  <div class="alert alert-info">Chưa có sinh viên nào trong hệ thống.</div>
<?php else: ?>
  <div class="table-controls" style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
      <button type="button" class="btn btn-primary" id="btnToggleEditRenewal" onclick="StudentAdmin.toggleRenewalMode()"
        <?= !$canRenew ? 'style="opacity: 0.6; cursor: not-allowed;" title="Vui lòng tạo học kỳ mới trước khi gia hạn"' : '' ?>
      >
          Mở chế độ gia hạn
      </button>

      <?php if (!$canRenew): ?>
          <span style="color: #ef4444; font-size: 14px; font-style: italic;">
              * Cần lập lịch học kỳ tới để mở tính năng gia hạn.
          </span>
      <?php endif; ?>
      
      <div id="renewalActions" style="display: none; gap: 10px;">
        <button type="button" class="btn btn-success" onclick="StudentAdmin.saveRenewalChanges()">
            Lưu thay đổi
        </button>
      </div>
  </div>

  <div class="student-table-wrapper">
    <table class="table student-table" id="studentTable">
      <thead>
        <tr>
          <th class="col-renewal" style="display: none; text-align: center;">
              <div class="th-content no-sort" style="justify-content: center; flex-direction: column; gap: 5px;">
                  <span>Gia hạn</span>
                  <input type="checkbox" id="selectAllRenewal" onclick="StudentAdmin.toggleSelectAll(this)">
              </div>
          </th>
          <th style="width: 130px">
            <div class="th-content" data-col="0">
              <span>Phòng</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="0" placeholder="Tìm phòng...">
          </th>
          <th>
            <div class="th-content" data-col="1">
              <span>Họ tên sinh viên</span>
              <span class="sort-icon">⇅</span>
            </div>
            <input type="text" class="col-search" data-col="1" placeholder="Tìm tên...">
          </th>
          <th style="width: 130px">
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
          <th style="width: 300px">
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
          <td class="col-renewal" style="display: none;">
              <input type="checkbox" class="renewal-cb" 
                    value="<?= $s['STD_ID'] ?>" 
                    <?= ($s['is_renewed'] > 0) ? 'checked' : '' ?>
                    onchange="StudentAdmin.updateCounter()">
          </td>
          <td>
              <span class="room-badge">
                  <?= $s['ROOM_ID'] ? htmlspecialchars($s['BLOCK_ID'] . $s['ROOM_ID']) : 'Chưa xếp' ?>
              </span>
          </td>
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
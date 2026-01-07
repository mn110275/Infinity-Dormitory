<?php
  require_once '../database_connection.php';
  require_once __DIR__ . '/../../auth/require_role.php';
  requireRole('manager');
  
  $raw_facilities = [];
  $roomStudents = [];
  $rooms = [];
  $itemTypes = [];
  $managerBlock = $_SESSION['block'];
  
  if ($conn)
    {
      mysqli_set_charset($conn, "utf8mb4");

      $roomQuery = "SELECT ROOM_ID, CAPACITY, OCCUPIED FROM ROOM WHERE BLOCK_ID = ? ORDER BY ROOM_ID";
      $stmt = $conn->prepare($roomQuery);
      $stmt->bind_param("s", $managerBlock);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc())
      {
        $rooms[$row['ROOM_ID']] = $row;
      }

      $fcltQuery = "SELECT * FROM FACILITY WHERE BLOCK_ID = ?";
      $stmtFclt = $conn->prepare($fcltQuery);
      $stmtFclt->bind_param("s", $managerBlock);
      $stmtFclt->execute();
      $resFclt = $stmtFclt->get_result();
      while ($f = $resFclt->fetch_assoc())
      {
        $raw_facilities[] = $f;
        if (!in_array($f['FCLT_TYPE'], $itemTypes))
            $itemTypes[] = $f['FCLT_TYPE'];
      }
      sort($itemTypes);

      $stdQuery = "SELECT * FROM STUDENT S JOIN CONTRACT C ON S.STD_ID = C.STD_ID WHERE C.STATUS = 'Active' AND BLOCK_ID = ?";
      $stmtStd = $conn->prepare($stdQuery);
      $stmtStd->bind_param("s", $managerBlock);
      $stmtStd->execute();
      $resStd = $stmtStd->get_result();
      while ($s = $resStd->fetch_assoc())
      {
        $roomStudents[$s['ROOM_ID']][] = [
          'name' => $s['STD_NAME'],
          'id' => $s['STD_ID'],
          'img' => $s['STD_IMG'],
          'gd' => $s['STD_GD'],
          'dob' => date('d/m/Y', strtotime($s['STD_DOB'])),
          'phone' => $s['STD_PHONE'],
          'adr' => $s['STD_ADR']
        ];
      }
  }
  
  function countItems($raw_data, $roomId, $type)
  {
      $count = 0;
      foreach ($raw_data as $item) {
          if ($item['ROOM_ID'] == $roomId && $item['FCLT_TYPE'] == $type)
              $count++;
      }
      return $count;
  }
?>

<script>
  window.APP_DATA = {
    managerBlock: <?php echo json_encode($managerBlock); ?>,
    raw_facilities: <?= json_encode($raw_facilities) ?>,
    students: <?= json_encode($roomStudents) ?>
  };
</script>

<div class="view-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <h2 style="margin: 0; color: #1e293b; font-family: 'Google Sans';">Quản lý Thiết bị - Tòa <?= $managerBlock ?></h2>
  <button onclick="FacilityManager.addNewType()"
    style="padding: 8px 24px; background: #64a9f2; color: white; font-family: 'Google Sans'; font-size: 16px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 12px rgba(100, 169, 242, 0.4);">
  THÊM LOẠI ĐỒ DÙNG MỚI
  </button>
</div>
<div class="matrix-container">
  <div class="matrix-table">
    <table>
      <thead>
        <tr>
          <th class="sticky-col" style="font-size: 20px; background: #f8fafc;">Vật dụng \ Phòng</th>
          <?php foreach ($rooms as $id => $room): ?>
          <th class="room-header" data-room-id="<?= $id ?>">
            <div class="room-no"><?= $id ?></div>
            <div class="room-stats">
              <i class="fa fa-users"></i> <?= $room['OCCUPIED'] ?>/<?= $room['CAPACITY'] ?>
            </div>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($itemTypes as $type): ?>
        <tr>
          <th class="sticky-col" style="font-weight: 600;"><?= htmlspecialchars($type) ?></th>
          <?php foreach ($rooms as $id => $room):
            $qty = countItems($raw_facilities, $id, $type);
            ?>
          <td>
            <span class="qty-cell <?= $qty > 0 ? 'has-items' : '' ?>" data-room-id="<?= $id ?>"
              data-item="<?= htmlspecialchars($type) ?>">
            <?= $qty ?>
            </span>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<aside id="viewer" class="right-sidebar">
  <div class="viewer-header">
    <h3 id="viewerTitle">Chi tiết</h3>
    <span class="close-btn" id="viewerClose">&times;</span>
  </div>
  <div id="viewerContent" style="display: flex; flex-direction: column; height: calc(100% - 60px);">
    <div id="viewerMain" class="viewer-hero">
      <img id="viewerMainImg" src="" alt="Facility Image" />
    </div>
    <div id="viewerThumbs" class="thumb-grid"></div>
    <div id="viewerLinks" class="action-links"
      style="margin-top: auto; padding: 15px; border-top: 1px solid #eee; display: flex; flex-direction: column; gap: 10px;">
    </div>
  </div>
</aside>
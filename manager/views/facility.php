<?php
// manager/views/inventory.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$rooms = [];
$items = [];
$managerBlock = $_SESSION['block'];

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");
        
        $roomQuery = "SELECT r.ROOM_ID, r.BLOCK_ID
                      FROM ROOM r
                      WHERE r.BLOCK_ID = ?
                      ORDER BY r.ROOM_ID";
        
        $stmt = mysqli_prepare($conn, $roomQuery);
        mysqli_stmt_bind_param($stmt, "s", $managerBlock);
        mysqli_stmt_execute($stmt);
        $roomResult = mysqli_stmt_get_result($stmt);
        
        if ($roomResult) {
            while ($row = mysqli_fetch_assoc($roomResult)) {
                $rooms[$row['ROOM_ID']] = [
                    'id' => $row['ROOM_ID'],
                    'number' => $row['ROOM_ID'],
                    'building' => $row['BLOCK_ID'],
                    'items' => [],
                    'images' => []
                ];
            }
        }
        
        if (!empty($rooms)) {
            $roomIds = array_keys($rooms);
            $placeholders = str_repeat('?,', count($roomIds) - 1) . '?';
            
            $equipQuery = "SELECT f.ROOM_ID, f.FCLT_TYPE, f.FCLT_STATUS,
                           COUNT(*) as quantity,
                           GROUP_CONCAT(DISTINCT f.FCLT_IMG SEPARATOR '|') as images
                           FROM FACILITY f
                           WHERE f.ROOM_ID IN ($placeholders)
                           GROUP BY f.ROOM_ID, f.FCLT_TYPE, f.FCLT_STATUS";
            
            $stmt = mysqli_prepare($conn, $equipQuery);
            $types = str_repeat('s', count($roomIds));
            mysqli_stmt_bind_param($stmt, $types, ...$roomIds);
            mysqli_stmt_execute($stmt);
            $equipResult = mysqli_stmt_get_result($stmt);
            
            if ($equipResult) {
                while ($eq = mysqli_fetch_assoc($equipResult)) {
                    $roomId = $eq['ROOM_ID'];
                    $itemName = $eq['FCLT_TYPE'];
                    
                    if (isset($rooms[$roomId])) {
                        if (!isset($rooms[$roomId]['items'][$itemName])) {
                            $rooms[$roomId]['items'][$itemName] = 0;
                        }
                        $rooms[$roomId]['items'][$itemName] += (int)$eq['quantity'];
                        
                        if (!empty($eq['images'])) {
                            $imgs = array_filter(explode('|', $eq['images']));
                            if (count($imgs) > 0) {
                                if (!isset($rooms[$roomId]['images'][$itemName])) {
                                    $rooms[$roomId]['images'][$itemName] = [];
                                }
                                $rooms[$roomId]['images'][$itemName] = array_merge(
                                    $rooms[$roomId]['images'][$itemName],
                                    $imgs
                                );
                            }
                        }
                        
                        if (!in_array($itemName, $items)) {
                            $items[] = $itemName;
                        }
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $inventoryError = $e->getMessage();
}

sort($items);
?>

<script>
  window.INVENTORY_DATA = <?= json_encode(array_values($rooms)) ?>;
</script>

<?php if (isset($inventoryError)): ?>
  <div class="alert alert-error">Lỗi: <?= htmlspecialchars($inventoryError) ?></div>
<?php elseif (empty($items)): ?>
  <div class="alert alert-info">Chưa có dữ liệu thiết bị nào.</div>
<?php else: ?>
  <div class="matrix-table">
    <table>
      <thead>
        <tr>
          <th style="width: 200px">Vật dụng \ Phòng</th>
          <?php foreach ($rooms as $room): ?>
            <th>
              <div><?= htmlspecialchars($room['number']) ?></div>
              <small><?= htmlspecialchars($room['building']) ?></small>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <th><?= htmlspecialchars($item) ?></th>
          <?php foreach ($rooms as $room): ?>
            <?php 
            $count = $room['items'][$item] ?? 0;
            $hasImages = isset($room['images'][$item]);
            ?>
            <td>
              <span class="qty-cell <?= $hasImages ? 'has-images' : '' ?>" 
                    data-room-id="<?= $room['id'] ?>" 
                    data-item="<?= htmlspecialchars($item) ?>">
                <?= $count ?>
              </span>
            </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php
// manager/dashboard.php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['block'])) {
    die('Lỗi: Không xác định được tòa quản lý. Vui lòng đăng nhập lại.');
}

require_once '../database_connection.php';

// Lấy dữ liệu sinh viên (chỉ tòa của manager)
$students = [];
$managerBlock = $_SESSION['block']; // Lấy tòa từ session

try {
    if ($conn && $managerBlock) {
        mysqli_set_charset($conn, "utf8mb4");
        
        $studentQuery = "SELECT s.STD_ID, s.STD_NAME, s.STD_GD, s.STD_PHONE, 
                         s.STD_ADR, s.STARTDATE, s.STD_IMG, r.ROOM_ID, r.BLOCK_ID
                         FROM STUDENT s
                         INNER JOIN ROOM r ON r.ROOM_ID = s.ROOM_ID
                         WHERE r.BLOCK_ID = ?
                         ORDER BY r.ROOM_ID, s.STD_NAME";
        
        $stmt = mysqli_prepare($conn, $studentQuery);
        mysqli_stmt_bind_param($stmt, "s", $managerBlock);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $students[] = $row;
            }
        }
    } elseif (!$managerBlock) {
        $studentError = "Không xác định được tòa quản lý. Vui lòng đăng nhập lại.";
    }
} catch (Exception $e) {
    $studentError = $e->getMessage();
}

// Lấy dữ liệu inventory (chỉ tòa của manager)
$rooms = [];
$items = [];
try {
    if ($conn && $managerBlock) {
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
        
        // Lấy thiết bị (FACILITY) - chỉ phòng của tòa này
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
                        // Tổng hợp số lượng theo loại (bất kể status)
                        if (!isset($rooms[$roomId]['items'][$itemName])) {
                            $rooms[$roomId]['items'][$itemName] = 0;
                        }
                        $rooms[$roomId]['items'][$itemName] += (int)$eq['quantity'];
                        
                        // Lưu ảnh
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
if (isset($conn)) mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý InfiDorm</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <!-- CSS riêng cho admin dashboard -->
  <link rel="stylesheet" href="../assets/css/manager_dashboard.css" />
  <!-- <link rel="stylesheet" href="manager.css" /> -->
</head>
<body>
  <header class="nav">
    <a href="home.php">Trang chủ</a>
    <a href="dashboard.php" class="active">Quản lý</a>
    <span style="margin-left: auto; color: #64748b;">
      <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?> - Tòa <?= htmlspecialchars($_SESSION['block'] ?? 'N/A') ?>
    </span>
  </header>

  <main class="admin-container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Danh mục quản lý KTX</h3>
        <small id="sidebar-sub">Admin</small>
      </div>

      <nav id="sidebarList" class="sidebar-list">
        <div class="sidebar-item active" data-view="students">
          <div>
            <div class="item-title">Danh sách phòng và sinh viên</div>
            <div class="item-desc">Xem danh sách từng phòng và sinh viên</div>
          </div>
        </div>
        <div class="sidebar-item" data-view="inventory">
          <div>
            <div class="item-title">Danh sách phòng và cơ sở vật chất</div>
            <div class="item-desc">Quản lý thiết bị</div>
          </div>
        </div>
      </nav>

      <div class="sidebar-footer">
        <button id="exportBtn" class="btn">Xuất log (JSON)</button>
        <button id="clearBtn" class="btn danger">Làm mới</button>
        <button id="logoutAdmin" class="btn ghost">Đăng xuất</button>
      </div>
    </aside>

    <section id="adminContent" class="content-area">
      <div id="contentInner" class="content-inner">
        <!-- Student view mặc định -->
        <div id="studentView" class="view-content">
          <?php if (isset($studentError)): ?>
            <div class="alert alert-error">⚠️ Lỗi: <?= htmlspecialchars($studentError) ?></div>
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
                    <th>
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
                    <th style="width: 130px">
                      <div class="th-content" data-col="3">
                        <span>Điện thoại</span>
                        <span class="sort-icon">⇅</span>
                      </div>
                      <input type="text" class="col-search" data-col="3" placeholder="Tìm SĐT...">
                    </th>
                    <th>
                      <div class="th-content" data-col="4">
                        <span>Địa chỉ</span>
                        <span class="sort-icon">⇅</span>
                      </div>
                      <input type="text" class="col-search" data-col="4" placeholder="Tìm địa chỉ...">
                    </th>
                    <th style="width: 120px">
                      <div class="th-content" data-col="5">
                        <span>Kỳ đăng ký</span>
                        <span class="sort-icon">⇅</span>
                      </div>
                      <input type="text" class="col-search" data-col="5" placeholder="Tìm kỳ...">
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($students as $s): ?>
                  <tr>
                    <td><span class="room-badge"><?= htmlspecialchars($s['ROOM_ID']) ?></span></td>
                    <td><?= htmlspecialchars($s['STD_NAME']) ?></td>
                    <td><?= htmlspecialchars($s['STD_ID']) ?></td>
                    <td><?= htmlspecialchars($s['STD_PHONE']) ?></td>
                    <td><?= htmlspecialchars($s['STD_ADR']) ?></td>
                    <td><?= htmlspecialchars($s['STARTDATE']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="table-info">Tổng: <?= count($students) ?> sinh viên</div>
          <?php endif; ?>
        </div>

        <!-- Inventory view -->
        <div id="inventoryView" class="view-content" style="display: none;">
          <?php if (isset($inventoryError)): ?>
            <div class="alert alert-error">⚠️ Lỗi: <?= htmlspecialchars($inventoryError) ?></div>
          <?php elseif (empty($items)): ?>
            <div class="alert alert-info">Chưa có dữ liệu thiết bị nào.</div>
          <?php else: ?>
            <div class="matrix-table">
              <table>
                <thead>
                  <tr>
                    <th>Vật dụng \ Phòng</th>
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
        </div>
      </div>
    </section>

    <!-- Viewer panel -->
    <aside id="viewer" class="viewer" aria-hidden="true">
      <button id="viewerClose" class="viewer-close" title="Đóng">✕</button>
      <div class="viewer-body">
        <h3 id="viewerTitle">Phòng - Vật dụng</h3>
        <div id="viewerMain" class="viewer-main">
          <img id="viewerMainImg" src="" alt="Ảnh vật dụng" />
        </div>
        <div id="viewerThumbs" class="viewer-thumbs"></div>
        <div id="viewerLinks" class="viewer-links"></div>
      </div>
    </aside>
  </main>

  <script>
    // Embed data
    window.INVENTORY_DATA = <?= json_encode(array_values($rooms)) ?>;
  </script>
  <script src="student_list.js"></script>
  <script src="inventory.js"></script>
</body>
</html>
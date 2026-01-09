<?php
// manager/views/requests.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('manager');

$userId = $_SESSION['user_id'];
$requests = [];

try {
    $mngQuery = "SELECT MNG_BLOCK FROM MANAGER WHERE USER_ID = ?";
    $stmt = mysqli_prepare($conn, $mngQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $manager = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $blockId = $manager['MNG_BLOCK'];

    $listQuery = "SELECT p.*, s.*, c.ROOM_ID 
                  FROM PROBLEM p 
                  JOIN STUDENT s ON p.STD_ID = s.STD_ID 
                  LEFT JOIN CONTRACT c ON s.STD_ID = c.STD_ID AND c.STATUS = 'Active'
                  WHERE p.BLOCK_ID = ? 
                  ORDER BY p.PR_ID DESC";
    
    $stmt = mysqli_prepare($conn, $listQuery);
    mysqli_stmt_bind_param($stmt, "s", $blockId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
} catch (Exception $e) { $error = $e->getMessage(); }
?>

<div class="header-section" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
  <h2>Yêu cầu hỗ trợ</h2>
  <button id="saveBatchBtn" class="btn btn-danger" onclick="RequestManager.resolveBatch()">
  Xóa các yêu cầu đã chọn
  </button>
</div>
<div class="student-table-wrapper">
  <table class="student-table" id="requestTable">
    <thead>
      <tr>
        <th style="width: 50px">
          <div class="th-content">
            <input type="checkbox" id="selectAll" onclick="RequestManager.toggleSelectAll(this)">
          </div>
          <div style="height: 45px"></div>
        </th>
        <th style="width: 80px">
          <div class="th-content" data-col="1"><span>ID</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="1" placeholder="ID...">
        </th>
        <th style="width: 100px">
          <div class="th-content" data-col="2"><span>Phòng</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="2" placeholder="Phòng...">
        </th>
        <th style="width: 200px">
          <div class="th-content" data-col="3"><span>Sinh viên</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="3" placeholder="Tên...">
        </th>
        <th style="width: 120px">
          <div class="th-content" data-col="4"><span>SĐT</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="4" placeholder="SĐT...">
        </th>
        <th style="width: 180px">
          <div class="th-content" data-col="5"><span>Tiêu đề</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="5" placeholder="Tiêu đề...">
        </th>
        <th>
          <div class="th-content" data-col="6"><span>Mô tả</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="6" placeholder="Mô tả...">
        </th>
        <th style="width: 160px">
          <div class="th-content" data-col="7"><span>Thời gian</span><span class="sort-icon">⇅</span></div>
          <input type="text" class="col-search" data-col="7" placeholder="Ngày...">
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($requests as $req): ?>
      <tr id="row-<?= $req['PR_ID'] ?>">
        <td class="text-center">
          <input type="checkbox" class="req-checkbox" value="<?= $req['PR_ID'] ?>" onclick="RequestManager.updateCounter()">
        </td>
        <td class="text-center">#<?= $req['PR_ID'] ?></td>
        <td class="text-center"><span class="room-pill"><?= htmlspecialchars($req['ROOM_ID'] ?: 'N/A') ?></span></td>
        <td><strong><?= htmlspecialchars($req['STD_NAME']) ?></strong></td>
        <td><?= htmlspecialchars($req['STD_PHONE']) ?></td>
        <td style="font-weight: bold;"><?= htmlspecialchars($req['PR_TITLE']) ?></td>
        <td class="content-cell"><?= nl2br(htmlspecialchars($req['CONTENT'])) ?></td>
        <td><?= date('H:i d/m/Y', strtotime($req['CREATED_AT'] ?? 'now')) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="table-info">
  <span id="statCounter">Đã giải quyết: 0 / <?= count($requests) ?> yêu cầu tồn đọng</span>
</div>
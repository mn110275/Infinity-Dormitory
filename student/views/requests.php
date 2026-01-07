<?php
  // student/views/requests.php
  require_once '../database_connection.php';
  require_once __DIR__ . '/../../auth/require_role.php';
  requireRole('student');
  
  $userId = $_SESSION['user_id'];
  $student = null;
  $problems = [];
  $error = null;

  try {
      $stdQuery = "SELECT S.STD_ID, BLOCK_ID FROM STUDENT S JOIN CONTRACT C ON S.STD_ID = C.STD_ID WHERE USER_ID = ?";
      $stmt = mysqli_prepare($conn, $stdQuery);
      mysqli_stmt_bind_param($stmt, "i", $userId);
      mysqli_stmt_execute($stmt);
      $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  } catch (Exception $e) {
      $error = $e->getMessage();
  }
  
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_problem') {
      try {
          $title = trim($_POST['title'] ?? '');
          $content = trim($_POST['content'] ?? '');
  
          if (empty($title))
              throw new Exception('Vui lòng nhập tiêu đề báo cáo');
  
          $query = "INSERT INTO PROBLEM (PR_TITLE, CONTENT, STD_ID, BLOCK_ID) VALUES (?, ?, ?, ?)";
          $stmt = mysqli_prepare($conn, $query);
          mysqli_stmt_bind_param($stmt, "ssss", $title, $content, $student['STD_ID'], $student['BLOCK_ID']);
  
          if (mysqli_stmt_execute($stmt)) {
              header('Location: ' . $_SERVER['PHP_SELF'] . '?view=requests');
              exit;
          }
      } catch (Exception $e) {
          $error = $e->getMessage();
      }
  }
  
  try {
      if ($student) {
          $listQuery = "SELECT * FROM PROBLEM WHERE STD_ID = ? ORDER BY PR_ID DESC LIMIT 10";
          $stmt = mysqli_prepare($conn, $listQuery);
          mysqli_stmt_bind_param($stmt, "s", $student['STD_ID']);
          mysqli_stmt_execute($stmt);
          $result = mysqli_stmt_get_result($stmt);
          while ($row = mysqli_fetch_assoc($result)) {
              $problems[] = $row;
          }
      }
  } catch (Exception $e) {
  }
  ?>
<div class="problem-form-section">
  <h2>Gửi yêu cầu hỗ trợ</h2>
  <form method="POST" id="problemForm">
    <input type="hidden" name="action" value="send_problem">
    <div class="form-group">
      <label for="title">Tiêu đề *</label>
      <input type="text" name="title" id="title" class="form-control" required
        placeholder="VD: Hỏng vòi nước, Cháy bóng đèn...">
    </div>
    <div class="form-group">
      <label for="content">Mô tả chi tiết</label>
      <textarea name="content" id="content" class="form-control" rows="6" maxlength="255"
        placeholder="Mô tả cụ thể tình trạng (không bắt buộc)..."></textarea>
    </div>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('problemForm').reset()">Xóa
      form</button>
      <button type="submit" class="btn btn-primary">Gửi báo cáo</button>
    </div>
  </form>
  <?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success" style="margin-top:15px;">Đã gửi yêu cầu thành công!</div>
  <?php endif; ?>
</div>
<div class="problem-history-section">
  <h3 style="padding-bottom: 20px">Yêu cầu đã gửi gần đây</h3>
  <?php if (empty($problems)): ?>
  <div class="empty-state-small">
    Chưa có báo cáo nào
  </div>
  <?php else: ?>
  <div class="problem-list">
    <?php foreach ($problems as $pr): ?>
    <div class="problem-item" id="problem-<?= $pr['PR_ID'] ?>">
      <div class="noti-header">
        <strong><?= htmlspecialchars($pr['PR_TITLE']) ?></strong>
        <span class="noti-date">
        #PR-<?= $pr['PR_ID'] ?>
        </span>
      </div>
      <div class="noti-content">
        <?= nl2br(htmlspecialchars($pr['CONTENT'] ?: '(Không có mô tả)')) ?>
      </div>
      <div class="noti-actions" style="display: flex; justify-content: flex-end">
        <button type="button" class="btn btn-danger" onclick="ProblemManager.revoke(<?= $pr['PR_ID'] ?>)">
        Thu hồi
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<script src="../js/requests.js"></script>
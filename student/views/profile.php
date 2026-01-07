<?php
// student/views/profile.php
require_once '../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$student = null;
$user = null;
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
  try {
    $stdPhone = $_POST['std_phone'] ?? '';
    $stdAdr = $_POST['std_adr'] ?? '';

    $imagePath = $_POST['current_image'] ?? '';
    if (isset($_FILES['std_img']) && $_FILES['std_img']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = '../uploads/students/';
      if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      $fileExt = strtolower(pathinfo($_FILES['std_img']['name'], PATHINFO_EXTENSION));
      $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

      if (in_array($fileExt, $allowedExts)) {
        $fileName = 'student_' . $userId . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['std_img']['tmp_name'], $targetPath)) {
          $imagePath = 'uploads/students/' . $fileName;
        }
      }
    }

    $updateQuery = "UPDATE STUDENT 
                        SET STD_PHONE = ?, 
                            STD_ADR = ?,
                            STD_IMG = ?
                        WHERE USER_ID = ?";

    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "sssi", $stdPhone, $stdAdr, $imagePath, $userId);

    if (mysqli_stmt_execute($stmt)) {
      $success = true;
      $_SESSION['name'] = $_POST['std_name'];
    } else {
      $error = "Không thể cập nhật thông tin";
    }
  } catch (Exception $e) {
    $error = $e->getMessage();
  }
}

try {
  if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");

    $query = "SELECT s.*, C.ROOM_ID, C.BLOCK_ID, u.EMAIL, u.EMAIL_VERIFIED_AT
                  FROM STUDENT s
                  JOIN CONTRACT C ON S.STD_ID = C.STD_ID
                  INNER JOIN USERS u ON s.USER_ID = u.USER_ID
                  WHERE s.USER_ID = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
      $student = $row;
      $user = [
        'email' => $row['EMAIL'],
        'verified' => $row['EMAIL_VERIFIED_AT']
      ];
    }
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}
?>

<h2>Thông tin cá nhân</h2>

<?php if ($success): ?>
  <script>
    alert("Cập nhật thông tin thành công!");
  </script>
<?php endif; ?>

<?php if ($error): ?>
<script>
    alert("Lỗi: <?= addslashes(htmlspecialchars($error)) ?>");
</script>
<?php endif; ?>

<?php if ($student): ?>
  <div class="profile-layout">
    <div class="profile-sidebar">
      <div class="avatar-wrapper">
        <?php if (!empty($student['STD_IMG'])): ?>
          <img src="../<?= htmlspecialchars($student['STD_IMG']) ?>" alt="Avatar" id="avatarPreview">
        <?php else: ?>
          <div class="avatar-placeholder">
            <span><?= strtoupper(substr($student['STD_NAME'], 0, 2)) ?></span>
          </div>
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-secondary" id="btnChangeAvatar">
        Đổi ảnh đại diện
      </button>

      <div style="text-align: center;">
        <h3><?= htmlspecialchars($student['STD_NAME']) ?></h3>
      </div>
      <div class="info-item">
        <strong>MSSV:</strong>
        <span><?= htmlspecialchars($student['STD_ID']) ?></span>
      </div>
      <div class="info-item">
        <strong>Giới tính:</strong>
        <span><?= htmlspecialchars($student['STD_GD']) ?></span>
      </div>
      <div class="info-item">
        <strong>Ngày sinh:</strong>
        <span><?= date('d/m/Y', strtotime($student['STD_DOB'])) ?></span>
      </div>
    </div>

    <div class="profile-main">
      <form method="POST" enctype="multipart/form-data" id="profileForm">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="current_image" value="<?= htmlspecialchars($student['STD_IMG'] ?? '') ?>">
        <input type="hidden" name="std_name" value="<?= htmlspecialchars($student['STD_NAME']) ?>">

        <input type="file" name="std_img" id="avatarInput" accept="image/*" style="display: none;">

        <div class="form-section">
          <h3>Thông tin cá nhân</h3>

          <div class="form-grid">
            <div class="form-group">
              <label>Họ và tên</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($student['STD_NAME']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Mã sinh viên</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($student['STD_ID']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Giới tính</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($student['STD_GD']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Ngày sinh</label>
              <input type="date" class="form-control" value="<?= date('Y-m-d', strtotime($student['STD_DOB'])) ?>"
                disabled>
            </div>

            <div class="form-group">
              <label>Phòng ở</label>
              <input type="text" class="form-control"
                value="<?= htmlspecialchars($student['ROOM_ID'] ?? 'Chưa sắp xếp') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Tòa</label>
              <input type="text" class="form-control"
                value="<?= htmlspecialchars($student['BLOCK_ID'] ?? 'Chưa sắp xếp') ?>" disabled>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Thông tin liên hệ</h3>

          <div class="form-grid">
            <div class="form-group">
              <label for="std_phone">Số điện thoại</label>
              <input type="tel" name="std_phone" id="std_phone" class="form-control"
                value="<?= htmlspecialchars($student['STD_PHONE']) ?>" required pattern="[0-9]{10,11}"
                placeholder="0901234567">
            </div>

            <div class="form-group">
              <label for="std_adr">Địa chỉ</label>
              <textarea name="std_adr" id="std_adr" class="form-control" rows="2" required
                placeholder="Nhập địa chỉ đầy đủ"><?= htmlspecialchars($student['STD_ADR']) ?></textarea>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Thông tin tài khoản</h3>

          <div class="form-grid">
            <div class="form-group">
              <div>
                <label>Email<span class="note">
                  <?php if ($user['verified']): ?>
                    (Đã xác thực)
                  <?php else: ?>
                    (Chưa xác thực)
                  <?php endif; ?>
                  </span>
                </label>
              </div>
              <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
              <div>
                <label>Mật khẩu</label>
              </div>
              <button type="button" class="btn btn-pro" style="margin-top: 0px" id="btnChangePassword">
                Đổi mật khẩu
              </button>
            </div>
          </div>  
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="location.reload()">
            Hủy bỏ
          </button>
          <button type="submit" class="btn btn-primary">
            Lưu thay đổi
          </button>
        </div>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-error">
    Không tìm thấy thông tin sinh viên. Vui lòng đăng nhập lại.
  </div>
<?php endif; ?>

<div id="passwordModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Đổi mật khẩu</h3>
      <span type="button" class="modal-close" id="closePasswordModal">✕</span>
    </div>
    <form id="passwordForm">
      <div class="form-group">
        <label for="current_password">Mật khẩu hiện tại *</label>
        <input type="password" id="current_password" name="current_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="new_password">Mật khẩu mới *</label>
        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
      </div>
      <div class="form-group">
        <label for="confirm_password">Xác nhận mật khẩu mới *</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="cancelPassword">Hủy</button>
        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
      </div>
    </form>
  </div>
</div>
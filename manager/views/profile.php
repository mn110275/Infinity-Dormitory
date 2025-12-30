<?php
// manager/views/profile.php
require_once '../database_connection.php';

$userId = $_SESSION['user_id'];
$manager = null;
$user = null;
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile')
{
  try
  {
    $mngPhone = $_POST['mng_phone'] ?? '';
    $mngAdr = $_POST['mng_adr'] ?? '';
    $imagePath = $_POST['current_image'] ?? '';
    if (isset($_FILES['mng_img']) && $_FILES['mng_img']['error'] === UPLOAD_ERR_OK)
    {
      $uploadDir = '../uploads/managers/';
      if (!file_exists($uploadDir))
      {
        mkdir($uploadDir, 0777, true);
      }

      $fileExt = strtolower(pathinfo($_FILES['mng_img']['name'], PATHINFO_EXTENSION));
      $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

      if (in_array($fileExt, $allowedExts))
      {
        $fileName = 'manager_'.$userId.
        '_'.time().
        '.'.$fileExt;
        $targetPath = $uploadDir.$fileName;

        if (move_uploaded_file($_FILES['mng_img']['tmp_name'], $targetPath))
        {
          $imagePath = 'uploads/managers/'.$fileName;
        }
      }
    }

    $updateQuery = "UPDATE MANAGER 
    SET MNG_PHONE = ? ,
      MNG_ADR = ? ,
      MNG_IMG = ?
      WHERE USER_ID = ? ";

    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "sssi", $mngPhone, $mngAdr, $imagePath, $userId);

    if (mysqli_stmt_execute($stmt))
    {
      $success = true;
      $_SESSION['name'] = $_POST['mng_name'];
    }
    else
    {
      $error = "Không thể cập nhật thông tin";
    }
  }
  catch (Exception $e)
  {
    $error = $e -> getMessage();
  }
}

try
{
  if ($conn)
  {
    mysqli_set_charset($conn, "utf8mb4");

    $query = "SELECT m.*, u.EMAIL, u.EMAIL_VERIFIED_AT
    FROM MANAGER m
    INNER JOIN USERS u ON m.USER_ID = u.USER_ID
    WHERE m.USER_ID = ? ";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result))
    {
      $manager = $row;
      $user = [
        'email' => $row['EMAIL'],
        'verified' => $row['EMAIL_VERIFIED_AT']
      ];
    }
  }
}
catch (Exception $e)
{
  $error = $e -> getMessage();
} 
?>

<h2>Thông tin cá nhân</h2>

<?php if ($success): ?>
  <div class="alert alert-success">
    Cập nhật thông tin thành công.
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-error">
    Lỗi: <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if ($manager): ?>
  <div class="profile-layout">
    <!-- Left: Avatar & Basic Info -->
    <div class="profile-sidebar">
      <div class="avatar-wrapper">
        <?php if (!empty($manager['MNG_IMG'])): ?>
          <img src="../<?= htmlspecialchars($manager['MNG_IMG']) ?>" alt="Avatar" id="avatarPreview">
        <?php else: ?>
          <div class="avatar-placeholder">
            <span><?= strtoupper(substr($manager['MNG_NAME'], 0, 2)) ?></span>
          </div>
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-secondary" id="btnChangeAvatar">
        Đổi ảnh đại diện
      </button>

      <div style="text-align: center;">
        <h2 style="margin: 10px;"><?= htmlspecialchars($manager['MNG_NAME']) ?></h2>
        <h3 style="color: var(--text-muted); font-weight: 600;">Quản lý tòa <?= htmlspecialchars($manager['MNG_BLOCK']) ?>
        </h3>
      </div>
      <div class="info-item">
        <strong>Mã QL:</strong>
        <span><?= htmlspecialchars($manager['MNG_ID']) ?></span>
      </div>
      <div class="info-item">
        <strong>Giới tính:</strong>
        <span><?= htmlspecialchars($manager['MNG_GD']) ?></span>
      </div>
      <div class="info-item">
        <strong>Ngày sinh:</strong>
        <span><?= date('d/m/Y', strtotime($manager['MNG_DOB'])) ?></span>
      </div>
    </div>

    <div class="profile-main">
      <form method="POST" enctype="multipart/form-data" id="profileForm">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="current_image" value="<?= htmlspecialchars($manager['MNG_IMG'] ?? '') ?>">
        <input type="hidden" name="mng_name" value="<?= htmlspecialchars($manager['MNG_NAME']) ?>">

        <input type="file" name="mng_img" id="avatarInput" accept="image/*" style="display: none;">

        <div class="form-section">
          <h3>Thông tin cá nhân</h3>

          <div class="form-grid">
            <div class="form-group">
              <label>Họ và tên</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($manager['MNG_NAME']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Mã quản lý</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($manager['MNG_ID']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Giới tính</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($manager['MNG_GD']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Ngày sinh</label>
              <input type="date" class="form-control" value="<?= date('Y-m-d', strtotime($manager['MNG_DOB'])) ?>"
                disabled>
            </div>

            <div class="form-group">
              <label>Tòa quản lý</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($manager['MNG_BLOCK']) ?>" disabled>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Thông tin liên hệ</h3>

          <div class="form-grid">
            <div class="form-group full-width">
              <label for="mng_phone">Số điện thoại</label>
              <input type="tel" name="mng_phone" id="mng_phone" class="form-control"
                value="<?= htmlspecialchars($manager['MNG_PHONE']) ?>" required pattern="[0-9]{10,11}"
                placeholder="0901234567">
            </div>

            <div class="form-group full-width">
              <label for="mng_adr">Địa chỉ</label>
              <textarea name="mng_adr" id="mng_adr" class="form-control" rows="2" required
                placeholder="Nhập địa chỉ đầy đủ"><?= htmlspecialchars($manager['MNG_ADR']) ?></textarea>
            </div>
          </div>
        </div>

        <div class="form-section" style="padding: 0px; border: none;">
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
    Không tìm thấy thông tin quản lý. Vui lòng đăng nhập lại.
  </div>
<?php endif; ?>


<div id="passwordModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Đổi mật khẩu</h3>
      <span type="button" class="modal-close" id="closePasswordModal">×</span>
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
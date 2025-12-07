<?php
session_start();
require '../database_connection.php'; // dùng chung database

// Kiểm tra phân quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header("Location: manager_login.php");
    exit;
}

// Lấy danh sách sinh viên + thông tin user + phòng
$sql = "
    SELECT sv.MSSV, sv.HO_TEN_SV, sv.CONTACT_SV, sv.ADDRESS_SV, sv.SEMESTER_DK, sv.IMAGE_SV,
           nd.EMAIL, ph.ROOM_NAME, t.TEN_TOA
    FROM SINHVIEN sv
    JOIN NGUOIDUNG nd ON sv.ID_USER = nd.ID_USER
    JOIN PHONG ph ON sv.ID_ROOM = ph.ID_ROOM
    JOIN TOA t ON ph.ID_TOA = t.ID_TOA
    ORDER BY sv.MSSV
";

$result = mysqli_query($conn, $sql);
$students = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quản lý InfiDorm</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/admin-dashboard.css" />
</head>
<body>
  <header class="nav">
    <a href="manager_home.php">Trang chủ</a>
    <a href="manager_dashboard.php" class="active">Quản lý</a>
    <a href="../logout.php" style="margin-left:auto">Đăng xuất</a>
  </header>

  <main class="admin-container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Quản lý KTX</h3>
        <small id="sidebar-sub">Admin</small>
      </div>

      <nav id="sidebarList" class="sidebar-list">
        <button class="sidebar-item" id="addStudentBtn">➕ Thêm sinh viên</button>
        <button class="sidebar-item" id="viewStudentListBtn">📄 Danh sách sinh viên</button>
      </nav>

      <div class="sidebar-footer">
        <button id="exportBtn" class="btn">Xuất log (JSON)</button>
        <button id="clearBtn" class="btn danger">Xóa toàn bộ</button>
        <button id="logoutAdmin" class="btn ghost">Đăng xuất</button>
      </div>
    </aside>

    <section id="adminContent" class="content-area">
      <div id="contentInner" class="content-inner">
        <h2>Danh sách sinh viên</h2>
        <table>
          <thead>
            <tr>
              <th>MSSV</th>
              <th>Họ và tên</th>
              <th>Email</th>
              <th>Liên hệ</th>
              <th>Địa chỉ</th>
              <th>Học kỳ đăng ký</th>
              <th>Phòng</th>
              <th>Tòa</th>
              <th>Ảnh</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $sv): ?>
              <tr>
                <td><?php echo htmlspecialchars($sv['MSSV']); ?></td>
                <td><?php echo htmlspecialchars($sv['HO_TEN_SV']); ?></td>
                <td><?php echo htmlspecialchars($sv['EMAIL']); ?></td>
                <td><?php echo htmlspecialchars($sv['CONTACT_SV']); ?></td>
                <td><?php echo htmlspecialchars($sv['ADDRESS_SV']); ?></td>
                <td><?php echo htmlspecialchars($sv['SEMESTER_DK']); ?></td>
                <td><?php echo htmlspecialchars($sv['ROOM_NAME']); ?></td>
                <td><?php echo htmlspecialchars($sv['TEN_TOA']); ?></td>
                <td>
                  <?php if($sv['IMAGE_SV']): ?>
                    <img src="<?php echo htmlspecialchars($sv['IMAGE_SV']); ?>" alt="Ảnh sinh viên" style="width:50px;height:50px;border-radius:4px;">
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if(empty($students)): ?>
              <tr>
                <td colspan="9" style="text-align:center;">Chưa có sinh viên nào.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Viewer (giữ nguyên) -->
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

  <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>

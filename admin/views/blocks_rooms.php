<?php
require_once __DIR__ . '/../../database_connection.php';
require_once __DIR__ . '/../../auth/require_role.php';
requireRole('admin');

$blocks = [];
$stats = [
    'total_rooms' => 0,
    'total_slots' => 0,
    'occupied' => 0,
    'available' => 0, 
    'unavailable' => 0 
];

try {
    if ($conn) {
        mysqli_set_charset($conn, "utf8mb4");

        $blockQuery = "SELECT b.*, 
                        (SELECT COUNT(*) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID AND r.GENDER = 'Nam') as count_male_rooms,
                        (SELECT COUNT(*) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID AND r.GENDER = 'Nữ') as count_female_rooms,
                        (SELECT COUNT(*) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID AND r.ROOM_STATUS = 'Active') as count_active_rooms,
                        (SELECT COUNT(*) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID AND r.ROOM_STATUS = 'Maintenance') as count_maint_rooms,
                        (SELECT COUNT(*) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID AND r.ROOM_STATUS = 'Closed') as count_closed_rooms,
                        (SELECT COUNT(*) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID) as count_rooms,
                        (SELECT SUM(CAPACITY) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID) as total_cap,
                        (SELECT SUM(OCCUPIED) FROM ROOM r WHERE r.BLOCK_ID = b.BLOCK_ID) as total_occ,
                        (SELECT SUM(CAPACITY - OCCUPIED) FROM ROOM r 
                        WHERE r.BLOCK_ID = b.BLOCK_ID AND r.ROOM_STATUS = 'Active' AND b.BLOCK_STATUS = 'Active') as available_slots
                    FROM BLOCK b ORDER BY b.BLOCK_ID ASC";
        
        $blocksData = [];
        $blockResult = mysqli_query($conn, $blockQuery);
        while ($row = mysqli_fetch_assoc($blockResult)) {
            $blocks[] = $row;

            $stats['total_rooms'] += $row['count_rooms'];
            $stats['total_slots'] += (int)$row['total_cap'];
            $stats['occupied']    += (int)$row['total_occ'];
            $stats['available']   += (int)$row['available_slots']; 
            $stats['unavailable'] += ($row['total_cap'] - $row['total_occ'] - $row['available_slots']);
            $blocksData[$row['BLOCK_ID']] = $row;
        }

        $rooms = [];
        $roomQuery = "SELECT * FROM ROOM ORDER BY ROOM_ID ASC";
        $roomResult = mysqli_query($conn, $roomQuery);
        while ($r = mysqli_fetch_assoc($roomResult)) {
            $rooms[] = $r;
        }
    }
} catch (Exception $e) { $errorMsg = $e->getMessage(); }

$activeBlocks = array_filter($blocks, function($b) { return $b['BLOCK_STATUS'] !== 'Closed'; });
$closedBlocks = array_filter($blocks, function($b) { return $b['BLOCK_STATUS'] === 'Closed'; });
$sortedBlocks = array_merge($activeBlocks, $closedBlocks);

function translateStatusPHP($status) {
    $map = [
        'Active' => 'Đang hoạt động',
        'Maintenance' => 'Bảo trì',
        'Closed' => 'Đã đóng cửa'
    ];
    return $map[$status] ?? $status;
}
?>

<div class="semester-wrapper">
    <div class="semester-header">
        <h2 class="title">Danh sách Tòa & Phòng</h2>
        <div class="header-actions">
            <button class="btn btn-action" onclick="BRAdmin.openAddBlock()">+ THÊM TÒA MỚI</button>
        </div>
    </div>

    <div class="stats-grid mb-20">
        <div class="stat-box info">
            <span class="stat-value" id="globalTotalSlots"><?= $stats['total_slots'] ?></span>
            <span class="stat-label">Tổng số giường</span>
        </div>
        
        <div class="stat-box success">
            <span class="stat-value" id="globalOccupied"><?= $stats['occupied'] ?></span>
            <span class="stat-label">Sinh viên đang ở</span>
        </div>

        <div class="stat-box warning">
            <span class="stat-value" id="globalAvailable"><?= $stats['available'] ?></span>
            <span class="stat-label">Chỗ còn sẵn</span>
        </div>

        <div class="stat-box danger">
            <span class="stat-value" id="globalUnavailable"><?= $stats['unavailable'] ?></span>
            <span class="stat-label">Chỗ chưa thể sử dụng</span>
        </div>
    </div>

    <div class="card shadow-sm mb-20">
        <h3 class="section-title">Hệ thống Tòa</h3>
        <div class="block-grid-4" id="blockContainer">
            <?php foreach ($sortedBlocks as $b): 
                $statusClass = '';
                if ($b['BLOCK_STATUS'] === 'Maintenance') $statusClass = 'is-maintenance';
                if ($b['BLOCK_STATUS'] === 'Closed') $statusClass = 'is-closed';
            ?>
                <div class="block-card <?= $statusClass ?>" 
                    data-block="<?= $b['BLOCK_ID'] ?>" 
                    data-status="<?= $b['BLOCK_STATUS'] ?>"
                    onclick="BRAdmin.selectBlock('<?= $b['BLOCK_ID'] ?>')">

                    <div class="block-header-flex">
                        <div class="block-name">Tòa <?= htmlspecialchars($b['BLOCK_ID']) ?></div>
                        <div class="block-status-container">
                            <span class="block-tag">
                                <?= translateStatusPHP($b['BLOCK_STATUS']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="block-info-wrapper"> <div class="block-info-line">
                            <strong><?= $b['count_rooms'] ?></strong> phòng
                        </div>
                        <div class="block-info-line">
                            Đã ở: <strong><?= (int)$b['total_occ'] ?>/<?= (int)$b['total_cap'] ?></strong> chỗ
                        </div>
                    </div>

                    <div class="progress-mini">
                        <div class="progress-bar" style="width: <?= ($b['total_cap'] > 0) ? ($b['total_occ']/$b['total_cap']*100) : 0 ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div id="selectedBlockHeader" style="display:none; margin-bottom: 15px;">
            <h2 style="margin:0; color: var(--cl); font-weight: 800; font-size: 24px;">
                TÒA <span id="selectedBlockIDDisplay">...</span>
            </h2>
        </div>

        <div id="blockStatusRow" style="display:none; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="label-hint" style="font-weight: 600; color: #64748b;">Trạng thái:</span>
                
                <div id="blockStatusTextContainer" style="display: flex; align-items: center; gap: 10px;">
                    <span id="blockStatusBadge" class="badge-status">...</span>
                    <button class="btn btn-pro" onclick="BRAdmin.toggleEditBlockStatus(true)">
                        <i class="fas fa-sync-alt"></i> Thay đổi
                    </button>
                </div>

                <div id="blockStatusEditContainer" style="display: none; align-items: center; gap: 10px;">
                    <select id="changeBlockStatus" class="form-control form-control-sm" style="width: 150px;">
                        <option value="Active">Hoạt động</option>
                        <option value="Maintenance">Bảo trì</option>
                        <option value="Closed">Đóng cửa</option>
                    </select>
                    <div class="edit-actions" style="display: flex; gap: 5px;">
                        <button class="btn-save-sm" onclick="BRAdmin.saveBlockStatus()">Lưu</button>
                        <button class="btn-cancel-sm" onclick="BRAdmin.toggleEditBlockStatus(false)">Hủy</button>
                    </div>
                </div>
            </div>

            <div id="blockDetailStats" style="display: flex; flex-wrap: wrap; gap: 20px; padding: 12px 15px; background: #f8fafc; border-radius: 10px;">
                <div class="mini-stat">♂ <span id="statMale">0</span> Phòng Nam</div>
                <div class="mini-stat">♀ <span id="statFemale">0</span> Phòng Nữ</div>
                <div style="width: 1px; background: #e2e8f0; height: 15px; align-self: center;"></div>
                <div class="mini-stat"><span class="dot-active"></span> <span id="statActive">0</span> Hoạt động</div>
                <div class="mini-stat"><span class="dot-maintenance"></span> <span id="statMaint">0</span> Bảo trì</div>
                <div class="mini-stat"><span class="dot-closed"></span> <span id="statClosed">0</span> Đóng</div>
            </div>
        </div>

        <div id="roomListHeader" class="flex-between" style="margin-bottom: 15px; display:none;">
            <h3 class="section-title no-margin">Danh sách Phòng</h3>
            <button id="btnAddRoom" class="btn btn-pro btn-custom-height" onclick="BRAdmin.openAddRoom()">
                <i class="fas fa-plus"></i> + Thêm Phòng
            </button>
        </div>
        <div id="roomListInstruction" style="display:none; margin-bottom: 15px; font-size: 13px; font-style: italic;"></div>
        
        <div id="roomContainer" class="room-grid mt-20">
            <div class="empty-state">Vui lòng chọn một tòa phía trên để xem danh sách phòng chi tiết.</div>
        </div>
    </div>
</div>

<div id="modalAddBlock" class="modal">
    <div class="modal-content shadow-lg" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Thêm Tòa mới</h3>
            <span class="close" onclick="BRAdmin.closeModal('modalAddBlock')">&times;</span>
        </div>
        <form id="formAddBlock" onsubmit="BRAdmin.submitAddBlock(event)">
            <div class="modal-body">
                <div class="form-group mb-15">
                    <label>Tên Tòa</label>
                    <input type="text" name="block_id" class="form-control" placeholder="Tên tòa" required maxlength="10">
                </div>
                <div class="form-group">
                    <label>Trạng thái ban đầu</label>
                    <select name="status" class="form-control">
                        <option value="Active">Hoạt động</option>
                        <option value="Maintenance">Bảo trì</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer mt-20">
                <button type="button" class="btn btn-secondary" onclick="BRAdmin.closeModal('modalAddBlock')">Hủy</button>
                <button type="submit" class="btn btn-pro">Xác nhận thêm tòa</button>
            </div>
        </form>
    </div>
</div>

<div id="modalAddRoom" class="modal">
    <div class="modal-content shadow-lg" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Thêm Phòng vào Tòa <span id="displayAddRoomBlockID">...</span></h3>
            <span class="close" onclick="BRAdmin.closeModal('modalAddRoom')">&times;</span>
        </div>
        <form id="formAddRoom" onsubmit="BRAdmin.submitAddRoom(event)">
            <div class="modal-body">
                <div class="form-group mb-15">
                    <label>Mã phòng</label>
                    <input type="text" name="room_id" class="form-control" placeholder="Ví dụ: 101, 102..." required maxlength="20">
                </div>
                
                <div class="row">
                    <div class="form-group mb-15">
                        <label>Sức chứa (giường)</label>
                        <input type="number" name="capacity" class="form-control" value="8" min="1" max="20" required>
                    </div>
                    <div class="form-group mb-15">
                        <label>Giới tính</label>
                        <select name="gender" class="form-control">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Trạng thái ban đầu</label>
                    <select name="status" class="form-control">
                        <option value="Active">Hoạt động </option>
                        <option value="Maintenance">Bảo trì</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer mt-20">
                <button type="button" class="btn btn-secondary" onclick="BRAdmin.closeModal('modalAddRoom')">Hủy</button>
                <button type="submit" class="btn btn-pro">Xác nhận thêm phòng</button>
            </div>
        </form>
    </div>
</div>

<div id="sideOverlay" class="sidebar-overlay" onclick="BRAdmin.closeSidebar()"></div>

<div id="roomSidebar" class="sidebar-panel">
    <div class="sidebar-content">
        <div class="sidebar-header-room">
            <h3>Phòng <span id="sideRoomId">...</span></h3>
            <span class="close" onclick="BRAdmin.closeSidebar()">&times;</span>
        </div>
        
        <div class="sidebar-body">
            <div id="roomSidebarWarning" style="display:none; background: #fff7ed; color: #9a3412; padding: 12px; border: 1px solid #fed7aa; border-radius: 8px; margin-bottom: 20px; font-size: 13px; line-height: 1.5;">
            </div>
            
            <div class="info-group-modern">
                <label class="info-label">TRẠNG THÁI</label>
                <div class="status-display-wrapper">
                    <div id="roomStatusTextContainer" class="status-text-row">
                        <span id="roomStatusBadge" class="badge-status">...</span>
                        <button class="btn" onclick="BRAdmin.toggleEditRoomStatus(true)">
                            <i class="fas fa-sync-alt"></i> Thay đổi
                        </button>
                    </div>

                    <div id="roomStatusEditContainer" class="status-edit-row" style="display: none;">
                        <select id="sideRoomStatus" class="form-control" style="flex: 1;">
                            <option value="Active">Đang hoạt động</option>
                            <option value="Maintenance">Bảo trì</option>
                            <option value="Closed">Đóng cửa</option>
                        </select>
                        <div class="edit-actions">
                            <button class="btn-save-sm" onclick="BRAdmin.saveRoomStatus()">Lưu</button>
                            <button class="btn-cancel-sm" onclick="BRAdmin.toggleEditRoomStatus(false)">Hủy</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="occupant-section mt-20">
                <h4 class="sub-title">Sinh viên đang ở</h4>
                <div id="occupantList" class="occupant-list">
                    <div class="empty-state">Đang tải dữ liệu...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.ALL_ROOMS = <?= json_encode($rooms) ?>;
    window.BLOCKS_DATA = <?= json_encode($blocksData) ?>;
</script>
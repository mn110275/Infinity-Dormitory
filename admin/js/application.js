const ApplicationAdmin = {
    data: [], 
    table: null,
    rows: [],
    el: {
        modal: null,
        body: null
    },
    selectedRoom: null,
    selectedBlock: null,

    init() {
        this.table = document.getElementById('applicationTable');
        if (!this.table) return;

        this.data = window.ALL_APPLICATIONS || [];
        this.rows = Array.from(this.table.querySelectorAll('tbody tr'));

        const defaultBtn = document.querySelector('.toggle-view[data-status="chua-xu-ly"]');
        if (defaultBtn) this.switchView('chua-xu-ly', defaultBtn);

        this.createPopups();
        this.bindEvents();
    },

    switchView(status, btnEl) {
        const table = document.getElementById('applicationTable');
        
        table.classList.remove('view-chua-xu-ly', 'view-da-chap-nhan', 'view-da-tu-choi');
        
        table.classList.add(`view-${status}`);
        
        document.querySelectorAll('.toggle-view').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline');
        });
        btnEl.classList.add('active', 'btn-primary');
        btnEl.classList.remove('btn-outline');

        if (typeof this.filter === 'function') this.filter();
    },

    createPopups() {
        if (document.getElementById('appDetailModal')) return;

        const m = document.createElement('div');
        m.id = 'appDetailModal';
        m.className = 'student-modal'; 
        m.innerHTML = `
            <div class="student-modal-content">
                <span class="close-btn" style="display: flex; justify-content: flex-end; cursor:pointer; font-size:24px;" onclick="ApplicationAdmin.closeModal()">×</span>
                <div id="appModalBody"></div>
            </div>`;
        document.body.appendChild(m);
        
        this.el.modal = m;
        this.el.body = document.getElementById('appModalBody');
    },

    bindEvents() {
        this.rows.forEach(row => {
            row.style.cursor = 'pointer';
            row.onclick = (e) => {
                if (e.target.closest('.col-search')) return;
                
                const id = row.getAttribute('data-id');
                this.showAppDetail(id);
            };
        });

        this.table.querySelectorAll('.th-content').forEach(el => {
            el.onclick = (e) => {
                e.stopPropagation();
                this.sort(parseInt(el.dataset.col), el);
            };
        });

        this.table.querySelectorAll('.col-search').forEach(input => {
            input.oninput = () => this.filter();
        });

        window.onclick = (e) => {
            if (e.target === this.el.modal) this.closeModal();
        };
    },

    showAppDetail(id) {
        const app = this.data.find(item => item.REG_ID == id);
        if (!app) return;

        this.selectedRoom = null;
        
        this.el.body.innerHTML = `
        <div class="profile-layout">
            <div class="profile-sidebar">
                <div class="avatar-wrapper">
                    <div class="avatar-placeholder">
                        <span>${app.REG_NAME.substring(0, 2).toUpperCase()}</span>
                    </div>
                </div>
                <div style="text-align:center; margin-bottom:20px;">
                    <h2 style="margin:0;">${app.REG_NAME}</h2>
                    <p style="color:var(--text-muted); margin:5px 0;">MSSV: ${app.REG_STD_ID}</p>
                    <span class="room-badge status-${this.slug(app.REG_STATUS)}">${app.REG_STATUS}</span>
                    <p style="font-size: 0.85rem; color: #888; margin-top: 10px;">Ngày gửi:<br>${app.CREATED_AT}</p>
                </div>
            </div>

            <div class="profile-column">
                <div class="profile-main">
                    ${app.REG_STATUS === 'Chưa xử lý' ? this.renderActionForm(app) : this.renderProcessedView(app)}
                </div>
            </div>
        </div>`;

        this.el.modal.style.display = 'flex';

        this.refreshRoomPicker()
    },  

    renderActionForm(app) {
        return `
        <div class="form-section">
            <h3>Thông tin sinh viên</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" id="reg-name" class="form-control" value="${app.REG_NAME}">
                </div>
                <div class="form-group">
                    <label>Mã số sinh viên (MSSV)</label>
                    <input type="text" id="reg-std-id" class="form-control" value="${app.REG_STD_ID}">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" id="reg-phone" class="form-control" value="${app.REG_PHONE}">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="reg-email" class="form-control" value="${app.REG_EMAIL}">
                </div>
                <div class="form-group">
                    <label>Ngày sinh</label>
                    <input type="date" id="reg-dob" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Giới tính</label>
                    <select id="reg-gender" class="form-control" onchange="ApplicationAdmin.refreshRoomPicker()">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Địa chỉ thường trú</label>
                    <textarea id="reg-adr" class="form-control" rows="2" placeholder="Nhập địa chỉ của sinh viên"></textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Chọn phòng lưu trú</h3>
            <div id="blockPicker" class="block-grid"></div>
            <div id="roomPicker" class="room-grid" style="margin-top:15px;"></div>
            <div id="room-selection-note" class="form-note hidden"></div>
        </div>

        <div class="form-actions" style="margin-top:20px; display:flex; gap:10px;">
            <button class="btn btn-danger" onclick="ApplicationAdmin.updateStatus(${app.REG_ID}, 'reject')">Từ chối đơn</button>
            <div style="flex-grow:1"></div>
            <button class="btn btn-secondary" onclick="ApplicationAdmin.closeModal()">Hủy</button>
            <button class="btn btn-primary" onclick="ApplicationAdmin.submitAccept(${app.REG_ID})">Chấp nhận đơn</button>
        </div>`;
    },

    renderProcessedView(app) {
        const isAccepted = app.REG_STATUS === 'Đã chấp nhận';
        const isRejected = app.REG_STATUS === 'Đã từ chối';

        return `
        <div class="form-section">
            <div class="form-note ${isAccepted ? 'info' : 'warning'} status-note">
                <strong>Trạng thái:</strong> ${app.REG_STATUS}
            </div>

            <h3>Thông tin đơn đăng ký</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" class="form-control" value="${app.REG_NAME}" readonly>
                </div>
                <div class="form-group">
                    <label>MSSV</label>
                    <input type="text" class="form-control" value="${app.REG_STD_ID}" readonly>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" class="form-control" value="${app.REG_PHONE}" readonly>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" class="form-control" value="${app.REG_EMAIL}" readonly>
                </div>
            </div>
        </div>

        <div class="modal-footer-custom">
            <div class="footer-left">
                ${isRejected ? `
                    <button class="btn btn-primary" onclick="ApplicationAdmin.updateStatus(${app.REG_ID}, 'undo')">
                        Hoàn tác từ chối
                    </button>
                ` : ''}
            </div>
            <div class="footer-right">
                <button class="btn btn-secondary" onclick="ApplicationAdmin.closeModal()">Đóng</button>
            </div>
        </div>`;
    },

    refreshRoomPicker() {
        const gender = document.getElementById('reg-gender').value;
        const note = document.getElementById('room-selection-note');
        const blockWrap = document.getElementById('blockPicker');
        const roomWrap = document.getElementById('roomPicker');

        blockWrap.innerHTML = '';
        roomWrap.innerHTML = '';
        this.selectedRoom = null;

        if (!blockWrap) return;
        blockWrap.innerHTML = '';
        document.getElementById('roomPicker').innerHTML = '';

        if (!gender) {
            note.textContent = 'Vui lòng chọn giới tính để hiển thị danh sách phòng phù hợp.';
            note.className = 'form-note warning'; 
            return;
        }

        note.className = 'form-note hidden';
        window.ALL_BLOCKS.forEach(block => {
            const available = block.available !== null ? block.available : 0;

            const div = document.createElement('div');
            div.className = 'block-card';
            div.innerHTML = `
                <div class="block-name">Tòa ${block.BLOCK_ID}</div>
                <div class="block-available">Còn ${available} chỗ</div>`;
            div.onclick = () => {
                document.querySelectorAll('.block-card').forEach(c => c.classList.remove('active'));
                div.classList.add('active');
                this.renderRooms(block.BLOCK_ID, gender);
            };
            blockWrap.appendChild(div);
        });
    },

    renderRooms(blockId) {
        const wrap = document.getElementById('roomPicker');
        wrap.innerHTML = '';
        const studentGender = document.getElementById('reg-gender').value;

        const rooms = window.ALL_ROOMS.filter(r => r.BLOCK_ID === blockId);

        if (rooms.length === 0) {
            wrap.innerHTML = '<p style="color:#888; grid-column:1/-1;">Không có phòng trong tòa này.</p>';
            return;
        }

        rooms.sort((a, b) => {
            const rank = (r) => (r.GENDER === studentGender && r.OCCUPIED < r.CAPACITY) ? 0 : 1;
            return rank(a) - rank(b);
        });

        rooms.forEach(r => {
            const div = document.createElement('div');
            div.className = 'room-card';
            
            const isGenderMatch = r.GENDER === studentGender;
            const hasSlot = r.OCCUPIED < r.CAPACITY;

            if (!isGenderMatch || !hasSlot) {
                div.classList.add('disabled');
            }
            
            if (this.selectedRoom === r.ROOM_ID) div.classList.add('active');

            div.innerHTML = `
                <div class="room-name">${r.ROOM_ID}</div>
                <div class="room-meta">${r.OCCUPIED}/${r.CAPACITY} chỗ · ${r.GENDER}</div>
                ${!isGenderMatch ? '<div class="room-note">Khác giới tính</div>' : (!hasSlot ? '<div class="room-note">Hết chỗ</div>' : '')}
            `;

            if (isGenderMatch && hasSlot) {
                div.onclick = () => {
                    this.selectedRoom = r.ROOM_ID;
                    this.selectedBlock = blockId;

                    document.querySelectorAll('.room-card').forEach(c => c.classList.remove('active'));
                    div.classList.add('active');
                    
                    const note = document.getElementById('room-selection-note');
                    note.textContent = `Đã chọn phòng: ${r.ROOM_ID} (Tòa ${blockId})`;
                    note.className = 'form-note info';
                };
            }
            wrap.appendChild(div);
        });
    },

    async submitAccept(id) {
        const name = document.getElementById('reg-name').value.trim();
        const stdId = document.getElementById('reg-std-id').value.trim();
        const phone = document.getElementById('reg-phone').value.trim();
        const email = document.getElementById('reg-email').value.trim();
        
        const dob = document.getElementById('reg-dob').value;
        const gd = document.getElementById('reg-gender').value;
        const adr = document.getElementById('reg-adr').value;

        if (!name || !stdId || !dob || !gd || !this.selectedRoom || !this.selectedBlock) {
            alert('Vui lòng nhập thông tin và chọn phòng đầy đủ!');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'accept');
        formData.append('id', id);
        
        formData.append('ho_ten', name);
        formData.append('mssv', stdId);
        formData.append('contact_sv', phone); 
        formData.append('email', email);
        
        formData.append('ngay_sinh', dob);
        formData.append('gioi_tinh', gd);
        formData.append('address_sv', adr);
        formData.append('id_room', this.selectedRoom);
        formData.append('id_block', this.selectedBlock);

        try {
            const res = await fetch('actions/application_action.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert('Chấp nhận đơn và cập nhật thông tin thành công!');
                location.reload();
            } else alert(data.message);
        } catch (e) { alert('Lỗi kết nối server'); }
    },

    async updateStatus(id, action) {
        if (!confirm('Xác nhận thao tác này?')) return;
        const res = await fetch('./actions/application_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, action })
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.message);
    },

    slug: (str) => {
        if (!str) return '';
        return str.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '') 
            .replace(/ /g, '-');
    },

    closeModal() { 
        if(this.el.modal) this.el.modal.style.display = 'none'; 
    },
    
    sort(colIndex, headerEl) {
        if (this.sortColumn === colIndex) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
        this.sortColumn = colIndex;
        this.sortOrder = 'asc';
        }

        this.table.querySelectorAll('.th-content')
        .forEach(e => e.classList.remove('sort-asc', 'sort-desc'));
        headerEl.classList.add(`sort-${this.sortOrder}`);

        this.rows.sort((a, b) => {
        const aText = a.children[colIndex]?.textContent.trim().toLowerCase() || '';
        const bText = b.children[colIndex]?.textContent.trim().toLowerCase() || '';
        const cmp = aText.localeCompare(bText, 'vi');
        return this.sortOrder === 'asc' ? cmp : -cmp;
        });

        const tbody = this.table.querySelector('tbody');
        this.rows.forEach(row => tbody.appendChild(row));

        this.filter();
    },

    filter() {
    const filters = Array.from(
        this.table.querySelectorAll('.col-search')
    ).map(input => input.value.toLowerCase());

    // Lấy class đang active trên table (ví dụ: view-chua-xu-ly)
    const currentViewClass = Array.from(this.table.classList).find(c => c.startsWith('view-'));
    const currentStatus = currentViewClass ? currentViewClass.replace('view-', '') : '';

    let visible = 0;

    this.rows.forEach(row => {
        const cells = Array.from(row.children);
        
        // 1. Kiểm tra tìm kiếm (Search)
        const matchSearch = filters.every((f, i) => {
            if (!f) return true;
            return (cells[i]?.textContent || '').toLowerCase().includes(f);
        });

        // 2. Kiểm tra bộ lọc trạng thái (View) - KHỚP HOÀN TOÀN VỚI CLASS PHP RENDER
        const matchView = row.classList.contains(`status-${currentStatus}`);

        if (matchSearch && matchView) {
            row.style.setProperty('display', 'table-row', 'important');
            visible++;
        } else {
            row.style.setProperty('display', 'none', 'important');
        }
    });

    const info = document.querySelector('.table-info');
    if (info) info.textContent = `Hiển thị: ${visible} đơn đăng ký`;
}
};

document.addEventListener('DOMContentLoaded', () => ApplicationAdmin.init());
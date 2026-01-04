const ManagerList = {
    data: [], 
    table: null,
    rows: [],
    el: {
        mngModal: null,
        body: null
    },

    init() {
        this.table = document.getElementById('managerTable');
        if (!this.table) return;

        this.data = window.ALL_MANAGERS || []; 
        this.rows = Array.from(this.table.querySelectorAll('tbody tr'));

        this.createPopups();
        this.bindEvents();
    },

    createPopups() {
        if (document.getElementById('managerProfileModal')) return;

        const mm = document.createElement('div');
        mm.id = 'managerProfileModal';
        mm.className = 'student-modal'; 
        mm.innerHTML = `
            <div class="student-modal-content">
                <span class="close-btn" style="display: flex; justify-content: flex-end;" onclick="ManagerList.closeModal()">×</span>
                <div id="managerProfileBody"></div>
            </div>`;
        document.body.appendChild(mm);
        this.el.mngModal = mm;
    },

    bindEvents() {
        this.table.querySelectorAll('.th-content').forEach(el => {
            el.onclick = () => this.sort(parseInt(el.dataset.col), el);
        });

        this.table.querySelectorAll('.col-search').forEach(input => {
            input.oninput = () => this.filter();
        });

        const btnAdd = document.getElementById('btnAddManager');
        btnAdd?.addEventListener('click', () => this.showManagerProfile(null));

        this.rows.forEach(row => {
            row.style.cursor = 'pointer';
            row.onclick = (e) => {
                const btnDelete = e.target.closest('.btn-delete');
                if (btnDelete) {
                    this.deleteManager(btnDelete.dataset.id);
                    return;
                }
                if (e.target.closest('.col-search')) return;
                this.showManagerProfile(row.dataset.id);
            };
        });

        window.onclick = (e) => {
            if (e.target === this.el.mngModal) this.closeModal();
        };
    },

    async showManagerProfile(mngId) {
        let m = { MNG_ID: '', MNG_NAME: '', MNG_EMAIL: '', MNG_DOB: '', MNG_GD: '', MNG_PHONE: '', MNG_ADR: '', MNG_BLOCK: '' };
        let isEdit = false;

        if (mngId) {
            isEdit = true;
            try {
                const res = await fetch(`actions/manager_action.php?id=${mngId}`);
                const resData = await res.json();
                if (resData.success) m = resData.manager;
                else return alert('Không tìm thấy thông tin quản lý');
            } catch (e) { return alert('Lỗi kết nối dữ liệu'); }
        }

        const body = document.getElementById('managerProfileBody');
        body.innerHTML = `
        <div class="profile-layout">
            <div class="profile-sidebar">
                <div class="avatar-wrapper">
                    <div class="avatar-placeholder"><span>${m.MNG_NAME ? m.MNG_NAME.substring(0, 2).toUpperCase() : 'M'}</span></div>
                </div>
                <div style="text-align:center; margin-bottom:10px;">
                    <input type="text" id="mng-name" class="form-control" 
                        style="font-size:22px; font-weight:bold; text-align:center; border:none; background:transparent;" 
                        placeholder="Nhập họ tên ..." value="${m.MNG_NAME}">
                    <p style="color:var(--text-muted); margin:5px 0 0 0;">${isEdit ? 'ID: ' + m.MNG_ID : 'Quản lý mới'}</p>
                </div>
                <div class="info-item"><strong>Vai trò:</strong> <span>Quản lý tòa</span></div>
            </div>

            <div class="profile-column">
                <div class="profile-main">
                    <div class="form-section">
                        <h3>Thông tin cá nhân</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Email đăng nhập</label>
                                <input type="email" id="mng-email" class="form-control" required value="${m.MNG_EMAIL || ''}" ${isEdit ? 'readonly style="background:#f5f5f5"' : ''}>
                            </div>
                            <div class="form-group">
                                <label>Giới tính</label>
                                <select id="mng-gd" class="form-control">
                                    <option value="Nam" ${m.MNG_GD === 'Nam' ? 'selected' : ''}>Nam</option>
                                    <option value="Nữ" ${m.MNG_GD === 'Nữ' ? 'selected' : ''}>Nữ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" id="mng-dob" class="form-control" required value="${m.MNG_DOB || ''}">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" id="mng-phone" class="form-control" value="${m.MNG_PHONE || ''}">
                            </div>
                            <div class="form-group full-width">
                                <label>Địa chỉ thường trú</label>
                                <textarea id="mng-adr" class="form-control" rows="2">${m.MNG_ADR || ''}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Công tác</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tòa quản lý</label>
                                <select id="mng-block" class="form-control">
                                    <option value="" disabled>Chọn tòa</option>
                                    ${(window.ALL_BLOCKS || []).map(b => `
                                        <option value="${b}" ${m.MNG_BLOCK === b ? 'selected' : ''}>Tòa ${b}</option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-secondary" onclick="ManagerList.closeModal()">Hủy</button>
                        <button class="btn btn-primary" onclick="ManagerList.saveManager('${m.MNG_ID}')">
                            ${isEdit ? 'Cập nhật' : 'Thêm quản lý'}
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
        this.el.mngModal.style.display = 'flex';
    },

    async saveManager(id) {
        const inputs = this.el.mngModal.querySelectorAll('[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.reportValidity(); 
                isValid = false;
            }
        });

        if (!isValid) return;

        const isEdit = id !== '';
        const formData = new FormData();

        formData.append('action', isEdit ? 'update' : 'add');
        formData.append('id', id);
        formData.append('mng_name', document.getElementById('mng-name').value);
        formData.append('mng_email', document.getElementById('mng-email').value);
        formData.append('mng_gd', document.getElementById('mng-gd').value);
        formData.append('mng_dob', document.getElementById('mng-dob').value);
        formData.append('mng_phone', document.getElementById('mng-phone').value);
        formData.append('mng_adr', document.getElementById('mng-adr').value);
        formData.append('mng_block', document.getElementById('mng-block').value);

        if (!formData.get('mng_name') || !formData.get('mng_email')) {
            return alert('Vui lòng điền thông tin cần thiết.');
        }

        try {
            const res = await fetch('actions/manager_action.php', {
                method: 'POST',
                body: formData 
            });
            const result = await res.json();

            if (result.success) {
                alert('Tạo quản lý mới thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (e) { 
            console.error(e);
            alert('Lỗi kết nối hệ thống'); 
        }
    },

    async deleteManager(id) {
        if (!confirm('Bạn có chắc chắn muốn xóa quản lý này?')) return;
        
        const params = new URLSearchParams();
        params.append('action', 'delete');
        params.append('id', id);

        try {
            const res = await fetch('actions/manager_action.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded' 
                },
                body: params.toString()
            });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(result.message);
        } catch (e) { 
            alert('Lỗi xóa dữ liệu'); 
        }
    },

    sort(colIndex, headerEl) {
        if (this.sortColumn === colIndex) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = colIndex;
            this.sortOrder = 'asc';
        }

        this.table.querySelectorAll('.th-content').forEach(e => e.classList.remove('sort-asc', 'sort-desc'));
        headerEl.classList.add(`sort-${this.sortOrder}`);

        this.rows.sort((a, b) => {
            const aText = a.children[colIndex]?.textContent.trim().toLowerCase() || '';
            const bText = b.children[colIndex]?.textContent.trim().toLowerCase() || '';
            return this.sortOrder === 'asc' ? aText.localeCompare(bText, 'vi') : bText.localeCompare(aText, 'vi');
        });

        const tbody = this.table.querySelector('tbody');
        this.rows.forEach(row => tbody.appendChild(row));
    },

    filter() {
        const filters = Array.from(this.table.querySelectorAll('.col-search')).map(input => input.value.toLowerCase());
        let visibleCount = 0;

        this.rows.forEach(row => {
            const cells = Array.from(row.children);
            const matches = filters.every((f, i) => !f || cells[i]?.textContent.toLowerCase().includes(f));
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        const infoEl = document.querySelector('.table-info');
        if (infoEl) infoEl.textContent = `Hiển thị: ${visibleCount} / ${this.rows.length} quản lý`;
    },

    closeModal() {
        if (this.el.mngModal) this.el.mngModal.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', () => ManagerList.init());
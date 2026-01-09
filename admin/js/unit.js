const Unit = {
    // 1. Logic ẩn hiện form tạo mới
    init() {
        const btnShow = document.getElementById('btnShowAddForm');
        const btnCancel = document.getElementById('btnCancelAdd');
        const section = document.getElementById('unit-create-section');
        const alertBox = document.getElementById('no-data-alert');

        if (btnShow) {
            btnShow.onclick = () => {
                section.style.display = 'block';
                alertBox.style.display = 'none';
            };
        }
        if (btnCancel) {
            btnCancel.onclick = () => {
                section.style.display = 'none';
                alertBox.style.display = 'block';
            };
        }

        // Xử lý submit form tạo mới
        document.getElementById('formAddUnit')?.addEventListener('submit', (e) => this.handleSave(e, 'add'));
    },

    // 2. Logic sửa trực tiếp trong bảng (Giống code cũ của bạn)
    startEdit(type, btn) {
        const cell = document.querySelector(`[data-unit="${type}"]`);
        const row = btn.closest('.action-cell');
        const oldVal = cell.textContent.replace(/\./g, '');

        cell.innerHTML = `<input type="number" id="edit-input-${type}" class="form-control" value="${oldVal}" style="width:150px">`;
        row.innerHTML = `
            <button class="btn btn-pro btn-sm" onclick="Unit.handleUpdate('${type}')">Lưu</button>
            <button class="btn btn-secondary btn-sm" onclick="location.reload()">Hủy</button>
        `;
    },

    async handleUpdate(type) {
        const val = document.getElementById(`edit-input-${type}`).value;
        const urlParams = new URLSearchParams(window.location.search);
        
        const body = new URLSearchParams();
        body.append('form_action', 'update');
        body.append(type, val);
        body.append('year', urlParams.get('year') || new Date().getFullYear());
        body.append('month', urlParams.get('month') || (new Date().getMonth() + 1));

        this.sendRequest(body);
    },

    async handleSave(e, action) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('form_action', action);
        this.sendRequest(new URLSearchParams(formData));
    },

    async sendRequest(body) {
        try {
            const res = await fetch('actions/unit_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            });
            const data = await res.json();
            if (data.success) {
                alert('Thành công!');
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (err) { alert('Lỗi hệ thống!'); }
    }
};

document.addEventListener('DOMContentLoaded', () => Unit.init());
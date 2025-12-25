const ManagerList = {
  table: null,

  init() {
    this.table = document.getElementById('managerTable');
    if (!this.table) return;

    this.bindEvents();
  },

  bindEvents() {
    /* ===== SỬA ===== */
    this.table.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', () => {
        this.openEditModal(btn.dataset.id);
      });
    });

    /* ===== XÓA ===== */
    this.table.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        this.deleteManager(btn.dataset.id);
      });
    });

    /* ===== THÊM ===== */
    const btnAdd = document.getElementById('btnAddManager');
    btnAdd?.addEventListener('click', () => this.openAddModal());

    /* ===== ĐÓNG MODAL ===== */
    document.getElementById('closeManagerModal')
      ?.addEventListener('click', () => this.closeModal());

    document.getElementById('cancelManager')
      ?.addEventListener('click', () => this.closeModal());

    /* ===== SUBMIT FORM ===== */
    document.getElementById('managerForm')
      ?.addEventListener('submit', e => {
        e.preventDefault();
        this.submitForm();
      });
  },

  /* ================= MODAL ================= */

  openAddModal() {
    const form = document.getElementById('managerForm');
    if (!form) return;

    form.reset();

    document.getElementById('modalTitle').textContent = 'Thêm quản lý mới';
    document.getElementById('form_action').value = 'add';
    document.getElementById('manager_id').value = '';

    this.showModal();
  },

  async openEditModal(id) {
    if (!id) {
      alert('Thiếu ID quản lý');
      return;
    }

    try {
      const res = await fetch(`actions/manager_action.php?id=${id}`, {
        headers: { 'Accept': 'application/json' }
      });

      const data = await res.json();

      if (!data.success) {
        alert(data.message || 'Không lấy được dữ liệu quản lý');
        return;
      }

      const m = data.manager;

      document.getElementById('modalTitle').textContent = 'Sửa thông tin quản lý';

      // action + id
      document.getElementById('form_action').value = 'update';
      document.getElementById('manager_id').value = m.MNG_ID;

      // fill dữ liệu
      document.getElementById('mng_name').value  = m.MNG_NAME  || '';
      document.getElementById('mng_dob').value   = m.MNG_DOB   || '';
      document.getElementById('mng_gd').value    = m.MNG_GD    || '';
      document.getElementById('mng_phone').value = m.MNG_PHONE || '';
      document.getElementById('mng_adr').value   = m.MNG_ADR   || '';
      document.getElementById('mng_block').value = m.MNG_BLOCK || '';

      const emailInput = document.getElementById('mng_email');
      if (emailInput) {
        emailInput.value = m.MNG_EMAIL || '';
      }

      this.showModal();
    } catch (err) {
      console.error(err);
      alert('Lỗi hệ thống khi tải dữ liệu');
    }
  },

  showModal() {
    document.getElementById('managerModal').style.display = 'flex';
  },

  closeModal() {
    document.getElementById('managerModal').style.display = 'none';
  },

  /* ================= SUBMIT ================= */

  async submitForm() {
    const form = document.getElementById('managerForm');
    if (!form) return;

    const formData = new FormData(form);

    try {
      const res = await fetch('actions/manager_action.php', {
        method: 'POST',
        headers: {
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        alert('Lưu quản lý thành công');
        this.closeModal();
        location.reload();
      } else {
        alert(data.message || 'Không thể lưu quản lý');
      }
    } catch (err) {
      console.error(err);
      alert('Lỗi hệ thống, vui lòng thử lại');
    }
  },

  /* ================= DELETE ================= */

  async deleteManager(id) {
    if (!id) return;
    if (!confirm('Bạn có chắc muốn xóa quản lý này?')) return;

    const body = new URLSearchParams();
    body.append('action', 'delete');
    body.append('id', id); 

    try {
      const res = await fetch('actions/manager_action.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json'
        },
        body: body.toString()
      });

      const data = await res.json();

      if (data.success) {
        alert('Xóa thành công');
        location.reload();
      } else {
        alert(data.message || 'Không thể xóa');
      }
    } catch (err) {
      console.error(err);
      alert('Lỗi hệ thống');
    }
  }
};

/* ================= INIT ================= */
document.addEventListener('DOMContentLoaded', () => ManagerList.init());

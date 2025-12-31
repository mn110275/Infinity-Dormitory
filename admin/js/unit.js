const Unit = {
  /* ================= STATE ================= */
  isEditing: false,
  editingType: null,
  editingValueCell: null,
  editingActionCell: null,
  oldText: '',

  /* ================= ELEMENTS ================= */
  monthSelect: null,
  yearSelect: null,
  addButton: null,
  addForm: null,  
  form: null,
  unitNoteDiv: null,  // div cảnh báo dự tính

  /* ================= INIT ================= */
  init() {
    this.monthSelect = document.querySelector('select[name="month"]');
    this.yearSelect  = document.querySelector('select[name="year"]');
    this.form        = document.querySelector('.date-inputs');
    this.unitNoteDiv = document.querySelector('.unit-note');  

    this.addButton = document.getElementById('btnAddFirstUnit');
    this.addForm = document.getElementById('addFirstUnitForm');
    this.btnCancelAdd = document.getElementById('btnCancelAdd');
    this.formAddUnit = document.getElementById('formAddUnit');

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-unit-edit');
      if (!btn || this.isEditing) return;

      this.startEdit(btn.dataset.type, btn);
    });

    this.yearSelect?.addEventListener('change', () => {
      this.updateMonthOptions();

      this.monthSelect.value = this.monthSelect.options[0]?.value || '';
    });

    this.form?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.loadByMonthYear();
    });

    // Bật/tắt hiển thị form thêm
    if (this.addButton && this.addForm && this.btnCancelAdd) {
      this.addButton.addEventListener('click', () => {
        this.addForm.style.display = 'block';
        this.addButton.style.display = 'none';
      });

      this.btnCancelAdd.addEventListener('click', () => {
        this.addForm.style.display = 'none';
        this.addButton.style.display = 'inline-block';
      });
    }

    // Xử lý submit form thêm bằng AJAX
    if (this.formAddUnit) {
      this.formAddUnit.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(this.formAddUnit);

        try {
          const res = await fetch(this.formAddUnit.getAttribute('action'), {
            method: 'POST',
            body: new URLSearchParams(formData),
            headers: { 'Accept': 'application/json' }
          });

          const data = await res.json();

          if (!data.success) {
            alert(data.message || 'Lỗi khi thêm đơn giá');
            return;
          }

          alert('Thêm đơn giá thành công');

          this.addForm.style.display = 'none';
          this.addButton.style.display = 'inline-block';

          // Tải lại toàn bộ trang
          location.reload(); 
        } catch (err) {
          console.error(err);
          alert('Lỗi kết nối hoặc hệ thống');
        }
      });
    }

    // Load lần đầu theo giá trị hiện tại trong dropdown
    this.loadByMonthYear();
  },

  /* ================= LOAD (XEM) ================= */
  async loadByMonthYear() {
    this.resetEditState();

    const month = this.monthSelect.value;
    const year  = this.yearSelect.value;

    try {
      const res = await fetch(
        `actions/unit_action.php?action=get&month=${month}&year=${year}`,
        { headers: { 'Accept': 'application/json' } }
      );

      const data = await res.json();

      this.renderUnit(data);

      // Hiển thị hoặc ẩn thông báo dự tính
      if (this.unitNoteDiv) {
        if (data.status === 'forecast') {
          this.unitNoteDiv.classList.remove('d-none');
        } else {
          this.unitNoteDiv.classList.add('d-none');
        }
      }

      // Nếu là tháng dự tính, gọi updateMonthOptions để cập nhật lại dropdown (loại bỏ tháng dự tính đã thành dữ liệu thật)
      if (data.status !== 'forecast') {
        this.updateMonthOptions();

        // Giữ lại giá trị tháng đã chọn nếu vẫn còn tồn tại trong dropdown
        if ([...this.monthSelect.options].some(opt => opt.value === month)) {
          this.monthSelect.value = month;
        } else if (this.monthSelect.options.length > 0) {
          this.monthSelect.value = this.monthSelect.options[0].value;
        }
      }
    } catch (err) {
      console.error(err);
      alert('Không thể tải đơn giá');
    }
  },

  /* ================= RENDER VIEW ================= */
  renderUnit(data) {
    const container = document.querySelector('.unit-container');

    if (this.addButton) {
      this.addButton.style.display = (data.electric_unit === null && data.water_unit === null) ? 'inline-block' : 'none';
    }     

    const elecCell  = document.querySelector('[data-unit="elec"]');
    const waterCell = document.querySelector('[data-unit="water"]');

    const elecAction  = elecCell.closest('tr').querySelector('.action-cell');
    const waterAction = waterCell.closest('tr').querySelector('.action-cell');

    /* ===== ĐƠN GIÁ ===== */
    elecCell.textContent =
      data.electric_unit !== null
        ? `${numberFormat(data.electric_unit)}`
        : '—';

    waterCell.textContent =
      data.water_unit !== null
        ? `${numberFormat(data.water_unit)}`
        : '—';

    /* ===== ACTION ===== */
    const editable = data.status !== 'past';

    elecAction.innerHTML = editable
      ? `<button class="btn btn-warning btn-unit-edit" data-type="elec">Sửa</button>`
      : `<button class="btn btn-secondary" disabled style="opacity:.5">Sửa</button>`;

    waterAction.innerHTML = editable
      ? `<button class="btn btn-warning btn-unit-edit" data-type="water">Sửa</button>`
      : `<button class="btn btn-secondary" disabled style="opacity:.5">Sửa</button>`;
  },

  /* ================= YEAR → MONTH ================= */
  updateMonthOptions() {
    const year = parseInt(this.yearSelect.value);
    const real = realMonthsByYear[year] || [];
    const proj = projectedMonthsByYear[year] || [];

    this.monthSelect.innerHTML = '';

    [...real, ...proj].forEach(m => {
      const opt = document.createElement('option');
      opt.value = m;
      opt.textContent = real.includes(m)
        ? `Tháng ${m}`
        : `Tháng ${m} (Dự tính)`;
      this.monthSelect.appendChild(opt);
    });
  },

  /* ================= EDIT ================= */
  startEdit(type, button) {
    this.isEditing = true;
    this.editingType = type;

    const valueCell  = document.querySelector(`[data-unit="${type}"]`);
    const actionCell = button.closest('.action-cell');

    this.editingValueCell  = valueCell;
    this.editingActionCell = actionCell;
    this.oldText = valueCell.textContent;

    const raw = this.oldText.replace(/[^\d]/g, '');

    valueCell.innerHTML = `
      <input type="number" id="unitInput"
        value="${raw}" min="0" style="width:140px">
    `;

    actionCell.innerHTML = `
      <button class="btn btn-primary btn-sm" id="btnSaveUnit">Lưu</button>
      <button class="btn btn-sm" id="btnCancelUnit">Hủy</button>
    `;

    document.getElementById('btnSaveUnit')
      ?.addEventListener('click', () => this.save());

    document.getElementById('btnCancelUnit')
      ?.addEventListener('click', () => this.cancel());
  },

  /* ================= CANCEL ================= */
  cancel() {
    this.editingValueCell.textContent = this.oldText;
    this.restoreAction();
    this.clearEditState();
  },

  /* ================= RESET EDIT ================= */
  resetEditState() {
    if (!this.isEditing) return;

    this.editingValueCell.textContent = this.oldText;
    this.restoreAction();
    this.clearEditState();
  },

  clearEditState() {
    this.isEditing = false;
    this.editingType = null;
    this.editingValueCell = null;
    this.editingActionCell = null;
    this.oldText = '';
  },

  restoreAction() {
    this.editingActionCell.innerHTML = `
      <button class="btn btn-warning btn-unit-edit" data-type="${this.editingType}">
        Sửa
      </button>
    `;
  },

  /* ================= SAVE ================= */
  async save() {
    const input = document.getElementById('unitInput');
    if (!input || input.value === '') {
      alert('Giá không được để trống');
      return;
    }

    const body = new URLSearchParams();
    body.append(this.editingType, input.value);
    body.append('year', this.yearSelect.value);
    body.append('month', this.monthSelect.value);

    const res = await fetch('actions/unit_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    const data = await res.json();
    if (!data.success) {
      alert(data.message);
      return;
    }

    this.editingValueCell.textContent =
      `${numberFormat(input.value)}`;

    this.restoreAction();
    this.clearEditState();
    alert('Lưu thành công');

    // Sau khi lưu thành công, load lại đơn giá để cập nhật trạng thái và dropdown
    this.loadByMonthYear();
  }
};

/* ================= INIT ================= */
document.addEventListener('DOMContentLoaded', () => Unit.init());

/* ================= UTILS ================= */
function numberFormat(n) {
  return new Intl.NumberFormat('vi-VN').format(n);
}

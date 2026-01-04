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
  dateForm: null,
  formAddUnit: null,
  cancelAddBtn: null,

  /* ================= INIT ================= */
  init() {
    this.monthSelect = document.querySelector('select[name="month"]');
    this.yearSelect  = document.querySelector('select[name="year"]');
    this.dateForm = document.getElementById('unitDateForm');

    this.addButton = document.getElementById('btnAddUnit');
    this.addForm = document.querySelector('.unit-create-form');
    this.formAddUnit = document.getElementById('formAddUnit');
    this.cancelAddBtn = document.getElementById('cancelAddUnit');

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-unit-edit');
      if (!btn || this.isEditing) return;

      this.startEdit(btn.dataset.type, btn);
    });

    this.yearSelect?.addEventListener('change', () => {
      this.updateMonthOptions(true, false);
      this.monthSelect.value = this.monthSelect.options[0]?.value || '';
    });

    this.dateForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.loadByMonthYear();
    });

    this.addButton && (this.addButton.onclick = () => {
      this.addForm.style.display = 'block';
      this.addButton.style.display = 'none';
    });

    this.cancelAddBtn && (this.cancelAddBtn.onclick = () => {
      this.addForm.style.display = 'none';
      this.addButton.style.display = 'inline-flex';
    });

    if (this.formAddUnit) {
      this.formAddUnit.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
          const formData = new URLSearchParams(new FormData(this.formAddUnit));
          
          const res = await fetch(this.formAddUnit.action, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
          });

          const data = await res.json();

          if (!data.success) {
            alert(data.message || 'Lỗi khi thêm đơn giá');
            return;
          }

          alert('Thêm đơn giá thành công');

          this.monthSelect.value = formData.get('month');
          this.yearSelect.value  = formData.get('year');

          this.loadByMonthYear();
        } catch (err) {
          console.error(err);
          alert('Lỗi hệ thống');
        }
      });
    }

    this.updateMonthOptions(false, true);
  },

  /* ================= LOAD (XEM) ================= */
  loadByMonthYear() {
    this.resetEditState();

    const params = new URLSearchParams(window.location.search);
    params.set('tab', 'unit');
    params.set('month', this.monthSelect.value);
    params.set('year', this.yearSelect.value);

    window.location.search = params.toString();
  },

  /* ================= MONTH DROPDOWN ================= */
  updateMonthOptions(keepSelected = false, autoLoad = false) {
    const year = parseInt(this.yearSelect.value);
    this.monthSelect.innerHTML = '';

    const years = Object.keys(monthsByYear).map(Number).sort((a, b) => a - b);
    const systemStartYear = years[0];
    const systemStartMonth = Math.min(...monthsByYear[systemStartYear]);

    for (let m = 1; m <= 12; m++) {
      const opt = document.createElement('option');
      opt.value = m;
      opt.textContent = `Tháng ${m}`;

      const isBeforeSystemStart =
        year < systemStartYear ||
        (year === systemStartYear && m < systemStartMonth);

      if (isBeforeSystemStart) {
        opt.disabled = true;
        opt.textContent += ' (không tồn tại)';
      }

      this.monthSelect.appendChild(opt);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const urlMonth = parseInt(urlParams.get('month'));

    if (keepSelected && this.monthSelect.value) return;

    const trySelect = (m) => {
      const opt = [...this.monthSelect.options]
        .find(o => parseInt(o.value) === m && !o.disabled);
      if (opt) this.monthSelect.value = opt.value;
      return !!opt;
    };

    if (urlMonth && trySelect(urlMonth)) return;
    if (trySelect(currentMonth)) return;

    const firstValid = [...this.monthSelect.options].find(o => !o.disabled);
    if (firstValid) this.monthSelect.value = firstValid.value;

    if (autoLoad) {
      this.loadByMonthYear(false, false);
    }
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
      <input type="number" id="unitInput" class="form-control"
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
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body
    });

    const data = await res.json();
    if (!data.success) {
      alert(data.message);
      return;
    }

    alert('Lưu thành công');

    this.loadByMonthYear();
  }
};

/* ================= INIT ================= */
document.addEventListener('DOMContentLoaded', () => Unit.init());

/* ================= UTILS ================= */
function numberFormat(n) {
  return new Intl.NumberFormat('vi-VN').format(n);
}

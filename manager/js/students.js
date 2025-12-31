// manager/js/student_list.js

const StudentManager = {
  data: [], // Chứa danh sách ALL_STUDENTS
  table: null,
  rows: [],

  el:
  {
    stdModal: null,
    body: null
  },

  init()
  {
    this.table = document.getElementById('studentTable');
    if (!this.table) return;

    this.data = window.ALL_STUDENTS || [];
    this.rows = Array.from(this.table.querySelectorAll('tbody tr'));

    this.createPopups();
    this.bindEvents();
  },

  createPopups()
  {
    if (document.getElementById('studentProfileModal')) return;

    const sm = document.createElement('div');
    sm.id = 'studentProfileModal';
    sm.className = 'student-modal';
    sm.innerHTML = `
            <div class="student-modal-content">
                <span class="close-btn" style="display: flex; justify-content: flex-end;" onclick="StudentManager.closeModal()">×</span>
                <div id="studentProfileBody"></div>
            </div>`;
    document.body.appendChild(sm);
    this.el.stdModal = sm;
  },

  bindEvents()
  {
    this.table.querySelectorAll('.th-content').forEach(el =>
    {
      el.onclick = () => this.sort(parseInt(el.dataset.col), el);
    });

    this.table.querySelectorAll('.col-search').forEach(input =>
    {
      input.oninput = () => this.filter();
    });

    this.rows.forEach(row =>
    {
      row.style.cursor = 'pointer';
      row.onclick = (e) =>
      {
        if (e.target.closest('.col-search')) return;
        this.showStudentProfile(row.dataset.id);
      };
    });

    window.onclick = (e) =>
    {
      if (e.target === this.el.stdModal) this.closeModal();
    };
  },

  // 4. HIỂN THỊ PROFILE
  showStudentProfile(stdId)
  {
    const s = this.data.find(item => item.STD_ID == stdId);
    if (!s) return;

    const rawDate = s.STD_DOB ? s.STD_DOB.substring(0, 10) : '';

    const body = document.getElementById('studentProfileBody');
    body.innerHTML = `
      <div class="profile-layout">
        <div class="profile-sidebar">
          <div class="avatar-wrapper">
            ${s.STD_IMG ? `<img src="../${s.STD_IMG}">` : `
            <div class="avatar-placeholder"><span>${s.STD_NAME.substring(0, 2).toUpperCase()}</span></div>
            `}
          </div>
          <div style="text-align:center; margin-bottom:10px;">
            <input type="text" id="edit-name" class="form-control" 
              style="font-size:22px; font-weight:bold; text-align:center; border:none; background:transparent;" 
              value="${s.STD_NAME}">
            <p style="color:var(--text-muted); margin:5px 0 0 0;">ID: ${s.STD_ID}</p>
          </div>
          <div class="info-item"><strong>Phòng:</strong> <span>${s.ROOM_ID}</span></div>
          <div class="info-item"><strong>Tòa:</strong> <span>${s.BLOCK_ID}</span></div>
        </div>
        <div class="profile-main">
          <div class="form-section">
            <h3>Thông tin cơ bản</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Giới tính</label>
                <select id="edit-gd" class="form-control">
                <option value="Nam" ${s.STD_GD === 'Nam' ? 'selected' : ''}>Nam</option>
                <option value="Nữ" ${s.STD_GD === 'Nữ' ? 'selected' : ''}>Nữ</option>
                <option value="Khác" ${s.STD_GD === 'Khác' ? 'selected' : ''}>Khác</option>
                </select>
              </div>
              <div class="form-group">
                <label>Ngày sinh</label>
                <input type="date" id="edit-dob" class="form-control" value="${rawDate}">
              </div>
              <div class="form-group full-width">
                <label>Số điện thoại</label>
                <input type="text" id="edit-phone" class="form-control" value="${s.STD_PHONE || ''}">
              </div>
            </div>
          </div>
          <div class="form-section">
            <h3>Địa chỉ liên hệ</h3>
            <div class="form-grid">
              <div class="form-group full-width">
                <label>Địa chỉ thường trú</label>
                <textarea id="edit-adr" class="form-control" rows="3">${s.STD_ADR || ''}</textarea>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-secondary" onclick="StudentManager.closeModal()">Hủy</button>
            <button class="btn btn-primary" onclick="StudentManager.saveStudent('${s.STD_ID}')">Lưu thay đổi</button>
          </div>
        </div>
      </div>`;
    this.el.stdModal.style.display = 'flex';
  },

  async saveStudent(stdId)
  {
    const data = {
      action: 'update_student',
      std_id: stdId,
      name: document.getElementById('edit-name').value,
      gd: document.getElementById('edit-gd').value,
      dob: document.getElementById('edit-dob').value,
      phone: document.getElementById('edit-phone').value,
      adr: document.getElementById('edit-adr').value
    };

    if (!confirm('Xác nhận cập nhật toàn bộ thông tin sinh viên?')) return;

    try
    {
      const res = await fetch('api/student_api.php',
      {
        method: 'POST',
        headers:
        {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      const result = await res.json();

      if (result.status === 'success')
      {
        alert('Đã lưu thay đổi!');
        const s = this.data.find(item => item.STD_ID == stdId);
        if (s)
        {
          s.STD_NAME = data.name;
          s.STD_GD = data.gd;
          s.STD_DOB = data.dob;
          s.STD_PHONE = data.phone;
          s.STD_ADR = data.adr;
        }
        this.closeModal();
        location.reload();
      }
      else
      {
        alert('Lỗi: ' + result.message);
      }
    }
    catch (e)
    {
      alert('Lỗi kết nối hệ thống.');
    }
  },

  sort(colIndex, headerEl)
  {
    if (this.sortColumn === colIndex)
    {
      this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
    }
    else
    {
      this.sortColumn = colIndex;
      this.sortOrder = 'asc';
    }

    this.table.querySelectorAll('.th-content').forEach(e =>
    {
      e.classList.remove('sort-asc', 'sort-desc');
    });
    headerEl.classList.add(`sort-${this.sortOrder}`);

    this.rows.sort((a, b) =>
    {
      const aText = a.children[colIndex]?.textContent.trim().toLowerCase() || '';
      const bText = b.children[colIndex]?.textContent.trim().toLowerCase() || '';
      const comparison = aText.localeCompare(bText, 'vi');
      return this.sortOrder === 'asc' ? comparison : -comparison;
    });

    const tbody = this.table.querySelector('tbody');
    this.rows.forEach(row => tbody.appendChild(row));

    this.filter();
  },

  filter()
  {
    const filters = Array.from(this.table.querySelectorAll('.col-search')).map(input =>
      input.value.toLowerCase()
    );

    let visibleCount = 0;
    this.rows.forEach(row =>
    {
      const cells = Array.from(row.children);
      const matches = filters.every((filter, i) =>
      {
        if (!filter) return true;
        const cellText = cells[i]?.textContent.trim().toLowerCase() || '';
        return cellText.includes(filter);
      });

      row.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    const infoEl = document.querySelector('.table-info');
    if (infoEl)
    {
      const total = this.rows.length;
      infoEl.textContent = visibleCount === total ?
        `Tổng: ${total} sinh viên` :
        `Hiển thị: ${visibleCount} / ${total} sinh viên`;
    }
  },

  closeModal()
  {
    if (this.el.stdModal) this.el.stdModal.style.display = 'none';
  }
};

document.addEventListener('DOMContentLoaded', () => StudentManager.init());
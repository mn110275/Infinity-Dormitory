const StudentAdmin = {
  data: [],
  table: null,
  rows: [],
  isRenewalMode: false,

  el:
  {
    stdModal: null,
    body: null
  },

  init() {
    this.table = document.getElementById('studentTable');
    if (!this.table) return;

    this.data = window.ALL_STUDENTS || [];
    this.rows = Array.from(this.table.querySelectorAll('tbody tr'));

    this.createPopups();
    this.bindEvents();
  },

  roomChange: {
    active: false,
    selectedBlock: null,
    selectedRoom: null,
    required: false
  },

  currentRoomGender: null,
  currentRoomId: null,
  currentBlockId: null,

  createPopups() {
    if (document.getElementById('studentProfileModal')) return;

    const sm = document.createElement('div');
    sm.id = 'studentProfileModal';
    sm.className = 'student-modal';
    sm.innerHTML = `
            <div class="student-modal-content">
                <span class="close-btn" style="display: flex; justify-content: flex-end;" onclick="StudentAdmin.closeModal()">×</span>
                <div id="studentProfileBody"></div>
            </div>`;
    document.body.appendChild(sm);
    this.el.stdModal = sm;
  },

  bindEvents() {
    this.table.querySelectorAll('.th-content').forEach(el => {
      if (el.classList.contains('no-sort')) return;
      el.onclick = () => this.sort(parseInt(el.dataset.col), el);
    });

    this.table.querySelectorAll('.col-search').forEach(input => {
      input.oninput = () => this.filter();
    });

    this.rows.forEach(row => {
      row.style.cursor = 'pointer';
      row.onclick = (e) => {
        if (e.target.closest('.col-renewal') || e.target.classList.contains('renewal-cb')) return;
        if (e.target.closest('.col-search')) return;
        this.showStudentProfile(row.dataset.id);
      };
    });

    window.onclick = (e) => {
      if (e.target === this.el.stdModal) this.closeModal();
    };
  },

  toggleRenewalMode() {
    if (this.isRenewalMode) {
      if (this.hasRenewalChanges()) {
        if (!confirm("Các thay đổi chưa được lưu sẽ bị hủy. Bạn có chắc chắn muốn thoát?")) {
          return;
        }
      }
      this.resetRenewalCheckboxes();
    }

    this.isRenewalMode = !this.isRenewalMode;
    const cols = document.querySelectorAll('.col-renewal');
    const thRenewal = this.table.querySelector('thead th:first-child');
    const actionDiv = document.getElementById('renewalActions');
    const btn = document.getElementById('btnToggleEditRenewal');

    const displayVal = this.isRenewalMode ? 'table-cell' : 'none';

    cols.forEach(c => c.style.display = displayVal);
    if (thRenewal) thRenewal.style.display = displayVal;

    actionDiv.style.display = this.isRenewalMode ? 'flex' : 'none';
    btn.innerHTML = this.isRenewalMode ? 'Thoát chế độ gia hạn' : 'Mở chế độ gia hạn';

    if (this.isRenewalMode) this.updateCounter();
  },

  updateCounter() {
    const checked = document.querySelectorAll('.renewal-cb:checked').length;
    const counterEl = document.getElementById('selectedCount');
    if (counterEl) counterEl.innerText = checked;
  },

  hasRenewalChanges() {
    const checkboxes = document.querySelectorAll('.renewal-cb');
    for (let cb of checkboxes) {
      const student = this.data.find(s => s.STD_ID == cb.value);
      if (student) {
        const originalStatus = !!parseInt(student.is_renewed);
        if (cb.checked !== originalStatus) return true;
      }
    }
    return false;
  },

  resetRenewalCheckboxes() {
    const checkboxes = document.querySelectorAll('.renewal-cb');
    checkboxes.forEach(cb => {
      const student = this.data.find(s => s.STD_ID == cb.value);
      if (student) {
        cb.checked = !!parseInt(student.is_renewed);
      }
    });
    this.updateCounter();
  },

  // 4. HIỂN THỊ PROFILE
  showStudentProfile(stdId) {
    const s = this.data.find(item => item.STD_ID == stdId);
    if (!s) return;

    const rawDate = s.STD_DOB ? s.STD_DOB.substring(0, 10) : '';

    this.roomChange = {
      active: false,
      selectedBlock: null,
      selectedRoom: null,
      required: false
    };

    const currentRoom = window.ALL_ROOMS.find(
      r => r.ROOM_ID === s.ROOM_ID
    );

    this.currentRoomGender = currentRoom?.GENDER || null;
    this.currentRoomId = s.ROOM_ID;
    this.currentBlockId = s.BLOCK_ID;

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

        <div class="profile-column">
          <div class="profile-main">
            <div class="form-section">
              <h3>Thông tin cơ bản</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label>Giới tính</label>
                  <select id="edit-gd" class="form-control">
                  <option value="Nam" ${s.STD_GD === 'Nam' ? 'selected' : ''}>Nam</option>
                  <option value="Nữ" ${s.STD_GD === 'Nữ' ? 'selected' : ''}>Nữ</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Ngày sinh</label>
                  <input type="date" id="edit-dob" class="form-control" value="${rawDate}">
                </div>
                <div class="form-group full-width">
                  <div id="gender-note" class="form-note hidden"></div>
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

            <div class="form-section">
              <h3 style="display:flex; justify-content:space-between; align-items:center;">
                Thông tin lưu trú
                <button class="btn btn-outline"
                  onclick="StudentAdmin.toggleRoomChange()">
                  Đổi phòng
                </button>
              </h3>

              <div class="residence-summary">
                <p><strong>Tòa hiện tại:</strong> ${s.BLOCK_ID}</p>
                <p><strong>Phòng hiện tại:</strong> ${s.ROOM_ID}</p>
              </div>

              <div id="room-note" class="form-note hidden"></div>

              <div id="roomChangePanel" style="display:none; margin-top:15px;">
                <div id="blockSelector" class="block-grid"></div>
                <div id="roomSelector" class="room-grid" style="margin-top:15px;"></div>
              </div>
            </div>

            <div class="form-actions">
              <button class="btn btn-danger" onclick="StudentAdmin.deleteStudent('${s.STD_ID}')" style="margin-right: auto;">Kết thúc lưu trú</button>
              <button class="btn btn-secondary" onclick="StudentAdmin.closeModal()">Hủy</button>
              <button class="btn btn-primary" onclick="StudentAdmin.saveStudent('${s.STD_ID}')">Lưu thay đổi</button>
            </div>
          </div>
        </div>
      </div>`;
    this.el.stdModal.style.display = 'flex';

    const genderSelect = document.getElementById('edit-gd');
    genderSelect.onchange = () => {
      this.roomChange.selectedRoom = null;
      document.querySelectorAll('.block-card, .room-card')
        .forEach(el => el.classList.remove('active'));

      this.checkGenderRoomMatch();

      if (this.roomChange.active && this.roomChange.selectedBlock) {
        this.renderRooms(this.roomChange.selectedBlock);
      }

      if (this.roomChange.required) {
        this.roomChange.active = true;
        document.getElementById('roomChangePanel').style.display = 'block';
        this.renderBlocks();

        if (this.roomChange.selectedBlock) {
          this.renderRooms(this.roomChange.selectedBlock);
        } else {
          document.getElementById('roomSelector').innerHTML = '';
        }
      } else {
        this.roomChange.active = false;
        document.getElementById('roomChangePanel').style.display = 'none';
      }
    };
  },

  checkGenderRoomMatch() {
    const gender = document.getElementById('edit-gd').value;
    const gdNote = document.getElementById('gender-note');
    const roomNote = document.getElementById('room-note');

    const selected = this.roomChange.selectedRoom;

    const roomGender =
      selected
        ? window.ALL_ROOMS.find(r => r.ROOM_ID === selected.room)?.GENDER
        : this.currentRoomGender;

    if (!roomGender || roomGender !== gender) {
      gdNote.textContent = 'Vui lòng chọn phòng mới cho sinh viên.';
      gdNote.className = 'form-note warning';
      this.roomChange.required = true;
    } else {
      gdNote.className = 'form-note hidden';
      this.roomChange.required = false;
    }

    if (selected) {
      roomNote.textContent = `Sẽ chuyển tới: Tòa ${selected.block} / Phòng ${selected.room}`;
      roomNote.className = 'form-note info';
      this.roomChange.required = false;
    } else {
      roomNote.className = 'form-note hidden';
    }
  },

  toggleRoomChange() {
    const panel = document.getElementById('roomChangePanel');
    if (!panel) return;

    this.roomChange.active = !this.roomChange.active;
    panel.style.display = this.roomChange.active ? 'block' : 'none';

    if (this.roomChange.active) {
      this.renderBlocks();

      if (this.roomChange.selectedBlock) {
        this.renderRooms(this.roomChange.selectedBlock);
      }
    }
  },

  renderBlocks() {
    const wrap = document.getElementById('blockSelector');
    if (!wrap) return;
    wrap.innerHTML = '';

    if (!Array.isArray(window.ALL_BLOCKS)) {
      console.error('ALL_BLOCKS chưa sẵn sàng:', window.ALL_BLOCKS);
      wrap.innerHTML = '<p style="color:red">Không tải được danh sách tòa</p>';
      return;
    }

    window.ALL_BLOCKS.forEach(block => {
      const available = block.available ?? 0;

      const div = document.createElement('div');
      div.className = 'block-card';
      div.dataset.block = block.BLOCK_ID;

      if (block.BLOCK_ID === this.currentBlockId)
        div.classList.add('current');

      div.innerHTML = `
        <div class="block-name">Tòa ${block.BLOCK_ID}</div>
        <div class="block-available">
          Còn ${available} chỗ
        </div>
      `;

      div.onclick = () => this.selectBlock(block.BLOCK_ID);

      wrap.appendChild(div);
    });
  },

  selectBlock(blockId) {
    this.roomChange.selectedBlock = blockId;

    document.querySelectorAll('.block-card').forEach(el => {
      el.classList.toggle('active', el.dataset.block === blockId);
    });

    this.renderRooms(blockId);
  },

  renderRooms(blockId) {
    const wrap = document.getElementById('roomSelector');
    if (!wrap) return;
    wrap.innerHTML = '';

    const studentGender = document.getElementById('edit-gd').value;

    const rooms = window.ALL_ROOMS.filter(r => r.BLOCK_ID === blockId);

    if (rooms.length === 0) {
      wrap.innerHTML = '<p style="color:#888; grid-column: 1 / -1;">Không có phòng trong tòa này.</p>';
      return;
    }

    rooms.sort((a, b) => {
      const rank = (r) => {
        const hasSlot = r.OCCUPIED < r.CAPACITY;
        if (!hasSlot) return 2;
        if (r.GENDER !== studentGender) return 1;
        return 0;
      };
      return rank(a) - rank(b);
    });

    rooms.forEach(r => {
      const div = document.createElement('div');
      div.className = 'room-card';
      div.dataset.room = r.ROOM_ID;

      const isGenderMatch = r.GENDER === studentGender;
      const hasSlot = r.OCCUPIED < r.CAPACITY;

      if (!isGenderMatch || !hasSlot) {
        div.classList.add('disabled');
      }

      div.innerHTML = `
        <div class="room-name">${r.ROOM_ID}</div>
        <div class="room-meta">
          ${r.OCCUPIED}/${r.CAPACITY} chỗ · ${r.GENDER}
        </div>
        ${!isGenderMatch
          ? `<div class="room-note"> Khác giới tính</div>`
          : !hasSlot
            ? `<div class="room-note"> Hết chỗ</div>`
            : ''
        }
      `;

      if (isGenderMatch && hasSlot) {
        div.onclick = () => {
          document.querySelectorAll('.room-card')
            .forEach(x => x.classList.remove('active'));

          div.classList.add('active');

          this.roomChange.selectedRoom = {
            room: r.ROOM_ID,
            block: r.BLOCK_ID
          };

          this.checkGenderRoomMatch();
        };
      }

      wrap.appendChild(div);
    });
  },

  async saveStudent(stdId) {
    const data = {
      action: 'update_student',
      std_id: stdId,
      name: document.getElementById('edit-name').value,
      gd: document.getElementById('edit-gd').value,
      dob: document.getElementById('edit-dob').value,
      phone: document.getElementById('edit-phone').value,
      adr: document.getElementById('edit-adr').value
    };

    if (this.roomChange.required) {
      alert('Giới tính đã được thay đổi, vui lòng chọn phòng mới phù hợp trước khi lưu.');
      return;
    }

    if (this.roomChange.selectedRoom) {
      data.room_id = this.roomChange.selectedRoom.room;
      data.block_id = this.roomChange.selectedRoom.block;
    }

    if (!confirm('Xác nhận cập nhật toàn bộ thông tin sinh viên?')) return;

    try {
      const res = await fetch('actions/student_action.php',
        {
          method: 'POST',
          headers:
          {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        });
      const result = await res.json();

      if (result.status === 'success') {
        alert('Đã lưu thay đổi!');
        const s = this.data.find(item => item.STD_ID == stdId);
        if (s) {
          s.STD_NAME = data.name;
          s.STD_GD = data.gd;
          s.STD_DOB = data.dob;
          s.STD_PHONE = data.phone;
          s.STD_ADR = data.adr;
        }
        this.closeModal();
        location.reload();
      }
      else {
        alert('Lỗi: ' + result.message);
      }
    }
    catch (e) {
      alert('Lỗi kết nối hệ thống.');
    }
  },

  async deleteStudent(stdId) {
    if (!confirm('Bạn có chắc chắn muốn kết thúc lưu trú cho sinh viên này?')) return;

    try {
      const res = await fetch('actions/student_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'delete_student',
          std_id: stdId
        })
      });
      const result = await res.json();

      if (result.status === 'success') {
        alert('Đã kết thúc lưu trú cho sinh viên thành công!');
        location.reload();
      } else {
        alert('Lỗi: ' + result.message);
      }
    } catch (e) {
      alert('Lỗi kết nối hệ thống.');
    }
  },

  async saveRenewalChanges() {
    const checkboxes = document.querySelectorAll('.renewal-cb');
    const renewalData = Array.from(checkboxes).map(cb => ({
      std_id: cb.value,
      is_marked: cb.checked ? 1 : 0
    }));

    if (!confirm(`Xác nhận lưu trạng thái gia hạn cho danh sách sinh viên hiện tại?`)) return;

    try {
      const res = await fetch('actions/student_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'bulk_mark_renewal',
          list: renewalData
        })
      });
      const result = await res.json();

      if (result.status === 'success') {
        alert('Đã cập nhật trạng thái gia hạn thành công!');
        renewalData.forEach(item => {
          const s = this.data.find(d => d.STD_ID == item.std_id);
          if (s) s.is_renewed = item.is_marked;
        });
        this.toggleRenewalMode();
      } else {
        alert('Lỗi: ' + result.message);
      }
    } catch (e) {
      alert('Lỗi kết nối hệ thống khi lưu gia hạn.');
    }
  },

  sort(colIndex, headerEl) {
    if (this.sortColumn === colIndex) {
      this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = colIndex;
      this.sortOrder = 'asc';
    }

    this.table.querySelectorAll('.th-content').forEach(e => {
      e.classList.remove('sort-asc', 'sort-desc');
    });
    headerEl.classList.add(`sort-${this.sortOrder}`);

    this.rows.sort((a, b) => {
      const aText = a.children[colIndex + 1]?.textContent.trim().toLowerCase() || '';
      const bText = b.children[colIndex + 1]?.textContent.trim().toLowerCase() || '';

      const comparison = aText.localeCompare(bText, 'vi');
      return this.sortOrder === 'asc' ? comparison : -comparison;
    });

    const tbody = this.table.querySelector('tbody');
    this.rows.forEach(row => tbody.appendChild(row));

    this.filter();
  },

  filter() {
    const searchInputs = Array.from(this.table.querySelectorAll('.col-search'));
    let visibleCount = 0;

    this.rows.forEach(row => {
      const cells = Array.from(row.children);

      const matches = searchInputs.every(input => {
        const filterValue = input.value.toLowerCase().trim();
        if (!filterValue) return true;

        const colIndex = parseInt(input.dataset.col) + 1;
        const cellText = cells[colIndex]?.textContent.trim().toLowerCase() || '';

        return cellText.includes(filterValue);
      });

      row.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    const infoEl = document.querySelector('.table-info');
    if (infoEl) {
      const total = this.rows.length;
      infoEl.textContent = visibleCount === total ?
        `Tổng: ${total} sinh viên` :
        `Hiển thị: ${visibleCount} / ${total} sinh viên`;
    }
  },

  toggleSelectAll(masterCb) {
    const visibleCheckboxes = this.rows
      .filter(row => row.style.display !== 'none')
      .map(row => row.querySelector('.renewal-cb'))
      .filter(cb => cb !== null);

    visibleCheckboxes.forEach(cb => {
      cb.checked = masterCb.checked;
    });
    this.updateCounter();
  },

  updateCounter() {
    const checked = document.querySelectorAll('.renewal-cb:checked').length;
    const infoEl = document.querySelector('.table-info');

    const visibleRows = this.rows.filter(r => r.style.display !== 'none');
    const visibleCount = visibleRows.length;
    const total = this.rows.length;

    if (this.isRenewalMode) {
      if (infoEl) {
        infoEl.innerHTML = `Đã gia hạn cho <b>${checked}</b> / <b>${visibleCount}</b> sinh viên`;
      }
    } else {
      if (infoEl) {
        infoEl.textContent = visibleCount === total ?
          `Tổng: ${total} sinh viên` :
          `Hiển thị: ${visibleCount} / ${total} sinh viên`;
      }
    }

    const selectAllCb = document.getElementById('selectAllRenewal');
    if (selectAllCb) {
      const visibleCbs = visibleRows.map(r => r.querySelector('.renewal-cb')).filter(cb => cb !== null);
      selectAllCb.checked = visibleCbs.length > 0 && visibleCbs.every(cb => cb.checked);
    }
  },

  closeModal() {
    if (this.el.stdModal) this.el.stdModal.style.display = 'none';
  }
};

document.addEventListener('DOMContentLoaded', () => StudentAdmin.init());
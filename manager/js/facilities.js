// manager/js/facility.js

const FacilityManager = {
  data: [],
  students:
  {},
  apiUrl: 'api/facility_api.php',

  el:
  {
    viewer: document.getElementById('viewer'),
    title: document.getElementById('viewerTitle'),
    content: document.getElementById('viewerContent'),
    closeBtn: document.getElementById('viewerClose'),
    modal: null, // Ảnh phóng to
    stdModal: null // Profile SV
  },

  // 1. KHỞI TẠO
  init()
  {
    if (window.APP_DATA)
    {
      this.data = window.APP_DATA.raw_facilities || [];
      this.students = window.APP_DATA.students ||
      {};
    }

    this.createPopups();
    this.bindEvents();
  },

  createPopups()
  {
    // Phóng to ảnh
    const m = document.createElement('div');
    m.className = 'modal-overlay';
    m.onclick = () => m.style.display = 'none';
    m.innerHTML = `<img id="modal-img" src="" style="border: 5px solid white; box-shadow: 0 0 20px rgba(0,0,0,0.5); max-width:90%; max-height:90%;">`;
    document.body.appendChild(m);
    this.el.modal = m;

    // Profile SV
    const sm = document.createElement('div');
    sm.id = 'studentProfileModal';
    sm.className = 'student-modal';
    sm.innerHTML = `
            <div class="student-modal-content">
                <span class="close-modal" onclick="document.getElementById('studentProfileModal').style.display='none'">×</span>
                <div id="studentProfileBody"></div>
            </div>`;
    document.body.appendChild(sm);
    this.el.stdModal = sm;
  },

  bindEvents()
  {
    // Event khi hover tên phòng
    document.querySelectorAll('.room-header').forEach(h =>
    {
      h.onmouseenter = () => this.renderStudents(h.dataset.roomId);
    });

    // Event khi hover đồ dùng
    document.querySelectorAll('.qty-cell').forEach(c =>
    {
      c.onmouseenter = () => this.renderEntities(c.dataset.roomId, c.dataset.item);
    });

    // Đóng viewer khi bỏ hover
    this.el.viewer.onmouseleave = () => this.close();
    if (this.el.closeBtn) this.el.closeBtn.onclick = () => this.close();
  },

  // 2. HIỂN THỊ DANH SÁCH SINH VIÊN TRONG SIDEBAR
  renderStudents(roomId)
  {
    const list = this.students[roomId] || [];
    this.el.title.innerText = `Thành viên - P.${roomId}`;

    // Dùng dấu = để reset nội dung, tránh x2
    let html = list.length ? list.map(s => `
            <div class="std-item" onclick="FacilityManager.showStudentProfile('${s.id}', '${roomId}')" 
                 style="display:flex; align-items:center; gap:12px; padding:12px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:0.2s;">
                <img src="../${s.img || 'assets/default-avatar.png'}" style="width:45px; height:45px; border-radius:50%; object-fit:cover; border: 2px solid #e2e8f0;">
                <div>
                    <div style="font-weight:bold; color:#2563eb;">${s.name}</div>
                    <div style="font-size:12px; color:#64748b;">MSSV: ${s.id}</div>
                </div>
            </div>`).join('') : '<p style="padding:20px; text-align:center; color:#94a3b8;">Phòng trống</p>';

    this.el.content.innerHTML = html;
    this.open();
  },

  // 3. HIỂN THỊ CHI TIẾT TRONG SIDEBAR
  renderEntities(roomId, itemType)
  {
    const items = this.data.filter(i => (i.ROOM_ID || i.room_id) == roomId && (i.FCLT_TYPE || i.fclt_type) == itemType);
    this.el.title.innerText = `${itemType} - P.${roomId}`;

    let html = `<div id="preview-box" style="width:100%; height:150px; background:#f1f5f9; margin-bottom:15px; border-radius:8px; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid #e2e8f0">
                        <img id="big-preview" src="" style="max-width:100%; max-height:100%; display:none;">
                        <span id="preview-text" style="color:#94a3b8; font-size:16px;">Rê chuột để xem nhanh. Click để phóng to</span>
                    </div>`;

    items.forEach((item) =>
    {
      const id = item.FCLT_ID || item.fclt_id;
      const note = item.FCLT_NOTE || item.fclt_note || '';
      const status = item.FCLT_STATUS || item.fclt_status || '';
      const photos = (item.FCLT_IMG || item.fclt_img) ? (item.FCLT_IMG || item.fclt_img).split('|') : [];

      html += `
            <div class="facility-entity-row" style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:15px; box-shadow:0 2px 4px rgba(0,0,0,0.02)">
              <div style="display:flex; justify-content:flex-end; margin-bottom:5px;">
                <span style="color:#ef4444; cursor:pointer; font-family: 'Google Sans'; font-size:16px; font-weight: bold;" 
                  onclick="FacilityManager.deleteEntity('${id}', '${roomId}', '${itemType}')">✖ Xóa bỏ</span>
              </div>
              <div class="image-scroller" style="display:flex; gap:8px; overflow-x:auto; padding:5px 0; margin-bottom:12px;">
                ${photos.map((src, pIdx) => `
                <div style="position:relative; flex:0 0 75px; height:75px;">
                  <img src="../${src}" style="width:100%; height:100%; object-fit:cover; border-radius:4px; cursor:zoom-in;" 
                    onmouseenter="FacilityManager.preview('../${src}')" onclick="FacilityManager.showPopup('../${src}')">
                  <button style="position:absolute; top:-5px; right:-5px; background:#ef4444; color:white; border:none; border-radius:50%; width:18px; height:18px; font-size:10px; cursor:pointer;"
                    onclick="FacilityManager.removePhoto('${id}', ${pIdx}, '${roomId}', '${itemType}')">×</button>
                </div>
                `).join('')}
                <div onclick="document.getElementById('file-${id}').click()" style="flex:0 0 75px; height:75px; border:2px dashed #cbd5e1; border-radius:4px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#94a3b8; font-size:24px;">+</div>
                <input type="file" id="file-${id}" hidden onchange="FacilityManager.uploadPhoto(this, '${id}', '${roomId}', '${itemType}')">
              </div>
              <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; align-items: flex-start; gap:8px;">
                  <span style="font-family: 'Google Sans'; font-size:18px; font-weight:bold; color:#475569; white-space:nowrap; margin-top:6px;">Mô tả:</span>
                  <textarea id="note-${id}" style="font-family: 'Google Sans'; font-weight: bold; flex:1; padding:6px; border:1px solid #cbd5e1; border-radius:4px; font-size:18px; min-height:45px;">${note}</textarea>
                </div>
                <div style="display:flex; align-items: center; gap:8px;">
                  <span style="font-family: 'Google Sans'; font-size:18px; font-weight:bold; color:#475569; white-space:nowrap;">Tình trạng:</span>
                  <input type="text" id="status-${id}" value="${status}" style="width: 180px; font-family: 'Google Sans'; font-size:18px; flex:1; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                  <button onclick="FacilityManager.saveInfo('${id}')" style="padding: 8px 12px; background:#64a9f2ff; color:white; border:none; border-radius:4px; cursor:pointer; ont-family: 'Google Sans'; font-size:16px; font-weight:bold;">LƯU</button>
                </div>
              </div>
            </div>`;
    });

    html += `<button onclick="FacilityManager.addNewEntity('${roomId}', '${itemType}')" style="width:100%; padding:12px; font-family: 'Google Sans'; font-size:18px; background:#64a9f2ff; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold; margin-top:5px;">✚ THÊM ĐỒ MỚI</button>`;
    this.el.content.innerHTML = html;
    this.open();
  },

  // 4. HIỂN THỊ PROFILE SINH VIÊN
  showStudentProfile(stdId, roomId)
  {
    const student = this.students[roomId].find(s => s.id === stdId);
    if (!student) return;

    const body = document.getElementById('studentProfileBody');
    body.innerHTML = `
      <div class="profile-container">
          <h2 style="margin-bottom:20px; color:#1e293b; border-bottom:2px solid #3b82f6; padding-bottom:10px;">Thông tin cá nhân</h2>
          <div class="profile-layout">
              <div class="profile-sidebar">
                  <div class="avatar-wrapper">
                      ${student.img ? `<img src="../${student.img}">` : `<div class="avatar-placeholder"><span>${student.name.substring(0, 2).toUpperCase()}</span></div>`}
                  </div>
                  <div class="info-card">
                      <h3 style="margin-top:10px;">${student.name}</h3>
                      <p style="color:#64748b;">MSSV: ${student.id}</p>
                  </div>
              </div>
              <div class="profile-main">
                  <div class="form-section">
                      <h3>Thông tin cơ bản</h3>
                      <div class="form-grid">
                          <div class="form-group"><label>Giới tính</label><input type="text" value="${student.gd || 'N/A'}" disabled></div>
                          <div class="form-group"><label>Ngày sinh</label><input type="text" value="${student.dob || 'N/A'}" disabled></div>
                          <div class="form-group"><label>Phòng ở</label><input type="text" value="${roomId}" disabled></div>
                          <div class="form-group"><label>Mã tòa</label><input type="text" value="${window.APP_DATA.managerBlock || 'N/A'}" disabled></div>
                      </div>
                  </div>
                  <div class="form-section">
                      <h3>Thông tin liên hệ</h3>
                      <div class="form-grid">
                          <div class="form-group"><label>Số điện thoại</label><input type="text" value="${student.phone || 'N/A'}" disabled></div>
                          <div class="form-group full-width"><label>Địa chỉ</label><textarea rows="2" disabled>${student.adr || 'N/A'}</textarea></div>
                      </div>
                  </div>
              </div>
          </div>
      </div>`;
    this.el.stdModal.style.display = 'flex';
  },

  // 5. CÁC HÀM XỬ LÝ DỮ LIỆU (ASYNC)
  async saveInfo(fcltId)
  {
    const status = document.getElementById(`status-${fcltId}`).value;
    const note = document.getElementById(`note-${fcltId}`).value;
    const res = await fetch(this.apiUrl,
    {
      method: 'POST',
      body: JSON.stringify(
      {
        action: 'update_full_info',
        fclt_id: fcltId,
        status,
        note
      })
    });
    if ((await res.json()).status === 'success')
    {
      const item = this.data.find(i => (i.FCLT_ID || i.fclt_id) == fcltId);
      item.FCLT_STATUS = status;
      item.FCLT_NOTE = note;
      alert('Đã cập nhật!');
    }
  },

  async deleteEntity(fcltId, roomId, itemType)
  {
    if (!confirm('Xóa vĩnh viễn đồ vật này?')) return;
    const res = await fetch(this.apiUrl,
    {
      method: 'POST',
      body: JSON.stringify(
      {
        action: 'delete_entity',
        fclt_id: fcltId
      })
    });
    if ((await res.json()).status === 'success')
    {
      this.data = this.data.filter(i => (i.FCLT_ID || i.fclt_id) != fcltId);
      this.renderEntities(roomId, itemType);
      this.updateTableQty(roomId, itemType);
    }
  },

  async addNewEntity(roomId, itemType)
  {
    const res = await fetch(this.apiUrl,
    {
      method: 'POST',
      body: JSON.stringify(
      {
        action: 'add_new_entity',
        room_id: roomId,
        item_type: itemType
      })
    });
    const result = await res.json();
    if (result.status === 'success')
    {
      this.data.push(
      {
        FCLT_ID: result.new_id,
        ROOM_ID: roomId,
        FCLT_TYPE: itemType,
        FCLT_IMG: '',
        FCLT_STATUS: '',
        FCLT_NOTE: ''
      });
      this.renderEntities(roomId, itemType);
      this.updateTableQty(roomId, itemType);
    }
  },

  async uploadPhoto(input, fcltId, roomId, itemType)
  {
    if (!input.files[0]) return;
    const formData = new FormData();
    formData.append('action', 'add_photo');
    formData.append('fclt_id', fcltId);
    formData.append('file', input.files[0]);
    const res = await fetch(this.apiUrl,
    {
      method: 'POST',
      body: formData
    });
    const result = await res.json();
    if (result.status === 'success')
    {
      const item = this.data.find(i => (i.FCLT_ID || i.fclt_id) == fcltId);
      const currentImg = item.FCLT_IMG || item.fclt_img;
      item.FCLT_IMG = currentImg ? currentImg + '|' + result.path : result.path;
      this.renderEntities(roomId, itemType);
    }
  },

  async removePhoto(fcltId, index, roomId, itemType)
  {
    const item = this.data.find(i => (i.FCLT_ID || i.fclt_id) == fcltId);
    let photos = (item.FCLT_IMG || item.fclt_img).split('|');
    photos.splice(index, 1);
    const newStr = photos.join('|');
    const res = await fetch(this.apiUrl,
    {
      method: 'POST',
      body: JSON.stringify(
      {
        action: 'update_photos',
        fclt_id: fcltId,
        images: newStr
      })
    });
    if ((await res.json()).status === 'success')
    {
      item.FCLT_IMG = newStr;
      this.renderEntities(roomId, itemType);
    }
  },

  async addNewType()
  {
    try
    {
      // Reset biến về rỗng
      let typeName = "";
      let roomId = "";

      typeName = prompt("Nhập tên loại đồ dùng mới (VD: Máy lạnh):", "");
      if (typeName === null || typeName.trim() === "") return;

      roomId = prompt(`Thêm "${typeName.trim()}" vào phòng nào?`, "");
      if (roomId === null || roomId.trim() === "")
      {
        alert("Hủy bỏ: Bạn chưa nhập mã phòng.");
        return;
      }

      const res = await fetch(this.apiUrl,
      {
        method: 'POST',
        headers:
        {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(
        {
          action: 'add_new_row_type',
          item_type: typeName.trim(),
          room_id: roomId.trim()
        })
      });

      const result = await res.json();

      if (result.status === 'success')
      {
        alert(`Đã thêm hàng "${typeName.trim()}" thành công!`);
        location.reload();
      }
      else
      {
        alert(result.message || "Lỗi: Kiểm tra lại mã phòng.");
      }
    }
    catch (error)
    {
      console.error("Lỗi:", error);
    }
  },

  // 6. CỬA SỔ
  preview(src)
  {
    const big = document.getElementById('big-preview');
    const text = document.getElementById('preview-text');
    if (big)
    {
      big.src = src;
      big.style.display = 'block';
    }
    if (text) text.style.display = 'none';
  },
  showPopup(src)
  {
    const img = document.getElementById('modal-img');
    img.src = src;
    this.el.modal.style.display = 'flex';
  },
  updateTableQty(roomId, itemType)
  {
    const cell = document.querySelector(`.qty-cell[data-room-id="${roomId}"][data-item="${itemType}"]`);
    if (cell)
    {
      const count = this.data.filter(i => (i.ROOM_ID || i.room_id) == roomId && (i.FCLT_TYPE || i.fclt_type) == itemType).length;
      cell.innerText = count;
    }
  },
  open()
  {
    this.el.viewer.classList.add('open');
  },
  close()
  {
    this.el.viewer.classList.remove('open');
  }
};

document.addEventListener('DOMContentLoaded', () => FacilityManager.init());
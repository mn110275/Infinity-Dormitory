const ApplicationList = {
  table: null,
  rows: [],
  sortColumn: null,
  sortOrder: 'asc',

  init() {
    this.table = document.getElementById('applicationTable');
    if (!this.table) return;

    this.rows = Array.from(this.table.querySelectorAll('tbody tr'));
    this.bindEvents();
  },

  bindEvents() {
    // Sort
    this.table.querySelectorAll('.th-content').forEach(el => {
      el.addEventListener('click', () => {
        const col = parseInt(el.dataset.col);
        this.sort(col, el);
      });
    });

    // Filter
    this.table.querySelectorAll('.col-search').forEach(input => {
      input.addEventListener('input', () => this.filter());
    });
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

    let visible = 0;

    this.rows.forEach(row => {
      const cells = Array.from(row.children);
      const match = filters.every((f, i) => {
        if (!f) return true;
        return (cells[i]?.textContent || '').toLowerCase().includes(f);
      });

      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    const info = document.querySelector('.table-info');
    if (info) {
      const total = this.rows.length;
      info.textContent =
        visible === total
          ? `Tổng: ${total} đơn đăng ký`
          : `Hiển thị: ${visible} / ${total} đơn đăng ký`;
    }
  }
};

// Auto init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => ApplicationList.init());
} else {
  ApplicationList.init();
}

/* =========================
   ACTION HANDLER
========================= */
document.addEventListener('click', async e => {

    /* ===== TỪ CHỐI ===== */
    if (e.target.classList.contains('btn-reject')) {
        const btn = e.target;
        const row = btn.closest('tr');
        const id = btn.dataset.id;
        btn.disabled = true;

        const ok = await updateStatus(id, 'reject');
        if (!ok) {
            btn.disabled = false;
            return;
        }

        window.location.href = 'dashboard.php?tab=application';
    }

    /* ===== HOÀN TÁC ===== */
    if (e.target.classList.contains('btn-undo')) {
        const btn = e.target;
        const row = btn.closest('tr');
        const id = btn.dataset.id;

        btn.disabled = true;

        const ok = await updateStatus(id, 'undo');
        if (!ok) {
            btn.disabled = false;
            return;
        }

        window.location.href = 'dashboard.php?tab=application';
    }

  /* ===== CHẤP NHẬN (tạm thời) ===== */
  if (e.target.classList.contains('btn-accept')) {
    const row = e.target.closest('tr');
    const name = row.children[1]?.textContent || '';
  }
});

/* =========================
   API CALL
========================= */
async function updateStatus(id, action) {
  try {
    const res = await fetch('./actions/application_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id, action })
    });

    const data = await res.json();
    if (!data.success) {
      alert(data.message || 'Cập nhật thất bại');
      return false;
    }

    return true;
  } catch (err) {
    console.error(err);
    alert('Không thể kết nối server');
    return false;
  }
}

/* =========================
   POPUP MODAL
========================= */
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('acceptModal');
  const closeBtn = document.getElementById('closeAcceptModal');
  const cancelBtn = document.getElementById('cancelAccept');
  const acceptForm = document.getElementById('acceptForm');

  // Mở modal khi bấm nút chấp nhận (giả sử bạn đã gắn class btn-accept và data-id)
  document.querySelectorAll('.btn-accept').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      const idDon = btn.getAttribute('data-id');
      const mssv = row.children[2].textContent.trim();
      const hoTen = row.children[1].textContent.trim();
      const contact = row.children[3].textContent.trim();

      // Reset form và điền dữ liệu
      acceptForm.reset();
      document.getElementById('application_id').value = idDon;
      document.getElementById('mssv').value = mssv;
      document.getElementById('ho_ten').value = hoTen;
      document.getElementById('contact_sv').value = contact;

      // Lọc phòng theo giới tính lần đầu
      filterRoomsByGender();

      modal.style.display = 'flex';
    });
  });

  // Đóng modal
  closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  // Lọc phòng theo giới tính
  const genderSelect = document.getElementById('gioi_tinh');
  const roomSelect = document.getElementById('id_room');

  function filterRoomsByGender() {
    const selectedGender = genderSelect.value;
    for (const option of roomSelect.options) {
      if (!option.value) continue;
      option.style.display = (selectedGender === "" || option.getAttribute('data-gender') === selectedGender) ? '' : 'none';
    }
    // Reset chọn nếu phòng hiện tại không hợp giới tính
    if (roomSelect.selectedOptions.length > 0) {
      const selectedOption = roomSelect.selectedOptions[0];
      if (selectedOption.style.display === 'none') {
        roomSelect.value = "";
      }
    }
  }

  genderSelect.addEventListener('change', filterRoomsByGender);

  acceptForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(acceptForm);

    formData.append('action', 'accept'); 
    formData.append('id', formData.get('application_id')); 

    fetch('actions/application_action.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        alert('Đã chấp nhận đơn và tạo người dùng sinh viên.');
        modal.style.display = 'none';
        location.reload();
      } else {
        alert('Lỗi: ' + data.message);
      }
    })
    .catch(() => alert('Lỗi hệ thống, vui lòng thử lại sau.'));
  });
});

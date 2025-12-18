/* manager_dashboard.js - Version with separate API for each view */

const Dashboard = {
  data: null,
  currentView: 'students',
  
  // DOM elements
  el: {},
  
  // Sidebar configuration
  sidebar: [
    { id: 'students', title: 'Danh sách phòng và sinh viên', desc: 'Xem danh sách từng phòng và sinh viên' },
    { id: 'inventory', title: 'Danh sách phòng và cơ sở vật chất', desc: 'None' }
  ],

  init() {
    this.cacheDOM();
    this.bindEvents();
    this.loadData();
  },

  cacheDOM() {
    this.el = {
      sidebarList: document.getElementById('sidebarList'),
      contentInner: document.getElementById('contentInner'),
      exportBtn: document.getElementById('exportBtn'),
      clearBtn: document.getElementById('clearBtn'),
      logoutBtn: document.getElementById('logoutAdmin'),
      viewer: document.getElementById('viewer'),
      viewerTitle: document.getElementById('viewerTitle'),
      viewerMainImg: document.getElementById('viewerMainImg'),
      viewerThumbs: document.getElementById('viewerThumbs'),
      viewerLinks: document.getElementById('viewerLinks'),
      viewerClose: document.getElementById('viewerClose')
    };
  },

  bindEvents() {
    this.el.exportBtn.addEventListener('click', () => this.exportData());
    this.el.clearBtn.addEventListener('click', () => this.clearCache());
    this.el.logoutBtn.addEventListener('click', () => location.href = '../index.html');
    this.el.viewerClose.addEventListener('click', () => this.hideViewer());
  },

  async loadData() {
    try {
      this.showLoading();
      const response = await fetch('../api/get-data.php');
      const result = await response.json();
      
      if (!result.success) throw new Error(result.error || 'Không thể tải dữ liệu');
      
      this.data = result;
      this.renderSidebar();
      this.renderView(this.currentView);
      
    } catch (error) {
      console.error('Load data error:', error);
      this.showError('Không thể kết nối database. Kiểm tra lại cấu hình.');
    }
  },

  async loadStudentsData() {
    try {
      const response = await fetch('../api/get-students.php');
      const result = await response.json();
      
      if (!result.success) throw new Error(result.error || 'Không thể tải dữ liệu sinh viên');
      
      return result.data;
      
    } catch (error) {
      console.error('Load students error:', error);
      throw error;
    }
  },

  showLoading() {
    this.el.contentInner.innerHTML = '<div class="loading">Đang tải dữ liệu...</div>';
  },

  showError(message) {
    this.el.contentInner.innerHTML = `<div class="error">${message}</div>`;
  },

  renderSidebar() {
    this.el.sidebarList.innerHTML = this.sidebar.map(item => `
      <div class="sidebar-item ${item.id === this.currentView ? 'active' : ''}" data-view="${item.id}">
        <div>
          <div class="item-title">${item.title}</div>
          <div class="item-desc">${item.desc}</div>
        </div>
      </div>
    `).join('');

    this.el.sidebarList.addEventListener('click', (e) => {
      const item = e.target.closest('.sidebar-item');
      if (!item) return;
      
      document.querySelectorAll('.sidebar-item').forEach(el => el.classList.remove('active'));
      item.classList.add('active');
      this.currentView = item.dataset.view;
      this.renderView(this.currentView);
    });
  },

  renderView(viewId) {
    const views = {
      students: () => this.renderStudentView(),
      inventory: () => this.renderInventoryView()
    };
    
    (views[viewId] || (() => this.el.contentInner.innerHTML = `<p>View "${viewId}" chưa có</p>`))();
  },

  // ============ STUDENT VIEW ============
  async renderStudentView() {
    this.showLoading();
    
    try {
      const students = await this.loadStudentsData();
      
      // Store for filtering and sorting
      this.studentsData = students;
      this.studentFilters = {
        room: '', name: '', mssv: '', contact: '', address: '', semester: ''
      };
      this.studentSort = { column: null, order: 'asc' };

      this.renderStudentTable(students);
      
    } catch (error) {
      this.showError('Không thể tải danh sách sinh viên. Vui lòng thử lại.');
    }
  },

  renderStudentTable(data) {
    const rows = data.map(s => `
      <tr>
        <td><span class="room-badge">${s.room}</span></td>
        <td>${s.name}</td>
        <td>${s.mssv}</td>
        <td>${s.contact}</td>
        <td>${s.address}</td>
        <td>${s.semester}</td>
      </tr>
    `).join('');

    this.el.contentInner.innerHTML = `
      <h2>Danh sách phòng & sinh viên</h2>
      <div class="student-table-wrapper">
        <table class="table student-table">
          <thead>
            <tr>
              <th style="width: 100px">
                <div class="th-content" data-col="room">
                  <span>Phòng</span>
                  <span class="sort-icon">⇅</span>
                </div>
                <input type="text" class="col-search" data-col="room" placeholder="Tìm phòng...">
              </th>
              <th>
                <div class="th-content" data-col="name">
                  <span>Họ tên sinh viên</span>
                  <span class="sort-icon">⇅</span>
                </div>
                <input type="text" class="col-search" data-col="name" placeholder="Tìm tên...">
              </th>
              <th style="width: 120px">
                <div class="th-content" data-col="mssv">
                  <span>MSSV</span>
                  <span class="sort-icon">⇅</span>
                </div>
                <input type="text" class="col-search" data-col="mssv" placeholder="Tìm MSSV...">
              </th>
              <th style="width: 130px">
                <div class="th-content" data-col="contact">
                  <span>Điện thoại</span>
                  <span class="sort-icon">⇅</span>
                </div>
                <input type="text" class="col-search" data-col="contact" placeholder="Tìm SĐT...">
              </th>
              <th>
                <div class="th-content" data-col="address">
                  <span>Địa chỉ</span>
                  <span class="sort-icon">⇅</span>
                </div>
                <input type="text" class="col-search" data-col="address" placeholder="Tìm địa chỉ...">
              </th>
              <th style="width: 120px">
                <div class="th-content" data-col="semester">
                  <span>Kỳ đăng ký</span>
                  <span class="sort-icon">⇅</span>
                </div>
                <input type="text" class="col-search" data-col="semester" placeholder="Tìm kỳ...">
              </th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="6" class="text-muted">Chưa có sinh viên nào</td></tr>'}</tbody>
        </table>
      </div>
      <div class="table-info">Tổng: ${data.length} sinh viên</div>
    `;

    // Bind events
    this.bindStudentTableEvents();
  },

  bindStudentTableEvents() {
    // Sort events
    document.querySelectorAll('.th-content').forEach(el => {
      el.addEventListener('click', () => {
        const col = el.dataset.col;
        if (this.studentSort.column === col) {
          this.studentSort.order = this.studentSort.order === 'asc' ? 'desc' : 'asc';
        } else {
          this.studentSort.column = col;
          this.studentSort.order = 'asc';
        }
        
        // Update sort icon
        document.querySelectorAll('.th-content').forEach(e => {
          e.classList.remove('sort-asc', 'sort-desc');
        });
        el.classList.add(`sort-${this.studentSort.order}`);
        
        this.filterAndSortStudents();
      });
    });

    // Filter events
    document.querySelectorAll('.col-search').forEach(input => {
      input.addEventListener('input', (e) => {
        this.studentFilters[e.target.dataset.col] = e.target.value.toLowerCase();
        this.filterAndSortStudents();
      });
    });
  },

  filterAndSortStudents() {
    let filtered = this.studentsData.filter(s => {
      return Object.keys(this.studentFilters).every(key => {
        const filterVal = this.studentFilters[key];
        if (!filterVal) return true;
        return String(s[key]).toLowerCase().includes(filterVal);
      });
    });

    // Sort
    if (this.studentSort.column) {
      filtered.sort((a, b) => {
        const aVal = String(a[this.studentSort.column]).toLowerCase();
        const bVal = String(b[this.studentSort.column]).toLowerCase();
        const comparison = aVal.localeCompare(bVal);
        return this.studentSort.order === 'asc' ? comparison : -comparison;
      });
    }

    this.renderStudentTable(filtered);
  },

  // ============ INVENTORY VIEW ============
  renderInventoryView() {
    if (!this.data) {
      this.showError('Không có dữ liệu. Vui lòng tải lại trang.');
      return;
    }

    // Lấy danh sách vật dụng unique
    const itemSet = new Set();
    this.data.rooms.forEach(r => Object.keys(r.items || {}).forEach(k => itemSet.add(k)));
    const items = Array.from(itemSet).sort();

    if (!items.length) {
      this.el.contentInner.innerHTML = '<p>Chưa có dữ liệu thiết bị nào.</p>';
      return;
    }

    const headerCells = this.data.rooms.map(r => 
      `<th><div>${r.number}</div><small style="font-weight:normal;opacity:0.7">${r.building || ''}</small></th>`
    ).join('');
    
    const rows = items.map(item => {
      const cells = this.data.rooms.map(room => {
        const count = room.items[item] || 0;
        return `<td><span class="qty-cell" data-room="${room.number}" data-item="${item}">${count}</span></td>`;
      }).join('');
      return `<tr><th>${item}</th>${cells}</tr>`;
    }).join('');

    this.el.contentInner.innerHTML = `
      <div class="matrix-table">
        <table>
          <thead>
            <tr><th>Vật dụng \\ Phòng</th>${headerCells}</tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `;

    // Bind hover events
    document.querySelectorAll('.qty-cell').forEach(cell => {
      cell.addEventListener('mouseenter', () => {
        this.showViewer(cell.dataset.room, cell.dataset.item);
      });
      cell.addEventListener('click', () => {
        this.showViewer(cell.dataset.room, cell.dataset.item);
      });
    });
  },

  // ============ VIEWER (for inventory) ============
  showViewer(roomNumber, itemName) {
    const room = this.data.rooms.find(r => r.number === roomNumber);
    if (!room) return;

    this.el.viewerTitle.textContent = `Phòng ${roomNumber} — ${itemName}`;

    // Lấy ảnh từ database hoặc fallback
    const images = room.images?.[itemName] || this.makePlaceholder(itemName);
    
    this.el.viewerMainImg.src = images[0];
    this.el.viewerMainImg.alt = `${itemName} - Phòng ${roomNumber}`;

    // Render thumbnails
    this.el.viewerThumbs.innerHTML = images.map((src, idx) => 
      `<img src="${src}" alt="${itemName} ${idx + 1}" onclick="Dashboard.el.viewerMainImg.src='${src}'">`
    ).join('');

    // Info section
    const count = room.items[itemName] || 0;
    this.el.viewerLinks.innerHTML = `
      <div>Số lượng: <strong>${count}</strong> ${itemName.toLowerCase()} trong phòng ${roomNumber}</div>
      <div style="margin-top:6px">
        <a href="#" onclick="return false;">Xem chi tiết & chỉnh sửa</a>
      </div>
    `;

    this.el.viewer.classList.add('open');
    this.el.viewer.setAttribute('aria-hidden', 'false');
  },

  hideViewer() {
    this.el.viewer.classList.remove('open');
    this.el.viewer.setAttribute('aria-hidden', 'true');
  },

  makePlaceholder(text) {
    const colors = [
      { bg: '#eef2ff', fg: '#0f172a' },
      { bg: '#fff7ed', fg: '#7c2d12' },
      { bg: '#ecfeff', fg: '#044e54' }
    ];
    
    return colors.map((c, i) => {
      const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='800' height='600'>
        <rect width='100%' height='100%' fill='${c.bg}'/>
        <text x='50%' y='50%' font-family='Arial' font-size='28' fill='${c.fg}' 
              text-anchor='middle' dominant-baseline='middle'>
          ${text} — ảnh ${i + 1}
        </text>
      </svg>`;
      return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
    });
  },

  // ============ UTILITY FUNCTIONS ============
  exportData() {
    const exportData = {
      students: this.studentsData || [],
      inventory: this.data || {}
    };
    
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ktx-data-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  },

  clearCache() {
    if (confirm('Xác nhận làm mới dữ liệu (reload từ database)?')) {
      this.loadData();
    }
  }
};

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => Dashboard.init());
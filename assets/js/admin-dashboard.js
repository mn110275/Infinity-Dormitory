/* admin-dashboard.js
   - Render sidebar, student list view, equipment matrix
   - Hover/click trên ô số lượng sẽ mở viewer (tab gạt sang) bên phải
   - Dễ mở rộng: thêm mục mới bằng renderSidebar() -> add item renderer
*/

document.addEventListener('DOMContentLoaded', () => {
  // DOM refs
  const sidebarList = document.getElementById('sidebarList');
  const contentInner = document.getElementById('contentInner');
  const exportBtn = document.getElementById('exportBtn');
  const clearBtn = document.getElementById('clearBtn');
  const logoutBtn = document.getElementById('logoutAdmin');

  const viewer = document.getElementById('viewer');
  const viewerTitle = document.getElementById('viewerTitle');
  const viewerMainImg = document.getElementById('viewerMainImg');
  const viewerThumbs = document.getElementById('viewerThumbs');
  const viewerLinks = document.getElementById('viewerLinks');
  const viewerClose = document.getElementById('viewerClose');

  // Load or create sample data
  const STORAGE_KEY = 'ktxData_v1';
  let ktxData = loadOrCreateSample();

  // Sidebar items definition (extend later)
  const sidebarItems = [
    {
      id: 'students',
      title: 'Danh sách phòng + sinh viên',
      desc: 'Xem danh sách từng phòng và sinh viên'
    },
    {
      id: 'inventory',
      title: 'Danh sách phòng + cơ sở vật chất',
      desc: 'Ma trận phòng × đồ dùng (hover để xem ảnh)'
    }
  ];

  renderSidebar();
  // default view
  renderView('students');

  // ---------- Export / Clear / Logout ----------
  exportBtn.addEventListener('click', () => {
    const blob = new Blob([JSON.stringify(ktxData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'ktx-data.json'; document.body.appendChild(a);
    a.click(); a.remove();
    URL.revokeObjectURL(url);
  });

  clearBtn.addEventListener('click', () => {
    if (!confirm('Xác nhận xóa toàn bộ dữ liệu local (không thể hoàn tác)?')) return;
    localStorage.removeItem(STORAGE_KEY);
    location.reload();
  });

  logoutBtn.addEventListener('click', () => {
    // nếu có auth thực sự thì redirect hoặc gọi API logout
    location.href = '../index.html';
  });

  viewerClose.addEventListener('click', hideViewer);

  // ---------- Functions ----------

  function renderSidebar() {
    sidebarList.innerHTML = '';
    sidebarItems.forEach(it => {
      const el = document.createElement('div');
      el.className = 'sidebar-item';
      el.dataset.action = it.id;
      el.innerHTML = `<div>
                        <div class="item-title">${it.title}</div>
                        <div class="item-desc">${it.desc}</div>
                      </div>`;
      el.addEventListener('click', () => {
        // active class
        [...sidebarList.querySelectorAll('.sidebar-item')].forEach(x => x.classList.remove('active'));
        el.classList.add('active');
        renderView(it.id);
      });
      sidebarList.appendChild(el);
    });
    // set first active
    const first = sidebarList.querySelector('.sidebar-item');
    if (first) first.classList.add('active');
  }

  function renderView(id) {
    if (id === 'students') return renderStudentView();
    if (id === 'inventory') return renderInventoryView();
    contentInner.innerHTML = `<p>Chưa có view cho "${id}"</p>`;
  }

  function renderStudentView() {
    contentInner.innerHTML = '';
    const title = document.createElement('h2'); title.textContent = 'Danh sách phòng & sinh viên';
    contentInner.appendChild(title);

    const table = document.createElement('table'); table.className = 'table';
    const thead = document.createElement('thead');
    thead.innerHTML = `<tr><th>Phòng</th><th>Danh sách sinh viên</th></tr>`;
    table.appendChild(thead);
    const tbody = document.createElement('tbody');

    ktxData.rooms.forEach(room => {
      const tr = document.createElement('tr');
      const tdRoom = document.createElement('td');
      tdRoom.innerHTML = `<span class="room-badge">Phòng ${room.number}</span>`;
      const tdList = document.createElement('td');
      if (room.students && room.students.length) {
        const ul = document.createElement('ul');
        room.students.forEach(s => {
          const li = document.createElement('li'); li.textContent = s;
          ul.appendChild(li);
        });
        tdList.appendChild(ul);
      } else {
        tdList.textContent = '(chưa có sinh viên)';
      }
      tr.appendChild(tdRoom); tr.appendChild(tdList);
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    contentInner.appendChild(table);
  }

  function renderInventoryView() {
    contentInner.innerHTML = '';
    const title = document.createElement('h2'); title.textContent = 'Ma trận: Phòng × Vật dụng';
    contentInner.appendChild(title);

    // compute unique item types
    const itemSet = new Set();
    ktxData.rooms.forEach(r => {
      const keys = r.items ? Object.keys(r.items) : [];
      keys.forEach(k => itemSet.add(k));
    });
    const items = Array.from(itemSet);
    if (!items.length) {
      contentInner.appendChild(document.createTextNode('Chưa có dữ liệu đồ dùng nào.'));
      return;
    }

    const wrap = document.createElement('div'); wrap.className = 'matrix-table';
    const table = document.createElement('table');
    // header: first empty then each room
    const thead = document.createElement('thead'); const htr = document.createElement('tr');
    htr.innerHTML = `<th>Vật dụng \\ Phòng</th>` + ktxData.rooms.map(r => `<th>Phòng ${r.number}</th>`).join('');
    thead.appendChild(htr); table.appendChild(thead);

    const tbody = document.createElement('tbody');
    items.forEach(item => {
      const tr = document.createElement('tr');
      const th = document.createElement('th'); th.textContent = item; tr.appendChild(th);
      ktxData.rooms.forEach(room => {
        const td = document.createElement('td');
        const count = (room.items && room.items[item]) ? room.items[item] : 0;
        const span = document.createElement('span');
        span.className = 'qty-cell';
        span.textContent = count;
        span.dataset.room = room.number;
        span.dataset.item = item;
        // hover & click events to open viewer
        span.addEventListener('mouseenter', () => { showViewer(room.number, item); });
        span.addEventListener('click', () => { showViewer(room.number, item); });
        td.appendChild(span);
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    wrap.appendChild(table);
    contentInner.appendChild(wrap);
  }

  function showViewer(roomNumber, itemName) {
    const room = ktxData.rooms.find(r => r.number === roomNumber);
    viewerTitle.textContent = `Phòng ${roomNumber} — ${itemName}`;

    // find images: room-specific first, else global sample
    let imgs = (room && room.images && room.images[itemName]) ? room.images[itemName] : null;
    if (!imgs || !imgs.length) {
      // fallback sample images for item
      imgs = makeSampleImages(itemName);
    }

    // set main image
    viewerMainImg.src = imgs[0];
    viewerMainImg.alt = `${itemName} - Phòng ${roomNumber}`;

    // thumbnails
    viewerThumbs.innerHTML = '';
    imgs.forEach((src, idx) => {
      const img = document.createElement('img');
      img.src = src;
      img.alt = `${itemName} ${idx+1}`;
      img.addEventListener('click', () => { viewerMainImg.src = src; });
      viewerThumbs.appendChild(img);
    });

    // optional link / info (placeholder)
    viewerLinks.innerHTML = `<div>Thông tin: <strong>${(room && room.items && room.items[itemName]) || 0}</strong> cái trong phòng ${roomNumber}.</div>
      <div style="margin-top:6px"><a href="#" onclick="return false;">Xem chi tiết & chỉnh sửa</a></div>`;

    // open viewer
    viewer.classList.add('open');
    viewer.setAttribute('aria-hidden', 'false');
  }

  function hideViewer() {
    viewer.classList.remove('open');
    viewer.setAttribute('aria-hidden', 'true');
  }

  // ---------- Helper / sample data ----------
  function loadOrCreateSample() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      try { return JSON.parse(raw); } catch(e) { console.warn('Dữ liệu localStorage lỗi, tạo sample mới'); }
    }

    // sample data: 3 phòng, students, items, images (empty => use placeholder)
    const sample = {
      createdAt: new Date().toISOString(),
      rooms: [
        {
          number: '101',
          students: ['Nguyễn Văn A','Trần Thị B','Lê Văn C','Phạm Minh D','Hoàng Thị E','Ngô Văn F','Lâm G','Vũ H'],
          items: { 'Giường': 4, 'Tủ': 4, 'Bàn học': 4, 'Ghế': 4 },
          images: {
            // if you later upload real images, set here: 'Giường': ['url1','url2']
          }
        },
        {
          number: '102',
          students: ['Bùi A','Đỗ B','Mai C'],
          items: { 'Giường': 3, 'Tủ': 2, 'Bàn học': 3, 'Ghế': 3 },
          images: {}
        },
        {
          number: '103',
          students: ['Trương A','Phan B','Võ C','Dương D'],
          items: { 'Giường': 4, 'Tủ': 3, 'Bàn học': 4, 'Ghế': 4, 'Quạt': 2 },
          images: {}
        }
      ]
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(sample));
    return sample;
  }

  // Make simple SVG data-url placeholder images for demo
  function svgDataUrl(text, w=800, h=600, bg='#eef2ff', fg='#0f172a') {
    const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='${w}' height='${h}'>
      <rect width='100%' height='100%' fill='${bg}'/>
      <text x='50%' y='50%' font-family='Arial' font-size='28' fill='${fg}' text-anchor='middle' dominant-baseline='middle'>
        ${escapeHtml(text)}
      </text>
    </svg>`;
    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
  }
  function makeSampleImages(itemName) {
    return [
      svgDataUrl(itemName + ' — ảnh 1'),
      svgDataUrl(itemName + ' — ảnh 2', 700, 500, '#fff7ed', '#7c2d12'),
      svgDataUrl(itemName + ' — ảnh 3', 700, 500, '#ecfeff', '#044e54')
    ];
  }
  function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[c]); }

});

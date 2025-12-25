// manager/facility.js

const Facility = {
  viewer: null,
  data: null,

  init() {
    this.data = window.INVENTORY_DATA || [];
    this.viewer = {
      el: document.getElementById('viewer'),
      title: document.getElementById('viewerTitle'),
      mainImg: document.getElementById('viewerMainImg'),
      thumbs: document.getElementById('viewerThumbs'),
      links: document.getElementById('viewerLinks'),
      closeBtn: document.getElementById('viewerClose')
    };

    this.bindEvents();
  },

  bindEvents() {
    if (this.viewer.closeBtn) {
      this.viewer.closeBtn.addEventListener('click', () => this.hideViewer());
    }

    if (this.viewer.el) {
      this.viewer.el.addEventListener('click', (e) => {
        if (e.target === this.viewer.el) this.hideViewer();
      });
    }

    document.querySelectorAll('.qty-cell').forEach(cell => {
      cell.addEventListener('click', () => {
        const roomId = cell.dataset.roomId;
        const itemName = cell.dataset.item;
        this.showViewer(roomId, itemName);
      });
    });
  },

  showViewer(roomId, itemName) {
    const room = this.data.find(r => r.id == roomId);
    if (!room) return;

    this.viewer.title.textContent = `Phòng ${room.number} — ${itemName}`;

    const images = room.images?.[itemName] || this.makePlaceholder(itemName);
    const count = room.items[itemName] || 0;

    this.viewer.mainImg.src = images[0];
    this.viewer.mainImg.alt = `${itemName} - Phòng ${room.number}`;

    this.viewer.thumbs.innerHTML = images.map((src, idx) => 
      `<img src="${src}" alt="${itemName} ${idx + 1}" 
            onclick="document.getElementById('viewerMainImg').src='${src}'">`
    ).join('');

    this.viewer.links.innerHTML = `
      <div><strong>Số lượng:</strong> ${count} ${itemName.toLowerCase()}</div>
      <div><strong>Phòng:</strong> ${room.number} (${room.building})</div>
      <div style="margin-top: 12px">
        <a href="#" onclick="return false;">Xem chi tiết & chỉnh sửa</a>
      </div>
    `;

    this.viewer.el.classList.add('open');
    this.viewer.el.setAttribute('aria-hidden', 'false');
  },

  hideViewer() {
    this.viewer.el.classList.remove('open');
    this.viewer.el.setAttribute('aria-hidden', 'true');
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
  }
};

document.addEventListener('DOMContentLoaded', () => {
  Facility.init();

  document.querySelectorAll('.sidebar-item').forEach(item => {
    item.addEventListener('click', () => {
      const view = item.dataset.view;

      document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');

      document.getElementById('studentView').style.display = view === 'students' ? 'block' : 'none';
      document.getElementById('facilityView').style.display = view === 'facility' ? 'block' : 'none';
    });
  });

  document.getElementById('exportBtn')?.addEventListener('click', () => {
    const data = {
      timestamp: new Date().toISOString(),
      students: Array.from(document.querySelectorAll('#studentTable tbody tr:not([style*="display: none"])')).map(row => {
        const cells = row.querySelectorAll('td');
        return {
          phong: cells[0]?.textContent.trim(),
          ten: cells[1]?.textContent.trim(),
          mssv: cells[2]?.textContent.trim(),
          sdt: cells[3]?.textContent.trim(),
          diachi: cells[4]?.textContent.trim(),
          ky: cells[5]?.textContent.trim()
        };
      }),
      facility: window.INVENTORY_DATA
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ktx-data-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  });

  document.getElementById('clearBtn')?.addEventListener('click', () => {
    if (confirm('Làm mới dữ liệu (reload trang)?')) {
      location.reload();
    }
  });

  document.getElementById('logoutAdmin')?.addEventListener('click', () => {
    location.href = '../index.html';
  });
});
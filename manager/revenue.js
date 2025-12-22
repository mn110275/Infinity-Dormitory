// manager/revenue.js

const RevenueManager = {
  data: null,
  currentMode: null, // 'view' or 'create'
  currentPeriod: null,
  revenueData: {},

  init() {
    this.data = window.REVENUE_DATA;
    this.bindEvents();
  },

  bindEvents() {
    // Search existing
    document.getElementById('btnSearch')?.addEventListener('click', () => {
      const month = document.getElementById('searchMonth').value;
      const year = document.getElementById('searchYear').value;
      this.loadExistingRevenue(year, month);
    });

    // Create new
    document.getElementById('btnCreateNew')?.addEventListener('click', () => {
      const month = document.getElementById('newMonth').value;
      const year = document.getElementById('newYear').value;
      this.createNewRevenue(year, month);
    });
  },

  async loadExistingRevenue(year, month) {
    try {
      const response = await fetch(`revenue_api.php?action=load&year=${year}&month=${month}&block=${this.data.block}`);
      const result = await response.json();

      if (result.success) {
        this.currentMode = 'view';
        this.currentPeriod = { year, month };
        this.revenueData = result.data;
        this.renderTable(false); // Read-only mode
      } else {
        alert(result.error || 'Không tìm thấy hóa đơn cho tháng này');
        this.showEmptyState();
      }
    } catch (error) {
      console.error('Load error:', error);
      alert('Lỗi khi tải dữ liệu');
    }
  },

  createNewRevenue(year, month) {
    // Check if already exists
    if (confirm(`Tạo hóa đơn mới cho tháng ${month}/${year}?`)) {
      this.currentMode = 'create';
      this.currentPeriod = { year, month };
      this.revenueData = {};
      
      // Initialize empty data
      this.data.rooms.forEach(roomId => {
        this.revenueData[roomId] = {
          elec: 0,
          water: 0,
          other: 0,
          note: ''
        };
      });
      
      this.renderTable(true); // Editable mode
    }
  },

  renderTable(editable) {
    const container = document.getElementById('revenueTableContainer');
    const { year, month } = this.currentPeriod;
    const elecPrice = this.data.unitPrices?.ELEC || 0;
    const waterPrice = this.data.unitPrices?.WATER || 0;

    let html = `
      <div class="revenue-table-wrapper">
        <h3 style="padding: 16px;">Hóa đơn tháng ${month}/${year}</h3>
        <table class="revenue-table">
          <thead>
            <tr>
              <th>Phòng</th>
              <th>Điện (kWh)<br><small>${elecPrice.toLocaleString()} VNĐ/kWh</small></th>
              <th>Nước (m³)<br><small>${waterPrice.toLocaleString()} VNĐ/m³</small></th>
              <th>Tổng Điện (VNĐ)</th>
              <th>Tổng Nước (VNĐ)</th>
              <th>Chi phí khác (VNĐ)</th>
              <th>Ghi chú</th>
              <th>TỔNG (VNĐ)</th>
            </tr>
          </thead>
          <tbody>
    `;

    this.data.rooms.forEach(roomId => {
      const data = this.revenueData[roomId] || { elec: 0, water: 0, other: 0, note: '' };
      const elecTotal = data.elec * elecPrice;
      const waterTotal = data.water * waterPrice;
      const total = elecTotal + waterTotal + (data.other || 0);

      html += `
        <tr>
          <th>${roomId}</th>
          <td>
            ${editable 
              ? `<input type="number" class="elec-input" data-room="${roomId}" value="${data.elec}" min="0" step="1">`
              : data.elec
            }
          </td>
          <td>
            ${editable
              ? `<input type="number" class="water-input" data-room="${roomId}" value="${data.water}" min="0" step="1">`
              : data.water
            }
          </td>
          <td class="calculated">${elecTotal.toLocaleString()}</td>
          <td class="calculated">${waterTotal.toLocaleString()}</td>
          <td>
            ${editable
              ? `<input type="number" class="other-input" data-room="${roomId}" value="${data.other || 0}" min="0" step="1000">`
              : (data.other || 0).toLocaleString()
            }
          </td>
          <td>
            ${editable
              ? `<input type="text" class="note-input" data-room="${roomId}" value="${data.note || ''}" placeholder="Ghi chú...">`
              : data.note || '-'
            }
          </td>
          <td class="calculated"><strong>${total.toLocaleString()}</strong></td>
        </tr>
      `;
    });

    html += `
          </tbody>
        </table>
      </div>
    `;

    if (editable) {
      html += `
        <div class="revenue-actions">
          <button id="btnSaveDraft" class="btn btn-warning">Lưu tạm</button>
          <button id="btnSaveAndNotify" class="btn btn-success">Lưu & Gửi thông báo</button>
        </div>
      `;
    }

    container.innerHTML = html;

    if (editable) {
      this.bindTableEvents();
    }
  },

  bindTableEvents() {
    // Auto-calculate on input change
    const inputs = document.querySelectorAll('.elec-input, .water-input, .other-input, .note-input');
    inputs.forEach(input => {
      input.addEventListener('input', (e) => {
        const roomId = e.target.dataset.room;
        const type = e.target.className.split('-')[0];
        
        if (!this.revenueData[roomId]) {
          this.revenueData[roomId] = { elec: 0, water: 0, other: 0, note: '' };
        }
        
        if (type === 'note') {
          this.revenueData[roomId].note = e.target.value;
        } else {
          this.revenueData[roomId][type] = parseFloat(e.target.value) || 0;
        }
        
        this.updateRowCalculation(roomId);
      });
    });

    // Save buttons
    document.getElementById('btnSaveDraft')?.addEventListener('click', () => {
      this.saveRevenue(false);
    });

    document.getElementById('btnSaveAndNotify')?.addEventListener('click', () => {
      this.saveRevenue(true);
    });
  },

  updateRowCalculation(roomId) {
    const row = document.querySelector(`input[data-room="${roomId}"]`).closest('tr');
    const data = this.revenueData[roomId];
    const elecPrice = this.data.unitPrices?.ELEC || 0;
    const waterPrice = this.data.unitPrices?.WATER || 0;

    const elecTotal = data.elec * elecPrice;
    const waterTotal = data.water * waterPrice;
    const total = elecTotal + waterTotal + (data.other || 0);

    const cells = row.querySelectorAll('td');
    cells[2].textContent = elecTotal.toLocaleString();
    cells[3].textContent = waterTotal.toLocaleString();
    cells[6].innerHTML = `<strong>${total.toLocaleString()}</strong>`;
  },

  async saveRevenue(sendNotification) {
    if (!confirm(sendNotification 
      ? 'Xác nhận lưu và gửi thông báo đến toàn bộ sinh viên?' 
      : 'Lưu tạm thời hóa đơn?')) {
      return;
    }

    const payload = {
      action: 'save',
      year: this.currentPeriod.year,
      month: this.currentPeriod.month,
      block: this.data.block,
      data: this.revenueData,
      sendNotification
    };

    try {
      const response = await fetch('revenue_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (result.success) {
        alert(sendNotification 
          ? 'Đã lưu và gửi thông báo thành công!' 
          : 'Đã lưu tạm hóa đơn');
        this.currentMode = 'view';
        this.renderTable(false);
      } else {
        alert('Lỗi: ' + (result.error || 'Không thể lưu'));
      }
    } catch (error) {
      console.error('Save error:', error);
      alert('Lỗi khi lưu dữ liệu');
    }
  },

  showEmptyState() {
    document.getElementById('revenueTableContainer').innerHTML = `
      <div class="empty-state">
        <p>Không tìm thấy dữ liệu. Vui lòng thử tháng khác hoặc tạo hóa đơn mới.</p>
      </div>
    `;
  }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => RevenueManager.init());
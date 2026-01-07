// manager/js/revenue.js

const RevenueManager = {
  data: null,
  currentMode: null,
  currentPeriod: null,
  revenueData:
  {},

  init()
  {
    this.data = window.REVENUE_DATA;
    this.bindEvents();
  },

  bindEvents()
  {
    document.getElementById('btnSearch')?.addEventListener('click', () =>
    {
      const month = document.getElementById('searchMonth').value;
      const year = document.getElementById('searchYear').value;
      this.loadExistingRevenue(year, month);
    });

    document.getElementById('btnCreateNew')?.addEventListener('click', () =>
    {
      const month = document.getElementById('newMonth').value;
      const year = document.getElementById('newYear').value;
      this.createNewRevenue(year, month);
    });
  },

  async loadExistingRevenue(year, month)
  {
    try
    {
      const response = await fetch(`api/revenue_api.php?year=${year}&month=${month}&block=${this.data.block}`);
      const result = await response.json();

      if (result.success)
      {
        this.currentPeriod = {
          year,
          month
        };
        this.revenueData = result.data;
        this.data.unitPrices = {
          ELEC: result.unitPrices.elec,
          WATER: result.unitPrices.water
        };

        this.currentMode = 'view';
        this.renderTable(false);
      }
      else
      {
        alert(result.error);
        this.showEmptyState();
      }
    }
    catch (error)
    {
      alert('Lỗi kết nối server');
    }
  },

  async createNewRevenue(year, month) {
    try {
      const response = await fetch(`api/revenue_api.php?year=${year}&month=${month}&block=${this.data.block}`);
      const result = await response.json();

      if (!result.unitPrices || !result.unitPrices.elec || !result.unitPrices.water) {
        alert(`Tháng ${month}/${year} chưa được Admin cập nhật đơn giá.\nVui lòng liên hệ Admin trước khi tạo hóa đơn.`);
        return;
      }

      // Nếu đã có đơn giá thì tạo
      if (confirm(`Tạo mới/cập nhật hóa đơn cho tháng ${month}/${year}?`)) {
        this.currentPeriod = { year, month };
        this.currentMode = 'create';
        this.data.unitPrices = {
          ELEC: result.unitPrices.elec,
          WATER: result.unitPrices.water
        };

        if (result.success && Object.keys(result.data).length > 0) {
          this.revenueData = result.data;
        } else {
          this.revenueData = {};
          this.data.rooms.forEach(roomId => {
            this.revenueData[roomId] = { elec: 0, water: 0, other: 0, note: '' };
          });
        }

        this.renderTable(true);
      }
    } catch (error) {
      console.error(error);
      alert('Lỗi kết nối server hoặc lỗi hệ thống.');
    }
  },

  renderTable(editable)
  {
    const container = document.getElementById('revenueTableContainer');
    const
    {
      year,
      month
    } = this.currentPeriod;
    const elecPrice = this.data.unitPrices?.ELEC || 0;
    const waterPrice = this.data.unitPrices?.WATER || 0;

    let html = `
      <div class="revenue-header-actions">
          <h3>Hóa đơn tháng ${month}/${year}</h3>
          ${!editable ? `<button id="btnEditMode" class="btn btn-primary">Chỉnh sửa hóa đơn</button>` 
                : `<button id="btnSaveDraft" class="btn btn-secondary">Lưu tạm</button>
                   <button id="btnSaveAndNotify" class="btn btn-success">Lưu & Gửi thông báo</button>`
          }
      </div>
      <table class="revenue-table">
          <thead>
              <tr>
                  <th style="width:50px">Phòng</th>
                  <th style="width:80px"> Điện (kWh)</th>
                  <th style="width:80px">Nước (m³)</th>
                  <th style="width:140px">Tiền Điện</th>
                  <th style="width:140px">Tiền Nước</th>
                  <th style="width:120px">Phí khác</th>
                  <th>Ghi chú</th>
                  <th>TỔNG</th>
              </tr>
          </thead>
          <tbody>
    `;

    this.data.rooms.forEach(roomId =>
    {
      const rowData = this.revenueData[roomId] ||
      {
        elec: 0,
        water: 0,
        other: 0,
        note: ''
      };
      const elecTotal = rowData.elec * elecPrice;
      const waterTotal = rowData.water * waterPrice;
      const total = elecTotal + waterTotal + (rowData.other || 0);

      html += `
        <tr data-room-id="${roomId}">
            <td><strong>${roomId}</strong></td>
            <td>${editable ? `<input type="number" class="elec-input" style="width: 60px" data-room="${roomId}" value="${rowData.elec}">` : rowData.elec}</td>
            <td>${editable ? `<input type="number" class="water-input" style="width: 60px" data-room="${roomId}" value="${rowData.water}">` : rowData.water}</td>
            <td class="cell-elec-total">${elecTotal.toLocaleString()}</td>
            <td class="cell-water-total">${waterTotal.toLocaleString()}</td>
            <td>${editable ? `<input type="number" class="other-input" style="width: 100px" data-room="${roomId}" value="${rowData.other}">` : rowData.other.toLocaleString()}</td>
            <td>${editable ? `<input type="text" placeholder="Nhập ghi chú" class="note-input" style="width: 300px" data-room="${roomId}" value="${rowData.note}">` : (rowData.note || '-')}</td>
            <td class="cell-row-total"><strong>${total.toLocaleString()}</strong></td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    if (editable)
    {
      html += `
        <div class="revenue-actions">
            <button id="btnSaveDraft" class="btn btn-secondary">Lưu</button>
            <button id="btnSaveAndNotify" class="btn btn-success">Lưu & Gửi thông báo</button>
        </div>
      `;
    }
    container.innerHTML = html;

    document.getElementById('btnEditMode')?.addEventListener('click', () =>
    {
      this.currentMode = 'edit';
      this.renderTable(true);
    });

    if (editable) this.bindTableEvents();
  },

  bindTableEvents()
  {
    const inputs = document.querySelectorAll('.elec-input, .water-input, .other-input, .note-input');
    inputs.forEach(input =>
    {
      input.addEventListener('input', (e) =>
      {
        const roomId = e.target.dataset.room;
        const type = e.target.className.split('-')[0];

        if (!this.revenueData[roomId])
        {
          this.revenueData[roomId] = {
            elec: 0,
            water: 0,
            other: 0,
            note: ''
          };
        }

        if (type === 'note')
        {
          this.revenueData[roomId].note = e.target.value;
        }
        else
        {
          this.revenueData[roomId][type] = parseFloat(e.target.value) || 0;
        }

        this.updateRowCalculation(roomId);
      });
    });

    document.getElementById('btnSaveDraft')?.addEventListener('click', () =>
    {
      this.saveRevenue(false);
    });

    document.getElementById('btnSaveAndNotify')?.addEventListener('click', () =>
    {
      this.saveRevenue(true);
    });
  },

  updateRowCalculation(roomId)
  {
    const row = document.querySelector(`tr[data-room-id="${roomId}"]`);
    const data = this.revenueData[roomId];
    const elecPrice = this.data.unitPrices?.ELEC || 0;
    const waterPrice = this.data.unitPrices?.WATER || 0;

    const elecTotal = data.elec * elecPrice;
    const waterTotal = data.water * waterPrice;
    const total = elecTotal + waterTotal + (data.other || 0);

    row.querySelector('.cell-elec-total').textContent = elecTotal.toLocaleString();
    row.querySelector('.cell-water-total').textContent = waterTotal.toLocaleString();
    row.querySelector('.cell-row-total').innerHTML = `<strong>${total.toLocaleString()}</strong>`;
  },

  async saveRevenue(sendNotification)
  {
    if (!confirm(sendNotification ?
        'Xác nhận lưu và gửi thông báo đến toàn bộ sinh viên?' :
        'Lưu tạm thời hóa đơn?'))
    {
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

    try
    {
      const response = await fetch('api/revenue_api.php',
      {
        method: 'POST',
        headers:
        {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (result.success)
      {
        alert(sendNotification ?
          'Đã lưu và gửi thông báo thành công!' :
          'Đã lưu tạm hóa đơn');
        this.currentMode = 'view';
        this.renderTable(false);
      }
      else
      {
        alert('Lỗi: ' + (result.error || 'Không thể lưu'));
      }
    }
    catch (error)
    {
      console.error('Save error:', error);
      alert('Lỗi khi lưu dữ liệu');
    }
  },

  showEmptyState()
  {
    document.getElementById('revenueTableContainer').innerHTML = `
      <div class="empty-state">
        <p>Không tìm thấy dữ liệu. Vui lòng thử tháng khác hoặc tạo hóa đơn mới.</p>
      </div>
    `;
  }
};

document.addEventListener('DOMContentLoaded', () => RevenueManager.init());
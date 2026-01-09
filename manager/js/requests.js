// manager/requests.js

const RequestManager = {
  init() {
    this.bindSearch();
    this.updateCounter();
  },

  bindSearch() {
    document.querySelectorAll('.col-search').forEach(input => {
      input.addEventListener('input', () => this.filterTable());
    });
  },

  toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('.req-checkbox');
    checkboxes.forEach(cb => {
      if (cb.closest('tr').style.display !== 'none') {
        cb.checked = source.checked;
      }
    });
    this.updateCounter();
  },

  updateCounter() {
    const total = document.querySelectorAll('.req-checkbox').length;
    const selected = document.querySelectorAll('.req-checkbox:checked').length;
    const counter = document.getElementById('statCounter');
    const saveBtn = document.getElementById('saveBatchBtn');

    counter.innerText = `Đã giải quyết: ${selected} / ${total} yêu cầu tồn đọng`;

    saveBtn.disabled = selected === 0;
    saveBtn.style.opacity = selected === 0 ? '0.5' : '1';
  },

  async resolveBatch() {
    const selectedCbs = document.querySelectorAll('.req-checkbox:checked');
    const ids = Array.from(selectedCbs).map(cb => cb.value);

    if (!confirm(`Xác nhận hoàn thành ${ids.length} yêu cầu đã chọn?`)) return;

    try {
      const response = await fetch('api/requests_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'resolve_batch',
          pr_ids: ids
        })
      });

      const data = await response.json();
      if (data.success) {
        ids.forEach(id => {
          const row = document.getElementById(`row-${id}`);
          if (row) row.remove();
        });
        this.updateCounter();
        document.getElementById('selectAll').checked = false;
      } else {
        alert(data.error);
      }
    } catch (err) {
      alert('Lỗi kết nối máy chủ');
    }
  },

  filterTable() {
    const rows = document.querySelectorAll("#requestTable tbody tr");
    const inputs = document.querySelectorAll(".col-search");

    rows.forEach(row => {
      let visible = true;
      inputs.forEach(input => {
        const colIndex = input.getAttribute('data-col');
        const text = row.cells[colIndex].textContent.toLowerCase();
        const val = input.value.toLowerCase();
        if (!text.includes(val)) visible = false;
      });
      row.style.display = visible ? "" : "none";
    });
  }
};

document.addEventListener('DOMContentLoaded', () => RequestManager.init());
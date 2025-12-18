// manager/student_list.js

const StudentList = {
  table: null,
  rows: [],
  sortColumn: null,
  sortOrder: 'asc',

  init() {
    this.table = document.getElementById('studentTable');
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

    this.table.querySelectorAll('.th-content').forEach(e => {
      e.classList.remove('sort-asc', 'sort-desc');
    });
    headerEl.classList.add(`sort-${this.sortOrder}`);

    this.rows.sort((a, b) => {
      const aText = a.children[colIndex]?.textContent.trim().toLowerCase() || '';
      const bText = b.children[colIndex]?.textContent.trim().toLowerCase() || '';
      const comparison = aText.localeCompare(bText, 'vi');
      return this.sortOrder === 'asc' ? comparison : -comparison;
    });

    const tbody = this.table.querySelector('tbody');
    this.rows.forEach(row => tbody.appendChild(row));
    
    this.filter();
  },

  filter() {
    const filters = Array.from(this.table.querySelectorAll('.col-search')).map(input => 
      input.value.toLowerCase()
    );

    let visibleCount = 0;
    this.rows.forEach(row => {
      const cells = Array.from(row.children);
      const matches = filters.every((filter, i) => {
        if (!filter) return true;
        const cellText = cells[i]?.textContent.trim().toLowerCase() || '';
        return cellText.includes(filter);
      });

      row.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    const infoEl = document.querySelector('.table-info');
    if (infoEl) {
      const total = this.rows.length;
      infoEl.textContent = visibleCount === total 
        ? `Tổng: ${total} sinh viên`
        : `Hiển thị: ${visibleCount} / ${total} sinh viên`;
    }
  }
};

// Auto init
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => StudentList.init());
} else {
  StudentList.init();
}
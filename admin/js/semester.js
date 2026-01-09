const SemesterAdmin = {
    async startRollover(e) {
        e.preventDefault();
        const name = document.getElementById('new_sem_name').value;
        const start = document.getElementById('new_sem_start').value;
        const end = document.getElementById('new_sem_end').value;

        if (new Date(end) <= new Date(start)) {
            alert("Ngày kết thúc phải sau ngày bắt đầu!");
            return;
        }

        if(!confirm(`Xác nhận lập lịch cho kỳ ${name}?\n\n- Bắt đầu: ${start}\n- Kết thúc: ${end}\n\nHệ thống sẽ tự động kích hoạt vào đúng 00:00 ngày ${start}.`)) return;

        this.sendAction('semester_rollover', { name, start, end });
    },

    async editUpcoming(semId, currentEnd) {
        const newEnd = prompt(`Sửa ngày kết thúc cho kỳ ${semId} (YYYY-MM-DD):`, currentEnd);
        
        if (!newEnd || newEnd === currentEnd) return;

        if(!confirm(`Xác nhận đổi ngày kết thúc kỳ ${semId} thành ${newEnd}?`)) return;

        this.sendAction('edit_upcoming', { 
            sem_id: semId, 
            new_end: newEnd 
        });
    },

    async deleteUpcoming(semId) {
        const confirmFirst = confirm(`CẢNH BÁO:\n\nBạn đang thực hiện HỦY LỊCH học kỳ: ${semId}.`);
        if (!confirmFirst) return;

        const confirmSecond = confirm(`LƯU Ý:\nNếu bạn đã thực hiện gia hạn cho sinh viên vào kỳ ${semId}, toàn bộ danh sách gia hạn đó SẼ BỊ XÓA VĨNH VIỄN.\n\nBạn vẫn muốn tiếp tục chứ?`);
        if (!confirmSecond) return;

        this.sendAction('delete_upcoming', { sem_id: semId });
    },

    async sendAction(action, data) {
        try {
            const res = await fetch('actions/semester_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action, ...data })
            });
            const result = await res.json();
            if(result.status === 'success') {
                location.reload();
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (err) {
            alert('Lỗi kết nối máy chủ.');
        }
    },

    async loadHistory(semId) {
        if(!semId) return;
        const container = document.getElementById('historyContainer');
        container.innerHTML = '<div style="text-align:center; padding:50px;"><i class="fas fa-circle-notch fa-spin"></i> Đang tải dữ liệu...</div>';
        
        document.getElementById('btnExportExcel').style.display = 'block';

        try {
            const res = await fetch(`actions/semester_action.php?action=get_history&sem_id=${encodeURIComponent(semId)}`);
            const html = await res.text();
            container.innerHTML = html;
        } catch (err) {
            container.innerHTML = '<div style="color:red; text-align:center; padding:20px;">Lỗi tải dữ liệu.</div>';
        }
    },

    exportToExcel: function() {
        const table = document.querySelector("#historyContainer table");
        if (!table) {
            alert("Không có dữ liệu để xuất!");
            return;
        }

        const semSelect = document.querySelector(".select-sem");
        let semName = semSelect.options[semSelect.selectedIndex].text;

        semName = semName.replace(/\s*\(.*?\)\s*/g, '').trim();

        const wb = XLSX.utils.table_to_book(table, { sheet: "Danh sách" });
        XLSX.writeFile(wb, `Danh_sach_SV_Ky_${semName}.xlsx`);
    }
};
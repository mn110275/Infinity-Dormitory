// student/js/requests.js
const ProblemManager = {
    init() {
        this.setupCharCounter();
        this.bindFormValidation();
    },

    setupCharCounter() {
        const contentArea = document.getElementById('content');
        if (!contentArea) return;

        const counterDiv = document.createElement('div');
        counterDiv.className = 'char-counter';
        counterDiv.textContent = '0 / 255';
        contentArea.parentElement.appendChild(counterDiv);

        contentArea.addEventListener('input', (e) => {
            const len = e.target.value.length;
            counterDiv.textContent = `${len} / 255`;
            counterDiv.style.color = len > 240 ? '#ef4444' : '#64748b';
        });
    },

    bindFormValidation() {
        const form = document.getElementById('problemForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const title = document.getElementById('title').value.trim();
            if (!title.length) {
                e.preventDefault();
                alert('Tiêu đề không được để trống.');
            } else if (!confirm('Xác nhận gửi báo cáo này?')) {
                e.preventDefault();
            }
        });
    },

    async revoke(prId) {
        if (!confirm('Bạn có chắc chắn muốn thu hồi báo cáo này?')) return;

        try {
            const response = await fetch('api/requests_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'revoke', pr_id: prId })
            });

            const data = await response.json();
            if (data.success) {
                const item = document.getElementById(`problem-${prId}`);
                item.style.opacity = '0';
                item.style.transform = 'translateX(20px)';
                setTimeout(() => item.remove(), 300);
            } else {
                alert(data.error || 'Không thể thu hồi');
            }
        } catch (err) {
            alert('Lỗi kết nối máy chủ');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => ProblemManager.init());
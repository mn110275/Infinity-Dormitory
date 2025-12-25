// student/js/notifications.js
const NotiApp = {
    init() {
        this.applyReadStatus();
        this.bindEvents();
    },

    bindEvents() {
        const cards = document.querySelectorAll('.noti-card');
        cards.forEach(card => {
            card.addEventListener('click', () => {
                const id = card.getAttribute('data-id');
                
                // Thêm class để đổi màu ngay lập tức
                card.classList.add('is-read');
                
                // Lưu ID này vào trình duyệt để ghi nhớ
                this.markAsRead(id);
            });
        });
    },

    markAsRead(id) {
        let readList = JSON.parse(localStorage.getItem('read_notifications') || '[]');
        if (!readList.includes(id)) {
            readList.push(id);
            localStorage.setItem('read_notifications', JSON.stringify(readList));
        }
    },

    applyReadStatus() {
        let readList = JSON.parse(localStorage.getItem('read_notifications') || '[]');
        readList.forEach(id => {
            const card = document.querySelector(`.noti-card[data-id="${id}"]`);
            if (card) {
                card.classList.add('is-read');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => NotiApp.init());
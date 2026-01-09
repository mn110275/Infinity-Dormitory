const BRAdmin = {
    selectedBlock: null,
    selectedRoom: null,

    translateStatus(status) {
        const map = {
            'Active': 'Đang hoạt động',
            'Maintenance': 'Bảo trì',
            'Closed': 'Đóng cửa'
        };
        return map[status] || status;
    },

    selectBlock(blockId) {
        this.selectedBlock = blockId;

        const allCards = document.querySelectorAll('.block-card');
        allCards.forEach(el => el.classList.remove('active'));

        const currentCard = document.querySelector(`.block-card[data-block="${blockId}"]`);
        if (!currentCard) return;

        currentCard.classList.add('active');

        const currentStatus = currentCard.dataset.status;

        const elementsToShow = [
            'selectedBlockHeader', 
            'blockStatusRow', 
            'roomListHeader'
        ];
        elementsToShow.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = (id === 'roomListHeader') ? 'flex' : 'block';
        });

        document.getElementById('selectedBlockIDDisplay').innerText = blockId;
        
        const statusSelect = document.getElementById('changeBlockStatus');
        if (statusSelect) {
            statusSelect.value = currentStatus;
        }

        const btnAddRoom = document.getElementById('btnAddRoom');
        if (btnAddRoom) {
            if (currentStatus === 'Closed') {
                btnAddRoom.disabled = true; 
                btnAddRoom.style.opacity = '0.5';
                btnAddRoom.style.cursor = 'not-allowed';
            } else {
                btnAddRoom.disabled = false;
                btnAddRoom.style.opacity = '1';
                btnAddRoom.style.cursor = 'pointer';
            }
        }

        this.renderRooms(blockId);

        document.getElementById('selectedBlockHeader').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    },

    renderRooms(blockId) {
        const container = document.getElementById('roomContainer');
        const rooms = window.ALL_ROOMS.filter(r => r.BLOCK_ID === blockId);

        const currentBlockCard = document.querySelector(`.block-card[data-block="${blockId}"]`);
        const blockStatus = currentBlockCard ? currentBlockCard.dataset.status : 'Active';

        if (rooms.length === 0) {
            container.innerHTML = `<div class="empty-state">Tòa này chưa có phòng nào.</div>`;
            return;
        }

        container.className = "room-grid-6 mt-20";

        container.innerHTML = rooms.map(r => {
            let displayStatus = r.ROOM_STATUS;
            
            if (blockStatus === 'Closed') {
                displayStatus = 'Closed';
            } else if (blockStatus === 'Maintenance' && r.ROOM_STATUS === 'Active') {
                displayStatus = 'Maintenance';
            }

            let cardClass = "room-card";
            let dotClass = "room-status-dot";

            if (displayStatus === 'Maintenance') {
                cardClass += " maintenance";
                dotClass += " dot-maintenance";
            } else if (displayStatus === 'Closed') {
                cardClass += " is-closed";
                dotClass += " dot-closed";
            } else {
                dotClass += " dot-active";
            }

            return `
                <div class="${cardClass}" onclick="BRAdmin.openRoomDetail('${r.ROOM_ID}')">
                    <div class="room-name">
                        <span class="${dotClass}"></span>
                        ${r.ROOM_ID}
                    </div>
                    <div class="room-meta">
                        <strong>${r.OCCUPIED}/${r.CAPACITY}</strong> giường<br>
                        <span style="font-size: 12px; font-weight: 500;">${r.GENDER}</span>
                    </div>
                </div>
            `;
        }).join('');
    },

    openAddBlock() {
        document.getElementById('modalAddBlock').style.display = 'flex';
    },

    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.getElementById('formAddBlock').reset();
    },

    async submitAddBlock(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add_block');
        
        try {
            const response = await fetch('actions/br_action.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.success) {
                alert('Thêm tòa thành công!');
                location.reload(); 
            } else {
                alert('Lỗi: ' + res.message);
            }
        } catch (err) {
            alert('Có lỗi xảy ra trong quá trình kết nối.');
        }
    },

    openAddRoom() {
        if (!this.selectedBlock) {
            alert("Vui lòng chọn một tòa trước khi thêm phòng.");
            return;
        }
        
        const modal = document.getElementById('modalAddRoom');
        if (modal) {
            document.getElementById('displayAddRoomBlockID').innerText = this.selectedBlock;
            modal.style.display = 'flex';
        }
    },

    async submitAddRoom(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        formData.append('action', 'add_room');
        formData.append('block_id', this.selectedBlock);

        try {
            const response = await fetch('actions/br_action.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.success) {
                alert('Thêm phòng thành công!');
                location.reload(); 
            } else {
                alert('Lỗi: ' + res.message);
            }
        } catch (err) {
            alert('Có lỗi xảy ra trong quá trình kết nối máy chủ.');
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            const form = modal.querySelector('form');
            if (form) form.reset();
        }
    },

    openRoomDetail(roomId) {
        this.selectedRoom = roomId;
        const room = window.ALL_ROOMS.find(r => r.ROOM_ID === roomId && r.BLOCK_ID === this.selectedBlock);
        
        if (!room) return;

        const currentBlockCard = document.querySelector(`.block-card[data-block="${this.selectedBlock}"]`);
        const blockStatus = currentBlockCard ? currentBlockCard.dataset.status : 'Active';

        document.getElementById('sideRoomId').innerText = roomId;
        
        let displayStatus = room.ROOM_STATUS;
        if (blockStatus === 'Closed') displayStatus = 'Closed';
        else if (blockStatus === 'Maintenance' && room.ROOM_STATUS === 'Active') displayStatus = 'Maintenance';

        this.updateRoomStatusUI(displayStatus);
        this.toggleEditRoomStatus(false);

        const editBtn = document.querySelector('#roomStatusTextContainer .btn-text-action');
        if (editBtn) {
            editBtn.style.display = (blockStatus === 'Closed') ? 'none' : 'block';
        }

        document.getElementById('roomSidebar').classList.add('open');
        document.getElementById('sideOverlay').classList.add('active');

        this.loadOccupants(this.selectedBlock, roomId);
    },

    toggleEditRoomStatus(isEdit) {
        document.getElementById('roomStatusTextContainer').style.display = isEdit ? 'none' : 'flex';
        document.getElementById('roomStatusEditContainer').style.display = isEdit ? 'flex' : 'none';
        
        if (isEdit) {
            const room = window.ALL_ROOMS.find(r => r.ROOM_ID === this.selectedRoom && r.BLOCK_ID === this.selectedBlock);
            document.getElementById('sideRoomStatus').value = room ? room.ROOM_STATUS : 'Active';
        }
    },

    updateRoomStatusUI(status) {
        const badge = document.getElementById('roomStatusBadge');
        badge.innerText = this.translateStatus(status);
        
        badge.className = 'badge-status';
        if (status === 'Active') badge.classList.add('badge-active');
        if (status === 'Maintenance') badge.classList.add('badge-maintenance');
        if (status === 'Closed') badge.classList.add('badge-closed');
    },

    closeSidebar() {
        document.getElementById('roomSidebar').classList.remove('open');
        document.getElementById('sideOverlay').classList.remove('active');
    },

    async saveRoomStatus(isForced = false) {
        const newStatus = document.getElementById('sideRoomStatus').value;
        const roomId = this.selectedRoom;
        const blockId = this.selectedBlock;

        const room = window.ALL_ROOMS.find(r => r.ROOM_ID === roomId && r.BLOCK_ID === blockId);
        const oldStatus = room ? room.ROOM_STATUS : 'Active';

        if (newStatus === oldStatus && !isForced) {
            this.toggleEditRoomStatus(false);
            return;
        }

        if (!isForced) {
            if (!confirm(`Xác nhận đổi trạng thái phòng ${roomId} sang ${this.translateStatus(newStatus)}?`)) {
                return;
            }
        }

        const formData = new FormData();
        formData.append('action', 'update_room_status');
        formData.append('block_id', blockId);
        formData.append('room_id', roomId);
        formData.append('status', newStatus);
        if (isForced) formData.append('force', 'true');

        try {
            const response = await fetch('actions/br_action.php', { method: 'POST', body: formData });
            const res = await response.json();

            if (res.success) {
                alert('Cập nhật phòng thành công!');
                location.reload(); 
            } else if (res.requires_force) {
                const secondCheck = confirm(`CẢNH BÁO:\n${res.message}\n\nBạn có chắc chắn muốn tiếp tục?`);
                if (secondCheck) {
                    this.saveRoomStatus(true);
                }
            } else {
                alert('Lỗi: ' + res.message);
            }
        } catch (err) {
            alert('Lỗi kết nối server.');
        }
    },

    async loadOccupants(blockId, roomId) {
        const container = document.getElementById('occupantList');
        try {
            const response = await fetch(`actions/br_action.php?action=get_occupants&block_id=${blockId}&room_id=${roomId}`);
            const data = await response.json();
            
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="empty-state">Phòng hiện đang trống.</div>';
                return;
            }

            container.innerHTML = data.map(s => `
                <div class="occupant-item" style="padding: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex:1">
                        <div style="font-weight:600; color: var(--text);">${s.STD_NAME}</div>
                        <div style="font-size:12px; color: var(--text-muted);">${s.STD_ID}</div>
                    </div>
                </div>
            `).join('');
        } catch (err) {
            container.innerHTML = '<div class="empty-state">Lỗi kết nối server.</div>';
        }
    },

    toggleBlockMenu(blockId) {
        document.querySelectorAll('.status-dropdown-menu').forEach(m => {
            if(m.id !== `menu-${blockId}`) m.classList.remove('show');
        });
        
        const menu = document.getElementById(`menu-${blockId}`);
        menu.classList.toggle('show');

        const closeMenu = (e) => {
            if (!e.target.closest('.block-status-container')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeMenu);
            }
        };
        document.addEventListener('click', closeMenu);
    },

    async updateBlockStatus(blockId, newStatus, isForced = false) {
        if (!blockId || !newStatus) return;

        const currentCard = document.querySelector(`.block-card[data-block="${blockId}"]`);
        const oldStatus = currentCard ? currentCard.dataset.status : '';

        if (newStatus === oldStatus) return;

        if (!isForced) {
            const statusVn = this.translateStatus(newStatus);
            const firstCheck = confirm(`Xác nhận thay đổi Tòa ${blockId} sang trạng thái: ${statusVn}?`);
            if (!firstCheck){
                const select = document.getElementById('changeBlockStatus');
                if (select) select.value = oldStatus;
                return;
            }
        } 

        const formData = new FormData();
        formData.append('action', 'update_block_status');
        formData.append('block_id', blockId);
        formData.append('status', newStatus);
        if (isForced) formData.append('force', 'true');

        try {
            const response = await fetch('actions/br_action.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.success) {
                alert('Cập nhật trạng thái thành công!');
                location.reload();
            } else if (res.requires_force) {
                const secondCheck = confirm(`CẢNH BÁO HỆ THỐNG:\n${res.message}\n\nBạn vẫn muốn tiếp tục?`);
                if (secondCheck) {
                    this.updateBlockStatus(blockId, newStatus, true);
                } else {
                    const select = document.getElementById('changeBlockStatus');
                    if (select) select.value = oldStatus;
                }
            } else {
                alert('Lỗi: ' + res.message);
                const select = document.getElementById('changeBlockStatus');
                if (select) select.value = oldStatus;
            }
        } catch (err) {
            alert('Lỗi kết nối máy chủ.');
        }
    }
};
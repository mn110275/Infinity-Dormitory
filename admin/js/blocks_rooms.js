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
        const data = window.BLOCKS_DATA[blockId];
        if (!data) return;
        this.selectedBlock = blockId;

        document.getElementById('selectedBlockHeader').style.display = 'block';
        document.getElementById('blockStatusRow').style.display = 'block';
        document.getElementById('selectedBlockIDDisplay').innerText = blockId;

        const allCards = document.querySelectorAll('.block-card');
        allCards.forEach(el => el.classList.remove('active'));

        const currentCard = document.querySelector(`.block-card[data-block="${blockId}"]`);
        if (!currentCard) return;

        currentCard.classList.add('active');
        const currentStatus = currentCard.dataset.status;

        const elementsToShow = ['selectedBlockHeader', 'blockStatusRow', 'roomListHeader'];
        elementsToShow.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = (id === 'roomListHeader') ? 'flex' : 'block';
        });

        document.getElementById('selectedBlockIDDisplay').innerText = blockId;
        
        this.updateBlockStatusUI(currentStatus);
        this.toggleEditBlockStatus(false);  

        document.getElementById('statMale').innerText = data.count_male_rooms;
        document.getElementById('statFemale').innerText = data.count_female_rooms;
        document.getElementById('statActive').innerText = data.count_active_rooms;
        document.getElementById('statMaint').innerText = data.count_maint_rooms;
        document.getElementById('statClosed').innerText = data.count_closed_rooms;

        const btnAddRoom = document.getElementById('btnAddRoom');
        const roomInstruction = document.getElementById('roomListInstruction');
        
        if (currentStatus !== 'Active') {
            if (btnAddRoom) {
                btnAddRoom.disabled = true; 
                btnAddRoom.style.opacity = '0.5';
                btnAddRoom.style.cursor = 'not-allowed';
            }
            if (roomInstruction) {
                roomInstruction.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> <span style="color: #d97706;">Lưu ý: Tòa đang <b>${this.translateStatus(currentStatus)}</b>. Hệ thống tạm khóa chức năng thay đổi trạng thái phòng.</span>`;
                roomInstruction.style.display = 'block';
            }
        } else {
            if (btnAddRoom) {
                btnAddRoom.disabled = false;
                btnAddRoom.style.opacity = '1';
                btnAddRoom.style.cursor = 'pointer';
            }
            if (roomInstruction) roomInstruction.style.display = 'none';
        }

        this.renderRooms(blockId);
        document.getElementById('selectedBlockHeader').scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    renderRooms(blockId) {
        const container = document.getElementById('roomContainer');
        const rooms = window.ALL_ROOMS.filter(r => r.BLOCK_ID === blockId);

        if (rooms.length === 0) {
            container.innerHTML = `<div class="empty-state">Tòa này chưa có phòng nào.</div>`;
            return;
        }

        container.className = "room-grid-6 mt-20";

        container.innerHTML = rooms.map(r => {
            let displayStatus = r.ROOM_STATUS; 
            
            let cardClass = "room-card";
            if (displayStatus === 'Active') cardClass += " active";
            else if (displayStatus === 'Maintenance') cardClass += " maintenance";
            else if (displayStatus === 'Closed') cardClass += " is-closed";

            let dotClass = "room-status-dot";
            if (displayStatus === 'Maintenance') dotClass += " dot-maintenance";
            else if (displayStatus === 'Closed') dotClass += " dot-closed";
            else dotClass += " dot-active";

            return `
                <div class="${cardClass}" onclick="BRAdmin.openRoomDetail('${r.ROOM_ID}')">
                    <div class="room-name">
                        <span class="${dotClass}"></span>
                        ${r.ROOM_ID}
                    </div>
                    <div class="room-info">${r.GENDER}</div>
                    <div class="occupancy-tag">${r.OCCUPIED}/${r.CAPACITY} Giường</div>
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
        const newBlockId = formData.get('block_id');
        formData.append('action', 'add_block');
        
        try {
            const response = await fetch('actions/br_action.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.success) {
                alert('Thêm tòa thành công!');
                this.closeModal('modalAddBlock');

                window.BLOCKS_DATA[newBlockId] = {
                    BLOCK_ID: newBlockId,
                    BLOCK_STATUS: formData.get('status'),
                    count_rooms: 0, total_cap: 0, total_occ: 0, available_slots: 0,
                    count_active_rooms: 0, count_maint_rooms: 0, count_closed_rooms: 0,
                    count_male_rooms: 0, count_female_rooms: 0
                };

                this.renderAllBlocks(); 
                
                this.selectBlock(newBlockId);
            } else {
                alert('Lỗi: ' + res.message);
            }
        } catch (err) {
            alert('Có lỗi xảy ra trong quá trình kết nối.');
        }
    },

    renderAllBlocks() {
        const container = document.getElementById('blockContainer');
        if (!container) return;

        const sorted = Object.values(window.BLOCKS_DATA).sort((a, b) => {
            if (a.BLOCK_STATUS === 'Closed' && b.BLOCK_STATUS !== 'Closed') return 1;
            if (a.BLOCK_STATUS !== 'Closed' && b.BLOCK_STATUS === 'Closed') return -1;
            return a.BLOCK_ID.localeCompare(b.BLOCK_ID);
        });

        container.innerHTML = sorted.map(b => {
            const statusClass = b.BLOCK_STATUS === 'Maintenance' ? 'is-maintenance' : (b.BLOCK_STATUS === 'Closed' ? 'is-closed' : '');
            const progress = (b.total_cap > 0) ? (b.total_occ / b.total_cap * 100) : 0;
            const activeClass = (this.selectedBlock === b.BLOCK_ID) ? 'active' : '';

            return `
                <div class="block-card ${statusClass} ${activeClass}" data-block="${b.BLOCK_ID}" data-status="${b.BLOCK_STATUS}" onclick="BRAdmin.selectBlock('${b.BLOCK_ID}')">
                    <div class="block-header-flex">
                        <div class="block-name">Tòa ${b.BLOCK_ID}</div>
                        <div class="block-status-container">
                            <span class="block-tag">${this.translateStatus(b.BLOCK_STATUS)}</span>
                        </div>
                    </div>
                    <div class="block-info-wrapper">
                        <div class="block-info-line"><strong>${b.count_rooms || 0}</strong> phòng</div>
                        <div class="block-info-line">Đã ở: <strong>${b.total_occ || 0}/${b.total_cap || 0}</strong> chỗ</div>
                    </div>
                    <div class="progress-mini">
                        <div class="progress-bar" style="width: ${progress}%"></div>
                    </div>
                </div>`;
        }).join('');
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
                this.closeModal('modalAddRoom');

                const newRoom = {
                    ROOM_ID: formData.get('room_id'),
                    BLOCK_ID: this.selectedBlock,
                    CAPACITY: parseInt(formData.get('capacity')),
                    OCCUPIED: 0,
                    GENDER: formData.get('gender'),
                    ROOM_STATUS: formData.get('status')
                };
                window.ALL_ROOMS.push(newRoom);

                if (window.BLOCKS_DATA[this.selectedBlock]) {
                    window.BLOCKS_DATA[this.selectedBlock].count_rooms = parseInt(window.BLOCKS_DATA[this.selectedBlock].count_rooms || 0) + 1;
                }

                this.refreshBlockStats(this.selectedBlock);
                this.renderAllBlocks(); 
                this.renderRooms(this.selectedBlock);
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
        
        this.updateRoomStatusUI(room.ROOM_STATUS);
        this.toggleEditRoomStatus(false);

        const editBtn = document.querySelector('#roomStatusTextContainer .btn-pro') || 
                        document.querySelector('#roomStatusTextContainer .btn-disabled'); 
        
        if (editBtn) {
            if (blockStatus !== 'Active') {
                editBtn.className = 'btn-disabled'; 
                editBtn.style.background = '#e2e8f0';
                editBtn.style.color = '#94a3b8';
                editBtn.innerHTML = '<i class="fas fa-lock"></i> Khóa';
                
                this.showSidebarWarning(`Tòa đang <b>${this.translateStatus(blockStatus)}</b>. 
                    Hệ thống ghi nhận trạng thái riêng của phòng, nhưng toàn bộ phòng thuộc tòa này đều sẽ phụ thuộc theo trạng thái của tòa.`);
            } else {
                editBtn.className = 'btn-pro';
                editBtn.style = ''; 
                editBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Thay đổi';
                
                this.hideSidebarWarning();
            }
        }

        document.getElementById('roomSidebar').classList.add('open');
        document.getElementById('sideOverlay').classList.add('active');
        this.loadOccupants(this.selectedBlock, roomId);
    },

    toggleEditRoomStatus(isEdit) {
        const currentBlockCard = document.querySelector(`.block-card[data-block="${this.selectedBlock}"]`);
        const blockStatus = currentBlockCard ? currentBlockCard.dataset.status : 'Active';

        if (isEdit && blockStatus !== 'Active') {
            return; 
        }

        const roomStatusTextContainer = document.getElementById('roomStatusTextContainer');
        const roomStatusEditContainer = document.getElementById('roomStatusEditContainer');
        const statusSelect = document.getElementById('sideRoomStatus');

        if (isEdit) {
            roomStatusTextContainer.style.display = 'none';
            roomStatusEditContainer.style.display = 'flex';
            
            const room = window.ALL_ROOMS.find(r => r.ROOM_ID === this.selectedRoom && r.BLOCK_ID === this.selectedBlock);
            if (room) statusSelect.value = room.ROOM_STATUS;
        } else {
            roomStatusTextContainer.style.display = 'flex';
            roomStatusEditContainer.style.display = 'none';
        }
    },

    showSidebarWarning(message) {
        let warningEl = document.getElementById('roomSidebarWarning');
        if (!warningEl) {
            warningEl = document.createElement('div');
            warningEl.id = 'roomSidebarWarning';
            warningEl.style = "background: #fff7ed; color: #9a3412; padding: 12px; border: 1px solid #fed7aa; border-radius: 8px; margin-bottom: 15px; font-size: 13px; line-height: 1.5;";
            const container = document.querySelector('.sidebar-content');
            if (container) container.prepend(warningEl);
        }
        warningEl.innerHTML = `<b>Cảnh báo:</b> ${message}`;
        warningEl.style.display = 'block';
    },

    hideSidebarWarning() {
        const warningEl = document.getElementById('roomSidebarWarning');
        if (warningEl) warningEl.style.display = 'none';
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
        const currentBlockCard = document.querySelector(`.block-card[data-block="${this.selectedBlock}"]`);
        const blockStatus = currentBlockCard ? currentBlockCard.dataset.status : 'Active';

        if (blockStatus !== 'Active') {
            alert("Thao tác bị từ chối: Tòa của phòng không ở trạng thái Đang hoạt động.");
            this.toggleEditRoomStatus(false);
            return;
        }

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
                const roomIndex = window.ALL_ROOMS.findIndex(r => r.ROOM_ID === roomId && r.BLOCK_ID === blockId);
                if (roomIndex !== -1) {
                    window.ALL_ROOMS[roomIndex].ROOM_STATUS = newStatus;

                    if (res.new_occupied !== undefined) {
                        window.ALL_ROOMS[roomIndex].OCCUPIED = res.new_occupied;
                    } else if (newStatus !== 'Active') {
                        window.ALL_ROOMS[roomIndex].OCCUPIED = 0;
                    }
                }

                this.refreshBlockStats(blockId);
                this.selectBlock(blockId);
                this.updateRoomStatusUI(newStatus); 
                this.renderRooms(blockId);         
                this.toggleEditRoomStatus(false);
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
                container.innerHTML = '<div class="empty-state">Phòng trống.</div>';
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

    toggleEditBlockStatus(isEdit) {
        const textContainer = document.getElementById('blockStatusTextContainer');
        const editContainer = document.getElementById('blockStatusEditContainer');
        const statusSelect = document.getElementById('changeBlockStatus');

        if (isEdit) {
            textContainer.style.display = 'none';
            editContainer.style.display = 'flex';
            const currentCard = document.querySelector(`.block-card[data-block="${this.selectedBlock}"]`);
            if (currentCard) statusSelect.value = currentCard.dataset.status;
        } else {
            textContainer.style.display = 'flex';
            editContainer.style.display = 'none';
        }
    },

    updateBlockStatusUI(status) {
        const badge = document.getElementById('blockStatusBadge');
        if (!badge) return;
        
        badge.innerText = this.translateStatus(status);
        badge.className = 'badge-status'; // Reset class
        
        if (status === 'Active') badge.classList.add('badge-active');
        else if (status === 'Maintenance') badge.classList.add('badge-maintenance');
        else if (status === 'Closed') badge.classList.add('badge-closed');
    },

    saveBlockStatus() {
        const newStatus = document.getElementById('changeBlockStatus').value;
        this.updateBlockStatus(this.selectedBlock, newStatus);
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
                
                if (window.BLOCKS_DATA[blockId]) {
                    window.BLOCKS_DATA[blockId].BLOCK_STATUS = newStatus;
                }

                const currentCard = document.querySelector(`.block-card[data-block="${blockId}"]`);
                if (currentCard) {
                    currentCard.dataset.status = newStatus;
                }

                this.refreshBlockStats(blockId);
                this.updateBlockStatusUI(newStatus);
                this.toggleEditBlockStatus(false);
                this.selectBlock(blockId);
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
    },

    refreshBlockStats(blockId) {
        const rooms = window.ALL_ROOMS.filter(r => r.BLOCK_ID === blockId);
        const blockData = window.BLOCKS_DATA[blockId];
        
        if (blockData) {
            blockData.count_active_rooms = rooms.filter(r => r.ROOM_STATUS === 'Active').length;
            blockData.count_maint_rooms = rooms.filter(r => r.ROOM_STATUS === 'Maintenance').length;
            blockData.count_closed_rooms = rooms.filter(r => r.ROOM_STATUS === 'Closed').length;

            let totalCap = 0;
            let totalOcc = 0;
            let availSlots = 0;

            rooms.forEach(r => {
                const cap = parseInt(r.CAPACITY) || 0;
                const occ = parseInt(r.OCCUPIED) || 0;
                totalCap += cap;
                totalOcc += occ;

                if (r.ROOM_STATUS === 'Active' && blockData.BLOCK_STATUS === 'Active') {
                    availSlots += (cap - occ);
                }
            });

            blockData.total_cap = totalCap;
            blockData.total_occ = totalOcc;
            blockData.available_slots = availSlots;

            if(document.getElementById('statActive')) document.getElementById('statActive').innerText = blockData.count_active_rooms;
            if(document.getElementById('statMaint')) document.getElementById('statMaint').innerText = blockData.count_maint_rooms;
            if(document.getElementById('statClosed')) document.getElementById('statClosed').innerText = blockData.count_closed_rooms;

            const currentCard = document.querySelector(`.block-card[data-block="${blockId}"]`);
            if (currentCard) {
                const infoLine = currentCard.querySelector('.block-info-line:last-child strong');
                if (infoLine) {
                    infoLine.innerText = `${blockData.total_occ}/${blockData.total_cap}`;
                }
                const progressBar = currentCard.querySelector('.progress-bar');
                if (progressBar) {
                    const percent = (blockData.total_cap > 0) ? (blockData.total_occ / blockData.total_cap * 100) : 0;
                    progressBar.style.width = percent + '%';
                }
            }

            this.updateGlobalStats();
        }
    },

    updateGlobalStats() {
        let gTotal = 0;
        let gOcc = 0;
        let gAvail = 0;

        // Duyệt qua tất cả các tòa để cộng dồn
        Object.values(window.BLOCKS_DATA).forEach(b => {
            const cap = parseInt(b.total_cap) || 0;
            const occ = parseInt(b.total_occ) || 0;
            const avail = parseInt(b.available_slots) || 0;

            gTotal += cap;
            gOcc += occ;
            gAvail += avail;
        });

        const gUnavail = gTotal - gOcc - gAvail;

        // Đổ dữ liệu vào các ID đã thêm ở Bước 1
        if(document.getElementById('globalTotalSlots')) document.getElementById('globalTotalSlots').innerText = gTotal;
        if(document.getElementById('globalOccupied')) document.getElementById('globalOccupied').innerText = gOcc;
        if(document.getElementById('globalAvailable')) document.getElementById('globalAvailable').innerText = gAvail;
        if(document.getElementById('globalUnavailable')) document.getElementById('globalUnavailable').innerText = gUnavail;
    }
};
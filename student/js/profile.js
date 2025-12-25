// student/js/profile.js

const StudentProfile = {
    init() {
        this.bindEvents();
    },

    bindEvents() {
        const btnChangeAvatar = document.getElementById('btnChangeAvatar');
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreview = document.getElementById('avatarPreview');

        if (btnChangeAvatar && avatarInput) {
            btnChangeAvatar.addEventListener('click', () => {
                avatarInput.click();
            });

            avatarInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        if (avatarPreview) {
                            avatarPreview.src = event.target.result;
                        } else {
                            const placeholder = document.querySelector('.avatar-placeholder');
                            if (placeholder) {
                                const img = document.createElement('img');
                                img.src = event.target.result;
                                img.alt = 'Avatar';
                                img.id = 'avatarPreview';
                                placeholder.parentElement.replaceChild(img, placeholder);
                            }
                        }
                    };
                    reader.readAsDataURL(file);

                    if (confirm('Bạn đã chọn ảnh mới. Nhớ nhấn "Lưu thông tin" để cập nhật!')) {
                        document.querySelector('.form-actions')?.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        }

        const btnChangePassword = document.getElementById('btnChangePassword');
        const passwordModal = document.getElementById('passwordModal');
        const closePasswordModal = document.getElementById('closePasswordModal');
        const cancelPassword = document.getElementById('cancelPassword');
        const passwordForm = document.getElementById('passwordForm');

        if (btnChangePassword && passwordModal) {
            btnChangePassword.addEventListener('click', () => {
                passwordModal.style.display = 'flex';
            });

            const closeModal = () => {
                passwordModal.style.display = 'none';
                passwordForm?.reset();
            };

            closePasswordModal?.addEventListener('click', closeModal);
            cancelPassword?.addEventListener('click', closeModal);

            passwordModal.addEventListener('click', (e) => {
                if (e.target === passwordModal) closeModal();
            });
        }

        if (passwordForm) {
            passwordForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (newPassword !== confirmPassword) {
                    alert('Mật khẩu mới và xác nhận không khớp.');
                    return;
                }

                if (newPassword.length < 6) {
                    alert('Mật khẩu mới phải có ít nhất 6 ký tự.');
                    return;
                }

                try {
                    const response = await fetch('api/profile_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'change_password',
                            current_password: currentPassword,
                            new_password: newPassword
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Đổi mật khẩu thành công.');
                        passwordModal.style.display = 'none';
                        passwordForm.reset();
                    } else {
                        alert((result.error || 'Không thể đổi mật khẩu'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Lỗi kết nối server.');
                }
            });
        }

        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                const phone = document.getElementById('std_phone').value;
                const address = document.getElementById('std_adr').value;

                if (!phone) {
                    e.preventDefault();
                    alert('Số điện thoại không được để trống.');
                    return;
                }

                if (!address) {
                    e.preventDefault();
                    alert('Địa chỉ không được để trống.');
                    return;
                }

                if (!confirm('Xác nhận cập nhật thông tin cá nhân?')) {
                    e.preventDefault();
                }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => StudentProfile.init());
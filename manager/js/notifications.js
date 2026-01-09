// manager/notifications.js

const NotificationManager = {
  init()
  {
    this.bindEvents();
    this.setupCharCounter();
  },

  bindEvents()
  {
    document.querySelectorAll('input[name="target_type"]').forEach(radio =>
    {
      radio.addEventListener('change', (e) =>
      {
        const roomGroup = document.getElementById('roomSelectionGroup');
        if (e.target.value === 'room')
        {
          roomGroup.style.display = 'block';
        }
        else
        {
          roomGroup.style.display = 'none';
          document.querySelectorAll('input[name="target_rooms[]"]').forEach(cb =>
          {
            cb.checked = false;
          });
        }
      });
    });

    const selectAllBtn = document.getElementById('selectAllRooms');
    if (selectAllBtn)
    {
      selectAllBtn.addEventListener('click', () =>
      {
        const checkboxes = document.querySelectorAll('input[name="target_rooms[]"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb =>
        {
          cb.checked = !allChecked;
        });

        selectAllBtn.textContent = allChecked ? 'Chọn tất cả' : 'Bỏ chọn tất cả';
      });
    }

    const form = document.getElementById('notificationForm');
    if (form)
    {
      form.addEventListener('submit', (e) =>
      {
        if (!this.validateForm())
        {
          e.preventDefault();
        }
      });
    }
  },

  setupCharCounter()
  {
    const contentTextarea = document.getElementById('content');
    if (!contentTextarea) return;

    const existingCounter = contentTextarea.parentElement.querySelector('.char-counter');
    if (existingCounter)
    {
      existingCounter.remove();
    }

    const counterDiv = document.createElement('div');
    counterDiv.className = 'char-counter';
    counterDiv.textContent = '0 / 1000';

    contentTextarea.parentElement.appendChild(counterDiv);

    contentTextarea.addEventListener('input', (e) =>
    {
      const length = e.target.value.length;
      counterDiv.textContent = `${length} / 1000`;

      if (length > 450)
      {
        counterDiv.style.color = '#ef4444';
      }
      else if (length > 400)
      {
        counterDiv.style.color = '#f59e0b';
      }
      else
      {
        counterDiv.style.color = '#64748b';
      }
    });

    if (contentTextarea.value)
    {
      const event = new Event('input');
      contentTextarea.dispatchEvent(event);
    }
  },

  validateForm()
  {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const targetType = document.querySelector('input[name="target_type"]:checked')?.value;

    if (!title)
    {
      alert('Tiêu đề không được để trống.');
      return false;
    }

    if (targetType === 'room')
    {
      const checkedRooms = document.querySelectorAll('input[name="target_rooms[]"]:checked');
      if (checkedRooms.length === 0)
      {
        alert('Vui lòng chọn ít nhất một phòng.');
        return false;
      }
    }

    return confirm('Xác nhận gửi thông báo đến sinh viên?');
  }
};

if (document.readyState === 'loading')
{
  document.addEventListener('DOMContentLoaded', () => NotificationManager.init());
}
else
{
  NotificationManager.init();
}
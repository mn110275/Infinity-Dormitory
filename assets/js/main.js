/* assets/js/main.js
   Dùng chung cho tất cả trang. Giữ nguyên logic đã có:
   - lưu / đọc studentsList từ localStorage
   - đăng ký / sửa / xóa
   - login student/admin
   - export / clear data (admin)
*/

console.log("✅ main.js loaded");

// ---------- keys ----------
const STUDENTS_KEY = "studentsList";
const LOGGED_STUDENT = "loggedInStudent";
const ADMIN_KEY = "isAdminLoggedIn";

// ---------- helpers ----------
function getStudents() {
  return JSON.parse(localStorage.getItem(STUDENTS_KEY)) || [];
}
function saveStudents(arr) {
  localStorage.setItem(STUDENTS_KEY, JSON.stringify(arr));
}
function addOrUpdateStudent(student) {
  const arr = getStudents();
  const idx = arr.findIndex(s => s.mssv === student.mssv);
  if (idx >= 0) {
    arr[idx] = student;
    saveStudents(arr);
    return { ok: true, updated: true };
  } else {
    // tránh duplicate MSSV
    if (arr.some(s => s.mssv === student.mssv)) {
      return { ok: false, msg: "MSSV đã tồn tại" };
    }
    arr.push(student);
    saveStudents(arr);
    return { ok: true, updated: false };
  }
}

function escapeHtml(str = "") {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

// ---------- REGISTER PAGE ----------
const registerForm = document.getElementById("registerForm");
if (registerForm) {
  console.log("Register form found.");
  const messageEl = document.getElementById("message");
  registerForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const name = document.getElementById("name").value.trim();
    const mssv = document.getElementById("mssv").value.trim();
    const email = document.getElementById("email").value.trim();
    const phone = document.getElementById("phone").value.trim();

    if (!name || !mssv || !email) {
      if (messageEl) messageEl.textContent = "Vui lòng điền đủ thông tin bắt buộc";
      return;
    }

    const res = addOrUpdateStudent({ name, mssv, email, phone });
    if (!res.ok) {
      if (messageEl) messageEl.textContent = "❌ " + res.msg;
      return;
    }

    if (res.updated) {
      if (messageEl) messageEl.textContent = "✏️ Cập nhật thông tin thành công!";
    } else {
      if (messageEl) messageEl.textContent = "✅ Đăng ký thành công!";
    }

    registerForm.reset();
    // nếu đang ở trang register, render lại list nếu có
    const listDiv = document.getElementById("studentList");
    if (listDiv) renderStudentsTo(listDiv);
  });

  // Nếu có dữ liệu editStudent (từ list -> edit), load lên form
  window.addEventListener("DOMContentLoaded", () => {
    const editData = JSON.parse(localStorage.getItem("editStudent") || "null");
    if (editData) {
      document.getElementById("name").value = editData.name || "";
      document.getElementById("mssv").value = editData.mssv || "";
      document.getElementById("email").value = editData.email || "";
      document.getElementById("phone").value = editData.phone || "";
      const messageEl = document.getElementById("message");
      if (messageEl) messageEl.textContent = "📝 Đang chỉnh sửa thông tin sinh viên...";
      localStorage.removeItem("editStudent");
    }
  });
}

// ---------- RENDER (dùng cho register (studentList) & admin dashboard) ----------
function renderStudentsTo(container) {
  if (!container) return;
  const students = getStudents();
  if (students.length === 0) {
    container.innerHTML = "<p>Chưa có sinh viên nào đăng ký.</p>";
    return;
  }
  let html = `<table>
    <tr><th>STT</th><th>Họ và tên</th><th>MSSV</th><th>Email</th><th>SĐT</th>`;
  const isAdminTable = container.id === "adminStudentList";
  if (isAdminTable) html += "<th>Hành động</th>";
  html += "</tr>";

  students.forEach((s, i) => {
    html += `<tr>
      <td>${i + 1}</td>
      <td>${escapeHtml(s.name)}</td>
      <td>${escapeHtml(s.mssv)}</td>
      <td>${escapeHtml(s.email)}</td>
      <td>${escapeHtml(s.phone || "")}</td>`;
    if (isAdminTable) {
      html += `<td>
        <button data-index="${i}" class="btn editBtn" data-mssv="${escapeHtml(s.mssv)}">✏️ Sửa</button>
        <button data-index="${i}" class="btn danger deleteBtn" data-mssv="${escapeHtml(s.mssv)}">🗑️ Xóa</button>
      </td>`;
    }
    html += `</tr>`;
  });

  html += "</table>";
  container.innerHTML = html;
}

// Khi load trang register, show list under form if element exists
document.addEventListener("DOMContentLoaded", () => {
  const listDiv = document.getElementById("studentList");
  if (listDiv) renderStudentsTo(listDiv);

  // nếu là admin page, render admin list
  const adminDiv = document.getElementById("adminStudentList");
  if (adminDiv) {
    // quyền admin?
    if (localStorage.getItem(ADMIN_KEY) !== "true") {
      window.location.href = "admin-login.html";
      return;
    }
    renderStudentsTo(adminDiv);
  }
});

// ---------- GLOBAL CLICK (event delegation) for edit/delete on admin table ----------
document.addEventListener("click", function (e) {
  // delete
  const del = e.target.closest(".deleteBtn");
  if (del) {
    const mssv = del.getAttribute("data-mssv");
    if (!mssv) return;
    if (!confirm("Xác nhận xóa sinh viên MSSV: " + mssv + " ?")) return;
    let arr = getStudents();
    arr = arr.filter(s => s.mssv !== mssv);
    saveStudents(arr);
    // re-render both admin and register lists if present
    const adminDiv = document.getElementById("adminStudentList");
    if (adminDiv) renderStudentsTo(adminDiv);
    const listDiv = document.getElementById("studentList");
    if (listDiv) renderStudentsTo(listDiv);
    return;
  }

  // edit
  const edit = e.target.closest(".editBtn");
  if (edit) {
    const mssv = edit.getAttribute("data-mssv");
    if (!mssv) return;
    const arr = getStudents();
    const student = arr.find(s => s.mssv === mssv);
    if (!student) return;
    localStorage.setItem("editStudent", JSON.stringify(student));
    // chuyển về trang register để edit
    // nếu đang ở folder pages, link tương đối sẽ là register.html; nếu ở root thì pages/register.html
    // dùng window.location with relative path; because this file is included from pages/*, so redirect to register.html in same folder
    const currentPath = window.location.pathname;
    // If current page is inside /pages/, go to register.html (same folder)
    if (currentPath.includes("/pages/")) {
      window.location.href = "register.html";
    } else {
      window.location.href = "pages/register.html";
    }
    return;
  }
});

// ---------- STUDENT LOGIN ----------
const studentLoginForm = document.getElementById("studentLoginForm");
if (studentLoginForm) {
  studentLoginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const mssv = document.getElementById("login_mssv").value.trim();
    const email = document.getElementById("login_email").value.trim();
    const students = getStudents();
    const found = students.find(s => s.mssv === mssv && s.email === email);
    const loginMsg = document.getElementById("loginMsg");
    if (found) {
      localStorage.setItem(LOGGED_STUDENT, JSON.stringify(found));
      // chuyển đến student-dashboard.html
      // if current path includes /pages/ use relative path
      const currentPath = window.location.pathname;
      if (currentPath.includes("/pages/")) {
        window.location.href = "student-dashboard.html";
      } else {
        window.location.href = "pages/student-dashboard.html";
      }
    } else {
      if (loginMsg) loginMsg.textContent = "❌ Thông tin không chính xác hoặc chưa đăng ký.";
    }
  });
}

// ---------- ADMIN LOGIN ----------
const adminLoginForm = document.getElementById("adminLoginForm");
if (adminLoginForm) {
  adminLoginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const adminLoginMsg = document.getElementById("adminLoginMsg");
    if (username === "admin" && password === "admin123") {
      localStorage.setItem(ADMIN_KEY, "true");
      const currentPath = window.location.pathname;
      if (currentPath.includes("/pages/")) {
        window.location.href = "admin-dashboard.html";
      } else {
        window.location.href = "pages/admin-dashboard.html";
      }
    } else {
      if (adminLoginMsg) adminLoginMsg.textContent = "❌ Sai tên đăng nhập hoặc mật khẩu.";
    }
  });
}

// ---------- STUDENT DASHBOARD ----------
const infoDiv = document.getElementById("info");
if (infoDiv) {
  const student = JSON.parse(localStorage.getItem(LOGGED_STUDENT) || "null");
  if (!student) {
    // redirect to login page
    const currentPath = window.location.pathname;
    if (currentPath.includes("/pages/")) {
      window.location.href = "student-login.html";
    } else {
      window.location.href = "pages/student-login.html";
    }
  } else {
    infoDiv.innerHTML = `
      <div class="card">
        <p><strong>Họ và tên:</strong> ${escapeHtml(student.name)}</p>
        <p><strong>MSSV:</strong> ${escapeHtml(student.mssv)}</p>
        <p><strong>Email:</strong> ${escapeHtml(student.email)}</p>
        <p><strong>SĐT:</strong> ${escapeHtml(student.phone || "")}</p>
      </div>
    `;
  }
  const logoutBtn = document.getElementById("logoutStudent");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      localStorage.removeItem(LOGGED_STUDENT);
      const currentPath = window.location.pathname;
      if (currentPath.includes("/pages/")) {
        window.location.href = "student-login.html";
      } else {
        window.location.href = "pages/student-login.html";
      }
    });
  }
}

// ---------- ADMIN DASHBOARD (export, clear, logout) ----------
if (document.getElementById("adminStudentList")) {
  // kiểm tra quyền
  if (localStorage.getItem(ADMIN_KEY) !== "true") {
    const currentPath = window.location.pathname;
    if (currentPath.includes("/pages/")) {
      window.location.href = "admin-login.html";
    } else {
      window.location.href = "pages/admin-login.html";
    }
  } else {
    // render list
    const adminDiv = document.getElementById("adminStudentList");
    renderStudentsTo(adminDiv);
  }

  const logoutAdmin = document.getElementById("logoutAdmin");
  if (logoutAdmin) {
    logoutAdmin.addEventListener("click", () => {
      localStorage.removeItem(ADMIN_KEY);
      const currentPath = window.location.pathname;
      if (currentPath.includes("/pages/")) {
        window.location.href = "admin-login.html";
      } else {
        window.location.href = "pages/admin-login.html";
      }
    });
  }

  const exportBtn = document.getElementById("exportBtn");
  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      const data = localStorage.getItem(STUDENTS_KEY) || "[]";
      const blob = new Blob([data], { type: "application/json" });
      const a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = "students_log.json";
      a.click();
    });
  }

  const clearBtn = document.getElementById("clearBtn");
  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      if (!confirm("Xác nhận xóa tất cả dữ liệu localStorage?")) return;
      localStorage.removeItem(STUDENTS_KEY);
      localStorage.removeItem(LOGGED_STUDENT);
      localStorage.removeItem(ADMIN_KEY);
      const adminDiv = document.getElementById("adminStudentList");
      if (adminDiv) renderStudentsTo(adminDiv);
      const listDiv = document.getElementById("studentList");
      if (listDiv) renderStudentsTo(listDiv);
    });
  }
}

// ---------- FEEDBACK (đơn giản, lưu localStorage) ----------
const feedbackForm = document.getElementById("feedbackForm");
if (feedbackForm) {
  const fbMsg = document.getElementById("fb_message");
  feedbackForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const name = document.getElementById("fb_name").value.trim();
    const email = document.getElementById("fb_email").value.trim();
    const text = document.getElementById("fb_text").value.trim();
    if (!text) {
      if (fbMsg) fbMsg.textContent = "Vui lòng nhập nội dung góp ý.";
      return;
    }
    const list = JSON.parse(localStorage.getItem("feedbacks") || "[]");
    list.push({ name, email, text, date: new Date().toISOString() });
    localStorage.setItem("feedbacks", JSON.stringify(list));
    if (fbMsg) fbMsg.textContent = "Cảm ơn bạn đã góp ý!";
    feedbackForm.reset();
  });
}

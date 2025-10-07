/* script.js - Dùng chung cho tất cả trang.
   Nội dung được viết defensively: trước khi thao tác DOM, luôn kiểm tra phần tử tồn tại.
*/

console.log("✅ script.js loaded");

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
function addStudent(student) {
  const arr = getStudents();
  // tránh duplicate MSSV
  if (arr.some(s => s.mssv === student.mssv)) {
    return { ok: false, msg: "MSSV đã tồn tại" };
  }
  arr.push(student);
  saveStudents(arr);
  return { ok: true };
}

// ---------- Register page ----------
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
      messageEl.textContent = "Vui lòng điền đủ thông tin bắt buộc";
      return;
    }

    const res = addStudent({ name, mssv, email, phone });
    if (!res.ok) {
      messageEl.textContent = "❌ " + res.msg;
      return;
    }

    messageEl.textContent = "✅ Đăng ký thành công!";
    registerForm.reset();
    showStudents(); // cập nhật danh sách ngay
  });
}

// ---------- Hiển thị danh sách (dùng cho register.html và admin.html) ----------
function renderStudentsTo(container) {
  if (!container) return;
  const students = getStudents();
  if (students.length === 0) {
    container.innerHTML = "<p>Chưa có sinh viên nào đăng ký.</p>";
    return;
  }
  let html = `<table>
    <tr><th>STT</th><th>Họ và tên</th><th>MSSV</th><th>Email</th><th>SĐT</th>`;
  // nếu là admin container đặt id adminStudentList thì thêm cột hành động
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
      html += `<td><button data-index="${i}" class="btn deleteBtn">Xóa</button></td>`;
    }
    html += `</tr>`;
  });

  html += "</table>";
  container.innerHTML = html;
}

// helper escape để tránh HTML injection (cơ bản)
function escapeHtml(str = "") {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

// gọi showStudents cho register page list
function showStudents() {
  const listDiv = document.getElementById("studentList");
  if (listDiv) {
    renderStudentsTo(listDiv);
  }
}
// gọi render cho admin page
function showAdminStudents() {
  const adminDiv = document.getElementById("adminStudentList");
  if (adminDiv) {
    renderStudentsTo(adminDiv);
  }
}

// ---------- Xử lý xóa trong trang admin (event delegation) ----------
document.addEventListener("click", function (e) {
  const del = e.target.closest(".deleteBtn");
  if (del) {
    const idx = Number(del.getAttribute("data-index"));
    const arr = getStudents();
    if (isNaN(idx) || idx < 0 || idx >= arr.length) return;
    if (!confirm(`Xác nhận xóa ${arr[idx].name} (${arr[idx].mssv}) ?`)) return;
    arr.splice(idx, 1);
    saveStudents(arr);
    showAdminStudents();
    showStudents();
  }
});

// ---------- Student login ----------
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
      window.location.href = "student.html";
    } else {
      if (loginMsg) loginMsg.textContent = "❌ Thông tin không chính xác hoặc chưa đăng ký.";
    }
  });
}

// ---------- Admin login ----------
const adminLoginForm = document.getElementById("adminLoginForm");
if (adminLoginForm) {
  adminLoginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const adminLoginMsg = document.getElementById("adminLoginMsg");
    // tài khoản mẫu:
    if (username === "admin" && password === "admin123") {
      localStorage.setItem(ADMIN_KEY, "true");
      window.location.href = "admin.html";
    } else {
      if (adminLoginMsg) adminLoginMsg.textContent = "❌ Sai tên đăng nhập hoặc mật khẩu.";
    }
  });
}

// ---------- Student page ----------
const infoDiv = document.getElementById("info");
if (infoDiv) {
  const student = JSON.parse(localStorage.getItem(LOGGED_STUDENT));
  if (!student) {
    window.location.href = "login.html";
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
      window.location.href = "login.html";
    });
  }
}

// ---------- Admin page ----------
if (document.getElementById("adminStudentList")) {
  // kiểm tra quyền
  if (localStorage.getItem(ADMIN_KEY) !== "true") {
    window.location.href = "admin-login.html";
  } else {
    showAdminStudents();
  }

  const logoutAdmin = document.getElementById("logoutAdmin");
  if (logoutAdmin) {
    logoutAdmin.addEventListener("click", () => {
      localStorage.removeItem(ADMIN_KEY);
      window.location.href = "admin-login.html";
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
      showAdminStudents();
      showStudents();
    });
  }
}

// ---------- Khi tải trang register.html, render list ----------
showStudents();

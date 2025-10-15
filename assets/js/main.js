console.log("✅ main.js loaded");

// ---------- keys ----------
const STUDENTS_KEY = "studentsList";
const LOGGED_STUDENT = "loggedInStudent";
const ADMIN_KEY = "isAdminLoggedIn";
const ROOMS_KEY = "roomsList";
const FACILITIES_KEY = "roomFacilities";

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
    // avoid duplicate MSSV
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

// ---------- ROOMS & FACILITIES helpers ----------
function getRooms() {
  return JSON.parse(localStorage.getItem(ROOMS_KEY)) || [];
}

function saveRooms(arr) {
  localStorage.setItem(ROOMS_KEY, JSON.stringify(arr));
}

function getFacilities() {
  return JSON.parse(localStorage.getItem(FACILITIES_KEY)) || {};
}

function saveFacilities(obj) {
  localStorage.setItem(FACILITIES_KEY, JSON.stringify(obj));
}

// If no rooms/facilities exist, create sample ones (keeps id/name)
function ensureSampleRoomsAndFacilities() {
  let rooms = getRooms();
  if (!rooms || rooms.length === 0) {
    // create sample rooms 101..105
    rooms = [101, 102, 103, 104, 105].map(n => ({ id: String(n), name: String(n) }));
    saveRooms(rooms);
  }

  let facilities = getFacilities();
  if (!facilities || Object.keys(facilities).length === 0) {
    // sample facility types
    const types = ["Giường", "Tủ", "Quạt", "Bóng đèn", "Bàn"];
    facilities = {};
    rooms.forEach(r => {
      facilities[r.id] = {};
      types.forEach(t => {
        // random 0..3 for demo
        facilities[r.id][t] = Math.floor(Math.random() * 4);
      });
    });
    saveFacilities(facilities);
  }

  // Also if students have no room assigned, evenly distribute them round-robin
  const students = getStudents();
  if (students.length > 0) {
    const needAssign = students.some(s => !s.room);
    if (needAssign) {
      students.forEach((s, idx) => {
        s.room = rooms[idx % rooms.length].id;
      });
      saveStudents(students);
    }
  }
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

    const res = addOrUpdateStudent({ name, mssv, email, phone, room: "" });
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
    // if list present, re-render
    const listDiv = document.getElementById("studentList");
    if (listDiv && typeof renderStudentsTo === "function") renderStudentsTo(listDiv);
  });

  // load editStudent if present
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

// ---------- RENDER students (kept from earlier) ----------
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

// ---------- GLOBAL CLICK delegation for edit/delete (kept) ----------
document.addEventListener("click", function (e) {
  const del = e.target.closest(".deleteBtn");
  if (del) {
    const mssv = del.getAttribute("data-mssv");
    if (!mssv) return;
    if (!confirm("Xác nhận xóa sinh viên MSSV: " + mssv + " ?")) return;
    let arr = getStudents();
    arr = arr.filter(s => s.mssv !== mssv);
    saveStudents(arr);
    // re-render everything that shows students
    const adminDiv = document.getElementById("adminStudentList");
    if (adminDiv) renderStudentsTo(adminDiv);
    const listDiv = document.getElementById("studentList");
    if (listDiv) renderStudentsTo(listDiv);
    return;
  }

  const edit = e.target.closest(".editBtn");
  if (edit) {
    const mssv = edit.getAttribute("data-mssv");
    if (!mssv) return;
    const arr = getStudents();
    const student = arr.find(s => s.mssv === mssv);
    if (!student) return;
    localStorage.setItem("editStudent", JSON.stringify(student));
    // navigate to register page in same folder (this script runs from pages/*)
    const currentPath = window.location.pathname;
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
    const currentPath = window.location.pathname;
    if (currentPath.includes("/pages/")) {
      window.location.href = ".html";
    } else {
      window.location.href = "pages/.html";
    }
  } else {
    infoDiv.innerHTML = `
      <div class="card">
        <p><strong>Họ và tên:</strong> ${escapeHtml(student.name)}</p>
        <p><strong>MSSV:</strong> ${escapeHtml(student.mssv)}</p>
        <p><strong>Email:</strong> ${escapeHtml(student.email)}</p>
        <p><strong>SĐT:</strong> ${escapeHtml(student.phone || "")}</p>
        <p><strong>Phòng:</strong> ${escapeHtml(student.room || "Chưa xếp")}</p>
      </div>
    `;
  }
  const logoutBtn = document.getElementById("logoutStudent");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      localStorage.removeItem(LOGGED_STUDENT);
      const currentPath = window.location.pathname;
      if (currentPath.includes("/pages/")) {
        window.location.href = ".html";
      } else {
        window.location.href = "pages/.html";
      }
    });
  }
}

// ---------- ADMIN Dashboard UI (NEW) ----------
function initAdminDashboard() {
  // check admin permission
  if (localStorage.getItem(ADMIN_KEY) !== "true") {
    const currentPath = window.location.pathname;
    if (currentPath.includes("/pages/")) {
      window.location.href = "login-admin.html";
    } else {
      window.location.href = "pages/login-admin.html";
    }
    return;
  }

  // ensure sample rooms & facilities exist
  ensureSampleRoomsAndFacilities();

  // cache elements
  const menuItems = Array.from(document.querySelectorAll(".menu .menu-item"));
  const mainContent = document.getElementById("mainContent");
  const roomsListPanel = document.getElementById("roomsListPanel");
  const sidebarSearch = document.getElementById("sidebarSearch");
  const slidePanel = document.getElementById("slidePanel");
  const slideContent = document.getElementById("slideContent");
  const closeSlide = document.getElementById("closeSlide");

  // render rooms list panel
  function renderRoomsList() {
    const rooms = getRooms();
    roomsListPanel.innerHTML = rooms.map(r => `<div class="room-item" data-room="${r.id}">Phòng ${escapeHtml(r.name)}</div>`).join("");
  }
  renderRoomsList();

  // menu click
  menuItems.forEach(mi => {
    mi.addEventListener("click", () => {
      menuItems.forEach(x => x.classList.remove("active"));
      mi.classList.add("active");
      const view = mi.getAttribute("data-view");
      if (view === "room-students") renderRoomStudentsView();
      else if (view === "room-facilities") renderFacilitiesMatrixView();
    });
  });

  // default show first
  renderRoomStudentsView();

  // search rooms
  if (sidebarSearch) {
    sidebarSearch.addEventListener("input", (e) => {
      const q = (e.target.value || "").toLowerCase();
      const rooms = getRooms().filter(r => r.name.toLowerCase().includes(q));
      roomsListPanel.innerHTML = rooms.map(r => `<div class="room-item" data-room="${r.id}">Phòng ${escapeHtml(r.name)}</div>`).join("");
    });
    // click room item to scroll to or highlight in current view
    roomsListPanel.addEventListener("click", (ev) => {
      const ri = ev.target.closest(".room-item");
      if (!ri) return;
      const roomId = ri.getAttribute("data-room");
      // if in room-students view, scroll to row
      const targetRow = document.querySelector(`[data-roomrow="${roomId}"]`);
      if (targetRow) targetRow.scrollIntoView({ behavior: "smooth", block: "center" });
    });
  }

  // close slide
  if (closeSlide) closeSlide.addEventListener("click", () => closeSlidePanel());

  // If clicking outside panel closes it (optional)
  document.addEventListener("click", (ev) => {
    if (!slidePanel) return;
    if (!slidePanel.classList.contains("open")) return;
    if (slidePanel.contains(ev.target) || ev.target.closest(".facility-cell")) return;
    // click outside -> close
    closeSlidePanel();
  });

  // ------------ render functions ------------

  // 1) Room + Students view: table with columns Room | Students (list)
  function renderRoomStudentsView() {
    const rooms = getRooms();
    const students = getStudents();
    // create mapping room -> students
    const map = {};
    rooms.forEach(r => map[r.id] = []);
    students.forEach(s => {
      const rid = s.room || rooms.length ? (s.room || rooms[0].id) : "101";
      if (!map[rid]) map[rid] = [];
      map[rid].push(s);
    });

    let html = `<div class="view-title"><h3>Danh sách phòng + sinh viên</h3></div>`;
    html += `<table class="room-students-table"><tr><th>Phòng</th><th>Sinh viên (tên - MSSV)</th></tr>`;
    rooms.forEach(r => {
      const arr = map[r.id] || [];
      const studentsHtml = arr.length === 0 ? "<em>Chưa có</em>" : arr.map(st => `${escapeHtml(st.name)} - <strong>${escapeHtml(st.mssv)}</strong>`).join("<br>");
      html += `<tr data-roomrow="${r.id}"><td style="width:120px"><strong>Phòng ${escapeHtml(r.name)}</strong></td><td>${studentsHtml}</td></tr>`;
    });
    html += `</table>`;
    mainContent.innerHTML = html;
  }

  // 2) Room + Facilities view: matrix with rows=facilities types, cols=rooms
  function renderFacilitiesMatrixView() {
    const rooms = getRooms();
    const facilities = getFacilities(); // object: { roomId: {FacilityName: count, ...}, ... }
    // determine unique facility types (rows)
    const typesSet = new Set();
    Object.values(facilities).forEach(obj => {
      Object.keys(obj).forEach(t => typesSet.add(t));
    });
    const types = Array.from(typesSet);

    let html = `<div class="view-title"><h3>Danh sách phòng + cơ sở vật chất (ma trận)</h3>
      <p>Di chuột hoặc bấm vào ô sẽ bật bảng ảnh đồ vật ở bên phải.</p></div>`;

    html += `<table class="facilities-matrix"><thead><tr><th>Vật dụng \\ Phòng</th>`;
    rooms.forEach(r => html += `<th>Phòng ${escapeHtml(r.name)}</th>`);
    html += `</tr></thead><tbody>`;

    types.forEach(type => {
      html += `<tr><th>${escapeHtml(type)}</th>`;
      rooms.forEach(r => {
        const count = (facilities[r.id] && facilities[r.id][type]) ? facilities[r.id][type] : 0;
        html += `<td class="facility-cell" data-room="${escapeHtml(r.id)}" data-type="${escapeHtml(type)}" data-count="${count}">${count}</td>`;
      });
      html += `</tr>`;
    });

    html += `</tbody></table>`;
    mainContent.innerHTML = html;

    // attach hover / click handlers to each cell
    const cells = mainContent.querySelectorAll(".facility-cell");
    cells.forEach(cell => {
      let hoverTimer = null;
      cell.addEventListener("mouseenter", () => {
        hoverTimer = setTimeout(() => {
          const room = cell.getAttribute("data-room");
          const type = cell.getAttribute("data-type");
          const count = Number(cell.getAttribute("data-count") || 0);
          // open slide panel with images for this room/type
          openSlidePanel(room, type, count);
        }, 180); // small delay to avoid flicker
      });
      cell.addEventListener("mouseleave", () => {
        if (hoverTimer) { clearTimeout(hoverTimer); hoverTimer = null; }
        // auto-close after short delay only if not hovering slide panel; we keep it open unless user clicks outside
      });
      // click toggles
      cell.addEventListener("click", () => {
        const room = cell.getAttribute("data-room");
        const type = cell.getAttribute("data-type");
        const count = Number(cell.getAttribute("data-count") || 0);
        openSlidePanel(room, type, count);
      });
    });
  }

  // Open slide panel to show images/info for a given room + facility type
  function openSlidePanel(roomId, facilityType, count) {
    if (!slidePanel || !slideContent) return;
    slidePanel.classList.add("open");
    slidePanel.setAttribute("aria-hidden", "false");
    // build content: title + quantity + thumbnails (placeholders)
    let html = `<h3>${escapeHtml(facilityType)} — Phòng ${escapeHtml(roomId)}</h3>`;
    html += `<p>Số lượng: <strong>${count}</strong></p>`;
    html += `<div><button class="btn" id="uploadImageBtn">Upload ảnh (tạm chưa active)</button></div>`;

    // get images (placeholders)
    const imgs = getFacilityImages(roomId, facilityType, count);
    if (imgs.length === 0) {
      html += `<p style="margin-top:8px;"><em>Chưa có ảnh cho vật dụng này.</em></p>`;
    } else {
      html += `<div class="thumbs">`;
      imgs.forEach((src, idx) => {
        html += `<div class="thumb"><img src="${src}" alt="${escapeHtml(facilityType)} ${idx + 1}" /><div style="margin-top:6px;font-size:0.9rem">${escapeHtml(facilityType)} #${idx + 1}</div></div>`;
      });
      html += `</div>`;
    }

    slideContent.innerHTML = html;
    // attach uploadBtn event (currently just demo)
    const uploadBtn = document.getElementById("uploadImageBtn");
    if (uploadBtn) {
      uploadBtn.addEventListener("click", () => {
        alert("Chức năng upload ảnh sẽ được thêm sau. Hiện tại là demo.");
      });
    }
  }

  function closeSlidePanel() {
    const slide = document.getElementById("slidePanel");
    if (!slide) return;
    slide.classList.remove("open");
    slide.setAttribute("aria-hidden", "true");
    // clear content optionally
    // document.getElementById("slideContent").innerHTML = "";
  }

  // return array of dataURIs (placeholder SVG) as images for demo
  function getFacilityImages(roomId, facilityType, count) {
    // For demo: create up to 4 placeholder SVG images
    const arr = [];
    const n = Math.min(4, Math.max(1, count)); // at least 1 placeholder to show
    for (let i = 0; i < n; i++) {
      const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='400' height='250'>
        <rect width='100%' height='100%' fill='#eef6ff'/>
        <text x='50%' y='45%' dominant-baseline='middle' text-anchor='middle' font-size='22' fill='#03396c' font-family='Arial'>
          ${facilityType}
        </text>
        <text x='50%' y='60%' dominant-baseline='middle' text-anchor='middle' font-size='16' fill='#124e8c' font-family='Arial'>
          Phòng ${roomId} — #${i + 1}
        </text>
      </svg>`;
      const uri = "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg);
      arr.push(uri);
    }
    return arr;
  }
} // end initAdminDashboard

// ---------- ADMIN controls export/clear/logout (for pages/admin-dashboard.html) ----------
document.addEventListener("DOMContentLoaded", () => {
  // render any register/list elements present (compat)
  const listDiv = document.getElementById("studentList");
  if (listDiv && typeof renderStudentsTo === "function") renderStudentsTo(listDiv);

  // If current page is admin dashboard page (has #adminDashboardApp), init it
  if (document.getElementById("adminDashboardApp")) {
    try {
      initAdminDashboard();
    } catch (err) {
      console.error("Lỗi initAdminDashboard:", err);
    }
  }

  // Export / clear / logout buttons (global handling)
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
      localStorage.removeItem(ROOMS_KEY);
      localStorage.removeItem(FACILITIES_KEY);
      // re-render if on admin page
      if (document.getElementById("adminDashboardApp")) {
        initAdminDashboard(); // re-init (it will recreate sample data)
      }
      const listDiv = document.getElementById("studentList");
      if (listDiv && typeof renderStudentsTo === "function") renderStudentsTo(listDiv);
      alert("Đã xóa.");
    });
  }

  const logoutAdmin = document.getElementById("logoutAdmin");
  if (logoutAdmin) {
    logoutAdmin.addEventListener("click", () => {
      localStorage.removeItem(ADMIN_KEY);
      const currentPath = window.location.pathname;
      if (currentPath.includes("/pages/")) {
        window.location.href = "login-admin.html";
      } else {
        window.location.href = "pages/login-admin.html";
      }
    });
  }
});

// ---------- FEEDBACK (kept) ----------
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

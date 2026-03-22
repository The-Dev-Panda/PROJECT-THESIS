<?php require_once __DIR__ . '/auth_user.php'; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings – Fit-Stop</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Chakra+Petch:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <!-- Base styles (sidebar, topbar, layout, variables) -->
    <link rel="stylesheet" href="user.css" />
    <!-- Settings page specific styles -->
    <link rel="stylesheet" href="settings.css" />
  </head>

  <body>
    <div class="dashboard">
      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <img
            src="userimage/FIT-STOP LOGO.png"
            alt="Fit-Stop Logo"
            class="logo-img"
          />
          <span class="logo-text">Fit-Stop</span>
        </div>

        <ul class="menu">
          <li>
            <a href="user.php"
              ><i class="bi bi-grid-1x2"></i
              ><span data-lang="nav-dashboard">Dashboard</span></a
            >
          </li>
          <li>
            <a href="bmi.php"
              ><i class="bi bi-heart-pulse"></i><span>BMI Tracker</span></a
            >
          </li>
          <li>
            <a href="myplan.php"
              ><i class="bi bi-clipboard-check"></i><span>My Plan</span></a
            >
          </li>
          <li>
            <a href="history.php"
              ><i class="bi bi-clock-history"></i><span>History</span></a
            >
          </li>
          <li>
            <a href="payments.php"
              ><i class="bi bi-credit-card"></i><span>Payments</span></a
            >
          </li>
          <li>
            <a href="profile.php"
              ><i class="bi bi-person"></i><span>Profile</span></a
            >
          </li>
          <li class="active">
            <a href="settings.php"
              ><i class="bi bi-gear"></i><span>Settings</span></a
            >
          </li>
          <li>
            <a href="logout.php"
              ><i class="bi bi-box-arrow-right"></i><span>Logout</span></a
            >
          </li>
        </ul>
      </aside>

      <!-- MAIN -->
      <main class="main-content">
        <!-- TOPBAR -->
        <header class="topbar">
          <div class="welcome">
            <h1>Settings</h1>
            <p>Manage your app preferences and support options</p>
          </div>
        </header>

        <!-- APP PREFERENCES -->
        <div class="settings-section">
          <div class="section-header">
            <h3><i class="bi bi-bell"></i> Notifications</h3>
          </div>

          <div class="settings-card">
            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon"><i class="bi bi-fork-knife"></i></div>
                <div class="setting-info">
                  <h4>Meal Reminders</h4>
                  <p>Enable or disable meal reminder notifications</p>
                </div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" id="notificationsToggle" />
                <div class="toggle-track"></div>
                <div class="toggle-thumb"></div>
              </label>
            </div>
            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon"><i class="bi bi-activity"></i></div>
                <div class="setting-info">
                  <h4>Workout Reminders</h4>
                  <p>Enable or disable workout reminder notifications</p>
                </div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" id="notificationsToggle" />
                <div class="toggle-track"></div>
                <div class="toggle-thumb"></div>
              </label>
            </div>
            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon"><i class="bi bi-droplet"></i></div>
                <div class="setting-info">
                  <h4>Water Reminders</h4>
                  <p>Enable or disable water reminder notifications</p>
                </div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" id="notificationsToggle" />
                <div class="toggle-track"></div>
                <div class="toggle-thumb"></div>
              </label>
            </div>
            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon">
                  <i class="bi bi-moon-stars-fill"></i>
                </div>
                <div class="setting-info">
                  <h4>Dark Mode</h4>
                  <p>Currently enabled</p>
                </div>
              </div>
              <label class="toggle-wrap">
                <input type="checkbox" id="darkModeToggle" checked />
                <div class="toggle-track"></div>
                <div class="toggle-thumb"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- SUPPORT -->
        <div class="settings-section">
          <div class="section-header">
            <h3><i class="bi bi-headset"></i> Support</h3>
          </div>

          <div class="settings-card">
            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon">
                  <i class="bi bi-info-circle"></i>
                </div>
                <div class="setting-info">
                  <h4>About</h4>
                  <p>Browse about Fit-Stop</p>
                </div>
              </div>
              <button class="arrow-btn">
                View <i class="bi bi-chevron-right"></i>
              </button>
            </div>
            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon">
                  <i class="bi bi-question-circle"></i>
                </div>
                <div class="setting-info">
                  <h4>Help & FAQ</h4>
                  <p>Browse common questions and guides</p>
                </div>
              </div>
              <button class="arrow-btn">
                View <i class="bi bi-chevron-right"></i>
              </button>
            </div>

            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon"><i class="bi bi-chat-dots"></i></div>
                <div class="setting-info">
                  <h4>Contact Support</h4>
                  <p>Get help from our team</p>
                </div>
              </div>
              <button class="arrow-btn">
                Open <i class="bi bi-chevron-right"></i>
              </button>
            </div>

            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon"><i class="bi bi-star"></i></div>
                <div class="setting-info">
                  <h4>Feedback & Bug Report</h4>
                  <p>Share thoughts or report issues</p>
                </div>
              </div>
              <button class="arrow-btn">
                Send <i class="bi bi-chevron-right"></i>
              </button>
            </div>

            <div class="setting-row">
              <div class="setting-left">
                <div class="setting-icon">
                  <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="setting-info">
                  <h4>Terms & Privacy Policy</h4>
                  <p>Legal information</p>
                </div>
              </div>
              <button class="arrow-btn">
                Read <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- SAVE -->
        <div class="save-bar">
          <button class="save-btn">
            <i class="bi bi-check-lg"></i> Save Changes
          </button>
        </div>
      </main>
    </div>
    <script src="lightmode.js"></script>
  </body>
</html>
<script>
  // ── Unique IDs fix (duplicate IDs cause bugs) ──────────────────────────
  document.getElementById("notificationsToggle")?.removeAttribute("id");
  const toggleInputs = document.querySelectorAll(
    '.toggle-wrap input[type="checkbox"]',
  );
  const [mealToggle, workoutToggle, waterToggle, darkModeToggle] = toggleInputs;

  // ── localStorage keys ──────────────────────────────────────────────────
  const KEYS = {
    meal: "fitstop_meal_reminder",
    workout: "fitstop_workout_reminder",
    water: "fitstop_water_reminder",
    dark: "fitstop_dark_mode",
  };

  // ── Load saved states on page load ─────────────────────────────────────
  function loadStates() {
    mealToggle.checked = localStorage.getItem(KEYS.meal) !== "false";
    workoutToggle.checked = localStorage.getItem(KEYS.workout) !== "false";
    waterToggle.checked = localStorage.getItem(KEYS.water) !== "false";
    // Dark mode: default ON (matches your HTML's `checked` attribute)
    const darkSaved = localStorage.getItem(KEYS.dark);
    darkModeToggle.checked = darkSaved === null ? true : darkSaved === "true";
    applyDarkMode(darkModeToggle.checked);
  }

  // ── Dark mode apply ────────────────────────────────────────────────────
  function applyDarkMode(enabled) {
    document.body.classList.toggle("light-mode", !enabled);
    const darkInfo = darkModeToggle
      .closest(".setting-row")
      ?.querySelector(".setting-info p");
    if (darkInfo)
      darkInfo.textContent = enabled
        ? "Currently enabled"
        : "Currently disabled";
  }

  // ── Toggle listeners ───────────────────────────────────────────────────
  darkModeToggle.addEventListener("change", () => {
    applyDarkMode(darkModeToggle.checked);
  });

  // Meal / Workout / Water — show browser notification permission prompt if enabled
  [mealToggle, workoutToggle, waterToggle].forEach((toggle, i) => {
    const labels = ["Meal", "Workout", "Water"];
    toggle.addEventListener("change", () => {
      if (
        toggle.checked &&
        "Notification" in window &&
        Notification.permission === "default"
      ) {
        Notification.requestPermission();
      }
      showToast(
        `${labels[i]} Reminders ${toggle.checked ? "enabled" : "disabled"}`,
      );
    });
  });

  // ── Save button ────────────────────────────────────────────────────────
  document.querySelector(".save-btn").addEventListener("click", () => {
    localStorage.setItem(KEYS.meal, mealToggle.checked);
    localStorage.setItem(KEYS.workout, workoutToggle.checked);
    localStorage.setItem(KEYS.water, waterToggle.checked);
    localStorage.setItem(KEYS.dark, darkModeToggle.checked);
    showToast("Settings saved!", "success");
  });

  // ── Support buttons ────────────────────────────────────────────────────
  const arrowBtns = document.querySelectorAll(".arrow-btn");
  const supportActions = [
    {
      label: "About",
      action: () =>
        showModal(
          "About Fit-Stop",
          "Version 1.0.0\nFit-Stop is your all-in-one fitness companion.",
        ),
    },
    {
      label: "Help & FAQ",
      action: () =>
        showModal(
          "Help & FAQ",
          "Q: How do I log a meal?\nA: Go to Dashboard → Log Meal.\n\nQ: How do I change my plan?\nA: Visit My Plan page.",
        ),
    },
    { label: "Contact Support", action: () => openContactSupport() },
    { label: "Feedback & Bug Report", action: () => openFeedback() },
    {
      label: "Terms & Privacy",
      action: () =>
        showModal(
          "Terms & Privacy Policy",
          "By using Fit-Stop you agree to our Terms of Service and Privacy Policy. We do not sell your data.",
        ),
    },
  ];
  arrowBtns.forEach((btn, i) => {
    btn.addEventListener("click", supportActions[i]?.action);
  });

  // ── Modal helper ───────────────────────────────────────────────────────
  function showModal(title, body) {
    const existing = document.getElementById("fs-modal");
    if (existing) existing.remove();

    const modal = document.createElement("div");
    modal.id = "fs-modal";
    modal.innerHTML = `
      <div class="fs-modal-backdrop"></div>
      <div class="fs-modal-box">
        <h3>${title}</h3>
        <p>${body.replace(/\n/g, "<br>")}</p>
        <button class="fs-modal-close save-btn" style="margin-top:1.2rem">Close</button>
      </div>`;
    modal
      .querySelector(".fs-modal-close")
      .addEventListener("click", () => modal.remove());
    modal
      .querySelector(".fs-modal-backdrop")
      .addEventListener("click", () => modal.remove());
    document.body.appendChild(modal);
  }

  // ── Contact Support form ───────────────────────────────────────────────
  function openContactSupport() {
    const existing = document.getElementById("fs-modal");
    if (existing) existing.remove();

    const modal = document.createElement("div");
    modal.id = "fs-modal";
    modal.innerHTML = `
      <div class="fs-modal-backdrop"></div>
      <div class="fs-modal-box">
        <h3>Contact Support</h3>
        <input class="fs-input" type="text"     placeholder="Your name"    id="cs-name" />
        <input class="fs-input" type="email"    placeholder="Your email"   id="cs-email" />
        <textarea class="fs-input" rows="4"     placeholder="Describe your issue..." id="cs-msg"></textarea>
        <div style="display:flex;gap:.8rem;margin-top:1.2rem">
          <button class="save-btn"      id="cs-send">Send</button>
          <button class="arrow-btn"     id="cs-cancel">Cancel</button>
        </div>
      </div>`;
    modal
      .querySelector("#cs-cancel")
      .addEventListener("click", () => modal.remove());
    modal
      .querySelector(".fs-modal-backdrop")
      .addEventListener("click", () => modal.remove());
    modal.querySelector("#cs-send").addEventListener("click", () => {
      const name = document.getElementById("cs-name").value.trim();
      const email = document.getElementById("cs-email").value.trim();
      const msg = document.getElementById("cs-msg").value.trim();
      if (!name || !email || !msg) {
        showToast("Please fill in all fields.", "error");
        return;
      }
      modal.remove();
      showToast("Message sent! We'll get back to you shortly.", "success");
    });
    document.body.appendChild(modal);
  }

  // ── Feedback form ──────────────────────────────────────────────────────
  function openFeedback() {
    const existing = document.getElementById("fs-modal");
    if (existing) existing.remove();

    const modal = document.createElement("div");
    modal.id = "fs-modal";
    modal.innerHTML = `
      <div class="fs-modal-backdrop"></div>
      <div class="fs-modal-box">
        <h3>Feedback & Bug Report</h3>
        <select class="fs-input" id="fb-type">
          <option value="">Select type…</option>
          <option value="feedback">General Feedback</option>
          <option value="bug">Bug Report</option>
          <option value="feature">Feature Request</option>
        </select>
        <textarea class="fs-input" rows="4" placeholder="Describe your feedback or bug..." id="fb-msg"></textarea>
        <div style="display:flex;gap:.8rem;margin-top:1.2rem">
          <button class="save-btn"   id="fb-send">Send</button>
          <button class="arrow-btn"  id="fb-cancel">Cancel</button>
        </div>
      </div>`;
    modal
      .querySelector("#fb-cancel")
      .addEventListener("click", () => modal.remove());
    modal
      .querySelector(".fs-modal-backdrop")
      .addEventListener("click", () => modal.remove());
    modal.querySelector("#fb-send").addEventListener("click", () => {
      const type = document.getElementById("fb-type").value;
      const msg = document.getElementById("fb-msg").value.trim();
      if (!type || !msg) {
        showToast("Please fill in all fields.", "error");
        return;
      }
      modal.remove();
      showToast("Thank you for your feedback!", "success");
    });
    document.body.appendChild(modal);
  }

  // ── Toast notification ─────────────────────────────────────────────────
  function showToast(message, type = "info") {
    const existing = document.querySelector(".fs-toast");
    if (existing) existing.remove();

    const toast = document.createElement("div");
    toast.className = `fs-toast fs-toast--${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add("fs-toast--visible"), 10);
    setTimeout(() => {
      toast.classList.remove("fs-toast--visible");
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // ── Inline styles for modal + toast (no extra CSS file needed) ─────────
  const style = document.createElement("style");
  style.textContent = `
    #fs-modal { position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center; }
    .fs-modal-backdrop { position:absolute;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(3px); }
    .fs-modal-box { position:relative;background:#1a1a1a;border:1px solid #f5c800;border-radius:0;padding:2rem;width:min(420px,90vw);display:flex;flex-direction:column;gap:.8rem; }    .fs-modal-box h3 { color:#f5c800;font-family:'Chakra Petch',sans-serif;font-size:1.1rem;margin:0 0 .4rem; }
    .fs-modal-box p  { color:#ccc;font-size:.9rem;line-height:1.6;margin:0; }
    .fs-input { width:100%;background:#111;border:1px solid #333;border-radius:0;padding:.7rem 1rem;color:#fff;font-size:.88rem;font-family:inherit;box-sizing:border-box;transition:border-color .2s; }    .fs-input:focus { outline:none;border-color:#f5c800; }
    .fs-toast { position:fixed;bottom:2rem;right:2rem;background:#1a1a1a;border:1px solid #f5c800;color:#fff;padding:.75rem 1.4rem;border-radius:0;font-size:.88rem;opacity:0;transform:translateY(8px);transition:opacity .3s,transform .3s;z-index:99999;pointer-events:none; }    .fs-toast--visible { opacity:1;transform:translateY(0); }
    .fs-toast--success { border-color:#4caf50; }
    .fs-toast--error   { border-color:#f44336; }
  `;
  document.head.appendChild(style);

  // ── Init ───────────────────────────────────────────────────────────────
  loadStates();
</script>


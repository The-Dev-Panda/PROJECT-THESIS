<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Dashboard</title>
    <link rel="stylesheet" href="user.css" />
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
    <style>
      /* ─── AI ADVISER SECTION ─────────────────────────────────── */
      .ai-adviser-section {
        margin: 28px 0;
      }

      .ai-adviser-section .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
      }

      .ai-adviser-section .section-header h3 {
        font-family: "Chakra Petch", sans-serif;
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--text-primary, #1a1a2e);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .ai-adviser-section .section-header h3 i {
        color: #7c3aed;
      }

      .ai-adviser-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 16px;
      }

      .ai-chat-box {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 18px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        box-shadow: 0 8px 32px rgba(124, 58, 237, 0.18);
        border: 1px solid rgba(124, 58, 237, 0.25);
      }

      .ai-chat-header {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .ai-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #7c3aed, #06b6d4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 0 14px rgba(124, 58, 237, 0.5);
        animation: pulse-glow 2.5s infinite;
      }

      @keyframes pulse-glow {
        0%,
        100% {
          box-shadow: 0 0 14px rgba(124, 58, 237, 0.5);
        }
        50% {
          box-shadow: 0 0 22px rgba(124, 58, 237, 0.85);
        }
      }

      .ai-chat-header-text h4 {
        color: #fff;
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0;
      }

      .ai-chat-header-text span {
        color: #06b6d4;
        font-size: 0.72rem;
      }

      .ai-messages {
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
        min-height: 130px;
        max-height: 200px;
        overflow-y: auto;
      }

      .ai-msg {
        display: flex;
        gap: 8px;
        align-items: flex-start;
      }

      .ai-msg.user-msg {
        flex-direction: row-reverse;
      }

      .ai-msg-bubble {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px 12px 12px 4px;
        padding: 10px 14px;
        color: #e2e8f0;
        font-size: 0.83rem;
        line-height: 1.5;
        max-width: 80%;
        backdrop-filter: blur(4px);
      }

      .ai-msg.user-msg .ai-msg-bubble {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        border-radius: 12px 12px 4px 12px;
        color: #fff;
      }

      .ai-input-row {
        display: flex;
        gap: 8px;
        align-items: center;
      }

      .ai-input {
        flex: 1;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(124, 58, 237, 0.3);
        border-radius: 10px;
        padding: 10px 14px;
        color: #fff;
        font-size: 0.84rem;
        outline: none;
        transition: border 0.2s;
      }

      .ai-input::placeholder {
        color: rgba(255, 255, 255, 0.35);
      }
      .ai-input:focus {
        border-color: #7c3aed;
      }

      .ai-send-btn {
        background: linear-gradient(135deg, #7c3aed, #06b6d4);
        border: none;
        border-radius: 10px;
        padding: 10px 14px;
        color: #fff;
        cursor: pointer;
        font-size: 0.9rem;
        transition:
          opacity 0.2s,
          transform 0.15s;
      }

      .ai-send-btn:hover {
        opacity: 0.88;
        transform: scale(1.05);
      }

      .ai-suggestions-box {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
        border: 1px solid #f0f0f0;
      }

      .ai-suggestions-box h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
      }

      .suggestion-tabs {
        display: flex;
        gap: 6px;
      }

      .tab-btn {
        padding: 5px 13px;
        border-radius: 20px;
        border: 1.5px solid #e5e7eb;
        background: transparent;
        font-size: 0.77rem;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
      }

      .tab-btn.active {
        background: linear-gradient(135deg, #7c3aed, #06b6d4);
        border-color: transparent;
        color: #fff;
      }

      .suggestion-cards {
        display: flex;
        flex-direction: column;
        gap: 9px;
      }

      .suggestion-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        border-radius: 12px;
        background: #f8f7ff;
        border: 1px solid #ede9fe;
        cursor: pointer;
        transition:
          background 0.2s,
          transform 0.15s;
      }

      .suggestion-card:hover {
        background: #ede9fe;
        transform: translateX(4px);
      }

      .suggestion-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
      }

      .suggestion-icon.workout {
        background: #fef3c7;
        color: #d97706;
      }
      .suggestion-icon.meal {
        background: #dcfce7;
        color: #16a34a;
      }
      .suggestion-icon.rest {
        background: #e0e7ff;
        color: #4f46e5;
      }

      .suggestion-text h5 {
        font-size: 0.82rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0 0 2px;
      }

      .suggestion-text p {
        font-size: 0.73rem;
        color: #6b7280;
        margin: 0;
      }

      /* ─── LEADERBOARD SECTION ────────────────────────────────── */
      .leaderboard-section {
        margin: 28px 0;
      }

      .leaderboard-section .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
      }

      .leaderboard-section .section-header h3 {
        font-family: "Chakra Petch", sans-serif;
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--text-primary, #1a1a2e);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .leaderboard-section .section-header h3 i {
        color: #f59e0b;
      }

      .leaderboard-filter {
        display: flex;
        gap: 6px;
      }

      .filter-btn {
        padding: 5px 14px;
        border-radius: 20px;
        border: 1.5px solid #e5e7eb;
        background: transparent;
        font-size: 0.76rem;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
      }

      .filter-btn.active {
        background: #f59e0b;
        border-color: #f59e0b;
        color: #fff;
      }

      .leaderboard-grid {
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 16px;
      }

      /* Podium */
      .podium-box {
        background: linear-gradient(160deg, #1a1a2e 0%, #0f3460 100%);
        border-radius: 18px;
        padding: 24px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 18px;
        box-shadow: 0 8px 32px rgba(245, 158, 11, 0.15);
        border: 1px solid rgba(245, 158, 11, 0.2);
      }

      .podium-title {
        color: #f59e0b;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
      }

      .podium-row {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 10px;
        width: 100%;
      }

      .podium-member {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
      }

      .podium-member .avatar {
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f59e0b;
      }

      .podium-member.rank-1 .avatar {
        width: 58px;
        height: 58px;
      }
      .podium-member.rank-2 .avatar,
      .podium-member.rank-3 .avatar {
        width: 46px;
        height: 46px;
        border-color: rgba(245, 158, 11, 0.5);
      }

      .podium-member .member-name {
        font-size: 0.72rem;
        color: #e2e8f0;
        font-weight: 600;
        text-align: center;
      }

      .podium-member .member-days {
        font-size: 0.67rem;
        color: #94a3b8;
      }

      .podium-stand {
        width: 60px;
        border-radius: 6px 6px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
      }

      .podium-stand.p1 {
        height: 64px;
        background: linear-gradient(180deg, #f59e0b, #d97706);
      }
      .podium-stand.p2 {
        height: 48px;
        background: linear-gradient(180deg, #94a3b8, #64748b);
      }
      .podium-stand.p3 {
        height: 38px;
        background: linear-gradient(180deg, #cd7f32, #92400e);
      }

      .podium-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
      }

      /* Rankings list */
      .rankings-box {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        gap: 0;
      }

      .ranking-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 4px;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.15s;
        border-radius: 8px;
      }

      .ranking-row:last-child {
        border-bottom: none;
      }
      .ranking-row:hover {
        background: #fafafa;
      }
      .ranking-row.highlight {
        background: #fffbeb;
        border-left: 3px solid #f59e0b;
        padding-left: 10px;
      }

      .rank-num {
        font-size: 0.8rem;
        font-weight: 700;
        color: #94a3b8;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
      }

      .rank-num.top {
        color: #f59e0b;
      }

      .rank-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        background: linear-gradient(135deg, #7c3aed20, #06b6d420);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: #7c3aed;
        font-weight: 600;
        border: 2px solid #ede9fe;
      }

      .rank-info {
        flex: 1;
      }
      .rank-info .rname {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1a1a2e;
        display: block;
      }
      .rank-info .rsub {
        font-size: 0.72rem;
        color: #94a3b8;
      }

      .rank-score {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
      }

      .rank-score .rscore {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1a1a2e;
      }
      .rank-score .rbadge {
        font-size: 0.65rem;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 500;
      }

      .rbadge.up {
        background: #dcfce7;
        color: #16a34a;
      }
      .rbadge.down {
        background: #fee2e2;
        color: #dc2626;
      }
      .rbadge.same {
        background: #f3f4f6;
        color: #6b7280;
      }

      /* QR Card */
      .qr-card {
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        border-radius: 18px;
        padding: 26px;
        color: #fff;
        display: flex;
        gap: 20px;
        align-items: center;
        box-shadow: 0 8px 28px rgba(6, 182, 212, 0.25);
      }

      .qr-placeholder {
        width: 90px;
        height: 90px;
        background: #fff;
        border-radius: 12px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #0ea5e9;
      }

      .qr-info h4 {
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 6px;
      }
      .qr-info p {
        font-size: 0.8rem;
        opacity: 0.85;
        margin: 0 0 14px;
        line-height: 1.5;
      }

      .qr-scan-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        color: #0ea5e9;
        border: none;
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
      }

      .qr-scan-btn:hover {
        opacity: 0.88;
      }
      /* ─── ATTENDANCE LOGS & E-RECEIPTS ───────────────────────── */
      .member-records-section {
        margin: 28px 0;
      }

      .member-records-section .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
      }

      .member-records-section .section-header h3 {
        font-family: "Chakra Petch", sans-serif;
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--text-primary, #1a1a2e);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .member-records-section .section-header h3 i {
        color: #10b981;
      }

      .records-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
      }

      .attendance-log-box,
      .ereceipt-box {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .attendance-log-box h4,
      .ereceipt-box h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .log-entry {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 12px;
        background: #f8fafb;
        border-radius: 10px;
        border-left: 3px solid #10b981;
      }

      .log-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10b981;
        flex-shrink: 0;
      }

      .log-info {
        flex: 1;
      }
      .log-info .log-date {
        font-size: 0.82rem;
        font-weight: 600;
        color: #1a1a2e;
        display: block;
      }
      .log-info .log-time {
        font-size: 0.72rem;
        color: #94a3b8;
      }
      .log-status {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 10px;
        background: #dcfce7;
        color: #16a34a;
      }

      .receipt-entry {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 12px;
        background: #f8fafb;
        border-radius: 10px;
      }

      .receipt-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #ecfdf5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #10b981;
        font-size: 1rem;
        flex-shrink: 0;
      }

      .receipt-info {
        flex: 1;
      }
      .receipt-info .rname {
        font-size: 0.82rem;
        font-weight: 600;
        color: #1a1a2e;
        display: block;
      }
      .receipt-info .rdate {
        font-size: 0.72rem;
        color: #94a3b8;
      }
      .receipt-amount {
        font-size: 0.88rem;
        font-weight: 700;
        color: #10b981;
      }

      .view-all-link {
        font-size: 0.78rem;
        color: #7c3aed;
        font-weight: 500;
        text-decoration: none;
        align-self: flex-end;
        display: flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
      }

      .view-all-link:hover {
        text-decoration: underline;
      }

      /* SECTION DIVIDER */
      .section-divider {
        border: none;
        border-top: 1.5px solid #f0f0f0;
        margin: 8px 0;
      }
    </style>
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
          <li   >
            <a href="user.php"
              ><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a
            >
          </li>
          <li class="active">
            <a href="bmi.html"
              ><i class="bi bi-heart-pulse"></i><span>BMI Tracker</span></a
            >
          </li>
          <li>
            <a href="myplan.html"
              ><i class="bi bi-clipboard-check"></i><span>My Plan</span></a
            >
          </li>
          <li>
            <a href="history.html"
              ><i class="bi bi-clock-history"></i
              ><span>Exercise History</span></a
            >
          </li>
          <li>
            <a href="payments.html"
              ><i class="bi bi-credit-card"></i><span>Payments</span></a
            >
          </li>
          <li>
            <a href="profile.html"
              ><i class="bi bi-person"></i><span>Profile</span></a
            >
          </li>
          <li>
            <a href="settings.html"
              ><i class="bi bi-gear"></i><span>Settings</span></a
            >
          </li>
          <li>
            <a href="logout.html"
              ><i class="bi bi-box-arrow-right"></i><span>Logout</span></a
            >
          </li>
        </ul>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
          <div class="welcome">
            <h1>Hey Sharien!</h1>
            <p>You are doing great so far</p>
          </div>
          <div class="search-container">
            <div class="search-wrapper">
              <i class="fas fa-search search-icon"></i>
              <input
                type="text"
                class="search-input"
                placeholder="What is your goal today?"
              />
            </div>
            <button class="search-btn">Search</button>
          </div>
          <div class="profile-container">
            <div class="profile-content">
              <div class="profile-text">
                <strong class="profile-name">Sharien Salarda</strong>
                <span class="profile-streak">🔥 Streaks • 135 days</span>
              </div>
              <div class="profile-pic">
                <img
                  src="userimage/andrea.jpg"
                  alt="User Profile"
                  class="profile-image"
                />
              </div>
            </div>
          </div>
        </header>

        <!-- NOTIFICATION BANNER -->
        <section class="notifications">
          <div class="notification-card attendance">
            <i class="bi bi-check-circle-fill"></i>
            <div class="notification-content">
              <h4>Attendance Confirmed</h4>
              <p>Check-in successful • Today at 6:30 AM</p>
            </div>
          </div>
          <div class="notification-card payment">
            <i class="bi bi-credit-card-fill"></i>
            <div class="notification-content">
              <h4>Payment Due Soon</h4>
              <p>Monthly membership renews in 5 days • $49.99</p>
            </div>
            <button class="notify-btn">Pay Now</button>
          </div>
        </section>

        <!-- BMI TRACKER SECTION -->
        <section class="bmi-section">
          <div class="bmi-card main-bmi">
            <div class="bmi-header">
              <h3>Your BMI Analysis</h3>
              <span class="bmi-badge healthy">Healthy</span>
            </div>
            <div class="bmi-display">
              <div class="bmi-circle">
                <div class="bmi-value">23.87</div>
                <span class="bmi-unit">kg/m²</span>
              </div>
              <div class="bmi-info">
                <div class="info-row">
                  <span class="label">Height:</span
                  ><span class="value">165 cm</span>
                </div>
                <div class="info-row">
                  <span class="label">Weight:</span
                  ><span class="value">65 kg</span>
                </div>
                <div class="info-row">
                  <span class="label">Target:</span
                  ><span class="value">60 kg</span>
                </div>
                <div class="info-row">
                  <span class="label">To Goal:</span
                  ><span class="value">-5 kg</span>
                </div>
              </div>
            </div>
            <div class="bmi-scale">
              <div class="scale-bar">
                <div class="scale-marker" style="left: 61%"></div>
              </div>
              <div class="scale-labels">
                <span>Underweight</span><span>Normal</span
                ><span>Overweight</span><span>Obese</span>
              </div>
            </div>
          </div>

          <div class="bmi-card progress-chart">
            <h3>Weight Progress</h3>
            <div class="chart-area">
              <div class="chart-line"></div>
            </div>
            <div class="progress-stats">
              <div class="stat-item">
                <span class="stat-label">This Month</span>
                <span class="stat-value">-2.3 kg</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Total Lost</span>
                <span class="stat-value">-8.7 kg</span>
              </div>
            </div>
          </div>

          <div class="bmi-card quick-stats">
            <h3>Today's Stats</h3>
            <div class="quick-stat-grid">
              <div class="quick-stat">
                <i class="fas fa-fire"></i>
                <div>
                  <span class="stat-number">1,458</span
                  ><span class="stat-text">Calories Burned</span>
                </div>
              </div>
              <div class="quick-stat">
                <i class="fas fa-shoe-prints"></i>
                <div>
                  <span class="stat-number">13,946</span
                  ><span class="stat-text">Steps</span>
                </div>
              </div>
              <div class="quick-stat">
                <i class="fas fa-moon"></i>
                <div>
                  <span class="stat-number">8h 54m</span
                  ><span class="stat-text">Sleep</span>
                </div>
              </div>
              <div class="quick-stat">
                <i class="fas fa-tint"></i>
                <div>
                  <span class="stat-number">2.1 L</span
                  ><span class="stat-text">Water Intake</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- EXERCISE HISTORY -->
        <div class="history-grid">
          <div class="history-card">
            <div class="history-date">
              <span class="date-day">Feb 7</span>
              <span class="date-full">Thursday</span>
            </div>
            <div class="history-workout">
              <div class="workout-icon cardio">
                <i class="fas fa-heartbeat"></i>
              </div>
              <div class="workout-details">
                <h4>Cardio & Abs</h4>
                <p>6:00 AM - 6:45 AM</p>
                <div class="workout-stats-mini">
                  <span><i class="fas fa-fire"></i> 380 cal</span>
                  <span><i class="fas fa-clock"></i> 45 mins</span>
                </div>
              </div>
            </div>
            <span class="completion-badge completed">Completed</span>
          </div>

          <div class="history-card">
            <div class="history-date">
              <span class="date-day">Feb 6</span>
              <span class="date-full">Wednesday</span>
            </div>
            <div class="history-workout">
              <div class="workout-icon back"><i class="fas fa-user"></i></div>
              <div class="workout-details">
                <h4>Back & Biceps</h4>
                <p>7:00 AM - 8:15 AM</p>
                <div class="workout-stats-mini">
                  <span><i class="fas fa-fire"></i> 465 cal</span>
                  <span
                    ><i class="fas fa-weight-hanging"></i> 2,890 kg total</span
                  >
                </div>
              </div>
            </div>
            <span class="completion-badge completed">Completed</span>
          </div>
        </div>
    <script src="lightmode.js"></script>
    <script>
      /* ── AI Adviser Chat ── */
      function sendAIMessage() {
        const input = document.getElementById("aiInput");
        const msgs = document.getElementById("aiMessages");
        const text = input.value.trim();
        if (!text) return;

        // User bubble
        msgs.innerHTML += `<div class="ai-msg user-msg"><div class="ai-msg-bubble">${text}</div></div>`;
        input.value = "";

        // Simulated AI response
        setTimeout(() => {
          const replies = [
            "Great question! Based on your BMI and fitness history, I'd recommend focusing on compound movements for faster fat loss. 💪",
            "Looking at your nutrition data, you're slightly low on protein today. Try adding a boiled egg or some Greek yogurt to your next meal! 🥚",
            "Your sleep stats look fantastic at 8h 54m! Make sure to stay consistent — quality sleep accelerates muscle recovery. 😴",
            "You're so close to your water goal! Drink 400ml more to hit 2.5 L today. 💧",
          ];
          const reply = replies[Math.floor(Math.random() * replies.length)];
          msgs.innerHTML += `<div class="ai-msg"><div class="ai-msg-bubble">${reply}</div></div>`;
          msgs.scrollTop = msgs.scrollHeight;
        }, 700);

        msgs.scrollTop = msgs.scrollHeight;
      }

      document.getElementById("aiInput").addEventListener("keydown", (e) => {
        if (e.key === "Enter") sendAIMessage();
      });

      /* ── Suggestion Tabs ── */
      function switchTab(btn, tab) {
        document
          .querySelectorAll(".tab-btn")
          .forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        document.querySelectorAll(".suggestion-card").forEach((card) => {
          card.style.display = card.dataset.tab === tab ? "flex" : "none";
        });
      }

      /* ── Star Rating ── */
      function setRating(n) {
        document.querySelectorAll(".star-btn").forEach((btn, i) => {
          btn.classList.toggle("active", i < n);
        });
      }

      /* ── Leaderboard Filter ── */
      document.querySelectorAll(".filter-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
          document
            .querySelectorAll(".filter-btn")
            .forEach((b) => b.classList.remove("active"));
          btn.classList.add("active");
        });
      });
    </script>
  </body>
</html>

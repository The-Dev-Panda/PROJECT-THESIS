<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Exercise History</title>
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
  </head>
  <body>
    <div class="dashboard">
      <!-- SIDEBAR -->
      <aside class="sidebar">
        <!-- LOGO -->
        <div class="sidebar-header">
          <img
            src="userimage/FIT-STOP LOGO.png"
            alt="Fit-Stop Logo"
            class="logo-img"
          />
          <span class="logo-text">Fit-Stop</span>
        </div>
        <!-- MENU -->
        <ul class="menu">
          <li>
            <a href="user.html">
              <i class="bi bi-grid-1x2"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="bmi.html">
              <i class="bi bi-heart-pulse"></i>
              <span>BMI Tracker</span>
            </a>
          </li>

          <li>
            <a href="myplan.html">
              <i class="bi bi-clipboard-check"></i>
              <span>My Plan</span>
            </a>
          </li>

          <li class="active">
            <a href="history.html">
              <i class="bi bi-clock-history"></i>
              <span>History</span>
            </a>
          </li>

          <li>
            <a href="payments.html">
              <i class="bi bi-credit-card"></i>
              <span>Payments</span>
            </a>
          </li>

          <li>
            <a href="profile.html">
              <i class="bi bi-person"></i>
              <span>Profile</span>
            </a>
          </li>

          <li>
            <a href="settings.html">
              <i class="bi bi-gear"></i>
              <span>Settings</span>
            </a>
          </li>

          <li>
            <a href="logout.html">
              <i class="bi bi-box-arrow-right"></i>
              <span>Logout</span>
            </a>
          </li>
        </ul>
      </aside>
      <!-- MAIN CONTENT -->
      <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
          <div class="welcome">
            <h1>History</h1>
            <p>Hi Linda! This is all your history for the past week.</p>
          </div>

          <div class="search-container">
            <div>
              <i class="fas fa-search search-icon"></i>
              <input
                type="text"
                class="search-input"
                placeholder="What is your goal today?"
              />
            </div>
            <button class="search-btn">Search</button>
          </div>
        </header>

        <!-- EXERCISE HISTORY SECTION -->
        <section class="history-section">
          <div class="section-header">
            <h3>Exercise History</h3>
            <div class="filter-buttons">
              <button class="filter-btn active">Week</button>
              <button class="filter-btn">Month</button>
              <button class="filter-btn">Year</button>
            </div>
          </div>

          <div class="history-grid">
            <div class="history-card">
              <div class="history-date">
                <span class="date-day">Today</span>
                <span class="date-full">Feb 9, 2026</span>
              </div>
              <div class="history-workout">
                <div class="workout-icon chest">
                  <i class="fas fa-dumbbell"></i>
                </div>
                <div class="workout-details">
                  <h4>Chest & Triceps</h4>
                  <p>6:30 AM - 7:45 AM</p>
                  <div class="workout-stats-mini">
                    <span><i class="fas fa-fire"></i> 420 cal</span>
                    <span
                      ><i class="fas fa-weight-hanging"></i> 2,450 kg
                      total</span
                    >
                  </div>
                </div>
              </div>
              <span class="completion-badge completed">Completed</span>
            </div>

            <div class="history-card">
              <div class="history-date">
                <span class="date-day">Yesterday</span>
                <span class="date-full">Feb 8, 2026</span>
              </div>
              <div class="history-workout">
                <div class="workout-icon legs">
                  <i class="fas fa-running"></i>
                </div>
                <div class="workout-details">
                  <h4>Leg Day</h4>
                  <p>5:00 PM - 6:30 PM</p>
                  <div class="workout-stats-mini">
                    <span><i class="fas fa-fire"></i> 580 cal</span>
                    <span
                      ><i class="fas fa-weight-hanging"></i> 3,200 kg
                      total</span
                    >
                  </div>
                </div>
              </div>
              <span class="completion-badge completed">Completed</span>
            </div>

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
                <div class="workout-icon back">
                  <i class="fas fa-user"></i>
                </div>
                <div class="workout-details">
                  <h4>Back & Biceps</h4>
                  <p>7:00 AM - 8:15 AM</p>
                  <div class="workout-stats-mini">
                    <span><i class="fas fa-fire"></i> 465 cal</span>
                    <span
                      ><i class="fas fa-weight-hanging"></i> 2,890 kg
                      total</span
                    >
                  </div>
                </div>
              </div>
              <span class="completion-badge completed">Completed</span>
            </div>
          </div>
        </section>
      </main>
    </div>
    <script src="lightmode.js"></script>
  </body>
</html>

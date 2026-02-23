<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard - Fit-Stop Gym</title>
  <link rel="stylesheet" href="staff.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="dashboard">

  
  <aside class="sidebar">
   
    <div class="sidebar-header">
      <img src="staffimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img">
      <span class="logo-text">Fit-Stop</span>
    </div>

 
    <ul class="menu">
  <li class="active" id="dashboardBtn" data-target="dashboard">
    <i class="bi bi-speedometer2"></i>
    <span>Dashboard</span>
  </li>

  <li id="clientRegBtn" data-target="clientRegistration">
    <i class="bi bi-person-plus"></i>
    <span>Client Registration</span>
  </li>

  <li id="inventoryBtn" data-target="inventory">
    <i class="bi bi-box-seam"></i>
    <span>Inventory Management</span>
  </li>

  <li id="attendanceBtn" data-target="attendance">
    <i class="bi bi-clipboard-check"></i>
    <span>Attendance Tracking</span>
  </li>

  <li id="memberBtn" data-target="memberManagement">
    <i class="bi bi-people"></i>
    <span>Member Management</span>
  </li>

  <li id="idGenBtn" data-target="idGeneration">
    <i class="bi bi-qr-code"></i>
    <span>ID Generation</span>
  </li>

  <li id="settingsBtn" data-target="settings">
    <i class="bi bi-gear"></i>
    <span>Settings</span>
  </li>

  <li id="logoutBtn">
    <i class="bi bi-box-arrow-right"></i>
    <span>Logout</span>
  </li>
</ul>
  </aside>

  <script>
  document.querySelectorAll(".menu li").forEach(item => {
    item.addEventListener("click", function () {

      const targetId = this.getAttribute("data-target");

      if (targetId) {
        document.getElementById(targetId).scrollIntoView({
          behavior: "smooth"
        });
      }

      document.querySelectorAll(".menu li").forEach(li => 
        li.classList.remove("active")
      );

      this.classList.add("active");
    });
  });
</script>

  <main class="main-content">

<section id="dashboard">

<div class="profile-container">
  
    <div class="profile-container">
      <div class="profile-content">
        <div class="profile-text">  
          <strong class="profile-name">Staff Portal</strong>
          <span class="profile-streak">🏋️ Active Staff Member</span>
        </div>
      </div>
    </div>

 
    <section class="quick-actions">
      <h2>Quick Actions</h2>
      <div class="actions-grid">
        <div class="action-card">
          <i class="bi bi-person-plus-fill"></i>
          <h3>Register New Member</h3>
          <p>Fast client data capture</p>
          <button class="action-btn">Start Registration</button>
        </div>
        <div class="action-card">
          <i class="bi bi-qr-code-scan"></i>
          <h3>Scan Attendance</h3>
          <p>Track member check-ins</p>
         <button class="action-btn" onclick="startScanner()">Scan QR Code</button>
        <div id="reader" style="width:300px; margin-top:15px;"></div>
        </div>
        <div class="action-card">
          <i class="bi bi-box-seam-fill"></i>
          <h3>Update Inventory</h3>
          <p>Manage equipment stock</p>
         <a href="inventory.php" class="action-btn">View Inventory</a>
        </div>
        <div class="action-card">
          <i class="bi bi-card-checklist"></i>
          <h3>Generate ID</h3>
          <p>System-generated member IDs</p>
          <button class="action-btn">Create ID</button>
        </div>
      </div>
    </section>

    <section class="statistics">
      <h2>Today's Overview</h2>
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-icon members">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">47</span>
            <span class="stat-label">Members Checked In</span>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon registrations">
            <i class="bi bi-person-check-fill"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">5</span>
            <span class="stat-label">New Registrations</span>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon equipment">
            <i class="bi bi-tools"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">2</span>
            <span class="stat-label">Equipment Issues</span>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon notifications">
            <i class="bi bi-bell-fill"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">12</span>
            <span class="stat-label">Pending Notifications</span>
          </div>
        </div>
      </div>
    </section>
</section>
   <section class="registration-section" id="clientRegistration">  
      <h2>Client Registration</h2>
      <div class="registration-card">
       <form class="registration-form" id="registrationForm">
          <div class="form-grid">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" placeholder="Enter client name" class="form-input">
            </div>
            <div class="form-group">
              <label>Age</label>
              <input type="number" placeholder="Enter age" class="form-input">
            </div>
            <div class="form-group">
              <label>Contact Number</label>
              <input type="tel" placeholder="09XXXXXXXXX" class="form-input">
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" placeholder="email@example.com" class="form-input">
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" placeholder="Complete address" class="form-input">
            </div>
            <div class="form-group">
              <label>Membership Package</label>
              <select class="form-input">
                <option>Bronze Plan</option>
                <option>Silver Plan</option>
                <option>Gold Plan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Height (cm)</label>
              <input type="number" placeholder="Enter height" class="form-input">
            </div>
            <div class="form-group">
              <label>Weight (kg)</label>
              <input type="number" placeholder="Enter weight" class="form-input">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-secondary" id="clearBtn">Clear Form</button>
            <button type="submit" class="btn-primary">Register & Generate ID</button>
          </div>
        </form>
      </div>
    </section>

    <!-- MEMBERSHIP PAYMENT SECTION -->
<section class="registration-section">
  <h2>Membership Payments</h2>
  <div class="registration-card">
    <form class="registration-form">
      <div class="form-grid">
        <div class="form-group">
          <label>Member ID</label>
          <input type="text" id="paymentMemberID" class="form-input">
        </div>
        <div class="form-group">
          <label>Amount</label>
          <input type="number" id="paymentAmount" class="form-input">
        </div>
        <div class="form-group">
          <label>Payment Method</label>
          <select class="form-input">
            <option>Cash</option>
            <option>GCash</option>
            <option>Bank Transfer</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn-primary" onclick="processPayment()">Record Payment</button>
      </div>
    </form>
  </div>
</section>
    <section class="inventory-section" id="inventory">
      <h2>Inventory Management</h2>
      
      <div class="inventory-header">
        <div class="search-container">
          <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search equipment...">
          </div>
          <button class="search-btn">Search</button>
        </div>
        <button class="add-btn"><i class="bi bi-plus-circle"></i> Add Equipment</button>
      </div>
      <div class="inventory-table">
        <table>
          <thead>
            <tr>
              <th>Equipment ID</th>
              <th>Equipment Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Status</th>
              <th>Last Maintenance</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#EQ001</td>
              <td>Treadmill Pro X5</td>
              <td>Cardio</td>
              <td>8</td>
              <td><span class="status-badge active">Active</span></td>
              <td>Jan 15, 2025</td>
              <td>
                <button class="btn-icon"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon"><i class="bi bi-qr-code"></i></button>
              </td>
            </tr>
            <tr>
              <td>#EQ002</td>
              <td>Dumbbells Set</td>
              <td>Strength</td>
              <td>45</td>
              <td><span class="status-badge active">Active</span></td>
              <td>Jan 10, 2025</td>
              <td>
                <button class="btn-icon"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon"><i class="bi bi-qr-code"></i></button>
              </td>
            </tr>
            <tr>
              <td>#EQ003</td>
              <td>Stationary Bike</td>
              <td>Cardio</td>
              <td>5</td>
              <td><span class="status-badge maintenance">Maintenance</span></td>
              <td>Dec 28, 2024</td>
              <td>
                <button class="btn-icon"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon"><i class="bi bi-qr-code"></i></button>
              </td>
            </tr>
            <tr>
              <td>#EQ004</td>
              <td>Rowing Machine</td>
              <td>Cardio</td>
              <td>3</td>
              <td><span class="status-badge low-stock">Low Stock</span></td>
              <td>Jan 05, 2025</td>
              <td>
                <button class="btn-icon"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon"><i class="bi bi-qr-code"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ATTENDANCE TRACKING -->
   <section class="attendance-section" id="attendance">
  <h2>Workout / Performance Log</h2>
  <div class="registration-card">
    <div class="form-grid">
      <div class="form-group">
        <label>Member ID</label>
        <input type="text" id="perfID" class="form-input">
      </div>
      <div class="form-group">
        <label>Exercise</label>
        <input type="text" id="exercise" class="form-input">
      </div>
      <div class="form-group">
        <label>Sets</label>
        <input type="number" id="sets" class="form-input">
      </div>
      <div class="form-group">
        <label>Reps</label>
        <input type="number" id="reps" class="form-input">
      </div>
    </div>
    <div class="form-actions">
      <button class="btn-primary" onclick="logWorkout()">Save Workout</button>
    </div>
  </div>

  <div id="workoutLogs" style="margin-top:20px;"></div>
</section>
      <h2>Real-Time Attendance</h2>
      <div class="attendance-grid">
        <div class="attendance-card">
          <h3>Recent Check-Ins</h3>
          <div class="attendance-list">
            <div class="attendance-item">
              <div class="member-info">
                <div class="member-avatar">JS</div>
                <div>
                  <strong>John Smith</strong>
                  <span class="time">2 mins ago</span>
                </div>
              </div>
              <span class="check-in-badge">Check-In</span>
            </div>
            <div class="attendance-item">
              <div class="member-info">
                <div class="member-avatar">MJ</div>
                <div>
                  <strong>Maria Johnson</strong>
                  <span class="time">5 mins ago</span>
                </div>
              </div>
              <span class="check-in-badge">Check-In</span>
            </div>
            <div class="attendance-item">
              <div class="member-info">
                <div class="member-avatar">RD</div>
                <div>
                  <strong>Robert Davis</strong>
                  <span class="time">8 mins ago</span>
                </div>
              </div>
              <span class="check-out-badge">Check-Out</span>
            </div>
            <div class="attendance-item">
              <div class="member-info">
                <div class="member-avatar">LW</div>
                <div>
                  <strong>Linda Wilson</strong>
                  <span class="time">12 mins ago</span>
                </div>
              </div>
              <span class="check-in-badge">Check-In</span>
            </div>
          </div>
        </div>

        <div class="attendance-card">
          <h3>Attendance Summary</h3>
          <div class="summary-chart">
            <div class="chart-item">
              <div class="chart-bar" style="height: 70%"></div>
              <span>Mon</span>
            </div>
            <div class="chart-item">
              <div class="chart-bar" style="height: 85%"></div>
              <span>Tue</span>
            </div>
            <div class="chart-item">
              <div class="chart-bar" style="height: 60%"></div>
              <span>Wed</span>
            </div>
            <div class="chart-item">
              <div class="chart-bar" style="height: 90%"></div>
              <span>Thu</span>
            </div>
            <div class="chart-item">
              <div class="chart-bar active" style="height: 95%"></div>
              <span>Fri</span>
            </div>
            <div class="chart-item">
              <div class="chart-bar" style="height: 75%"></div>
              <span>Sat</span>
            </div>
            <div class="chart-item">
              <div class="chart-bar" style="height: 65%"></div>
              <span>Sun</span>
            </div>
          </div>
        </div>
      </div>
    </section>


    <section class="members-section" id="memberManagement">
      <h2>Active Members Management</h2>
      <div class="members-grid">
        <div class="member-card">
          <img src="staffimage/kevin.jpg" alt="Member" class="member-img">
          <h4>KEVIN BARRETTO</h4>
          <p class="member-id">ID: #MB2024001</p>
          <div class="member-details">
            <span><i class="bi bi-calendar"></i> Gold Plan</span>
            <span><i class="bi bi-fire"></i> 45 days streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item">
              <span class="label">BMI</span>
              <span class="value">22.5</span>
            </div>
            <div class="stat-item">
              <span class="label">Sessions</span>
              <span class="value">68</span>
            </div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>

        <div class="member-card">
          <img src="staffimage/cj.jpg" alt="Member" class="member-img">
          <h4>CHARLES CARILLO</h4>
          <p class="member-id">ID: #MB2024002</p>
          <div class="member-details">
            <span><i class="bi bi-calendar"></i> Silver Plan</span>
            <span><i class="bi bi-fire"></i> 28 days streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item">
              <span class="label">BMI</span>
              <span class="value">24.1</span>
            </div>
            <div class="stat-item">
              <span class="label">Sessions</span>
              <span class="value">42</span>
            </div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>

        <div class="member-card">
          <img src="staffimage/sha.jpg" alt="Member" class="member-img">
          <h4>SHARIEN SALARDA</h4>
          <p class="member-id">ID: #MB2024003</p>
          <div class="member-details">
            <span><i class="bi bi-calendar"></i> Gold Plan</span>
            <span><i class="bi bi-fire"></i> 92 days streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item">
              <span class="label">BMI</span>
              <span class="value">21.8</span>
            </div>
            <div class="stat-item">
              <span class="label">Sessions</span>
              <span class="value">135</span>
            </div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>

        <div class="member-card">
          <img src="staffimage/lance.jpg" alt="Member" class="member-img">
          <h4>LANCE CHUA</h4>
          <p class="member-id">ID: #MB2024004</p>
          <div class="member-details">
            <span><i class="bi bi-calendar"></i> Bronze Plan</span>
            <span><i class="bi bi-fire"></i> 15 days streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item">
              <span class="label">BMI</span>
              <span class="value">26.3</span>
            </div>
            <div class="stat-item">
              <span class="label">Sessions</span>
              <span class="value">18</span>
            </div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>
      </div>
    </section>

    <section id="settings">
  <h2>Settings</h2>
  <p>System settings will be configured here.</p>
</section>

    <section class="notifications-section">
      <h2>Pending Notifications</h2>
      <div class="notifications-list">
        <div class="notification-item priority-high">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            <strong>Equipment Maintenance Required</strong>
            <p>Stationary Bike #EQ003 needs immediate attention</p>
            <span class="notification-time">30 mins ago</span>
          </div>
        </div>
        <div class="notification-item priority-medium">
          <i class="bi bi-calendar-event"></i>
          <div>
            <strong>Membership Renewal Reminder</strong>
            <p>5 members' subscriptions expiring in 3 days</p>
            <span class="notification-time">1 hour ago</span>
          </div>
        </div>
        <div class="notification-item priority-low">
          <i class="bi bi-box-seam"></i>
          <div>
            <strong>Inventory Low Stock Alert</strong>
            <p>Rowing Machine quantity below minimum threshold</p>
            <span class="notification-time">2 hours ago</span>
          </div>
        </div>
      </div>
    </section>

  </main>
</div>
<script>
  document.getElementById("clearBtn").addEventListener("click", function() {
      document.getElementById("registrationForm").reset();
  });
</script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
function startScanner() {

  const readerDiv = document.getElementById("reader");
  readerDiv.innerHTML = ""; 

  const html5QrCode = new Html5Qrcode("reader");

  html5QrCode.start(
    { facingMode: "environment" }, 
    {
      fps: 10,
      qrbox: 250
    },
    qrCodeMessage => {

      alert("Scanned Member ID: " + qrCodeMessage);

      html5QrCode.stop();
    },
    errorMessage => {
    }
  ).catch(err => {
    alert("Camera Error: " + err);
  });
}
</script>
<script>
function goToInventory() {
  document.getElementById("inventory").scrollIntoView({
    behavior: "smooth"
  });
}
</script>
<script>

function logAttendance(memberID) {
  const attendanceList = document.querySelector(".attendance-list");

  const newItem = document.createElement("div");
  newItem.classList.add("attendance-item");

  newItem.innerHTML = `
    <div class="member-info">
      <div class="member-avatar">${memberID.substring(0,2)}</div>
      <div>
        <strong>${memberID}</strong>
        <span class="time">Just now</span>
      </div>
    </div>
    <span class="check-in-badge">Check-In</span>
  `;

  attendanceList.prepend(newItem);
  addNotification("Attendance logged for " + memberID);
}

function processPayment(){
  const id = document.getElementById("paymentMemberID").value;
  const amount = document.getElementById("paymentAmount").value;

  if(id && amount){
    addNotification("Payment received from " + id + " (₱" + amount + ")");
    alert("Payment Recorded Successfully!");
  }
}

function logWorkout(){
  const id = document.getElementById("perfID").value;
  const exercise = document.getElementById("exercise").value;
  const sets = document.getElementById("sets").value;
  const reps = document.getElementById("reps").value;

  if(id && exercise){
    const logs = document.getElementById("workoutLogs");

    const entry = document.createElement("div");
    entry.classList.add("attendance-item");
    entry.innerHTML = `<strong>${id}</strong> - ${exercise} (${sets} sets x ${reps} reps)`;

    logs.prepend(entry);
    addNotification("Workout logged for " + id);
  }
}

function addNotification(message){
  const list = document.querySelector(".notifications-list");

  const item = document.createElement("div");
  item.classList.add("notification-item", "priority-low");
  item.innerHTML = `
    <i class="bi bi-bell-fill"></i>
    <div>
      <strong>${message}</strong>
      <span class="notification-time">Just now</span>
    </div>
  `;

  list.prepend(item);
}

</script>
</body>
</html>
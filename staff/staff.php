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
        <div style="margin-top: 12px; text-align: left;">
          <label for="manualAttendanceUser" style="display:block; margin-bottom:6px; color:#d6d6d6; font-size:13px;">Manual Attendance (Members Only)</label>
          <select id="manualAttendanceUser" class="form-input" style="margin-bottom:10px;">
            <option value="">Select a member...</option>
          </select>
          <button class="action-btn" onclick="submitManualAttendance()">Record Manual Attendance</button>
        </div>
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
  <h2>Payment Processing</h2>
  <div class="registration-card">
    <form class="registration-form" id="paymentForm">
      <div style="margin-bottom: 20px;">
        <label style="color: #a0a0a0; font-size: 11px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block;">Customer Type</label>
        <div style="display: flex; gap: 15px;">
          <label style="display: flex; align-items: center; cursor: pointer; color: #fff;">
            <input type="radio" name="customerType" value="member" checked onchange="toggleCustomerType('member')" style="margin-right: 8px;">
            <span style="font-weight: 600;">Member</span>
          </label>
          <label style="display: flex; align-items: center; cursor: pointer; color: #fff;">
            <input type="radio" name="customerType" value="non-member" onchange="toggleCustomerType('non-member')" style="margin-right: 8px;">
            <span style="font-weight: 600;">Walk-In</span>
          </label>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group" id="memberIdGroup">
          <label>Member ID</label>
          <input type="text" id="paymentMemberID" class="form-input" placeholder="#MB2024001">
        </div>
        <div class="form-group" id="customerNameGroup" style="display: none;">
          <label>Customer Name</label>
          <input type="text" id="paymentCustomerName" class="form-input" placeholder="Enter full name">
        </div>
        <div class="form-group">
          <label>Amount (₱)</label>
          <input type="number" id="paymentAmount" class="form-input" placeholder="0.00" step="0.01">
        </div>
        <div class="form-group">
          <label>Paid For</label>
          <input type="text" id="paymentPaidFor" class="form-input" placeholder="Monthly Membership / Protein Shake / etc.">
        </div>
        <div class="form-group">
          <label>Optional Notes</label>
          <input type="text" id="paymentNotes" class="form-input" placeholder="Optional note">
        </div>
        <div class="form-group">
          <label>Payment Method</label>
          <select id="paymentMethod" class="form-input">
            <option value="">Select Method</option>
            <option value="Cash">Cash</option>
            <option value="GCash">GCash</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn-secondary" onclick="clearPaymentForm()">Clear</button>
        <button type="button" class="btn-primary" id="paymentSubmitBtn" onclick="processPayment()">Generate Receipt</button>
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
        <input type="text" id="exercise" class="form-input" list="exerciseOptions" placeholder="Select or type exercise">
        <datalist id="exerciseOptions"></datalist>
      </div>
      <div class="form-group">
        <label id="performanceMetricLabel">Weight (kg)</label>
        <input type="number" id="performanceMetric" class="form-input" placeholder="Enter weight" step="0.1" min="0">
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

<!-- E-RECEIPT MODAL -->
<div id="receiptModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; align-items:center; justify-content:center;">
  <div style="background:#1a1a1a; border:1px solid #333; padding:40px; max-width:500px; width:90%; color:#fff; clip-path:polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);">
    <div style="text-align:center; margin-bottom:30px; padding-bottom:20px; border-bottom:2px dashed #333;">
      <h2 style="font-family:'Chakra Petch',sans-serif; text-transform:uppercase; letter-spacing:1px; color:#FFCC00; margin-bottom:5px;">Receipt</h2>
      <p style="color:#a0a0a0; font-size:12px;">Payment Confirmed</p>
    </div>
    
    <div id="receiptContent" style="margin-bottom:30px; font-size:14px; line-height:1.8;"></div>
    
    <div style="display:flex; gap:10px; justify-content:center;">
      <button onclick="printReceipt()" style="background:#FFCC00; color:#000; border:none; padding:12px 25px; font-weight:700; cursor:pointer; font-family:'Chakra Petch',sans-serif; text-transform:uppercase; letter-spacing:0.5px; clip-path:polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);">Print</button>
      <button onclick="closeReceipt()" style="background:transparent; color:#FFCC00; border:1px solid #FFCC00; padding:12px 25px; font-weight:700; cursor:pointer; font-family:'Chakra Petch',sans-serif; text-transform:uppercase; letter-spacing:0.5px; clip-path:polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);">Close</button>
    </div>
  </div>
</div>
<script>
  document.getElementById("clearBtn").addEventListener("click", function() {
      document.getElementById("registrationForm").reset();
  });
</script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function resolveMemberRefFromQr(qrCodeMessage) {
  const raw = String(qrCodeMessage || '').trim();
  if (!raw) {
    return '';
  }

  // Support JSON QR payloads and plain id/username payloads.
  if (raw.startsWith('{') && raw.endsWith('}')) {
    try {
      const payload = JSON.parse(raw);
      const preferred = payload.member_ref || payload.member_id || payload.user_id || payload.id || payload.username;
      return preferred ? String(preferred).trim() : '';
    } catch (err) {
      return raw;
    }
  }

  return raw;
}

function submitAttendance(memberRef, source) {
  return fetch('../Database/save_attendance.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      member_ref: memberRef,
      source: source
    })
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        throw new Error(data.error || 'Unable to record attendance.');
      }

      const label = data.member_display_name || memberRef;
      logAttendance(label, Boolean(data.point_awarded));
      addNotification(
        data.point_awarded
          ? 'Attendance saved for ' + label + ' (+1 point today)'
          : 'Attendance saved for ' + label + ' (point already credited today)'
      );

      return data;
    });
}

function loadAttendanceMembers() {
  fetch('../Database/get_attendance_members.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.members)) {
        return;
      }

      const select = document.getElementById('manualAttendanceUser');
      if (!select) {
        return;
      }

      select.innerHTML = '<option value="">Select a member...</option>';
      data.members.forEach(member => {
        const option = document.createElement('option');
        option.value = member.member_ref;
        option.textContent = member.display_name;
        select.appendChild(option);
      });
    })
    .catch(() => {
      // Keep UI usable even if member list is temporarily unavailable.
    });
}

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
      const memberRef = resolveMemberRefFromQr(qrCodeMessage);
      if (!memberRef) {
        alert('Invalid QR data. Please scan a valid member QR code.');
        return;
      }

      submitAttendance(memberRef, 'qr')
        .then(data => {
          const msg = data.point_awarded
            ? 'Attendance recorded. +1 point credited.'
            : 'Attendance recorded. Point already credited today.';
          alert(msg);
        })
        .catch(err => {
          alert(err.message || 'Unable to save attendance right now.');
        })
        .finally(() => {
          html5QrCode.stop();
        });
    },
    errorMessage => {
    }
  ).catch(err => {
    alert("Camera Error: " + err);
  });
}

function submitManualAttendance() {
  const select = document.getElementById('manualAttendanceUser');
  if (!select || !select.value) {
    alert('Please select a member first.');
    return;
  }

  submitAttendance(select.value, 'manual')
    .then(data => {
      const msg = data.point_awarded
        ? 'Manual attendance recorded. +1 point credited.'
        : 'Manual attendance recorded. Point already credited today.';
      alert(msg);
    })
    .catch(err => {
      alert(err.message || 'Unable to save attendance right now.');
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

function logAttendance(memberID, pointAwarded) {
  const attendanceList = document.querySelector(".attendance-list");

  const newItem = document.createElement("div");
  newItem.classList.add("attendance-item");

  const rawMember = String(memberID || '').trim() || 'Member';
  const safeMember = escapeHtml(rawMember);
  const initials = rawMember.substring(0, 2).toUpperCase();
  const badgeClass = pointAwarded ? 'check-in-badge' : 'check-out-badge';
  const badgeText = pointAwarded ? 'Check-In (+1 pt)' : 'Check-In (No point)';

  newItem.innerHTML = `
    <div class="member-info">
      <div class="member-avatar">${initials}</div>
      <div>
        <strong>${safeMember}</strong>
        <span class="time">Just now</span>
      </div>
    </div>
    <span class="${badgeClass}">${badgeText}</span>
  `;

  attendanceList.prepend(newItem);
}

function processPayment(){
  const customerType = document.querySelector('input[name="customerType"]:checked').value;
  const amount = document.getElementById("paymentAmount").value.trim();
  const paidFor = document.getElementById("paymentPaidFor").value.trim();
  const notes = document.getElementById("paymentNotes").value.trim();
  const method = document.getElementById("paymentMethod").value;
  const paymentSubmitBtn = document.getElementById("paymentSubmitBtn");
  
  let memberId = null;
  let customerName = null;

  if(customerType === 'member') {
    memberId = document.getElementById("paymentMemberID").value.trim();
    if(!memberId) {
      alert("Please enter Member ID!");
      return;
    }
  } else {
    customerName = document.getElementById("paymentCustomerName").value.trim();
    if(!customerName) {
      alert("Please enter customer name!");
      return;
    }
  }

  if(!amount || !method || !paidFor){
    alert("Please fill in all fields!");
    return;
  }

  if(parseFloat(amount) <= 0){
    alert("Amount must be greater than 0!");
    return;
  }

  paymentSubmitBtn.disabled = true;
  paymentSubmitBtn.textContent = 'Saving...';

  fetch('../Database/save_transaction.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      customer_type: customerType,
      member_ref: memberId,
      customer_name: customerName,
      amount: parseFloat(amount),
      payment_method: method,
      paid_for: paidFor,
      notes: notes
    })
  })
    .then(response => response.json())
    .then(data => {
      paymentSubmitBtn.disabled = false;
      paymentSubmitBtn.textContent = 'Generate Receipt';

      if (!data.success) {
        alert(data.error || 'Failed to save transaction.');
        return;
      }

      displayReceipt(data.receipt);
      clearPaymentForm();
    })
    .catch(() => {
      paymentSubmitBtn.disabled = false;
      paymentSubmitBtn.textContent = 'Generate Receipt';
      alert('Unable to save transaction right now.');
    });
}

function displayReceipt(receipt) {
  const modal = document.getElementById("receiptModal");
  const content = document.getElementById("receiptContent");
  
  const customerInfo = receipt.customerType === 'member' 
    ? `<p style="margin:5px 0;"><strong>Member ID:</strong> ${receipt.memberId}</p>`
    : `<p style="margin:5px 0;"><strong>Customer:</strong> ${receipt.customerName}</p>`;
  const noteInfo = receipt.notes ? `<p style="margin:5px 0;"><strong>Notes:</strong> ${receipt.notes}</p>` : '';
  
  content.innerHTML = `
    <div style="margin-bottom:15px;">
      <p style="margin:5px 0;"><strong>Receipt #:</strong> ${receipt.receiptNumber}</p>
      <p style="margin:5px 0;"><strong>Date:</strong> ${receipt.date}</p>
      <p style="margin:5px 0;"><strong>Time:</strong> ${receipt.time}</p>
    </div>
    
    <div style="padding:15px; background:#141414; margin:15px 0; border:1px solid #333; clip-path:polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);">
      ${customerInfo}
      <p style="margin:5px 0;"><strong>Type:</strong> ${receipt.customerType === 'member' ? 'Member' : 'Walk-In'}</p>
      <p style="margin:5px 0;"><strong>Paid For:</strong> ${receipt.paidFor || '-'}</p>
      <p style="margin:5px 0;"><strong>Payment:</strong> ${receipt.method}</p>
      <p style="margin:5px 0;"><strong>Status:</strong> <span style="color:#00d084;">✓ ${receipt.status}</span></p>
      ${noteInfo}
    </div>
    
    <div style="border-top:2px dashed #333; padding-top:15px;">
      <div style="display:flex; justify-content:space-between; margin:10px 0; font-size:16px; font-weight:700;">
        <span>TOTAL:</span>
        <span style="color:#FFCC00;">₱${receipt.amount.toFixed(2)}</span>
      </div>
    </div>
  `;
  
  window.currentReceipt = receipt;
  modal.style.display = "flex";
}

function closeReceipt() {
  document.getElementById("receiptModal").style.display = "none";
  addNotification("Receipt " + window.currentReceipt.receiptNumber + " generated");
}

function printReceipt() {
  const r = window.currentReceipt;
  const customerInfo = r.customerType === 'member'
    ? `Member ID: ${r.memberId}`
    : `Customer: ${r.customerName}`;
  const noteInfo = r.notes ? `<div class="detailsrow"><span>Notes:</span> <span>${r.notes}</span></div>` : '';
  
  const printWindow = window.open('', '', 'height=500,width=700');
  printWindow.document.write(`
    <html>
      <head>
        <title>Receipt - ${r.receiptNumber}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 40px; background: white; }
          .header { text-align: center; margin-bottom: 30px; border-bottom: 2px dashed black; padding-bottom: 20px; }
          .header h2 { margin: 0; font-size: 24px; }
          .detailsrow { display: flex; justify-content: space-between; padding: 8px 0; }
          .total { border-top: 2px dashed black; padding-top: 20px; display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; }
          .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        </style>
      </head>
      <body>
        <div class="header">
          <h2>FIT-STOP GYM</h2>
          <p>Receipt</p>
        </div>
        <div class="details">
          <div class="detailsrow"><span>Receipt #:</span> <span>${r.receiptNumber}</span></div>
          <div class="detailsrow"><span>Date:</span> <span>${r.date} ${r.time}</span></div>
          <div class="detailsrow"><span>${customerInfo}</span></div>
          <div class="detailsrow"><span>Type:</span> <span>${r.customerType === 'member' ? 'Member' : 'Walk-In'}</span></div>
          <div class="detailsrow"><span>Paid For:</span> <span>${r.paidFor || '-'}</span></div>
          <div class="detailsrow"><span>Payment:</span> <span>${r.method}</span></div>
          ${noteInfo}
        </div>
        <div class="total">
          <span>TOTAL:</span> <span>₱${r.amount.toFixed(2)}</span>
        </div>
        <div class="footer">
          <p>Thank you for your payment!</p>
        </div>
      </body>
    </html>
  `);
  printWindow.document.close();
  setTimeout(() => printWindow.print(), 100);
}

function toggleCustomerType(type) {
  if(type === 'member') {
    document.getElementById('memberIdGroup').style.display = 'block';
    document.getElementById('customerNameGroup').style.display = 'none';
  } else {
    document.getElementById('memberIdGroup').style.display = 'none';
    document.getElementById('customerNameGroup').style.display = 'block';
  }
}

function clearPaymentForm() {
  document.getElementById("paymentForm").reset();
  toggleCustomerType('member');
}

let exerciseNameToId = {};
let exerciseNameToType = {};

function loadExerciseOptions() {
  fetch('../Database/get_exercises.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.exercises)) {
        return;
      }

      const exerciseOptions = document.getElementById('exerciseOptions');
      if (!exerciseOptions) {
        return;
      }

      exerciseNameToId = {};
      exerciseNameToType = {};
      data.exercises.forEach(item => {
        exerciseNameToId[item.name.toLowerCase()] = item.exercise_id;
        exerciseNameToType[item.name.toLowerCase()] = (item.movement_type || '').toLowerCase();
      });

      exerciseOptions.innerHTML = data.exercises
        .map(item => `<option value="${item.name}"></option>`)
        .join('');
    })
    .catch(() => {
      // Keep workout form usable even if exercise list is unavailable.
    });
}

function updatePerformanceMetricField() {
  const exerciseInput = document.getElementById('exercise');
  const metricLabel = document.getElementById('performanceMetricLabel');
  const metricInput = document.getElementById('performanceMetric');
  if (!exerciseInput || !metricLabel || !metricInput) {
    return;
  }

  const movementType = exerciseNameToType[exerciseInput.value.trim().toLowerCase()] || '';
  if (movementType === 'cardio') {
    metricLabel.textContent = 'Minutes';
    metricInput.placeholder = 'Enter minutes';
  } else {
    metricLabel.textContent = 'Weight (kg)';
    metricInput.placeholder = 'Enter weight';
  }
}

function logWorkout(){
  const id = document.getElementById("perfID").value.trim();
  const exercise = document.getElementById("exercise").value.trim();
  const metricValue = document.getElementById("performanceMetric").value.trim();
  const reps = document.getElementById("reps").value.trim();

  if(!id || !exercise || !metricValue || !reps){
    alert("Please provide Member ID, Exercise, Weight/Minutes, and Reps.");
    return;
  }

  const exerciseId = exerciseNameToId[exercise.toLowerCase()];
  if (!exerciseId) {
    alert("Please choose a valid exercise from the dropdown list.");
    return;
  }

  const movementType = exerciseNameToType[exercise.toLowerCase()] || '';
  const metricNumber = parseFloat(metricValue);
  if (Number.isNaN(metricNumber) || metricNumber < 0) {
    alert("Please enter a valid Weight/Minutes value.");
    return;
  }

  fetch('../Database/save_workout_log.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      member_ref: id,
      exercise_id: exerciseId,
      reps: parseInt(reps, 10),
      weight: metricNumber
    })
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        alert(data.error || "Failed to save workout log.");
        return;
      }

      const logs = document.getElementById("workoutLogs");
      const entry = document.createElement("div");
      entry.classList.add("attendance-item");
      const metricSuffix = movementType === 'cardio' ? 'min' : 'kg';
      entry.innerHTML = `<strong>${id}</strong> - ${exercise} (#${exerciseId}) (${metricNumber} ${metricSuffix}, ${reps} reps)`;
      logs.prepend(entry);
      addNotification("Workout logged for " + id);
    })
    .catch(() => {
      alert("Unable to save workout log right now.");
    });
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

document.addEventListener('DOMContentLoaded', function () {
  loadExerciseOptions();
  loadAttendanceMembers();
  const exerciseInput = document.getElementById('exercise');
  if (exerciseInput) {
    exerciseInput.addEventListener('input', updatePerformanceMetricField);
    exerciseInput.addEventListener('change', updatePerformanceMetricField);
  }
  updatePerformanceMetricField();
});

</script>
</body>
</html>
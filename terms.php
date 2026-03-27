<?php
// Terms & Conditions + Privacy Policy
// FIT-STOP Gym Management System
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FIT-STOP — Terms & Conditions</title>
  <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
  <style>
    :root {
      --bg-void: #0a0a0a;
      --bg-surface: #141414;
      --bg-card: #1a1a1a;
      --hazard-yellow: #ffcc00;
      --hazard-yellow-dim: #c9a400;
      --text-primary: #ffffff;
      --text-muted: #a0a0a0;
      --border: #2a2a2a;
      --border-light: #333;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background-color: var(--bg-void);
      color: var(--text-primary);
      font-family: 'Inter', sans-serif;
      overflow-x: hidden;
      min-height: 100vh;
    }

    /* Hazard stripe bottom bar */
    body::after {
      content: '';
      display: block;
      height: 10px;
      background: repeating-linear-gradient(-45deg, var(--hazard-yellow) 0, var(--hazard-yellow) 14px, #111 14px, #111 28px);
      position: fixed;
      bottom: 0; left: 0; right: 0;
      z-index: 999;
    }

    /* NAV */
    .navbar {
      background-color: rgba(10,10,10,0.95);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--border-light);
      padding: 1rem 0;
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
    }
    .navbar-inner {
      max-width: 1140px;
      margin: 0 auto;
      padding: 0 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .navbar-brand {
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--hazard-yellow);
      border: 2px solid var(--hazard-yellow);
      padding: 0.2rem 0.8rem;
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .nav-back {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-muted);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: color 0.2s;
    }
    .nav-back:hover { color: var(--hazard-yellow); }

    /* PAGE WRAPPER */
    .page-wrap {
      max-width: 860px;
      margin: 0 auto;
      padding: 120px 1.5rem 80px;
    }

    /* PAGE HEADER */
    .page-header {
      margin-bottom: 3rem;
      animation: slideUp 0.5s ease both;
    }
    .page-header .eyebrow {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 3px;
      color: var(--hazard-yellow);
      text-transform: uppercase;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .page-header .eyebrow::before {
      content: '';
      display: inline-block;
      width: 24px; height: 2px;
      background: var(--hazard-yellow);
    }
    .page-header h1 {
      font-family: 'Chakra Petch', sans-serif;
      font-size: clamp(2.2rem, 5vw, 3.5rem);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      line-height: 1;
      color: #fff;
      margin-bottom: 1rem;
    }
    .page-header .meta {
      font-size: 0.8rem;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 1.5rem;
      flex-wrap: wrap;
      margin-top: 1rem;
    }
    .page-header .meta span {
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .page-header .meta i { color: var(--hazard-yellow); font-size: 0.75rem; }

    /* HAZARD STRIPE DIVIDER */
    .hazard-divider {
      height: 6px;
      background: repeating-linear-gradient(45deg, var(--hazard-yellow), var(--hazard-yellow) 10px, #000 10px, #000 20px);
      margin: 2.5rem 0;
      opacity: 0.7;
    }

    /* STICKY TOC */
    .doc-layout {
      display: grid;
      grid-template-columns: 200px 1fr;
      gap: 3rem;
      align-items: start;
    }
    .toc {
      position: sticky;
      top: 100px;
      animation: slideUp 0.5s 0.1s ease both;
    }
    .toc-label {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.65rem;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 1rem;
    }
    .toc-list { list-style: none; }
    .toc-list li { margin-bottom: 0.15rem; }
    .toc-list a {
      font-size: 0.78rem;
      color: var(--text-muted);
      text-decoration: none;
      display: block;
      padding: 0.3rem 0.6rem;
      border-left: 2px solid transparent;
      transition: all 0.2s;
      font-family: 'Inter', sans-serif;
    }
    .toc-list a:hover,
    .toc-list a.active {
      color: var(--hazard-yellow);
      border-left-color: var(--hazard-yellow);
      background: rgba(255,204,0,0.04);
    }
    .toc-section-label {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.6rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #555;
      padding: 0.8rem 0.6rem 0.3rem;
    }

    /* MAIN CONTENT */
    .doc-content { animation: slideUp 0.5s 0.15s ease both; }

    .doc-section {
      margin-bottom: 3.5rem;
    }

    .section-tag {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--hazard-yellow);
      margin-bottom: 0.5rem;
      opacity: 0.8;
    }

    .section-title {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #fff;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .section-title .num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 28px; height: 28px;
      background: var(--hazard-yellow);
      color: #000;
      font-size: 0.75rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    .doc-text {
      font-size: 0.9rem;
      line-height: 1.85;
      color: #c8c8c8;
      margin-bottom: 1rem;
    }

    /* INFO CARD */
    .info-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-left: 3px solid var(--hazard-yellow);
      padding: 1.25rem 1.5rem;
      margin-bottom: 1rem;
      position: relative;
    }
    .info-card .card-label {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.65rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--hazard-yellow);
      margin-bottom: 0.6rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .info-card p {
      font-size: 0.875rem;
      line-height: 1.75;
      color: #bbb;
      margin: 0;
    }

    /* WARNING CARD */
    .warning-card {
      background: rgba(255,204,0,0.05);
      border: 1px solid rgba(255,204,0,0.25);
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.25rem;
      display: flex;
      gap: 1rem;
      align-items: flex-start;
    }
    .warning-card .warn-icon {
      color: var(--hazard-yellow);
      font-size: 1rem;
      margin-top: 2px;
      flex-shrink: 0;
    }
    .warning-card .warn-content strong {
      display: block;
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--hazard-yellow);
      margin-bottom: 0.3rem;
    }
    .warning-card .warn-content p {
      font-size: 0.875rem;
      line-height: 1.7;
      color: #c0c0c0;
      margin: 0;
    }

    /* ITEM LIST */
    .item-list { list-style: none; margin: 0.5rem 0 1rem; }
    .item-list li {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      font-size: 0.875rem;
      line-height: 1.75;
      color: #c0c0c0;
      padding: 0.5rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .item-list li:last-child { border-bottom: none; }
    .item-list li .bullet {
      width: 6px; height: 6px;
      background: var(--hazard-yellow);
      flex-shrink: 0;
      margin-top: 8px;
      clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
    }
    .item-list li strong {
      color: #e8e8e8;
      font-weight: 600;
    }

    /* RIGHTS TABLE */
    .rights-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1px;
      background: var(--border);
      border: 1px solid var(--border);
      margin: 1rem 0;
    }
    .rights-cell {
      background: var(--bg-surface);
      padding: 1rem 1.25rem;
    }
    .rights-cell.header {
      background: #000;
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.65rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--hazard-yellow);
    }
    .rights-cell .right-title {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #fff;
      margin-bottom: 0.3rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .rights-cell .right-title i { color: var(--hazard-yellow); font-size: 0.7rem; }
    .rights-cell p { font-size: 0.8rem; color: #999; line-height: 1.6; margin: 0; }

    /* SECTION SEPARATOR */
    .section-separator {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 3.5rem 0;
    }
    .section-separator .sep-label {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.7rem;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--hazard-yellow);
      white-space: nowrap;
      padding: 0.3rem 0.8rem;
      border: 1px solid rgba(255,204,0,0.4);
      background: rgba(255,204,0,0.05);
    }
    .section-separator::before,
    .section-separator::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* CONTACT CARD */
    .contact-card {
      background: var(--bg-card);
      border: 1px solid var(--border-light);
      padding: 2rem;
      display: flex;
      align-items: center;
      gap: 2rem;
      margin-top: 1.5rem;
    }
    .contact-icon {
      width: 52px; height: 52px;
      background: var(--hazard-yellow);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      font-size: 1.25rem;
      color: #000;
    }
    .contact-info h4 {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #fff;
      margin-bottom: 0.3rem;
    }
    .contact-info p { font-size: 0.85rem; color: var(--text-muted); margin: 0; }

    /* COMPLIANCE BADGE */
    .compliance-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255,204,0,0.08);
      border: 1px solid rgba(255,204,0,0.3);
      padding: 0.4rem 1rem;
      font-family: 'Chakra Petch', sans-serif;
      font-size: 0.65rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--hazard-yellow);
      margin-bottom: 2rem;
    }
    .compliance-badge i { font-size: 0.7rem; }

    /* ANIMATION */
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .doc-layout { grid-template-columns: 1fr; }
      .toc { display: none; }
      .rights-grid { grid-template-columns: 1fr; }
      .contact-card { flex-direction: column; gap: 1rem; }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="navbar-inner">
      <a href="#" class="navbar-brand">[FIT-STOP]</a>
      <a href="#" class="nav-back"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="page-wrap">

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="eyebrow">Legal Documentation</div>
      <h1>Terms &amp; Conditions<br/><span style="color:var(--hazard-yellow)">+ Privacy Policy</span></h1>
      <div class="meta">
        <span><i class="fa-solid fa-calendar-days"></i> Last Updated: March 27, 2026</span>
        <span><i class="fa-solid fa-shield-halved"></i> R.A. 10173 Compliant</span>
        <span><i class="fa-solid fa-gavel"></i> Binding Agreement</span>
      </div>
    </div>

    <div class="hazard-divider"></div>

    <!-- LAYOUT WITH TOC -->
    <div class="doc-layout">

      <!-- STICKY TOC -->
      <aside class="toc">
        <div class="toc-label">Contents</div>
        <ul class="toc-list">
          <div class="toc-section-label">Terms</div>
          <li><a href="#scope">1. Scope of Service</a></li>
          <li><a href="#ai">2. AI Adviser Disclaimer</a></li>
          <li><a href="#accounts">3. User Accounts</a></li>
          <li><a href="#integrity">4. System Integrity</a></li>
          <div class="toc-section-label">Privacy</div>
          <li><a href="#collection">1. Data Collection</a></li>
          <li><a href="#consent">2. Use &amp; Consent</a></li>
          <li><a href="#security">3. Data Security</a></li>
          <li><a href="#retention">4. Retention &amp; Deletion</a></li>
          <li><a href="#rights">5. Your Rights</a></li>
          <div class="toc-section-label">Other</div>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </aside>

      <!-- DOCUMENT BODY -->
      <main class="doc-content">

        <div class="info-card" style="margin-bottom:2rem;">
          <div class="card-label"><i class="fa-solid fa-circle-info"></i> Notice</div>
          <p>By accessing or using the Fit-Stop Gym Management System (the "Web-App"), you agree to be bound by these Terms and Conditions. Please read this document carefully before proceeding.</p>
        </div>

        <!-- ─── TERMS & CONDITIONS ─── -->
        <div class="compliance-badge">
          <i class="fa-solid fa-file-contract"></i> Terms &amp; Conditions
        </div>

        <!-- 1. Scope -->
        <div class="doc-section" id="scope">
          <div class="section-tag">Section 01</div>
          <h2 class="section-title"><span class="num">1</span> Scope of Service</h2>
          <p class="doc-text">Fit-Stop Gym provides this web-based platform to manage memberships, inventory, and offer personalized fitness guidance. This system is designed to assist gym staff and enhance the member experience.</p>
          <div class="warning-card">
            <i class="fa-solid fa-triangle-exclamation warn-icon"></i>
            <div class="warn-content">
              <strong>Important Limitation</strong>
              <p>This platform does not replace human trainers or professional medical practitioners. The system is a supplementary management tool only.</p>
            </div>
          </div>
        </div>

        <!-- 2. AI Adviser -->
        <div class="doc-section" id="ai">
          <div class="section-tag">Section 02</div>
          <h2 class="section-title"><span class="num">2</span> AI Adviser Disclaimer</h2>
          <ul class="item-list">
            <li>
              <span class="bullet"></span>
              <span><strong>Guidance Only:</strong> The AI-generated workout, diet, and recovery plans are for informational purposes only.</span>
            </li>
            <li>
              <span class="bullet"></span>
              <span><strong>No Medical Advice:</strong> These outputs are not a substitute for professional medical advice, diagnosis, or treatment. Users are urged to consult a physician before starting any new fitness or nutrition program.</span>
            </li>
            <li>
              <span class="bullet"></span>
              <span><strong>Risk Assumption:</strong> Use of AI-generated suggestions is at the user's own risk. Fit-Stop Gym is not liable for injuries or health complications resulting from the use of the AI Adviser.</span>
            </li>
          </ul>
        </div>

        <!-- 3. User Accounts -->
        <div class="doc-section" id="accounts">
          <div class="section-tag">Section 03</div>
          <h2 class="section-title"><span class="num">3</span> User Accounts &amp; Security</h2>
          <ul class="item-list">
            <li>
              <span class="bullet"></span>
              <span><strong>Credentials:</strong> You are responsible for maintaining the confidentiality of your login credentials.</span>
            </li>
            <li>
              <span class="bullet"></span>
              <span><strong>Authentication:</strong> The system utilizes session-based authentication and role-based access control (RBAC). Attempting to bypass these security layers or access administrative pages without authorization is strictly prohibited.</span>
            </li>
            <li>
              <span class="bullet"></span>
              <span><strong>Prohibited Use:</strong> Users may not use the AI Adviser to generate unsafe, illegal, or harmful content. The system employs hard-refusal rules for such queries.</span>
            </li>
          </ul>
        </div>

        <!-- 4. System Integrity -->
        <div class="doc-section" id="integrity">
          <div class="section-tag">Section 04</div>
          <h2 class="section-title"><span class="num">4</span> System Integrity</h2>
          <p class="doc-text">To protect the community, Fit-Stop Gym implements CSRF protection, encrypted password hashing, and audit trails.</p>
          <div class="warning-card">
            <i class="fa-solid fa-ban warn-icon"></i>
            <div class="warn-content">
              <strong>Zero Tolerance Policy</strong>
              <p>Any attempt to compromise the system via SQL injection, XSS, or session fixation will result in immediate account termination and potential legal action.</p>
            </div>
          </div>
        </div>

        <!-- SECTION BREAK -->
        <div class="section-separator">
          <span class="sep-label"><i class="fa-solid fa-shield-halved" style="margin-right:0.5rem"></i> Privacy Policy</span>
        </div>

        <div class="compliance-badge">
          <i class="fa-solid fa-shield-halved"></i> In Compliance with R.A. 10173 — Data Privacy Act of 2012
        </div>

        <p class="doc-text" style="margin-bottom:2rem;">Fit-Stop Gym is committed to protecting your Sensitive Personal Information. This policy explains how we handle your health metrics and contact details.</p>

        <!-- 1. Collection -->
        <div class="doc-section" id="collection">
          <div class="section-tag">Privacy Section 01</div>
          <h2 class="section-title"><span class="num">1</span> Information Collection</h2>
          <p class="doc-text">We only collect data that you explicitly provide, adhering to the principle of data minimization. This includes:</p>
          <ul class="item-list">
            <li><span class="bullet"></span><span><strong>Contact Details:</strong> For membership and account management.</span></li>
            <li><span class="bullet"></span><span><strong>Health Metrics:</strong> BMI, weight, and exercise history to provide personalized fitness guidance.</span></li>
            <li><span class="bullet"></span><span><strong>Session Data:</strong> Temporary storage of AI conversation history to ensure continuity during your active session.</span></li>
          </ul>
        </div>

        <!-- 2. Use & Consent -->
        <div class="doc-section" id="consent">
          <div class="section-tag">Privacy Section 02</div>
          <h2 class="section-title"><span class="num">2</span> Use of Data &amp; Consent</h2>
          <ul class="item-list">
            <li><span class="bullet"></span><span><strong>Informed Consent:</strong> We process your data only after you have provided explicit consent via our landing page.</span></li>
            <li><span class="bullet"></span><span><strong>Purpose:</strong> Data is used solely for gym operations (membership/inventory) and providing the AI-driven fitness guidance you request.</span></li>
          </ul>
        </div>

        <!-- 3. Security -->
        <div class="doc-section" id="security">
          <div class="section-tag">Privacy Section 03</div>
          <h2 class="section-title"><span class="num">3</span> Data Security &amp; Storage</h2>
          <div class="info-card" style="margin-bottom:1rem;">
            <div class="card-label"><i class="fa-solid fa-lock"></i> Centralized Protection</div>
            <p>Data is stored in a secured centralized database with server-side processing to ensure consistency and security.</p>
          </div>
          <ul class="item-list">
            <li><span class="bullet"></span><span><strong>Encryption:</strong> Passwords are never stored in plaintext; we utilize industry-standard hashing (<code style="font-size:0.8rem;background:#111;padding:1px 5px;color:var(--hazard-yellow)">password_verify()</code>).</span></li>
            <li><span class="bullet"></span><span><strong>Audit Trails:</strong> We monitor login attempts and system activity to ensure accountability and prevent unauthorized access.</span></li>
          </ul>
          <div class="warning-card">
            <i class="fa-solid fa-circle-info warn-icon"></i>
            <div class="warn-content">
              <strong>Note on Hosting</strong>
              <p>While our code uses prepared statements and output escaping to prevent common web attacks (SQLi/XSS), users are advised that full protection in transit requires HTTPS/SSL hosting configurations.</p>
            </div>
          </div>
        </div>

        <!-- 4. Retention -->
        <div class="doc-section" id="retention">
          <div class="section-tag">Privacy Section 04</div>
          <h2 class="section-title"><span class="num">4</span> Data Retention &amp; Deletion</h2>
          <ul class="item-list">
            <li><span class="bullet"></span><span><strong>AI History:</strong> Your conversations with the AI Adviser are stored temporarily in your session and are not permanently stored in our main database.</span></li>
            <li><span class="bullet"></span><span><strong>Member Records:</strong> We retain your profile data only as long as you maintain an active membership with Fit-Stop Gym.</span></li>
          </ul>
        </div>

        <!-- 5. Rights -->
        <div class="doc-section" id="rights">
          <div class="section-tag">Privacy Section 05</div>
          <h2 class="section-title"><span class="num">5</span> Your Rights as a Data Subject</h2>
          <p class="doc-text">Under the Data Privacy Act of 2012, you have the following rights:</p>
          <div class="rights-grid">
            <div class="rights-cell header">Right</div>
            <div class="rights-cell header">Description</div>
            <div class="rights-cell">
              <div class="right-title"><i class="fa-solid fa-eye"></i> Access</div>
              <p>View your stored health metrics and profile information at any time.</p>
            </div>
            <div class="rights-cell">
              <p>Request a full summary of the personal data we hold about you.</p>
            </div>
            <div class="rights-cell">
              <div class="right-title"><i class="fa-solid fa-pen"></i> Correction</div>
              <p>Update or correct your BMI, weight, and exercise history.</p>
            </div>
            <div class="rights-cell">
              <p>Submit amendments at any time through your member profile.</p>
            </div>
            <div class="rights-cell">
              <div class="right-title"><i class="fa-solid fa-trash"></i> Erasure</div>
              <p>Request deletion of your personal data upon ending your membership.</p>
            </div>
            <div class="rights-cell">
              <p>Contact gym administration to initiate a data erasure request.</p>
            </div>
          </div>
        </div>

        <!-- CONTACT -->
        <div class="doc-section" id="contact">
          <div class="section-tag">Get in Touch</div>
          <h2 class="section-title" style="font-size:1.2rem;">Contact Information</h2>
          <p class="doc-text">For questions regarding these policies, please contact the Fit-Stop Gym administration.</p>
          <div class="contact-card">
            <div class="contact-icon"><i class="fa-solid fa-dumbbell"></i></div>
            <div class="contact-info">
              <h4>Fit-Stop Gym Administration</h4>
              <p><i class="fa-solid fa-location-dot" style="color:var(--hazard-yellow);margin-right:0.4rem"></i> Malabon Branch, Metro Manila, Philippines</p>
            </div>
          </div>
        </div>

      </main>
    </div><!-- end doc-layout -->
  </div><!-- end page-wrap -->

  <script>
    // Highlight active TOC link on scroll
    const sections = document.querySelectorAll('[id]');
    const links = document.querySelectorAll('.toc-list a');
    window.addEventListener('scroll', () => {
      let current = '';
      sections.forEach(s => {
        if (window.scrollY >= s.offsetTop - 140) current = s.id;
      });
      links.forEach(a => {
        a.classList.toggle('active', a.getAttribute('href') === '#' + current);
      });
    });

    // Smooth scroll
    links.forEach(a => {
      a.addEventListener('click', e => {
        e.preventDefault();
        const target = document.querySelector(a.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  </script>
</body>
</html>
<?php
/**
 * INDEX.PHP - Landing Page
 * 
 * Public access page that showcases:
 * - Gym features and capabilities
 * - Location and contact information
 * - Membership pricing
 * - System features (AI tracking, live inventory, etc.)
 * 
 * Security: No login required - This is a public marketing page
 */

// Set page-specific variables before including header
$page_title = 'FIT-STOP | Bakal Meets Tech';

// Include reusable header component
include('includes/header.php');
?>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="d-inline-block bg-warning text-black px-2 py-1 mb-3 fw-bold small brand-font">
                        <i class="fa-solid fa-bolt me-1"></i> LIVE SYSTEM ACTIVE
                    </div>
                    <h1>
                        BAKAL MEETS <span class="text-hazard">TECH</span>
                    </h1>
                    <p>
                        Old school grit. Updated Machines. <br>
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#pricing" class="btn btn-hazard btn-lg">JOIN US</a>
                    </div>
                </div>

                <?php
                $attendanceToday = 0;
                $attendance7d = 0;
                $workouts24h = 0;

                if (isset($pdo)) {
                    try {
                        $today = date('Y-m-d');
                        $attendanceTodayStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date(datetime) = :today");
                        $attendanceTodayStmt->execute([':today' => $today]);
                        $attendanceToday = (int)$attendanceTodayStmt->fetchColumn();

                        $attendance7dStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date(datetime) >= date('now', '-6 days')");
                        $attendance7dStmt->execute();
                        $attendance7d = (int)$attendance7dStmt->fetchColumn();

                        $workouts24hStmt = $pdo->prepare("SELECT COUNT(*) FROM workout_logs WHERE datetime(logged_at, 'localtime') >= datetime('now', '-24 hours')");
                        $workouts24hStmt->execute();
                        $workouts24h = (int)$workouts24hStmt->fetchColumn();
                    } catch (Throwable $e) {
                        error_log('index.php: live stats query failed - ' . $e->getMessage());
                    }
                }
                ?>

                
            </div>
        </div>
    </section>

    <!-- Visual Separator -->
    <div class="hazard-stripes"></div>

    <!-- SYSTEM CAPABILITIES SECTION -->
    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="row mb-5 text-center">
                <div class="col-12">
                    <h2 class="text-white">SYSTEM <span class="text-hazard">CAPABILITIES</span></h2>
                    <p class="text-muted">More than just weights. We provide the data.</p>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Feature: AI Tracking -->
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fa-solid fa-brain feature-icon"></i>
                        <h4 class="text-white mb-3">AI TRACKING</h4>
                        <p class="text-muted small">
                            Smart logging automatically adjusts your progressive overload. 
                            Our system learns your strength curve and suggests the next weight.
                        </p>
                    </div>
                </div>

                <!-- Feature: Live Inventory -->
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fa-solid fa-boxes-stacked feature-icon"></i>
                        <h4 class="text-white mb-3">LIVE INVENTORY</h4>
                        <p class="text-muted small">
                            Real-time stock check for supplements and gear. 
                            Never guess if your pre-workout is in stock. Reserve via the app.
                        </p>
                    </div>
                </div>

                <!-- Feature: Digital Access -->
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="fa-solid fa-id-card feature-icon"></i>
                        <h4 class="text-white mb-3">DIGITAL ACCESS</h4>
                        <p class="text-muted small">
                            No more writing. QR code entry and automated attendance logs.
                            Track your consistency with military precision.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LOCATION SECTION WITH EMBEDDED MAP -->
    <section id="location" class="position-relative">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3528.2517828673112!2d120.94931007468473!3d14.657770985835343!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b53cbce5c081%3A0x91f475680dc2eb3e!2sFit-Stop!5e1!3m2!1sen!2sph!4v1769238056652!5m2!1sen!2sph"
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
        
        <!-- Location Info Overlay -->
        <div class="map-overlay">
            <h5 class="text-hazard mb-1 brand-font">FIT-STOP CENTER</h5>
            <p class="text-white mb-0 small">Malabon City, Metro Manila</p>
            <p class="text-muted small mb-0">OPEN DAILY: 6AM - 10PM</p>
        </div>
    </section>

    <!-- PRICING SECTION -->
    <section id="pricing" class="py-5 bg-black">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2>CHOOSE YOUR <span class="text-hazard">ACCESS</span></h2>
            </div>
            
            <div class="row justify-content-center g-4">
                <!-- Walk-In Plan -->
                <div class="col-md-5 col-lg-4">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h4 class="mb-0">WALK-IN</h4>
                            <div class="display-6 fw-bold mt-2 text-white">₱60<span class="fs-6 text-muted">/day</span></div>
                            <div class="mt-1 small text-muted">or <strong>₱750</strong><span class="fs-6 text-muted">/mo</span> (Monthly Walk-In) - ₱9000 /yr</div>
                        </div>
                        <div class="pricing-body">
                            <ul class="check-list ps-0">
                                <li><i class="fa-solid fa-check"></i> Gym Floor Access</li>
                                <li class="text-muted"><i class="fa-solid fa-xmark"></i> Locker Use</li>
                                <li class="text-muted"><i class="fa-solid fa-xmark"></i> App Tracking</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Smart Member Plan (Featured) -->
                <div class="col-md-5 col-lg-4">
                    <div class="pricing-card featured">
                        <div class="pricing-header">
                            <h4 class="mb-0 text-hazard">SMART MEMBER</h4>
                            <div class="display-6 fw-bold mt-2 text-white">₱650<span class="fs-6 text-muted">/mo</span></div>
                            <div class="mt-1 small text-muted">+ <strong>₱500</strong><span class="fs-6 text-muted">/yr</span> (Membership) - ₱8300 /yr </div>
                        </div>
                        <div class="pricing-body">
                            <ul class="check-list ps-0">
                                <li><i class="fa-solid fa-check"></i> Unlimited Access</li>
                                <li><i class="fa-solid fa-check"></i> <strong>Full App Features</strong></li>
                                <li><i class="fa-solid fa-check"></i> AI Progress Tracking</li>
                                <li><i class="fa-solid fa-check"></i> Inventory Reservation</li>
                            </ul>
                            <a href="Login/Login_Page.php" class="btn btn-hazard w-100 mt-3">BECOME A MEMBER</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// Include reusable footer component
include('includes/footer.php');
?>

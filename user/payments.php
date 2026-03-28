<?php require_once __DIR__ . '/auth_user.php'; 

$transactions = [];
try {
    $dbPath = __DIR__ . '/../Database/DB.sqlite';
    if (file_exists($dbPath)) {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare('SELECT receipt_number, amount, payment_method, status, "desc", created_at FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute([':user_id' => $_SESSION['id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Database error - continue without transactions
}
?>
<?php $activePage = 'payments'; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fit-Stop - User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-OERcA2zY1OHt4q4Fv8B+U7MeM3NnN3KK2eEbV5t8JSaI1zlzW3URy9Bv1WTRi7v8Q" crossorigin="anonymous">
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
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main-content">
        <!-- PROFILE SECTIONS -->
        <section class="profile-page">
        
          <!-- E-Receipts -->
          <div class="profile-card terms-card">
            <h4><i class="bi bi-receipt"></i> E-Receipts</h4>
            <hr class="section-divider" />

            <?php if (empty($transactions)): ?>
              <p style="text-align: center; color: #999; padding: 20px;">No transactions found.</p>
            <?php else: ?>
              <?php foreach ($transactions as $transaction): ?>
              <div class="receipt-entry">
                <div class="receipt-icon">
                  <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="receipt-info">
                  <span class="rname"><?php echo htmlspecialchars($transaction['desc'] ?? 'Payment'); ?></span>
                  <span class="rdate"><?php echo htmlspecialchars($transaction['created_at'] ?? date('M d, Y')); ?> &nbsp;•&nbsp; <?php echo htmlspecialchars($transaction['payment_method'] ?? 'N/A'); ?></span>
                </div>
                <span class="receipt-amount">₱<?php echo number_format((float)$transaction['amount'], 2); ?></span>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <!-- TERMS & POLICY -->
          <div class="profile-card terms-card">
            <div class="card-header-hazard">
              <h3><i class="fas fa-file-contract"></i> Membership Agreement</h3>
            </div>

            <div class="terms-content">
              <div class="terms-box">
                <h4>Important Notice</h4>
                <p>
                  This is an Agreement under which you agree to become a Member
                  of F-Stop Fitness Center (Fitstop). When you sign this
                  Agreement, you are entering into a legally binding contract
                  with us. This agreement sets out your rights to use the
                  Facilities and Services and the responsibilities you have as a
                  Member, including payment of Membership Fees and gym packages.
                </p>
              </div>

              <div class="terms-box">
                <h4>Your Safety</h4>
                <p>
                  You agree to give all relevant health and fitness information
                  before or during any exercise. Each time you use the
                  Facilities and Services, you must ensure you are in good
                  physical condition and know of no medical or other reason why
                  you should not exercise. If unsure, you should seek medical
                  guidance.
                </p>
              </div>

              <div class="terms-box">
                <h4>Liability Waiver</h4>
                <p>
                  I hereby hold F-Stop Fitness Center and its associates free
                  from any liabilities in the loss of personal properties and/or
                  physical injuries, accidents arising from use of the gym's
                  facilities and its equipment.
                </p>
              </div>

              <div class="terms-box">
                <h4>Action for Risky or Inappropriate Conduct</h4>
                <p>
                  If you behave in a risky or seriously inappropriate way, for
                  example, if you threaten or harass others, damage equipment,
                  distribute or use illicit substances, or train other Members
                  without authorization, appropriate action will be taken. Your
                  membership may be immediately suspended or cancelled.
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
    <script src="lightmode.js"></script>
  </body>
</html>


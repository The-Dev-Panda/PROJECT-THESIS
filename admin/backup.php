<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}

require_once("../Login/connection.php");

// Get last backup info
$stmt = $pdo->query("SELECT * FROM backup_history ORDER BY backup_date DESC LIMIT 1");
$last_backup = $stmt->fetch();

// Get all backup history
$stmt = $pdo->query("SELECT * FROM backup_history ORDER BY backup_date DESC LIMIT 20");
$backup_history = $stmt->fetchAll();

// Get total backups
$stmt = $pdo->query("SELECT COUNT(*) as total FROM backup_history");
$total_backups = $stmt->fetch()['total'];

// Get successful backups
$stmt = $pdo->query("SELECT COUNT(*) as total FROM backup_history WHERE status = 'success'");
$successful_backups = $stmt->fetch()['total'];

// Calculate next scheduled backup
$next_backup = $last_backup ? date('M d, Y', strtotime($last_backup['backup_date'] . ' +1 month')) : 'Not scheduled';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Database Backup - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .backup-btn {
            background: linear-gradient(135deg, var(--hazard) 0%, #ffd700 100%);
            color: #000;
            border: none;
            padding: 16px 32px;
            font-family: 'Chakra Petch', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            letter-spacing: 1px;
        }
        
        .backup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.4);
        }
        
        .backup-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .progress-container {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--hazard), #ffd700);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Chakra Petch', sans-serif;
            font-weight: 700;
            color: #000;
        }
        
        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .status-success {
            background: rgba(34, 208, 122, 0.2);
            color: var(--success);
        }
        
        .status-failed {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger);
        }
        
        .status-pending {
            background: rgba(255, 204, 0, 0.2);
            color: var(--hazard);
        }
    </style>
</head>
<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-database-fill-lock"></i> Database Backup</h1>
                <p>Secure your data with automated backups</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-shield-check"></i>
                    <span><?php echo $successful_backups; ?> Successful Backups</span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i> Backup completed and sent to email successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-box h-100">
                    <div class="stat-icon members"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stat-value" style="font-size: 14px;"><?php echo $last_backup ? date('M d, Y', strtotime($last_backup['backup_date'])) : 'Never'; ?></div>
                        <div class="stat-label">Last Backup</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-box h-100">
                    <div class="stat-icon registrations"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-value" style="font-size: 14px;"><?php echo $next_backup; ?></div>
                        <div class="stat-label">Next Scheduled</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-box h-100">
                    <div class="stat-icon equipment"><i class="bi bi-hdd-stack"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $total_backups; ?></div>
                        <div class="stat-label">Total Backups</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-box h-100">
                    <div class="stat-icon notifications"><i class="bi bi-envelope-check"></i></div>
                    <div>
                        <div class="stat-value" style="font-size: 12px;">noreplayfitstop@gmail.com</div>
                        <div class="stat-label">Backup Email</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Backup Section -->
        <section>
            <h2><i class="bi bi-download"></i> Manual Backup</h2>
            <div class="registration-card">
                <div class="row">
                    <div class="col-12 col-lg-8">
                        <h3 style="font-family: 'Chakra Petch', sans-serif; font-size: 16px; color: var(--hazard); margin-bottom: 10px;">
                            Create Database Backup
                        </h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                            This will create a compressed, encrypted backup of your entire database and send it to your email.
                            The backup will be password-protected for security.
                        </p>
                        
                        <div style="background: rgba(255, 204, 0, 0.1); border: 1px solid var(--hazard); padding: 15px; margin-bottom: 20px;">
                            <h4 style="font-size: 12px; color: var(--hazard); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
                                <i class="bi bi-info-circle"></i> Backup Information
                            </h4>
                            <ul style="margin: 0; padding-left: 20px; color: var(--text-sub); font-size: 12px;">
                                <li>Backup will be sent to: <strong>noreplayfitstop@gmail.com</strong></li>
                                <li>File format: Encrypted ZIP archive</li>
                                <li>Includes: All tables, data, and structure</li>
                                <li>Compression: Yes (for email size optimization)</li>
                                <li>Automated monthly backups are enabled</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-12 col-lg-4 d-flex align-items-center justify-content-center">
                        <button id="backupBtn" class="backup-btn" onclick="startBackup()">
                            <i class="bi bi-database-fill-down"></i> Create Backup Now
                        </button>
                    </div>
                </div>

                <div id="progressContainer" class="progress-container">
                    <div style="margin-bottom: 10px; color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                        <i class="bi bi-hourglass-split"></i> <span id="statusText">Initializing backup...</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressFill" class="progress-fill">0%</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Backup History -->
        <section>
            <h2><i class="bi bi-clock-history"></i> Backup History</h2>
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($backup_history) > 0): ?>
                            <?php foreach ($backup_history as $backup): ?>
                                <tr>
                                    <td><?php echo $backup['backup_id']; ?></td>
                                    <td>
                                        <div class="status-icon <?php echo $backup['status'] === 'success' ? 'status-success' : 'status-failed'; ?>">
                                            <i class="bi bi-<?php echo $backup['status'] === 'success' ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                        </div>
                                    </td>
                                    <td><code style="font-size: 11px;"><?php echo htmlspecialchars($backup['backup_filename']); ?></code></td>
                                    <td><?php echo $backup['backup_size'] ? number_format($backup['backup_size'] / 1024, 2) . ' KB' : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $backup['backup_type'] === 'manual' ? 'active' : 'low-stock'; ?>">
                                            <?php echo strtoupper($backup['backup_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($backup['sent_to_email']); ?></td>
                                    <td><?php echo htmlspecialchars($backup['created_by']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($backup['backup_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($backup['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No backup history found. Create your first backup!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function startBackup() {
            const btn = document.getElementById('backupBtn');
            const progressContainer = document.getElementById('progressContainer');
            const progressFill = document.getElementById('progressFill');
            const statusText = document.getElementById('statusText');
            
            btn.disabled = true;
            progressContainer.style.display = 'block';
            
            // Simulate progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                progressFill.style.width = progress + '%';
                progressFill.textContent = Math.round(progress) + '%';
                
                if (progress < 30) {
                    statusText.textContent = 'Exporting database...';
                } else if (progress < 60) {
                    statusText.textContent = 'Compressing files...';
                } else if (progress < 90) {
                    statusText.textContent = 'Encrypting backup...';
                }
            }, 200);
            
            // Send backup request
            fetch('process_backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'create_backup' })
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(interval);
                
                if (data.success) {
                    progressFill.style.width = '100%';
                    progressFill.textContent = '100%';
                    statusText.textContent = 'Sending to email...';
                    
                    setTimeout(() => {
                        window.location.href = 'backup.php?success=1';
                    }, 1000);
                } else {
                    statusText.textContent = 'Error: ' + data.error;
                    progressFill.style.background = 'var(--danger)';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                clearInterval(interval);
                statusText.textContent = 'Error: ' + error.message;
                progressFill.style.background = 'var(--danger)';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
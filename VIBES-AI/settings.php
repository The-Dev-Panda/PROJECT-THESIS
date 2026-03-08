<?php
/**
 * USER SETTINGS PAGE
 * 
 * Features:
 * - Manage Google AI Studio API Key (BYOK)
 * - Profile settings
 * - Notification preferences
 */

session_start();

// Security check
if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'user') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

require_once('../Login/connection.php');
require_once('GoogleAIAssistant.php');

$userId = $_SESSION['id'];
$username = $_SESSION['username'];

// Initialize AI assistant
$aiAssistant = new GoogleAIAssistant($pdo, $userId);
$hasApiKey = $aiAssistant->hasApiKey($userId);

// Get API key usage stats if exists
$apiKeyStats = null;
if ($hasApiKey) {
    $stmt = $pdo->prepare("SELECT usage_count, last_used, created_at FROM api_keys WHERE user_id = ? AND service_name = 'google_ai_studio' AND is_active = 1 LIMIT 1");
    $stmt->execute([$userId]);
    $apiKeyStats = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - FitStop</title>
    <link rel="stylesheet" href="user.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Chakra+Petch:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .settings-card h3 {
            color: #333;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .api-key-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
        }
        .api-key-section h2 {
            margin: 0 0 10px 0;
            font-size: 1.6rem;
        }
        .api-key-section p {
            opacity: 0.9;
            margin-bottom: 20px;
        }
        .api-key-input-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .api-key-input {
            flex: 1;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 0.95rem;
            font-family: 'Courier New', monospace;
        }
        .api-key-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        .btn-save-key {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-save-key:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.3);
        }
        .btn-delete-key {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .api-key-status {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        .api-key-status .stat {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 0.9rem;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            color: #0d47a1;
        }
        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }
        .info-box ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 5px 0;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin: 15px 0;
            display: none;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="userimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img" />
                <span class="logo-text">Fit-Stop</span>
            </div>
            <ul class="menu">
                <li><a href="user.php"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a></li>
                <li><a href="bmi.php"><i class="bi bi-heart-pulse"></i><span>BMI Tracker</span></a></li>
                <li><a href="myplan.php"><i class="bi bi-clipboard-check"></i><span>My Plan</span></a></li>
                <li><a href="history.php"><i class="bi bi-clock-history"></i><span>Exercise History</span></a></li>
                <li><a href="payments.php"><i class="bi bi-credit-card"></i><span>Payments</span></a></li>
                <li><a href="profile.php"><i class="bi bi-person"></i><span>Profile</span></a></li>
                <li class="active"><a href="settings.php"><i class="bi bi-gear"></i><span>Settings</span></a></li>
                <li><a href="../Login/logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="topbar">
                <div class="welcome">
                    <h1><i class="fas fa-cog"></i> Settings</h1>
                    <p>Manage your AI assistant and preferences</p>
                </div>
                <div class="topbar-actions">
                    <span class="user-badge"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </header>

            <div id="alertContainer"></div>

            <!-- AI API KEY SECTION -->
            <div class="api-key-section">
                <h2><i class="fas fa-robot"></i> AI Assistant Configuration</h2>
                <p>
                    <?php if ($hasApiKey): ?>
                        ✅ API Key Active - AI features enabled
                    <?php else: ?>
                        Enhance your workout experience with AI-powered tips, nutrition advice, and personalized guidance.
                    <?php endif; ?>
                </p>

                <?php if (!$hasApiKey): ?>
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> How to get your FREE Google AI Studio API Key:</strong>
                    <ol>
                        <li>Visit <a href="https://aistudio.google.com/" target="_blank" style="color: #2196F3;">https://aistudio.google.com/</a></li>
                        <li>Sign in with your Google account</li>
                        <li>Click "Get API Key" → "Create API Key"</li>
                        <li>Copy your API key and paste it below</li>
                    </ol>
                    <p style="margin-top: 10px;"><strong>Note:</strong> Your API key is stored securely and only you can use it. Google provides generous free quotas.</p>
                </div>

                <div class="api-key-input-group">
                    <input type="password" id="apiKeyInput" class="api-key-input" placeholder="AIzaSy..." />
                    <button class="btn-save-key" onclick="saveApiKey()">
                        <i class="fas fa-save"></i> Save API Key
                    </button>
                </div>
                <?php else: ?>
                <div class="api-key-status">
                    <div class="stat">
                        <span><strong>Status:</strong></span>
                        <span><i class="fas fa-check-circle"></i> Active</span>
                    </div>
                    <div class="stat">
                        <span><strong>Total Uses:</strong></span>
                        <span><?php echo number_format($apiKeyStats['usage_count']); ?></span>
                    </div>
                    <div class="stat">
                        <span><strong>Last Used:</strong></span>
                        <span>
                            <?php 
                            if ($apiKeyStats['last_used']) {
                                echo date('F j, Y g:i A', strtotime($apiKeyStats['last_used']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="stat">
                        <span><strong>Added:</strong></span>
                        <span><?php echo date('F j, Y', strtotime($apiKeyStats['created_at'])); ?></span>
                    </div>
                </div>
                
                <button class="btn-delete-key" onclick="deleteApiKey()">
                    <i class="fas fa-trash"></i> Remove API Key
                </button>
                <?php endif; ?>
            </div>

            <!-- GENERAL SETTINGS -->
            <div class="settings-card">
                <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                <p style="color: #666;">Manage how you receive updates and reminders</p>
                
                <div style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                        <input type="checkbox" checked> Workout reminder notifications
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                        <input type="checkbox" checked> Progress milestone alerts
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                        <input type="checkbox"> Weekly summary emails
                    </label>
                </div>
            </div>

            <div class="settings-card">
                <h3><i class="fas fa-info-circle"></i> About This System</h3>
                <p style="color: #666; line-height: 1.6;">
                    <strong>Fit-Stop Gym Management System</strong><br>
                    Version 1.0<br>
                    <br>
                    This system uses a <strong>rule-based workout generator</strong> as its core feature. 
                    AI assistance is an optional enhancement that requires your own Google AI Studio API key (BYOK).
                    <br><br>
                    <strong>Privacy:</strong> Your API key is encrypted and stored locally in our database. 
                    We never share or transmit it to any third party.
                </p>
            </div>

        </main>
    </div>

    <script>
        async function saveApiKey() {
            const apiKey = document.getElementById('apiKeyInput').value.trim();
            
            if (!apiKey) {
                showAlert('Please enter your API key', 'error');
                return;
            }
            
            if (!apiKey.startsWith('AIza')) {
                showAlert('Invalid API key format. Google AI Studio keys start with "AIza"', 'error');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('api_key', apiKey);
                
                const response = await fetch('../Database/workout_api.php?action=save_api_key', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('✅ API Key saved successfully! Reloading...', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('❌ ' + data.error, 'error');
                }
            } catch (error) {
                showAlert('❌ Request failed: ' + error.message, 'error');
            }
        }

        async function deleteApiKey() {
            if (!confirm('Are you sure you want to remove your API key? AI features will be disabled.')) {
                return;
            }
            
            try {
                const response = await fetch('../Database/workout_api.php?action=delete_api_key', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('✅ API Key removed. Reloading...', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('❌ ' + data.error, 'error');
                }
            } catch (error) {
                showAlert('❌ Request failed: ' + error.message, 'error');
            }
        }

        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = 'alert ' + type;
            alert.innerHTML = message;
            alert.style.display = 'block';
            
            container.innerHTML = '';
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>

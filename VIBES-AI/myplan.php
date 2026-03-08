<?php
/**
 * MEMBER WORKOUT PLAN PAGE
 * 
 * Features:
 * - Generate personalized workout plans (rule-based)
 * - View active workout plan
 * - Optional AI enhancement with BYOK
 * - Nutrition guidance
 * - AI assistant chat
 */

session_start();

// Security check
if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'user') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

require_once('../Login/connection.php');
require_once('WorkoutEngine.php');
require_once('GoogleAIAssistant.php');

$userId = $_SESSION['id'];
$username = $_SESSION['username'];

// Initialize engines
$workoutEngine = new WorkoutEngine($pdo);
$aiAssistant = new GoogleAIAssistant($pdo, $userId);

// Check for active plan
$activePlan = $workoutEngine->getActivePlan($userId);
$hasApiKey = $aiAssistant->hasApiKey($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workout Plan - FitStop</title>
    <link rel="stylesheet" href="user.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Chakra+Petch:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .plan-generator-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 25px;
        }
        .plan-generator-card h2 {
            margin: 0 0 15px 0;
            font-size: 1.8rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            opacity: 0.95;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 0.95rem;
        }
        .form-group select option {
            color: #333;
        }
        .btn-generate {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,255,255,0.3);
        }
        .workout-day-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .workout-day-card h3 {
            color: #667eea;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .exercise-item {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .exercise-item strong {
            color: #333;
            font-size: 1.05rem;
        }
        .exercise-specs {
            display: flex;
            gap: 20px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .spec-badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .ai-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 18px;
            padding: 25px;
            color: white;
            margin-top: 25px;
        }
        .ai-chat-messages {
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 15px;
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
            margin: 15px 0;
        }
        .ai-message {
            background: rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .ai-input-box {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .ai-input-box input {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .btn-ai-send {
            background: linear-gradient(135deg, #7c3aed, #06b6d4);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
        }
        .api-key-setup {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .loading {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                <li class="active"><a href="myplan.php"><i class="bi bi-clipboard-check"></i><span>My Plan</span></a></li>
                <li><a href="history.php"><i class="bi bi-clock-history"></i><span>Exercise History</span></a></li>
                <li><a href="payments.php"><i class="bi bi-credit-card"></i><span>Payments</span></a></li>
                <li><a href="profile.php"><i class="bi bi-person"></i><span>Profile</span></a></li>
                <li><a href="settings.php"><i class="bi bi-gear"></i><span>Settings</span></a></li>
                <li><a href="../Login/logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="topbar">
                <div class="welcome">
                    <h1><i class="fas fa-dumbbell"></i> My Workout Plan</h1>
                    <p>Personalized training powered by rule-based engine <?php echo $hasApiKey ? '+ AI' : ''; ?></p>
                </div>
                <div class="topbar-actions">
                    <span class="user-badge"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </header>

            <?php if (!$hasApiKey): ?>
            <div class="api-key-setup">
                <strong><i class="fas fa-info-circle"></i> Optional: AI Enhancement Available!</strong>
                <p>Get AI-powered workout tips and nutrition advice. Add your Google AI Studio API key in <a href="settings.php">Settings</a>.</p>
            </div>
            <?php endif; ?>

            <!-- PLAN GENERATOR -->
            <div class="plan-generator-card">
                <h2><i class="fas fa-magic"></i> Generate Your Workout Plan</h2>
                <p>Answer a few questions to get a personalized plan tailored to your goals</p>
                
                <form id="planGeneratorForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Experience Level</label>
                            <select name="experience_level" required>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fitness Goal</label>
                            <select name="fitness_goal" required>
                                <option value="general_fitness">General Fitness</option>
                                <option value="weight_loss">Weight Loss</option>
                                <option value="muscle_gain">Muscle Gain</option>
                                <option value="strength">Strength Building</option>
                                <option value="endurance">Endurance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Workout Days Per Week</label>
                            <select name="workout_days" required>
                                <option value="3">3 Days</option>
                                <option value="4">4 Days</option>
                                <option value="5">5 Days</option>
                                <option value="6">6 Days</option>
                            </select>
                        </div>
                        <?php if ($hasApiKey): ?>
                        <div class="form-group">
                            <label>Use AI Enhancement</label>
                            <select name="use_ai">
                                <option value="true">Yes (AI Tips)</option>
                                <option value="false">No (Rules Only)</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn-generate">
                        <i class="fas fa-bolt"></i> Generate My Plan
                    </button>
                </form>
            </div>

            <!-- ACTIVE PLAN DISPLAY -->
            <div id="activePlanContainer">
                <?php if ($activePlan): ?>
                    <h2><i class="fas fa-calendar-alt"></i> Your Active Plan: <?php echo htmlspecialchars($activePlan['plan_name']); ?></h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        Generated <?php echo date('F j, Y', strtotime($activePlan['generated_at'])); ?> 
                        • Method: <?php echo $activePlan['generation_method'] === 'ai_assisted' ? 'AI-Assisted' : 'Rule-Based'; ?>
                    </p>
                    
                    <div id="planContent">
                        <?php 
                        $planData = json_decode($activePlan['plan_data'], true);
                        foreach ($planData as $day): 
                        ?>
                            <div class="workout-day-card">
                                <h3>
                                    <i class="fas fa-calendar-day"></i>
                                    <?php echo htmlspecialchars($day['day']); ?> - <?php echo htmlspecialchars($day['focus']); ?>
                                </h3>
                                
                                <?php foreach ($day['exercises'] as $exercise): ?>
                                    <div class="exercise-item">
                                        <strong><?php echo htmlspecialchars($exercise['name']); ?></strong>
                                        <div class="exercise-specs">
                                            <span class="spec-badge"><i class="fas fa-repeat"></i> <?php echo $exercise['recommended_sets']; ?> sets</span>
                                            <span class="spec-badge"><i class="fas fa-hashtag"></i> <?php echo $exercise['recommended_reps']; ?> reps</span>
                                            <span class="spec-badge"><i class="fas fa-clock"></i> <?php echo $exercise['recommended_rest']; ?>s rest</span>
                                            <span class="spec-badge"><i class="fas fa-dumbbell"></i> <?php echo ucfirst($exercise['equipment']); ?></span>
                                        </div>
                                        <p style="color: #666; margin-top: 8px; font-size: 0.9rem;">
                                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($exercise['description']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($activePlan['ai_recommendations'])): ?>
                    <div class="ai-section">
                        <h3><i class="fas fa-robot"></i> AI Recommendations</h3>
                        <div class="ai-message">
                            <?php echo nl2br(htmlspecialchars($activePlan['ai_recommendations'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3>No Active Plan Yet</h3>
                        <p>Generate your first workout plan above to get started!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- AI ASSISTANT CHAT -->
            <?php if ($hasApiKey): ?>
            <div class="ai-section">
                <h3><i class="fas fa-comments"></i> AI Assistant - Ask Me Anything</h3>
                <p style="opacity: 0.8; font-size: 0.9rem;">Ask about exercises, form tips, nutrition, or motivation</p>
                
                <div class="ai-chat-messages" id="aiChatMessages">
                    <div class="ai-message">
                        <strong>AI Assistant:</strong> Hi <?php echo htmlspecialchars($username); ?>! I'm here to help with your fitness journey. Ask me anything about workouts, nutrition, or form!
                    </div>
                </div>
                
                <div class="ai-input-box">
                    <input type="text" id="aiQuestionInput" placeholder="Ask a question..." />
                    <button class="btn-ai-send" onclick="askAI()">
                        <i class="fas fa-paper-plane"></i> Ask
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        // Generate workout plan
        document.getElementById('planGeneratorForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner loading"></i> Generating...';
            button.disabled = true;
            
            try {
                const response = await fetch('../Database/workout_api.php?action=generate_plan', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Workout plan generated successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            } catch (error) {
                alert('❌ Request failed: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        });

        // AI Assistant Chat
        async function askAI() {
            const input = document.getElementById('aiQuestionInput');
            const question = input.value.trim();
            
            if (!question) {
                alert('Please enter a question');
                return;
            }
            
            const messagesContainer = document.getElementById('aiChatMessages');
            
            // Add user message
            const userMsg = document.createElement('div');
            userMsg.className = 'ai-message';
            userMsg.innerHTML = '<strong>You:</strong> ' + escapeHtml(question);
            messagesContainer.appendChild(userMsg);
            
            // Add loading message
            const loadingMsg = document.createElement('div');
            loadingMsg.className = 'ai-message';
            loadingMsg.innerHTML = '<strong>AI:</strong> <i class="fas fa-spinner loading"></i> Thinking...';
            messagesContainer.appendChild(loadingMsg);
            
            input.value = '';
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            try {
                const formData = new FormData();
                formData.append('question', question);
                
                const response = await fetch('../Database/workout_api.php?action=ai_question', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Remove loading message
                loadingMsg.remove();
                
                // Add AI response
                const aiMsg = document.createElement('div');
                aiMsg.className = 'ai-message';
                
                if (data.success) {
                    aiMsg.innerHTML = '<strong>AI:</strong> ' + escapeHtml(data.response).replace(/\n/g, '<br>');
                } else {
                    aiMsg.innerHTML = '<strong>AI:</strong> <span style="color: #ff6b6b;">' + escapeHtml(data.error) + '</span>';
                }
                
                messagesContainer.appendChild(aiMsg);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } catch (error) {
                loadingMsg.remove();
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'ai-message';
                errorMsg.innerHTML = '<strong>AI:</strong> <span style="color: #ff6b6b;">Request failed. Please try again.</span>';
                messagesContainer.appendChild(errorMsg);
            }
        }

        // Handle Enter key in AI input
        document.getElementById('aiQuestionInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                askAI();
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

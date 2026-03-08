<?php
/**
 * WORKOUT PLAN GENERATOR API
 * 
 * Endpoints:
 * - generate_plan: Create new workout plan
 * - get_active_plan: Fetch current plan
 * - log_workout: Record workout completion
 * - ai_enhance: Get AI recommendations (if API key exists)
 * - ai_nutrition: Get nutrition advice
 * - ai_question: Ask AI assistant
 */

session_start();
header('Content-Type: application/json');

// Security: Check if user is logged in
if (empty($_SESSION['username']) || empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once('../Login/connection.php');
require_once('WorkoutEngine.php');
require_once('GoogleAIAssistant.php');

$userId = $_SESSION['id'];
$action = $_GET['action'] ?? '';

// Initialize engines
$workoutEngine = new WorkoutEngine($pdo);
$aiAssistant = new GoogleAIAssistant($pdo, $userId);

try {
    switch ($action) {
        case 'generate_plan':
            // Generate new workout plan
            $params = [
                'experience_level' => $_POST['experience_level'] ?? 'beginner',
                'fitness_goal' => $_POST['fitness_goal'] ?? 'general_fitness',
                'workout_days' => (int) ($_POST['workout_days'] ?? 3)
            ];
            
            // Validate inputs
            if (!in_array($params['experience_level'], ['beginner', 'intermediate', 'advanced'])) {
                throw new Exception('Invalid experience level');
            }
            
            if (!in_array($params['fitness_goal'], ['weight_loss', 'muscle_gain', 'strength', 'endurance', 'general_fitness'])) {
                throw new Exception('Invalid fitness goal');
            }
            
            // Generate rule-based plan
            $result = $workoutEngine->generatePlan($userId, $params);
            
            // Optionally enhance with AI if user has API key
            if ($aiAssistant->hasApiKey($userId) && isset($_POST['use_ai']) && $_POST['use_ai'] === 'true') {
                $profile = [
                    'experience_level' => $params['experience_level'],
                    'fitness_goal' => $params['fitness_goal'],
                    'bmi' => $_POST['bmi'] ?? null
                ];
                
                $aiEnhancement = $aiAssistant->enhanceWorkoutPlan($userId, $result['plan'], $profile);
                
                if ($aiEnhancement['success']) {
                    $result['ai_recommendations'] = $aiEnhancement['recommendations'];
                    
                    // Update plan with AI recommendations
                    $stmt = $pdo->prepare("UPDATE generated_plans SET ai_recommendations = ?, generation_method = 'ai_assisted' WHERE id = ?");
                    $stmt->execute([$aiEnhancement['recommendations'], $result['plan_id']]);
                }
            }
            
            echo json_encode($result);
            break;
            
        case 'get_active_plan':
            // Get user's active workout plan
            $plan = $workoutEngine->getActivePlan($userId);
            
            if ($plan) {
                echo json_encode(['success' => true, 'plan' => $plan]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No active plan found', 'needs_generation' => true]);
            }
            break;
            
        case 'log_workout':
            // Log workout completion
            $exerciseData = [
                'exercise_name' => $_POST['exercise_name'] ?? '',
                'sets' => (int) ($_POST['sets'] ?? 0),
                'reps' => (int) ($_POST['reps'] ?? 0),
                'weight' => (float) ($_POST['weight'] ?? 0),
                'difficulty' => (int) ($_POST['difficulty'] ?? 3),
                'notes' => $_POST['notes'] ?? ''
            ];
            
            $planId = (int) ($_POST['plan_id'] ?? 0);
            
            $logId = $workoutEngine->logWorkout($userId, $planId, $exerciseData);
            
            echo json_encode(['success' => true, 'log_id' => $logId, 'message' => 'Workout logged successfully!']);
            break;
            
        case 'ai_nutrition':
            // Get AI nutrition advice
            $profile = [
                'fitness_goal' => $_POST['fitness_goal'] ?? 'general_fitness',
                'bmi' => (float) ($_POST['bmi'] ?? 0)
            ];
            
            $advice = $aiAssistant->getNutritionAdvice($userId, $profile);
            echo json_encode($advice);
            break;
            
        case 'ai_question':
            // Ask AI assistant
            $question = $_POST['question'] ?? '';
            
            if (empty($question)) {
                throw new Exception('Question is required');
            }
            
            $context = [
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $response = $aiAssistant->askQuestion($userId, $question, $context);
            echo json_encode($response);
            break;
            
        case 'save_api_key':
            // Save Google AI Studio API key
            $apiKey = $_POST['api_key'] ?? '';
            
            if (empty($apiKey)) {
                throw new Exception('API key is required');
            }
            
            $result = $aiAssistant->saveApiKey($userId, $apiKey);
            echo json_encode($result);
            break;
            
        case 'delete_api_key':
            // Remove API key
            $result = $aiAssistant->deleteApiKey($userId);
            echo json_encode($result);
            break;
            
        case 'check_api_key':
            // Check if user has API key
            $hasKey = $aiAssistant->hasApiKey($userId);
            echo json_encode(['success' => true, 'has_key' => $hasKey]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

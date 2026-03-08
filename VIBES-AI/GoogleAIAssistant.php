<?php
/**
 * GOOGLE AI STUDIO INTEGRATION (BYOK - Bring Your Own Key)
 * 
 * Optional enhancement to the rule-based system.
 * Uses Google's Gemini API to provide:
 * - Natural language workout recommendations
 * - Nutrition guidance
 * - Form tips and motivation
 * 
 * Users provide their own API key from https://aistudio.google.com/
 */

class GoogleAIAssistant {
    private $pdo;
    private $apiKey;
    private $apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        
        if ($userId) {
            $this->loadUserApiKey($userId);
        }
    }
    
    /**
     * Load user's API key from database
     */
    private function loadUserApiKey($userId) {
        $stmt = $this->pdo->prepare("SELECT api_key FROM api_keys WHERE user_id = ? AND service_name = 'google_ai_studio' AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->apiKey = $result['api_key'];
        }
    }
    
    /**
     * Save user's API key
     */
    public function saveApiKey($userId, $apiKey) {
        // Validate API key first
        $isValid = $this->validateApiKey($apiKey);
        
        if (!$isValid) {
            return ['success' => false, 'error' => 'Invalid API key. Please check your key from Google AI Studio.'];
        }
        
        // Deactivate old keys
        $stmt = $this->pdo->prepare("UPDATE api_keys SET is_active = 0 WHERE user_id = ? AND service_name = 'google_ai_studio'");
        $stmt->execute([$userId]);
        
        // Insert new key
        $stmt = $this->pdo->prepare("INSERT INTO api_keys (user_id, service_name, api_key, is_active) VALUES (?, 'google_ai_studio', ?, 1)");
        $stmt->execute([$userId, $apiKey]);
        
        return ['success' => true, 'message' => 'API key saved successfully!'];
    }
    
    /**
     * Delete user's API key
     */
    public function deleteApiKey($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM api_keys WHERE user_id = ? AND service_name = 'google_ai_studio'");
        $stmt->execute([$userId]);
        
        return ['success' => true, 'message' => 'API key removed'];
    }
    
    /**
     * Check if user has active API key
     */
    public function hasApiKey($userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM api_keys WHERE user_id = ? AND service_name = 'google_ai_studio' AND is_active = 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Validate API key by making test request
     */
    private function validateApiKey($apiKey) {
        $testPrompt = "Say 'OK' if this works";
        
        try {
            $response = $this->makeApiCall($apiKey, $testPrompt);
            return !empty($response);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Enhance workout plan with AI recommendations
     */
    public function enhanceWorkoutPlan($userId, $generatedPlan, $memberProfile) {
        if (!$this->apiKey) {
            return ['success' => false, 'error' => 'No API key configured. Using rule-based plan only.'];
        }
        
        $prompt = $this->buildWorkoutEnhancementPrompt($generatedPlan, $memberProfile);
        
        try {
            $aiResponse = $this->makeApiCall($this->apiKey, $prompt);
            
            // Update usage count
            $this->incrementUsageCount($userId);
            
            return [
                'success' => true,
                'recommendations' => $aiResponse,
                'enhanced_plan' => $generatedPlan
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get personalized nutrition advice
     */
    public function getNutritionAdvice($userId, $memberProfile) {
        if (!$this->apiKey) {
            return $this->getRuleBasedNutrition($memberProfile);
        }
        
        $prompt = $this->buildNutritionPrompt($memberProfile);
        
        try {
            $aiResponse = $this->makeApiCall($this->apiKey, $prompt);
            $this->incrementUsageCount($userId);
            
            return ['success' => true, 'advice' => $aiResponse];
            
        } catch (Exception $e) {
            // Fallback to rule-based
            return $this->getRuleBasedNutrition($memberProfile);
        }
    }
    
    /**
     * Ask AI assistant a question
     */
    public function askQuestion($userId, $question, $context = []) {
        if (!$this->apiKey) {
            return ['success' => false, 'error' => 'Please add your Google AI Studio API key in Settings to use the AI assistant.'];
        }
        
        $prompt = $this->buildQuestionPrompt($question, $context);
        
        try {
            $aiResponse = $this->makeApiCall($this->apiKey, $prompt);
            $this->incrementUsageCount($userId);
            
            return ['success' => true, 'response' => $aiResponse];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'AI request failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Build workout enhancement prompt
     */
    private function buildWorkoutEnhancementPrompt($plan, $profile) {
        $planSummary = json_encode($plan, JSON_PRETTY_PRINT);
        $experience = $profile['experience_level'] ?? 'beginner';
        $goal = $profile['fitness_goal'] ?? 'general_fitness';
        
        return "You are a certified personal trainer at Fit-Stop Gym. Review this generated workout plan and provide brief enhancement tips.

**Member Profile:**
- Experience Level: $experience
- Fitness Goal: $goal
- BMI: " . ($profile['bmi'] ?? 'Not recorded') . "

**Generated Plan:**
$planSummary

Provide:
1. Brief form tips for key exercises (2-3 sentences each for top 3 exercises)
2. Motivation tip related to their goal
3. One nutrition suggestion

Keep response under 300 words, friendly and encouraging tone.";
    }
    
    /**
     * Build nutrition prompt
     */
    private function buildNutritionPrompt($profile) {
        $goal = $profile['fitness_goal'] ?? 'general_fitness';
        $bmi = $profile['bmi'] ?? 'unknown';
        
        return "You are a fitness nutrition advisor. Provide simple, practical nutrition guidance for a gym member.

**Member Info:**
- Fitness Goal: $goal
- BMI: $bmi

Provide:
1. Daily calorie range recommendation
2. Macronutrient split (protein/carbs/fats)
3. 3 meal suggestions (breakfast, lunch, post-workout)
4. 2 hydration tips

Keep it simple, practical, and under 250 words. Use Filipino context for food availability.";
    }
    
    /**
     * Build question prompt
     */
    private function buildQuestionPrompt($question, $context) {
        $contextStr = empty($context) ? '' : "\n\n**Context:** " . json_encode($context);
        
        return "You are a knowledgeable gym assistant at Fit-Stop Gym. Answer this member's question clearly and helpfully.

**Question:** $question
$contextStr

Provide a concise, actionable answer (under 150 words). Be friendly and encouraging.";
    }
    
    /**
     * Make API call to Google AI Studio
     */
    private function makeApiCall($apiKey, $prompt) {
        $url = $this->apiEndpoint . '?key=' . $apiKey;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API request failed with code $httpCode");
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
        
        throw new Exception("Unexpected API response format");
    }
    
    /**
     * Increment API usage counter
     */
    private function incrementUsageCount($userId) {
        $stmt = $this->pdo->prepare("UPDATE api_keys SET usage_count = usage_count + 1, last_used = CURRENT_TIMESTAMP WHERE user_id = ? AND service_name = 'google_ai_studio' AND is_active = 1");
        $stmt->execute([$userId]);
    }
    
    /**
     * Fallback: Rule-based nutrition advice
     */
    private function getRuleBasedNutrition($profile) {
        $goal = $profile['fitness_goal'] ?? 'general_fitness';
        
        $advice = [
            'weight_loss' => [
                'calories' => '1500-1800 kcal/day',
                'macros' => 'Protein: 30%, Carbs: 40%, Fats: 30%',
                'tips' => [
                    'Focus on lean proteins (chicken, fish, eggs)',
                    'Reduce rice portions, add more vegetables',
                    'Drink 8-10 glasses of water daily',
                    'Eat smaller meals every 3-4 hours'
                ]
            ],
            'muscle_gain' => [
                'calories' => '2200-2800 kcal/day',
                'macros' => 'Protein: 35%, Carbs: 45%, Fats: 20%',
                'tips' => [
                    'Eat 1.6-2g protein per kg bodyweight',
                    'Post-workout: rice + chicken/fish within 1 hour',
                    'Include healthy fats: nuts, eggs, avocado',
                    'Consistent meal timing every day'
                ]
            ],
            'strength' => [
                'calories' => '2000-2500 kcal/day',
                'macros' => 'Protein: 30%, Carbs: 45%, Fats: 25%',
                'tips' => [
                    'Quality protein sources at every meal',
                    'Complex carbs for energy: rice, oats, sweet potato',
                    'Pre-workout: banana + coffee 30-60 mins before',
                    'Stay hydrated during heavy lifting'
                ]
            ],
            'general_fitness' => [
                'calories' => '1800-2200 kcal/day',
                'macros' => 'Protein: 25%, Carbs: 50%, Fats: 25%',
                'tips' => [
                    'Balanced meals: protein, carbs, vegetables',
                    'Eat breakfast within 1 hour of waking',
                    'Avoid excessive sugar and processed foods',
                    'Meal prep on weekends for consistency'
                ]
            ]
        ];
        
        $data = $advice[$goal] ?? $advice['general_fitness'];
        
        return [
            'success' => true,
            'advice' => "**Nutrition Guidelines for " . ucfirst(str_replace('_', ' ', $goal)) . "**\n\n" .
                       "**Daily Calories:** " . $data['calories'] . "\n" .
                       "**Macros:** " . $data['macros'] . "\n\n" .
                       "**Tips:**\n- " . implode("\n- ", $data['tips']),
            'source' => 'rule_based'
        ];
    }
}

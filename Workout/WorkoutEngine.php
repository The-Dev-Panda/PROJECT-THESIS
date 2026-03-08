<?php
/**
 * RULE-BASED WORKOUT ENGINE
 * 
 * Core algorithm for generating personalized workout plans based on:
 * - Member experience level (beginner/intermediate/advanced)
 * - Fitness goals (weight loss, muscle gain, strength, endurance, general fitness)
 * - Available gym equipment
 * - Admin-configured rules
 * 
 * This is the PRIMARY thesis feature - structured rule-based system.
 * AI assistance is OPTIONAL enhancement via BYOK.
 */

class WorkoutEngine {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * MAIN PLAN GENERATION METHOD
     * 
     * @param int $userId User ID from session
     * @param array $params User preferences (experience, goal, days)
     * @return array Generated workout plan
     */
    public function generatePlan($userId, $params) {
        // 1. Get member profile or create default
        $profile = $this->getMemberProfile($userId, $params);
        
        // 2. Find matching workout rule
        $rule = $this->findMatchingRule($profile['experience_level'], $profile['fitness_goal']);
        
        // 3. Get appropriate exercises
        $exercises = $this->selectExercises($profile['experience_level'], $profile['fitness_goal'], $rule['exercises_per_session']);
        
        // 4. Build weekly workout split
        $weeklyPlan = $this->buildWeeklySplit($exercises, $rule);
        
        // 5. Apply rule multipliers (sets, reps, rest)
        $adjustedPlan = $this->applyRuleMultipliers($weeklyPlan, $rule);
        
        // 6. Save to database
        $planId = $this->savePlan($userId, $profile, $rule, $adjustedPlan, 'rule_based');
        
        return [
            'success' => true,
            'plan_id' => $planId,
            'plan' => $adjustedPlan,
            'profile' => $profile,
            'rule_applied' => $rule['rule_name']
        ];
    }
    
    /**
     * Get or create member profile
     */
    private function getMemberProfile($userId, $params) {
        $stmt = $this->pdo->prepare("SELECT * FROM member_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profile) {
            // Create new profile with defaults
            $stmt = $this->pdo->prepare("INSERT INTO member_profiles (user_id, experience_level, fitness_goal, workout_days_per_week) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $params['experience_level'] ?? 'beginner',
                $params['fitness_goal'] ?? 'general_fitness',
                $params['workout_days'] ?? 3
            ]);
            
            // Fetch newly created profile
            $stmt = $this->pdo->prepare("SELECT * FROM member_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Update existing profile with new preferences
            $stmt = $this->pdo->prepare("UPDATE member_profiles SET experience_level = ?, fitness_goal = ?, workout_days_per_week = ?, last_updated = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([
                $params['experience_level'] ?? $profile['experience_level'],
                $params['fitness_goal'] ?? $profile['fitness_goal'],
                $params['workout_days'] ?? $profile['workout_days_per_week'],
                $userId
            ]);
            
            // Refresh profile
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $profile;
    }
    
    /**
     * Find matching workout rule from database
     */
    private function findMatchingRule($experienceLevel, $fitnessGoal) {
        $stmt = $this->pdo->prepare("SELECT * FROM workout_rules WHERE experience_level = ? AND goal = ? AND is_active = 1 ORDER BY priority_order ASC LIMIT 1");
        $stmt->execute([$experienceLevel, $fitnessGoal]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rule) {
            // Fallback: general fitness rule for experience level
            $stmt = $this->pdo->prepare("SELECT * FROM workout_rules WHERE experience_level = ? AND goal = 'general_fitness' AND is_active = 1 LIMIT 1");
            $stmt->execute([$experienceLevel]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $rule;
    }
    
    /**
     * Select exercises based on difficulty and goal
     */
    private function selectExercises($experienceLevel, $fitnessGoal, $exerciseCount) {
        // Muscle group distribution based on goal
        $distributions = [
            'weight_loss' => ['cardio' => 2, 'legs' => 2, 'chest' => 1, 'back' => 1],
            'muscle_gain' => ['chest' => 2, 'back' => 2, 'legs' => 2, 'shoulders' => 1, 'biceps' => 1, 'triceps' => 1],
            'strength' => ['legs' => 3, 'chest' => 2, 'back' => 2, 'shoulders' => 1],
            'endurance' => ['cardio' => 3, 'legs' => 2, 'back' => 1, 'chest' => 1],
            'general_fitness' => ['legs' => 2, 'chest' => 1, 'back' => 1, 'shoulders' => 1, 'cardio' => 1]
        ];
        
        $distribution = $distributions[$fitnessGoal] ?? $distributions['general_fitness'];
        $allExercises = [];
        
        foreach ($distribution as $muscleGroup => $count) {
            $stmt = $this->pdo->prepare("SELECT * FROM exercises WHERE muscle_group = ? AND difficulty = ? AND is_active = 1 ORDER BY RANDOM() LIMIT ?");
            $stmt->execute([$muscleGroup, $experienceLevel, $count]);
            $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If not enough exercises for exact difficulty, get any difficulty
            if (count($exercises) < $count) {
                $stmt = $this->pdo->prepare("SELECT * FROM exercises WHERE muscle_group = ? AND is_active = 1 ORDER BY RANDOM() LIMIT ?");
                $stmt->execute([$muscleGroup, $count]);
                $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $allExercises = array_merge($allExercises, $exercises);
        }
        
        return array_slice($allExercises, 0, $exerciseCount);
    }
    
    /**
     * Build weekly workout split
     */
    private function buildWeeklySplit($exercises, $rule) {
        $daysPerWeek = $rule['workout_days_per_week'];
        $exercisesPerDay = ceil(count($exercises) / $daysPerWeek);
        
        $weeklyPlan = [];
        $exerciseChunks = array_chunk($exercises, $exercisesPerDay);
        
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        for ($i = 0; $i < $daysPerWeek; $i++) {
            if (isset($exerciseChunks[$i])) {
                $weeklyPlan[] = [
                    'day' => $dayNames[$i],
                    'day_number' => $i + 1,
                    'exercises' => $exerciseChunks[$i],
                    'focus' => $this->getDayFocus($exerciseChunks[$i])
                ];
            }
        }
        
        return $weeklyPlan;
    }
    
    /**
     * Determine workout focus for the day
     */
    private function getDayFocus($exercises) {
        $muscleGroups = array_column($exercises, 'muscle_group');
        $counts = array_count_values($muscleGroups);
        arsort($counts);
        $primary = key($counts);
        
        $focusNames = [
            'chest' => 'Chest & Push',
            'back' => 'Back & Pull',
            'legs' => 'Legs & Lower Body',
            'shoulders' => 'Shoulders & Arms',
            'cardio' => 'Cardio & Conditioning',
            'biceps' => 'Arms',
            'triceps' => 'Arms'
        ];
        
        return $focusNames[$primary] ?? 'Full Body';
    }
    
    /**
     * Apply rule multipliers to sets, reps, rest times
     */
    private function applyRuleMultipliers($weeklyPlan, $rule) {
        foreach ($weeklyPlan as &$day) {
            foreach ($day['exercises'] as &$exercise) {
                // Apply sets multiplier
                $baseSets = rand($exercise['sets_min'], $exercise['sets_max']);
                $exercise['recommended_sets'] = max(1, round($baseSets * $rule['sets_multiplier']));
                
                // Apply reps (higher for weight loss, lower for strength)
                $exercise['recommended_reps'] = rand($exercise['reps_min'], $exercise['reps_max']);
                
                // Apply rest time multiplier
                $exercise['recommended_rest'] = round($exercise['rest_seconds'] * $rule['rest_time_multiplier']);
                
                // Add intensity guidance
                $exercise['intensity'] = $this->calculateIntensity($rule);
            }
        }
        
        return $weeklyPlan;
    }
    
    /**
     * Calculate intensity level
     */
    private function calculateIntensity($rule) {
        if ($rule['sets_multiplier'] >= 1.3 && $rule['rest_time_multiplier'] >= 1.3) {
            return 'High Intensity - Heavy Weight';
        } elseif ($rule['rest_time_multiplier'] <= 0.7) {
            return 'High Tempo - Short Rest';
        } elseif ($rule['sets_multiplier'] <= 0.9) {
            return 'Moderate - Focus on Form';
        } else {
            return 'Standard Training';
        }
    }
    
    /**
     * Save generated plan to database
     */
    private function savePlan($userId, $profile, $rule, $plan, $method = 'rule_based') {
        $planName = ucfirst($profile['experience_level']) . " " . str_replace('_', ' ', ucfirst($profile['fitness_goal'])) . " Plan";
        $planData = json_encode($plan);
        
        // Deactivate old plans
        $stmt = $this->pdo->prepare("UPDATE generated_plans SET is_active = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Insert new plan
        $stmt = $this->pdo->prepare("INSERT INTO generated_plans (user_id, plan_name, generation_method, experience_level, fitness_goal, workout_days, plan_data, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $userId,
            $planName,
            $method,
            $profile['experience_level'],
            $profile['fitness_goal'],
            $rule['workout_days_per_week'],
            $planData
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get active plan for user
     */
    public function getActivePlan($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM generated_plans WHERE user_id = ? AND is_active = 1 ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            $plan['plan_data'] = json_decode($plan['plan_data'], true);
        }
        
        return $plan;
    }
    
    /**
     * Log workout completion
     */
    public function logWorkout($userId, $planId, $exerciseData) {
        $stmt = $this->pdo->prepare("INSERT INTO workout_logs (user_id, plan_id, exercise_name, sets_completed, reps_completed, weight_used, difficulty_rating, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $userId,
            $planId,
            $exerciseData['exercise_name'],
            $exerciseData['sets'] ?? null,
            $exerciseData['reps'] ?? null,
            $exerciseData['weight'] ?? null,
            $exerciseData['difficulty'] ?? null,
            $exerciseData['notes'] ?? null
        ]);
        
        // Update leaderboard
        $this->updateLeaderboard($userId);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update leaderboard stats
     */
    private function updateLeaderboard($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM leaderboard WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stats) {
            $stmt = $this->pdo->prepare("INSERT INTO leaderboard (user_id, total_workouts, current_streak, longest_streak, last_workout_date) VALUES (?, 1, 1, 1, DATE('now'))");
            $stmt->execute([$userId]);
        } else {
            $lastWorkout = $stats['last_workout_date'];
            $today = date('Y-m-d');
            
            // Calculate streak
            $lastDate = new DateTime($lastWorkout);
            $currentDate = new DateTime($today);
            $diff = $lastDate->diff($currentDate)->days;
            
            $newStreak = ($diff <= 1) ? $stats['current_streak'] + 1 : 1;
            $longestStreak = max($stats['longest_streak'], $newStreak);
            
            $stmt = $this->pdo->prepare("UPDATE leaderboard SET total_workouts = total_workouts + 1, current_streak = ?, longest_streak = ?, last_workout_date = DATE('now'), updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$newStreak, $longestStreak, $userId]);
        }
    }
}

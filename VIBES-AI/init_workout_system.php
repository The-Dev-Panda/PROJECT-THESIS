<?php
/**
 * WORKOUT SYSTEM DATABASE INITIALIZATION
 * 
 * Creates all necessary tables for the rule-based workout generator:
 * - exercises: Gym equipment database
 * - workout_rules: Rule engine configuration
 * - member_profiles: Member fitness data
 * - generated_plans: System-generated workout plans
 * - workout_logs: Member exercise history
 * - api_keys: Optional Google AI Studio keys (BYOK)
 * 
 * Run this once to set up the database schema.
 */

require_once('../Login/connection.php');

try {
    // 1. EXERCISES DATABASE - All gym equipment and exercises
    $pdo->exec("CREATE TABLE IF NOT EXISTS exercises (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        equipment TEXT NOT NULL,
        muscle_group TEXT NOT NULL,
        difficulty TEXT CHECK(difficulty IN ('beginner', 'intermediate', 'advanced')),
        description TEXT,
        video_url TEXT,
        sets_min INTEGER DEFAULT 3,
        sets_max INTEGER DEFAULT 4,
        reps_min INTEGER DEFAULT 8,
        reps_max INTEGER DEFAULT 12,
        rest_seconds INTEGER DEFAULT 60,
        is_active BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. WORKOUT RULES - Admin-configurable rule engine
    $pdo->exec("CREATE TABLE IF NOT EXISTS workout_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rule_name TEXT NOT NULL,
        experience_level TEXT CHECK(experience_level IN ('beginner', 'intermediate', 'advanced')),
        goal TEXT CHECK(goal IN ('weight_loss', 'muscle_gain', 'strength', 'endurance', 'general_fitness')),
        workout_days_per_week INTEGER,
        exercises_per_session INTEGER,
        sets_multiplier REAL DEFAULT 1.0,
        rest_time_multiplier REAL DEFAULT 1.0,
        priority_order INTEGER,
        is_active BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. MEMBER PROFILES - Member fitness data and goals
    $pdo->exec("CREATE TABLE IF NOT EXISTS member_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        experience_level TEXT DEFAULT 'beginner',
        fitness_goal TEXT DEFAULT 'general_fitness',
        current_weight REAL,
        target_weight REAL,
        height_cm REAL,
        age INTEGER,
        bmi REAL,
        workout_days_per_week INTEGER DEFAULT 3,
        health_conditions TEXT,
        preferences TEXT,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 4. GENERATED PLANS - System-generated workout plans
    $pdo->exec("CREATE TABLE IF NOT EXISTS generated_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_name TEXT NOT NULL,
        generation_method TEXT CHECK(generation_method IN ('rule_based', 'ai_assisted')),
        experience_level TEXT,
        fitness_goal TEXT,
        workout_days INTEGER,
        plan_data TEXT NOT NULL,
        ai_recommendations TEXT,
        is_active BOOLEAN DEFAULT 1,
        generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        trainer_override BOOLEAN DEFAULT 0,
        override_notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 5. WORKOUT LOGS - Member exercise tracking
    $pdo->exec("CREATE TABLE IF NOT EXISTS workout_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_id INTEGER,
        exercise_name TEXT NOT NULL,
        sets_completed INTEGER,
        reps_completed INTEGER,
        weight_used REAL,
        duration_minutes INTEGER,
        difficulty_rating INTEGER CHECK(difficulty_rating BETWEEN 1 AND 5),
        notes TEXT,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES generated_plans(id) ON DELETE SET NULL
    )");

    // 6. API KEYS - BYOK for Google AI Studio
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        service_name TEXT DEFAULT 'google_ai_studio',
        api_key TEXT NOT NULL,
        is_active BOOLEAN DEFAULT 1,
        usage_count INTEGER DEFAULT 0,
        last_used DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 7. LEADERBOARD - Track member achievements
    $pdo->exec("CREATE TABLE IF NOT EXISTS leaderboard (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        total_workouts INTEGER DEFAULT 0,
        current_streak INTEGER DEFAULT 0,
        longest_streak INTEGER DEFAULT 0,
        total_weight_lifted REAL DEFAULT 0,
        badges TEXT,
        last_workout_date DATE,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 8. ATTENDANCE - Track gym check-ins
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        check_in_time DATETIME NOT NULL,
        check_out_time DATETIME,
        scan_method TEXT CHECK(scan_method IN ('qr_code', 'manual', 'nfc')),
        logged_by INTEGER,
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    // 9. PAYMENTS - Transaction logging
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        payment_method TEXT,
        transaction_id TEXT,
        description TEXT,
        status TEXT DEFAULT 'completed',
        receipt_url TEXT,
        processed_by INTEGER,
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    // 10. BMI RECORDS - Track member BMI history
    $pdo->exec("CREATE TABLE IF NOT EXISTS bmi_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        weight_kg REAL NOT NULL,
        height_cm REAL NOT NULL,
        bmi REAL NOT NULL,
        bmi_category TEXT,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // INSERT DEFAULT EXERCISES (Gym equipment from equipment.php)
    $exercises = [
        // CABLE MACHINE
        ['Cable Chest Fly', 'Cable Machine', 'chest', 'beginner', 'Mid-height pulleys, cable fly motion', null, 3, 4, 10, 15, 60],
        ['Cable Crossover', 'Cable Machine', 'chest', 'intermediate', 'High pulleys, crossing motion', null, 3, 4, 10, 12, 60],
        ['Tricep Pushdown', 'Cable Machine', 'triceps', 'beginner', 'High pulley, push down with rope/bar', null, 3, 4, 10, 15, 45],
        ['Cable Bicep Curl', 'Cable Machine', 'biceps', 'beginner', 'Low pulley, curl motion', null, 3, 4, 10, 12, 45],
        ['Cable Lateral Raise', 'Cable Machine', 'shoulders', 'intermediate', 'Low pulley, lateral raise', null, 3, 4, 12, 15, 45],
        
        // SMITH MACHINE
        ['Smith Squat', 'Smith Machine', 'legs', 'beginner', 'Vertical bar path squat', null, 3, 5, 8, 12, 90],
        ['Smith Bench Press', 'Smith Machine', 'chest', 'beginner', 'Guided bench press', null, 3, 4, 8, 10, 90],
        ['Smith Shoulder Press', 'Smith Machine', 'shoulders', 'intermediate', 'Seated/standing shoulder press', null, 3, 4, 8, 10, 75],
        ['Smith Romanian Deadlift', 'Smith Machine', 'legs', 'intermediate', 'Hip hinge hamstring focus', null, 3, 4, 10, 12, 90],
        
        // LEG PRESS / HACK SQUAT
        ['Leg Press', 'Leg Press Machine', 'legs', 'beginner', '45-degree leg press', null, 3, 4, 10, 15, 90],
        ['Hack Squat', 'Hack Squat Machine', 'legs', 'intermediate', 'Angled squat machine', null, 3, 4, 8, 12, 90],
        
        // CHEST MACHINES
        ['Seated Chest Press', 'Chest Press Machine', 'chest', 'beginner', 'Seated horizontal press', null, 3, 4, 10, 12, 75],
        ['Decline Chest Press', 'Decline Press Machine', 'chest', 'intermediate', 'Lower chest emphasis', null, 3, 4, 10, 12, 75],
        ['Pec Deck Fly', 'Pec Deck Machine', 'chest', 'beginner', 'Chest fly motion', null, 3, 4, 12, 15, 60],
        
        // BACK MACHINES
        ['Lat Pulldown', 'Lat Pulldown Machine', 'back', 'beginner', 'Wide grip pulldown', null, 3, 4, 10, 12, 60],
        ['Seated Cable Row', 'Cable Row Machine', 'back', 'beginner', 'Horizontal rowing', null, 3, 4, 10, 12, 60],
        ['Pull-up', 'Pull-up Station', 'back', 'advanced', 'Bodyweight vertical pull', null, 3, 4, 5, 10, 90],
        
        // SHOULDER MACHINES
        ['Shoulder Press Machine', 'Shoulder Press Machine', 'shoulders', 'beginner', 'Guided overhead press', null, 3, 4, 10, 12, 75],
        ['Rear Delt Fly', 'Pec Deck Machine', 'shoulders', 'intermediate', 'Reverse pec deck', null, 3, 4, 12, 15, 60],
        
        // MULTI-PRESS
        ['Multi-Press Chest', 'Multi-Press Machine', 'chest', 'intermediate', 'Multi-angle press', null, 3, 4, 10, 12, 75],
        ['Multi-Press Shoulder', 'Multi-Press Machine', 'shoulders', 'intermediate', 'Multi-angle overhead', null, 3, 4, 10, 12, 75],
        
        // FREE WEIGHTS
        ['Dumbbell Bench Press', 'Dumbbells', 'chest', 'intermediate', 'DB flat bench press', null, 3, 4, 8, 12, 90],
        ['Dumbbell Shoulder Press', 'Dumbbells', 'shoulders', 'beginner', 'Seated/standing DB press', null, 3, 4, 10, 12, 75],
        ['Dumbbell Row', 'Dumbbells', 'back', 'beginner', 'Single-arm bent row', null, 3, 4, 10, 12, 60],
        ['Dumbbell Curl', 'Dumbbells', 'biceps', 'beginner', 'Standing DB curl', null, 3, 4, 10, 12, 45],
        ['Dumbbell Tricep Extension', 'Dumbbells', 'triceps', 'beginner', 'Overhead DB extension', null, 3, 4, 10, 12, 45],
        
        // CARDIO
        ['Treadmill Running', 'Treadmill', 'cardio', 'beginner', 'Cardiovascular exercise', null, 1, 1, 20, 30, 0],
        ['Stationary Bike', 'Exercise Bike', 'cardio', 'beginner', 'Low-impact cardio', null, 1, 1, 20, 30, 0],
        ['Elliptical', 'Elliptical', 'cardio', 'beginner', 'Full-body cardio', null, 1, 1, 20, 30, 0],
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO exercises (name, equipment, muscle_group, difficulty, description, video_url, sets_min, sets_max, reps_min, reps_max, rest_seconds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($exercises as $exercise) {
        $stmt->execute($exercise);
    }

    // INSERT DEFAULT WORKOUT RULES
    $rules = [
        // BEGINNER RULES
        ['Beginner Weight Loss', 'beginner', 'weight_loss', 3, 6, 0.8, 0.8, 1, 1],
        ['Beginner Muscle Gain', 'beginner', 'muscle_gain', 3, 6, 1.0, 1.0, 2, 1],
        ['Beginner General Fitness', 'beginner', 'general_fitness', 3, 6, 1.0, 1.0, 3, 1],
        
        // INTERMEDIATE RULES
        ['Intermediate Weight Loss', 'intermediate', 'weight_loss', 4, 8, 0.9, 0.7, 4, 1],
        ['Intermediate Muscle Gain', 'intermediate', 'muscle_gain', 4, 8, 1.2, 1.2, 5, 1],
        ['Intermediate Strength', 'intermediate', 'strength', 4, 6, 1.3, 1.5, 6, 1],
        ['Intermediate General Fitness', 'intermediate', 'general_fitness', 4, 7, 1.0, 1.0, 7, 1],
        
        // ADVANCED RULES
        ['Advanced Muscle Gain', 'advanced', 'muscle_gain', 5, 10, 1.5, 1.3, 8, 1],
        ['Advanced Strength', 'advanced', 'strength', 5, 8, 1.5, 1.8, 9, 1],
        ['Advanced Endurance', 'advanced', 'endurance', 5, 10, 0.8, 0.5, 10, 1],
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO workout_rules (rule_name, experience_level, goal, workout_days_per_week, exercises_per_session, sets_multiplier, rest_time_multiplier, priority_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($rules as $rule) {
        $stmt->execute($rule);
    }

    echo "✅ Database initialization complete!\n\n";
    echo "Created tables:\n";
    echo "- exercises (30 default exercises added)\n";
    echo "- workout_rules (10 default rules added)\n";
    echo "- member_profiles\n";
    echo "- generated_plans\n";
    echo "- workout_logs\n";
    echo "- api_keys (for BYOK Google AI Studio)\n";
    echo "- leaderboard\n";
    echo "- attendance\n";
    echo "- payments\n";
    echo "- bmi_records\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
    exit();
}

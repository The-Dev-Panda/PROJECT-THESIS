# 🏋️ Fit-Stop Workout Generator System

## 📋 Overview

This is the **CORE THESIS FEATURE** - A rule-based intelligent workout generator with optional AI enhancement (BYOK - Bring Your Own Key).

### System Architecture

```
┌─────────────────────────────────────────────────────┐
│         RULE-BASED WORKOUT ENGINE (Primary)          │
│  - Configurable rules (admin-managed)               │
│  - Exercise database (gym equipment)                │
│  - Member profiling (experience, goals, health)     │
│  - Personalized plan generation                     │
└─────────────────────────────────────────────────────┘
                         ▼
                    ┌────────┐
                    │ Member │
                    └────────┘
                         ▼
┌─────────────────────────────────────────────────────┐
│     GOOGLE AI STUDIO INTEGRATION (Optional)         │
│  - BYOK (User provides own API key)                │
│  - Enhances plans with form tips                    │
│  - Nutrition guidance                               │
│  - AI chat assistant                                │
└─────────────────────────────────────────────────────┘
```

---

## 🚀 Installation & Setup

### Step 1: Initialize Database

Run this **ONCE** to create all necessary tables:

```bash
# Navigate to Workout folder
cd Workout

# Run PHP initialization script
php init_workout_system.php
```

**This creates:**
- ✅ 10 database tables
- ✅ 30+ default exercises (gym equipment)
- ✅ 10 workout rules (beginner/intermediate/advanced)

**Output should show:**
```
✅ Database initialization complete!

Created tables:
- exercises (30 default exercises added)
- workout_rules (10 default rules added)
- member_profiles
- generated_plans
- workout_logs
- api_keys (for BYOK Google AI Studio)
- leaderboard
- attendance
- payments
- bmi_records
```

### Step 2: Test the System

1. **Login as a member** (user_type = 'user')
2. Go to **My Plan** page (`Workout/myplan.php`)
3. Generate a workout plan!

---

## 🎯 How It Works

### Rule-Based Generation (Core Feature)

The system generates personalized workout plans using these inputs:

1. **Member Profile:**
   - Experience level (beginner/intermediate/advanced)
   - Fitness goal (weight_loss, muscle_gain, strength, endurance, general_fitness)
   - Workout days per week (3-6 days)
   - Health data (BMI, age, weight)

2. **Rule Selection:**
   - System finds matching rule from `workout_rules` table
   - Rules configure: exercises_per_session, sets_multiplier, rest_time_multiplier

3. **Exercise Selection:**
   - Queries `exercises` table filtered by:
     - Difficulty matching experience level
     - Muscle groups matching fitness goal
     - Available gym equipment

4. **Plan Assembly:**
   - Distributes exercises across workout days
   - Applies rule multipliers to sets/reps/rest
   - Generates weekly split (e.g., Push/Pull/Legs)

5. **Plan Storage:**
   - Saves to `generated_plans` table as JSON
   - User can view anytime in "My Plan" page

### Example Rule Logic

```php
// For Beginner + Weight Loss:
Rule: 
  - 3 days/week
  - 6 exercises per session
  - 0.8x sets multiplier (lighter volume)
  - 0.8x rest multiplier (higher tempo)
  
Muscle Distribution:
  - 2 cardio exercises
  - 2 leg exercises
  - 1 chest, 1 back
  
Result: High-rep, circuit-style workouts
```

---

## 🤖 AI Enhancement (Optional BYOK)

### What is BYOK?

**Bring Your Own Key** - Users provide their own Google AI Studio API key. Your system never pays for AI usage.

### How to Get a FREE API Key

1. Go to https://aistudio.google.com/
2. Sign in with Google account
3. Click **"Get API Key"** → **"Create API Key"**
4. Copy the key (starts with `AIzaSy...`)
5. Paste in **Settings** page

### What AI Provides

When a user has an API key, they get:

✅ **Enhanced Workout Plans:**
- Form tips for exercises
- Motivation based on goals
- Nutrition suggestions

✅ **AI Chat Assistant:**
- Ask questions about exercises
- Get real-time fitness advice
- Nutrition guidance

✅ **Personalized Recommendations:**
- Meal planning
- Recovery tips
- Progress feedback

### AI vs Rule-Based

| Feature | Rule-Based (Default) | AI-Assisted (Optional) |
|---------|---------------------|------------------------|
| Workout Generation | ✅ Yes | ✅ Yes |
| Exercise Selection | ✅ Yes | ✅ Yes |
| Form Tips | ❌ No | ✅ Yes |
| Nutrition Advice | ✅ Basic | ✅ Advanced |
| Chat Interface | ❌ No | ✅ Yes |
| Cost | FREE | FREE (user's key) |

**Important:** The system works 100% without AI. AI is purely an enhancement.

---

## 📂 File Structure

```
Workout/                           # All-in-one folder (temporary)
├── init_workout_system.php       # Database setup (run once)
├── WorkoutEngine.php              # Core rule-based engine
├── GoogleAIAssistant.php          # AI integration class
├── workout_api.php                # API endpoints
├── myplan.php                     # Main workout plan interface
├── settings.php                   # API key management
└── WORKOUT_SYSTEM_README.md       # Documentation

admin/
└── [admin_workout_config.php]     # TODO: Rule editor
```

---

## 🔧 API Endpoints

**Base URL:** `Workout/workout_api.php`

### Generate Plan
```
POST ?action=generate_plan
Body:
  - experience_level: beginner|intermediate|advanced
  - fitness_goal: weight_loss|muscle_gain|strength|endurance|general_fitness
  - workout_days: 3-6
  - use_ai: true|false (optional, requires API key)

Response:
{
  "success": true,
  "plan_id": 123,
  "plan": {...},
  "rule_applied": "Beginner Weight Loss",
  "ai_recommendations": "..." (if AI used)
}
```

### Get Active Plan
```
GET ?action=get_active_plan

Response:
{
  "success": true,
  "plan": {
    "plan_name": "Beginner General Fitness Plan",
    "plan_data": [...],
    "generation_method": "rule_based",
    ...
  }
}
```

### Log Workout
```
POST ?action=log_workout
Body:
  - plan_id: integer
  - exercise_name: string
  - sets: integer
  - reps: integer
  - weight: float
  - difficulty: 1-5
  - notes: string

Response:
{
  "success": true,
  "log_id": 456
}
```

### AI Chat
```
POST ?action=ai_question
Body:
  - question: string

Response:
{
  "success": true,
  "response": "..." (AI answer)
}
```

### Save API Key
```
POST ?action=save_api_key
Body:
  - api_key: string (starts with AIza)

Response:
{
  "success": true,
  "message": "API key saved"
}
```

---

## 🗄️ Database Schema

### exercises
Stores all gym equipment and exercises.

```sql
id, name, equipment, muscle_group, difficulty,
description, sets_min, sets_max, reps_min, reps_max,
rest_seconds, is_active
```

### workout_rules
Admin-configurable rules for plan generation.

```sql
id, rule_name, experience_level, goal,
workout_days_per_week, exercises_per_session,
sets_multiplier, rest_time_multiplier, is_active
```

### member_profiles
Member fitness data and preferences.

```sql
id, user_id, experience_level, fitness_goal,
current_weight, target_weight, height_cm, age, bmi,
workout_days_per_week, health_conditions
```

### generated_plans
System-generated workout plans.

```sql
id, user_id, plan_name, generation_method,
plan_data (JSON), ai_recommendations,
is_active, generated_at
```

### workout_logs
Member exercise tracking.

```sql
id, user_id, plan_id, exercise_name,
sets_completed, reps_completed, weight_used,
difficulty_rating, logged_at
```

### api_keys
BYOK API keys for Google AI Studio.

```sql
id, user_id, service_name, api_key,
is_active, usage_count, last_used
```

---

## 📊 Admin Features (TODO)

Create `admin/workout_config.php` to manage:

- ✅ View all exercises
- ✅ Add/edit/delete exercises
- ✅ Configure workout rules
- ✅ Adjust sets/reps/rest multipliers
- ✅ View member workout logs
- ✅ Override member plans

---

## 🔒 Security Notes

1. **API Keys are User-Owned:**
   - Each user provides their own key
   - Keys are stored per-user in database
   - Never shared or exposed

2. **Session-Based Authentication:**
   - All API endpoints check `$_SESSION['id']`
   - Users can only access their own plans

3. **No AI Key Required:**
   - System works 100% without AI
   - AI is purely optional enhancement

---

## 🎓 Thesis Alignment

### Scope Document Requirements

✅ **Rule-Based Intelligent Guidance Features:**
- [x] Personalized workout plan generation
- [x] Based on equipment, experience, health data
- [x] Simple nutrition guidance (rule-based + AI)
- [x] Trainer override capability (database field ready)
- [x] Performance tracking for progression

✅ **Structured, Rule-Based Logic:**
- [x] NOT machine learning
- [x] Admin-configurable rules
- [x] Transparent decision-making

✅ **Optional AI Enhancement:**
- [x] BYOK implementation
- [x] Enhances but doesn't replace rule-based core
- [x] Falls back to rules if no API key

---

## 🐛 Troubleshooting

### "No active plan found"
- User needs to generate a plan first
- Check if `generated_plans` table has data

### "Invalid API key"
- Ensure key starts with `AIzaSy`
- Test key at https://aistudio.google.com/

### "Database error"
- Run `init_workout_system.php` again
- Check `DB.sqlite` file permissions

### AI not responding
- Check API key is active in Settings
- Verify internet connection
- Check Google AI Studio quota

---

## 📈 Future Enhancements

- [ ] Progressive overload tracking
- [ ] Exercise video integration
- [ ] Social features (share plans)
- [ ] Admin rule configuration UI
- [ ] Mobile PWA version

---

## 📞 Support

For questions about the workout system:
- Check code comments in `WorkoutEngine.php`
- Review `init_workout_system.php` for database structure
- Test API endpoints using Postman

---

**Built for Fit-Stop Gym Management System**  
Core Thesis Feature - Rule-Based Workout Generator  
Optional Enhancement - Google AI Studio Integration (BYOK)

# Workout Folder - Temporary Consolidated Location

## 📁 About This Folder

This `Workout/` folder contains **all workout system files** in one location for ease of development and testing.

### Why Consolidated?

> "Not all machines are there yet" - Since the gym equipment inventory is still being set up, all workout-related files are kept here temporarily for easier management.

---

## 📦 What's Inside

```
Workout/
├── init_workout_system.php       Database initialization script
├── WorkoutEngine.php              Core rule-based workout generator
├── GoogleAIAssistant.php          Optional AI enhancement (BYOK)
├── workout_api.php                RESTful API endpoints
├── myplan.php                     Member workout plan interface
├── settings.php                   API key management page
├── WORKOUT_SYSTEM_README.md       Full technical documentation
├── QUICK_START.md                 5-minute setup guide
└── README_FOLDER_STRUCTURE.md     This file
```

---

## 🚀 Quick Start

```bash
# 1. Initialize database (run once)
cd Workout
php init_workout_system.php

# 2. Login as member and visit:
# http://yoursite/Workout/myplan.php
```

---

## 🔄 Future Reorganization

Once all gym equipment/machines are finalized, files can be moved to their logical locations:

**Suggested Final Structure:**
```
Database/
├── init_workout_system.php
├── WorkoutEngine.php
├── GoogleAIAssistant.php
└── workout_api.php

user/
├── myplan.php
└── settings.php
```

**Migration Steps:**
1. Update `require_once()` paths in PHP files
2. Update navigation links in header/menu
3. Update documentation file paths
4. Test all functionality after move

---

## ⚠️ Important Notes

### Current File Dependencies:

- **myplan.php** requires:
  - `../Login/connection.php`
  - `WorkoutEngine.php` (same folder)
  - `GoogleAIAssistant.php` (same folder)

- **settings.php** requires:
  - `../Login/connection.php`
  - `GoogleAIAssistant.php` (same folder)

- **workout_api.php** requires:
  - `../Login/connection.php`
  - `WorkoutEngine.php` (same folder)
  - `GoogleAIAssistant.php` (same folder)

- **init_workout_system.php** requires:
  - `../Login/connection.php`

### Database Location:

All files connect to: `../Login/DB.sqlite` (unchanged)

---

## 🎯 System Status

✅ **Fully Functional** - All files work in current location  
✅ **Session-based Auth** - User login required  
✅ **Rule-based Engine** - Works without AI  
✅ **Optional AI** - BYOK Google AI Studio  

---

## 📝 Development Notes

**Current Phase:** Prototype/Development  
**Equipment Status:** In progress (not all machines added yet)  
**Testing Status:** Ready for testing  
**Documentation:** Complete (see WORKOUT_SYSTEM_README.md)

---

## 🆘 Support

**Database not initializing?**
- Check connection.php path is correct
- Ensure DB.sqlite has write permissions

**Pages not loading?**
- Verify session is active (user logged in)
- Check user_type = 'user' in session

**API not responding?**
- Ensure jQuery/fetch is working
- Check browser console for errors

**Need more help?**
- See: `WORKOUT_SYSTEM_README.md` (full documentation)
- See: `QUICK_START.md` (setup guide)

---

**Created:** March 5, 2026  
**Purpose:** Temporary consolidated development folder  
**Status:** Active development

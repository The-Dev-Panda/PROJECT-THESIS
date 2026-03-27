# Gym Management System — Database Schema Reference

> **Purpose:** AI-assisted programming context document. Use this to understand the full database structure, relationships, constraints, and business logic of the gym management system.

---

## Overview

- **Database:** SQLite
- **Foreign Keys:** Enabled (`PRAGMA foreign_keys=ON`)
- **Application Type:** Gym management system with members, staff, admin roles, inventory, transactions, workout tracking, and attendance.

---

## Tables

### `users`
Core authentication and profile table for all system actors (admin, staff, members).

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | INTEGER | PK, AUTOINCREMENT | |
| `username` | VARCHAR(50) | NOT NULL, UNIQUE | |
| `first_name` | VARCHAR(50) | NOT NULL | |
| `last_name` | VARCHAR(50) | NOT NULL | |
| `email` | VARCHAR(100) | NOT NULL, UNIQUE | |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt hashed (`$2y$10$...`) |
| `user_type` | VARCHAR(50) | | `'admin'`, `'staff'`, `'user'` |
| `verification_code` | VARCHAR(50) | DEFAULT NULL | Email verification token |
| `verification_code_expiration` | TIMESTAMP | DEFAULT NULL | |
| `is_verified` | BOOLEAN | DEFAULT FALSE | `0` = unverified, `1` = verified |
| `profile_picture` | BLOB | | |
| `last_logged_in` | TIMESTAMP | DEFAULT NULL | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | |
| `points` | INTEGER | NOT NULL, DEFAULT 0 | Loyalty/reward points |
| `dpa_consent` | INTEGER | NOT NULL, DEFAULT 0 | Data Privacy Act consent flag |
| `dpa_consent_at` | TIMESTAMP | DEFAULT NULL | When consent was given |
| `address` | VARCHAR(255) | | |

**Indexes:**
- `idx_users_type_points` on `(user_type, points)`

**Known Users (Seed Data):**
| id | username | user_type | is_verified |
|---|---|---|---|
| 1 | alexthompson | admin | ✅ |
| 2 | mariarodriguez | staff | ✅ |
| 5 | michaelchen | user | ❌ |
| 6 | emilypark | user | ❌ |
| 7 | lancechua | user | ✅ |
| 10 | kevin123 | staff | ✅ |

---

### `member_profiles`
Extended profile data for members (users with `user_type = 'user'`).

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | INTEGER | PK, AUTOINCREMENT | |
| `user_id` | INTEGER | NOT NULL, UNIQUE, FK → `users(id)` ON DELETE CASCADE | One-to-one with users |
| `age` | INTEGER | | |
| `height_cm` | REAL | | |
| `weight_kg` | REAL | | |
| `fitness_level` | TEXT | | `'Beginner'`, `'Intermediate'`, `'Advanced'` |
| `goal` | TEXT | | e.g. `'Muscle Gain'`, `'Weight Loss'`, `'Endurance'`, `'General Fitness'` |
| `contact` | TEXT | | Phone number |
| `gender` | TEXT | | |
| `remarks` | TEXT | | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | |

**Trigger:** `trg_member_profiles_archive_before_update` — Before any UPDATE, the old row is automatically inserted into `old_member_profiles`.

---

### `old_member_profiles`
Audit/history table. Auto-populated by trigger on `member_profiles` updates.

| Column | Type | Notes |
|---|---|---|
| `history_id` | INTEGER PK | |
| `profile_id` | INTEGER | References original `member_profiles.id` |
| `user_id` | INTEGER FK → `users(id)` | |
| `age`, `height_cm`, `weight_kg` | | Snapshot values |
| `fitness_level`, `goal`, `contact`, `gender`, `remarks` | | Snapshot values |
| `created_at`, `updated_at` | TIMESTAMP | From original row |
| `archived_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `changed_by_user_id` | INTEGER FK → `users(id)` | NULL if trigger-driven |
| `change_source` | TEXT | DEFAULT `'profile_update'`; trigger inserts `'before_update'` |
| `change_note` | TEXT | |

**Indexes:**
- `idx_old_member_profiles_user_id_archived_at` on `(user_id, archived_at DESC)`
- `idx_old_member_profiles_profile_id` on `(profile_id)`

---

### `attendance`
Tracks gym check-ins per user per day. Enforces one check-in per user per calendar day.

| Column | Type | Constraints |
|---|---|---|
| `id` | INTEGER | PK |
| `user_id` | INTEGER | NOT NULL |
| `datetime` | DATETIME | NOT NULL |

**Indexes:**
- `idx_attendance_user_day` — UNIQUE on `(user_id, date(datetime))` → **prevents duplicate check-ins on same day**
- `idx_attendance_user_datetime` on `(user_id, datetime)`

---

### `inventory`
Gym store products.

| Column | Type | Notes |
|---|---|---|
| `id` | INTEGER PK AUTOINCREMENT | |
| `item_name` | VARCHAR(100) NOT NULL | |
| `category` | VARCHAR(50) NOT NULL | `'Beverage'`, `'Supplements'`, `'Snacks'` |
| `quantity` | INTEGER | DEFAULT 0 |
| `price` | DECIMAL(10,2) | DEFAULT 0.00 |
| `description` | TEXT | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Current Stock:**
| id | item_name | category | qty | price (₱) |
|---|---|---|---|---|
| 2 | Sting | Beverage | 34 | 20 |
| 3 | Amino | Supplements | 5 | 10 |
| 4 | Pre-Workout | Supplements | 15 | 35 |
| 5 | Gatorade | Beverage | 23 | 25 |
| 6 | Creatine | Supplements | 19 | 20 |
| 7 | Whey | Supplements | 30 | 75 |
| 8 | Protein Bar | Snacks | 7 | 120 |

---

### `transactions`
All financial transactions: member payments, inventory sales, entry fees.

| Column | Type | Notes |
|---|---|---|
| `id` | INTEGER PK AUTOINCREMENT | |
| `receipt_number` | TEXT UNIQUE NOT NULL | Format: `RCP{date}-{rand}`, `INV-{date}-{item_id}-{rand}`, `SALE-...`, `ENTRY-...` |
| `customer_type` | TEXT NOT NULL | `'member'`, `'non-member'`, `'inventory'`, `'entry'` |
| `user_id` | INTEGER | FK → `users(id)`, NULL for walk-ins |
| `customer_name` | TEXT | |
| `amount` | DECIMAL(10,2) NOT NULL | In Philippine Peso (₱) |
| `payment_method` | TEXT NOT NULL | `'Cash'`, `'GCash'`, `'Inventory Deduction'` |
| `staff_id` | INTEGER | FK → `users(id)`, staff who processed |
| `transaction_date` | TIMESTAMP | |
| `status` | TEXT | DEFAULT `'Confirmed'`; inventory uses `'completed'` |
| `created_at` | TIMESTAMP | |
| `desc` | TEXT | Human-readable description of what was paid for |

**Receipt Number Patterns:**
- Member payment: `RCP{YYYYMMDD}-{5-digit rand}`
- Inventory deduction: `INV-{YYYYMMDD}-{item_id}-{3-digit rand}`
- Walk-in sale: `SALE-{YYYYMMDD}-{item_id}-{3-digit rand}`
- Entry fees batch: `ENTRY-{YYYYMMDD}-{rand}`

---

### `membership_price`
Configurable pricing table for membership and entry options.

| Column | Type | Notes |
|---|---|---|
| `m_price_id` | INTEGER PK AUTOINCREMENT | |
| `price` | REAL NOT NULL | In ₱ |
| `promo_type` | TEXT | Label for the pricing tier |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

**Current Pricing:**
| promo_type | price (₱) |
|---|---|
| annual | 500 |
| MONTHLY (Member) | 650 |
| MONTHLY (Non-Member) | 750 |
| WALK-IN (Non-Member) | 60 |
| WALK-IN (MEMBER) | 50 |

---

### `monthly`
Active monthly memberships.

| Column | Type | Notes |
|---|---|---|
| `id` | INTEGER PK | |
| `name` | TEXT | Member's display name |
| `expires_in` | DATETIME | Expiry date |
| `member` | INTEGER | FK → `users(id)`, NULL for non-members |
| `image` | BLOB | |

---

### `exercises`
Exercise library used for workout logging.

| Column | Type | Notes |
|---|---|---|
| `exercise_id` | INTEGER PK AUTOINCREMENT | |
| `name` | TEXT NOT NULL | |
| `target_muscle` | TEXT | e.g. `'Chest'`, `'Lats'`, `'Quads'`, `'Cardio'` |
| `movement_type` | TEXT NOT NULL | `'Push'`, `'Pull'`, `'Legs'`, `'Core'`, `'Cardio'` |

**~97 exercises** covering Cable, Dumbbell, Smith Machine, and bodyweight movements.

---

### `workout_logs`
Individual set/rep logs per user per exercise.

| Column | Type | Notes |
|---|---|---|
| `log_id` | INTEGER PK AUTOINCREMENT | |
| `user_id` | INTEGER NOT NULL | FK → `users(id)` ON DELETE CASCADE |
| `exercise_id` | INTEGER NOT NULL | FK → `exercises(exercise_id)` ON DELETE CASCADE |
| `weight` | REAL NOT NULL | In kg |
| `reps` | INTEGER NOT NULL | |
| `logged_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

---

### `meal_logs`
Nutritional tracking per user per meal.

| Column | Type | Notes |
|---|---|---|
| `meal_id` | INTEGER PK AUTOINCREMENT | |
| `user_id` | INTEGER NOT NULL | FK → `users(id)` ON DELETE CASCADE |
| `logged_date` | TEXT NOT NULL | Format: `YYYY-MM-DD` |
| `meal_type` | TEXT NOT NULL | e.g. `'Breakfast'`, `'Lunch'`, `'Dinner'`, `'Snack'` |
| `food_name` | TEXT NOT NULL | |
| `quantity` | REAL NOT NULL | |
| `calories` | INTEGER NOT NULL | |
| `protein` | REAL NOT NULL | grams |
| `carbs` | REAL NOT NULL | grams |
| `fat` | REAL NOT NULL | grams |
| `created_at` | TIMESTAMP | |

**Index:** `idx_meal_logs_user_date` on `(user_id, logged_date)`

---

### `announcements`
Admin-published gym announcements.

| Column | Type | Notes |
|---|---|---|
| `id` | INTEGER PK AUTOINCREMENT | |
| `title` | VARCHAR(255) NOT NULL | |
| `description` | TEXT NOT NULL | |
| `image` | BLOB | Optional image attachment |
| `created_by` | VARCHAR(50) | Username of creator |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

---

### `feedback`
Member/visitor feedback and issue reports.

| Column | Type | Notes |
|---|---|---|
| `id` | INTEGER PK AUTOINCREMENT | |
| `about` | VARCHAR | Subject/equipment name |
| `reporterID` | INT | FK → `users(id)`, NULL for anonymous |
| `last_name` | VARCHAR | Reporter's last name |
| `created_at` | DATETIME | |
| `desc` | VARCHAR NOT NULL | Feedback body |
| `resolved_at` | DATETIME | NULL if unresolved |
| `status` | VARCHAR NOT NULL | `'pending'`, `'closed'` |

---

### `notification_history`
Admin notification log (system events).

| Column | Type | Notes |
|---|---|---|
| `notif_id` | INTEGER PK AUTOINCREMENT | |
| `name` | VARCHAR(255) NOT NULL | Event name e.g. `'New Member'`, `'ITEM ADDED'` |
| `description` | TEXT | Detail string |
| `datetime` | TIMESTAMP | |
| `remarks` | TEXT | e.g. `'BY ADMIN'`, `'Created via API'` |
| `is_read` | BOOLEAN | DEFAULT 0 |
| `category` | VARCHAR(100) | `'Inventory'`, `'Accounts'`, `'Announcements'`, `'membership'`, `'Pricing'` |

---

### `inventory_notifications`
Low-stock or sold-item alerts for inventory.

| Column | Type | Notes |
|---|---|---|
| `id` | INTEGER PK AUTOINCREMENT | |
| `user_id` | INTEGER NOT NULL | `0` = system-generated |
| `item_id` | INTEGER NOT NULL | FK → `inventory(id)` |
| `item_name` | VARCHAR(100) NOT NULL | |
| `qty_sold` | INTEGER NOT NULL | |
| `notif_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

---

### `expense_history`
Operational expense tracking.

| Column | Type | Notes |
|---|---|---|
| `expense_id` | INTEGER PK AUTOINCREMENT | |
| `expense_name` | TEXT NOT NULL | |
| `expense` | REAL NOT NULL | Amount in ₱ |
| `description` | TEXT | |
| `author` | TEXT | Who recorded it |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

---

### `api_table`
External API integrations (keys stored in DB).

| Column | Type | Notes |
|---|---|---|
| `api_id` | INTEGER PK AUTOINCREMENT | |
| `api_name` | TEXT NOT NULL | |
| `api_url` | TEXT NOT NULL | |
| `api_key` | TEXT NOT NULL UNIQUE | |
| `status` | TEXT | DEFAULT `'active'` |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

**Known Integration:** GROQ API (`https://api.groq.com/openai/v1/chat/completions`) — used for AI features.

---

## Relationships Summary

```
users (1) ──────────────── (1) member_profiles
users (1) ──────────────── (N) old_member_profiles  [audit trail]
users (1) ──────────────── (N) attendance
users (1) ──────────────── (N) workout_logs
users (1) ──────────────── (N) meal_logs
users (1) ──────────────── (N) transactions          [as customer]
users (1) ──────────────── (N) transactions          [as staff_id]
users (1) ──────────────── (N) feedback              [as reporterID]
users (1) ──────────────── (N) monthly               [as member]
exercises (1) ──────────── (N) workout_logs
inventory (1) ──────────── (N) inventory_notifications
```

---

## Business Logic Notes

1. **One attendance per day** — enforced by unique index `idx_attendance_user_day` on `(user_id, date(datetime))`.
2. **Member profile history** — every update to `member_profiles` is auto-archived to `old_member_profiles` via trigger before the update is applied.
3. **Inventory sales create two records** — a member receipt (`RCP...`) and a corresponding inventory deduction record (`INV...`) in `transactions`.
4. **User roles:** `admin` > `staff` > `user`. Admins manage everything; staff handle transactions and attendance; users are gym members.
5. **Points system** — `users.points` is incremented (business logic in app layer, not enforced by DB constraints).
6. **DPA consent** — `dpa_consent` (0/1) and `dpa_consent_at` track PDPA/DPA compliance for Philippine data privacy law.
7. **Non-member transactions** — `user_id` is NULL; `customer_name` stores a freetext name instead.
8. **Currency:** Philippine Peso (₱). All prices and amounts are in ₱.
9. **Unverified users** — `is_verified = 0` users may have limited access; verification via `verification_code` token.
10. **Membership pricing is dynamic** — stored in `membership_price` table, not hardcoded. Admin can add/remove tiers.

---

## Raw SQL Schema (SQLite Dump)

```sql
PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "users" (   id INTEGER PRIMARY KEY AUTOINCREMENT,   username VARCHAR(50) NOT NULL UNIQUE,   first_name VARCHAR(50) NOT NULL,   last_name VARCHAR(50) NOT NULL,
email VARCHAR(100) NOT NULL UNIQUE,   password VARCHAR(255) NOT NULL,   user_type VARCHAR(50),   verification_code VARCHAR(50) DEFAULT NULL,   verification_code_expiration TIMESTAMP DEFAULT NULL,   is_verified BOOLEAN DEFAULT FALSE,   profile_picture BLOB,   last_logged_in TIMESTAMP DEFAULT NULL,   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP , "points" INTEGER NOT NULL DEFAULT 0, dpa_consent INTEGER NOT NULL DEFAULT 0, dpa_consent_at TIMESTAMP DEFAULT NULL, "address" VARCHAR(255));
CREATE TABLE inventory (   id INTEGER PRIMARY KEY AUTOINCREMENT,   item_name VARCHAR(100) NOT NULL,   category VARCHAR(50) NOT NULL,   quantity INTEGER NOT NULL DEFAULT 0,   price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,   description TEXT,   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE announcements (   id INTEGER PRIMARY KEY AUTOINCREMENT,   title VARCHAR(255) NOT NULL,   description TEXT NOT NULL,   image BLOB,   created_by VARCHAR(50),   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE IF NOT EXISTS "attendance" ("id" integer NOT NULL,"user_id" integer NOT NULL,"datetime" datetime NOT NULL, PRIMARY KEY ("id"));
CREATE TABLE feedback(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    about VARCHAR,
    reporterID INT,
    last_name VARCHAR,
    created_at DATETIME,
    desc VARCHAR NOT NULL,
    resolved_at DATETIME,
    status VARCHAR NOT NULL,
    FOREIGN KEY (reporterID) REFERENCES users(id)
);
CREATE TABLE exercises (
    exercise_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    target_muscle TEXT,
    "movement_type" text NOT NULL
);
CREATE TABLE workout_logs (
    log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    exercise_id INTEGER NOT NULL,
    weight REAL NOT NULL,
    reps INTEGER NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE CASCADE
);
CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_number TEXT UNIQUE NOT NULL,
    customer_type TEXT NOT NULL,
    user_id INTEGER,
    customer_name TEXT,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method TEXT NOT NULL,
    staff_id INTEGER,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "desc" TEXT
);
CREATE TABLE member_profiles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  age INTEGER,
  height_cm REAL,
  weight_kg REAL,
  fitness_level TEXT,
  goal TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  "contact" text,
  "gender" text,
  "remarks" TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE inventory_notifications (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER NOT NULL,
  item_id     INTEGER NOT NULL,
  item_name   VARCHAR(100) NOT NULL,
  qty_sold    INTEGER NOT NULL,
  notif_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE notification_history (   notif_id INTEGER PRIMARY KEY AUTOINCREMENT,   name VARCHAR(255) NOT NULL,   description TEXT,   datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   remarks TEXT,   is_read BOOLEAN DEFAULT 0,   category VARCHAR(100) );
CREATE TABLE meal_logs (
      meal_id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      logged_date TEXT NOT NULL,
      meal_type TEXT NOT NULL,
      food_name TEXT NOT NULL,
      quantity REAL NOT NULL,
      calories INTEGER NOT NULL,
      protein REAL NOT NULL,
      carbs REAL NOT NULL,
      fat REAL NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
CREATE TABLE membership_price (     m_price_id INTEGER PRIMARY KEY AUTOINCREMENT,     price REAL NOT NULL,       promo_type TEXT,     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,     updated_at DATETIME DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE old_member_profiles (
  history_id INTEGER PRIMARY KEY AUTOINCREMENT,
  profile_id INTEGER,
  user_id INTEGER NOT NULL,
  age INTEGER,
  height_cm REAL,
  weight_kg REAL,
  fitness_level TEXT,
  goal TEXT,
  contact TEXT,
  gender TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  changed_by_user_id INTEGER,
  change_source TEXT NOT NULL DEFAULT 'profile_update',
  change_note TEXT,
  "remarks" TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS "monthly" ("id" integer,"name" text,"expires_in" datetime, "member" INTEGER, image BLOB, PRIMARY KEY ("id"));
CREATE TABLE expense_history (     expense_id INTEGER PRIMARY KEY AUTOINCREMENT,     expense_name TEXT NOT NULL,     expense REAL NOT NULL,     description TEXT,     author TEXT,     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,     updated_at DATETIME DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE api_table (     api_id INTEGER PRIMARY KEY AUTOINCREMENT,     api_name TEXT NOT NULL,     api_url TEXT NOT NULL,     api_key TEXT NOT NULL UNIQUE,     status TEXT DEFAULT 'active',     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,     updated_at DATETIME DEFAULT CURRENT_TIMESTAMP );
CREATE TRIGGER trg_member_profiles_archive_before_update
BEFORE UPDATE ON member_profiles
FOR EACH ROW
BEGIN
  INSERT INTO old_member_profiles (
    profile_id,
    user_id,
    age,
    height_cm,
    weight_kg,
    fitness_level,
    goal,
    contact,
    gender,
    created_at,
    updated_at,
    changed_by_user_id,
    change_source
  )
  VALUES (
    OLD.id,
    OLD.user_id,
    OLD.age,
    OLD.height_cm,
    OLD.weight_kg,
    OLD.fitness_level,
    OLD.goal,
    OLD.contact,
    OLD.gender,
    OLD.created_at,
    OLD.updated_at,
    NULL,
    'before_update'
  );
END;
CREATE UNIQUE INDEX idx_attendance_user_day
ON attendance(user_id, date(datetime));
CREATE INDEX idx_attendance_user_datetime
ON attendance(user_id, datetime);
CREATE INDEX idx_users_type_points
ON users(user_type, points);
CREATE INDEX idx_meal_logs_user_date ON meal_logs(user_id, logged_date);
CREATE INDEX idx_old_member_profiles_user_id_archived_at
ON old_member_profiles(user_id, archived_at DESC);
CREATE INDEX idx_old_member_profiles_profile_id
ON old_member_profiles(profile_id);
COMMIT;
```

CREATE TABLE "walk_attendance" ("id" integer,"name" varchar(255),"month_id" integer, PRIMARY KEY ("id"))
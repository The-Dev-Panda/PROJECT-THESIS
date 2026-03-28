# MySQL Schema Reference
> Converted from SQLite on 2026-03-28. Use this document to update all database queries and ORM code to work with the new MySQL schema.

---

## Key Differences from SQLite

| SQLite | MySQL |
|--------|-------|
| `AUTOINCREMENT` | `AUTO_INCREMENT` |
| `BOOLEAN` | `TINYINT(1)` (0 = false, 1 = true) |
| `BLOB` | `LONGBLOB` |
| `REAL` | `DOUBLE` |
| `INTEGER PRIMARY KEY` (rowid alias) | `INT PRIMARY KEY AUTO_INCREMENT` |
| `"double_quoted"` identifiers | `` `backtick` `` identifiers |
| `PRAGMA foreign_keys=ON` | `SET FOREIGN_KEY_CHECKS=1` |
| `DATE('now')` | `CURDATE()` |
| `CURRENT_TIMESTAMP` | `CURRENT_TIMESTAMP` ✅ same |
| `unistr(...)` for blobs | `NULL` (binary data must be handled in app layer) |

### Reserved Word: `desc`
The `transactions` table has a column called `desc`, which is a **reserved word in MySQL**. Always quote it:
```sql
-- Wrong
SELECT desc FROM transactions;
-- Correct
SELECT `desc` FROM transactions;
```

### Trigger Delimiter
MySQL requires `DELIMITER` changes for triggers/stored procedures. The dump handles this — but if you recreate the trigger programmatically, use:
```sql
DELIMITER //
CREATE TRIGGER ... BEGIN ... END//
DELIMITER ;
```

### Functional Unique Index (MySQL 8.0+ only)
The original SQLite had a unique index on `attendance(user_id, date(datetime))`. This has been converted to a regular (non-unique) composite index for MySQL 5.7 compatibility. If you're on **MySQL 8.0+**, you can restore it:
```sql
CREATE UNIQUE INDEX `idx_attendance_user_day` ON `attendance`(user_id, (DATE(`datetime`)));
```

---

## Table Schemas

### `users`
```sql
CREATE TABLE `users` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  user_type VARCHAR(50),                          -- values: 'admin', 'staff', 'user'
  verification_code VARCHAR(50) DEFAULT NULL,
  verification_code_expiration TIMESTAMP DEFAULT NULL,
  is_verified TINYINT(1) DEFAULT 0,              -- was BOOLEAN
  profile_picture LONGBLOB,                       -- was BLOB
  last_logged_in TIMESTAMP DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  points INTEGER NOT NULL DEFAULT 0,
  dpa_consent INTEGER NOT NULL DEFAULT 0,
  dpa_consent_at TIMESTAMP DEFAULT NULL,
  address VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `inventory`
```sql
CREATE TABLE `inventory` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  item_name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 0,
  price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `announcements`
```sql
CREATE TABLE `announcements` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  image LONGBLOB,                                 -- was BLOB
  created_by VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `attendance`
```sql
CREATE TABLE `attendance` (
  `id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `datetime` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
> ⚠️ `datetime` is both the column name and a MySQL keyword — always quote it as `` `datetime` `` in queries.

### `feedback`
```sql
CREATE TABLE `feedback` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  about VARCHAR(255),
  reporterID INT,
  last_name VARCHAR(255),
  created_at DATETIME,
  `desc` VARCHAR(1000) NOT NULL,                 -- reserved word, must be backtick-quoted
  resolved_at DATETIME,
  status VARCHAR(255) NOT NULL,
  FOREIGN KEY (reporterID) REFERENCES `users`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `exercises`
```sql
CREATE TABLE `exercises` (
  exercise_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  name TEXT NOT NULL,
  target_muscle TEXT,
  `movement_type` TEXT NOT NULL                  -- values: 'Push', 'Pull', 'Legs', 'Core', 'Cardio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `transactions`
```sql
CREATE TABLE `transactions` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  receipt_number TEXT UNIQUE NOT NULL,
  customer_type TEXT NOT NULL,                   -- values: 'member', 'non-member', 'inventory', 'entry'
  user_id INTEGER,
  customer_name TEXT,
  amount DECIMAL(10, 2) NOT NULL,
  payment_method TEXT NOT NULL,
  staff_id INTEGER,
  transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status TEXT DEFAULT 'Confirmed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `desc` TEXT                                    -- reserved word, must be backtick-quoted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `member_profiles`
```sql
CREATE TABLE `member_profiles` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  age INTEGER,
  height_cm DOUBLE,                              -- was REAL
  weight_kg DOUBLE,                              -- was REAL
  fitness_level TEXT,                            -- values: 'Beginner', 'Intermediate', 'Advanced'
  goal TEXT,                                     -- values: 'Muscle Gain', 'Weight Loss', 'Endurance', 'General Fitness'
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `contact` TEXT,
  `gender` TEXT,
  `remarks` TEXT,
  `e_contact` TEXT,
  `e_name` TEXT,
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `inventory_notifications`
```sql
CREATE TABLE `inventory_notifications` (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,
  user_id INTEGER NOT NULL,
  item_id INTEGER NOT NULL,
  item_name VARCHAR(100) NOT NULL,
  qty_sold INTEGER NOT NULL,
  notif_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `notification_history`
```sql
CREATE TABLE `notification_history` (
  notif_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  `datetime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- ⚠️ reserved word, quote it
  remarks TEXT,
  is_read TINYINT(1) DEFAULT 0,                    -- was BOOLEAN
  category VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `membership_price`
```sql
CREATE TABLE `membership_price` (
  m_price_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  price DOUBLE NOT NULL,                           -- was REAL
  promo_type TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `old_member_profiles`
```sql
CREATE TABLE `old_member_profiles` (
  history_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  profile_id INTEGER,
  user_id INTEGER NOT NULL,
  age INTEGER,
  height_cm DOUBLE,
  weight_kg DOUBLE,
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
  `remarks` TEXT,
  FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES `users`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `monthly`
```sql
CREATE TABLE `monthly` (
  `id` INT,
  `name` TEXT,
  `expires_in` DATETIME,
  `member` INTEGER,
  image LONGBLOB,                                -- was BLOB
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `expense_history`
```sql
CREATE TABLE `expense_history` (
  expense_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  expense_name TEXT NOT NULL,
  expense DOUBLE NOT NULL,
  description TEXT,
  author TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `api_table`
```sql
CREATE TABLE `api_table` (
  api_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  api_name TEXT NOT NULL,
  api_url TEXT NOT NULL,
  api_key TEXT NOT NULL UNIQUE,
  status TEXT DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `workout_logs`
```sql
CREATE TABLE `workout_logs` (
  log_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  user_id INTEGER NOT NULL,
  exercise_id INTEGER NOT NULL,
  weight INTEGER NOT NULL,
  sets INTEGER NOT NULL,
  reps INTEGER NOT NULL,
  logged_at INTEGER NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `backup_history`
```sql
CREATE TABLE `backup_history` (
  backup_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  backup_filename VARCHAR(255),
  backup_size INTEGER,
  backup_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  backup_type VARCHAR(20),
  status VARCHAR(20),
  sent_to_email VARCHAR(255),
  created_by VARCHAR(100),
  notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `walk_attendance`
```sql
CREATE TABLE `walk_attendance` (
  `id` INT,
  `name` VARCHAR(255),
  `month_id` INT,
  `datetime` DATETIME,                           -- ⚠️ reserved word, quote it
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `meals`
```sql
CREATE TABLE `meals` (
  meal_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  food_name TEXT NOT NULL,
  serving_grams DOUBLE NOT NULL,
  calories DOUBLE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `meal_logs`
```sql
CREATE TABLE `meal_logs` (
  log_id INTEGER PRIMARY KEY AUTO_INCREMENT,
  user_id INTEGER NOT NULL,
  meal_id INTEGER NOT NULL,
  meal_type TEXT CHECK(meal_type IN ('breakfast','lunch','dinner','snack')),
  log_date DATE DEFAULT (CURDATE()),             -- was DATE('now')
  grams_consumed DOUBLE DEFAULT 0,
  calories DOUBLE,
  FOREIGN KEY (meal_id) REFERENCES `meals`(meal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Indexes

| Index Name | Table | Columns | Type |
|---|---|---|---|
| `idx_attendance_user_day` | `attendance` | `user_id`, `datetime` | Regular (was unique on `date(datetime)`) |
| `idx_attendance_user_datetime` | `attendance` | `user_id`, `datetime` | Regular |
| `idx_users_type_points` | `users` | `user_type`, `points` | Regular |
| `idx_old_member_profiles_user_id_archived_at` | `old_member_profiles` | `user_id`, `archived_at DESC` | Regular |
| `idx_old_member_profiles_profile_id` | `old_member_profiles` | `profile_id` | Regular |

---

## Trigger

```sql
DELIMITER //
CREATE TRIGGER `trg_member_profiles_archive_before_update`
BEFORE UPDATE ON `member_profiles`
FOR EACH ROW
BEGIN
  INSERT INTO `old_member_profiles` (
    profile_id, user_id, age, height_cm, weight_kg,
    fitness_level, goal, contact, gender,
    created_at, updated_at, changed_by_user_id, change_source
  ) VALUES (
    OLD.id, OLD.user_id, OLD.age, OLD.height_cm, OLD.weight_kg,
    OLD.fitness_level, OLD.goal, OLD.contact, OLD.gender,
    OLD.created_at, OLD.updated_at, NULL, 'before_update'
  );
END//
DELIMITER ;
```

---

## Columns to Watch in Code

These columns need special attention when updating queries:

| Table | Column | Issue |
|---|---|---|
| `transactions` | `desc` | MySQL reserved word — always write `` `desc` `` |
| `feedback` | `desc` | MySQL reserved word — always write `` `desc` `` |
| `attendance` | `datetime` | MySQL reserved word — always write `` `datetime` `` |
| `notification_history` | `datetime` | MySQL reserved word — always write `` `datetime` `` |
| `walk_attendance` | `datetime` | MySQL reserved word — always write `` `datetime` `` |
| `users` | `is_verified` | Now `TINYINT(1)`, not `BOOLEAN` — compare with `= 1` / `= 0` |
| `notification_history` | `is_read` | Now `TINYINT(1)`, not `BOOLEAN` |
| `member_profiles` | `height_cm`, `weight_kg` | Now `DOUBLE`, not `REAL` |
| `meal_logs` | `log_date` | Default changed from `DATE('now')` to `CURDATE()` |

---

## Import Instructions

```bash
mysql -u <user> -p <database_name> < gym_mysql.sql
```

Or via MySQL Workbench: Server → Data Import → Import from Self-Contained File → select `gym_mysql.sql`.

> **Note:** `SET FOREIGN_KEY_CHECKS=0` is set at the top of the file and re-enabled at the bottom, so table import order doesn't matter.
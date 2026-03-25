-- ============================================================
-- FITSTOP – Database Schema
-- ============================================================
-- The application supports SQLite (local development),
-- MySQL, and PostgreSQL (production / Render).
-- Run the appropriate section below when setting up a new
-- managed database.
-- ============================================================

-- ============================================================
-- SQLite (local development)
-- ============================================================

CREATE TABLE "users" (   
    id INTEGER PRIMARY KEY AUTOINCREMENT,   
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,  
    last_name VARCHAR(50) NOT NULL,   
    email VARCHAR(100) NOT NULL UNIQUE,   
    password VARCHAR(255) NOT NULL,   
    user_type VARCHAR(50),   
    verification_code VARCHAR(50) DEFAULT NULL,   
    verification_code_expiration TIMESTAMP DEFAULT NULL,   
    is_verified BOOLEAN DEFAULT FALSE,   profile_picture BLOB,   
    last_logged_in TIMESTAMP DEFAULT NULL,   
    dpa_consent INTEGER NOT NULL DEFAULT 0,
    dpa_consent_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP , 
    points INTEGER NOT NULL DEFAULT 0);


CREATE TABLE inventory (   
    id INTEGER PRIMARY KEY AUTOINCREMENT,   
    item_name VARCHAR(100) NOT NULL,   
    category VARCHAR(50) NOT NULL,   
    quantity INTEGER NOT NULL DEFAULT 0,   
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,   
    description TEXT,   
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
)

CREATE TABLE announcements (   
    id INTEGER PRIMARY KEY AUTOINCREMENT,   
    title VARCHAR(255) NOT NULL,   
    description TEXT NOT NULL,   
    image BLOB,   
    created_by VARCHAR(50),   
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
)

CREATE TABLE "attendance" (
    "id" integer NOT NULL,
    "user_id" integer NOT NULL,
    "datetime" datetime NOT NULL, 
    PRIMARY KEY ("id")
)

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
)

CREATE TABLE exercises (
    exercise_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    target_muscle TEXT,
    "movement_type" TEXT NOT NULL
)

CREATE TABLE workout_logs (
    log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,     
    exercise_id INTEGER NOT NULL,  
    weight REAL NOT NULL, 
    reps INTEGER NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE CASCADE
)

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
)

CREATE TABLE member_profiles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  age INTEGER,
  height_cm REAL,
  weight_kg REAL,
  fitness_level TEXT,
  goal TEXT,
  contact TEXT,
  gender TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_attendance_user_datetime
ON attendance(user_id, datetime);

CREATE INDEX IF NOT EXISTS idx_users_type_points
ON users(user_type, points);

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

CREATE INDEX IF NOT EXISTS idx_meal_logs_user_date
ON meal_logs(user_id, logged_date);

CREATE TABLE IF NOT EXISTS old_member_profiles (
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
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_old_member_profiles_user_id_archived_at
ON old_member_profiles(user_id, archived_at DESC);

CREATE INDEX IF NOT EXISTS idx_old_member_profiles_profile_id
ON old_member_profiles(profile_id);

CREATE TRIGGER IF NOT EXISTS trg_member_profiles_archive_before_update
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

-- ============================================================
-- MySQL (production – set DB_DRIVER=mysql in environment)
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type VARCHAR(50),
    verification_code VARCHAR(50) DEFAULT NULL,
    verification_code_expiration TIMESTAMP NULL DEFAULT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    profile_picture LONGBLOB,
    last_logged_in TIMESTAMP NULL DEFAULT NULL,
    dpa_consent INT NOT NULL DEFAULT 0,
    dpa_consent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    points INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image LONGBLOB,
    created_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    datetime DATETIME NOT NULL,
    INDEX idx_attendance_user_datetime (user_id, datetime),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feedback (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    about VARCHAR(255),
    reporterID INT,
    last_name VARCHAR(255),
    created_at DATETIME,
    `desc` VARCHAR(1000) NOT NULL,
    resolved_at DATETIME,
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY (reporterID) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exercises (
    exercise_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    target_muscle VARCHAR(100),
    movement_type VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workout_logs (
    log_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_id INT NOT NULL,
    weight DECIMAL(10,2) NOT NULL,
    reps INT NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(255) UNIQUE NOT NULL,
    customer_type VARCHAR(50) NOT NULL,
    user_id INT,
    customer_name VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    staff_id INT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `desc` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_profiles (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    age INT,
    height_cm DECIMAL(5,2),
    weight_kg DECIMAL(5,2),
    fitness_level VARCHAR(100),
    goal TEXT,
    contact VARCHAR(50),
    gender VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_users_type_points ON users(user_type, points);

CREATE TABLE IF NOT EXISTS meal_logs (
    meal_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    logged_date VARCHAR(10) NOT NULL,
    meal_type VARCHAR(50) NOT NULL,
    food_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(10,2) NOT NULL,
    carbs DECIMAL(10,2) NOT NULL,
    fat DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (meal_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_meal_logs_user_date ON meal_logs(user_id, logged_date);

CREATE TABLE IF NOT EXISTS old_member_profiles (
    history_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    profile_id INT,
    user_id INT NOT NULL,
    age INT,
    height_cm DECIMAL(5,2),
    weight_kg DECIMAL(5,2),
    fitness_level VARCHAR(100),
    goal TEXT,
    contact VARCHAR(50),
    gender VARCHAR(20),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changed_by_user_id INT,
    change_source VARCHAR(100) NOT NULL DEFAULT 'profile_update',
    change_note TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_old_member_profiles_user_id_archived_at ON old_member_profiles(user_id, archived_at);
CREATE INDEX IF NOT EXISTS idx_old_member_profiles_profile_id ON old_member_profiles(profile_id);

-- MySQL equivalent of the archive trigger (archives profile before each update):
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS trg_member_profiles_archive_before_update
BEFORE UPDATE ON member_profiles
FOR EACH ROW
BEGIN
  INSERT INTO old_member_profiles (
    profile_id, user_id, age, height_cm, weight_kg,
    fitness_level, goal, contact, gender,
    created_at, updated_at, changed_by_user_id, change_source
  ) VALUES (
    OLD.id, OLD.user_id, OLD.age, OLD.height_cm, OLD.weight_kg,
    OLD.fitness_level, OLD.goal, OLD.contact, OLD.gender,
    OLD.created_at, OLD.updated_at, NULL, 'before_update'
  );
END$$
DELIMITER ;

-- ============================================================
-- PostgreSQL (production – set DB_DRIVER=pgsql in environment)
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type VARCHAR(50),
    verification_code VARCHAR(50) DEFAULT NULL,
    verification_code_expiration TIMESTAMP DEFAULT NULL,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    profile_picture BYTEA,
    last_logged_in TIMESTAMP DEFAULT NULL,
    dpa_consent INTEGER NOT NULL DEFAULT 0,
    dpa_consent_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    points INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS inventory (
    id SERIAL PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 0,
    price NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image BYTEA,
    created_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    datetime TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_attendance_user_datetime ON attendance(user_id, datetime);

CREATE TABLE IF NOT EXISTS feedback (
    id SERIAL PRIMARY KEY,
    about VARCHAR(255),
    "reporterID" INTEGER,
    last_name VARCHAR(255),
    created_at TIMESTAMP,
    "desc" VARCHAR(1000) NOT NULL,
    resolved_at TIMESTAMP,
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY ("reporterID") REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS exercises (
    exercise_id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    target_muscle TEXT,
    movement_type TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS workout_logs (
    log_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    exercise_id INTEGER NOT NULL,
    weight NUMERIC(10,2) NOT NULL,
    reps INTEGER NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    receipt_number TEXT UNIQUE NOT NULL,
    customer_type TEXT NOT NULL,
    user_id INTEGER,
    customer_name TEXT,
    amount NUMERIC(10,2) NOT NULL,
    payment_method TEXT NOT NULL,
    staff_id INTEGER,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "desc" TEXT
);

CREATE TABLE IF NOT EXISTS member_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE,
    age INTEGER,
    height_cm NUMERIC(5,2),
    weight_kg NUMERIC(5,2),
    fitness_level TEXT,
    goal TEXT,
    contact TEXT,
    gender TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_users_type_points ON users(user_type, points);

CREATE TABLE IF NOT EXISTS meal_logs (
    meal_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    logged_date VARCHAR(10) NOT NULL,
    meal_type VARCHAR(50) NOT NULL,
    food_name VARCHAR(255) NOT NULL,
    quantity NUMERIC(10,2) NOT NULL,
    calories INTEGER NOT NULL,
    protein NUMERIC(10,2) NOT NULL,
    carbs NUMERIC(10,2) NOT NULL,
    fat NUMERIC(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_meal_logs_user_date ON meal_logs(user_id, logged_date);

CREATE TABLE IF NOT EXISTS old_member_profiles (
    history_id SERIAL PRIMARY KEY,
    profile_id INTEGER,
    user_id INTEGER NOT NULL,
    age INTEGER,
    height_cm NUMERIC(5,2),
    weight_kg NUMERIC(5,2),
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_old_member_profiles_user_id_archived_at ON old_member_profiles(user_id, archived_at DESC);
CREATE INDEX IF NOT EXISTS idx_old_member_profiles_profile_id ON old_member_profiles(profile_id);

-- PostgreSQL archive trigger:
CREATE OR REPLACE FUNCTION fn_archive_member_profile() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO old_member_profiles (
        profile_id, user_id, age, height_cm, weight_kg,
        fitness_level, goal, contact, gender,
        created_at, updated_at, changed_by_user_id, change_source
    ) VALUES (
        OLD.id, OLD.user_id, OLD.age, OLD.height_cm, OLD.weight_kg,
        OLD.fitness_level, OLD.goal, OLD.contact, OLD.gender,
        OLD.created_at, OLD.updated_at, NULL, 'before_update'
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER trg_member_profiles_archive_before_update
BEFORE UPDATE ON member_profiles
FOR EACH ROW EXECUTE FUNCTION fn_archive_member_profile();
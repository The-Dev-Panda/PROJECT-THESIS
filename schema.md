CREATE TABLE "users" (   id INTEGER PRIMARY KEY AUTOINCREMENT,   username VARCHAR(50) NOT NULL UNIQUE,   first_name VARCHAR(50) NOT NULL,   last_name VARCHAR(50) NOT NULL,   email VARCHAR(100) NOT NULL UNIQUE,   password VARCHAR(255) NOT NULL,   user_type VARCHAR(50),   verification_code VARCHAR(50) DEFAULT NULL,   verification_code_expiration TIMESTAMP DEFAULT NULL,   is_verified BOOLEAN DEFAULT FALSE,   profile_picture BLOB,   last_logged_in TIMESTAMP DEFAULT NULL,   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP , "points" INTEGER NOT NULL DEFAULT 0, dpa_consent INTEGER NOT NULL DEFAULT 0, dpa_consent_at TIMESTAMP DEFAULT NULL, "address" VARCHAR(255))


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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, "contact" text, "gender" text, "remarks" TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)

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
  change_note TEXT, "remarks" TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
)
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

CREATE TABLE "monthly" ("id" integer,"name" text,"expires_in" datetime, "member" INTEGER, PRIMARY KEY ("id"))

CREATE TABLE "emergency_contact" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "member_id" INTEGER,
    "name" TEXT NOT NULL,
    "contact" TEXT NOT NULL,
    FOREIGN KEY ("member_id") REFERENCES "members_profile" ("id")
);
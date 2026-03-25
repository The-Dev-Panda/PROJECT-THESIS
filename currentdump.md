PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "users" (   id INTEGER PRIMARY KEY AUTOINCREMENT,   username VARCHAR(50) NOT NULL UNIQUE,   first_name VARCHAR(50) NOT NULL,   last_name VARCHAR(50) NOT NULL,
email VARCHAR(100) NOT NULL UNIQUE,   password VARCHAR(255) NOT NULL,   user_type VARCHAR(50),   verification_code VARCHAR(50) DEFAULT NULL,   verification_code_expiration TIMESTAMP DE
FAULT NULL,   is_verified BOOLEAN DEFAULT FALSE,   profile_picture BLOB,   last_logged_in TIMESTAMP DEFAULT NULL,   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP
DEFAULT CURRENT_TIMESTAMP , "points" INTEGER NOT NULL DEFAULT 0, dpa_consent INTEGER NOT NULL DEFAULT 0, dpa_consent_at TIMESTAMP DEFAULT NULL);
INSERT INTO users VALUES(1,'alexthompson','Alex','Thompson','alex.thompson@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,NULL,1,NULL,'2026-03-25
15:33:58','2026-02-08 08:33:48','2026-02-08 08:33:48',0,0,NULL),
  (2,'mariarodriguez','Maria','Rodriguez','maria.rodriguez@email.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','staff',NULL,NULL,1,NULL,'2026-03-25 15:28:31','2026-
02-08 08:33:48','2026-02-08 08:33:48',0,0,NULL),
  (5,'michaelchen','Michael','Chen','angkevin33@gmail.com','$2y$10$cDadl.fuZfvDN9mWowhpK.Cs78ExFUSv9KT/gIdB4M83i2S1IqQZC','user',NULL,NULL,0,NULL,'2026-03-19 10:35:10','2026-02-08 13:40:
56','2026-02-08 13:40:56',3,0,NULL),
  (6,'emilypark','Emily','Park','emily.park@email.com','$2y$10$R6Q.6J5UK0xIgwFw6u1veujTXvMGb03Ga3Eh20tjrvxqBUOlsfg0m','user','VRF2T8N4K6L9',NULL,0,NULL,NULL,'2026-02-08 13:40:56','2026-0
2-08 13:40:56',4,0,NULL),
  (7,'lancechua','Lance','Chua','chua@gmail.com','$2y$10$WrNK6DfmdRbHUASVjSsi8uYJngdnhZ2Zlbl4yE.YapeNI7D1ioTyy','user',NULL,NULL,1,NULL,'2026-03-23 19:42:19','2026-03-19 16:41:25','2026-
03-19 16:41:25',4,1,'2026-03-23 11:42:21'),
  (8,'testpassword','Test','Password','lance@gmail.com','$2y$10$ISX1OAIfwxLaENFuqQpNiOxviXdG0b7wFBr9lc96lmOro9G5eoAUq','user',NULL,NULL,1,NULL,NULL,'2026-03-20 04:34:14','2026-03-20 04:3
4:14',4,0,NULL),
  (9,'pointtest','Point','Test','67@gmail.com','$2y$10$AM8QxnXl8NHrJj76Kr/Rpe4ZpNjk5S2ZfIeDQbT9Z0vGaVmR./Jpe','user',NULL,NULL,1,NULL,'2026-03-20 12:37:57','2026-03-20 04:37:03','2026-03
-20 04:37:03',4,0,NULL),
  (10,'kevin123','kevin','b','angkevin38@yahoo.com','$2y$10$najbbryzrZ8cQCfgsdItquyGz0qG6/I7s8yZOCDbwecMuPjFOrZYu','staff',NULL,NULL,1,NULL,NULL,'2026-03-24 00:58:30','2026-03-24 00:58:3
0',0,0,NULL),
  (11,'newattendance','NEW','ATTENDANCE','lance.andre.chua@gmail.com','$2y$10$QgkrDo6ndtkgw.08Ve4OjOt.QKqDCRYYAXce8EK6yshTXLJDkyOnW','user',NULL,NULL,0,NULL,NULL,'2026-03-25 03:55:45','2
026-03-25 03:55:45',1,0,NULL),
  (12,'sharienmaesalarda','Sharien','Mae Salarda','yshaymae12@gmail.com','$2y$10$Wuh1GnZC3oaNA.ZuHb3vHO2MBlMRl3VhbE64R7QX9WKbWebwOXBNC','user',NULL,NULL,0,NULL,NULL,'2026-03-25 13:16:00'
,'2026-03-25 13:16:00',1,0,NULL),
  (13,'nigganiggason','Nigga','NIggason','6767@gmail.com','$2y$10$.sDFscY4m1myi1t8uLxy2e3wOFxOQDvpYJVxQjsb27Ediaalg82wy','user',NULL,NULL,0,NULL,NULL,'2026-03-25 14:57:46','2026-03-25 14
:57:46',1,0,NULL),
  (14,'pogandanglilyror','pogandang','lilyror','lilyrorpogandang@gmail.com','$2y$10$r7Lk0rCPKADQgq1oOrDru.ePIF.6h4RY0S7.afSfcJHIF4BkH3H0u','user',NULL,NULL,0,NULL,NULL,'2026-03-25 15:33:
09','2026-03-25 15:33:09',0,0,NULL);
CREATE TABLE inventory (   id INTEGER PRIMARY KEY AUTOINCREMENT,   item_name VARCHAR(100) NOT NULL,   category VARCHAR(50) NOT NULL,   quantity INTEGER NOT NULL DEFAULT 0,   price DECIMA
L(10, 2) NOT NULL DEFAULT 0.00,   description TEXT,   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );
INSERT INTO inventory VALUES(2,'Sting','Beverage',34,20,'','2026-03-21 14:52:42','2026-03-25 05:13:25'),
  (3,'Amino','Supplements',9,10,'','2026-03-23 02:58:18','2026-03-25 05:14:13'),
  (4,'Pre-Workout','Supplements',18,35,'','2026-03-23 03:04:22','2026-03-25 06:18:16'),
  (5,'Gatorade','Beverage',28,25,'','2026-03-23 03:06:12','2026-03-25 01:28:39'),
  (6,'Creatine','Supplements',32,20,'','2026-03-23 03:06:45','2026-03-25 06:17:23'),
  (7,'Whey','Supplements',30,75,'','2026-03-23 03:07:18','2026-03-23 03:07:18'),
  (8,'Protein Bar','Snacks',8,120,'','2026-03-23 03:07:36','2026-03-23 03:27:20');
CREATE TABLE announcements (   id INTEGER PRIMARY KEY AUTOINCREMENT,   title VARCHAR(255) NOT NULL,   description TEXT NOT NULL,   image BLOB,   created_by VARCHAR(50),   created_at TIME
STAMP DEFAULT CURRENT_TIMESTAMP,   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );
INSERT INTO announcements VALUES(1,'NEW GYM SYSTEM','IMPLEMENTATION OF NEW GYM SYSTEM ON MARCH 29',unistr('񐎇\u000d\u000a\u001a\u000a'),'alexthompson','2026-03-02 01:22:40','2026-03-02 01
:22:40'),
  (2,'filter and sorting added to admin page ','functionality for sorting and filtering',NULL,'alexthompson','2026-03-24 01:18:26','2026-03-24 01:18:26'),
  (3,'3rd announcement test','testing',NULL,'alexthompson','2026-03-24 01:21:37','2026-03-24 01:21:37'),
  (4,'4th announcement test','testing',NULL,'alexthompson','2026-03-24 01:26:02','2026-03-24 01:26:02');
CREATE TABLE IF NOT EXISTS "attendance" ("id" integer NOT NULL,"user_id" integer NOT NULL,"datetime" datetime NOT NULL, PRIMARY KEY ("id"));
INSERT INTO attendance VALUES(1,7,'2026-03-19 16:49:13'),
  (2,6,'2026-03-20 04:25:10'),
  (3,7,'2026-03-20 04:25:12'),
  (4,5,'2026-03-20 04:25:15'),
  (5,8,'2026-03-20 04:34:36'),
  (6,9,'2026-03-20 04:37:12'),
  (7,8,'2026-03-21 15:24:42'),
  (8,7,'2026-03-22 12:59:37'),
  (9,9,'2026-03-22 13:06:05'),
  (10,6,'2026-03-22 13:06:12'),
  (11,8,'2026-03-22 13:06:24'),
  (12,5,'2026-03-22 14:30:18'),
  (13,7,'2026-03-23 02:49:17'),
  (14,9,'2026-03-23 03:35:20'),
  (15,6,'2026-03-23 03:42:18'),
  (16,8,'2026-03-25 00:39:41'),
  (17,7,'2026-03-25 01:07:07'),
  (18,6,'2026-03-25 01:08:21'),
  (19,5,'2026-03-25 01:34:40'),
  (20,9,'2026-03-25 02:58:10'),
  (21,11,'2026-03-25 03:55:57'),
  (22,12,'2026-03-25 06:27:22'),
  (23,13,'2026-03-25 06:57:57');
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
INSERT INTO feedback VALUES(1,'Smith Machine',NULL,'Test1','2026-03-14 03:38:12','Aliquam quo ad voluptas eos consequuntur praesentium voluptatem. Quis in minima odio et mollitia fuga nu
lla. Atque tenetur voluptatum vero dolorem tenetur.',NULL,'pending'),
  (2,'Seated Chest Press',5,'Chen','2026-03-14 03:39:22','Feedback test logged in as michaelchen',NULL,'closed'),
  (3,'Machine Row',1,'Thompson','2026-03-20 14:02:40','I am an admin testing feedback.',NULL,'closed');
CREATE TABLE exercises (
    exercise_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    target_muscle TEXT
, "movement_type" text NOT NULL);
INSERT INTO exercises VALUES(38,'Cable Bicep Curl','Biceps','Pull'),
  (39,'Cable Crossover','Chest','Push'),
  (40,'Chest Press','Chest','Push'),
  (41,'Chin-ups','Lats','Pull'),
  (42,'Close-Grip Row','Middle Back','Pull'),
  (43,'Decline Press','Lower Chest','Push'),
  (44,'Flat Chest Press','Chest','Push'),
  (45,'Hack Squat','Quads','Legs'),
  (46,'Incline Chest Press','Upper Chest','Push'),
  (47,'Incline Walking','Cardio','Cardio'),
  (48,'Jogging','Cardio','Cardio'),
  (49,'Lat Pulldown','Lats','Pull'),
  (50,'Lateral Raise','Side Delt','Push'),
  (51,'Leg Extension','Quads','Legs'),
  (52,'Leg Press','Quads','Legs'),
  (53,'Parallel Bar Dips','Triceps','Push'),
  (54,'Pec Deck Fly','Chest','Push'),
  (55,'Preacher Curl','Biceps','Pull'),
  (56,'Pullups','Lats','Pull'),
  (57,'Rear Delt Fly','Rear Delt','Pull'),
  (58,'Running','Cardio','Cardio'),
  (59,'Seated Cable Row','Middle Back','Pull'),
  (60,'Seated Leg Curl','Hamstrings','Legs'),
  (61,'Seated Machine Row','Middle Back','Pull'),
  (62,'Shoulder Press','Front Delt','Push'),
  (63,'Single-Arm Row','Lats','Pull'),
  (64,'Single-Leg Curl','Hamstrings','Legs'),
  (65,'Single-Leg Extension','Quads','Legs'),
  (66,'Smith Bench Press','Chest','Push'),
  (67,'Smith Row','Middle Back','Pull'),
  (68,'Smith Shoulder Press','Front Delt','Push'),
  (69,'Smith Squat','Legs','Legs'),
  (70,'Straight-Leg Raise','Lower Abs','Core'),
  (71,'Tricep Pushdown','Triceps','Push'),
  (72,'Vertical Knee Raise','Lower Abs','Core'),
  (73,'Walking','Cardio','Cardio'),
  (74,'Wide-Grip Row','Upper Back','Pull'),
  (75,'Standing Cable Chest Press','Chest','Push'),
  (76,'Dual-Cable Incline Chest Press','Upper Chest','Push'),
  (77,'Dual-Cable Decline Chest Press','Lower Chest','Push'),
  (78,'Single-Arm Cable Fly (High-to-Low)','Lower Chest','Push'),
  (79,'Single-Arm Cable Fly (Low-to-High)','Upper Chest','Push'),
  (80,'Cable Squeeze Press','Chest','Push'),
  (81,'Cable Reverse Fly','Rear Delt','Pull'),
  (82,'Cable High Row','Upper Back','Pull'),
  (83,'Cable Underhand Lat Pulldown','Lats','Pull'),
  (84,'Cable Lat Prayer Pulldown','Lats','Pull'),
  (85,'Cable Rear Delt Row','Rear Delt','Pull'),
  (86,'Cable Rope Pullover','Lats','Pull'),
  (87,'Cable Rope Hammer Curl','Biceps','Pull'),
  (88,'Cable Concentration Curl','Biceps','Pull'),
  (89,'Cable Cross-Body Curl','Biceps','Pull'),
  (90,'Cable Tricep Kickback','Triceps','Push'),
  (91,'Single-Arm Cable Tricep Kickback','Triceps','Push'),
  (92,'Cable Reverse-Grip Pushdown','Triceps','Push'),
  (93,'Cable Front Squat','Quads','Legs'),
  (94,'Cable Reverse Lunge','Legs','Legs'),
  (95,'Cable Lateral Lunge','Adductors','Legs'),
  (96,'Cable Romanian Deadlift','Hamstrings','Legs'),
  (97,'Standing Cable Hip Abduction','Glutes','Legs'),
  (98,'Standing Cable Hip Adduction','Adductors','Legs'),
  (99,'Cable Standing Calf Raise','Calves','Legs'),
  (100,'Standing Cable Crunch','Upper Abs','Core');
INSERT INTO exercises VALUES(101,'Cable Reverse Crunch','Lower Abs','Core'),
  (102,'Cable Pallof Hold','Obliques','Core'),
  (103,'Incline Dumbbell Fly','Upper Chest','Push'),
  (104,'Flat Dumbbell Fly','Chest','Push'),
  (105,'Decline Dumbbell Fly','Lower Chest','Push'),
  (106,'Neutral-Grip Dumbbell Bench Press','Chest','Push'),
  (107,'Single-Arm Dumbbell Bench Press','Chest','Push'),
  (108,'Dumbbell Pullover','Chest','Pull'),
  (109,'Incline Dumbbell Row','Lats','Pull'),
  (110,'Chest-Supported Dumbbell Row','Middle Back','Pull'),
  (111,'Renegade Row','Core','Pull'),
  (112,'Dumbbell Rear Delt Fly','Rear Delt','Pull'),
  (113,'Arnold Dumbbell Press','Front Delt','Push'),
  (114,'Dumbbell Z Press','Shoulders','Push'),
  (115,'Dumbbell Front Raise','Front Delt','Push'),
  (116,'Alternating Dumbbell Curl','Biceps','pull'),
  (117,'Dumbbell Concentration Curl','Biceps','Pull'),
  (118,'Dumbbell Zottman Curl','Biceps','Pull'),
  (119,'Dumbbell Reverse Curl','Brachialis','Pull'),
  (120,'Lying Dumbbell Tricep Extension','Triceps','Push'),
  (121,'Single-Arm Overhead Dumbbell Tricep Extension','Triceps','Push'),
  (122,'Dumbbell Tate Press','Triceps','Push'),
  (123,'Goblet Squat','Quads','Legs'),
  (124,'Dumbbell Romanian Deadlift','Hamstrings','Legs'),
  (125,'Dumbbell Step-Up','Quads','Legs'),
  (126,'Dumbbell Walking Lunge','Legs','Legs'),
  (127,'Dumbbell Lateral Lunge','Adductors','Legs'),
  (128,'Dumbbell Sumo Squat','Glutes','Legs'),
  (129,'Dumbbell Hip Thrust','Glutes','Legs'),
  (130,'Dumbbell Glute Bridge','Glutes','Legs'),
  (131,'Dumbbell Standing Calf Raise','Calves','Legs'),
  (132,'Dumbbell Seated Calf Raise','Calves','Legs'),
  (133,'Dumbbell Russian Twist','Obliques','Core'),
  (134,'Dumbbell Side Bend','Obliques','Core');
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
INSERT INTO workout_logs VALUES(1,6,49,25.0,12,'2026-03-17 12:33:09');
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
, "desc" TEXT);
INSERT INTO transactions VALUES(1,'RCP20260317-68120','member',5,'5',100,'Cash',NULL,'2026-03-17 13:08:15','Confirmed','2026-03-17 13:08:15','Paid For: Entrance + Sting | Notes: 2 Sting'
),
  (2,'RCP20260319-21101','member',7,'7',650,'Cash',NULL,'2026-03-19 16:42:33','Confirmed','2026-03-19 16:42:33','Paid For: Monthly'),
  (3,'INV-20260322-2-840','inventory',NULL,'Customer Purchase',60,'Inventory Deduction',NULL,'2026-03-22 14:14:29','completed','2026-03-22 14:14:29','Sold 3x Sting'),
  (4,'INV-20260323-2-800','inventory',NULL,'Customer Purchase',40,'Inventory Deduction',NULL,'2026-03-23 03:18:25','completed','2026-03-23 03:18:25','Sold 2x Sting'),
  (5,'INV-20260323-2-188','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',NULL,'2026-03-23 03:21:54','completed','2026-03-23 03:21:54','Sold 1x Sting'),
  (6,'INV-20260323-4-648','inventory',NULL,'Customer Purchase',35,'Inventory Deduction',NULL,'2026-03-23 03:24:28','completed','2026-03-23 03:24:28','Sold 1x Pre-Workout'),
  (7,'INV-20260323-6-583','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',NULL,'2026-03-23 03:24:47','completed','2026-03-23 03:24:47','Sold 1x Creatine'),
  (8,'INV-20260323-2-640','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',NULL,'2026-03-23 03:25:13','completed','2026-03-23 03:25:13','Sold 1x Sting'),
  (9,'INV-20260323-2-715','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',NULL,'2026-03-23 03:25:16','completed','2026-03-23 03:25:16','Sold 1x Sting'),
  (10,'SALE-20260323-5-107','inventory',NULL,'Walk-in Sale',25,'Cash',NULL,'2026-03-23 03:27:20','completed','2026-03-23 03:27:20','Daily Sale: 1x Gatorade @ ₱25 = ₱25'),
  (11,'SALE-20260323-6-479','inventory',NULL,'Walk-in Sale',120,'Cash',NULL,'2026-03-23 03:27:20','completed','2026-03-23 03:27:20','Daily Sale: 6x Creatine @ ₱20 = ₱120'),
  (12,'SALE-20260323-8-880','inventory',NULL,'Walk-in Sale',240,'Cash',NULL,'2026-03-23 03:27:20','completed','2026-03-23 03:27:20','Daily Sale: 2x Protein Bar @ ₱120 = ₱240'),
  (13,'ENTRY-20260323-5620','entry',NULL,'Daily Entry Fees',650,'Cash',NULL,'2026-03-23 03:27:20','completed','2026-03-23 03:27:20','Entry Fees: 10x Non-Member(₱60), 1x Member(₱50), 9x M
onthly'),
  (14,'INV-20260324-2-427','inventory',NULL,'Customer Purchase',60,'Inventory Deduction',NULL,'2026-03-24 06:09:55','completed','2026-03-24 06:09:55','Sold 3x Sting'),
  (15,'RCP20260324-15530','member',5,'Michael Chen',650,'GCash',2,'2026-03-24 12:10:11','Confirmed','2026-03-24 12:10:11','Paid For: Monthly'),
  (16,'INV-20260325-2-929','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 01:20:10','completed','2026-03-25 01:20:10','Sold 1x Sting'),
  (17,'INV-20260325-2-565','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 01:24:01','completed','2026-03-25 01:24:01','Sold 1x Sting'),
  (18,'INV-20260325-2-953','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 01:26:50','completed','2026-03-25 01:26:50','Sold 1x Sting');
INSERT INTO transactions VALUES(19,'INV-20260325-5-657','inventory',NULL,'Customer Purchase',25,'Inventory Deduction',2,'2026-03-25 01:28:39','completed','2026-03-25 01:28:39','Sold 1x G
atorade'),
  (20,'INV-20260325-2-829','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 01:31:49','completed','2026-03-25 01:31:49','Sold 1x Sting'),
  (21,'INV-20260325-2-123','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 04:10:33','completed','2026-03-25 04:10:33','Sold 1x Sting'),
  (22,'INV-20260325-2-211','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 04:22:24','completed','2026-03-25 04:22:24','Sold 1x Sting'),
  (23,'INV-20260325-2-272','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 04:27:18','completed','2026-03-25 04:27:18','Sold 1x Sting'),
  (24,'RCP20260325-74679','member',7,'Lance Chua',20,'Cash',2,'2026-03-25 05:13:25','Confirmed','2026-03-25 05:13:25','Paid For: Inventory - Sting'),
  (25,'INV-20260325-2-370','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 05:13:25','completed','2026-03-25 05:13:25','Sold 1x Sting'),
  (26,'INV-20260325-3-482','inventory',NULL,'Customer Purchase',10,'Inventory Deduction',2,'2026-03-25 05:14:13','completed','2026-03-25 05:14:13','Sold 1x Amino'),
  (27,'RCP20260325-93441','member',5,'Michael Chen',20,'Cash',2,'2026-03-25 06:17:23','Confirmed','2026-03-25 06:17:23','Paid For: Inventory - Creatine'),
  (28,'INV-20260325-6-100','inventory',NULL,'Customer Purchase',20,'Inventory Deduction',2,'2026-03-25 06:17:23','completed','2026-03-25 06:17:23','Sold 1x Creatine'),
  (29,'RCP20260325-35425','member',11,'NEW ATTENDANCE',35,'Cash',2,'2026-03-25 06:18:16','Confirmed','2026-03-25 06:18:16','Paid For: Inventory - Pre-Workout'),
  (30,'INV-20260325-4-408','inventory',NULL,'Customer Purchase',35,'Inventory Deduction',2,'2026-03-25 06:18:16','completed','2026-03-25 06:18:16','Sold 1x Pre-Workout'),
  (31,'RCP20260325-64361','member',7,'Lance Chua',500,'Cash',2,'2026-03-25 06:39:40','Confirmed','2026-03-25 06:39:40','Paid For: Membership'),
  (32,'RCP20260325-50637','member',6,'Emily Park',40,'Cash',2,'2026-03-25 06:40:11','Confirmed','2026-03-25 06:40:11','Paid For: Special Rate'),
  (33,'RCP20260325-14616','member',5,'Michael Chen',500,'Cash',2,'2026-03-25 06:40:31','Confirmed','2026-03-25 06:40:31','Paid For: Membership'),
  (34,'RCP20260325-91504','member',6,'Emily Park',500,'Cash',2,'2026-03-25 06:53:37','Confirmed','2026-03-25 06:53:37','Paid For: Membership'),
  (35,'RCP20260325-16646','member',5,'Michael Chen',500,'Cash',2,'2026-03-25 06:54:38','Confirmed','2026-03-25 06:54:38','Paid For: Membership'),
  (36,'RCP20260325-36929','member',9,'Point Test',50,'Cash',2,'2026-03-25 06:55:04','Confirmed','2026-03-25 06:55:04','Paid For: Day Pass / Walk-In'),
  (37,'RCP20260325-62039','member',13,'Nigga NIggason',500,'Cash',2,'2026-03-25 06:58:24','Confirmed','2026-03-25 06:58:24','Paid For: Membership');
INSERT INTO transactions VALUES(38,'RCP20260325-65823','member',14,'pogandang lilyror',50,'Cash',2,'2026-03-25 07:33:25','Confirmed','2026-03-25 07:33:25','Paid For: Day Pass / Walk-In')
;
CREATE TABLE member_profiles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  age INTEGER,
  height_cm REAL,
  weight_kg REAL,
  fitness_level TEXT,
  goal TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, "contact" text, "gender" text,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
INSERT INTO member_profiles VALUES(1,5,22,170.0,70.0,'Beginner','Muscle Gain','2026-03-19 02:36:34','2026-03-19 02:36:34',NULL,NULL),
  (2,7,22,179.0,88.0,'Intermediate','Weight Loss','2026-03-19 16:41:25','2026-03-22 14:34:39','09338698009','Male'),
  (3,8,22,169.0,67.0,'Intermediate','Endurance','2026-03-20 04:34:14','2026-03-20 04:34:14',NULL,NULL),
  (4,9,67,167.0,67.0,'Advanced','Weight Loss','2026-03-20 04:37:03','2026-03-20 04:37:03',NULL,NULL),
  (5,11,22,178.0,88.0,'Advanced','Muscle Gain','2026-03-25 03:55:45','2026-03-25 03:55:45',NULL,NULL),
  (6,12,20,166.0,50.0,'Beginner','Weight Loss','2026-03-25 13:16:00','2026-03-25 13:16:00',NULL,NULL),
  (7,13,67,167.0,67.0,'Advanced','General Fitness','2026-03-25 14:57:46','2026-03-25 14:57:46',NULL,NULL),
  (8,14,35,120.0,56.0,'Beginner','Weight Loss','2026-03-25 15:33:09','2026-03-25 15:33:09',NULL,NULL);
CREATE TABLE inventory_notifications (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER NOT NULL,
  item_id     INTEGER NOT NULL,
  item_name   VARCHAR(100) NOT NULL,
  qty_sold    INTEGER NOT NULL,
  notif_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO inventory_notifications VALUES(1,0,2,'Sting',2,'2026-03-21 15:29:32'),
  (2,0,2,'Sting',1,'2026-03-21 15:49:33'),
  (3,0,2,'Sting',3,'2026-03-21 16:17:46'),
  (4,0,2,'Sting',2,'2026-03-21 16:20:14'),
  (5,0,1,'whey',1,'2026-03-22 13:03:36');
CREATE TABLE notification_history (   notif_id INTEGER PRIMARY KEY AUTOINCREMENT,   name VARCHAR(255) NOT NULL,   description TEXT,   datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   rema
rks TEXT,   is_read BOOLEAN DEFAULT 0,   category VARCHAR(100) );
INSERT INTO notification_history VALUES(1,'ITEM ADDED','Item Name: Amino, Category: Supplements, Quantity: 10, Price: 10, Description: ','2026-03-23 02:58:18','BY ADMIN',1,'Inventory'),
  (2,'ITEM UPDATED','Item Name: whey, Category: Supplements, Quantity: 15, Price: 75, Description: protein ','2026-03-23 02:58:33','BY ADMIN',0,'Inventory'),
  (3,'ITEM UPDATED','Item Name: Sting, Category: Beverage, Quantity: 50, Price: 20, Description: ','2026-03-23 02:58:39','BY ADMIN',0,'Inventory'),
  (4,'ITEM ADDED','Item Name: Pre-Workout, Category: Supplements, Quantity: 20, Price: 35, Description: ','2026-03-23 03:04:22','BY ADMIN',0,'Inventory'),
  (5,'ITEM ADDED','Item Name: Gatorade, Category: Beverage, Quantity: 30, Price: 25, Description: ','2026-03-23 03:06:12','BY ADMIN',0,'Inventory'),
  (6,'ITEM ADDED','Item Name: Creatine, Category: Supplements, Quantity: 40, Price: 20, Description: ','2026-03-23 03:06:45','BY ADMIN',1,'Inventory'),
  (7,'INVENTORY ITEM DELETED','Item ''whey'' (15 units) from category ''Supplements'' has been removed','2026-03-23 03:07:04','Deleted by alexthompson',1,'Inventory'),
  (8,'ITEM ADDED','Item Name: Whey, Category: Supplements, Quantity: 30, Price: 75, Description: ','2026-03-23 03:07:18','BY ADMIN',0,'Inventory'),
  (9,'ITEM ADDED','Item Name: Protein Bar, Category: Snacks, Quantity: 10, Price: 120, Description: ','2026-03-23 03:07:36','BY ADMIN',0,'Inventory'),
  (10,'STAFF CREATED','kevin123, kevin, b, angkevin38@yahoo.com','2026-03-24 00:58:31','BY ADMIN',0,'Accounts'),
  (11,'ANNOUNCEMENT CREATED','functionality for sorting and filtering','2026-03-24 01:18:27','alexthompson',0,'Announcements'),
  (12,'ANNOUNCEMENT CREATED','testing','2026-03-24 01:21:37','alexthompson',0,'Announcements'),
  (13,'ANNOUNCEMENT CREATED','testing','2026-03-24 01:26:02','alexthompson',0,'Announcements'),
  (14,'New Member','username: newattendance','2026-03-25 11:55:45','Created via API',0,'membership'),
  (15,'New Member','username: sharienmaesalarda','2026-03-25 13:16:00','Created via API',0,'membership'),
  (16,'New Member','username: nigganiggason','2026-03-25 14:57:46','Created via API',0,'membership'),
  (17,'New Member','username: pogandanglilyror','2026-03-25 15:33:09','Created via API',0,'membership');
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
CREATE TABLE membership_price (     m_price_id INTEGER PRIMARY KEY AUTOINCREMENT,     price REAL NOT NULL,       promo_type TEXT,     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,     u
pdated_at DATETIME DEFAULT CURRENT_TIMESTAMP );
INSERT INTO membership_price VALUES(1,500.0,'annual','2026-03-24 06:03:51','2026-03-24 06:03:51'),
  (2,750.0,'monthly','2026-03-24 06:05:07','2026-03-24 06:05:07');
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
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
PRAGMA writable_schema=ON;
CREATE TABLE IF NOT EXISTS sqlite_sequence(name,seq);
DELETE FROM sqlite_sequence;
INSERT INTO sqlite_sequence VALUES('users',14),
  ('inventory',8),
  ('announcements',4),
  ('feedback',3),
  ('exercises',134),
  ('workout_logs',1),
  ('transactions',38),
  ('member_profiles',8),
  ('inventory_notifications',5),
  ('notification_history',17),
  ('membership_price',2);
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
PRAGMA writable_schema=OFF;
COMMIT;
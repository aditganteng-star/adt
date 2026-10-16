-- =====================================================================
--  Hakuna Matata Course — skema database (MySQL / MariaDB, XAMPP)
--  Dijalankan otomatis oleh setup.php. Aman dijalankan berulang kali.
-- =====================================================================

CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  google_id     VARCHAR(64)  NULL,
  avatar_url    VARCHAR(500) NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'student',   -- student | tutor | admin
  phone         VARCHAR(30)  NULL,
  school        VARCHAR(150) NULL,
  grade_level   VARCHAR(20)  NULL,                          -- SD | SMP | SMA | UTBK
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tutor_profiles (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  name        VARCHAR(120) NOT NULL,
  subject     VARCHAR(120) NOT NULL,
  university  VARCHAR(150) NOT NULL,
  major       VARCHAR(150) NOT NULL,
  bio         TEXT,
  photo       VARCHAR(255) NULL,                            -- path foto (assets/uploads/tutors/...)
  achievement VARCHAR(255) NULL,
  experience  VARCHAR(80)  NULL,
  levels      VARCHAR(80)  NULL,                            -- contoh: SMP,SMA
  color       VARCHAR(20)  NOT NULL DEFAULT 'green',
  is_active   TINYINT NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS packages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(60)  NOT NULL UNIQUE,
  name        VARCHAR(150) NOT NULL,
  category    VARCHAR(30)  NOT NULL,                        -- les | tryout | utbk
  level       VARCHAR(20)  NOT NULL,                        -- SD | SMP | SMA | UTBK
  description VARCHAR(500) NOT NULL,
  features    TEXT NOT NULL,                                -- satu fitur per baris
  price       INT NOT NULL,                                 -- harga per bulan / per paket
  old_price   INT NULL,                                     -- harga coret
  unit        VARCHAR(30) NOT NULL DEFAULT 'bulan',
  badge       VARCHAR(40) NULL,
  is_active   TINYINT NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  package_id   INT NULL,
  package_name VARCHAR(150) NOT NULL,
  duration     INT NOT NULL DEFAULT 1,
  total        INT NOT NULL DEFAULT 0,
  name         VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  NOT NULL,
  email        VARCHAR(190) NULL,
  note         VARCHAR(500) NULL,
  source       VARCHAR(30)  NOT NULL DEFAULT 'paket',       -- paket | kontak | hero
  status       VARCHAR(20)  NOT NULL DEFAULT 'baru',        -- baru | diproses | aktif | batal
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entitlements (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  package_id INT NOT NULL,
  expires_at DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, package_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  subject     VARCHAR(100) NOT NULL,
  level       VARCHAR(20)  NOT NULL,
  tutor_id    INT NOT NULL,
  schedule    VARCHAR(150) NULL,
  description VARCHAR(500) NULL,
  join_code   VARCHAR(12)  NOT NULL UNIQUE,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  class_id   INT NOT NULL,
  student_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (class_id, student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS class_sessions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  class_id     INT NOT NULL,
  title        VARCHAR(190) NOT NULL,
  session_date DATE NOT NULL,
  attend_code  VARCHAR(8) NOT NULL,
  is_open      TINYINT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  student_id INT NOT NULL,
  status     VARCHAR(10) NOT NULL DEFAULT 'Hadir',          -- Hadir | Izin | Sakit | Alpa
  note       VARCHAR(300) NULL,
  method     VARCHAR(10) NOT NULL DEFAULT 'tutor',          -- tutor | kode
  marked_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (session_id, student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materials (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  class_id    INT NOT NULL,
  title       VARCHAR(190) NOT NULL,
  description VARCHAR(600) NULL,
  kind        VARCHAR(10) NOT NULL DEFAULT 'link',          -- file | link | video
  url         VARCHAR(1000) NULL,
  file_name   VARCHAR(255) NULL,
  file_path   VARCHAR(255) NULL,
  created_by  INT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  class_id    INT NOT NULL,
  title       VARCHAR(190) NOT NULL,
  description TEXT NULL,
  due_date    DATE NULL,
  max_score   INT NOT NULL DEFAULT 100,
  created_by  INT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS submissions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT NOT NULL,
  student_id    INT NOT NULL,
  answer        TEXT NULL,
  file_name     VARCHAR(255) NULL,
  file_path     VARCHAR(255) NULL,
  score         INT NULL,
  feedback      VARCHAR(600) NULL,
  status        VARCHAR(15) NOT NULL DEFAULT 'Dikumpulkan', -- Dikumpulkan | Dinilai
  submitted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (assignment_id, student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  class_id   INT NOT NULL,
  title      VARCHAR(190) NOT NULL,
  body       VARCHAR(1000) NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tryouts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(190) NOT NULL,
  category     VARCHAR(30)  NOT NULL DEFAULT 'UTBK',
  description  VARCHAR(500) NULL,
  duration_min INT NOT NULL DEFAULT 30,
  package_id   INT NULL,                                    -- NULL = gratis untuk semua murid
  is_public    TINYINT NOT NULL DEFAULT 0,                  -- tampil sebagai mini-tryout di halaman publik
  is_published TINYINT NOT NULL DEFAULT 1,
  created_by   INT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tryout_questions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tryout_id   INT NOT NULL,
  subtest     VARCHAR(80) NOT NULL DEFAULT 'Umum',
  question    TEXT NOT NULL,
  option_a    VARCHAR(300) NOT NULL,
  option_b    VARCHAR(300) NOT NULL,
  option_c    VARCHAR(300) NOT NULL,
  option_d    VARCHAR(300) NOT NULL,
  correct     CHAR(1) NOT NULL,
  explanation VARCHAR(800) NULL,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tryout_attempts (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tryout_id   INT NOT NULL,
  student_id  INT NOT NULL,
  answers     TEXT NULL,                                    -- JSON {question_id: "A"}
  correct_cnt INT NOT NULL DEFAULT 0,
  total_cnt   INT NOT NULL DEFAULT 0,
  score       INT NOT NULL DEFAULT 0,
  started_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  finished_at TIMESTAMP NULL
) ENGINE=InnoDB;

-- ============================================================
-- Cybersecurity Skills Hub — setup_database.sql
-- ------------------------------------------------------------
-- HOW TO RUN
--   phpMyAdmin:  start Apache + MySQL in XAMPP, open
--                http://localhost/phpmyadmin, click the SQL tab,
--                paste this whole file and press Go.
--
--   Command line:  mysql -u root -p < setup_database.sql
-- ============================================================

-- 1. The database
CREATE DATABASE IF NOT EXISTS cyberskillshub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cyberskillshub;

-- 2. The table that stores form submissions.
--    The `course` column stores the short code submitted by the
--    <select> in register.html (hygiene / network / soc / hacking);
--    PHP maps codes to display names when showing records.
--    `goals` allows 200 characters, matching the form's maxlength.
DROP TABLE IF EXISTS registrations;

CREATE TABLE registrations (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fullname        VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    phone           VARCHAR(30)   NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,   -- bcrypt hash, never plain text
    level           ENUM('beginner','intermediate','advanced') NOT NULL,
    course          VARCHAR(20)   NOT NULL,   -- hygiene | network | soc | hacking
    goals           VARCHAR(200)  NULL,
    terms_accepted  TINYINT(1)    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email),               -- stops duplicate sign-ups
    KEY idx_course (course),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Three sample rows so the records page is not empty the first
--    time it is demonstrated. Course values are the real option
--    codes from the form. The hash below is bcrypt of "password".
INSERT INTO registrations
    (fullname, email, phone, password_hash, level, course, goals, terms_accepted)
VALUES
    ('Amina Wanjiru', 'amina.wanjiru@example.co.ke', '+254712345678',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'beginner', 'network',
     'Move from IT support into a SOC analyst role within a year.', 1),

    ('Brian Otieno', 'brian.otieno@example.co.ke', '+254798765432',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'intermediate', 'hacking',
     'Preparing for CompTIA Security+ and my first pen-test role.', 1),

    ('Faith Njeri', 'faith.njeri@example.co.ke', '+254733112233',
     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
     'intermediate', 'soc',
     'Land a Tier 1 SOC role at an MSSP in Nairobi.', 1);

-- 4. Confirm it worked
SELECT id, fullname, email, level, course, created_at
FROM registrations
ORDER BY created_at DESC;

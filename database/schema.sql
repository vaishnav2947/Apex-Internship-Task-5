-- ═══════════════════════════════════════════════════════════════
-- database/schema.sql
-- ApexPlanet Internship — Complete Database Schema
--
-- HOW TO IMPORT:
--   1. Open http://localhost/phpmyadmin
--   2. Click "New" → name it "blog" → Create
--   3. Click the "blog" database → Import tab
--   4. Choose this file → Go
--
-- OR via command line:
--   mysql -u root -p < database/schema.sql
-- ═══════════════════════════════════════════════════════════════

-- ── Create & select database ─────────────────────────────────
CREATE DATABASE IF NOT EXISTS blog
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE blog;

-- ── Drop tables in reverse dependency order (for fresh reinstall) ──
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;


-- ════════════════════════════════════════════
-- TABLE: users
-- Stores registered users of the application
-- Task 2: basic columns
-- Task 4: added role, is_active, failed_login_attempts
-- ════════════════════════════════════════════
CREATE TABLE users (
    id                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    username              VARCHAR(50)      NOT NULL,
    email                 VARCHAR(120)     NOT NULL,
    password              VARCHAR(255)     NOT NULL,  -- bcrypt hash (never plain text)
    role                  ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    is_active             TINYINT(1)       NOT NULL DEFAULT 1,
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until          DATETIME                  DEFAULT NULL,
    created_at            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    UNIQUE KEY uq_email    (email),
    KEY idx_role           (role)           -- fast queries by role
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════
-- TABLE: posts
-- Blog posts created by users
-- Task 2: create, read, update, delete
-- Task 3: search (title/content), pagination
-- ════════════════════════════════════════════
CREATE TABLE posts (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,               -- FK → users.id
    title      VARCHAR(200) NOT NULL,
    content    TEXT         NOT NULL,
    status     ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user_id   (user_id),
    KEY idx_status    (status),
    KEY idx_created   (created_at),
    FULLTEXT KEY ft_search (title, content),       -- enables fast FULLTEXT search (Task 3)

    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE                          -- delete posts when user deleted
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════
-- TABLE: activity_log
-- Audit trail — who did what and when (Task 4 security)
-- ════════════════════════════════════════════
CREATE TABLE activity_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED          DEFAULT NULL,  -- NULL = anonymous/unauthenticated
    action     VARCHAR(100) NOT NULL,               -- e.g. 'login', 'create_post'
    ip_address VARCHAR(45)           DEFAULT NULL,  -- IPv4 or IPv6
    user_agent VARCHAR(300)          DEFAULT NULL,
    details    TEXT                  DEFAULT NULL,  -- JSON or free text
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user_id  (user_id),
    KEY idx_action   (action),
    KEY idx_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════
-- SEED DATA — sample users & posts
-- ════════════════════════════════════════════

-- Admin user  (password: Admin@1234)
-- Hash generated with: password_hash('Admin@1234', PASSWORD_BCRYPT)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@apexplanet.in',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Editor user (password: Editor@1234)
INSERT INTO users (username, email, password, role) VALUES
('editor', 'editor@apexplanet.in',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor');

-- Regular user (password: User@1234)
INSERT INTO users (username, email, password, role) VALUES
('john_doe', 'john@example.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- NOTE: The hash above decodes to the password "password"
-- In real seed data you would generate individual hashes.
-- To generate your own: php -r "echo password_hash('YourPass', PASSWORD_BCRYPT);"


-- Sample posts
INSERT INTO posts (user_id, title, content, status) VALUES
(1, 'Welcome to ApexPlanet Blog',
 'This is the first post on our internship blog application. Built with PHP & MySQL using the PDO extension for secure database access.',
 'published'),

(1, 'Understanding PHP PDO',
 'PDO (PHP Data Objects) provides a consistent interface for accessing databases. Using prepared statements with PDO prevents SQL injection attacks, one of the most common web vulnerabilities.',
 'published'),

(2, 'Bootstrap 5 Grid System Explained',
 'Bootstrap 5 uses a 12-column grid system. Columns are defined using classes like col-md-6 (6 of 12 columns on medium screens). The grid is fully responsive and mobile-first.',
 'published'),

(3, 'Getting Started with Git',
 'Git is a distributed version control system. Key commands: git init, git add, git commit, git push, git pull. Always write meaningful commit messages describing what changed and why.',
 'published'),

(2, 'MySQL Indexing for Performance',
 'Database indexes speed up SELECT queries but slow down INSERT/UPDATE. Add indexes to columns used in WHERE, ORDER BY, and JOIN clauses. Use EXPLAIN to analyze query performance.',
 'draft');

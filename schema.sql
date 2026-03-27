-- Task Manager Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_manager;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    title       VARCHAR(255)  NOT NULL,
    description TEXT,
    status      ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    priority    ENUM('low','medium','high')               NOT NULL DEFAULT 'medium',
    due_date    DATE,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample user (password: Password@123)
INSERT INTO users (name, email, password) VALUES
('Alice Johnson', 'alice@example.com', '$2y$12$YetX8Nt1rJHCw2DKCxp4/.JU9UXLnv8z5UMoHAGGLPdj1KLGp5pKe');

-- Sample tasks for Alice (user_id = 1)
INSERT INTO tasks (user_id, title, description, status, priority, due_date) VALUES
(1, 'Set up project repository',  'Initialise Git and push boilerplate code.',         'completed',   'high',   '2026-03-10'),
(1, 'Design database schema',     'Create ERD and write schema.sql.',                   'completed',   'high',   '2026-03-12'),
(1, 'Build authentication flow',  'Register, login, logout with session management.',   'in_progress', 'high',   '2026-03-30'),
(1, 'Implement dashboard page',   'Task grid with filters and summary stat cards.',     'in_progress', 'medium', '2026-04-02'),
(1, 'Write unit tests',           'Cover auth and CRUD operations.',                    'pending',     'medium', '2026-04-10'),
(1, 'Deploy to production',       'Configure server, run migrations, go live.',         'pending',     'low',    '2026-04-20');

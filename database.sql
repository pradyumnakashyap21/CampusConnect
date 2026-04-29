-- ================================================
-- COLLEGE EVENT MANAGEMENT SYSTEM - DATABASE SCHEMA
-- Phase 1: Authentication & Core Setup
-- ================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS college_events;
USE college_events;

-- ================================================
-- Table 1: USERS
-- ================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    college VARCHAR(255),
    stream VARCHAR(100),
    year INT,
    interests TEXT,
    projects TEXT,
    avatar VARCHAR(255),
    bio TEXT,
    phone VARCHAR(15),
    is_club_member INT DEFAULT 0,
    club_id INT,
    role ENUM('student', 'club_member', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- ================================================
-- Table 2: CLUBS
-- ================================================
CREATE TABLE IF NOT EXISTS clubs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    president_id INT,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (president_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ================================================
-- Table 3: CATEGORIES
-- ================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================================
-- Table 4: EVENTS
-- ================================================
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    club VARCHAR(255),
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(255),
    description TEXT,
    poster_path VARCHAR(255),
    registration_link VARCHAR(500),
    created_by INT NOT NULL,
    total_views INT DEFAULT 0,
    total_registrations INT DEFAULT 0,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ================================================
-- Table 5: EVENT_REGISTRATIONS
-- ================================================
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id)
);

-- ================================================
-- INSERT DEFAULT DATA
-- ================================================

-- Insert Default Clubs
INSERT INTO clubs (club_name, description) VALUES
('Tech Club', 'For tech enthusiasts and developers'),
('Design Club', 'Creative design and UI/UX club'),
('Arts Society', 'Cultural and arts appreciation'),
('Sports Association', 'Sports and fitness activities'),
('Music Club', 'Music lovers and performers');

-- Insert Default Categories
INSERT INTO categories (category_name, description, icon) VALUES
('Technical', 'Coding, hackathons, tech talks', '💻'),
('Cultural', 'Festivals, performances, exhibitions', '🎭'),
('Sports', 'Games, tournaments, fitness activities', '⚽'),
('Social', 'Social gatherings and networking', '👥'),
('Workshop', 'Training sessions and workshops', '📚');

-- ================================================
-- Indexes for Performance
-- ================================================
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_user_club ON users(club_id);
CREATE INDEX idx_event_date ON events(event_date);
CREATE INDEX idx_event_category ON events(category);
CREATE INDEX idx_event_created_by ON events(created_by);
CREATE INDEX idx_registration_event ON event_registrations(event_id);
CREATE INDEX idx_registration_user ON event_registrations(user_id);

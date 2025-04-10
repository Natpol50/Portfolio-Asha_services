-- Main database schema for multilingual portfolio website

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Languages supported by the system
CREATE TABLE languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE COMMENT 'ISO language code (e.g., "en", "fr")',
    name VARCHAR(50) NOT NULL COMMENT 'Language name in its own language',
    is_active BOOLEAN DEFAULT TRUE
);

-- Projects/Experiences
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Project type (e.g., "personal", "cesi", etc.)',
    status VARCHAR(20) NOT NULL COMMENT 'Current, past, or canceled',
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL if ongoing',
    github_url VARCHAR(255) NULL,
    website_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Project translations (multilingual content)
CREATE TABLE project_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    language_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    description TEXT NOT NULL,
    skills TEXT NOT NULL COMMENT 'Comma-separated list of skills',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (project_id, language_id)
);

-- Personal info (about section)
CREATE TABLE personal_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    github_url VARCHAR(255) NOT NULL,
    linkedin_url VARCHAR(255) NOT NULL,
    discord_url VARCHAR(255) NULL,
    profile_picture_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Personal info translations
CREATE TABLE personal_info_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_info_id INT NOT NULL,
    language_id INT NOT NULL,
    about_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personal_info_id) REFERENCES personal_info(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (personal_info_id, language_id)
);

-- Static UI Texts (for multilingual interface elements)
CREATE TABLE ui_texts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_key VARCHAR(100) NOT NULL COMMENT 'Unique identifier for the text',
    context VARCHAR(100) NULL COMMENT 'Where this text is used'
);

-- UI text translations
CREATE TABLE ui_text_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ui_text_id INT NOT NULL,
    language_id INT NOT NULL,
    text TEXT NOT NULL,
    FOREIGN KEY (ui_text_id) REFERENCES ui_texts(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (ui_text_id, language_id)
);

-- Initialize languages
INSERT INTO languages (code, name) VALUES 
('en', 'English'), 
('fr', 'Français');

-- Insert default user (password: Admin@123)
INSERT INTO users (username, email, password_hash, first_name, last_name) 
VALUES ('admin', 'nathan.polette@gmail.com', '$2y$10.lMK', 'Nathan', 'Polette');
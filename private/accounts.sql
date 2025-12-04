CREATE DATABASE IF NOT EXISTS guardia_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE guardia_app;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des événements
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    theme VARCHAR(25) NULL,
    description TEXT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    end_date DATE NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(50) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    country VARCHAR(100) DEFAULT 'France',
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création des utilisateurs MySQL
-- Compte admin (tous droits)
CREATE USER IF NOT EXISTS 'admin'@'%' IDENTIFIED BY 'adminpassword';
GRANT ALL PRIVILEGES ON guardia_app.* TO 'admin'@'%';

-- Compte utilisateur limité (SELECT, INSERT, UPDATE, DELETE)
CREATE USER IF NOT EXISTS 'appuser'@'%' IDENTIFIED BY 'apppassword';
GRANT SELECT, INSERT, UPDATE, DELETE ON guardia_app.* TO 'appuser'@'%';

FLUSH PRIVILEGES;
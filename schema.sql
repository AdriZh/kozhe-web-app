-- Run once in MySQL (e.g. via phpMyAdmin) before opening the app.
CREATE DATABASE IF NOT EXISTS cheaply_assured_raccoon;
USE cheaply_assured_raccoon;

CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,   -- bcrypt hash from password_hash()
    email VARCHAR(50),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

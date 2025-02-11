CREATE DATABASE IF NOT EXISTS fungidb;

USE fungidb;

CREATE TABLE fungi (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) UNIQUE,
	author VARCHAR(255),
	edibility ENUM('buen-comestible', 'comestible', 'comestible-precaucion',
		'excelente-comestible', 'excelente-comestible-precaucion',
		'mortal', 'no-comestible', 'sin-valor', 'toxica') NOT NULL,
	habitat TEXT,
	observations TEXT,
	common_name VARCHAR(255),
	synonym VARCHAR(255),
	title VARCHAR(255)
);

CREATE TABLE taxonomy (
	fungi_id INT PRIMARY KEY,
	division VARCHAR(100),
	subdivision VARCHAR(100),
	class VARCHAR(100),
	subclass VARCHAR(100),
	ordo VARCHAR(100),
	family VARCHAR(100),
	FOREIGN KEY (fungi_id) REFERENCES fungi(id)
);

CREATE TABLE characteristics (
	fungi_id INT PRIMARY KEY,
	cap TEXT,
	hymenium TEXT,
	stipe TEXT,
	flesh TEXT,
	FOREIGN KEY (fungi_id) REFERENCES fungi(id)
);

CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(255) UNIQUE NOT NULL,
	email VARCHAR(255) UNIQUE NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('user', 'admin') DEFAULT 'user',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE access_logs (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT,
	access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	action ENUM('login', 'logout', 'view_fungi', 'edit_fungi') NOT NULL,
	ip_address VARCHAR(255),
	FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE jwt_tokens (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT,
	token VARCHAR(1024) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	expires_at TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id)
);


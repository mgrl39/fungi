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

CREATE TABLE image_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(255) UNIQUE NOT NULL COMMENT 'Ej: upload_path, thumbnail_path',
    path VARCHAR(255) NOT NULL COMMENT 'Ruta base ej: "/uploads/fungi/"',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo ej: "amanita-muscaria-1.jpg"',
    config_key VARCHAR(255) NOT NULL COMMENT 'Clave para obtener la ruta base',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (config_key) REFERENCES image_config(config_key)
);

CREATE TABLE fungi_images (
    fungi_id INT NOT NULL,
    image_id INT NOT NULL,
    PRIMARY KEY (fungi_id, image_id),
    FOREIGN KEY (fungi_id) REFERENCES fungi(id),
    FOREIGN KEY (image_id) REFERENCES images(id)
);

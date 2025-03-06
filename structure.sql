-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Temps de generació: 06-03-2025 a les 15:20:23
-- Versió del servidor: 8.0.39-0ubuntu0.22.04.1
-- Versió de PHP: 8.1.2-1ubuntu2.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de dades: `fungidb`
--

-- --------------------------------------------------------

--
-- Estructura de la taula `access_logs`
--

CREATE TABLE `access_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `access_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `action` enum('login','logout','view_fungi','edit_fungi') NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `characteristics`
--

CREATE TABLE `characteristics` (
  `fungi_id` int NOT NULL,
  `cap` text,
  `hymenium` text,
  `stipe` text,
  `flesh` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `fungi`
--

CREATE TABLE `fungi` (
  `id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `edibility` enum('buen-comestible','comestible','comestible-precaucion','excelente-comestible','excelente-comestible-precaucion','mortal','no-comestible','sin-valor','toxica') NOT NULL,
  `habitat` text,
  `observations` text,
  `common_name` varchar(255) DEFAULT NULL,
  `synonym` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `fungi_images`
--

CREATE TABLE `fungi_images` (
  `fungi_id` int NOT NULL,
  `image_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `fungi_popularity`
--

CREATE TABLE `fungi_popularity` (
  `fungi_id` int NOT NULL,
  `views` int DEFAULT '0',
  `likes` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `images`
--

CREATE TABLE `images` (
  `id` int NOT NULL,
  `filename` varchar(255) NOT NULL COMMENT 'Nombre del archivo ej: "amanita-muscaria-1.jpg"',
  `config_key` varchar(255) NOT NULL COMMENT 'Clave para obtener la ruta base',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `image_config`
--

CREATE TABLE `image_config` (
  `id` int NOT NULL,
  `config_key` varchar(255) NOT NULL COMMENT 'Ej: upload_path, thumbnail_path',
  `path` varchar(255) NOT NULL COMMENT 'Ruta base ej: "/uploads/fungi/"',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `jwt_tokens`
--

CREATE TABLE `jwt_tokens` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `token` varchar(1024) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `taxonomy`
--

CREATE TABLE `taxonomy` (
  `fungi_id` int NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `subdivision` varchar(100) DEFAULT NULL,
  `class` varchar(100) DEFAULT NULL,
  `subclass` varchar(100) DEFAULT NULL,
  `ordo` varchar(100) DEFAULT NULL,
  `family` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `jwt` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `bio` text,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `user_favorites`
--

CREATE TABLE `user_favorites` (
  `user_id` int NOT NULL,
  `fungi_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de la taula `user_likes`
--

CREATE TABLE `user_likes` (
  `user_id` int NOT NULL,
  `fungi_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índexs per a les taules bolcades
--

--
-- Índexs per a la taula `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índexs per a la taula `characteristics`
--
ALTER TABLE `characteristics`
  ADD PRIMARY KEY (`fungi_id`);

--
-- Índexs per a la taula `fungi`
--
ALTER TABLE `fungi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índexs per a la taula `fungi_images`
--
ALTER TABLE `fungi_images`
  ADD PRIMARY KEY (`fungi_id`,`image_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Índexs per a la taula `fungi_popularity`
--
ALTER TABLE `fungi_popularity`
  ADD PRIMARY KEY (`fungi_id`);

--
-- Índexs per a la taula `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `config_key` (`config_key`);

--
-- Índexs per a la taula `image_config`
--
ALTER TABLE `image_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Índexs per a la taula `jwt_tokens`
--
ALTER TABLE `jwt_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índexs per a la taula `taxonomy`
--
ALTER TABLE `taxonomy`
  ADD PRIMARY KEY (`fungi_id`);

--
-- Índexs per a la taula `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índexs per a la taula `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`user_id`,`fungi_id`),
  ADD KEY `fungi_id` (`fungi_id`);

--
-- Índexs per a la taula `user_likes`
--
ALTER TABLE `user_likes`
  ADD PRIMARY KEY (`user_id`,`fungi_id`),
  ADD KEY `fungi_id` (`fungi_id`);

--
-- AUTO_INCREMENT per les taules bolcades
--

--
-- AUTO_INCREMENT per la taula `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la taula `fungi`
--
ALTER TABLE `fungi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la taula `images`
--
ALTER TABLE `images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la taula `image_config`
--
ALTER TABLE `image_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la taula `jwt_tokens`
--
ALTER TABLE `jwt_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la taula `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restriccions per a les taules bolcades
--

--
-- Restriccions per a la taula `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restriccions per a la taula `characteristics`
--
ALTER TABLE `characteristics`
  ADD CONSTRAINT `characteristics_ibfk_1` FOREIGN KEY (`fungi_id`) REFERENCES `fungi` (`id`);

--
-- Restriccions per a la taula `fungi_images`
--
ALTER TABLE `fungi_images`
  ADD CONSTRAINT `fungi_images_ibfk_1` FOREIGN KEY (`fungi_id`) REFERENCES `fungi` (`id`),
  ADD CONSTRAINT `fungi_images_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`);

--
-- Restriccions per a la taula `fungi_popularity`
--
ALTER TABLE `fungi_popularity`
  ADD CONSTRAINT `fungi_popularity_ibfk_1` FOREIGN KEY (`fungi_id`) REFERENCES `fungi` (`id`);

--
-- Restriccions per a la taula `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`config_key`) REFERENCES `image_config` (`config_key`);

--
-- Restriccions per a la taula `jwt_tokens`
--
ALTER TABLE `jwt_tokens`
  ADD CONSTRAINT `jwt_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restriccions per a la taula `taxonomy`
--
ALTER TABLE `taxonomy`
  ADD CONSTRAINT `taxonomy_ibfk_1` FOREIGN KEY (`fungi_id`) REFERENCES `fungi` (`id`);

--
-- Restriccions per a la taula `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`fungi_id`) REFERENCES `fungi` (`id`);

--
-- Restriccions per a la taula `user_likes`
--
ALTER TABLE `user_likes`
  ADD CONSTRAINT `user_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_likes_ibfk_2` FOREIGN KEY (`fungi_id`) REFERENCES `fungi` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

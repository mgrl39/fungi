-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 05-03-2025 a las 07:07:55
-- Versión del servidor: 8.0.39-0ubuntu0.22.04.1
-- Versión de PHP: 8.1.2-1ubuntu2.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fungidb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
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

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `jwt`, `token`, `avatar_url`, `bio`, `last_login`) VALUES
(3, 'daniel', 'daniel@gmail.com', '$2y$10$d/eyKE7s7DR9A0qa9EZU8ON4X.Hi7uuxlCaWnhLPkQj1F2avrJDka', 'user', '2025-03-05 05:43:01', NULL, NULL, NULL, NULL, NULL),
(5, 'pepe', 'pepe@gmail.com', '$2y$10$K0gql80Ji2s2P1AyoOcmYup6BndgodlvHTvi0/wXO0ah69LKGDFIq', 'user', '2025-03-05 05:45:31', NULL, NULL, NULL, NULL, NULL),
(7, 'paula', 'paula@gmail.com', '$2y$10$rSjCke6LasqmbkTaN0y7R.bObbsplODWgVXS06SuunSSlTofKJRyy', 'user', '2025-03-05 05:47:03', NULL, NULL, NULL, NULL, NULL),
(8, 'rbuen', 'ruben@gmail.com', 'rubenruben', 'user', '2025-03-05 05:59:19', NULL, NULL, NULL, NULL, NULL),
(9, 'rohan', 'rohan@gmail.com', '$2y$10$Z2cK3dUKlgp/qRW0iinHTOn2mBLtmF.uoONooUDKkotpwv7xA3Xxm', 'user', '2025-03-05 06:01:03', NULL, NULL, NULL, NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Modificar la tabla users
ALTER TABLE users 
ADD COLUMN jwt VARCHAR(255),
ADD COLUMN token VARCHAR(255),
ADD COLUMN avatar_url VARCHAR(255),
ADD COLUMN bio TEXT,
ADD COLUMN last_login TIMESTAMP;

-- Crear tabla de favoritos
CREATE TABLE user_favorites (
    user_id INT,
    fungi_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, fungi_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (fungi_id) REFERENCES fungi(id)
);

CREATE TABLE jwt_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    is_revoked BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

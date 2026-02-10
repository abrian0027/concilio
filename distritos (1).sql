-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-02-2026 a las 17:44:06
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `eventos_iml`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distritos`
--

CREATE TABLE `distritos` (
  `id` int(11) NOT NULL,
  `conferencia_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `distritos`
--

INSERT INTO `distritos` (`id`, `conferencia_id`, `codigo`, `nombre`, `activo`, `creado_en`) VALUES
(1, 1, 'NO-101', 'Distrito Santiago', 1, '2026-01-18 22:18:19'),
(2, 1, 'NO-102', 'Distrito Santiago Sur', 1, '2026-01-18 22:18:19'),
(3, 1, 'NO-103', 'Distrito Puerto Plata', 1, '2026-01-18 22:18:19'),
(4, 1, 'NO-104', 'Distrito Valverde', 1, '2026-01-18 22:18:19'),
(5, 2, 'CE-101', 'Distrito Central', 1, '2026-01-18 22:18:19'),
(6, 2, 'CE-102', 'Distrito NorCentral', 1, '2026-01-18 22:18:19'),
(7, 2, 'CE-103', 'Distrito Nordeste 1', 1, '2026-01-18 22:18:19'),
(8, 2, 'CE-104', 'Distrito Nordeste 2', 1, '2026-01-18 22:18:19'),
(9, 2, 'CE-105', 'Distrito Noreste', 1, '2026-01-18 22:18:19'),
(10, 3, 'SE-101', 'Distrito Santo Domingo Central', 1, '2026-01-18 22:18:19'),
(11, 3, 'SE-102', 'Distrito Santo Domingo Oriental', 1, '2026-01-18 22:18:19'),
(12, 3, 'SE-103', 'Distrito Este', 1, '2026-01-18 22:18:19'),
(13, 3, 'SE-104', 'Distrito Sur', 1, '2026-01-18 22:18:19');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `distritos`
--
ALTER TABLE `distritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_distrito_conferencia` (`conferencia_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `distritos`
--
ALTER TABLE `distritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `distritos`
--
ALTER TABLE `distritos`
  ADD CONSTRAINT `fk_distrito_conferencia` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

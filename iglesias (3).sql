-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-02-2026 a las 17:45:00
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
-- Estructura de tabla para la tabla `iglesias`
--

CREATE TABLE `iglesias` (
  `id` int(11) NOT NULL,
  `distrito_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `iglesias`
--

INSERT INTO `iglesias` (`id`, `distrito_id`, `codigo`, `nombre`, `activo`, `creado_en`) VALUES
(40, 10, '', 'IML – Casa del Padre – Hotel Golden House', 1, '2026-01-24 10:37:29'),
(41, 10, '', 'IML – Cristo Rey', 1, '2026-01-24 10:37:29'),
(42, 10, '', 'IML – Ensanche La Fe', 1, '2026-01-24 10:37:29'),
(43, 10, '', 'IML – Ensanche Luperón', 1, '2026-01-24 10:37:29'),
(44, 10, '', 'IML – Ensanche Quisqueya', 1, '2026-01-24 10:37:29'),
(45, 10, '', 'IML – Arroyo Bonito – Capilla Quisqueya', 1, '2026-01-24 10:37:29'),
(46, 10, '', 'IML – Haina Étnico', 1, '2026-01-24 10:37:29'),
(47, 10, '', 'IML – Boca Nigua – Capilla Haina Boca Étnico', 1, '2026-01-24 10:37:29'),
(48, 10, '', 'IML – Haina Shalom', 1, '2026-01-24 10:37:29'),
(49, 10, '', 'IML – Herrera – Barrio Enriquillo', 1, '2026-01-24 10:37:29'),
(50, 10, '', 'IML – Palmarejo – Capilla Herrera', 1, '2026-01-24 10:37:29'),
(51, 10, '', 'IML – Jardines del Norte', 1, '2026-01-24 10:37:29'),
(52, 10, '', 'IML – Jesús el Mesías (La 15) – Barrio 27 de Febrero', 1, '2026-01-24 10:37:29'),
(53, 10, '', 'IML – Juan de Morfa (Central)', 1, '2026-01-24 10:37:29'),
(54, 10, '', 'IML – Km 24 – Barrio Eduardo Brito, Autopista Duarte', 1, '2026-01-24 10:37:29'),
(55, 10, '', 'IML – Km 24 Étnico – Capilla', 1, '2026-01-24 10:37:29'),
(56, 10, '', 'IML – Manoguayabo – Hato Nuevo', 1, '2026-01-24 10:37:29'),
(57, 10, '', 'IML – Nación Santa – Enriquillo', 1, '2026-01-24 10:37:29'),
(58, 10, '', 'IML – Haina Balsequillo – Capilla Nación Santa', 1, '2026-01-24 10:37:29'),
(59, 10, '', 'IML – Majagual, Sabana Perdida – Capilla Nación Santa', 1, '2026-01-24 10:37:29'),
(60, 10, '', 'IML – Pantoja', 1, '2026-01-24 10:37:29'),
(61, 10, '', 'IML – Roca Mar – En Su Presencia', 1, '2026-01-24 10:37:29'),
(62, 10, '', 'IML – Constanza – Capilla En Su Presencia', 1, '2026-01-24 10:37:29'),
(63, 10, '', 'IML – Simón Bolívar', 1, '2026-01-24 10:37:29'),
(64, 10, '', 'IML – Villa Linda – Ciudad Satélite – Capilla', 1, '2026-01-24 10:37:29'),
(65, 11, '', 'IML – Alma Rosa Primera', 1, '2026-01-24 10:43:47'),
(66, 11, '', 'IML – Carretera Mella (Luz en las Tinieblas)', 1, '2026-01-24 10:43:47'),
(67, 11, '', 'IML – Ensanche Isabelita', 1, '2026-01-24 10:43:47'),
(68, 11, '', 'IML – Ensanche Cancela (Étnico)', 1, '2026-01-24 10:43:47'),
(69, 11, '', 'IML – Ensanche Ozama', 1, '2026-01-24 10:43:47'),
(70, 11, '', 'IML – Mendoza – Capilla Ozama', 1, '2026-01-24 10:43:47'),
(71, 11, '', 'IML – Invivienda', 1, '2026-01-24 10:43:47'),
(72, 11, '', 'IML – Villa Esfuerzo – Capilla Invivienda', 1, '2026-01-24 10:43:47'),
(73, 11, '', 'IML – Los Frailes I', 1, '2026-01-24 10:43:47'),
(74, 11, '', 'IML – Los Mina', 1, '2026-01-24 10:43:47'),
(75, 11, '', 'IML – Los Tres Brazos', 1, '2026-01-24 10:43:47'),
(76, 11, '', 'IML – Los Tres Ojos', 1, '2026-01-24 10:43:47'),
(77, 11, '', 'IML – Urbanización Ciudad Juan Bosch', 1, '2026-01-24 10:43:47'),
(78, 11, '', 'IML – Urbanización Lomisa', 1, '2026-01-24 10:43:47'),
(79, 11, '', 'IML – Valiente (Étnico)', 1, '2026-01-24 10:43:47'),
(80, 11, '', 'IML – Villa Faro', 1, '2026-01-24 10:43:47'),
(81, 11, '', 'IML – Villa Mella – Buena Vista II', 1, '2026-01-24 10:43:47'),
(82, 11, '', 'IML – Villa Mella – El Edén', 1, '2026-01-24 10:43:47'),
(83, 11, '', 'IML – Villa Mella – Guaricano Étnico', 1, '2026-01-24 10:43:47'),
(84, 11, '', 'IML – Villa Mella – Vista Bella III', 1, '2026-01-24 10:43:47'),
(85, 12, '', 'IML – El Valle', 1, '2026-01-24 10:47:37'),
(86, 12, '', 'IML – Higüey I', 1, '2026-01-24 10:47:37'),
(87, 12, '', 'IML – Higüey II', 1, '2026-01-24 10:47:37'),
(88, 12, '', 'IML – Magua', 1, '2026-01-24 10:47:37'),
(89, 12, '', 'IML – Romana I (Central)', 1, '2026-01-24 10:47:37'),
(90, 12, '', 'IML – Romana II – Quisqueya', 1, '2026-01-24 10:47:37'),
(91, 12, '', 'IML – Romana III – Casa de Alabanzas', 1, '2026-01-24 10:47:37'),
(92, 12, '', 'IML – Romana IV – Villa Progreso', 1, '2026-01-24 10:47:37'),
(93, 12, '', 'IML – Romana V – La Lechoza', 1, '2026-01-24 10:47:37'),
(94, 12, '', 'IML – Romana VI – Barrio George', 1, '2026-01-24 10:47:37'),
(95, 12, '', 'IML – Romana VII – Piedra Linda', 1, '2026-01-24 10:47:37'),
(96, 12, '', 'IML – Romana VIII', 1, '2026-01-24 10:47:37'),
(97, 12, '', 'IML – Romana IX', 1, '2026-01-24 10:47:37'),
(98, 12, '', 'IML – Romana X', 1, '2026-01-24 10:47:37'),
(99, 12, '', 'IML – Romana XI (Benjamín)', 1, '2026-01-24 10:47:37'),
(100, 12, '', 'IML – Sabana de la Mar', 1, '2026-01-24 10:47:37'),
(101, 12, '', 'IML – San Pedro I – Central', 1, '2026-01-24 10:47:37'),
(102, 12, '', 'IML – San Pedro II – Villa Olímpica', 1, '2026-01-24 10:47:37'),
(103, 12, '', 'IML – San Pedro IV (Canaán) – Capilla San Pedro II', 1, '2026-01-24 10:47:37'),
(104, 12, '', 'IML – San Pedro III – Barrio Miramar', 1, '2026-01-24 10:47:37'),
(105, 13, '', 'IML – Azua Central', 1, '2026-01-24 10:51:07'),
(106, 13, '', 'IML – Azua – Finca 6', 1, '2026-01-24 10:51:07'),
(107, 13, '', 'IML – Azua – Finca Étnico', 1, '2026-01-24 10:51:07'),
(108, 13, '', 'IML – Azua – Las Charcas (Étnico)', 1, '2026-01-24 10:51:07'),
(109, 13, '', 'IML – Azua – Sector El Hoyo', 1, '2026-01-24 10:51:07'),
(110, 13, '', 'IML – Baní', 1, '2026-01-24 10:51:07'),
(111, 13, '', 'IML – Baní – El Fundo – Capilla Baní', 1, '2026-01-24 10:51:07'),
(112, 13, '', 'IML – Barahona', 1, '2026-01-24 10:51:07'),
(113, 13, '', 'IML – Elías Piña', 1, '2026-01-24 10:51:07'),
(114, 13, '', 'IML – Ocoa Étnico', 1, '2026-01-24 10:51:07'),
(115, 13, '', 'IML – San Cristóbal', 1, '2026-01-24 10:51:07'),
(116, 13, '', 'IML – San Cristóbal (Étnico)', 1, '2026-01-24 10:51:07'),
(117, 13, '', 'IML – San Cristóbal Étnico II', 1, '2026-01-24 10:51:07'),
(118, 13, '', 'IML – San José de Ocoa', 1, '2026-01-24 10:51:07'),
(119, 13, '', 'IML – San Juan I (Central)', 1, '2026-01-24 10:51:07'),
(120, 13, '', 'IML – San Juan II – Casa de Adoración', 1, '2026-01-24 10:51:07'),
(121, 13, '', 'IML – San Juan III (El Renuevo)', 1, '2026-01-24 10:51:07'),
(122, 5, '', 'IML-San Francisco Central', 1, '2026-01-24 11:05:10'),
(123, 5, '', 'IML-Castillo', 1, '2026-01-24 11:05:10'),
(124, 5, '', 'IML-Cotui', 1, '2026-01-24 11:05:10'),
(125, 5, '', 'IML-El Indio', 1, '2026-01-24 11:05:10'),
(126, 5, '', 'IML-La Bija', 1, '2026-01-24 11:05:10'),
(127, 5, '', 'IML-Piantini', 1, '2026-01-24 11:05:10'),
(128, 5, '', 'IML-Pimentel', 1, '2026-01-24 11:05:10'),
(129, 5, '', 'IML-Soledad', 1, '2026-01-24 11:05:10'),
(130, 5, '', 'IML-Ventura Grullón', 1, '2026-01-24 11:05:10'),
(131, 5, '', 'IML-Villa Rivas', 1, '2026-01-24 11:05:10'),
(132, 5, '', 'IML-Vista al Valle', 1, '2026-01-24 11:05:10'),
(133, 5, '', 'IML-La Enea', 1, '2026-01-24 11:05:10'),
(134, 6, '', 'IML-La Espinola', 1, '2026-01-24 11:07:23'),
(135, 6, '', 'IML-Bayacanes', 1, '2026-01-24 11:07:23'),
(136, 6, '', 'IML-Palmar', 1, '2026-01-24 11:07:23'),
(137, 6, '', 'IML-Salcedo', 1, '2026-01-24 11:07:23'),
(138, 6, '', 'IML-Moca', 1, '2026-01-24 11:07:23'),
(139, 6, '', 'IML-La Vega', 1, '2026-01-24 11:07:23'),
(140, 6, '', 'IML-Bonao', 1, '2026-01-24 11:07:23'),
(141, 7, '', 'IML-Buenos Aires', 1, '2026-01-24 11:08:22'),
(142, 7, '', 'IML-La Pichinga', 1, '2026-01-24 11:08:22'),
(143, 7, '', 'IML-El Factor', 1, '2026-01-24 11:08:22'),
(144, 7, '', 'IML-Barrio Quisqueyano', 1, '2026-01-24 11:08:22'),
(145, 7, '', 'IML-Barrio Santísima Trinidad', 1, '2026-01-24 11:08:22'),
(146, 7, '', 'IML-La Ceja', 1, '2026-01-24 11:08:22'),
(147, 7, '', 'IML-La Cejita', 1, '2026-01-24 11:08:22'),
(148, 7, '', 'IML-Los Limones', 1, '2026-01-24 11:08:22'),
(149, 7, '', 'IML-San José de Villa', 1, '2026-01-24 11:08:22'),
(150, 7, '', 'IML-El Yayal', 1, '2026-01-24 11:08:22'),
(151, 7, '', 'IML-Kilometro 3', 1, '2026-01-24 11:08:22'),
(152, 7, '', 'IML-La Capitalita', 1, '2026-01-24 11:08:22'),
(153, 7, '', 'IML-Las Quinientas', 1, '2026-01-24 11:08:22'),
(154, 7, '', 'IML-La Travesia', 1, '2026-01-24 11:08:22'),
(155, 7, '', 'IML-Matancitas', 1, '2026-01-24 11:08:22'),
(156, 7, '', 'IML-Los Yayales', 1, '2026-01-24 11:08:22'),
(157, 7, '', 'IML-Soldado Arriba', 1, '2026-01-24 11:08:22'),
(158, 8, '', 'IML-Baoba Pueblo Nuevo', 1, '2026-01-24 11:09:09'),
(159, 8, '', 'IML-La Piragua', 1, '2026-01-24 11:09:09'),
(160, 8, '', 'IML-Bella Vista', 1, '2026-01-24 11:09:09'),
(161, 8, '', 'IML-Boba', 1, '2026-01-24 11:09:09'),
(162, 8, '', 'IML-El Juncal', 1, '2026-01-24 11:09:09'),
(163, 8, '', 'IML-Los Naranjos', 1, '2026-01-24 11:09:09'),
(164, 8, '', 'IML-Los Rincones de Boba', 1, '2026-01-24 11:09:09'),
(165, 8, '', 'IML-Las Gordas', 1, '2026-01-24 11:09:09'),
(166, 8, '', 'IML-Nagua Central', 1, '2026-01-24 11:09:09'),
(167, 8, '', 'IML-Telanza', 1, '2026-01-24 11:09:09'),
(168, 8, '', 'IML-La Entrada', 1, '2026-01-24 11:09:09'),
(169, 8, '', 'IML-Abreu', 1, '2026-01-24 11:09:09'),
(170, 8, '', 'IML-Baoba Central', 1, '2026-01-24 11:09:09'),
(171, 9, '', 'IML-Arroyo Hondo Robalos', 1, '2026-01-24 11:28:29'),
(172, 9, '', 'IML-El Limón', 1, '2026-01-24 11:28:29'),
(173, 9, '', 'IML-El Naranjito – Las Terrenas', 1, '2026-01-24 11:28:29'),
(174, 9, '', 'IML-La Ceiba - Las Terrenas', 1, '2026-01-24 11:28:29'),
(175, 9, '', 'IML-La Majagua', 1, '2026-01-24 11:28:29'),
(176, 9, '', 'IML-La Pascuala', 1, '2026-01-24 11:28:29'),
(177, 9, '', 'IML-Las Terrenas', 1, '2026-01-24 11:28:29'),
(178, 9, '', 'IML-Los Corrales', 1, '2026-01-24 11:28:29'),
(179, 9, '', 'IML-Los Robalos', 1, '2026-01-24 11:28:29'),
(180, 9, '', 'IML-Punta Gorda', 1, '2026-01-24 11:28:29'),
(181, 9, '', 'IML-Samaná', 1, '2026-01-24 11:28:29'),
(182, 9, '', 'IML-Sánchez', 1, '2026-01-24 11:28:29'),
(183, 9, '', 'IML-Catey', 1, '2026-01-24 11:28:29'),
(184, 1, '', 'IML – Santiago – Juan Fernando', 1, '2026-01-24 14:26:29'),
(185, 1, '', 'IML – Santiago – Griselda Morfe', 1, '2026-01-24 14:26:29'),
(186, 1, '', 'IML – Santiago – Nathanael Félix', 1, '2026-01-24 14:26:29'),
(187, 1, '', 'IML – Santiago – Juan Aridio Familia', 1, '2026-01-24 14:26:29'),
(188, 1, '', 'IML – Santiago – Cristóbal Pichardo', 1, '2026-01-24 14:26:29'),
(189, 1, '', 'IML – Santiago – Luis Manuel Lora', 1, '2026-01-24 14:26:29'),
(190, 1, '', 'IML – Santiago – Audy Martínez', 1, '2026-01-24 14:26:29'),
(191, 1, '', 'IML – Los Cocos – Santiago', 1, '2026-01-24 14:26:29'),
(192, 1, '', 'IML – Puesto Grande – Santiago', 1, '2026-01-24 14:26:29'),
(193, 1, '', 'IML – Villa Olímpica – Santiago', 1, '2026-01-24 14:26:29'),
(194, 1, '', 'IML – Santiago – Félix Menas', 1, '2026-01-24 14:26:29'),
(195, 1, '', 'IML – Cien Fuego – Santiago – Pedro Vanderlinder', 1, '2026-01-24 14:26:29'),
(196, 1, '', 'IML – Cien Fuego – Santiago – Victoria Rodríguez', 1, '2026-01-24 14:26:29'),
(197, 1, '', 'IML – Navarrete – Santiago', 1, '2026-01-24 14:26:29'),
(198, 1, '', 'IML – Gurabo – Santiago', 1, '2026-01-24 14:26:29'),
(199, 1, '', 'IML – Santiago – Rosa Ángela Liz', 1, '2026-01-24 14:26:29'),
(200, 1, '', 'IML – Urbanización Fernando – Santiago', 1, '2026-01-24 14:26:29'),
(201, 1, '', 'IML – Valerio, Calle 4, Casa 8 – Santiago de los Caballeros', 1, '2026-01-24 14:26:29'),
(202, 1, '', 'IML – Edificio 1, Apto. 1D', 1, '2026-01-24 14:26:29'),
(203, 1, '', 'IML – Manzana A', 1, '2026-01-24 14:26:29'),
(204, 1, '', 'IML – Villa Olímpica – Santiago II', 1, '2026-01-24 14:26:29'),
(205, 1, '', 'IML – Beller – Santiago', 1, '2026-01-24 14:26:29'),
(206, 1, '', 'IML – Los Cerritos – Santiago – Calle Proyecto #3', 1, '2026-01-24 14:26:29'),
(207, 1, '', 'IML – Canca, La Piedra – Santiago', 1, '2026-01-24 14:26:29'),
(208, 1, '', 'IML – El INVI – Santiago', 1, '2026-01-24 14:26:29'),
(209, 1, '', 'IML – Cien Fuego – Santiago', 1, '2026-01-24 14:26:29'),
(210, 1, '', 'IML – Hoya del Caimito – Santiago', 1, '2026-01-24 14:26:29'),
(211, 1, '', 'IML – Los Jardines – Santiago', 1, '2026-01-24 14:26:29'),
(212, 1, '', 'IML – Navarrete – 27 de Febrero', 1, '2026-01-24 14:26:29'),
(213, 3, '', 'IML – Las Cañas', 1, '2026-01-24 14:30:03'),
(214, 3, '', 'IML – Betel', 1, '2026-01-24 14:30:03'),
(215, 3, '', 'IML – Central Altamira', 1, '2026-01-24 14:30:03'),
(216, 3, '', 'IML – Caonao', 1, '2026-01-24 14:30:03'),
(217, 3, '', 'IML – La Jagua', 1, '2026-01-24 14:30:03'),
(218, 3, '', 'IML – Puerto Plata Central', 1, '2026-01-24 14:30:03'),
(219, 3, '', 'IML – Malecón', 1, '2026-01-24 14:30:03'),
(220, 3, '', 'IML – La Isabela', 1, '2026-01-24 14:30:03'),
(221, 3, '', 'IML – Escalereta', 1, '2026-01-24 14:30:03'),
(222, 3, '', 'IML – Fundación', 1, '2026-01-24 14:30:03'),
(223, 3, '', 'IML – La Mariposa', 1, '2026-01-24 14:30:03'),
(224, 3, '', 'IML – Ranchito Los Peralta', 1, '2026-01-24 14:30:03'),
(225, 3, '', 'IML – Navas', 1, '2026-01-24 14:30:03'),
(226, 3, '', 'IML – San Marcos', 1, '2026-01-24 14:30:03'),
(227, 3, '', 'IML – Cabía', 1, '2026-01-24 14:30:03'),
(228, 3, '', 'IML – Rincón', 1, '2026-01-24 14:30:03'),
(229, 3, '', 'IML – Tabernáculo de Adoración', 1, '2026-01-24 14:30:03'),
(230, 3, '', 'IML – Ranchito Los Vargas', 1, '2026-01-24 14:30:03'),
(231, 3, '', 'IML – El Lirial', 1, '2026-01-24 14:30:03'),
(232, 3, '', 'IML – La Balsa', 1, '2026-01-24 14:30:03'),
(233, 3, '', 'IML – Arroyo Dulce', 1, '2026-01-24 14:30:03'),
(234, 3, '', 'IML – Palmarito', 1, '2026-01-24 14:30:03'),
(235, 3, '', 'IML – Ruiseñor', 1, '2026-01-24 14:30:03'),
(236, 3, '', 'IML – El Mamey', 1, '2026-01-24 14:30:03'),
(237, 3, '', 'IML – Luperón', 1, '2026-01-24 14:30:03'),
(238, 3, '', 'IML – Arroyo Blanco', 1, '2026-01-24 14:30:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `iglesias`
--
ALTER TABLE `iglesias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_iglesia_distrito` (`distrito_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `iglesias`
--
ALTER TABLE `iglesias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `iglesias`
--
ALTER TABLE `iglesias`
  ADD CONSTRAINT `fk_iglesia_distrito` FOREIGN KEY (`distrito_id`) REFERENCES `distritos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

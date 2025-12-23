-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-12-2025 a las 16:54:52
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
-- Base de datos: `concilio_multitenencia`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_actualizar_datos_pastor` (IN `p_pastor_id` INT)   BEGIN
  UPDATE pastores 
  SET 
    edad = TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()),
    anos_servicio = TIMESTAMPDIFF(YEAR, fecha_ingreso_ministerio, CURDATE())
  WHERE id = p_pastor_id OR p_pastor_id IS NULL;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas_ministeriales`
--

CREATE TABLE `areas_ministeriales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('general','personalizado') DEFAULT 'general',
  `iglesia_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `areas_ministeriales`
--

INSERT INTO `areas_ministeriales` (`id`, `nombre`, `descripcion`, `tipo`, `iglesia_id`, `activo`, `creado_en`) VALUES
(1, 'Damas', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(2, 'Caballeros', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(3, 'Jóvenes', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(4, 'Jovencitos', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(5, 'Niños (CIC)', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(6, 'Obra Social', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(7, 'Música y Adoración', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(8, 'Misiones', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(9, 'Étnico', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(10, 'Oración', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(11, 'Educación Cristiana', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(12, 'Escuela Bíblica', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(13, 'Encargado de Finanzas', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(14, 'Comité de Disciplina y Ética', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(15, 'Comité de Membresía y Consejo', NULL, 'general', NULL, 1, '2025-12-04 00:26:04'),
(16, 'Evangelismo', 'Ministerio de evangelismo e iglecrecimiento', 'general', NULL, 1, '2025-12-04 00:34:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `area_lideres`
--

CREATE TABLE `area_lideres` (
  `id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `miembro_id` int(11) NOT NULL,
  `tipo` enum('lider','colider') DEFAULT 'lider',
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `area_lideres`
--

INSERT INTO `area_lideres` (`id`, `iglesia_id`, `area_id`, `periodo_id`, `miembro_id`, `tipo`, `activo`, `creado_en`) VALUES
(1, 40, 3, 1, 8, 'lider', 1, '2025-12-15 02:54:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargos_junta`
--

CREATE TABLE `cargos_junta` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cargos_junta`
--

INSERT INTO `cargos_junta` (`id`, `nombre`, `orden`, `activo`) VALUES
(1, 'Pastor (Presidente)', 1, 1),
(2, 'Delegado', 2, 1),
(3, 'Secretario', 3, 1),
(4, 'Tesorero', 4, 1),
(5, 'Secretario de Finanzas', 5, 1),
(6, 'Miembro', 6, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`id`, `nombre`, `activo`) VALUES
(1, 'Educación', 1),
(2, 'Medicina', 1),
(3, 'Ingeniería', 1),
(4, 'Derecho/Abogacía', 1),
(5, 'Contabilidad', 1),
(6, 'Administración', 1),
(7, 'Enfermería', 1),
(8, 'Psicología', 1),
(9, 'Teología', 1),
(10, 'Informática/Sistemas', 1),
(11, 'Arquitectura', 1),
(12, 'Comunicación Social', 1),
(13, 'Otra', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conferencias`
--

CREATE TABLE `conferencias` (
  `id` int(11) NOT NULL,
  `superintendente_id` int(11) DEFAULT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `superintendente_nombre` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `conferencias`
--

INSERT INTO `conferencias` (`id`, `superintendente_id`, `codigo`, `nombre`, `superintendente_nombre`, `telefono`, `correo`, `activo`, `creado_en`) VALUES
(1, NULL, 'N101', 'Conferencia Noroeste', NULL, NULL, NULL, 1, '2025-12-01 15:04:47'),
(2, 5, 'C102', 'Conferencia Central', NULL, '(809) 841-4514', NULL, 1, '2025-12-01 15:04:47'),
(3, NULL, 'S103', 'Conferencia Sureste', NULL, NULL, NULL, 1, '2025-12-01 15:04:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conferencia_superintendentes_historial`
--

CREATE TABLE `conferencia_superintendentes_historial` (
  `id` int(11) NOT NULL,
  `conferencia_id` int(11) NOT NULL,
  `pastor_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `motivo_fin` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `conferencia_superintendentes_historial`
--

INSERT INTO `conferencia_superintendentes_historial` (`id`, `conferencia_id`, `pastor_id`, `fecha_inicio`, `fecha_fin`, `motivo_fin`, `observaciones`, `creado_en`) VALUES
(1, 2, 5, '2025-08-01', NULL, NULL, NULL, '2025-12-11 10:22:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distritos`
--

CREATE TABLE `distritos` (
  `id` int(11) NOT NULL,
  `conferencia_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `supervisor_nombre` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `distritos`
--

INSERT INTO `distritos` (`id`, `conferencia_id`, `supervisor_id`, `codigo`, `nombre`, `supervisor_nombre`, `telefono`, `correo`, `activo`, `creado_en`) VALUES
(1, 2, NULL, '101', 'Distrito Central', NULL, NULL, NULL, 1, '2025-12-01 15:07:47'),
(2, 2, NULL, '102', 'NorCentral', NULL, NULL, NULL, 1, '2025-12-01 19:11:02'),
(3, 2, 3, '103', 'Distrito Nordeste 1', NULL, NULL, NULL, 1, '2025-12-10 09:43:18'),
(4, 2, NULL, '104', 'Distrito Nordeste 2', NULL, NULL, NULL, 1, '2025-12-10 09:43:50'),
(5, 2, NULL, '105', 'Distrito Noreste', NULL, NULL, NULL, 1, '2025-12-10 10:40:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distrito_supervisores_historial`
--

CREATE TABLE `distrito_supervisores_historial` (
  `id` int(11) NOT NULL,
  `distrito_id` int(11) NOT NULL,
  `pastor_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `motivo_fin` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `distrito_supervisores_historial`
--

INSERT INTO `distrito_supervisores_historial` (`id`, `distrito_id`, `pastor_id`, `fecha_inicio`, `fecha_fin`, `motivo_fin`, `observaciones`, `creado_en`) VALUES
(1, 3, 3, '2025-08-01', NULL, NULL, NULL, '2025-12-11 14:31:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familias`
--

CREATE TABLE `familias` (
  `id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `apellido_familia` varchar(150) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `familias`
--

INSERT INTO `familias` (`id`, `iglesia_id`, `codigo`, `apellido_familia`, `creado_en`) VALUES
(1, 40, 'FAM-001', 'NOLBERTO ENCARNACION', '2025-12-11 18:19:42'),
(2, 40, 'FAM-002', 'FLORIMON VENTURA', '2025-12-15 02:54:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fin_categorias`
--

CREATE TABLE `fin_categorias` (
  `id` int(11) NOT NULL,
  `id_iglesia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('ENTRADA','SALIDA') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fin_categorias`
--

INSERT INTO `fin_categorias` (`id`, `id_iglesia`, `nombre`, `tipo`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 40, 'Ofrendas', 'ENTRADA', 'Ofrenda de los cultos realizados', 1, '2025-12-21 10:30:47'),
(2, 40, 'Diezmo', 'ENTRADA', 'Registro de los Diezmos', 1, '2025-12-21 10:31:13'),
(3, 40, 'Luz', 'SALIDA', '', 1, '2025-12-21 20:53:02'),
(4, 40, '10 % Conferenciar', 'SALIDA', '', 1, '2025-12-22 11:36:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fin_cierres`
--

CREATE TABLE `fin_cierres` (
  `id` int(11) NOT NULL,
  `id_iglesia` int(11) NOT NULL,
  `mes` tinyint(4) NOT NULL,
  `ano` smallint(6) NOT NULL,
  `total_entradas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_salidas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saldo_final` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cerrado` tinyint(1) DEFAULT 0,
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `id_usuario_cierre` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fin_cuentas`
--

CREATE TABLE `fin_cuentas` (
  `id` int(11) NOT NULL,
  `id_iglesia` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `saldo_inicial` decimal(12,2) DEFAULT 0.00,
  `saldo_actual` decimal(12,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fin_cuentas`
--

INSERT INTO `fin_cuentas` (`id`, `id_iglesia`, `codigo`, `nombre`, `descripcion`, `saldo_inicial`, `saldo_actual`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 40, 'FG', 'Fondo General', '', 0.00, 1200.00, 1, '2025-12-17 04:27:13', '2025-12-22 11:38:16'),
(2, 40, 'FC', 'Fondo de Construccion', '', 0.00, 0.00, 1, '2025-12-20 03:06:51', '2025-12-22 11:29:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fin_entradas`
--

CREATE TABLE `fin_entradas` (
  `id` int(11) NOT NULL,
  `id_iglesia` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `id_miembro` int(11) DEFAULT NULL COMMENT 'Miembro de la BD si existe',
  `nombre_manual` varchar(200) DEFAULT NULL COMMENT 'Nombre si no está en BD',
  `fecha` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `forma_pago` enum('EFECTIVO','CHEQUE','TRANSFERENCIA') DEFAULT 'EFECTIVO',
  `descripcion` text DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fin_entradas`
--

INSERT INTO `fin_entradas` (`id`, `id_iglesia`, `id_categoria`, `id_cuenta`, `id_miembro`, `nombre_manual`, `fecha`, `monto`, `forma_pago`, `descripcion`, `id_usuario_registro`, `fecha_registro`) VALUES
(1, 40, 1, 1, NULL, 'Ofrenda del Domingo', '2025-12-21', 1300.00, 'EFECTIVO', '', 3, '2025-12-21 10:32:49'),
(2, 40, 1, 1, NULL, 'Ofrenda del Domingo', '2025-12-21', 1300.00, 'EFECTIVO', '', 3, '2025-12-21 10:38:18'),
(3, 40, 2, 1, 8, '', '2025-12-21', 500.00, 'EFECTIVO', '', 3, '2025-12-21 10:39:28'),
(4, 40, 2, 1, 8, '', '2025-12-21', 500.00, 'EFECTIVO', '', 3, '2025-12-21 10:39:34'),
(6, 40, 2, 1, 8, '', '2025-12-21', 500.00, 'EFECTIVO', '', 3, '2025-12-21 11:05:42'),
(8, 40, 2, 1, 8, '', '2025-12-21', 500.00, 'EFECTIVO', '', 3, '2025-12-21 11:06:00'),
(9, 40, 2, 1, 6, '', '2025-12-21', 600.00, 'EFECTIVO', '', 3, '2025-12-21 11:06:46'),
(11, 40, 2, 1, 6, '', '2025-12-21', 600.00, 'EFECTIVO', '', 3, '2025-12-21 11:39:37'),
(12, 40, 2, 1, 3, '', '2025-12-21', 3800.00, 'EFECTIVO', '', 3, '2025-12-21 21:11:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fin_salidas`
--

CREATE TABLE `fin_salidas` (
  `id` int(11) NOT NULL,
  `id_iglesia` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `beneficiario` varchar(200) NOT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `forma_pago` enum('EFECTIVO','CHEQUE','TRANSFERENCIA') DEFAULT 'EFECTIVO',
  `descripcion` text DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fin_salidas`
--

INSERT INTO `fin_salidas` (`id`, `id_iglesia`, `id_categoria`, `id_cuenta`, `fecha`, `monto`, `beneficiario`, `numero_documento`, `forma_pago`, `descripcion`, `id_usuario_registro`, `fecha_registro`) VALUES
(3, 40, 3, 1, '2025-12-21', 900.00, 'Edenorte', '', 'EFECTIVO', '', 3, '2025-12-21 21:14:02'),
(4, 40, 4, 1, '2025-12-22', 7500.00, 'Conferencias', '', 'EFECTIVO', '', 3, '2025-12-22 11:38:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fin_transferencias`
--

CREATE TABLE `fin_transferencias` (
  `id` int(11) NOT NULL,
  `id_iglesia` int(11) NOT NULL,
  `id_cuenta_origen` int(11) NOT NULL,
  `id_cuenta_destino` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_usuario_registro` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fin_transferencias`
--

INSERT INTO `fin_transferencias` (`id`, `id_iglesia`, `id_cuenta_origen`, `id_cuenta_destino`, `fecha`, `monto`, `descripcion`, `id_usuario_registro`, `fecha_registro`) VALUES
(1, 40, 1, 2, '2025-12-21', 600.00, 'Prueba', 3, '2025-12-21 23:45:02'),
(2, 40, 1, 2, '2025-12-21', 600.00, 'Prueba', 3, '2025-12-21 23:45:07'),
(3, 40, 1, 2, '2025-12-21', 600.00, 'Prueba', 3, '2025-12-21 23:57:08'),
(4, 40, 2, 1, '2025-12-22', 1000.00, '', 3, '2025-12-22 11:28:22'),
(5, 40, 2, 1, '2025-12-22', 800.00, '', 3, '2025-12-22 11:29:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iglesias`
--

CREATE TABLE `iglesias` (
  `id` int(11) NOT NULL,
  `distrito_id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` enum('Circuito','Capilla','Proyecto Evangelístico') NOT NULL DEFAULT 'Circuito',
  `direccion` varchar(255) DEFAULT NULL,
  `provincia_id` int(11) DEFAULT NULL,
  `municipio_id` int(11) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `iglesias`
--

INSERT INTO `iglesias` (`id`, `distrito_id`, `codigo`, `nombre`, `categoria`, `direccion`, `provincia_id`, `municipio_id`, `telefono`, `correo`, `activo`, `creado_en`) VALUES
(1, 1, 'IMLC-201', 'IML-San Francisco Central', 'Circuito', 'C/ Rivas #70', 6, 33, NULL, NULL, 1, '2025-12-09 18:00:18'),
(2, 1, 'IMLC-202', 'IML-Castillo', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(3, 1, 'IMLC-203', 'IML-Cotui', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(4, 1, 'IMLC-204', 'IML-El Indio', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(5, 1, 'IMLC-205', 'IML-La Bija', 'Circuito', '', NULL, NULL, NULL, NULL, 0, '2025-12-09 18:00:18'),
(6, 1, 'IMLC-206', 'IML-Piantini', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(7, 1, 'IMLC-207', 'IML-Pimentel', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(8, 1, 'IMLC-208', 'IML-Soledad', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(9, 1, 'IMLC-209', 'IML-Ventura Grullón', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(10, 1, 'IMLC-210', 'IML-Villa Rivas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(11, 1, 'IMLC-211', 'IML-Vista al Valle', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 18:00:18'),
(12, 1, 'IMLC-212', 'IML- La Enea', 'Circuito', 'La Enea', 6, 37, NULL, NULL, 1, '2025-12-10 09:11:32'),
(13, 2, 'IMLC-213', 'IML-La Espinola', 'Circuito', 'La Espinola', 6, 33, NULL, NULL, 1, '2025-12-10 09:14:22'),
(20, 2, 'IMLC-214', 'IML-Bayacanes', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:32:29'),
(21, 2, 'IMLC-215', 'IML-Palmar', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:32:29'),
(22, 2, 'IMLC-216', 'IML-Salcedo', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:32:29'),
(23, 2, 'IMLC-217', 'IML-Moca', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:32:29'),
(24, 2, 'IMLC-218', 'IML-La Vega', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:32:29'),
(25, 2, 'IMLC-219', 'IML-Bonao', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:32:29'),
(26, 3, 'IMLC-220', 'IML-Buenos Aires', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(27, 3, 'IMLC-221', 'IML-La Pichinga', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(28, 3, 'IMLC-222', 'IML-El Factor', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(29, 3, 'IMLC-223', 'IML-Barrio Quisqueyano', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(30, 3, 'IMLC-224', 'IML-Barrio Santísima Trinidad', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(31, 3, 'IMLC-225', 'IML-La Ceja', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(32, 3, 'IMLC-226', 'IML-La Cejita', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(33, 3, 'IMLC-227', 'IML-Los Limones', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(34, 3, 'IMLC-228', 'IML-San José de Villa', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(35, 3, 'IMLC-229', 'IML-El Yayal', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(36, 3, 'IMLC-230', 'IML-Kilometro 3', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(37, 3, 'IMLC-231', 'IML-La Capitalita', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(38, 3, 'IMLC-232', 'IML-Las Quinientas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(39, 3, 'IMLC-233', 'IML-La Travesia', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(40, 3, 'IMLC-234', 'IML-Matancitas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(41, 3, 'IMLC-235', 'IML-Los Yayales', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(42, 3, 'IMLC-236', 'IML-Soldado Arriba', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 09:56:13'),
(43, 4, 'IMLC-240', 'IML-Baoba Pueblo Nuevo', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(44, 4, 'IMLC-241', 'IML-La Piragua', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(45, 4, 'IMLC-242', 'IML-Bella Vista', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(46, 4, 'IMLC-243', 'IML-Boba', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(47, 4, 'IMLC-244', 'IML-El Juncal', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(48, 4, 'IMLC-245', 'IML-Los Naranjos', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(49, 4, 'IMLC-246', 'IML-Los Rincones de Boba', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(50, 4, 'IMLC-247', 'IML-Las Gordas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(51, 4, 'IMLC-248', 'IML-Nagua Central', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(52, 4, 'IMLC-249', 'IML-Telanza', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(53, 4, 'IMLC-250', 'IML-La Entrada', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(54, 4, 'IMLC-251', 'IML-Abreu', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(55, 4, 'IMLC-252', 'IML-Baoba Central', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:12:53'),
(56, 5, 'IMLC-253', 'IML-Arroyo Hondo Robalos', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(57, 5, 'IMLC-254', 'IML-El Limón', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(58, 5, 'IMLC-255', 'IML-El Naranjito – Las Terrenas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(59, 5, 'IMLC-256', 'IML-La Ceiba - Las Terrenas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(60, 5, 'IMLC-257', 'IML-La Majagua', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(61, 5, 'IMLC-258', 'IML-La Pascuala', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(62, 5, 'IMLC-259', 'IML-Las Terrenas', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(63, 5, 'IMLC-260', 'IML-Los Corrales', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(64, 5, 'IMLC-261', 'IML-Los Robalos', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(65, 5, 'IMLC-262', 'IML-Punta Gorda', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(66, 5, 'IMLC-263', 'IML-Samaná', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(67, 5, 'IMLC-264', 'IML-Sánchez', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13'),
(68, 5, 'IMLC-265', 'IML-Catey', 'Circuito', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-10 10:44:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `juntas`
--

CREATE TABLE `juntas` (
  `id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `tipo` enum('5','7') DEFAULT '5',
  `activa` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `juntas`
--

INSERT INTO `juntas` (`id`, `iglesia_id`, `periodo_id`, `tipo`, `activa`, `creado_en`) VALUES
(1, 40, 1, '5', 1, '2025-12-11 00:40:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `junta_miembros`
--

CREATE TABLE `junta_miembros` (
  `id` int(11) NOT NULL,
  `junta_id` int(11) NOT NULL,
  `miembro_id` int(11) NOT NULL,
  `cargo_id` int(11) NOT NULL,
  `es_pastor` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `junta_miembros`
--

INSERT INTO `junta_miembros` (`id`, `junta_id`, `miembro_id`, `cargo_id`, `es_pastor`, `activo`, `creado_en`) VALUES
(1, 1, 1, 1, 0, 0, '2025-12-11 00:40:34'),
(2, 1, 3, 3, 0, 1, '2025-12-11 18:17:53'),
(3, 1, 1, 1, 1, 1, '2025-12-16 02:34:27'),
(4, 1, 10, 6, 0, 1, '2025-12-17 17:52:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembros`
--

CREATE TABLE `miembros` (
  `id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `familia_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `sexo` enum('M','F') NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `nacionalidad_id` int(11) DEFAULT NULL,
  `tipo_documento` enum('cedula','pasaporte') DEFAULT 'cedula',
  `numero_documento` varchar(30) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `estado_civil` enum('soltero','casado','union_libre','divorciado','viudo') DEFAULT 'soltero',
  `nivel_estudio_id` int(11) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `formacion_continuada` text DEFAULT NULL COMMENT 'Maestría, Doctorado, Diplomados, Especialidades',
  `estado_miembro` enum('en_plena','en_preparacion','miembro_menor') DEFAULT 'en_preparacion',
  `ministerio_id` int(11) DEFAULT NULL,
  `es_bautizado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_bautismo` date DEFAULT NULL,
  `es_lider` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('activo','inactivo','fallecido','trasladado') NOT NULL DEFAULT 'activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `miembros`
--

INSERT INTO `miembros` (`id`, `iglesia_id`, `familia_id`, `nombre`, `apellido`, `sexo`, `fecha_nacimiento`, `nacionalidad_id`, `tipo_documento`, `numero_documento`, `telefono`, `direccion`, `foto`, `estado_civil`, `nivel_estudio_id`, `carrera_id`, `formacion_continuada`, `estado_miembro`, `ministerio_id`, `es_bautizado`, `fecha_bautismo`, `es_lider`, `estado`, `creado_en`, `actualizado_en`) VALUES
(1, 40, 1, 'MILTON ABRIAN', 'NOLBERTO DE LA CRUZ', 'M', '1983-02-07', 1, 'cedula', '071-0046738-5', '(809) 391-9509', 'Calle Bertilio Rodríguez #7', 'miembro_1765477417_693b0c29d1316.jpg', 'casado', 6, 1, NULL, 'en_plena', 2, 1, NULL, 1, 'activo', '2025-12-11 00:23:40', '2025-12-11 18:24:36'),
(2, 22, NULL, 'Jean Carlos', 'Valdez', 'M', '1990-01-01', 1, 'cedula', '060-0021508-4', '(809) 250-4244', NULL, NULL, 'casado', 5, NULL, NULL, 'en_plena', NULL, 0, NULL, 1, 'activo', '2025-12-11 01:48:45', '2025-12-11 01:48:45'),
(3, 40, NULL, 'KEREN ESTHER', 'MEREJILDO VASQUEZ', 'M', '1990-01-01', 1, 'cedula', '071-0049631-9', '(829) 540-1036', NULL, NULL, 'soltero', 6, 7, NULL, 'en_plena', 1, 1, NULL, 1, 'activo', '2025-12-11 08:45:51', '2025-12-22 15:17:14'),
(4, 36, NULL, 'DAVID LEONEL', 'LOPEZ NOLBERTO', 'M', '1979-08-31', 1, 'cedula', '071-0036553-0', '(809) 665-8257', NULL, NULL, 'casado', 6, 5, NULL, 'en_plena', 2, 0, NULL, 1, 'activo', '2025-12-11 09:25:09', '2025-12-13 09:22:49'),
(5, 23, NULL, 'Miguel Ángel', 'Alcantara Marte', 'M', '1977-01-01', 1, 'cedula', '048-0070759-0', '(809) 449-0735', NULL, NULL, 'casado', 6, 9, NULL, 'en_plena', NULL, 0, NULL, 1, 'activo', '2025-12-11 10:16:16', '2025-12-11 10:16:16'),
(6, 40, 1, 'RAFAELITA', 'ENCARNACION VICENTE', 'F', '1988-12-29', 1, 'cedula', '229-0006588-3', '809-670-7988', 'Calle Bertilio Rodríguez #7', 'miembro_1765477302_693b0bb63adc4.jpg', 'soltero', 6, 1, NULL, 'en_plena', 1, 1, NULL, 1, 'activo', '2025-12-11 18:21:42', '2025-12-19 12:36:18'),
(7, 9, NULL, 'DARLING', 'JAVIER KERY', 'M', '1999-01-01', 1, 'cedula', '402-2664144-3', '(809) 849-1883', NULL, NULL, 'soltero', 6, 8, NULL, 'en_plena', 3, 0, NULL, 1, 'activo', '2025-12-12 09:44:04', '2025-12-19 12:11:15'),
(8, 40, 2, 'ENGER', 'FLORIMON', 'M', '1998-01-01', 1, 'cedula', '402-1923018-8', '809-376-1649', NULL, NULL, 'soltero', 3, NULL, NULL, 'en_plena', 3, 1, NULL, 1, 'activo', '2025-12-15 02:54:09', '2025-12-15 02:54:09'),
(9, 37, NULL, 'Keyra Nicool', 'Nolberto De La Cruz', 'F', '1987-10-01', 1, 'cedula', '071-0047938-0', '(829) 388-1194', NULL, NULL, 'casado', NULL, NULL, NULL, 'en_plena', NULL, 0, NULL, 1, 'activo', '2025-12-15 03:07:52', '2025-12-15 03:07:52'),
(10, 40, NULL, 'CRISTINA', 'CARDENAS', 'F', '1970-01-01', 1, 'cedula', NULL, NULL, NULL, NULL, 'soltero', NULL, NULL, NULL, 'en_plena', 1, 1, NULL, 1, 'activo', '2025-12-17 17:51:01', '2025-12-17 17:54:05'),
(11, 40, NULL, 'MARIBEL', 'RAPOSO VENTURA', 'F', '1990-01-01', 1, 'cedula', '071-1544444-4', NULL, NULL, NULL, 'soltero', NULL, NULL, NULL, 'en_plena', 3, 0, NULL, 0, 'activo', '2025-12-19 12:07:26', '2025-12-19 12:15:48'),
(12, 40, NULL, 'JORDANY', 'MERCEDES', 'M', '2003-01-01', 1, 'cedula', '074-1000114-6', NULL, NULL, NULL, 'soltero', NULL, NULL, NULL, 'en_plena', 3, 0, NULL, 0, 'activo', '2025-12-19 12:14:56', '2025-12-19 12:14:56'),
(13, 40, 1, 'ABRAHAM', 'NOLBERTO DE LA CRUZ', 'M', '2013-08-31', 1, 'cedula', NULL, NULL, NULL, NULL, 'soltero', 3, NULL, NULL, 'miembro_menor', 4, 0, NULL, 0, 'activo', '2025-12-19 12:46:11', '2025-12-19 12:59:56'),
(14, 40, 1, 'ABRIAN', 'NOLBERTO ENCARNACION', 'M', '2015-02-07', 1, 'cedula', NULL, NULL, NULL, NULL, 'soltero', 2, NULL, NULL, 'miembro_menor', 4, 0, NULL, 0, 'activo', '2025-12-22 11:11:30', '2025-12-22 15:54:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ministerios`
--

CREATE TABLE `ministerios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ministerios`
--

INSERT INTO `ministerios` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Damas', 'Ministerio de mujeres / damas', 1),
(2, 'Caballeros', 'Ministerio de hombres / caballeros', 1),
(3, 'Jóvenes', 'Ministerio de jóvenes', 1),
(4, 'Niños', 'Ministerio de niños', 1),
(5, 'Adolescentes', 'Ministerio de Adolescentes', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ministerio_lideres_conferencia`
--

CREATE TABLE `ministerio_lideres_conferencia` (
  `id` int(11) NOT NULL,
  `conferencia_id` int(11) NOT NULL,
  `ministerio_id` int(11) NOT NULL,
  `miembro_id` int(11) NOT NULL,
  `cargo` enum('presidente','vicepresidente','secretario','tesorero','vocal') NOT NULL DEFAULT 'presidente',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `periodo_conferencia` varchar(20) DEFAULT NULL COMMENT 'Ej: 2024-2027',
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ministerio_lideres_conferencia`
--

INSERT INTO `ministerio_lideres_conferencia` (`id`, `conferencia_id`, `ministerio_id`, `miembro_id`, `cargo`, `fecha_inicio`, `fecha_fin`, `periodo_conferencia`, `observaciones`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 2, 3, 7, 'presidente', '2025-08-01', NULL, '2025-2028', '', 1, '2025-12-12 09:45:02', '2025-12-12 09:45:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ministerio_lideres_conferencia_historial`
--

CREATE TABLE `ministerio_lideres_conferencia_historial` (
  `id` int(11) NOT NULL,
  `conferencia_id` int(11) NOT NULL,
  `ministerio_id` int(11) NOT NULL,
  `miembro_id` int(11) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `periodo_conferencia` varchar(20) DEFAULT NULL,
  `motivo_fin` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipios`
--

CREATE TABLE `municipios` (
  `id` int(11) NOT NULL,
  `provincia_id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `municipios`
--

INSERT INTO `municipios` (`id`, `provincia_id`, `nombre`, `activo`) VALUES
(1, 1, 'Distrito Nacional', 1),
(2, 2, 'Azua', 1),
(3, 2, 'Estebanía', 1),
(4, 2, 'Guayabal', 1),
(5, 2, 'Las Charcas', 1),
(6, 2, 'Las Yayas de Viajama', 1),
(7, 2, 'Padre Las Casas', 1),
(8, 2, 'Peralta', 1),
(9, 2, 'Pueblo Viejo', 1),
(10, 2, 'Sabana Yegua', 1),
(11, 2, 'Tábara Arriba', 1),
(12, 3, 'Neiba', 1),
(13, 3, 'Galván', 1),
(14, 3, 'Los Ríos', 1),
(15, 3, 'Tamayo', 1),
(16, 3, 'Villa Jaragua', 1),
(17, 4, 'Barahona', 1),
(18, 4, 'Cabral', 1),
(19, 4, 'El Peñón', 1),
(20, 4, 'Enriquillo', 1),
(21, 4, 'Fundación', 1),
(22, 4, 'Jaquimeyes', 1),
(23, 4, 'La Ciénaga', 1),
(24, 4, 'Las Salinas', 1),
(25, 4, 'Paraíso', 1),
(26, 4, 'Polo', 1),
(27, 4, 'Vicente Noble', 1),
(28, 5, 'Dajabón', 1),
(29, 5, 'El Pino', 1),
(30, 5, 'Loma de Cabrera', 1),
(31, 5, 'Partido', 1),
(32, 5, 'Restauración', 1),
(33, 6, 'San Francisco de Macorís', 1),
(34, 6, 'Arenoso', 1),
(35, 6, 'Castillo', 1),
(36, 6, 'Eugenio María de Hostos', 1),
(37, 6, 'Las Guáranas', 1),
(38, 6, 'Pimentel', 1),
(39, 6, 'Villa Riva', 1),
(40, 7, 'Comendador', 1),
(41, 7, 'Bánica', 1),
(42, 7, 'El Llano', 1),
(43, 7, 'Hondo Valle', 1),
(44, 7, 'Juan Santiago', 1),
(45, 7, 'Pedro Santana', 1),
(46, 8, 'El Seibo', 1),
(47, 8, 'Miches', 1),
(48, 9, 'Moca', 1),
(49, 9, 'Cayetano Germosén', 1),
(50, 9, 'Gaspar Hernández', 1),
(51, 9, 'Jamao al Norte', 1),
(52, 10, 'Hato Mayor del Rey', 1),
(53, 10, 'El Valle', 1),
(54, 10, 'Sabana de la Mar', 1),
(55, 11, 'Salcedo', 1),
(56, 11, 'Tenares', 1),
(57, 11, 'Villa Tapia', 1),
(58, 12, 'Jimaní', 1),
(59, 12, 'Cristóbal', 1),
(60, 12, 'Duvergé', 1),
(61, 12, 'La Descubierta', 1),
(62, 12, 'Mella', 1),
(63, 12, 'Postrer Río', 1),
(64, 13, 'Higüey', 1),
(65, 13, 'San Rafael del Yuma', 1),
(66, 14, 'La Romana', 1),
(67, 14, 'Guaymate', 1),
(68, 14, 'Villa Hermosa', 1),
(69, 15, 'La Vega', 1),
(70, 15, 'Constanza', 1),
(71, 15, 'Jarabacoa', 1),
(72, 15, 'Jima Abajo', 1),
(73, 16, 'Nagua', 1),
(74, 16, 'Cabrera', 1),
(75, 16, 'El Factor', 1),
(76, 16, 'Río San Juan', 1),
(77, 17, 'Bonao', 1),
(78, 17, 'Maimón', 1),
(79, 17, 'Piedra Blanca', 1),
(80, 18, 'Monte Cristi', 1),
(81, 18, 'Castañuela', 1),
(82, 18, 'Guayubín', 1),
(83, 18, 'Las Matas de Santa Cruz', 1),
(84, 18, 'Pepillo Salcedo', 1),
(85, 18, 'Villa Vásquez', 1),
(86, 19, 'Monte Plata', 1),
(87, 19, 'Bayaguana', 1),
(88, 19, 'Peralvillo', 1),
(89, 19, 'Sabana Grande de Boyá', 1),
(90, 19, 'Yamasá', 1),
(91, 20, 'Pedernales', 1),
(92, 20, 'Oviedo', 1),
(93, 21, 'Baní', 1),
(94, 21, 'Nizao', 1),
(95, 21, 'Matanzas', 1),
(96, 22, 'Puerto Plata', 1),
(97, 22, 'Altamira', 1),
(98, 22, 'Guananico', 1),
(99, 22, 'Imbert', 1),
(100, 22, 'Los Hidalgos', 1),
(101, 22, 'Luperón', 1),
(102, 22, 'Sosúa', 1),
(103, 22, 'Villa Isabela', 1),
(104, 22, 'Villa Montellano', 1),
(105, 23, 'Samaná', 1),
(106, 23, 'Las Terrenas', 1),
(107, 23, 'Sánchez', 1),
(108, 24, 'San Cristóbal', 1),
(109, 24, 'Bajos de Haina', 1),
(110, 24, 'Cambita Garabitos', 1),
(111, 24, 'Los Cacaos', 1),
(112, 24, 'Sabana Grande de Palenque', 1),
(113, 24, 'San Gregorio de Nigua', 1),
(114, 24, 'Villa Altagracia', 1),
(115, 25, 'San José de Ocoa', 1),
(116, 25, 'Rancho Arriba', 1),
(117, 25, 'Sabana Larga', 1),
(118, 26, 'San Juan de la Maguana', 1),
(119, 26, 'Bohechío', 1),
(120, 26, 'El Cercado', 1),
(121, 26, 'Juan de Herrera', 1),
(122, 26, 'Las Matas de Farfán', 1),
(123, 26, 'Vallejuelo', 1),
(124, 27, 'San Pedro de Macorís', 1),
(125, 27, 'Consuelo', 1),
(126, 27, 'Guayacanes', 1),
(127, 27, 'Quisqueya', 1),
(128, 27, 'Ramón Santana', 1),
(129, 27, 'San José de los Llanos', 1),
(130, 28, 'Cotuí', 1),
(131, 28, 'Cevicos', 1),
(132, 28, 'Fantino', 1),
(133, 28, 'La Mata', 1),
(134, 29, 'Santiago', 1),
(135, 29, 'Bisonó', 1),
(136, 29, 'Jánico', 1),
(137, 29, 'Licey al Medio', 1),
(138, 29, 'Puñal', 1),
(139, 29, 'Sabana Iglesia', 1),
(140, 29, 'San José de las Matas', 1),
(141, 29, 'Tamboril', 1),
(142, 29, 'Villa González', 1),
(143, 30, 'San Ignacio de Sabaneta', 1),
(144, 30, 'Monción', 1),
(145, 30, 'Villa Los Almácigos', 1),
(146, 31, 'Santo Domingo Este', 1),
(147, 31, 'Santo Domingo Norte', 1),
(148, 31, 'Santo Domingo Oeste', 1),
(149, 31, 'Boca Chica', 1),
(150, 31, 'San Antonio de Guerra', 1),
(151, 31, 'Pedro Brand', 1),
(152, 31, 'Los Alcarrizos', 1),
(153, 32, 'Mao', 1),
(154, 32, 'Esperanza', 1),
(155, 32, 'Laguna Salada', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nacionalidades`
--

CREATE TABLE `nacionalidades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nacionalidades`
--

INSERT INTO `nacionalidades` (`id`, `nombre`, `activo`) VALUES
(1, 'Dominicana', 1),
(2, 'Haitiana', 1),
(3, 'Venezolana', 1),
(4, 'Colombiana', 1),
(5, 'Estadounidense', 1),
(6, 'Puerto Rico', 1),
(7, 'Otra', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niveles_estudio`
--

CREATE TABLE `niveles_estudio` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `requiere_carrera` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `niveles_estudio`
--

INSERT INTO `niveles_estudio` (`id`, `nombre`, `requiere_carrera`, `activo`) VALUES
(1, 'Sin estudios', 0, 1),
(2, 'Primaria', 0, 1),
(3, 'Secundaria', 0, 1),
(4, 'Técnico / Técnico Profesional', 0, 1),
(5, 'Estudiante Universitario', 1, 1),
(6, 'Profesional', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pastores`
--

CREATE TABLE `pastores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `tipo_documento` enum('Cédula','Pasaporte') NOT NULL DEFAULT 'Cédula',
  `cedula` varchar(20) NOT NULL,
  `pasaporte` varchar(30) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `edad` int(3) DEFAULT NULL COMMENT 'Calculado automáticamente',
  `nacionalidad_id` int(11) DEFAULT NULL,
  `estado_civil` enum('Soltero','Casado','Divorciado','Viudo') DEFAULT 'Soltero',
  `sexo` enum('M','F') NOT NULL DEFAULT 'M',
  `foto` varchar(255) DEFAULT NULL,
  `nivel_estudio_id` int(11) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `fecha_ingreso_ministerio` date NOT NULL,
  `anos_servicio` int(3) DEFAULT NULL COMMENT 'Calculado automáticamente',
  `orden_ministerial` enum('Candidato Ministerial','Diácono','Presbítero') DEFAULT 'Candidato Ministerial',
  `formacion_continuada` text DEFAULT NULL COMMENT 'Maestría, Doctorado, Diplomados, Especialidades',
  `conferencia_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pastores`
--

INSERT INTO `pastores` (`id`, `nombre`, `apellido`, `tipo_documento`, `cedula`, `pasaporte`, `telefono`, `fecha_nacimiento`, `edad`, `nacionalidad_id`, `estado_civil`, `sexo`, `foto`, `nivel_estudio_id`, `carrera_id`, `fecha_ingreso_ministerio`, `anos_servicio`, `orden_ministerial`, `formacion_continuada`, `conferencia_id`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'Milton Abrian', 'Nolberto De La Cruz', 'Cédula', '071-0046738-5', NULL, '(809) 391-9509', '1983-02-07', 42, 1, 'Casado', 'M', NULL, NULL, NULL, '2012-07-01', 13, 'Presbítero', NULL, 2, 1, '2025-12-11 00:07:14', '2025-12-11 00:07:14'),
(2, 'Jean Carlos', 'Valdez', 'Cédula', '060-0021508-4', NULL, '(809) 250-4244', '1990-01-01', 35, 1, 'Casado', 'M', NULL, 5, NULL, '2009-02-01', 16, 'Presbítero', NULL, 2, 1, '2025-12-11 01:47:44', '2025-12-11 01:47:44'),
(3, 'David Leonel', 'Lopez Nolberto', 'Cédula', '071-0036553-0', NULL, '(809) 665-8257', '1979-08-31', 46, 1, 'Casado', 'M', NULL, 6, 5, '2004-07-01', 21, 'Presbítero', NULL, 2, 1, '2025-12-11 09:24:13', '2025-12-11 09:24:13'),
(4, 'Miguel Ángel', 'Alcantara Marte', 'Cédula', '048-0070759-0', NULL, '(809) 449-0735', '1977-01-01', 48, 1, 'Casado', 'M', NULL, 6, 9, '2007-07-01', 18, 'Presbítero', NULL, 2, 1, '2025-12-11 10:15:08', '2025-12-11 10:15:08'),
(5, 'Isaac', 'Nuñez Martinez', 'Cédula', '060-0004226-4', NULL, '(809) 841-4514', '1965-01-01', 60, 1, 'Casado', 'M', NULL, NULL, NULL, '1990-01-01', 35, 'Presbítero', NULL, 2, 1, '2025-12-11 10:20:42', '2025-12-11 10:21:27'),
(6, 'Darling', 'Javier Kery', 'Cédula', '402-2664144-3', NULL, '(809) 849-1883', '1999-01-01', 26, 1, 'Soltero', 'M', NULL, 6, 8, '2020-01-01', 5, 'Diácono', NULL, 2, 1, '2025-12-12 09:40:02', '2025-12-12 09:40:02'),
(7, 'Keyra Nicool', 'Nolberto De La Cruz', 'Cédula', '071-0047938-0', NULL, '(829) 388-1194', '1987-10-01', 38, 1, 'Casado', 'F', NULL, NULL, NULL, '2009-07-01', 16, 'Diácono', NULL, 2, 1, '2025-12-15 03:01:33', '2025-12-15 03:01:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pastor_historial_asignaciones`
--

CREATE TABLE `pastor_historial_asignaciones` (
  `id` int(11) NOT NULL,
  `pastor_id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `motivo_fin` varchar(255) DEFAULT NULL COMMENT 'Traslado, Renuncia, Jubilación, etc.',
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pastor_historial_asignaciones`
--

INSERT INTO `pastor_historial_asignaciones` (`id`, `pastor_id`, `iglesia_id`, `fecha_inicio`, `fecha_fin`, `motivo_fin`, `observaciones`, `creado_en`) VALUES
(1, 1, 40, '2025-07-01', NULL, NULL, NULL, '2025-12-11 00:23:40'),
(2, 2, 22, '2025-08-01', NULL, NULL, NULL, '2025-12-11 01:48:45'),
(3, 3, 36, '2025-07-01', NULL, NULL, NULL, '2025-12-11 09:25:09'),
(4, 4, 23, '2025-08-01', NULL, NULL, NULL, '2025-12-11 10:16:16'),
(5, 6, 9, '2025-08-01', NULL, NULL, NULL, '2025-12-12 09:44:04'),
(6, 7, 37, '2025-08-01', NULL, NULL, NULL, '2025-12-15 03:07:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pastor_iglesias`
--

CREATE TABLE `pastor_iglesias` (
  `id` int(11) NOT NULL,
  `pastor_id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL si está activo',
  `es_principal` tinyint(1) DEFAULT 1 COMMENT '1=Iglesia principal, 0=Secundaria',
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pastor_iglesias`
--

INSERT INTO `pastor_iglesias` (`id`, `pastor_id`, `iglesia_id`, `fecha_asignacion`, `fecha_fin`, `es_principal`, `activo`, `creado_en`) VALUES
(1, 1, 40, '2025-07-01', NULL, 1, 1, '2025-12-11 00:23:40'),
(2, 2, 22, '2025-08-01', NULL, 1, 1, '2025-12-11 01:48:45'),
(3, 3, 36, '2025-07-01', NULL, 1, 1, '2025-12-11 09:25:09'),
(4, 4, 23, '2025-08-01', NULL, 1, 1, '2025-12-11 10:16:16'),
(5, 6, 9, '2025-08-01', NULL, 1, 1, '2025-12-12 09:44:04'),
(6, 7, 37, '2025-08-01', NULL, 1, 1, '2025-12-15 03:07:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pastor_ministerios_conferenciales`
--

CREATE TABLE `pastor_ministerios_conferenciales` (
  `id` int(11) NOT NULL,
  `pastor_id` int(11) NOT NULL,
  `area_ministerial_id` int(11) NOT NULL,
  `conferencia_id` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos_iglesia`
--

CREATE TABLE `periodos_iglesia` (
  `id` int(11) NOT NULL,
  `iglesia_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodos_iglesia`
--

INSERT INTO `periodos_iglesia` (`id`, `iglesia_id`, `nombre`, `fecha_inicio`, `fecha_fin`, `activo`, `creado_en`) VALUES
(1, 40, 'PERIODO 2025-2028', '2025-08-01', '2028-08-01', 1, '2025-12-11 00:40:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincias`
--

CREATE TABLE `provincias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `provincias`
--

INSERT INTO `provincias` (`id`, `nombre`, `activo`) VALUES
(1, 'Distrito Nacional', 1),
(2, 'Azua', 1),
(3, 'Bahoruco', 1),
(4, 'Barahona', 1),
(5, 'Dajabón', 1),
(6, 'Duarte', 1),
(7, 'Elías Piña', 1),
(8, 'El Seibo', 1),
(9, 'Espaillat', 1),
(10, 'Hato Mayor', 1),
(11, 'Hermanas Mirabal', 1),
(12, 'Independencia', 1),
(13, 'La Altagracia', 1),
(14, 'La Romana', 1),
(15, 'La Vega', 1),
(16, 'María Trinidad Sánchez', 1),
(17, 'Monseñor Nouel', 1),
(18, 'Monte Cristi', 1),
(19, 'Monte Plata', 1),
(20, 'Pedernales', 1),
(21, 'Peravia', 1),
(22, 'Puerto Plata', 1),
(23, 'Samaná', 1),
(24, 'San Cristóbal', 1),
(25, 'San José de Ocoa', 1),
(26, 'San Juan', 1),
(27, 'San Pedro de Macorís', 1),
(28, 'Sánchez Ramírez', 1),
(29, 'Santiago', 1),
(30, 'Santiago Rodríguez', 1),
(31, 'Santo Domingo', 1),
(32, 'Valverde', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'super_admin', 'Ve todo el sistema, todas las conferencias y finanzas'),
(2, 'obispo', 'Obispo general, ve estadísticas globales de las 3 conferencias'),
(3, 'super_conferencia', 'Superintendente de conferencia, ve su conferencia'),
(4, 'super_distrito', 'Supervisor de distrito'),
(5, 'pastor', 'Pastor local'),
(6, 'secretaria', 'Secretaria local'),
(7, 'tesorero', 'Tesorero local'),
(8, 'lider_ministerio', 'Líder de ministerio (damas, jóvenes, etc.)');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `conferencia_id` int(11) DEFAULT NULL,
  `distrito_id` int(11) DEFAULT NULL,
  `iglesia_id` int(11) DEFAULT NULL,
  `ministerio_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `usuario`, `clave`, `correo`, `rol_id`, `conferencia_id`, `distrito_id`, `iglesia_id`, `ministerio_id`, `activo`, `creado_en`) VALUES
(1, 'Super', 'Administrador', 'superadmin', '$2y$10$uRG9kmiW.G2OVBDIPhu8h.CUuQYdvxp3cE4PvX2pMPA6cYNFtoJgm', '', 1, NULL, NULL, NULL, NULL, 1, '2025-11-30 03:10:59'),
(2, 'NombreObispo', 'ApellidosObispo', 'obispo', '$2y$10$FCE.38MShowWQUpQnhymtuKHx0neKNcp8HCKD3LTovfuGAfKlwFeS', '', 2, NULL, NULL, NULL, NULL, 1, '2025-11-30 03:10:59'),
(3, 'Milton Abrian', 'Nolberto De La Cruz', '071-0046738-5', '$2y$10$yCPcnJ9b/UPpQzc.HxQVbukeq7B4yYgGe8GVEjgfkAGao8SZknQi.', '', 5, 2, 3, 40, NULL, 1, '2025-12-11 00:23:40'),
(4, 'Jean Carlos', 'Valdez Mena', '060-0021508-4', '$2y$10$Y2eYNcwg7o7lUgew.BlnHObzOZQWthSyQPWP1EzavSbZKKzk1vZqW', '', 5, 2, 2, 22, NULL, 1, '2025-12-11 01:48:45'),
(6, 'Keren Esther', 'Merejildo Vasquez', '071-0049631-9', '$2y$10$xaPmX0t1E3Nz1y1Pv92gTeOr0mbJ5adsmoIendjufyajyfHXRL0Zu', NULL, 6, 2, 3, 40, NULL, 1, '2025-12-11 08:45:51'),
(7, 'David Leonel', 'Lopez Nolberto', '071-0036553-0', '$2y$10$rq3tvgCGPCWJRQByDt7x5eGew9zitn3ywCSwwyTrpqKPjyiMDS/dW', '', 5, 2, 3, 36, NULL, 1, '2025-12-11 09:25:09'),
(8, 'Miguel Ángel', 'Alcantara Marte', '048-0070759-0', '$2y$10$0YaLnIPjWvvnJYQyJ4Xp1.tbuKp04wElQGDc.kbp6ndnJJp2UWQn.', '', 5, 2, 2, 23, NULL, 1, '2025-12-11 10:16:16'),
(9, 'Isaac', 'Nuñez Martinez', '060-0004226-4', '$2y$10$r.hik6d3jnd0YwFrFQiA6uIGU/b/H8N08SfKRxLDZAvSp52H5B2FO', '', 3, 2, NULL, NULL, NULL, 1, '2025-12-11 10:22:08'),
(11, 'Darling', 'Javier Kery', '402-2664144-3', '$2y$10$8.ix3F/XmlQv0nMCHvpcq.kweN21q1PaXYVHZ6cTpzatmhFTINeRa', '', 5, 2, 1, 9, NULL, 1, '2025-12-12 09:44:04'),
(12, 'Keyra Nicool', 'Nolberto De La Cruz', '071-0047938-0', '$2y$10$kRRwV/RKgJYiXVkm12FCw.HCTXPC1jhB2tEaJZvdRkAdG.XD1CiW6', '', 5, 2, 3, 37, NULL, 1, '2025-12-15 03:07:52');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_conferencias_completo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_conferencias_completo` (
`id` int(11)
,`codigo` varchar(20)
,`nombre` varchar(150)
,`superintendente_id` int(11)
,`telefono` varchar(30)
,`correo` varchar(150)
,`activo` tinyint(1)
,`creado_en` timestamp
,`superintendente_nombre` varchar(100)
,`superintendente_apellido` varchar(100)
,`superintendente_completo` varchar(201)
,`superintendente_cedula` varchar(20)
,`superintendente_telefono` varchar(20)
,`orden_ministerial` enum('Candidato Ministerial','Diácono','Presbítero')
,`total_distritos` bigint(21)
,`total_iglesias` bigint(21)
,`total_pastores` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_distritos_completo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_distritos_completo` (
`id` int(11)
,`conferencia_id` int(11)
,`codigo` varchar(20)
,`nombre` varchar(150)
,`supervisor_id` int(11)
,`telefono` varchar(30)
,`correo` varchar(150)
,`activo` tinyint(1)
,`creado_en` timestamp
,`conferencia_codigo` varchar(20)
,`conferencia_nombre` varchar(150)
,`supervisor_nombre` varchar(100)
,`supervisor_apellido` varchar(100)
,`supervisor_completo` varchar(201)
,`supervisor_cedula` varchar(20)
,`supervisor_telefono` varchar(20)
,`orden_ministerial` enum('Candidato Ministerial','Diácono','Presbítero')
,`total_iglesias` bigint(21)
,`total_pastores` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_lideres_ministerio_conferencia`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_lideres_ministerio_conferencia` (
`id` int(11)
,`conferencia_id` int(11)
,`conferencia_nombre` varchar(150)
,`conferencia_codigo` varchar(20)
,`ministerio_id` int(11)
,`ministerio_nombre` varchar(100)
,`miembro_id` int(11)
,`lider_nombre` varchar(201)
,`lider_cedula` varchar(30)
,`lider_telefono` varchar(30)
,`iglesia_origen` varchar(150)
,`iglesia_id` int(11)
,`distrito_nombre` varchar(150)
,`cargo` enum('presidente','vicepresidente','secretario','tesorero','vocal')
,`fecha_inicio` date
,`fecha_fin` date
,`periodo_conferencia` varchar(20)
,`activo` tinyint(1)
,`creado_en` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_pastores_completo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_pastores_completo` (
`id` int(11)
,`nombre` varchar(100)
,`apellido` varchar(100)
,`nombre_completo` varchar(201)
,`tipo_documento` enum('Cédula','Pasaporte')
,`cedula` varchar(20)
,`pasaporte` varchar(30)
,`telefono` varchar(20)
,`fecha_nacimiento` date
,`edad` int(3)
,`nacionalidad_id` int(11)
,`nacionalidad` varchar(100)
,`estado_civil` enum('Soltero','Casado','Divorciado','Viudo')
,`sexo` enum('M','F')
,`foto` varchar(255)
,`nivel_estudio_id` int(11)
,`nivel_estudio` varchar(100)
,`carrera_id` int(11)
,`carrera` varchar(100)
,`formacion_continuada` text
,`fecha_ingreso_ministerio` date
,`anos_servicio` int(3)
,`orden_ministerial` enum('Candidato Ministerial','Diácono','Presbítero')
,`conferencia_id` int(11)
,`conferencia` varchar(150)
,`activo` tinyint(1)
,`creado_en` timestamp
,`iglesia_principal` varchar(150)
,`cantidad_iglesias` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_conferencias_completo`
--
DROP TABLE IF EXISTS `v_conferencias_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`renovaciondocent`@`localhost` SQL SECURITY INVOKER VIEW `v_conferencias_completo`  AS SELECT `c`.`id` AS `id`, `c`.`codigo` AS `codigo`, `c`.`nombre` AS `nombre`, `c`.`superintendente_id` AS `superintendente_id`, `c`.`telefono` AS `telefono`, `c`.`correo` AS `correo`, `c`.`activo` AS `activo`, `c`.`creado_en` AS `creado_en`, `p`.`nombre` AS `superintendente_nombre`, `p`.`apellido` AS `superintendente_apellido`, concat(`p`.`nombre`,' ',`p`.`apellido`) AS `superintendente_completo`, `p`.`cedula` AS `superintendente_cedula`, `p`.`telefono` AS `superintendente_telefono`, `p`.`orden_ministerial` AS `orden_ministerial`, (select count(0) from `distritos` `d` where `d`.`conferencia_id` = `c`.`id` and `d`.`activo` = 1) AS `total_distritos`, (select count(0) from (`iglesias` `i` join `distritos` `d` on(`i`.`distrito_id` = `d`.`id`)) where `d`.`conferencia_id` = `c`.`id` and `i`.`activo` = 1) AS `total_iglesias`, (select count(0) from `pastores` `pas` where `pas`.`conferencia_id` = `c`.`id`) AS `total_pastores` FROM (`conferencias` `c` left join `pastores` `p` on(`c`.`superintendente_id` = `p`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_distritos_completo`
--
DROP TABLE IF EXISTS `v_distritos_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`renovaciondocent`@`localhost` SQL SECURITY INVOKER VIEW `v_distritos_completo`  AS SELECT `d`.`id` AS `id`, `d`.`conferencia_id` AS `conferencia_id`, `d`.`codigo` AS `codigo`, `d`.`nombre` AS `nombre`, `d`.`supervisor_id` AS `supervisor_id`, `d`.`telefono` AS `telefono`, `d`.`correo` AS `correo`, `d`.`activo` AS `activo`, `d`.`creado_en` AS `creado_en`, `c`.`codigo` AS `conferencia_codigo`, `c`.`nombre` AS `conferencia_nombre`, `p`.`nombre` AS `supervisor_nombre`, `p`.`apellido` AS `supervisor_apellido`, concat(`p`.`nombre`,' ',`p`.`apellido`) AS `supervisor_completo`, `p`.`cedula` AS `supervisor_cedula`, `p`.`telefono` AS `supervisor_telefono`, `p`.`orden_ministerial` AS `orden_ministerial`, (select count(0) from `iglesias` `i` where `i`.`distrito_id` = `d`.`id` and `i`.`activo` = 1) AS `total_iglesias`, (select count(0) from ((`pastores` `pas` join `pastor_iglesias` `pi` on(`pas`.`id` = `pi`.`pastor_id`)) join `iglesias` `ig` on(`pi`.`iglesia_id` = `ig`.`id`)) where `ig`.`distrito_id` = `d`.`id` and `pi`.`activo` = 1) AS `total_pastores` FROM ((`distritos` `d` join `conferencias` `c` on(`d`.`conferencia_id` = `c`.`id`)) left join `pastores` `p` on(`d`.`supervisor_id` = `p`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_lideres_ministerio_conferencia`
--
DROP TABLE IF EXISTS `v_lideres_ministerio_conferencia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`renovaciondocent`@`localhost` SQL SECURITY INVOKER VIEW `v_lideres_ministerio_conferencia`  AS SELECT `mlc`.`id` AS `id`, `mlc`.`conferencia_id` AS `conferencia_id`, `c`.`nombre` AS `conferencia_nombre`, `c`.`codigo` AS `conferencia_codigo`, `mlc`.`ministerio_id` AS `ministerio_id`, `m`.`nombre` AS `ministerio_nombre`, `mlc`.`miembro_id` AS `miembro_id`, concat(`mb`.`nombre`,' ',`mb`.`apellido`) AS `lider_nombre`, `mb`.`numero_documento` AS `lider_cedula`, `mb`.`telefono` AS `lider_telefono`, `i`.`nombre` AS `iglesia_origen`, `i`.`id` AS `iglesia_id`, `d`.`nombre` AS `distrito_nombre`, `mlc`.`cargo` AS `cargo`, `mlc`.`fecha_inicio` AS `fecha_inicio`, `mlc`.`fecha_fin` AS `fecha_fin`, `mlc`.`periodo_conferencia` AS `periodo_conferencia`, `mlc`.`activo` AS `activo`, `mlc`.`creado_en` AS `creado_en` FROM (((((`ministerio_lideres_conferencia` `mlc` join `conferencias` `c` on(`mlc`.`conferencia_id` = `c`.`id`)) join `ministerios` `m` on(`mlc`.`ministerio_id` = `m`.`id`)) join `miembros` `mb` on(`mlc`.`miembro_id` = `mb`.`id`)) left join `iglesias` `i` on(`mb`.`iglesia_id` = `i`.`id`)) left join `distritos` `d` on(`i`.`distrito_id` = `d`.`id`)) ORDER BY `c`.`nombre` ASC, `m`.`nombre` ASC, CASE `mlc`.`cargo` WHEN 'presidente' THEN 1 WHEN 'vicepresidente' THEN 2 WHEN 'secretario' THEN 3 WHEN 'tesorero' THEN 4 ELSE 5 END ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_pastores_completo`
--
DROP TABLE IF EXISTS `v_pastores_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`renovaciondocent`@`localhost` SQL SECURITY INVOKER VIEW `v_pastores_completo`  AS SELECT `p`.`id` AS `id`, `p`.`nombre` AS `nombre`, `p`.`apellido` AS `apellido`, concat(`p`.`nombre`,' ',`p`.`apellido`) AS `nombre_completo`, `p`.`tipo_documento` AS `tipo_documento`, `p`.`cedula` AS `cedula`, `p`.`pasaporte` AS `pasaporte`, `p`.`telefono` AS `telefono`, `p`.`fecha_nacimiento` AS `fecha_nacimiento`, `p`.`edad` AS `edad`, `p`.`nacionalidad_id` AS `nacionalidad_id`, `n`.`nombre` AS `nacionalidad`, `p`.`estado_civil` AS `estado_civil`, `p`.`sexo` AS `sexo`, `p`.`foto` AS `foto`, `p`.`nivel_estudio_id` AS `nivel_estudio_id`, `ne`.`nombre` AS `nivel_estudio`, `p`.`carrera_id` AS `carrera_id`, `car`.`nombre` AS `carrera`, `p`.`formacion_continuada` AS `formacion_continuada`, `p`.`fecha_ingreso_ministerio` AS `fecha_ingreso_ministerio`, `p`.`anos_servicio` AS `anos_servicio`, `p`.`orden_ministerial` AS `orden_ministerial`, `p`.`conferencia_id` AS `conferencia_id`, `c`.`nombre` AS `conferencia`, `p`.`activo` AS `activo`, `p`.`creado_en` AS `creado_en`, (select `i`.`nombre` from (`pastor_iglesias` `pi` join `iglesias` `i` on(`pi`.`iglesia_id` = `i`.`id`)) where `pi`.`pastor_id` = `p`.`id` and `pi`.`activo` = 1 and `pi`.`es_principal` = 1 limit 1) AS `iglesia_principal`, (select count(0) from `pastor_iglesias` `pi` where `pi`.`pastor_id` = `p`.`id` and `pi`.`activo` = 1) AS `cantidad_iglesias` FROM ((((`pastores` `p` left join `nacionalidades` `n` on(`p`.`nacionalidad_id` = `n`.`id`)) left join `niveles_estudio` `ne` on(`p`.`nivel_estudio_id` = `ne`.`id`)) left join `carreras` `car` on(`p`.`carrera_id` = `car`.`id`)) left join `conferencias` `c` on(`p`.`conferencia_id` = `c`.`id`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas_ministeriales`
--
ALTER TABLE `areas_ministeriales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iglesia_id` (`iglesia_id`);

--
-- Indices de la tabla `area_lideres`
--
ALTER TABLE `area_lideres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iglesia_id` (`iglesia_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `periodo_id` (`periodo_id`),
  ADD KEY `miembro_id` (`miembro_id`);

--
-- Indices de la tabla `cargos_junta`
--
ALTER TABLE `cargos_junta`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `conferencias`
--
ALTER TABLE `conferencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_superintendente` (`superintendente_id`);

--
-- Indices de la tabla `conferencia_superintendentes_historial`
--
ALTER TABLE `conferencia_superintendentes_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conferencia` (`conferencia_id`),
  ADD KEY `idx_pastor` (`pastor_id`);

--
-- Indices de la tabla `distritos`
--
ALTER TABLE `distritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_distrito_codigo_conferencia` (`conferencia_id`,`codigo`),
  ADD KEY `idx_supervisor` (`supervisor_id`);

--
-- Indices de la tabla `distrito_supervisores_historial`
--
ALTER TABLE `distrito_supervisores_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_distrito` (`distrito_id`),
  ADD KEY `idx_pastor` (`pastor_id`);

--
-- Indices de la tabla `familias`
--
ALTER TABLE `familias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_familia_codigo_iglesia` (`iglesia_id`,`codigo`);

--
-- Indices de la tabla `fin_categorias`
--
ALTER TABLE `fin_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_categoria_iglesia` (`id_iglesia`,`nombre`,`tipo`);

--
-- Indices de la tabla `fin_cierres`
--
ALTER TABLE `fin_cierres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cierre` (`id_iglesia`,`ano`,`mes`);

--
-- Indices de la tabla `fin_cuentas`
--
ALTER TABLE `fin_cuentas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cuenta_iglesia` (`id_iglesia`,`codigo`);

--
-- Indices de la tabla `fin_entradas`
--
ALTER TABLE `fin_entradas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `idx_fecha` (`id_iglesia`,`fecha`),
  ADD KEY `idx_cuenta` (`id_cuenta`),
  ADD KEY `idx_miembro` (`id_miembro`);

--
-- Indices de la tabla `fin_salidas`
--
ALTER TABLE `fin_salidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `idx_fecha` (`id_iglesia`,`fecha`),
  ADD KEY `idx_cuenta` (`id_cuenta`);

--
-- Indices de la tabla `fin_transferencias`
--
ALTER TABLE `fin_transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_iglesia` (`id_iglesia`),
  ADD KEY `id_cuenta_origen` (`id_cuenta_origen`),
  ADD KEY `id_cuenta_destino` (`id_cuenta_destino`);

--
-- Indices de la tabla `iglesias`
--
ALTER TABLE `iglesias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_iglesia_codigo_distrito` (`distrito_id`,`codigo`),
  ADD KEY `fk_iglesias_provincia` (`provincia_id`),
  ADD KEY `fk_iglesias_municipio` (`municipio_id`);

--
-- Indices de la tabla `juntas`
--
ALTER TABLE `juntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iglesia_id` (`iglesia_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indices de la tabla `junta_miembros`
--
ALTER TABLE `junta_miembros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `junta_id` (`junta_id`),
  ADD KEY `miembro_id` (`miembro_id`),
  ADD KEY `cargo_id` (`cargo_id`);

--
-- Indices de la tabla `miembros`
--
ALTER TABLE `miembros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_miembros_iglesia` (`iglesia_id`),
  ADD KEY `fk_miembros_familia` (`familia_id`),
  ADD KEY `fk_miembros_nacionalidad` (`nacionalidad_id`),
  ADD KEY `fk_miembros_nivel_estudio` (`nivel_estudio_id`),
  ADD KEY `fk_miembros_carrera` (`carrera_id`),
  ADD KEY `fk_miembros_ministerio` (`ministerio_id`);

--
-- Indices de la tabla `ministerios`
--
ALTER TABLE `ministerios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ministerio_lideres_conferencia`
--
ALTER TABLE `ministerio_lideres_conferencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_lider_ministerio_conf` (`conferencia_id`,`ministerio_id`,`cargo`,`miembro_id`,`activo`),
  ADD KEY `miembro_id` (`miembro_id`),
  ADD KEY `idx_mlc_conferencia` (`conferencia_id`),
  ADD KEY `idx_mlc_ministerio` (`ministerio_id`),
  ADD KEY `idx_mlc_activo` (`activo`);

--
-- Indices de la tabla `ministerio_lideres_conferencia_historial`
--
ALTER TABLE `ministerio_lideres_conferencia_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conferencia_id` (`conferencia_id`),
  ADD KEY `ministerio_id` (`ministerio_id`),
  ADD KEY `miembro_id` (`miembro_id`);

--
-- Indices de la tabla `municipios`
--
ALTER TABLE `municipios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_municipio` (`provincia_id`,`nombre`);

--
-- Indices de la tabla `nacionalidades`
--
ALTER TABLE `nacionalidades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `niveles_estudio`
--
ALTER TABLE `niveles_estudio`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pastores`
--
ALTER TABLE `pastores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pastor_cedula` (`cedula`),
  ADD KEY `idx_pastor_conferencia` (`conferencia_id`),
  ADD KEY `idx_pastor_activo` (`activo`),
  ADD KEY `fk_pastor_nacionalidad` (`nacionalidad_id`),
  ADD KEY `fk_pastor_nivel_estudio` (`nivel_estudio_id`),
  ADD KEY `fk_pastor_carrera` (`carrera_id`);

--
-- Indices de la tabla `pastor_historial_asignaciones`
--
ALTER TABLE `pastor_historial_asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historial_pastor` (`pastor_id`),
  ADD KEY `idx_historial_iglesia` (`iglesia_id`);

--
-- Indices de la tabla `pastor_iglesias`
--
ALTER TABLE `pastor_iglesias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pastor_iglesia_activo` (`pastor_id`,`iglesia_id`,`activo`),
  ADD KEY `idx_pastor_iglesias_pastor` (`pastor_id`),
  ADD KEY `idx_pastor_iglesias_iglesia` (`iglesia_id`);

--
-- Indices de la tabla `pastor_ministerios_conferenciales`
--
ALTER TABLE `pastor_ministerios_conferenciales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pastor_ministerio_conf` (`pastor_id`,`area_ministerial_id`,`conferencia_id`,`activo`),
  ADD KEY `idx_pastor_min_pastor` (`pastor_id`),
  ADD KEY `idx_pastor_min_area` (`area_ministerial_id`),
  ADD KEY `idx_pastor_min_conf` (`conferencia_id`);

--
-- Indices de la tabla `periodos_iglesia`
--
ALTER TABLE `periodos_iglesia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `iglesia_id` (`iglesia_id`);

--
-- Indices de la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_provincia_nombre` (`nombre`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_usuarios_rol` (`rol_id`),
  ADD KEY `fk_usuarios_conferencia` (`conferencia_id`),
  ADD KEY `fk_usuarios_distrito` (`distrito_id`),
  ADD KEY `fk_usuarios_iglesia` (`iglesia_id`),
  ADD KEY `fk_usuarios_ministerio` (`ministerio_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas_ministeriales`
--
ALTER TABLE `areas_ministeriales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `area_lideres`
--
ALTER TABLE `area_lideres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cargos_junta`
--
ALTER TABLE `cargos_junta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `conferencias`
--
ALTER TABLE `conferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `conferencia_superintendentes_historial`
--
ALTER TABLE `conferencia_superintendentes_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `distritos`
--
ALTER TABLE `distritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `distrito_supervisores_historial`
--
ALTER TABLE `distrito_supervisores_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `familias`
--
ALTER TABLE `familias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `fin_categorias`
--
ALTER TABLE `fin_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `fin_cierres`
--
ALTER TABLE `fin_cierres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fin_cuentas`
--
ALTER TABLE `fin_cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `fin_entradas`
--
ALTER TABLE `fin_entradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `fin_salidas`
--
ALTER TABLE `fin_salidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `fin_transferencias`
--
ALTER TABLE `fin_transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `iglesias`
--
ALTER TABLE `iglesias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de la tabla `juntas`
--
ALTER TABLE `juntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `junta_miembros`
--
ALTER TABLE `junta_miembros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `miembros`
--
ALTER TABLE `miembros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `ministerios`
--
ALTER TABLE `ministerios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ministerio_lideres_conferencia`
--
ALTER TABLE `ministerio_lideres_conferencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ministerio_lideres_conferencia_historial`
--
ALTER TABLE `ministerio_lideres_conferencia_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `municipios`
--
ALTER TABLE `municipios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT de la tabla `nacionalidades`
--
ALTER TABLE `nacionalidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `niveles_estudio`
--
ALTER TABLE `niveles_estudio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pastores`
--
ALTER TABLE `pastores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `pastor_historial_asignaciones`
--
ALTER TABLE `pastor_historial_asignaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pastor_iglesias`
--
ALTER TABLE `pastor_iglesias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pastor_ministerios_conferenciales`
--
ALTER TABLE `pastor_ministerios_conferenciales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `periodos_iglesia`
--
ALTER TABLE `periodos_iglesia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `areas_ministeriales`
--
ALTER TABLE `areas_ministeriales`
  ADD CONSTRAINT `areas_ministeriales_ibfk_1` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `area_lideres`
--
ALTER TABLE `area_lideres`
  ADD CONSTRAINT `area_lideres_ibfk_1` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `area_lideres_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas_ministeriales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `area_lideres_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_iglesia` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `area_lideres_ibfk_4` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `conferencias`
--
ALTER TABLE `conferencias`
  ADD CONSTRAINT `fk_conferencias_superintendente` FOREIGN KEY (`superintendente_id`) REFERENCES `pastores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `conferencia_superintendentes_historial`
--
ALTER TABLE `conferencia_superintendentes_historial`
  ADD CONSTRAINT `fk_hist_super_conferencia` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_super_pastor` FOREIGN KEY (`pastor_id`) REFERENCES `pastores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `distritos`
--
ALTER TABLE `distritos`
  ADD CONSTRAINT `fk_distritos_conferencia` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_distritos_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `pastores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `distrito_supervisores_historial`
--
ALTER TABLE `distrito_supervisores_historial`
  ADD CONSTRAINT `fk_hist_sup_distrito` FOREIGN KEY (`distrito_id`) REFERENCES `distritos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_sup_pastor` FOREIGN KEY (`pastor_id`) REFERENCES `pastores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `familias`
--
ALTER TABLE `familias`
  ADD CONSTRAINT `fk_familias_iglesia` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `fin_categorias`
--
ALTER TABLE `fin_categorias`
  ADD CONSTRAINT `fin_categorias_ibfk_1` FOREIGN KEY (`id_iglesia`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fin_cierres`
--
ALTER TABLE `fin_cierres`
  ADD CONSTRAINT `fin_cierres_ibfk_1` FOREIGN KEY (`id_iglesia`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fin_cuentas`
--
ALTER TABLE `fin_cuentas`
  ADD CONSTRAINT `fin_cuentas_ibfk_1` FOREIGN KEY (`id_iglesia`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fin_entradas`
--
ALTER TABLE `fin_entradas`
  ADD CONSTRAINT `fin_entradas_ibfk_1` FOREIGN KEY (`id_iglesia`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fin_entradas_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `fin_categorias` (`id`),
  ADD CONSTRAINT `fin_entradas_ibfk_3` FOREIGN KEY (`id_cuenta`) REFERENCES `fin_cuentas` (`id`),
  ADD CONSTRAINT `fin_entradas_ibfk_4` FOREIGN KEY (`id_miembro`) REFERENCES `miembros` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `fin_salidas`
--
ALTER TABLE `fin_salidas`
  ADD CONSTRAINT `fin_salidas_ibfk_1` FOREIGN KEY (`id_iglesia`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fin_salidas_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `fin_categorias` (`id`),
  ADD CONSTRAINT `fin_salidas_ibfk_3` FOREIGN KEY (`id_cuenta`) REFERENCES `fin_cuentas` (`id`);

--
-- Filtros para la tabla `fin_transferencias`
--
ALTER TABLE `fin_transferencias`
  ADD CONSTRAINT `fin_transferencias_ibfk_1` FOREIGN KEY (`id_iglesia`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fin_transferencias_ibfk_2` FOREIGN KEY (`id_cuenta_origen`) REFERENCES `fin_cuentas` (`id`),
  ADD CONSTRAINT `fin_transferencias_ibfk_3` FOREIGN KEY (`id_cuenta_destino`) REFERENCES `fin_cuentas` (`id`);

--
-- Filtros para la tabla `iglesias`
--
ALTER TABLE `iglesias`
  ADD CONSTRAINT `fk_iglesias_distrito` FOREIGN KEY (`distrito_id`) REFERENCES `distritos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_iglesias_municipio` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_iglesias_provincia` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `juntas`
--
ALTER TABLE `juntas`
  ADD CONSTRAINT `juntas_ibfk_1` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `juntas_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_iglesia` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `junta_miembros`
--
ALTER TABLE `junta_miembros`
  ADD CONSTRAINT `junta_miembros_ibfk_1` FOREIGN KEY (`junta_id`) REFERENCES `juntas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `junta_miembros_ibfk_2` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `junta_miembros_ibfk_3` FOREIGN KEY (`cargo_id`) REFERENCES `cargos_junta` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `miembros`
--
ALTER TABLE `miembros`
  ADD CONSTRAINT `fk_miembros_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_miembros_familia` FOREIGN KEY (`familia_id`) REFERENCES `familias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_miembros_iglesia` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_miembros_ministerio` FOREIGN KEY (`ministerio_id`) REFERENCES `ministerios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_miembros_nacionalidad` FOREIGN KEY (`nacionalidad_id`) REFERENCES `nacionalidades` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_miembros_nivel_estudio` FOREIGN KEY (`nivel_estudio_id`) REFERENCES `niveles_estudio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ministerio_lideres_conferencia`
--
ALTER TABLE `ministerio_lideres_conferencia`
  ADD CONSTRAINT `ministerio_lideres_conferencia_ibfk_1` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ministerio_lideres_conferencia_ibfk_2` FOREIGN KEY (`ministerio_id`) REFERENCES `ministerios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ministerio_lideres_conferencia_ibfk_3` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ministerio_lideres_conferencia_historial`
--
ALTER TABLE `ministerio_lideres_conferencia_historial`
  ADD CONSTRAINT `ministerio_lideres_conferencia_historial_ibfk_1` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ministerio_lideres_conferencia_historial_ibfk_2` FOREIGN KEY (`ministerio_id`) REFERENCES `ministerios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ministerio_lideres_conferencia_historial_ibfk_3` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `municipios`
--
ALTER TABLE `municipios`
  ADD CONSTRAINT `fk_municipio_provincia` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pastores`
--
ALTER TABLE `pastores`
  ADD CONSTRAINT `fk_pastor_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pastor_conferencia` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pastor_nacionalidad` FOREIGN KEY (`nacionalidad_id`) REFERENCES `nacionalidades` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pastor_nivel_estudio` FOREIGN KEY (`nivel_estudio_id`) REFERENCES `niveles_estudio` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pastor_historial_asignaciones`
--
ALTER TABLE `pastor_historial_asignaciones`
  ADD CONSTRAINT `fk_historial_iglesia` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_pastor` FOREIGN KEY (`pastor_id`) REFERENCES `pastores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pastor_iglesias`
--
ALTER TABLE `pastor_iglesias`
  ADD CONSTRAINT `fk_pastor_iglesias_iglesia` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pastor_iglesias_pastor` FOREIGN KEY (`pastor_id`) REFERENCES `pastores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pastor_ministerios_conferenciales`
--
ALTER TABLE `pastor_ministerios_conferenciales`
  ADD CONSTRAINT `fk_pastor_min_area` FOREIGN KEY (`area_ministerial_id`) REFERENCES `areas_ministeriales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pastor_min_conf` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pastor_min_pastor` FOREIGN KEY (`pastor_id`) REFERENCES `pastores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `periodos_iglesia`
--
ALTER TABLE `periodos_iglesia`
  ADD CONSTRAINT `periodos_iglesia_ibfk_1` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_conferencia` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_distrito` FOREIGN KEY (`distrito_id`) REFERENCES `distritos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_iglesia` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_ministerio` FOREIGN KEY (`ministerio_id`) REFERENCES `ministerios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

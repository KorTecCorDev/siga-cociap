-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-05-2026 a las 07:59:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Crear la base de datos.
  CREATE DATABASE IF NOT EXISTS `siga_cociap`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

--
-- Base de datos: `siga_cociap`
--

USE siga_cociap;


-- Eliminar tablas

SET FOREIGN_KEY_CHECKS=0;
  DROP TABLE IF EXISTS `vinculo_familiar`, `usuarios`, `subareas`,
    `sesiones_horario`, `secciones`, `roles`, `reglas_especiales`,
    `personas`, `periodos`, `niveles`, `matriculas`, `grados`,
    `estudiantes`, `criterios`, `configuracion_horario`, `competencias`,
    `cargas_academicas`, `calificaciones_criterio`, `calificaciones`,
    `bloques_horario`, `bloqueos_competencia`, `areas`, `apoderados`,
    `anios_academicos`, `alertas`;
  SET FOREIGN_KEY_CHECKS=1;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id` int(10) UNSIGNED NOT NULL,
  `tutor_id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('academica','conductual','asistencia','general') NOT NULL DEFAULT 'general',
  `mensaje` text NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `enviada_correo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anios_academicos`
--

CREATE TABLE `anios_academicos` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `anio` year(4) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('planificado','activo','cerrado') NOT NULL DEFAULT 'planificado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `anios_academicos`
--

INSERT INTO `anios_academicos` (`id`, `anio`, `fecha_inicio`, `fecha_fin`, `estado`, `created_at`) VALUES
(1, '2026', '2026-03-09', '2026-12-18', 'activo', '2026-05-11 23:15:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apoderados`
--

CREATE TABLE `apoderados` (
  `id` int(10) UNSIGNED NOT NULL,
  `persona_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `apoderados`
--

INSERT INTO `apoderados` (`id`, `persona_id`, `created_at`) VALUES
(1, 8, '2026-05-11 23:15:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nivel_id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `nombre_boleta` varchar(120) DEFAULT NULL,
  `alias_boleta` varchar(80) DEFAULT NULL,
  `nombre_siagie` varchar(120) DEFAULT NULL,
  `tipo` enum('area_curso','con_subareas','transversal') NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id`, `nivel_id`, `nombre`, `nombre_boleta`, `alias_boleta`, `nombre_siagie`, `tipo`, `orden`, `activa`) VALUES
(1, 1, 'Personal Social', 'Personal Social', NULL, 'Personal Social', 'area_curso', 2, 1),
(2, 1, 'Educación Física', 'Educación Física', NULL, 'Educación Física', 'area_curso', 4, 1),
(3, 1, 'Arte y Cultura', 'Arte y Cultura', NULL, 'Arte y Cultura', 'area_curso', 6, 1),
(4, 1, 'Inglés', 'Inglés como Lengua Extranjera', NULL, 'Inglés como Lengua Extranjera', 'area_curso', 1, 1),
(5, 1, 'Educación Religiosa', 'Educación Religiosa', NULL, 'Educación Religiosa', 'area_curso', 3, 1),
(6, 1, 'Comunicación', 'Comunicación', NULL, 'Comunicación', 'con_subareas', 5, 1),
(7, 1, 'Matemática', 'Matemática', NULL, 'Matemática', 'con_subareas', 7, 1),
(8, 1, 'Ciencia y Tecnología', 'Ciencia y Tecnología', NULL, 'Ciencia y Tecnología', 'con_subareas', 8, 1),
(9, 1, 'Competencias Transversales', 'Comp. Transv.', NULL, NULL, 'transversal', 9, 1),
(10, 2, 'Desarrollo Personal, Ciudadanía y Cívica', 'DPCC', NULL, 'Desarrollo Personal, Ciudadanía y Cívica', 'area_curso', 1, 1),
(11, 2, 'Educación Física', 'Educación Física', NULL, 'Educación Física', 'area_curso', 3, 1),
(12, 2, 'Arte y Cultura', 'Arte y Cultura', NULL, 'Arte y Cultura', 'area_curso', 4, 1),
(13, 2, 'Inglés', 'Inglés', NULL, 'Inglés como Lengua Extranjera', 'area_curso', 6, 1),
(14, 2, 'Educación Religiosa', 'Educación Religiosa', '(Ética y Valores)', 'Educación Religiosa', 'area_curso', 10, 1),
(15, 2, 'Educación para el Trabajo', 'EPT', '(Habilidades Pedagógicas)', 'Educación para el Trabajo', 'area_curso', 11, 1),
(16, 2, 'Taller de Razonamiento Matemático', 'Taller Raz. Matemático', NULL, 'Educación Religiosa', 'area_curso', 8, 1),
(17, 2, 'Ciencias Sociales', 'Ciencias Sociales', NULL, 'Ciencias Sociales', 'con_subareas', 2, 1),
(18, 2, 'Comunicación', 'Comunicación', NULL, 'Comunicación', 'con_subareas', 5, 1),
(19, 2, 'Matemática', 'Matemática', NULL, 'Matemática', 'con_subareas', 7, 1),
(20, 2, 'Ciencia y Tecnología', 'Ciencia y Tecnología', NULL, 'Ciencia y Tecnología', 'con_subareas', 9, 1),
(21, 2, 'Competencias Transversales', 'Comp. Transv.', NULL, NULL, 'transversal', 12, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloqueos_competencia`
--

CREATE TABLE `bloqueos_competencia` (
  `id` int(10) UNSIGNED NOT NULL,
  `carga_id` int(10) UNSIGNED NOT NULL,
  `competencia_id` smallint(5) UNSIGNED NOT NULL,
  `periodo_id` smallint(5) UNSIGNED NOT NULL,
  `bloqueado_por` int(10) UNSIGNED NOT NULL,
  `bloqueado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bloqueos_competencia`
--

INSERT INTO `bloqueos_competencia` (`id`, `carga_id`, `competencia_id`, `periodo_id`, `bloqueado_por`, `bloqueado_en`) VALUES
(2, 0, 78, 1, 2, '2026-05-11 22:46:04'),
(3, 0, 79, 1, 2, '2026-05-11 22:46:04'),
(4, 0, 80, 1, 2, '2026-05-11 22:46:04'),
(5, 0, 81, 1, 2, '2026-05-11 22:46:04'),
(6, 0, 82, 1, 2, '2026-05-11 22:46:04'),
(7, 0, 83, 1, 2, '2026-05-11 22:46:04'),
(8, 0, 84, 1, 2, '2026-05-11 22:46:04'),
(9, 0, 85, 1, 2, '2026-05-11 22:46:04'),
(10, 0, 86, 1, 2, '2026-05-11 22:46:04'),
(11, 0, 87, 1, 2, '2026-05-11 22:46:04'),
(12, 0, 88, 1, 2, '2026-05-11 22:46:04'),
(13, 0, 89, 1, 2, '2026-05-11 22:46:04'),
(14, 0, 90, 1, 2, '2026-05-11 22:46:04'),
(15, 0, 91, 1, 2, '2026-05-11 22:46:04'),
(16, 0, 92, 1, 2, '2026-05-11 22:46:04'),
(17, 0, 93, 1, 2, '2026-05-11 22:46:04'),
(18, 0, 94, 1, 2, '2026-05-11 22:46:04'),
(19, 0, 95, 1, 2, '2026-05-11 22:46:04'),
(20, 0, 98, 1, 2, '2026-05-11 22:46:04'),
(21, 0, 99, 1, 2, '2026-05-11 22:46:04'),
(22, 0, 100, 1, 2, '2026-05-11 22:46:04'),
(23, 0, 101, 1, 2, '2026-05-11 22:46:04'),
(24, 0, 102, 1, 2, '2026-05-11 22:46:04'),
(25, 0, 103, 1, 2, '2026-05-11 22:46:04'),
(26, 0, 104, 1, 2, '2026-05-11 22:46:04'),
(27, 0, 78, 2, 2, '2026-05-11 22:46:04'),
(28, 0, 79, 2, 2, '2026-05-11 22:46:04'),
(29, 0, 80, 2, 2, '2026-05-11 22:46:04'),
(30, 0, 81, 2, 2, '2026-05-11 22:46:04'),
(31, 0, 82, 2, 2, '2026-05-11 22:46:04'),
(32, 0, 83, 2, 2, '2026-05-11 22:46:04'),
(33, 0, 84, 2, 2, '2026-05-11 22:46:04'),
(34, 0, 85, 2, 2, '2026-05-11 22:46:04'),
(35, 0, 86, 2, 2, '2026-05-11 22:46:04'),
(36, 0, 87, 2, 2, '2026-05-11 22:46:04'),
(37, 0, 88, 2, 2, '2026-05-11 22:46:04'),
(38, 0, 89, 2, 2, '2026-05-11 22:46:04'),
(39, 0, 90, 2, 2, '2026-05-11 22:46:04'),
(40, 0, 91, 2, 2, '2026-05-11 22:46:04'),
(41, 0, 92, 2, 2, '2026-05-11 22:46:04'),
(42, 0, 93, 2, 2, '2026-05-11 22:46:04'),
(43, 0, 94, 2, 2, '2026-05-11 22:46:04'),
(44, 0, 95, 2, 2, '2026-05-11 22:46:04'),
(45, 0, 98, 2, 2, '2026-05-11 22:46:04'),
(46, 0, 99, 2, 2, '2026-05-11 22:46:04'),
(47, 0, 100, 2, 2, '2026-05-11 22:46:04'),
(48, 0, 101, 2, 2, '2026-05-11 22:46:04'),
(49, 0, 102, 2, 2, '2026-05-11 22:46:04'),
(50, 0, 103, 2, 2, '2026-05-11 22:46:04'),
(51, 0, 104, 2, 2, '2026-05-11 22:46:04'),
(52, 0, 78, 3, 2, '2026-05-11 22:46:04'),
(53, 0, 79, 3, 2, '2026-05-11 22:46:04'),
(54, 0, 80, 3, 2, '2026-05-11 22:46:04'),
(55, 0, 81, 3, 2, '2026-05-11 22:46:04'),
(56, 0, 82, 3, 2, '2026-05-11 22:46:04'),
(57, 0, 83, 3, 2, '2026-05-11 22:46:04'),
(58, 0, 84, 3, 2, '2026-05-11 22:46:04'),
(59, 0, 85, 3, 2, '2026-05-11 22:46:04'),
(60, 0, 86, 3, 2, '2026-05-11 22:46:04'),
(61, 0, 87, 3, 2, '2026-05-11 22:46:04'),
(62, 0, 88, 3, 2, '2026-05-11 22:46:04'),
(63, 0, 89, 3, 2, '2026-05-11 22:46:04'),
(64, 0, 90, 3, 2, '2026-05-11 22:46:04'),
(65, 0, 91, 3, 2, '2026-05-11 22:46:04'),
(66, 0, 92, 3, 2, '2026-05-11 22:46:04'),
(67, 0, 93, 3, 2, '2026-05-11 22:46:04'),
(68, 0, 94, 3, 2, '2026-05-11 22:46:04'),
(69, 0, 95, 3, 2, '2026-05-11 22:46:04'),
(70, 0, 98, 3, 2, '2026-05-11 22:46:04'),
(71, 0, 99, 3, 2, '2026-05-11 22:46:04'),
(72, 0, 100, 3, 2, '2026-05-11 22:46:04'),
(73, 0, 101, 3, 2, '2026-05-11 22:46:04'),
(74, 0, 102, 3, 2, '2026-05-11 22:46:04'),
(75, 0, 103, 3, 2, '2026-05-11 22:46:04'),
(76, 0, 104, 3, 2, '2026-05-11 22:46:04'),
(77, 0, 78, 4, 2, '2026-05-11 22:46:04'),
(78, 0, 79, 4, 2, '2026-05-11 22:46:04'),
(79, 0, 80, 4, 2, '2026-05-11 22:46:04'),
(80, 0, 81, 4, 2, '2026-05-11 22:46:04'),
(81, 0, 82, 4, 2, '2026-05-11 22:46:04'),
(82, 0, 83, 4, 2, '2026-05-11 22:46:04'),
(83, 0, 84, 4, 2, '2026-05-11 22:46:04'),
(84, 0, 85, 4, 2, '2026-05-11 22:46:04'),
(85, 0, 86, 4, 2, '2026-05-11 22:46:04'),
(86, 0, 87, 4, 2, '2026-05-11 22:46:04'),
(87, 0, 88, 4, 2, '2026-05-11 22:46:04'),
(88, 0, 89, 4, 2, '2026-05-11 22:46:04'),
(89, 0, 90, 4, 2, '2026-05-11 22:46:04'),
(90, 0, 91, 4, 2, '2026-05-11 22:46:04'),
(91, 0, 92, 4, 2, '2026-05-11 22:46:04'),
(92, 0, 93, 4, 2, '2026-05-11 22:46:04'),
(93, 0, 94, 4, 2, '2026-05-11 22:46:04'),
(94, 0, 95, 4, 2, '2026-05-11 22:46:04'),
(95, 0, 98, 4, 2, '2026-05-11 22:46:04'),
(96, 0, 99, 4, 2, '2026-05-11 22:46:04'),
(97, 0, 100, 4, 2, '2026-05-11 22:46:04'),
(98, 0, 101, 4, 2, '2026-05-11 22:46:04'),
(99, 0, 102, 4, 2, '2026-05-11 22:46:04'),
(100, 0, 103, 4, 2, '2026-05-11 22:46:04'),
(101, 0, 104, 4, 2, '2026-05-11 22:46:04'),
(102, 1, 96, 2, 2, '2026-05-11 22:46:04'),
(103, 1, 96, 3, 2, '2026-05-11 22:46:04'),
(104, 1, 96, 4, 2, '2026-05-11 22:46:04'),
(106, 2, 97, 2, 2, '2026-05-11 22:46:04'),
(107, 2, 97, 3, 2, '2026-05-11 22:46:04'),
(108, 2, 97, 4, 2, '2026-05-11 22:46:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloques_horario`
--

CREATE TABLE `bloques_horario` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `config_id` smallint(5) UNSIGNED NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes') NOT NULL,
  `numero_bloque` tinyint(3) UNSIGNED NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bloques_horario`
--

INSERT INTO `bloques_horario` (`id`, `config_id`, `dia_semana`, `numero_bloque`, `hora_inicio`, `hora_fin`) VALUES
(1, 1, 'lunes', 1, '16:30:00', '17:20:00'),
(2, 1, 'lunes', 2, '17:20:00', '18:10:00'),
(3, 1, 'lunes', 3, '13:00:00', '13:50:00'),
(4, 1, 'lunes', 4, '13:50:00', '14:40:00'),
(5, 1, 'lunes', 5, '14:40:00', '15:30:00'),
(6, 1, 'lunes', 6, '15:30:00', '16:10:00'),
(7, 1, 'lunes', 7, '16:30:00', '17:15:00'),
(8, 1, 'lunes', 8, '17:15:00', '18:00:00'),
(9, 1, 'lunes', 9, '18:00:00', '18:45:00'),
(10, 1, 'lunes', 10, '13:00:00', '13:45:00'),
(11, 1, 'martes', 1, '13:00:00', '13:45:00'),
(12, 1, 'lunes', 11, '13:45:00', '14:30:00'),
(13, 1, 'lunes', 12, '14:30:00', '15:15:00'),
(14, 1, 'martes', 2, '13:45:00', '14:30:00'),
(15, 1, 'martes', 3, '14:30:00', '15:15:00'),
(16, 1, 'martes', 4, '15:15:00', '16:00:00'),
(17, 1, 'lunes', 13, '13:10:00', '13:55:00'),
(18, 1, 'lunes', 14, '14:40:00', '16:10:00'),
(19, 1, 'lunes', 15, '13:10:00', '14:40:00'),
(20, 1, 'lunes', 16, '16:35:00', '17:20:00'),
(21, 1, 'lunes', 17, '17:20:00', '18:50:00'),
(22, 1, 'martes', 5, '13:10:00', '14:40:00'),
(23, 1, 'lunes', 18, '14:40:00', '15:25:00'),
(24, 1, 'martes', 6, '15:25:00', '16:10:00'),
(25, 1, 'martes', 7, '16:35:00', '17:20:00'),
(26, 1, 'martes', 8, '17:20:00', '18:50:00'),
(27, 1, 'miercoles', 1, '13:10:00', '14:40:00'),
(28, 1, 'miercoles', 2, '14:40:00', '16:10:00'),
(29, 1, 'miercoles', 3, '16:35:00', '17:20:00'),
(30, 1, 'miercoles', 4, '17:20:00', '18:50:00'),
(31, 1, 'jueves', 1, '14:40:00', '16:10:00'),
(32, 1, 'jueves', 2, '16:35:00', '17:20:00'),
(33, 1, 'jueves', 3, '17:20:00', '18:05:00'),
(34, 1, 'jueves', 4, '18:05:00', '18:50:00'),
(35, 1, 'viernes', 1, '13:10:00', '14:40:00'),
(36, 1, 'viernes', 2, '14:40:00', '16:10:00'),
(37, 1, 'viernes', 3, '17:20:00', '18:05:00'),
(38, 1, 'viernes', 4, '18:05:00', '18:50:00'),
(39, 1, 'lunes', 19, '16:35:00', '18:05:00'),
(40, 1, 'lunes', 20, '18:05:00', '18:50:00'),
(41, 1, 'miercoles', 5, '16:35:00', '18:05:00'),
(42, 1, 'jueves', 5, '13:10:00', '14:40:00'),
(43, 1, 'jueves', 6, '16:35:00', '18:05:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `carga_id` int(10) UNSIGNED NOT NULL,
  `periodo_id` smallint(5) UNSIGNED NOT NULL,
  `competencia_id` smallint(5) UNSIGNED NOT NULL,
  `nota_numerica` tinyint(3) UNSIGNED NOT NULL,
  `conclusion_descriptiva` text DEFAULT NULL,
  `registrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `modificado_en` datetime DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calificaciones`
--

INSERT INTO `calificaciones` (`id`, `matricula_id`, `carga_id`, `periodo_id`, `competencia_id`, `nota_numerica`, `conclusion_descriptiva`, `registrado_en`, `modificado_en`, `registrado_por`) VALUES
(1, 1, 2, 1, 97, 6, 'La estudiante presenta dificultades para resolver problemas de regularidad, equivalencia y cambio. No logra identificar patrones, trabajar con expresiones algebraicas simples ni resolver ecuaciones de primer grado. Sus errores reflejan falta de comprensión conceptual básica en álgebra. Se recomienda trabajar con material visual y concreto, practicar problemas de forma progresiva, utilizar recursos interactivos de matemáticas en línea y pedir apoyo de tutoría escolar.', '2026-05-11 22:14:24', '2026-05-11 22:46:45', 2),
(2, 2, 2, 1, 97, 15, NULL, '2026-05-11 22:14:24', NULL, 2),
(3, 3, 2, 1, 97, 15, NULL, '2026-05-11 22:14:24', NULL, 2),
(4, 4, 2, 1, 97, 15, NULL, '2026-05-11 22:14:24', NULL, 2),
(5, 5, 2, 1, 97, 14, NULL, '2026-05-11 22:14:24', NULL, 2),
(6, 92, 3, 1, 96, 15, NULL, '2026-05-11 22:17:04', NULL, 2),
(7, 93, 3, 1, 96, 14, NULL, '2026-05-11 22:17:04', NULL, 2),
(8, 94, 3, 1, 96, 14, NULL, '2026-05-11 22:17:04', NULL, 2),
(9, 95, 3, 1, 96, 14, NULL, '2026-05-11 22:17:04', NULL, 2),
(10, 96, 3, 1, 96, 14, NULL, '2026-05-11 22:17:04', NULL, 2),
(11, 99, 4, 1, 96, 14, NULL, '2026-05-11 22:18:04', NULL, 2),
(12, 100, 4, 1, 96, 14, NULL, '2026-05-11 22:18:04', NULL, 2),
(13, 101, 4, 1, 96, 13, NULL, '2026-05-11 22:18:04', NULL, 2),
(14, 102, 4, 1, 96, 16, NULL, '2026-05-11 22:18:04', NULL, 2),
(15, 103, 4, 1, 96, 15, NULL, '2026-05-11 22:18:04', NULL, 2),
(16, 106, 5, 1, 96, 14, NULL, '2026-05-11 22:20:03', '2026-05-11 22:22:49', 2),
(17, 107, 5, 1, 96, 15, NULL, '2026-05-11 22:20:03', '2026-05-11 22:22:49', 2),
(18, 108, 5, 1, 96, 14, NULL, '2026-05-11 22:20:03', '2026-05-11 22:22:49', 2),
(19, 109, 5, 1, 96, 15, NULL, '2026-05-11 22:20:03', '2026-05-11 22:22:49', 2),
(20, 110, 5, 1, 96, 14, NULL, '2026-05-11 22:20:03', '2026-05-11 22:22:49', 2),
(26, 113, 6, 1, 96, 13, NULL, '2026-05-11 22:23:02', NULL, 2),
(27, 114, 6, 1, 96, 13, NULL, '2026-05-11 22:23:02', NULL, 2),
(28, 115, 6, 1, 96, 13, NULL, '2026-05-11 22:23:02', NULL, 2),
(29, 116, 6, 1, 96, 13, NULL, '2026-05-11 22:23:02', NULL, 2),
(30, 117, 6, 1, 96, 13, NULL, '2026-05-11 22:23:02', NULL, 2),
(31, 120, 7, 1, 96, 15, NULL, '2026-05-11 22:23:15', '2026-05-11 22:23:48', 2),
(32, 121, 7, 1, 96, 15, NULL, '2026-05-11 22:23:15', '2026-05-11 22:23:48', 2),
(33, 122, 7, 1, 96, 15, NULL, '2026-05-11 22:23:15', '2026-05-11 22:23:48', 2),
(34, 123, 7, 1, 96, 13, NULL, '2026-05-11 22:23:15', '2026-05-11 22:23:48', 2),
(35, 124, 7, 1, 96, 14, NULL, '2026-05-11 22:23:15', '2026-05-11 22:23:48', 2),
(41, 127, 8, 1, 96, 14, NULL, '2026-05-11 22:24:04', NULL, 2),
(42, 128, 8, 1, 96, 13, NULL, '2026-05-11 22:24:04', NULL, 2),
(43, 129, 8, 1, 96, 14, NULL, '2026-05-11 22:24:04', NULL, 2),
(44, 130, 8, 1, 96, 14, NULL, '2026-05-11 22:24:04', NULL, 2),
(45, 131, 8, 1, 96, 14, NULL, '2026-05-11 22:24:04', NULL, 2),
(46, 134, 9, 1, 96, 15, NULL, '2026-05-11 22:31:16', NULL, 2),
(47, 135, 9, 1, 96, 15, NULL, '2026-05-11 22:31:16', NULL, 2),
(48, 136, 9, 1, 96, 14, NULL, '2026-05-11 22:31:16', NULL, 2),
(49, 137, 9, 1, 96, 15, NULL, '2026-05-11 22:31:16', NULL, 2),
(50, 138, 9, 1, 96, 15, NULL, '2026-05-11 22:31:16', NULL, 2),
(51, 141, 11, 1, 96, 15, NULL, '2026-05-11 22:32:18', NULL, 2),
(52, 142, 11, 1, 96, 15, NULL, '2026-05-11 22:32:18', NULL, 2),
(53, 143, 11, 1, 96, 14, NULL, '2026-05-11 22:32:18', NULL, 2),
(54, 144, 11, 1, 96, 14, NULL, '2026-05-11 22:32:18', NULL, 2),
(55, 145, 11, 1, 96, 14, NULL, '2026-05-11 22:32:18', NULL, 2),
(56, 148, 10, 1, 96, 13, NULL, '2026-05-11 22:32:34', NULL, 2),
(57, 149, 10, 1, 96, 14, NULL, '2026-05-11 22:32:34', NULL, 2),
(58, 150, 10, 1, 96, 14, NULL, '2026-05-11 22:32:34', NULL, 2),
(59, 151, 10, 1, 96, 14, NULL, '2026-05-11 22:32:34', NULL, 2),
(60, 152, 10, 1, 96, 14, NULL, '2026-05-11 22:32:34', NULL, 2),
(61, 155, 12, 1, 96, 15, NULL, '2026-05-11 22:32:46', NULL, 2),
(62, 156, 12, 1, 96, 15, NULL, '2026-05-11 22:32:46', NULL, 2),
(63, 157, 12, 1, 96, 14, NULL, '2026-05-11 22:32:46', NULL, 2),
(64, 158, 12, 1, 96, 14, NULL, '2026-05-11 22:32:46', NULL, 2),
(65, 159, 12, 1, 96, 15, NULL, '2026-05-11 22:32:46', NULL, 2),
(66, 1, 1, 1, 96, 7, 'La estudiante presenta serias dificultades para resolver problemas de cantidad. No logra aplicar estrategias de cálculo aritmético básico ni comprender el sistema de numeración de manera adecuada. Sus errores son frecuentes en operaciones con números naturales, fracciones y decimales. Se recomienda reforzar las operaciones básicas con material concreto en casa, practicar el cálculo mental a diario y solicitar apoyo de tutoría para lograr una nivelación matemática efectiva.', '2026-05-11 22:33:34', '2026-05-11 22:46:45', 2),
(67, 2, 1, 1, 96, 14, '', '2026-05-11 22:33:34', '2026-05-11 22:36:08', 2),
(68, 3, 1, 1, 96, 15, '', '2026-05-11 22:33:34', '2026-05-11 22:36:08', 2),
(69, 4, 1, 1, 96, 15, '', '2026-05-11 22:33:34', '2026-05-11 22:36:08', 2),
(70, 5, 1, 1, 96, 14, '', '2026-05-11 22:33:34', '2026-05-11 22:36:08', 2),
(71, 1, 0, 1, 78, 7, 'La estudiante presenta serias dificultades para construir su identidad personal. No logra reflexionar sobre sus emociones, valores ni reconocer su historia personal con profundidad. Muestra inseguridad al relacionarse con sus pares y al expresar sus puntos de vista. Es necesario brindarle acompañamiento emocional permanente, fortalecer el vínculo familiar y promover actividades que desarrollen su autoestima y sentido de pertenencia a la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(72, 1, 0, 2, 78, 7, 'La estudiante presenta serias dificultades para construir su identidad personal. No logra reflexionar sobre sus emociones, valores ni reconocer su historia personal con profundidad. Muestra inseguridad al relacionarse con sus pares y al expresar sus puntos de vista. Es necesario brindarle acompañamiento emocional permanente, fortalecer el vínculo familiar y promover actividades que desarrollen su autoestima y sentido de pertenencia a la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(73, 1, 0, 3, 78, 7, 'La estudiante presenta serias dificultades para construir su identidad personal. No logra reflexionar sobre sus emociones, valores ni reconocer su historia personal con profundidad. Muestra inseguridad al relacionarse con sus pares y al expresar sus puntos de vista. Es necesario brindarle acompañamiento emocional permanente, fortalecer el vínculo familiar y promover actividades que desarrollen su autoestima y sentido de pertenencia a la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(74, 1, 0, 4, 78, 7, 'La estudiante presenta serias dificultades para construir su identidad personal. No logra reflexionar sobre sus emociones, valores ni reconocer su historia personal con profundidad. Muestra inseguridad al relacionarse con sus pares y al expresar sus puntos de vista. Es necesario brindarle acompañamiento emocional permanente, fortalecer el vínculo familiar y promover actividades que desarrollen su autoestima y sentido de pertenencia a la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(78, 1, 0, 1, 79, 6, 'La estudiante no logra convivir ni participar democráticamente de forma efectiva en el aula. Muestra dificultades para respetar normas de convivencia, escuchar opiniones distintas y asumir compromisos ciudadanos básicos. Su participación en actividades grupales es mínima y poco propositiva. Se recomienda reforzar hábitos de convivencia en el hogar, dialogar sobre el respeto mutuo y motivarla a involucrarse en espacios de participación estudiantil del colegio.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(79, 1, 0, 2, 79, 6, 'La estudiante no logra convivir ni participar democráticamente de forma efectiva en el aula. Muestra dificultades para respetar normas de convivencia, escuchar opiniones distintas y asumir compromisos ciudadanos básicos. Su participación en actividades grupales es mínima y poco propositiva. Se recomienda reforzar hábitos de convivencia en el hogar, dialogar sobre el respeto mutuo y motivarla a involucrarse en espacios de participación estudiantil del colegio.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(80, 1, 0, 3, 79, 6, 'La estudiante no logra convivir ni participar democráticamente de forma efectiva en el aula. Muestra dificultades para respetar normas de convivencia, escuchar opiniones distintas y asumir compromisos ciudadanos básicos. Su participación en actividades grupales es mínima y poco propositiva. Se recomienda reforzar hábitos de convivencia en el hogar, dialogar sobre el respeto mutuo y motivarla a involucrarse en espacios de participación estudiantil del colegio.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(81, 1, 0, 4, 79, 6, 'La estudiante no logra convivir ni participar democráticamente de forma efectiva en el aula. Muestra dificultades para respetar normas de convivencia, escuchar opiniones distintas y asumir compromisos ciudadanos básicos. Su participación en actividades grupales es mínima y poco propositiva. Se recomienda reforzar hábitos de convivencia en el hogar, dialogar sobre el respeto mutuo y motivarla a involucrarse en espacios de participación estudiantil del colegio.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(85, 1, 0, 1, 90, 8, 'La estudiante muestra dificultades significativas para construir interpretaciones históricas. No logra analizar fuentes ni relacionar causas y consecuencias de los procesos del Perú y el mundo. Sus producciones escritas presentan escasa argumentación e incoherencia histórica. Se recomienda apoyarla con lecturas accesibles sobre historia peruana, ver documentales históricos, practicar líneas de tiempo y solicitar acompañamiento en tutoría para reforzar esta competencia.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(86, 1, 0, 2, 90, 8, 'La estudiante muestra dificultades significativas para construir interpretaciones históricas. No logra analizar fuentes ni relacionar causas y consecuencias de los procesos del Perú y el mundo. Sus producciones escritas presentan escasa argumentación e incoherencia histórica. Se recomienda apoyarla con lecturas accesibles sobre historia peruana, ver documentales históricos, practicar líneas de tiempo y solicitar acompañamiento en tutoría para reforzar esta competencia.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(87, 1, 0, 3, 90, 8, 'La estudiante muestra dificultades significativas para construir interpretaciones históricas. No logra analizar fuentes ni relacionar causas y consecuencias de los procesos del Perú y el mundo. Sus producciones escritas presentan escasa argumentación e incoherencia histórica. Se recomienda apoyarla con lecturas accesibles sobre historia peruana, ver documentales históricos, practicar líneas de tiempo y solicitar acompañamiento en tutoría para reforzar esta competencia.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(88, 1, 0, 4, 90, 8, 'La estudiante muestra dificultades significativas para construir interpretaciones históricas. No logra analizar fuentes ni relacionar causas y consecuencias de los procesos del Perú y el mundo. Sus producciones escritas presentan escasa argumentación e incoherencia histórica. Se recomienda apoyarla con lecturas accesibles sobre historia peruana, ver documentales históricos, practicar líneas de tiempo y solicitar acompañamiento en tutoría para reforzar esta competencia.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(92, 1, 0, 1, 91, 7, 'La estudiante presenta dificultades para gestionar responsablemente el espacio y el ambiente. No identifica problemáticas ambientales de su entorno ni propone soluciones concretas. Muestra desinterés por el cuidado del medio ambiente y desconoce nociones básicas de geografía local y regional. Se sugiere promover hábitos ecológicos en casa, visitar espacios naturales de Áncash y consultar materiales sobre geografía e impacto ambiental de la región para mejorar su comprensión.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(93, 1, 0, 2, 91, 7, 'La estudiante presenta dificultades para gestionar responsablemente el espacio y el ambiente. No identifica problemáticas ambientales de su entorno ni propone soluciones concretas. Muestra desinterés por el cuidado del medio ambiente y desconoce nociones básicas de geografía local y regional. Se sugiere promover hábitos ecológicos en casa, visitar espacios naturales de Áncash y consultar materiales sobre geografía e impacto ambiental de la región para mejorar su comprensión.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(94, 1, 0, 3, 91, 7, 'La estudiante presenta dificultades para gestionar responsablemente el espacio y el ambiente. No identifica problemáticas ambientales de su entorno ni propone soluciones concretas. Muestra desinterés por el cuidado del medio ambiente y desconoce nociones básicas de geografía local y regional. Se sugiere promover hábitos ecológicos en casa, visitar espacios naturales de Áncash y consultar materiales sobre geografía e impacto ambiental de la región para mejorar su comprensión.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(95, 1, 0, 4, 91, 7, 'La estudiante presenta dificultades para gestionar responsablemente el espacio y el ambiente. No identifica problemáticas ambientales de su entorno ni propone soluciones concretas. Muestra desinterés por el cuidado del medio ambiente y desconoce nociones básicas de geografía local y regional. Se sugiere promover hábitos ecológicos en casa, visitar espacios naturales de Áncash y consultar materiales sobre geografía e impacto ambiental de la región para mejorar su comprensión.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(99, 1, 0, 1, 92, 6, 'La estudiante no ha desarrollado capacidades para gestionar responsablemente los recursos económicos. Presenta dificultades para comprender nociones básicas de economía familiar y ciudadana, no aplica estrategias de ahorro ni reconoce la importancia del trabajo productivo. Se recomienda trabajar situaciones económicas de la vida cotidiana en familia, dialogar sobre el manejo del dinero y apoyarse en material audiovisual básico de educación financiera para adolescentes.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(100, 1, 0, 2, 92, 6, 'La estudiante no ha desarrollado capacidades para gestionar responsablemente los recursos económicos. Presenta dificultades para comprender nociones básicas de economía familiar y ciudadana, no aplica estrategias de ahorro ni reconoce la importancia del trabajo productivo. Se recomienda trabajar situaciones económicas de la vida cotidiana en familia, dialogar sobre el manejo del dinero y apoyarse en material audiovisual básico de educación financiera para adolescentes.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(101, 1, 0, 3, 92, 6, 'La estudiante no ha desarrollado capacidades para gestionar responsablemente los recursos económicos. Presenta dificultades para comprender nociones básicas de economía familiar y ciudadana, no aplica estrategias de ahorro ni reconoce la importancia del trabajo productivo. Se recomienda trabajar situaciones económicas de la vida cotidiana en familia, dialogar sobre el manejo del dinero y apoyarse en material audiovisual básico de educación financiera para adolescentes.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(102, 1, 0, 4, 92, 6, 'La estudiante no ha desarrollado capacidades para gestionar responsablemente los recursos económicos. Presenta dificultades para comprender nociones básicas de economía familiar y ciudadana, no aplica estrategias de ahorro ni reconoce la importancia del trabajo productivo. Se recomienda trabajar situaciones económicas de la vida cotidiana en familia, dialogar sobre el manejo del dinero y apoyarse en material audiovisual básico de educación financiera para adolescentes.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(106, 1, 0, 1, 80, 9, 'La estudiante muestra dificultades para desenvolverse de manera autónoma a través de su motricidad. No controla su cuerpo con eficiencia en actividades físicas básicas ni desarrolla su coordinación motora de forma adecuada. Presenta poca confianza en sus capacidades físicas y evita ejercicios que requieran esfuerzo. Se recomienda practicar actividades físicas sencillas en casa, caminar a diario y participar en juegos recreativos para fortalecer gradualmente su motricidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(107, 1, 0, 2, 80, 9, 'La estudiante muestra dificultades para desenvolverse de manera autónoma a través de su motricidad. No controla su cuerpo con eficiencia en actividades físicas básicas ni desarrolla su coordinación motora de forma adecuada. Presenta poca confianza en sus capacidades físicas y evita ejercicios que requieran esfuerzo. Se recomienda practicar actividades físicas sencillas en casa, caminar a diario y participar en juegos recreativos para fortalecer gradualmente su motricidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(108, 1, 0, 3, 80, 9, 'La estudiante muestra dificultades para desenvolverse de manera autónoma a través de su motricidad. No controla su cuerpo con eficiencia en actividades físicas básicas ni desarrolla su coordinación motora de forma adecuada. Presenta poca confianza en sus capacidades físicas y evita ejercicios que requieran esfuerzo. Se recomienda practicar actividades físicas sencillas en casa, caminar a diario y participar en juegos recreativos para fortalecer gradualmente su motricidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(109, 1, 0, 4, 80, 9, 'La estudiante muestra dificultades para desenvolverse de manera autónoma a través de su motricidad. No controla su cuerpo con eficiencia en actividades físicas básicas ni desarrolla su coordinación motora de forma adecuada. Presenta poca confianza en sus capacidades físicas y evita ejercicios que requieran esfuerzo. Se recomienda practicar actividades físicas sencillas en casa, caminar a diario y participar en juegos recreativos para fortalecer gradualmente su motricidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(113, 1, 0, 1, 81, 8, 'La estudiante presenta dificultades para asumir hábitos de vida saludable. No demuestra conciencia sobre la importancia del ejercicio, la alimentación balanceada ni el descanso adecuado para su desarrollo. Sus rutinas cotidianas no favorecen el bienestar físico y emocional esperado para su edad. Se recomienda establecer horarios de alimentación y descanso regulares en casa, reducir el sedentarismo y orientar a la familia sobre nutrición básica para adolescentes de su etapa.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(114, 1, 0, 2, 81, 8, 'La estudiante presenta dificultades para asumir hábitos de vida saludable. No demuestra conciencia sobre la importancia del ejercicio, la alimentación balanceada ni el descanso adecuado para su desarrollo. Sus rutinas cotidianas no favorecen el bienestar físico y emocional esperado para su edad. Se recomienda establecer horarios de alimentación y descanso regulares en casa, reducir el sedentarismo y orientar a la familia sobre nutrición básica para adolescentes de su etapa.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(115, 1, 0, 3, 81, 8, 'La estudiante presenta dificultades para asumir hábitos de vida saludable. No demuestra conciencia sobre la importancia del ejercicio, la alimentación balanceada ni el descanso adecuado para su desarrollo. Sus rutinas cotidianas no favorecen el bienestar físico y emocional esperado para su edad. Se recomienda establecer horarios de alimentación y descanso regulares en casa, reducir el sedentarismo y orientar a la familia sobre nutrición básica para adolescentes de su etapa.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(116, 1, 0, 4, 81, 8, 'La estudiante presenta dificultades para asumir hábitos de vida saludable. No demuestra conciencia sobre la importancia del ejercicio, la alimentación balanceada ni el descanso adecuado para su desarrollo. Sus rutinas cotidianas no favorecen el bienestar físico y emocional esperado para su edad. Se recomienda establecer horarios de alimentación y descanso regulares en casa, reducir el sedentarismo y orientar a la familia sobre nutrición básica para adolescentes de su etapa.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(120, 1, 0, 1, 82, 9, 'La estudiante presenta dificultades para interactuar a través de sus habilidades sociomotrices. No logra participar de forma activa y colaborativa en juegos y deportes grupales. Muestra limitada tolerancia a la frustración y dificultad para respetar reglas de juego en equipo. Se recomienda motivarla a practicar deportes sencillos con familiares, participar en actividades recreativas de la comunidad y desarrollar el trabajo colaborativo mediante juegos grupales y de equipo.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(121, 1, 0, 2, 82, 9, 'La estudiante presenta dificultades para interactuar a través de sus habilidades sociomotrices. No logra participar de forma activa y colaborativa en juegos y deportes grupales. Muestra limitada tolerancia a la frustración y dificultad para respetar reglas de juego en equipo. Se recomienda motivarla a practicar deportes sencillos con familiares, participar en actividades recreativas de la comunidad y desarrollar el trabajo colaborativo mediante juegos grupales y de equipo.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(122, 1, 0, 3, 82, 9, 'La estudiante presenta dificultades para interactuar a través de sus habilidades sociomotrices. No logra participar de forma activa y colaborativa en juegos y deportes grupales. Muestra limitada tolerancia a la frustración y dificultad para respetar reglas de juego en equipo. Se recomienda motivarla a practicar deportes sencillos con familiares, participar en actividades recreativas de la comunidad y desarrollar el trabajo colaborativo mediante juegos grupales y de equipo.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(123, 1, 0, 4, 82, 9, 'La estudiante presenta dificultades para interactuar a través de sus habilidades sociomotrices. No logra participar de forma activa y colaborativa en juegos y deportes grupales. Muestra limitada tolerancia a la frustración y dificultad para respetar reglas de juego en equipo. Se recomienda motivarla a practicar deportes sencillos con familiares, participar en actividades recreativas de la comunidad y desarrollar el trabajo colaborativo mediante juegos grupales y de equipo.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(127, 1, 0, 1, 83, 7, 'La estudiante presenta dificultades para apreciar de manera crítica manifestaciones artístico-culturales. No logra analizar ni emitir juicios fundamentados sobre obras artísticas o expresiones culturales de su entorno. Muestra desinterés por el arte y las manifestaciones culturales locales. Se recomienda visitar el Museo Regional de Áncash, participar en actividades culturales de Huaraz, explorar el arte andino y ver documentales sobre manifestaciones culturales del Perú.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(128, 1, 0, 2, 83, 7, 'La estudiante presenta dificultades para apreciar de manera crítica manifestaciones artístico-culturales. No logra analizar ni emitir juicios fundamentados sobre obras artísticas o expresiones culturales de su entorno. Muestra desinterés por el arte y las manifestaciones culturales locales. Se recomienda visitar el Museo Regional de Áncash, participar en actividades culturales de Huaraz, explorar el arte andino y ver documentales sobre manifestaciones culturales del Perú.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(129, 1, 0, 3, 83, 7, 'La estudiante presenta dificultades para apreciar de manera crítica manifestaciones artístico-culturales. No logra analizar ni emitir juicios fundamentados sobre obras artísticas o expresiones culturales de su entorno. Muestra desinterés por el arte y las manifestaciones culturales locales. Se recomienda visitar el Museo Regional de Áncash, participar en actividades culturales de Huaraz, explorar el arte andino y ver documentales sobre manifestaciones culturales del Perú.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(130, 1, 0, 4, 83, 7, 'La estudiante presenta dificultades para apreciar de manera crítica manifestaciones artístico-culturales. No logra analizar ni emitir juicios fundamentados sobre obras artísticas o expresiones culturales de su entorno. Muestra desinterés por el arte y las manifestaciones culturales locales. Se recomienda visitar el Museo Regional de Áncash, participar en actividades culturales de Huaraz, explorar el arte andino y ver documentales sobre manifestaciones culturales del Perú.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(134, 1, 0, 1, 84, 7, 'La estudiante muestra dificultades para crear proyectos desde los lenguajes artístico-culturales. Sus producciones carecen de creatividad, planificación y uso apropiado de los elementos expresivos propios de cada lenguaje artístico. No logra comunicar ideas o emociones de forma efectiva a través del arte. Se sugiere practicar dibujo, pintura o danza en casa, explorar técnicas artísticas sencillas disponibles en internet y participar en talleres culturales de la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(135, 1, 0, 2, 84, 7, 'La estudiante muestra dificultades para crear proyectos desde los lenguajes artístico-culturales. Sus producciones carecen de creatividad, planificación y uso apropiado de los elementos expresivos propios de cada lenguaje artístico. No logra comunicar ideas o emociones de forma efectiva a través del arte. Se sugiere practicar dibujo, pintura o danza en casa, explorar técnicas artísticas sencillas disponibles en internet y participar en talleres culturales de la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(136, 1, 0, 3, 84, 7, 'La estudiante muestra dificultades para crear proyectos desde los lenguajes artístico-culturales. Sus producciones carecen de creatividad, planificación y uso apropiado de los elementos expresivos propios de cada lenguaje artístico. No logra comunicar ideas o emociones de forma efectiva a través del arte. Se sugiere practicar dibujo, pintura o danza en casa, explorar técnicas artísticas sencillas disponibles en internet y participar en talleres culturales de la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(137, 1, 0, 4, 84, 7, 'La estudiante muestra dificultades para crear proyectos desde los lenguajes artístico-culturales. Sus producciones carecen de creatividad, planificación y uso apropiado de los elementos expresivos propios de cada lenguaje artístico. No logra comunicar ideas o emociones de forma efectiva a través del arte. Se sugiere practicar dibujo, pintura o danza en casa, explorar técnicas artísticas sencillas disponibles en internet y participar en talleres culturales de la comunidad.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(141, 1, 0, 1, 93, 6, 'La estudiante presenta dificultades para comunicarse oralmente con coherencia y fluidez en su lengua materna. No logra expresar ideas de forma organizada en conversaciones ni exposiciones orales, muestra inseguridad al hablar ante el grupo y emplea vocabulario muy reducido. Se recomienda practicar la lectura en voz alta, participar en diálogos familiares sobre temas cotidianos, escuchar audios educativos y realizar pequeñas exposiciones en casa para ganar confianza y soltura.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(142, 1, 0, 2, 93, 6, 'La estudiante presenta dificultades para comunicarse oralmente con coherencia y fluidez en su lengua materna. No logra expresar ideas de forma organizada en conversaciones ni exposiciones orales, muestra inseguridad al hablar ante el grupo y emplea vocabulario muy reducido. Se recomienda practicar la lectura en voz alta, participar en diálogos familiares sobre temas cotidianos, escuchar audios educativos y realizar pequeñas exposiciones en casa para ganar confianza y soltura.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(143, 1, 0, 3, 93, 6, 'La estudiante presenta dificultades para comunicarse oralmente con coherencia y fluidez en su lengua materna. No logra expresar ideas de forma organizada en conversaciones ni exposiciones orales, muestra inseguridad al hablar ante el grupo y emplea vocabulario muy reducido. Se recomienda practicar la lectura en voz alta, participar en diálogos familiares sobre temas cotidianos, escuchar audios educativos y realizar pequeñas exposiciones en casa para ganar confianza y soltura.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(144, 1, 0, 4, 93, 6, 'La estudiante presenta dificultades para comunicarse oralmente con coherencia y fluidez en su lengua materna. No logra expresar ideas de forma organizada en conversaciones ni exposiciones orales, muestra inseguridad al hablar ante el grupo y emplea vocabulario muy reducido. Se recomienda practicar la lectura en voz alta, participar en diálogos familiares sobre temas cotidianos, escuchar audios educativos y realizar pequeñas exposiciones en casa para ganar confianza y soltura.', '2026-05-11 22:46:03', '2026-05-11 22:46:45', 2),
(148, 1, 0, 1, 94, 7, 'La estudiante muestra serias dificultades para leer y comprender diversos tipos de textos escritos en su lengua materna. No identifica la información explícita ni realiza inferencias básicas sobre lo que lee. Su nivel de comprensión lectora está significativamente por debajo de lo esperado para su grado. Se recomienda leer diariamente textos breves de su interés, aplicar estrategias de subrayado y resumen, y participar activamente en el plan lector del colegio cada semana.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(149, 1, 0, 2, 94, 7, 'La estudiante muestra serias dificultades para leer y comprender diversos tipos de textos escritos en su lengua materna. No identifica la información explícita ni realiza inferencias básicas sobre lo que lee. Su nivel de comprensión lectora está significativamente por debajo de lo esperado para su grado. Se recomienda leer diariamente textos breves de su interés, aplicar estrategias de subrayado y resumen, y participar activamente en el plan lector del colegio cada semana.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(150, 1, 0, 3, 94, 7, 'La estudiante muestra serias dificultades para leer y comprender diversos tipos de textos escritos en su lengua materna. No identifica la información explícita ni realiza inferencias básicas sobre lo que lee. Su nivel de comprensión lectora está significativamente por debajo de lo esperado para su grado. Se recomienda leer diariamente textos breves de su interés, aplicar estrategias de subrayado y resumen, y participar activamente en el plan lector del colegio cada semana.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(151, 1, 0, 4, 94, 7, 'La estudiante muestra serias dificultades para leer y comprender diversos tipos de textos escritos en su lengua materna. No identifica la información explícita ni realiza inferencias básicas sobre lo que lee. Su nivel de comprensión lectora está significativamente por debajo de lo esperado para su grado. Se recomienda leer diariamente textos breves de su interés, aplicar estrategias de subrayado y resumen, y participar activamente en el plan lector del colegio cada semana.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(155, 1, 0, 1, 95, 5, 'La estudiante presenta dificultades significativas para escribir diversos tipos de textos en su lengua materna. Sus producciones escritas evidencian graves errores de coherencia, cohesión, ortografía y puntuación. No logra planificar ni revisar sus textos antes de entregarlos. Se recomienda practicar la escritura diaria mediante redacciones cortas, leer textos modelo de distintos tipos, revisar normas ortográficas básicas y solicitar retroalimentación continua del docente.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(156, 1, 0, 2, 95, 5, 'La estudiante presenta dificultades significativas para escribir diversos tipos de textos en su lengua materna. Sus producciones escritas evidencian graves errores de coherencia, cohesión, ortografía y puntuación. No logra planificar ni revisar sus textos antes de entregarlos. Se recomienda practicar la escritura diaria mediante redacciones cortas, leer textos modelo de distintos tipos, revisar normas ortográficas básicas y solicitar retroalimentación continua del docente.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(157, 1, 0, 3, 95, 5, 'La estudiante presenta dificultades significativas para escribir diversos tipos de textos en su lengua materna. Sus producciones escritas evidencian graves errores de coherencia, cohesión, ortografía y puntuación. No logra planificar ni revisar sus textos antes de entregarlos. Se recomienda practicar la escritura diaria mediante redacciones cortas, leer textos modelo de distintos tipos, revisar normas ortográficas básicas y solicitar retroalimentación continua del docente.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(158, 1, 0, 4, 95, 5, 'La estudiante presenta dificultades significativas para escribir diversos tipos de textos en su lengua materna. Sus producciones escritas evidencian graves errores de coherencia, cohesión, ortografía y puntuación. No logra planificar ni revisar sus textos antes de entregarlos. Se recomienda practicar la escritura diaria mediante redacciones cortas, leer textos modelo de distintos tipos, revisar normas ortográficas básicas y solicitar retroalimentación continua del docente.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(162, 1, 0, 1, 85, 5, 'La estudiante no ha alcanzado el nivel esperado en comunicación en inglés como lengua extranjera. Presenta dificultades para comprender y producir textos orales y escritos básicos del idioma. Su vocabulario en inglés es muy reducido y muestra escaso dominio de estructuras gramaticales elementales. Se recomienda el uso diario de aplicaciones de aprendizaje de idiomas, practicar vocabulario básico mediante canciones en inglés, videos cortos y ejercicios interactivos en línea.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(163, 1, 0, 2, 85, 5, 'La estudiante no ha alcanzado el nivel esperado en comunicación en inglés como lengua extranjera. Presenta dificultades para comprender y producir textos orales y escritos básicos del idioma. Su vocabulario en inglés es muy reducido y muestra escaso dominio de estructuras gramaticales elementales. Se recomienda el uso diario de aplicaciones de aprendizaje de idiomas, practicar vocabulario básico mediante canciones en inglés, videos cortos y ejercicios interactivos en línea.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(164, 1, 0, 3, 85, 5, 'La estudiante no ha alcanzado el nivel esperado en comunicación en inglés como lengua extranjera. Presenta dificultades para comprender y producir textos orales y escritos básicos del idioma. Su vocabulario en inglés es muy reducido y muestra escaso dominio de estructuras gramaticales elementales. Se recomienda el uso diario de aplicaciones de aprendizaje de idiomas, practicar vocabulario básico mediante canciones en inglés, videos cortos y ejercicios interactivos en línea.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(165, 1, 0, 4, 85, 5, 'La estudiante no ha alcanzado el nivel esperado en comunicación en inglés como lengua extranjera. Presenta dificultades para comprender y producir textos orales y escritos básicos del idioma. Su vocabulario en inglés es muy reducido y muestra escaso dominio de estructuras gramaticales elementales. Se recomienda el uso diario de aplicaciones de aprendizaje de idiomas, practicar vocabulario básico mediante canciones en inglés, videos cortos y ejercicios interactivos en línea.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(169, 1, 1, 2, 96, 7, 'La estudiante presenta serias dificultades para resolver problemas de cantidad. No logra aplicar estrategias de cálculo aritmético básico ni comprender el sistema de numeración de manera adecuada. Sus errores son frecuentes en operaciones con números naturales, fracciones y decimales. Se recomienda reforzar las operaciones básicas con material concreto en casa, practicar el cálculo mental a diario y solicitar apoyo de tutoría para lograr una nivelación matemática efectiva.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(170, 1, 1, 3, 96, 7, 'La estudiante presenta serias dificultades para resolver problemas de cantidad. No logra aplicar estrategias de cálculo aritmético básico ni comprender el sistema de numeración de manera adecuada. Sus errores son frecuentes en operaciones con números naturales, fracciones y decimales. Se recomienda reforzar las operaciones básicas con material concreto en casa, practicar el cálculo mental a diario y solicitar apoyo de tutoría para lograr una nivelación matemática efectiva.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(171, 1, 1, 4, 96, 7, 'La estudiante presenta serias dificultades para resolver problemas de cantidad. No logra aplicar estrategias de cálculo aritmético básico ni comprender el sistema de numeración de manera adecuada. Sus errores son frecuentes en operaciones con números naturales, fracciones y decimales. Se recomienda reforzar las operaciones básicas con material concreto en casa, practicar el cálculo mental a diario y solicitar apoyo de tutoría para lograr una nivelación matemática efectiva.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(172, 1, 2, 2, 97, 6, 'La estudiante presenta dificultades para resolver problemas de regularidad, equivalencia y cambio. No logra identificar patrones, trabajar con expresiones algebraicas simples ni resolver ecuaciones de primer grado. Sus errores reflejan falta de comprensión conceptual básica en álgebra. Se recomienda trabajar con material visual y concreto, practicar problemas de forma progresiva, utilizar recursos interactivos de matemáticas en línea y pedir apoyo de tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(173, 1, 2, 3, 97, 6, 'La estudiante presenta dificultades para resolver problemas de regularidad, equivalencia y cambio. No logra identificar patrones, trabajar con expresiones algebraicas simples ni resolver ecuaciones de primer grado. Sus errores reflejan falta de comprensión conceptual básica en álgebra. Se recomienda trabajar con material visual y concreto, practicar problemas de forma progresiva, utilizar recursos interactivos de matemáticas en línea y pedir apoyo de tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(174, 1, 2, 4, 97, 6, 'La estudiante presenta dificultades para resolver problemas de regularidad, equivalencia y cambio. No logra identificar patrones, trabajar con expresiones algebraicas simples ni resolver ecuaciones de primer grado. Sus errores reflejan falta de comprensión conceptual básica en álgebra. Se recomienda trabajar con material visual y concreto, practicar problemas de forma progresiva, utilizar recursos interactivos de matemáticas en línea y pedir apoyo de tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(175, 1, 0, 1, 98, 7, 'La estudiante muestra dificultades para resolver problemas de forma, movimiento y localización. No logra identificar figuras geométricas básicas ni calcular perímetros y áreas elementales. Presenta escasa comprensión de conceptos espaciales requeridos para su nivel. Se recomienda practicar con figuras geométricas concretas en casa, utilizar aplicaciones de geometría interactiva, reforzar con ejercicios progresivos de nivel básico y solicitar apoyo en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(176, 1, 0, 2, 98, 7, 'La estudiante muestra dificultades para resolver problemas de forma, movimiento y localización. No logra identificar figuras geométricas básicas ni calcular perímetros y áreas elementales. Presenta escasa comprensión de conceptos espaciales requeridos para su nivel. Se recomienda practicar con figuras geométricas concretas en casa, utilizar aplicaciones de geometría interactiva, reforzar con ejercicios progresivos de nivel básico y solicitar apoyo en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(177, 1, 0, 3, 98, 7, 'La estudiante muestra dificultades para resolver problemas de forma, movimiento y localización. No logra identificar figuras geométricas básicas ni calcular perímetros y áreas elementales. Presenta escasa comprensión de conceptos espaciales requeridos para su nivel. Se recomienda practicar con figuras geométricas concretas en casa, utilizar aplicaciones de geometría interactiva, reforzar con ejercicios progresivos de nivel básico y solicitar apoyo en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(178, 1, 0, 4, 98, 7, 'La estudiante muestra dificultades para resolver problemas de forma, movimiento y localización. No logra identificar figuras geométricas básicas ni calcular perímetros y áreas elementales. Presenta escasa comprensión de conceptos espaciales requeridos para su nivel. Se recomienda practicar con figuras geométricas concretas en casa, utilizar aplicaciones de geometría interactiva, reforzar con ejercicios progresivos de nivel básico y solicitar apoyo en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(182, 1, 0, 1, 99, 6, 'La estudiante presenta dificultades para resolver problemas de gestión de datos e incertidumbre. No logra recopilar, organizar ni interpretar información estadística básica. Le cuesta representar datos en tablas o gráficos simples y obtener conclusiones a partir de ellos. Se recomienda practicar ejercicios de estadística descriptiva básica, interpretar gráficos de la vida cotidiana, trabajar con datos reales del entorno familiar y solicitar refuerzo en tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(183, 1, 0, 2, 99, 6, 'La estudiante presenta dificultades para resolver problemas de gestión de datos e incertidumbre. No logra recopilar, organizar ni interpretar información estadística básica. Le cuesta representar datos en tablas o gráficos simples y obtener conclusiones a partir de ellos. Se recomienda practicar ejercicios de estadística descriptiva básica, interpretar gráficos de la vida cotidiana, trabajar con datos reales del entorno familiar y solicitar refuerzo en tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(184, 1, 0, 3, 99, 6, 'La estudiante presenta dificultades para resolver problemas de gestión de datos e incertidumbre. No logra recopilar, organizar ni interpretar información estadística básica. Le cuesta representar datos en tablas o gráficos simples y obtener conclusiones a partir de ellos. Se recomienda practicar ejercicios de estadística descriptiva básica, interpretar gráficos de la vida cotidiana, trabajar con datos reales del entorno familiar y solicitar refuerzo en tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(185, 1, 0, 4, 99, 6, 'La estudiante presenta dificultades para resolver problemas de gestión de datos e incertidumbre. No logra recopilar, organizar ni interpretar información estadística básica. Le cuesta representar datos en tablas o gráficos simples y obtener conclusiones a partir de ellos. Se recomienda practicar ejercicios de estadística descriptiva básica, interpretar gráficos de la vida cotidiana, trabajar con datos reales del entorno familiar y solicitar refuerzo en tutoría escolar.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(189, 1, 0, 1, 100, 8, 'La estudiante presenta dificultades para indagar mediante métodos científicos. No logra formular preguntas de investigación, planificar procedimientos ni registrar observaciones con orden y precisión. Muestra poco interés por la ciencia y escaso dominio del método científico. Se recomienda realizar experimentos sencillos en casa con materiales cotidianos, observar fenómenos naturales del entorno, llevar un cuaderno de ciencias y ver videos educativos de ciencias naturales.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(190, 1, 0, 2, 100, 8, 'La estudiante presenta dificultades para indagar mediante métodos científicos. No logra formular preguntas de investigación, planificar procedimientos ni registrar observaciones con orden y precisión. Muestra poco interés por la ciencia y escaso dominio del método científico. Se recomienda realizar experimentos sencillos en casa con materiales cotidianos, observar fenómenos naturales del entorno, llevar un cuaderno de ciencias y ver videos educativos de ciencias naturales.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(191, 1, 0, 3, 100, 8, 'La estudiante presenta dificultades para indagar mediante métodos científicos. No logra formular preguntas de investigación, planificar procedimientos ni registrar observaciones con orden y precisión. Muestra poco interés por la ciencia y escaso dominio del método científico. Se recomienda realizar experimentos sencillos en casa con materiales cotidianos, observar fenómenos naturales del entorno, llevar un cuaderno de ciencias y ver videos educativos de ciencias naturales.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(192, 1, 0, 4, 100, 8, 'La estudiante presenta dificultades para indagar mediante métodos científicos. No logra formular preguntas de investigación, planificar procedimientos ni registrar observaciones con orden y precisión. Muestra poco interés por la ciencia y escaso dominio del método científico. Se recomienda realizar experimentos sencillos en casa con materiales cotidianos, observar fenómenos naturales del entorno, llevar un cuaderno de ciencias y ver videos educativos de ciencias naturales.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(196, 1, 0, 1, 101, 7, 'La estudiante muestra dificultades para explicar el mundo físico basándose en conocimientos científicos sobre materia, energía y seres vivos. No logra relacionar conceptos con fenómenos cotidianos ni usa terminología básica con precisión. Se recomienda revisar contenidos de ciencias con videos educativos accesibles, leer resúmenes sobre biología y química básica, practicar ejercicios de aplicación conceptual y solicitar apoyo a su docente en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(197, 1, 0, 2, 101, 7, 'La estudiante muestra dificultades para explicar el mundo físico basándose en conocimientos científicos sobre materia, energía y seres vivos. No logra relacionar conceptos con fenómenos cotidianos ni usa terminología básica con precisión. Se recomienda revisar contenidos de ciencias con videos educativos accesibles, leer resúmenes sobre biología y química básica, practicar ejercicios de aplicación conceptual y solicitar apoyo a su docente en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(198, 1, 0, 3, 101, 7, 'La estudiante muestra dificultades para explicar el mundo físico basándose en conocimientos científicos sobre materia, energía y seres vivos. No logra relacionar conceptos con fenómenos cotidianos ni usa terminología básica con precisión. Se recomienda revisar contenidos de ciencias con videos educativos accesibles, leer resúmenes sobre biología y química básica, practicar ejercicios de aplicación conceptual y solicitar apoyo a su docente en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(199, 1, 0, 4, 101, 7, 'La estudiante muestra dificultades para explicar el mundo físico basándose en conocimientos científicos sobre materia, energía y seres vivos. No logra relacionar conceptos con fenómenos cotidianos ni usa terminología básica con precisión. Se recomienda revisar contenidos de ciencias con videos educativos accesibles, leer resúmenes sobre biología y química básica, practicar ejercicios de aplicación conceptual y solicitar apoyo a su docente en horas de tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(203, 1, 0, 1, 102, 6, 'La estudiante presenta dificultades para diseñar y construir soluciones tecnológicas ante problemas de su entorno. No logra planificar procesos tecnológicos ni seleccionar materiales adecuados para sus proyectos. Sus producciones carecen de planificación y no responden a los requerimientos del nivel. Se recomienda explorar proyectos tecnológicos sencillos en casa, ver tutoriales de construcción básica, practicar manualidades funcionales y reforzar los contenidos con tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(204, 1, 0, 2, 102, 6, 'La estudiante presenta dificultades para diseñar y construir soluciones tecnológicas ante problemas de su entorno. No logra planificar procesos tecnológicos ni seleccionar materiales adecuados para sus proyectos. Sus producciones carecen de planificación y no responden a los requerimientos del nivel. Se recomienda explorar proyectos tecnológicos sencillos en casa, ver tutoriales de construcción básica, practicar manualidades funcionales y reforzar los contenidos con tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(205, 1, 0, 3, 102, 6, 'La estudiante presenta dificultades para diseñar y construir soluciones tecnológicas ante problemas de su entorno. No logra planificar procesos tecnológicos ni seleccionar materiales adecuados para sus proyectos. Sus producciones carecen de planificación y no responden a los requerimientos del nivel. Se recomienda explorar proyectos tecnológicos sencillos en casa, ver tutoriales de construcción básica, practicar manualidades funcionales y reforzar los contenidos con tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(206, 1, 0, 4, 102, 6, 'La estudiante presenta dificultades para diseñar y construir soluciones tecnológicas ante problemas de su entorno. No logra planificar procesos tecnológicos ni seleccionar materiales adecuados para sus proyectos. Sus producciones carecen de planificación y no responden a los requerimientos del nivel. Se recomienda explorar proyectos tecnológicos sencillos en casa, ver tutoriales de construcción básica, practicar manualidades funcionales y reforzar los contenidos con tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2);
INSERT INTO `calificaciones` (`id`, `matricula_id`, `carga_id`, `periodo_id`, `competencia_id`, `nota_numerica`, `conclusion_descriptiva`, `registrado_en`, `modificado_en`, `registrado_por`) VALUES
(210, 1, 0, 1, 86, 9, 'La estudiante presenta dificultades para construir su identidad como persona humana amada por Dios. No logra reflexionar sobre su dignidad, libertad y trascendencia desde una perspectiva de fe. Muestra escasa capacidad para relacionar los valores religiosos con su vida cotidiana. Se recomienda propiciar momentos de reflexión personal en familia, leer textos religiosos adecuados para adolescentes y dialogar sobre la importancia de los valores espirituales en la vida.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(211, 1, 0, 2, 86, 9, 'La estudiante presenta dificultades para construir su identidad como persona humana amada por Dios. No logra reflexionar sobre su dignidad, libertad y trascendencia desde una perspectiva de fe. Muestra escasa capacidad para relacionar los valores religiosos con su vida cotidiana. Se recomienda propiciar momentos de reflexión personal en familia, leer textos religiosos adecuados para adolescentes y dialogar sobre la importancia de los valores espirituales en la vida.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(212, 1, 0, 3, 86, 9, 'La estudiante presenta dificultades para construir su identidad como persona humana amada por Dios. No logra reflexionar sobre su dignidad, libertad y trascendencia desde una perspectiva de fe. Muestra escasa capacidad para relacionar los valores religiosos con su vida cotidiana. Se recomienda propiciar momentos de reflexión personal en familia, leer textos religiosos adecuados para adolescentes y dialogar sobre la importancia de los valores espirituales en la vida.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(213, 1, 0, 4, 86, 9, 'La estudiante presenta dificultades para construir su identidad como persona humana amada por Dios. No logra reflexionar sobre su dignidad, libertad y trascendencia desde una perspectiva de fe. Muestra escasa capacidad para relacionar los valores religiosos con su vida cotidiana. Se recomienda propiciar momentos de reflexión personal en familia, leer textos religiosos adecuados para adolescentes y dialogar sobre la importancia de los valores espirituales en la vida.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(217, 1, 0, 1, 87, 8, 'La estudiante muestra dificultades para asumir la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida. No logra relacionar los principios religiosos aprendidos con sus decisiones cotidianas ni participa activamente en celebraciones comunitarias. Se recomienda fomentar la práctica religiosa en familia, participar en actividades parroquiales, leer sobre espiritualidad para adolescentes y reflexionar sobre su proyecto de vida personal.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(218, 1, 0, 2, 87, 8, 'La estudiante muestra dificultades para asumir la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida. No logra relacionar los principios religiosos aprendidos con sus decisiones cotidianas ni participa activamente en celebraciones comunitarias. Se recomienda fomentar la práctica religiosa en familia, participar en actividades parroquiales, leer sobre espiritualidad para adolescentes y reflexionar sobre su proyecto de vida personal.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(219, 1, 0, 3, 87, 8, 'La estudiante muestra dificultades para asumir la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida. No logra relacionar los principios religiosos aprendidos con sus decisiones cotidianas ni participa activamente en celebraciones comunitarias. Se recomienda fomentar la práctica religiosa en familia, participar en actividades parroquiales, leer sobre espiritualidad para adolescentes y reflexionar sobre su proyecto de vida personal.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(220, 1, 0, 4, 87, 8, 'La estudiante muestra dificultades para asumir la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida. No logra relacionar los principios religiosos aprendidos con sus decisiones cotidianas ni participa activamente en celebraciones comunitarias. Se recomienda fomentar la práctica religiosa en familia, participar en actividades parroquiales, leer sobre espiritualidad para adolescentes y reflexionar sobre su proyecto de vida personal.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(224, 1, 0, 1, 88, 8, 'La estudiante presenta dificultades para gestionar proyectos de emprendimiento económico o social. No logra identificar oportunidades, planificar acciones ni evaluar los recursos disponibles en su entorno. Muestra escaso dominio de conceptos básicos de economía y empresa. Se recomienda explorar historias de emprendimiento local de Huaraz, participar en ferias escolares, diseñar pequeños proyectos en casa con apoyo familiar y ver contenidos sobre emprendimiento juvenil.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(225, 1, 0, 2, 88, 8, 'La estudiante presenta dificultades para gestionar proyectos de emprendimiento económico o social. No logra identificar oportunidades, planificar acciones ni evaluar los recursos disponibles en su entorno. Muestra escaso dominio de conceptos básicos de economía y empresa. Se recomienda explorar historias de emprendimiento local de Huaraz, participar en ferias escolares, diseñar pequeños proyectos en casa con apoyo familiar y ver contenidos sobre emprendimiento juvenil.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(226, 1, 0, 3, 88, 8, 'La estudiante presenta dificultades para gestionar proyectos de emprendimiento económico o social. No logra identificar oportunidades, planificar acciones ni evaluar los recursos disponibles en su entorno. Muestra escaso dominio de conceptos básicos de economía y empresa. Se recomienda explorar historias de emprendimiento local de Huaraz, participar en ferias escolares, diseñar pequeños proyectos en casa con apoyo familiar y ver contenidos sobre emprendimiento juvenil.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(227, 1, 0, 4, 88, 8, 'La estudiante presenta dificultades para gestionar proyectos de emprendimiento económico o social. No logra identificar oportunidades, planificar acciones ni evaluar los recursos disponibles en su entorno. Muestra escaso dominio de conceptos básicos de economía y empresa. Se recomienda explorar historias de emprendimiento local de Huaraz, participar en ferias escolares, diseñar pequeños proyectos en casa con apoyo familiar y ver contenidos sobre emprendimiento juvenil.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(231, 1, 0, 1, 89, 6, 'La estudiante se encuentra en nivel de inicio en el desarrollo del razonamiento matemático aplicado. Presenta serias dificultades para resolver situaciones que requieren análisis lógico, pensamiento abstracto y aplicación de estrategias matemáticas no rutinarias. Sus respuestas evidencian falta de comprensión del enunciado y ausencia de procedimientos. Se recomienda practicar ejercicios de lógica básica en casa, usar juegos matemáticos y solicitar apoyo en tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(232, 1, 0, 2, 89, 6, 'La estudiante se encuentra en nivel de inicio en el desarrollo del razonamiento matemático aplicado. Presenta serias dificultades para resolver situaciones que requieren análisis lógico, pensamiento abstracto y aplicación de estrategias matemáticas no rutinarias. Sus respuestas evidencian falta de comprensión del enunciado y ausencia de procedimientos. Se recomienda practicar ejercicios de lógica básica en casa, usar juegos matemáticos y solicitar apoyo en tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(233, 1, 0, 3, 89, 6, 'La estudiante se encuentra en nivel de inicio en el desarrollo del razonamiento matemático aplicado. Presenta serias dificultades para resolver situaciones que requieren análisis lógico, pensamiento abstracto y aplicación de estrategias matemáticas no rutinarias. Sus respuestas evidencian falta de comprensión del enunciado y ausencia de procedimientos. Se recomienda practicar ejercicios de lógica básica en casa, usar juegos matemáticos y solicitar apoyo en tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(234, 1, 0, 4, 89, 6, 'La estudiante se encuentra en nivel de inicio en el desarrollo del razonamiento matemático aplicado. Presenta serias dificultades para resolver situaciones que requieren análisis lógico, pensamiento abstracto y aplicación de estrategias matemáticas no rutinarias. Sus respuestas evidencian falta de comprensión del enunciado y ausencia de procedimientos. Se recomienda practicar ejercicios de lógica básica en casa, usar juegos matemáticos y solicitar apoyo en tutoría.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(238, 1, 0, 1, 103, 7, 'La estudiante presenta dificultades para desenvolverse en entornos virtuales generados por las TIC. No logra usar herramientas digitales básicas de forma segura ni aprovecharlas para su aprendizaje. Muestra escasa competencia digital para buscar información confiable, comunicarse y crear contenidos simples. Se recomienda orientarla en el uso responsable de dispositivos e internet, guiarla para explorar plataformas educativas digitales y practicar habilidades básicas de informática.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(239, 1, 0, 2, 103, 7, 'La estudiante presenta dificultades para desenvolverse en entornos virtuales generados por las TIC. No logra usar herramientas digitales básicas de forma segura ni aprovecharlas para su aprendizaje. Muestra escasa competencia digital para buscar información confiable, comunicarse y crear contenidos simples. Se recomienda orientarla en el uso responsable de dispositivos e internet, guiarla para explorar plataformas educativas digitales y practicar habilidades básicas de informática.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(240, 1, 0, 3, 103, 7, 'La estudiante presenta dificultades para desenvolverse en entornos virtuales generados por las TIC. No logra usar herramientas digitales básicas de forma segura ni aprovecharlas para su aprendizaje. Muestra escasa competencia digital para buscar información confiable, comunicarse y crear contenidos simples. Se recomienda orientarla en el uso responsable de dispositivos e internet, guiarla para explorar plataformas educativas digitales y practicar habilidades básicas de informática.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(241, 1, 0, 4, 103, 7, 'La estudiante presenta dificultades para desenvolverse en entornos virtuales generados por las TIC. No logra usar herramientas digitales básicas de forma segura ni aprovecharlas para su aprendizaje. Muestra escasa competencia digital para buscar información confiable, comunicarse y crear contenidos simples. Se recomienda orientarla en el uso responsable de dispositivos e internet, guiarla para explorar plataformas educativas digitales y practicar habilidades básicas de informática.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(245, 1, 0, 1, 104, 6, 'La estudiante muestra dificultades para gestionar su aprendizaje de manera autónoma. No organiza su tiempo de estudio, no establece metas claras ni reflexiona sobre sus avances y dificultades. Depende excesivamente de la orientación del docente y no aplica estrategias propias de aprendizaje. Se recomienda establecer horarios de estudio fijos en casa, usar una agenda para organizar tareas, practicar técnicas básicas de estudio y reflexionar diariamente sobre sus metas.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(246, 1, 0, 2, 104, 6, 'La estudiante muestra dificultades para gestionar su aprendizaje de manera autónoma. No organiza su tiempo de estudio, no establece metas claras ni reflexiona sobre sus avances y dificultades. Depende excesivamente de la orientación del docente y no aplica estrategias propias de aprendizaje. Se recomienda establecer horarios de estudio fijos en casa, usar una agenda para organizar tareas, practicar técnicas básicas de estudio y reflexionar diariamente sobre sus metas.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(247, 1, 0, 3, 104, 6, 'La estudiante muestra dificultades para gestionar su aprendizaje de manera autónoma. No organiza su tiempo de estudio, no establece metas claras ni reflexiona sobre sus avances y dificultades. Depende excesivamente de la orientación del docente y no aplica estrategias propias de aprendizaje. Se recomienda establecer horarios de estudio fijos en casa, usar una agenda para organizar tareas, practicar técnicas básicas de estudio y reflexionar diariamente sobre sus metas.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(248, 1, 0, 4, 104, 6, 'La estudiante muestra dificultades para gestionar su aprendizaje de manera autónoma. No organiza su tiempo de estudio, no establece metas claras ni reflexiona sobre sus avances y dificultades. Depende excesivamente de la orientación del docente y no aplica estrategias propias de aprendizaje. Se recomienda establecer horarios de estudio fijos en casa, usar una agenda para organizar tareas, practicar técnicas básicas de estudio y reflexionar diariamente sobre sus metas.', '2026-05-11 22:46:04', '2026-05-11 22:46:45', 2),
(279, 1, 24, 1, 102, 14, NULL, '2026-05-12 00:15:27', NULL, 17),
(280, 2, 24, 1, 102, 14, NULL, '2026-05-12 00:15:27', NULL, 17),
(281, 3, 24, 1, 102, 14, NULL, '2026-05-12 00:15:27', NULL, 17),
(282, 4, 24, 1, 102, 14, NULL, '2026-05-12 00:15:27', NULL, 17),
(283, 5, 24, 1, 102, 14, NULL, '2026-05-12 00:15:27', NULL, 17),
(284, 127, 8, 1, 97, 14, NULL, '2026-05-12 00:57:38', '2026-05-12 00:59:23', 2),
(285, 128, 8, 1, 97, 14, NULL, '2026-05-12 00:57:38', '2026-05-12 00:59:23', 2),
(286, 129, 8, 1, 97, 15, NULL, '2026-05-12 00:57:38', '2026-05-12 00:59:23', 2),
(287, 130, 8, 1, 97, 15, NULL, '2026-05-12 00:57:38', '2026-05-12 00:59:23', 2),
(288, 131, 8, 1, 97, 15, NULL, '2026-05-12 00:57:38', '2026-05-12 00:59:23', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_criterio`
--

CREATE TABLE `calificaciones_criterio` (
  `id` int(10) UNSIGNED NOT NULL,
  `criterio_id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `nota` tinyint(3) UNSIGNED NOT NULL,
  `registrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `modificado_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calificaciones_criterio`
--

INSERT INTO `calificaciones_criterio` (`id`, `criterio_id`, `matricula_id`, `nota`, `registrado_en`, `modificado_en`) VALUES
(1, 2, 1, 15, '2026-05-11 22:14:24', NULL),
(2, 2, 2, 15, '2026-05-11 22:14:24', NULL),
(3, 2, 3, 15, '2026-05-11 22:14:24', NULL),
(4, 2, 4, 15, '2026-05-11 22:14:24', NULL),
(5, 2, 5, 14, '2026-05-11 22:14:24', NULL),
(6, 3, 92, 15, '2026-05-11 22:17:04', NULL),
(7, 3, 93, 14, '2026-05-11 22:17:04', NULL),
(8, 3, 94, 14, '2026-05-11 22:17:04', NULL),
(9, 3, 95, 14, '2026-05-11 22:17:04', NULL),
(10, 3, 96, 14, '2026-05-11 22:17:04', NULL),
(11, 4, 99, 14, '2026-05-11 22:18:04', NULL),
(12, 4, 100, 14, '2026-05-11 22:18:04', NULL),
(13, 4, 101, 13, '2026-05-11 22:18:04', NULL),
(14, 4, 102, 16, '2026-05-11 22:18:04', NULL),
(15, 4, 103, 15, '2026-05-11 22:18:04', NULL),
(16, 5, 106, 14, '2026-05-11 22:20:03', '2026-05-11 22:22:49'),
(17, 5, 107, 15, '2026-05-11 22:20:03', '2026-05-11 22:22:49'),
(18, 5, 108, 14, '2026-05-11 22:20:03', '2026-05-11 22:22:49'),
(19, 5, 109, 15, '2026-05-11 22:20:03', '2026-05-11 22:22:49'),
(20, 5, 110, 14, '2026-05-11 22:20:03', '2026-05-11 22:22:49'),
(26, 6, 113, 13, '2026-05-11 22:23:02', NULL),
(27, 6, 114, 13, '2026-05-11 22:23:02', NULL),
(28, 6, 115, 13, '2026-05-11 22:23:02', NULL),
(29, 6, 116, 13, '2026-05-11 22:23:02', NULL),
(30, 6, 117, 13, '2026-05-11 22:23:02', NULL),
(31, 7, 120, 15, '2026-05-11 22:23:15', '2026-05-11 22:23:48'),
(32, 7, 121, 15, '2026-05-11 22:23:15', '2026-05-11 22:23:48'),
(33, 7, 122, 15, '2026-05-11 22:23:15', '2026-05-11 22:23:48'),
(34, 7, 123, 13, '2026-05-11 22:23:15', '2026-05-11 22:23:48'),
(35, 7, 124, 14, '2026-05-11 22:23:15', '2026-05-11 22:23:48'),
(41, 8, 127, 14, '2026-05-11 22:24:04', NULL),
(42, 8, 128, 13, '2026-05-11 22:24:04', NULL),
(43, 8, 129, 14, '2026-05-11 22:24:04', NULL),
(44, 8, 130, 14, '2026-05-11 22:24:04', NULL),
(45, 8, 131, 14, '2026-05-11 22:24:04', NULL),
(46, 9, 134, 15, '2026-05-11 22:31:16', NULL),
(47, 9, 135, 15, '2026-05-11 22:31:16', NULL),
(48, 9, 136, 14, '2026-05-11 22:31:16', NULL),
(49, 9, 137, 15, '2026-05-11 22:31:16', NULL),
(50, 9, 138, 15, '2026-05-11 22:31:16', NULL),
(51, 10, 141, 15, '2026-05-11 22:32:18', NULL),
(52, 10, 142, 15, '2026-05-11 22:32:18', NULL),
(53, 10, 143, 14, '2026-05-11 22:32:18', NULL),
(54, 10, 144, 14, '2026-05-11 22:32:18', NULL),
(55, 10, 145, 14, '2026-05-11 22:32:18', NULL),
(56, 11, 148, 13, '2026-05-11 22:32:34', NULL),
(57, 11, 149, 14, '2026-05-11 22:32:34', NULL),
(58, 11, 150, 14, '2026-05-11 22:32:34', NULL),
(59, 11, 151, 14, '2026-05-11 22:32:34', NULL),
(60, 11, 152, 14, '2026-05-11 22:32:34', NULL),
(61, 12, 155, 15, '2026-05-11 22:32:46', NULL),
(62, 12, 156, 15, '2026-05-11 22:32:46', NULL),
(63, 12, 157, 14, '2026-05-11 22:32:46', NULL),
(64, 12, 158, 14, '2026-05-11 22:32:46', NULL),
(65, 12, 159, 15, '2026-05-11 22:32:46', NULL),
(66, 1, 1, 14, '2026-05-11 22:33:34', NULL),
(67, 1, 2, 14, '2026-05-11 22:33:34', NULL),
(68, 1, 3, 15, '2026-05-11 22:33:34', NULL),
(69, 1, 4, 15, '2026-05-11 22:33:34', NULL),
(70, 1, 5, 14, '2026-05-11 22:33:34', NULL),
(71, 13, 1, 14, '2026-05-12 00:15:27', NULL),
(72, 13, 2, 14, '2026-05-12 00:15:27', NULL),
(73, 13, 3, 14, '2026-05-12 00:15:27', NULL),
(74, 13, 4, 14, '2026-05-12 00:15:27', NULL),
(75, 13, 5, 14, '2026-05-12 00:15:27', NULL),
(76, 14, 127, 14, '2026-05-12 00:57:38', '2026-05-12 00:59:23'),
(77, 14, 128, 14, '2026-05-12 00:57:38', '2026-05-12 00:59:23'),
(78, 14, 129, 15, '2026-05-12 00:57:38', '2026-05-12 00:59:23'),
(79, 14, 130, 15, '2026-05-12 00:57:38', '2026-05-12 00:59:23'),
(80, 14, 131, 15, '2026-05-12 00:57:38', '2026-05-12 00:59:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargas_academicas`
--

CREATE TABLE `cargas_academicas` (
  `id` int(10) UNSIGNED NOT NULL,
  `docente_id` int(10) UNSIGNED NOT NULL,
  `seccion_id` smallint(5) UNSIGNED NOT NULL,
  `anio_id` smallint(5) UNSIGNED NOT NULL,
  `subarea_id` smallint(5) UNSIGNED DEFAULT NULL,
  `area_id` smallint(5) UNSIGNED DEFAULT NULL,
  `horas_semanales` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cargas_academicas`
--

INSERT INTO `cargas_academicas` (`id`, `docente_id`, `seccion_id`, `anio_id`, `subarea_id`, `area_id`, `horas_semanales`, `estado`, `created_at`) VALUES
(1, 7, 13, 1, 15, NULL, 2, 'activa', '2026-05-11 23:15:50'),
(2, 12, 13, 1, 22, NULL, 2, 'activa', '2026-05-11 23:20:44'),
(3, 9, 13, 1, 16, NULL, 2, 'activa', '2026-05-12 02:55:01'),
(4, 16, 13, 1, 12, NULL, 1, 'activa', '2026-05-12 02:59:27'),
(5, 19, 13, 1, NULL, 11, 2, 'activa', '2026-05-12 03:02:00'),
(6, 11, 13, 1, NULL, 12, 1, 'activa', '2026-05-12 03:02:30'),
(7, 15, 13, 1, 14, NULL, 2, 'activa', '2026-05-12 03:04:28'),
(8, 2, 13, 1, 18, NULL, 2, 'activa', '2026-05-12 03:04:59'),
(9, 8, 13, 1, 20, NULL, 2, 'activa', '2026-05-12 03:05:48'),
(10, 6, 13, 1, NULL, 13, 2, 'activa', '2026-05-12 03:07:17'),
(11, 17, 13, 1, 23, NULL, 2, 'activa', '2026-05-12 03:26:16'),
(12, 18, 13, 1, NULL, 16, 2, 'activa', '2026-05-12 03:27:32'),
(13, 7, 19, 1, 15, NULL, 2, 'activa', '2026-05-12 04:20:42'),
(14, 6, 19, 1, NULL, 13, 2, 'activa', '2026-05-12 04:21:25'),
(15, 8, 19, 1, 20, NULL, 2, 'activa', '2026-05-12 04:21:58'),
(16, 9, 19, 1, 16, NULL, 2, 'activa', '2026-05-12 04:23:52'),
(17, 10, 19, 1, NULL, 10, 2, 'activa', '2026-05-12 04:24:21'),
(18, 11, 19, 1, NULL, 12, 1, 'activa', '2026-05-12 04:26:23'),
(19, 12, 19, 1, 22, NULL, 2, 'activa', '2026-05-12 04:26:49'),
(20, 13, 19, 1, 19, NULL, 2, 'activa', '2026-05-12 04:28:33'),
(21, 14, 19, 1, 11, NULL, 2, 'activa', '2026-05-12 04:30:03'),
(22, 15, 19, 1, 14, NULL, 2, 'activa', '2026-05-12 04:33:08'),
(23, 16, 19, 1, 12, NULL, 2, 'activa', '2026-05-12 04:37:12'),
(24, 17, 19, 1, 23, NULL, 2, 'activa', '2026-05-12 04:38:32'),
(25, 18, 19, 1, NULL, 16, 2, 'activa', '2026-05-12 04:39:33'),
(26, 17, 19, 1, 21, NULL, 2, 'activa', '2026-05-12 04:40:52'),
(27, 19, 19, 1, NULL, 11, 2, 'activa', '2026-05-12 04:41:36'),
(28, 20, 19, 1, NULL, 15, 1, 'activa', '2026-05-12 04:44:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `competencias`
--

CREATE TABLE `competencias` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `codigo_minedu` varchar(5) DEFAULT NULL,
  `nombre_completo` text NOT NULL,
  `nombre_corto` varchar(120) DEFAULT NULL,
  `subarea_id` smallint(5) UNSIGNED DEFAULT NULL,
  `area_id` smallint(5) UNSIGNED DEFAULT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_horario`
--

CREATE TABLE `configuracion_horario` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `anio_id` smallint(5) UNSIGNED NOT NULL,
  `duracion_hora_min` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `hora_inicio_clases` time NOT NULL DEFAULT '07:45:00',
  `recreo_bloques` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recreo_bloques`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_horario`
--

INSERT INTO `configuracion_horario` (`id`, `anio_id`, `duracion_hora_min`, `hora_inicio_clases`, `recreo_bloques`, `created_at`) VALUES
(1, 1, 45, '13:10:00', NULL, '2026-05-11 23:17:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `criterios`
--

CREATE TABLE `criterios` (
  `id` int(10) UNSIGNED NOT NULL,
  `carga_id` int(10) UNSIGNED NOT NULL,
  `competencia_id` smallint(5) UNSIGNED NOT NULL,
  `periodo_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `criterios`
--

INSERT INTO `criterios` (`id`, `carga_id`, `competencia_id`, `periodo_id`, `nombre`, `orden`, `created_at`, `updated_at`) VALUES
(1, 1, 96, 1, 'Examen de entrada', 1, '2026-05-11 23:21:40', '2026-05-11 23:21:40'),
(2, 2, 97, 1, 'Examen de entrada', 1, '2026-05-12 03:14:18', '2026-05-12 03:14:18'),
(3, 3, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:14:57', '2026-05-12 03:14:57'),
(4, 4, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:17:11', '2026-05-12 03:17:11'),
(5, 5, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:19:55', '2026-05-12 03:19:55'),
(6, 6, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:22:57', '2026-05-12 03:22:57'),
(7, 7, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:23:09', '2026-05-12 03:23:09'),
(8, 8, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:23:59', '2026-05-12 03:23:59'),
(9, 9, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:31:12', '2026-05-12 03:31:12'),
(10, 11, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:32:13', '2026-05-12 03:32:13'),
(11, 10, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:32:26', '2026-05-12 03:32:26'),
(12, 12, 96, 1, 'Examen de entrada', 1, '2026-05-12 03:32:41', '2026-05-12 03:32:41'),
(13, 24, 102, 1, 'Examen de Entrada', 1, '2026-05-12 05:15:19', '2026-05-12 05:15:19'),
(14, 8, 97, 1, 'Examen de entrada', 1, '2026-05-12 05:57:30', '2026-05-12 05:57:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `persona_id` int(10) UNSIGNED NOT NULL,
  `codigo_estudiante` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `persona_id`, `codigo_estudiante`, `created_at`) VALUES
(1, 5, NULL, '2026-05-11 23:15:50'),
(2, 6, NULL, '2026-05-11 23:15:50'),
(3, 4, NULL, '2026-05-11 23:15:50'),
(4, 7, NULL, '2026-05-11 23:15:50'),
(5, 3, NULL, '2026-05-11 23:15:50'),
(8, 9, NULL, '2026-05-11 23:16:08'),
(9, 10, NULL, '2026-05-11 23:16:08'),
(10, 11, NULL, '2026-05-11 23:16:08'),
(11, 12, NULL, '2026-05-11 23:16:08'),
(12, 13, NULL, '2026-05-11 23:16:08'),
(13, 14, NULL, '2026-05-11 23:16:08'),
(14, 15, NULL, '2026-05-11 23:16:08'),
(15, 16, NULL, '2026-05-11 23:16:08'),
(16, 17, NULL, '2026-05-11 23:16:08'),
(17, 18, NULL, '2026-05-11 23:16:08'),
(18, 19, NULL, '2026-05-11 23:16:08'),
(19, 20, NULL, '2026-05-11 23:16:08'),
(20, 21, NULL, '2026-05-11 23:16:08'),
(21, 22, NULL, '2026-05-11 23:16:08'),
(22, 23, NULL, '2026-05-11 23:16:08'),
(23, 24, NULL, '2026-05-11 23:16:08'),
(24, 25, NULL, '2026-05-11 23:16:08'),
(25, 26, NULL, '2026-05-11 23:16:08'),
(26, 27, NULL, '2026-05-11 23:16:08'),
(27, 28, NULL, '2026-05-11 23:16:08'),
(28, 29, NULL, '2026-05-11 23:16:08'),
(29, 30, NULL, '2026-05-11 23:16:08'),
(30, 31, NULL, '2026-05-11 23:16:08'),
(31, 32, NULL, '2026-05-11 23:16:08'),
(32, 33, NULL, '2026-05-11 23:16:08'),
(33, 34, NULL, '2026-05-11 23:16:08'),
(34, 35, NULL, '2026-05-11 23:16:08'),
(35, 36, NULL, '2026-05-11 23:16:08'),
(36, 37, NULL, '2026-05-11 23:16:08'),
(37, 38, NULL, '2026-05-11 23:16:08'),
(38, 39, NULL, '2026-05-11 23:16:08'),
(39, 40, NULL, '2026-05-11 23:16:08'),
(40, 41, NULL, '2026-05-11 23:16:08'),
(41, 42, NULL, '2026-05-11 23:16:08'),
(42, 43, NULL, '2026-05-11 23:16:08'),
(43, 44, NULL, '2026-05-11 23:16:08'),
(44, 45, NULL, '2026-05-11 23:16:08'),
(45, 46, NULL, '2026-05-11 23:16:08'),
(46, 47, NULL, '2026-05-11 23:16:08'),
(47, 48, NULL, '2026-05-11 23:16:08'),
(48, 49, NULL, '2026-05-11 23:16:08'),
(49, 50, NULL, '2026-05-11 23:16:08'),
(50, 51, NULL, '2026-05-11 23:16:08'),
(51, 52, NULL, '2026-05-11 23:16:08'),
(52, 53, NULL, '2026-05-11 23:16:08'),
(53, 54, NULL, '2026-05-11 23:16:08'),
(54, 55, NULL, '2026-05-11 23:16:08'),
(55, 56, NULL, '2026-05-11 23:16:08'),
(56, 57, NULL, '2026-05-11 23:16:08'),
(57, 58, NULL, '2026-05-11 23:16:08'),
(58, 59, NULL, '2026-05-11 23:16:08'),
(59, 60, NULL, '2026-05-11 23:16:08'),
(60, 61, NULL, '2026-05-11 23:16:08'),
(61, 62, NULL, '2026-05-11 23:16:08'),
(62, 63, NULL, '2026-05-11 23:16:08'),
(63, 64, NULL, '2026-05-11 23:16:08'),
(64, 65, NULL, '2026-05-11 23:16:08'),
(65, 66, NULL, '2026-05-11 23:16:08'),
(66, 67, NULL, '2026-05-11 23:16:08'),
(67, 68, NULL, '2026-05-11 23:16:08'),
(68, 69, NULL, '2026-05-11 23:16:08'),
(69, 70, NULL, '2026-05-11 23:16:08'),
(70, 71, NULL, '2026-05-11 23:16:08'),
(71, 72, NULL, '2026-05-11 23:16:08'),
(72, 73, NULL, '2026-05-11 23:16:08'),
(73, 74, NULL, '2026-05-11 23:16:08'),
(74, 75, NULL, '2026-05-11 23:16:08'),
(75, 76, NULL, '2026-05-11 23:16:08'),
(76, 77, NULL, '2026-05-11 23:16:08'),
(77, 78, NULL, '2026-05-11 23:16:08'),
(78, 79, NULL, '2026-05-11 23:16:08'),
(79, 80, NULL, '2026-05-11 23:16:08'),
(80, 81, NULL, '2026-05-11 23:16:08'),
(81, 82, NULL, '2026-05-11 23:16:08'),
(82, 83, NULL, '2026-05-11 23:16:08'),
(83, 84, NULL, '2026-05-11 23:16:08'),
(84, 85, NULL, '2026-05-11 23:16:08'),
(85, 86, NULL, '2026-05-11 23:16:08'),
(86, 87, NULL, '2026-05-11 23:16:08'),
(87, 88, NULL, '2026-05-11 23:16:08'),
(88, 89, NULL, '2026-05-11 23:16:08'),
(89, 90, NULL, '2026-05-11 23:16:08'),
(90, 91, NULL, '2026-05-11 23:16:08'),
(91, 92, NULL, '2026-05-11 23:16:08'),
(92, 93, NULL, '2026-05-11 23:16:08'),
(93, 94, NULL, '2026-05-11 23:16:08'),
(94, 95, NULL, '2026-05-11 23:16:08'),
(95, 96, NULL, '2026-05-11 23:16:08'),
(96, 97, NULL, '2026-05-11 23:16:08'),
(97, 98, NULL, '2026-05-11 23:16:08'),
(98, 99, NULL, '2026-05-11 23:16:08'),
(99, 100, NULL, '2026-05-11 23:16:08'),
(100, 101, NULL, '2026-05-11 23:16:08'),
(101, 102, NULL, '2026-05-11 23:16:08'),
(102, 103, NULL, '2026-05-11 23:16:08'),
(103, 104, NULL, '2026-05-11 23:16:08'),
(104, 105, NULL, '2026-05-11 23:16:08'),
(105, 106, NULL, '2026-05-11 23:16:08'),
(106, 107, NULL, '2026-05-11 23:16:08'),
(107, 108, NULL, '2026-05-11 23:16:08'),
(108, 109, NULL, '2026-05-11 23:16:08'),
(109, 110, NULL, '2026-05-11 23:16:08'),
(110, 111, NULL, '2026-05-11 23:16:08'),
(111, 112, NULL, '2026-05-11 23:16:08'),
(112, 113, NULL, '2026-05-11 23:16:08'),
(113, 114, NULL, '2026-05-11 23:16:08'),
(114, 115, NULL, '2026-05-11 23:16:08'),
(115, 116, NULL, '2026-05-11 23:16:08'),
(116, 117, NULL, '2026-05-11 23:16:08'),
(117, 118, NULL, '2026-05-11 23:16:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grados`
--

CREATE TABLE `grados` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nivel_id` tinyint(3) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `nombre_display` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grados`
--

INSERT INTO `grados` (`id`, `nivel_id`, `numero`, `nombre_display`) VALUES
(1, 1, 1, '1°'),
(2, 1, 2, '2°'),
(3, 1, 3, '3°'),
(4, 1, 4, '4°'),
(5, 1, 5, '5°'),
(6, 1, 6, '6°'),
(7, 2, 1, '1°'),
(8, 2, 2, '2°'),
(9, 2, 3, '3°'),
(10, 2, 4, '4°'),
(11, 2, 5, '5°');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `seccion_id` smallint(5) UNSIGNED DEFAULT NULL,
  `anio_id` smallint(5) UNSIGNED NOT NULL,
  `tipo_matricula` enum('regular','traslado_entrada') NOT NULL DEFAULT 'regular',
  `estado` enum('registrada','pendiente_documentos','observada','aprobada','retirada') NOT NULL DEFAULT 'registrada',
  `seccion_solicitada` varchar(5) DEFAULT NULL,
  `fecha_registro` date NOT NULL,
  `limite_documentos` date DEFAULT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED NOT NULL,
  `aprobado_por` int(10) UNSIGNED DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `matriculas`
--

INSERT INTO `matriculas` (`id`, `estudiante_id`, `seccion_id`, `anio_id`, `tipo_matricula`, `estado`, `seccion_solicitada`, `fecha_registro`, `limite_documentos`, `fecha_aprobacion`, `registrado_por`, `aprobado_por`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 1, 'regular', 'aprobada', NULL, '2026-05-11', NULL, NULL, 1, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(2, 3, 1, 1, 'regular', 'aprobada', NULL, '2026-05-11', NULL, NULL, 1, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(3, 1, 1, 1, 'regular', 'aprobada', NULL, '2026-05-11', NULL, NULL, 1, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(4, 2, 1, 1, 'regular', 'aprobada', NULL, '2026-05-11', NULL, NULL, 1, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(5, 4, 1, 1, 'regular', 'aprobada', NULL, '2026-05-11', NULL, NULL, 1, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(8, 8, 3, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(9, 9, 3, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(10, 10, 3, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(11, 11, 3, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(12, 12, 3, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(15, 13, 2, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(16, 14, 2, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(17, 15, 2, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(18, 16, 2, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(19, 17, 2, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(22, 18, 5, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(23, 19, 5, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(24, 20, 5, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(25, 21, 5, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(26, 22, 5, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(29, 23, 4, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(30, 24, 4, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(31, 25, 4, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(32, 26, 4, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(33, 27, 4, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(36, 28, 7, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(37, 29, 7, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(38, 30, 7, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(39, 31, 7, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(40, 32, 7, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(43, 33, 6, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(44, 34, 6, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(45, 35, 6, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(46, 36, 6, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(47, 37, 6, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(50, 38, 9, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(51, 39, 9, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(52, 40, 9, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(53, 41, 9, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(54, 42, 9, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(57, 43, 8, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(58, 44, 8, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(59, 45, 8, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(60, 46, 8, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(61, 47, 8, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(64, 48, 11, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(65, 49, 11, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(66, 50, 11, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(67, 51, 11, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(68, 52, 11, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(71, 53, 10, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(72, 54, 10, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(73, 55, 10, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(74, 56, 10, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(75, 57, 10, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(78, 58, 13, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(79, 59, 13, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(80, 60, 13, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(81, 61, 13, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(82, 62, 13, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(85, 63, 12, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(86, 64, 12, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(87, 65, 12, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(88, 66, 12, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(89, 67, 12, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(92, 68, 18, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(93, 69, 18, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(94, 70, 18, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(95, 71, 18, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(96, 72, 18, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(99, 73, 17, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(100, 74, 17, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(101, 75, 17, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(102, 76, 17, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(103, 77, 17, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(106, 78, 20, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(107, 79, 20, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(108, 80, 20, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(109, 81, 20, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(110, 82, 20, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(113, 83, 19, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(114, 84, 19, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(115, 85, 19, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(116, 86, 19, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(117, 87, 19, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(120, 88, 22, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(121, 89, 22, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(122, 90, 22, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(123, 91, 22, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(124, 92, 22, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(127, 93, 21, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(128, 94, 21, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(129, 95, 21, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(130, 96, 21, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(131, 97, 21, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(134, 98, 24, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(135, 99, 24, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(136, 100, 24, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(137, 101, 24, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(138, 102, 24, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(141, 103, 23, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(142, 104, 23, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(143, 105, 23, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(144, 106, 23, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(145, 107, 23, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(148, 108, 26, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(149, 109, 26, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(150, 110, 26, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(151, 111, 26, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(152, 112, 26, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(155, 113, 25, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(156, 114, 25, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(157, 115, 25, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(158, 116, 25, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09'),
(159, 117, 25, 1, 'regular', 'aprobada', NULL, '2026-03-01', NULL, '2026-03-08', 1, NULL, NULL, '2026-05-11 23:16:09', '2026-05-11 23:16:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niveles`
--

CREATE TABLE `niveles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `escala_boleta` enum('solo_literal','ambas') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `niveles`
--

INSERT INTO `niveles` (`id`, `nombre`, `codigo`, `escala_boleta`, `created_at`) VALUES
(1, 'Primaria', 'prim', 'solo_literal', '2026-05-11 23:13:16'),
(2, 'Secundaria', 'sec', 'ambas', '2026-05-11 23:13:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `anio_id` smallint(5) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `tipo` enum('bimestre','trimestre') NOT NULL DEFAULT 'bimestre',
  `nombre_display` varchar(30) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `limite_notas` datetime DEFAULT NULL,
  `estado` enum('pendiente','activo','cerrado') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id`, `anio_id`, `numero`, `tipo`, `nombre_display`, `fecha_inicio`, `fecha_fin`, `limite_notas`, `estado`) VALUES
(1, 1, 1, 'bimestre', 'I Bimestre', '2026-03-09', '2026-05-15', '2026-05-20 23:59:00', 'activo'),
(2, 1, 2, 'bimestre', 'II Bimestre', '2026-05-19', '2026-07-17', NULL, 'pendiente'),
(3, 1, 3, 'bimestre', 'III Bimestre', '2026-08-03', '2026-10-02', NULL, 'pendiente'),
(4, 1, 4, 'bimestre', 'IV Bimestre', '2026-10-05', '2026-12-04', NULL, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id` int(10) UNSIGNED NOT NULL,
  `dni` varchar(8) NOT NULL,
  `apellido_paterno` varchar(60) NOT NULL,
  `apellido_materno` varchar(60) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` enum('M','F') DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`id`, `dni`, `apellido_paterno`, `apellido_materno`, `nombres`, `fecha_nacimiento`, `sexo`, `telefono`, `correo`, `direccion`, `created_at`, `updated_at`) VALUES
(1, '00000000', 'Sistema', 'COCIAP', 'Administrador', NULL, NULL, NULL, 'admin@cociap.edu.pe', NULL, '2026-05-11 23:13:16', '2026-05-11 23:13:16'),
(2, '12345678', 'Guillermo', 'Chavez', 'Luis Waldir', NULL, 'M', NULL, 'waldirguillermoc@gmail.com', NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(3, '77752898', 'Angeles', 'Fernandez', 'Xiara Daleshka', NULL, 'F', NULL, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(4, '61314557', 'Aguilar', 'Rosario', 'Vanessa Yanneth', NULL, 'F', NULL, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(5, '45678901', 'Ramirez', 'Torres', 'Carlos Alberto', NULL, 'M', NULL, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(6, '56789012', 'Mendoza', 'Quispe', 'Lucia Valentina', NULL, 'F', NULL, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(7, '67890123', 'Huanca', 'Vidal', 'Diego Alejandro', NULL, 'M', NULL, NULL, NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(8, '99999999', 'Fernandez', 'Torres', 'Maria Elena', NULL, NULL, '943123456', 'fertome1983@gmail.com', NULL, '2026-05-11 23:15:50', '2026-05-11 23:15:50'),
(9, '10000001', 'QUISPE', 'FLORES', 'JUAN CARLOS', '2019-04-10', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(10, '10000002', 'MAMANI', 'GARCIA', 'ANA LUCIA', '2019-06-22', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(11, '10000003', 'ROJAS', 'TORRES', 'MIGUEL ANGEL', '2019-02-14', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(12, '10000004', 'FLORES', 'RAMIREZ', 'SOFIA CAMILA', '2019-08-30', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(13, '10000005', 'GARCIA', 'MENDOZA', 'PEDRO PABLO', '2019-05-18', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(14, '10000006', 'HUANCA', 'CHAVEZ', 'VALERIA NICOL', '2019-01-25', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(15, '10000007', 'TORRES', 'VARGAS', 'ANDRES MARTIN', '2018-11-08', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(16, '10000008', 'RAMOS', 'QUISPE', 'DIANA PAOLA', '2019-03-17', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(17, '10000009', 'CONDORI', 'APAZA', 'FRANCO ALEXIS', '2018-12-05', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(18, '10000010', 'MENDOZA', 'HUAMAN', 'LUCIA VALENTINA', '2019-07-14', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(19, '10000011', 'CASTILLO', 'MORALES', 'BRYAN SEBASTIAN', '2018-02-28', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(20, '10000012', 'RAMIREZ', 'SANCHEZ', 'ROCIO PAMELA', '2018-05-11', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(21, '10000013', 'VARGAS', 'PEREZ', 'DAVID ALEJANDRO', '2018-09-20', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(22, '10000014', 'GONZALES', 'LLANOS', 'KAREN ELIZABETH', '2018-01-07', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(23, '10000015', 'CHAVEZ', 'DIAZ', 'CARLOS ENRIQUE', '2018-07-03', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(24, '10000016', 'TAPIA', 'ROJAS', 'ESTEFANY MISHEL', '2017-11-14', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(25, '10000017', 'PIZARRO', 'CASTILLO', 'AARON MATIAS', '2018-04-22', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(26, '10000018', 'MORALES', 'FLORES', 'YESENIA PAOLA', '2017-12-30', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(27, '10000019', 'SALAZAR', 'TORRES', 'FABIAN RODRIGO', '2018-08-16', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(28, '10000020', 'HUAMAN', 'QUISPE', 'CINTHIA MILAGROS', '2018-03-09', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(29, '10000021', 'DIAZ', 'RAMIREZ', 'ERICK PAUL', '2017-06-18', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(30, '10000022', 'PEREZ', 'MENDOZA', 'ANALI DEL PILAR', '2017-02-24', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(31, '10000023', 'LLANOS', 'VARGAS', 'OMAR GABRIEL', '2017-09-05', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(32, '10000024', 'CCORI', 'GONZALES', 'BRENDA LORENA', '2017-04-12', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(33, '10000025', 'APAZA', 'HUANCA', 'WILDER JESUS', '2017-11-28', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(34, '10000026', 'MEZA', 'CONDORI', 'LEIDY JOHANA', '2016-12-07', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(35, '10000027', 'ESPINOZA', 'APAZA', 'KEVIN DANIEL', '2017-03-15', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(36, '10000028', 'GUTIERREZ', 'CCORI', 'ROSA MARIA', '2017-07-21', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(37, '10000029', 'RIVERA', 'PIZARRO', 'JHON ALEX', '2017-01-08', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(38, '10000030', 'SANCHEZ', 'MEZA', 'DANI LUCERO', '2017-05-30', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(39, '10000031', 'FLORES', 'ESPINOZA', 'SERGIO ANTONIO', '2016-03-22', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(40, '10000032', 'TORRES', 'GUTIERREZ', 'MILUSKA ALEJANDRA', '2016-06-14', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(41, '10000033', 'QUISPE', 'RIVERA', 'JUNIOR JHAIR', '2016-09-08', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(42, '10000034', 'GARCIA', 'SANCHEZ', 'FERNANDA ANAIS', '2016-01-27', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(43, '10000035', 'MAMANI', 'DIAZ', 'NELSON FELIX', '2016-11-03', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(44, '10000036', 'ROJAS', 'MAMANI', 'ASHLEY NICHOLE', '2015-12-19', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(45, '10000037', 'RAMIREZ', 'GARCIA', 'JOSE LUIS', '2016-04-07', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(46, '10000038', 'VARGAS', 'ROJAS', 'KIARA DANIELA', '2016-08-25', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(47, '10000039', 'MENDOZA', 'FLORES', 'LUIS ALBERTO', '2016-02-11', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(48, '10000040', 'CHAVEZ', 'TORRES', 'MARIA JOSE', '2016-10-16', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(49, '10000041', 'CASTILLO', 'CHAVEZ', 'RAUL EMILIO', '2015-05-20', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(50, '10000042', 'GONZALES', 'VARGAS', 'PAOLA STEFANY', '2015-08-03', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(51, '10000043', 'HUANCA', 'MENDOZA', 'RODRIGO ANDRE', '2015-01-17', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(52, '10000044', 'MORALES', 'CASTILLO', 'NADIA ROSARIO', '2015-11-29', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(53, '10000045', 'TAPIA', 'GONZALES', 'ALEXIS RENATO', '2015-03-08', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(54, '10000046', 'PIZARRO', 'HUANCA', 'ISABEL CRISTINA', '2014-12-14', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(55, '10000047', 'CCORI', 'MORALES', 'CESAR AUGUSTO', '2015-07-26', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(56, '10000048', 'LLANOS', 'TAPIA', 'GABRIELA YASMIN', '2015-04-09', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(57, '10000049', 'APAZA', 'PIZARRO', 'CRISTIAN JAVIER', '2014-10-31', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(58, '10000050', 'MEZA', 'CCORI', 'MARIA FERNANDA', '2015-09-22', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(59, '10000051', 'ESPINOZA', 'LLANOS', 'VICTOR MANUEL', '2014-02-18', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(60, '10000052', 'GUTIERREZ', 'APAZA', 'XIOMARA ELENA', '2014-06-07', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(61, '10000053', 'RIVERA', 'MEZA', 'JOSE MANUEL', '2014-11-25', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(62, '10000054', 'SANCHEZ', 'ESPINOZA', 'NATALIA BELEN', '2014-04-14', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(63, '10000055', 'FLORES', 'GUTIERREZ', 'MARIO ANTONIO', '2014-08-30', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(64, '10000056', 'TORRES', 'RIVERA', 'ADRIANA NICOLE', '2013-12-05', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(65, '10000057', 'QUISPE', 'SANCHEZ', 'HENRY OMAR', '2014-03-19', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(66, '10000058', 'MAMANI', 'FLORES', 'CARMEN ROSA', '2014-07-08', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(67, '10000059', 'GARCIA', 'TORRES', 'EDWIN RAUL', '2013-09-24', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(68, '10000060', 'ROJAS', 'QUISPE', 'ELIZABETH PILAR', '2014-01-12', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(69, '10000061', 'RAMIREZ', 'MAMANI', 'SEBASTIAN ALAN', '2013-05-07', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(70, '10000062', 'VARGAS', 'GARCIA', 'STEPHANIE MARIE', '2013-08-21', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(71, '10000063', 'MENDOZA', 'ROJAS', 'GABRIEL ALEJANDRO', '2013-01-14', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(72, '10000064', 'CHAVEZ', 'RAMIREZ', 'FIORELLA LUCIA', '2013-10-29', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(73, '10000065', 'CASTILLO', 'VARGAS', 'ANDERSON PAUL', '2013-04-16', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(74, '10000066', 'GONZALES', 'MENDOZA', 'DANNA VALERIA', '2012-11-03', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(75, '10000067', 'HUANCA', 'CASTILLO', 'PIERO ALEJANDRO', '2013-03-18', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(76, '10000068', 'MORALES', 'GONZALES', 'NAOMI ESTHER', '2013-07-02', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(77, '10000069', 'TAPIA', 'HUANCA', 'RUDOLFO CESAR', '2012-09-27', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(78, '10000070', 'PIZARRO', 'MORALES', 'ALISON DIANA', '2013-02-11', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(79, '10000071', 'CCORI', 'TAPIA', 'ANTHONY JAMES', '2012-06-15', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(80, '10000072', 'LLANOS', 'PIZARRO', 'XIOMARA VALERIA', '2012-09-28', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(81, '10000073', 'APAZA', 'CCORI', 'LUIS RODRIGO', '2012-02-07', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(82, '10000074', 'MEZA', 'LLANOS', 'CINDY LORENA', '2012-12-21', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(83, '10000075', 'ESPINOZA', 'APAZA', 'JORGE LUIS', '2012-05-04', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(84, '10000076', 'GUTIERREZ', 'MEZA', 'JESSICA PAMELA', '2011-11-18', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(85, '10000077', 'RIVERA', 'ESPINOZA', 'RONALDO DAVID', '2012-04-03', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(86, '10000078', 'SANCHEZ', 'GUTIERREZ', 'DIANA CAROLINA', '2012-07-16', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(87, '10000079', 'FLORES', 'RIVERA', 'IVAN AUGUSTO', '2011-10-09', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(88, '10000080', 'TORRES', 'SANCHEZ', 'MELISSA ANAHI', '2012-01-25', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(89, '10000081', 'QUISPE', 'TORRES', 'RENATO FABIAN', '2011-05-12', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(90, '10000082', 'MAMANI', 'QUISPE', 'YOLANDA CRISTINA', '2011-08-27', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(91, '10000083', 'GARCIA', 'MAMANI', 'JHONATAN ALEXIS', '2011-02-18', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(92, '10000084', 'ROJAS', 'GARCIA', 'MAYRA STEFANI', '2011-11-04', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(93, '10000085', 'RAMIREZ', 'ROJAS', 'ANGEL GABRIEL', '2011-06-30', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(94, '10000086', 'VARGAS', 'RAMIREZ', 'DENISSE PAMELA', '2010-12-15', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(95, '10000087', 'MENDOZA', 'VARGAS', 'RODRIGO ALONSO', '2011-04-08', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(96, '10000088', 'CHAVEZ', 'MENDOZA', 'VERONICA MILAGROS', '2011-07-23', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(97, '10000089', 'CASTILLO', 'CHAVEZ', 'JOEL CRISTIAN', '2010-09-17', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(98, '10000090', 'GONZALES', 'CASTILLO', 'CARLA MELISA', '2011-01-05', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(99, '10000091', 'HUANCA', 'GONZALES', 'MARCO ANTONIO', '2010-06-19', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(100, '10000092', 'MORALES', 'HUANCA', 'PRISCILA RUTH', '2010-09-02', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(101, '10000093', 'TAPIA', 'MORALES', 'JESUS ALEJANDRO', '2010-03-14', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(102, '10000094', 'PIZARRO', 'TAPIA', 'PAMELA ESTHER', '2010-12-28', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(103, '10000095', 'CCORI', 'PIZARRO', 'RICHARD SMITH', '2010-05-07', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(104, '10000096', 'LLANOS', 'CCORI', 'GLORIA ESTEFANI', '2009-11-22', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(105, '10000097', 'APAZA', 'LLANOS', 'CHRISTIAN ALBERTO', '2010-04-16', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(106, '10000098', 'MEZA', 'APAZA', 'PATRICIA LORENA', '2010-08-09', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(107, '10000099', 'ESPINOZA', 'MEZA', 'ALEX RODRIGO', '2009-10-25', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(108, '10000100', 'GUTIERREZ', 'ESPINOZA', 'ROSA ELENA', '2010-01-13', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(109, '10000101', 'RIVERA', 'GUTIERREZ', 'FRANK ALDAIR', '2009-07-04', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(110, '10000102', 'SANCHEZ', 'RIVERA', 'MARIELA ROSA', '2009-11-18', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(111, '10000103', 'FLORES', 'SANCHEZ', 'PEDRO JOSE', '2009-04-26', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(112, '10000104', 'TORRES', 'FLORES', 'WENDY CAROLINA', '2009-08-12', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(113, '10000105', 'QUISPE', 'TORRES', 'JOSUE ELIAS', '2009-02-20', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(114, '10000106', 'MAMANI', 'QUISPE', 'KERLY DIANA', '2008-12-09', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(115, '10000107', 'GARCIA', 'MAMANI', 'JULIO CESAR', '2009-05-27', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(116, '10000108', 'ROJAS', 'GARCIA', 'NORMA YANETH', '2009-09-13', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(117, '10000109', 'RAMIREZ', 'ROJAS', 'FRANK OSWALDO', '2008-11-08', 'M', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(118, '10000110', 'VARGAS', 'RAMIREZ', 'CONNIE MISHEL', '2009-03-01', 'F', NULL, NULL, NULL, '2026-05-11 23:16:08', '2026-05-11 23:16:08'),
(229, '20000001', 'BUENO', 'DE LA O', 'KAREN VIOLETA', NULL, 'F', '935321323', 'KBUENO@COCIAPVVG.EDU.PE', NULL, '2026-05-12 03:52:42', '2026-05-12 03:52:42'),
(230, '20000002', 'HUAYANEY', 'GRANADOS', 'KATTY JANETH', NULL, 'F', '935461123', 'KHUAYANEYG@COCIAPVVG.EDU.PE', NULL, '2026-05-12 03:53:26', '2026-05-12 03:53:26'),
(231, '20000003', 'SOTELO', 'ROQUE', 'SAARA', NULL, 'F', '934121111', 'SSOTELOR@COCIAVVG.EDU.PE', NULL, '2026-05-12 03:54:31', '2026-05-12 03:54:31'),
(232, '20000004', 'MENACHO', 'QUESADA', 'KETTY', NULL, 'F', '934000123', 'KMENACHOQ@COCIAPVVG.EDU.PE', NULL, '2026-05-12 03:55:56', '2026-05-12 03:55:56'),
(233, '20000005', 'CASTILLEJO', 'MORALES', 'NACHO EDUAR', NULL, 'M', '914034311', 'NCASTILLEJOM@COCIAPVVG.EDU.PE', NULL, '2026-05-12 03:57:43', '2026-05-12 03:57:43'),
(234, '20000006', 'NUÑUVERO', 'RAMIREZ', 'LESLIE', NULL, 'F', '940010099', 'LNUNUVEROR@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:01:01', '2026-05-12 04:01:01'),
(235, '20000007', 'CLEMENTE', 'ANGELES', 'MARSHALL ALEKHENE', NULL, 'M', '905123433', 'MCLEMENTEA@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:03:24', '2026-05-12 04:03:24'),
(236, '20000008', 'MONTES', 'DEPAZ', 'HILBER CARLOS', NULL, 'M', '932414433', 'HMONTESD@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:04:20', '2026-05-12 04:04:20'),
(237, '20000009', 'OLIVERA', 'RAMIREZ', 'SILVIA MILAGROS', NULL, 'F', '941001433', 'SOLIVERAR@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:05:09', '2026-05-12 04:05:09'),
(238, '20000010', 'ZAMBRANO', 'GUILLERMO', 'EDINZON ALEX', NULL, 'M', '941339090', 'EZAMBRANOG@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:06:05', '2026-05-12 04:06:05'),
(239, '20000011', 'PUMA', 'TINTA', 'MISHEL', NULL, 'F', '901441211', 'MPUMAT@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:07:28', '2026-05-12 04:07:28'),
(240, '20000012', 'CARRILLO', 'MEJIA', 'NORELI MILAGROS', NULL, 'F', '932121212', 'NCARRILLOM@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:08:28', '2026-05-12 04:08:28'),
(241, '20000013', 'MOSQUERA', 'DEPAZ', 'LEANDRO HERCILIO', NULL, 'M', '912131313', 'LMOSQUERAD@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:09:49', '2026-05-12 04:09:49'),
(242, '20000014', 'ANAYA', 'MORALES', 'VALOIS', NULL, 'M', '900123123', 'VANAYAM@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:11:38', '2026-05-12 04:11:38'),
(243, '20000015', 'BELLO', 'REYES', 'FREDY JESUS', NULL, 'M', '912443211', 'FBELLOR@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:12:22', '2026-05-12 04:12:22'),
(244, '20000016', 'ZAVALETA', 'ROSALES', 'JORGE ARTURO DANIEL', NULL, 'M', '941000233', 'JZAVALETAR@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:14:18', '2026-05-12 04:14:18'),
(245, '20000017', 'TRUJILLO', 'ALVAREZ', 'NELLY YUBITZA', NULL, 'F', '914112288', 'TYUBITZA@COCIAPVVG.EDU.PE', NULL, '2026-05-12 04:17:57', '2026-05-12 04:17:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reglas_especiales`
--

CREATE TABLE `reglas_especiales` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `area_id` smallint(5) UNSIGNED NOT NULL,
  `nivel_id` tinyint(3) UNSIGNED NOT NULL,
  `grado_desde` tinyint(3) UNSIGNED NOT NULL,
  `grado_hasta` tinyint(3) UNSIGNED NOT NULL,
  `nombre_override` varchar(120) DEFAULT NULL,
  `alias_override` varchar(80) DEFAULT NULL,
  `area_siagie_id` smallint(5) UNSIGNED DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reglas_especiales`
--

INSERT INTO `reglas_especiales` (`id`, `area_id`, `nivel_id`, `grado_desde`, `grado_hasta`, `nombre_override`, `alias_override`, `area_siagie_id`, `descripcion`) VALUES
(1, 12, 2, 4, 5, 'Arte y Cultura', '(Raz. Matemático)', NULL, 'En 4° y 5° de secundaria las notas de Raz. Matemático se registran en el campo Arte y Cultura del SIAGIE'),
(2, 16, 2, 4, 5, NULL, NULL, 12, '4°-5° sec: Taller Raz. Matemático se registra como Arte y Cultura en SIAGIE (sobreescribe el nombre_siagie por defecto)');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `codigo`, `descripcion`, `created_at`) VALUES
(1, 'Administrador', 'admin', 'Acceso total al sistema', '2026-05-11 23:13:16'),
(2, 'Registro Académico', 'registro_academico', 'Gestión de matrículas, traslados y documentos oficiales', '2026-05-11 23:13:16'),
(3, 'Director General', 'director_general', 'Supervisión de todos los niveles', '2026-05-11 23:13:16'),
(4, 'Director EBR', 'director_ebr', 'Supervisión de su nivel educativo', '2026-05-11 23:13:16'),
(5, 'Secretaria', 'secretaria', 'Registro de matrículas y atención', '2026-05-11 23:13:16'),
(6, 'Docente', 'docente', 'Registro de calificaciones de sus cargas', '2026-05-11 23:13:16'),
(7, 'Padre de Familia', 'padre', 'Consulta del progreso de su menor hijo', '2026-05-11 23:13:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

CREATE TABLE `secciones` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `grado_id` tinyint(3) UNSIGNED NOT NULL,
  `anio_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(5) NOT NULL,
  `tutor_id` int(10) UNSIGNED DEFAULT NULL,
  `es_unidocente` tinyint(1) NOT NULL DEFAULT 0,
  `estado_nomina` enum('borrador','aprobada') NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `secciones`
--

INSERT INTO `secciones` (`id`, `grado_id`, `anio_id`, `nombre`, `tutor_id`, `es_unidocente`, `estado_nomina`, `created_at`) VALUES
(1, 1, 1, 'A', NULL, 1, 'aprobada', '2026-05-11 23:15:50'),
(2, 1, 1, 'B', NULL, 1, 'aprobada', '2026-05-11 23:16:08'),
(3, 2, 1, 'A', NULL, 1, 'aprobada', '2026-05-11 23:16:08'),
(4, 2, 1, 'B', NULL, 1, 'aprobada', '2026-05-11 23:16:08'),
(5, 3, 1, 'A', NULL, 1, 'aprobada', '2026-05-11 23:16:08'),
(6, 3, 1, 'B', NULL, 1, 'aprobada', '2026-05-11 23:16:08'),
(7, 4, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(8, 4, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(9, 5, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(10, 5, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(11, 6, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(12, 6, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(13, 7, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(14, 7, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(15, 7, 1, 'C', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(16, 8, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(17, 8, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(18, 9, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(19, 9, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(20, 10, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(21, 10, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(22, 11, 1, 'A', NULL, 0, 'aprobada', '2026-05-11 23:16:08'),
(23, 11, 1, 'B', NULL, 0, 'aprobada', '2026-05-11 23:16:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_horario`
--

CREATE TABLE `sesiones_horario` (
  `id` int(10) UNSIGNED NOT NULL,
  `carga_id` int(10) UNSIGNED NOT NULL,
  `bloque_id` smallint(5) UNSIGNED NOT NULL,
  `seccion_id` smallint(5) UNSIGNED NOT NULL,
  `docente_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones_horario`
--

INSERT INTO `sesiones_horario` (`id`, `carga_id`, `bloque_id`, `seccion_id`, `docente_id`) VALUES
(19, 14, 18, 13, 6),
(20, 13, 19, 13, 7),
(22, 16, 22, 13, 9),
(24, 18, 24, 13, 11),
(26, 20, 26, 13, 13),
(27, 21, 27, 13, 14),
(28, 22, 28, 13, 15),
(29, 19, 25, 13, 12),
(30, 19, 29, 13, 12),
(31, 23, 30, 13, 16),
(32, 24, 31, 13, 17),
(34, 17, 23, 13, 10),
(35, 17, 33, 13, 10),
(36, 15, 20, 13, 8),
(37, 15, 34, 13, 8),
(38, 26, 35, 13, 17),
(39, 27, 36, 13, 19),
(40, 28, 37, 13, 20),
(41, 25, 32, 13, 18),
(42, 25, 38, 13, 18),
(43, 2, 19, 19, 12),
(44, 1, 39, 19, 7),
(45, 4, 40, 19, 16),
(46, 5, 22, 19, 19),
(47, 6, 25, 19, 11),
(48, 7, 26, 19, 15),
(49, 8, 27, 19, 2),
(50, 9, 28, 19, 8),
(51, 11, 41, 19, 17),
(52, 10, 42, 19, 6),
(53, 12, 31, 19, 18),
(54, 3, 43, 19, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subareas`
--

CREATE TABLE `subareas` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `area_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `subareas`
--

INSERT INTO `subareas` (`id`, `area_id`, `nombre`, `orden`) VALUES
(1, 6, 'Comunicación', 1),
(2, 6, 'Plan Lector', 2),
(3, 6, 'Razonamiento Verbal', 3),
(4, 7, 'Aritmética', 1),
(5, 7, 'Álgebra', 2),
(6, 7, 'Geometría', 3),
(7, 7, 'Razonamiento Matemático', 4),
(8, 8, 'Química', 1),
(9, 8, 'Biología', 2),
(10, 8, 'Física', 3),
(11, 17, 'Historia', 1),
(12, 17, 'Geografía', 2),
(13, 17, 'Economía', 3),
(14, 18, 'Razonamiento Verbal', 1),
(15, 18, 'Literatura', 2),
(16, 18, 'Lenguaje', 3),
(17, 19, 'Aritmética', 1),
(18, 19, 'Álgebra', 2),
(19, 19, 'Geometría', 3),
(20, 19, 'Trigonometría', 4),
(21, 20, 'Química', 1),
(22, 20, 'Biología', 2),
(23, 20, 'Física', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `persona_id` int(10) UNSIGNED NOT NULL,
  `rol_id` tinyint(3) UNSIGNED NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `sesion_token` varchar(64) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `persona_id`, `rol_id`, `password_hash`, `ultimo_acceso`, `sesion_token`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '$2y$10$uYEa/sZHfN6Rj5XRdY861euNiXsccWZk0SynNzcFebqNf9V1j2Pfy', '2026-05-12 00:58:06', '2ddc46c61a98d3e17260ad16db993707bc0f829ddcf0d81021c3bf12c7b62eb0', 'activo', '2026-05-11 23:13:16', '2026-05-12 05:58:06'),
(2, 2, 6, '$2y$10$HfNJaWDDQrGujO9FkqlAjeO2l2t46KGasEY2W3cz2a4Y2jlcl9.3O', '2026-05-12 00:58:51', '724e376b8e1b61cca996439fd450bd275b7841376d6236fea66626b4157cccda', 'activo', '2026-05-11 23:15:50', '2026-05-12 05:58:51'),
(3, 8, 7, '$2y$10$NWvhpNy1mHlJXDH/ofWadeU2LDGBypIQSfbywvmnTVPJc1RU1PXeG', '2026-05-12 00:10:35', NULL, 'activo', '2026-05-11 23:19:09', '2026-05-12 05:14:31'),
(4, 229, 6, '$2y$12$yOODf6OtZsuUAif9xeLxduku8De.FgSpIWa4u1JT1xYQd3MYvoe5e', NULL, NULL, 'activo', '2026-05-12 03:52:42', '2026-05-12 03:52:42'),
(5, 230, 6, '$2y$12$Y1ySvwJQCIMS4JFLZ0edGeaFrsCLgEWcZA6ZSOrkTVg4F4dRZ2mma', NULL, NULL, 'activo', '2026-05-12 03:53:26', '2026-05-12 03:53:26'),
(6, 231, 6, '$2y$12$rHPNGdvo098umnXfM9snfeNK0c4IiWx18rPM6C9sVZqVicWjg0kd6', NULL, NULL, 'activo', '2026-05-12 03:54:31', '2026-05-12 03:54:31'),
(7, 232, 6, '$2y$12$A3mky7IdWRu8.anguaW76.kXx8pY6BBqMzl2LVvdz5u6MrzDcybZW', NULL, NULL, 'activo', '2026-05-12 03:55:56', '2026-05-12 03:55:56'),
(8, 233, 6, '$2y$12$DaCNDK45/8V8AMMo4B.rVeBitdSoYu8QeefKud63ZDOwt4nDmEqwS', NULL, NULL, 'activo', '2026-05-12 03:57:44', '2026-05-12 03:57:44'),
(9, 234, 6, '$2y$12$2j/Yp8JGm1QjEze0XvuU1e02GoGUKmxjAmHqIOWkhBzrx5RipkbDa', NULL, NULL, 'activo', '2026-05-12 04:01:01', '2026-05-12 04:01:01'),
(10, 235, 6, '$2y$12$DeJeT9DcroXJSYUOB3bGwuFGYfCM1Va.zIUvI3tZnuTkPttRiC1lC', NULL, NULL, 'activo', '2026-05-12 04:03:25', '2026-05-12 04:03:25'),
(11, 236, 6, '$2y$12$CFqJrcTyltnLow0KtILXb.8/SR54YJe3zSZarzMdgmTR8iDTfxtvi', NULL, NULL, 'activo', '2026-05-12 04:04:20', '2026-05-12 04:04:20'),
(12, 237, 6, '$2y$12$iz6.x5mDUSO7n0NERFcMF.oMPLk4N6VxMyH1vL3Iuosa4ed1CqLae', NULL, NULL, 'activo', '2026-05-12 04:05:09', '2026-05-12 04:05:09'),
(13, 238, 6, '$2y$12$WOs0WETmqYhFh62idSvSQeFThUIwftKs3z8Jgm0Nz91bJVT0BFQ5.', NULL, NULL, 'activo', '2026-05-12 04:06:05', '2026-05-12 04:06:05'),
(14, 239, 6, '$2y$12$DXeRfPwJUMI4wqRfwB8E5.JuM2vx1ILaR90y.scciUqSUn2KiFxU.', NULL, NULL, 'activo', '2026-05-12 04:07:28', '2026-05-12 04:07:28'),
(15, 240, 6, '$2y$12$hD8yR0xd9//5CFpLnlorTuO/4h84J4ISYYKFunEpARF42KAou6uZe', NULL, NULL, 'activo', '2026-05-12 04:08:28', '2026-05-12 04:08:28'),
(16, 241, 6, '$2y$12$AJ.8JGgstj/RnBuQYTlgLuwUFFDv2wA16AITO102xdL5VNSh8lGSC', NULL, NULL, 'activo', '2026-05-12 04:09:49', '2026-05-12 04:09:49'),
(17, 242, 6, '$2y$12$/nxt33x9NKN/wUCyt3zw9uwJgfAdjrhoV15LltBoiXiWmQb56qFfi', '2026-05-12 00:14:55', NULL, 'activo', '2026-05-12 04:11:39', '2026-05-12 05:28:30'),
(18, 243, 6, '$2y$12$R6AnOgX.ARv32DSZpkYAreeMDEOWvvj4RpohBZwVGBLpwxAQd2.LW', NULL, NULL, 'activo', '2026-05-12 04:12:22', '2026-05-12 04:12:22'),
(19, 244, 6, '$2y$12$./3qJOg80fiSdEj0BjZqweBfv6csBSZeMUHleZCTMoEgTIIuZCNRm', NULL, NULL, 'activo', '2026-05-12 04:14:18', '2026-05-12 04:14:18'),
(20, 245, 6, '$2y$12$tPXKSUb6qrc.5v0ajEA/rOi/5rHPieMXh4Pz3AqcV3PCD4X/Y/lnW', NULL, NULL, 'activo', '2026-05-12 04:17:57', '2026-05-12 04:17:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vinculo_familiar`
--

CREATE TABLE `vinculo_familiar` (
  `id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `apoderado_id` int(10) UNSIGNED NOT NULL,
  `tipo_vinculo` enum('padre','madre','apoderado') NOT NULL,
  `es_responsable` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vinculo_familiar`
--

INSERT INTO `vinculo_familiar` (`id`, `estudiante_id`, `apoderado_id`, `tipo_vinculo`, `es_responsable`) VALUES
(1, 5, 1, 'madre', 1);

--
-- Índices para tablas volcadas
--


INSERT INTO competencias 
(id, codigo_minedu, nombre_completo, nombre_corto, area_id, subarea_id, orden)
VALUES
-- PRIMARIA
(1,'C1','Se comunica en inglés como lengua extranjera.','Comunicación en inglés',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Inglés'),NULL,1),

(2,'C2','Lee diversos tipos de textos escritos en inglés como lengua extranjera',
 'Lee y comprende en inglés',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Inglés'),NULL,2),

(3,'C3','Escribe diversos tipos de textos en inglés como lengua extranjera',
 'Redacción en inglés',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Inglés'),NULL,3),

(4,'C4','Construye su identidad.','Construye su identidad',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Personal Social'),NULL,4),

(5,'C5','Convive y participa democráticamente en la búsqueda del bien común.',
 'Convive y participa por el bien común',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Personal Social'),NULL,5),

(6,'C6','Construye interpretaciones históricas.',
 'Construye interpretaciones históricas',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Personal Social'),NULL,6),

(7,'C7','Gestiona responsablemente el espacio y el ambiente.',
 'Gestiona el espacio y el ambiente',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Personal Social'),NULL,7),

(8,'C8','Gestiona responsablemente los recursos económicos.',
 'Gestiona los recursos económicos',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Personal Social'),NULL,8),

(9,'C9','Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.',
 'Identidad como persona amada por Dios',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Educación Religiosa'),NULL,9),

(10,'C10','Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa',
 'Asume la experiencia de Dios en su proyecto de vida',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Educación Religiosa'),NULL,10),

(11,'C13','Se desenvuelve de manera autónoma a través de su motricidad.',
 'Motricidad autónoma',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Educación Física'),NULL,11),

(12,'C14','Asume una vida saludable.',
 'Vida saludable',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Educación Física'),NULL,12),

(13,'C15','Interactúa a través de sus habilidades sociomotrices.',
 'Habilidades sociomotrices',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Educación Física'),NULL,13),

(14,'C16','Se comunica oralmente en su lengua materna.',
 'Se comunica oralmente en sulengua materna.',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Comunicación'),NULL,14),

(15,'C17','Lee diversos tipos de textos escritos en su lengua materna.',
 'Lee diversos tipos de textos escritos en su lengua materna',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Comunicación'),NULL,15),

(16,'C18','Escribe diversos tipos de textos en su lengua materna.',
 'Escribe diversos tipos de textos en su lengua materna',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Comunicación'),NULL,16),

(17,'C19','Aprecia de manera crítica manifestaciones artístico-culturales.',
 'Aprecia manifestaciones artísticas',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Arte y Cultura'),NULL,17),

(18,'C20','Crea proyectos desde los lenguajes artísticos.',
 'Crea proyectos artísticos',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Arte y Cultura'),NULL,18),

(19,'C21','Resuelve problemas de cantidad.',
 'Resuelve problemas de cantidad',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Aritmética'
    AND a.nivel_id=1
    AND a.nombre='Matemática'),
 19),

(20,'C22','Resuelve problemas de regularidad, equivalencia y cambio.',
 'Resuelve problemas de regularidad, equivalencia y cambio',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Álgebra'
    AND a.nivel_id=1
    AND a.nombre='Matemática'),
 20),

(21,'C23','Resuelve problemas de forma, movimiento y localización.',
 'Resuelve problemas de forma, movimiento y localización',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Geometría'
    AND a.nivel_id=1
    AND a.nombre='Matemática'),
 21),

(22,'C24','Resuelve problemas de gestión de datos e incertidumbre.',
 'Gestión de datos',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Razonamiento Matemático'
    AND a.nivel_id=1
    AND a.nombre='Matemática'),
 22),

(23,'C25','Indaga mediante métodos científicos para construir sus conocimientos.',
 'Indaga mediante el método científico',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Química'
    AND a.nivel_id=1
    AND a.nombre='Ciencia y Tecnología'),
 23),

(24,'C26','Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.',
 'Explica el mundo físico basándose en los seres vivos',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Biología'
    AND a.nivel_id=1
    AND a.nombre='Ciencia y Tecnología'),
 24),

(25,'C27','Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
 'Diseña y construye soluciones tecnológicas',
 NULL,
 (SELECT s.id
  FROM subareas s
  INNER JOIN areas a ON a.id=s.area_id
  WHERE s.nombre='Física'
    AND a.nivel_id=1
    AND a.nombre='Ciencia y Tecnología'),
 25),

(26,'CT1','Se desenvuelve en entornos virtuales generados por las TIC.',
 'Entornos virtuales / TIC',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Competencias Transversales'),
 NULL,26),

(27,'CT2','Gestiona su aprendizaje de manera autónoma.',
 'Aprendizaje autónomo',
 (SELECT id FROM areas WHERE nivel_id=1 AND nombre='Competencias Transversales'),
 NULL,27),

-- SECUNDARIA
(28,'C28','Construye su identidad.',
 'Construye su identidad',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica'),
 NULL,1),

(29,'C29','Convive y participa democráticamente en la búsqueda del bien común.',
 'Convive democráticamente en la búsqueda del bien común',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica'),
 NULL,2),

(30,'C30','Construye interpretaciones históricas.',
 'Construye interpretaciones históricas',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias sociales'),
 NULL,3),

(31,'C31','Gestiona responsablemente el espacio y el ambiente.',
 'Gestiona responsablemente el espacio y el ambiente',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias sociales'),
 NULL,4),

(32,'C32','Gestiona responsablemente los recursos económicos.',
 'Gestiona responsablemente los recursos económicos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias sociales'),
 NULL,5),

(33,'C33','Asume una vida saludable.',
 'Asume una vida saludable.',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física'),
 NULL,6),

(34,'C34','Interactúa a través de sus habilidades sociomotrices.',
 'Interactúa a través de sus habilidades sociomotrices.',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física'),
 NULL,7),

(35,'C35','Asume una vida saludable.',
 'Asume una vida saludable.',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física'),
 NULL,8),

(36,'C36','Aprecia de manera crítica manifestaciones artístico-culturales.',
 'Aprecia de manera crítica manifestaciones artístico-culturales',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Arte y Cultura'),
 NULL,9),

(37,'C37','Crea proyectos desde los lenguajes artísticos.',
 'Crea proyectos desde los lenguajes artísticos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Arte y Cultura'),
 NULL,10),

(38,'C38','Se comunica oralmente en su lengua materna.',
 'Se comunica oralmente',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación'),
 NULL,11),

(39,'C39','Lee diversos tipos de textos escritos en su lengua materna.',
 'Lee diversos tipos de textos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación'),
 NULL,12),

(40,'C40','Escribe diversos tipos de textos en su lengua materna.',
 'Escribe diversos tipos de textos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación'),
 NULL,13),

(41,'C41','Se comunica oralmente.',
 'Se comunica oralmente',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Inglés'),
 NULL,14),

(42,'C42','Lee diversos tipos de textos escritos.',
 'Lee diversos tipos de textos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Inglés'),
 NULL,15),

(43,'C43','Escribe diversos tipos de texto.',
 'Escribe diversos tipos de textos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Inglés'),
 NULL,16),

(44,'C44','Resuelve problemas de cantidad.',
 'Resuelve problemas de cantidad',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'),
 NULL,17),

(45,'C45','Resuelve problemas de regularidad, equivalencia y cambio.',
 'Resuelve problemas de regularidad, equivalencia y cambio',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'),
 NULL,18),

(46,'C46','Resuelve problemas de forma, movimiento y localización.',
 'Resuelve problemas de forma, movimiento y localización',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'),
 NULL,19),

(47,'C47','Resuelve problemas de gestión de datos e incertidumbre.',
 'Resuelve problemas de gestión de datos e incertidumbre',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'),
 NULL,20),

(48,'C48','Indaga mediante métodos científicos para construir sus conocimientos.',
 'Indaga mediante métodos científicos',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología'),
 NULL,21),

(49,'C49','Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.',
 'Explica el mundo físico basándose en conocimientos sobre la Tierra y el Universo.',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología'),
 NULL,22),

(50,'C50','Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
 'Diseña y construye soluciones tecnológicas',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología'),
 NULL,23),

(51,'C51','Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.',
 'Construye su identidad como persona amada por Dios',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Religiosa'),
 NULL,24),

(52,'C52','Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.',
 'Asume la experiencia del encuentro con Dios en su vida',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Religiosa'),
 NULL,25),

(53,'C53','Gestiona proyectos de emprendimiento económico o social.',
 'Gestiona proyectos de emprendimiento',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación para el Trabajo'),
 NULL,26),

(54,'CT3','Se desenvuelve en entornos virtuales generados por las TIC.',
 'Entornos virtuales / TIC',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Competencias Transversales'),
 NULL,26),

(55,'CT4','Gestiona su aprendizaje de manera autónoma.',
 'Aprendizaje autónomo',
 (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Competencias Transversales'),
 NULL,27);





--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `idx_matricula` (`matricula_id`),
  ADD KEY `idx_leida` (`leida`);

--
-- Indices de la tabla `anios_academicos`
--
ALTER TABLE `anios_academicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anio` (`anio`);

--
-- Indices de la tabla `apoderados`
--
ALTER TABLE `apoderados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `persona_id` (`persona_id`);

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nivel_tipo` (`nivel_id`,`tipo`);

--
-- Indices de la tabla `bloqueos_competencia`
--
ALTER TABLE `bloqueos_competencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bloqueo` (`carga_id`,`competencia_id`,`periodo_id`),
  ADD KEY `competencia_id` (`competencia_id`),
  ADD KEY `periodo_id` (`periodo_id`),
  ADD KEY `bloqueado_por` (`bloqueado_por`);

--
-- Indices de la tabla `bloques_horario`
--
ALTER TABLE `bloques_horario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_config_dia_bloque` (`config_id`,`dia_semana`,`numero_bloque`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nota` (`matricula_id`,`carga_id`,`periodo_id`,`competencia_id`),
  ADD KEY `carga_id` (`carga_id`),
  ADD KEY `periodo_id` (`periodo_id`),
  ADD KEY `competencia_id` (`competencia_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_matricula_periodo` (`matricula_id`,`periodo_id`);

--
-- Indices de la tabla `calificaciones_criterio`
--
ALTER TABLE `calificaciones_criterio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_criterio_matricula` (`criterio_id`,`matricula_id`),
  ADD KEY `idx_criterio` (`criterio_id`),
  ADD KEY `idx_matricula` (`matricula_id`);

--
-- Indices de la tabla `cargas_academicas`
--
ALTER TABLE `cargas_academicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anio_id` (`anio_id`),
  ADD KEY `subarea_id` (`subarea_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `idx_docente_anio` (`docente_id`,`anio_id`),
  ADD KEY `idx_seccion_anio` (`seccion_id`,`anio_id`);

--
-- Indices de la tabla `competencias`
--
ALTER TABLE `competencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subarea` (`subarea_id`),
  ADD KEY `idx_area` (`area_id`);

--
-- Indices de la tabla `configuracion_horario`
--
ALTER TABLE `configuracion_horario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anio_id` (`anio_id`);

--
-- Indices de la tabla `criterios`
--
ALTER TABLE `criterios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `competencia_id` (`competencia_id`),
  ADD KEY `periodo_id` (`periodo_id`),
  ADD KEY `idx_carga_competencia_periodo` (`carga_id`,`competencia_id`,`periodo_id`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `persona_id` (`persona_id`);

--
-- Indices de la tabla `grados`
--
ALTER TABLE `grados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nivel_numero` (`nivel_id`,`numero`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estudiante_anio` (`estudiante_id`,`anio_id`),
  ADD KEY `seccion_id` (`seccion_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `aprobado_por` (`aprobado_por`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_anio` (`anio_id`);

--
-- Indices de la tabla `niveles`
--
ALTER TABLE `niveles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_anio_numero` (`anio_id`,`numero`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_apellidos` (`apellido_paterno`,`apellido_materno`);

--
-- Indices de la tabla `reglas_especiales`
--
ALTER TABLE `reglas_especiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `nivel_id` (`nivel_id`),
  ADD KEY `area_siagie_id` (`area_siagie_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grado_anio_seccion` (`grado_id`,`anio_id`,`nombre`),
  ADD KEY `anio_id` (`anio_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indices de la tabla `sesiones_horario`
--
ALTER TABLE `sesiones_horario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seccion_bloque` (`seccion_id`,`bloque_id`),
  ADD UNIQUE KEY `uq_docente_bloque` (`docente_id`,`bloque_id`),
  ADD KEY `carga_id` (`carga_id`),
  ADD KEY `bloque_id` (`bloque_id`);

--
-- Indices de la tabla `subareas`
--
ALTER TABLE `subareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_area` (`area_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `persona_id` (`persona_id`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `vinculo_familiar`
--
ALTER TABLE `vinculo_familiar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estudiante_tipo` (`estudiante_id`,`tipo_vinculo`),
  ADD KEY `apoderado_id` (`apoderado_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `anios_academicos`
--
ALTER TABLE `anios_academicos`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `apoderados`
--
ALTER TABLE `apoderados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `bloqueos_competencia`
--
ALTER TABLE `bloqueos_competencia`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT de la tabla `bloques_horario`
--
ALTER TABLE `bloques_horario`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=294;

--
-- AUTO_INCREMENT de la tabla `calificaciones_criterio`
--
ALTER TABLE `calificaciones_criterio`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT de la tabla `cargas_academicas`
--
ALTER TABLE `cargas_academicas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `competencias`
--
ALTER TABLE `competencias`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT de la tabla `configuracion_horario`
--
ALTER TABLE `configuracion_horario`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `criterios`
--
ALTER TABLE `criterios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de la tabla `grados`
--
ALTER TABLE `grados`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=246;

--
-- AUTO_INCREMENT de la tabla `reglas_especiales`
--
ALTER TABLE `reglas_especiales`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `secciones`
--
ALTER TABLE `secciones`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `sesiones_horario`
--
ALTER TABLE `sesiones_horario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `subareas`
--
ALTER TABLE `subareas`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `vinculo_familiar`
--
ALTER TABLE `vinculo_familiar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

SET FOREIGN_KEY_CHECKS=0;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `alertas_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`);

--
-- Filtros para la tabla `apoderados`
--
ALTER TABLE `apoderados`
  ADD CONSTRAINT `apoderados_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`);

--
-- Filtros para la tabla `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`);

--
-- Filtros para la tabla `bloqueos_competencia`
--
ALTER TABLE `bloqueos_competencia`
  ADD CONSTRAINT `bloqueos_competencia_ibfk_1` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`),
  ADD CONSTRAINT `bloqueos_competencia_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`),
  ADD CONSTRAINT `bloqueos_competencia_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  ADD CONSTRAINT `bloqueos_competencia_ibfk_4` FOREIGN KEY (`bloqueado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `bloques_horario`
--
ALTER TABLE `bloques_horario`
  ADD CONSTRAINT `bloques_horario_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `configuracion_horario` (`id`);

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`),
  ADD CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`),
  ADD CONSTRAINT `calificaciones_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  ADD CONSTRAINT `calificaciones_ibfk_4` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`),
  ADD CONSTRAINT `calificaciones_ibfk_5` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `calificaciones_criterio`
--
ALTER TABLE `calificaciones_criterio`
  ADD CONSTRAINT `calificaciones_criterio_ibfk_1` FOREIGN KEY (`criterio_id`) REFERENCES `criterios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_criterio_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`);

--
-- Filtros para la tabla `cargas_academicas`
--
ALTER TABLE `cargas_academicas`
  ADD CONSTRAINT `cargas_academicas_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cargas_academicas_ibfk_2` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`),
  ADD CONSTRAINT `cargas_academicas_ibfk_3` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`),
  ADD CONSTRAINT `cargas_academicas_ibfk_4` FOREIGN KEY (`subarea_id`) REFERENCES `subareas` (`id`),
  ADD CONSTRAINT `cargas_academicas_ibfk_5` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `competencias`
--
ALTER TABLE `competencias`
  ADD CONSTRAINT `competencias_ibfk_1` FOREIGN KEY (`subarea_id`) REFERENCES `subareas` (`id`),
  ADD CONSTRAINT `competencias_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `configuracion_horario`
--
ALTER TABLE `configuracion_horario`
  ADD CONSTRAINT `configuracion_horario_ibfk_1` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`);

--
-- Filtros para la tabla `criterios`
--
ALTER TABLE `criterios`
  ADD CONSTRAINT `criterios_ibfk_1` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `criterios_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`),
  ADD CONSTRAINT `criterios_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`);

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`);

--
-- Filtros para la tabla `grados`
--
ALTER TABLE `grados`
  ADD CONSTRAINT `grados_ibfk_1` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`);

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  ADD CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`),
  ADD CONSTRAINT `matriculas_ibfk_3` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`),
  ADD CONSTRAINT `matriculas_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `matriculas_ibfk_5` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD CONSTRAINT `periodos_ibfk_1` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`);

--
-- Filtros para la tabla `reglas_especiales`
--
ALTER TABLE `reglas_especiales`
  ADD CONSTRAINT `reglas_especiales_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  ADD CONSTRAINT `reglas_especiales_ibfk_2` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`),
  ADD CONSTRAINT `reglas_especiales_ibfk_3` FOREIGN KEY (`area_siagie_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD CONSTRAINT `secciones_ibfk_1` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`),
  ADD CONSTRAINT `secciones_ibfk_2` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`),
  ADD CONSTRAINT `secciones_ibfk_3` FOREIGN KEY (`tutor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `sesiones_horario`
--
ALTER TABLE `sesiones_horario`
  ADD CONSTRAINT `sesiones_horario_ibfk_1` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`),
  ADD CONSTRAINT `sesiones_horario_ibfk_2` FOREIGN KEY (`bloque_id`) REFERENCES `bloques_horario` (`id`),
  ADD CONSTRAINT `sesiones_horario_ibfk_3` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`),
  ADD CONSTRAINT `sesiones_horario_ibfk_4` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `subareas`
--
ALTER TABLE `subareas`
  ADD CONSTRAINT `subareas_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `vinculo_familiar`
--
ALTER TABLE `vinculo_familiar`
  ADD CONSTRAINT `vinculo_familiar_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  ADD CONSTRAINT `vinculo_familiar_ibfk_2` FOREIGN KEY (`apoderado_id`) REFERENCES `apoderados` (`id`);

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: siga_cociap
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `siga_cociap`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `siga_cociap` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci */;

USE `siga_cociap`;

--
-- Table structure for table `alertas`
--

DROP TABLE IF EXISTS `alertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alertas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tutor_id` int(10) unsigned NOT NULL,
  `matricula_id` int(10) unsigned NOT NULL,
  `tipo` enum('academica','conductual','asistencia','general') NOT NULL DEFAULT 'general',
  `mensaje` text NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `enviada_correo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tutor_id` (`tutor_id`),
  KEY `idx_matricula` (`matricula_id`),
  KEY `idx_leida` (`leida`),
  CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `alertas_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertas`
--

LOCK TABLES `alertas` WRITE;
/*!40000 ALTER TABLE `alertas` DISABLE KEYS */;
/*!40000 ALTER TABLE `alertas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `anios_academicos`
--

DROP TABLE IF EXISTS `anios_academicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anios_academicos` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `anio` year(4) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('planificado','activo','cerrado') NOT NULL DEFAULT 'planificado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `anio` (`anio`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anios_academicos`
--

LOCK TABLES `anios_academicos` WRITE;
/*!40000 ALTER TABLE `anios_academicos` DISABLE KEYS */;
INSERT INTO `anios_academicos` (`id`, `anio`, `fecha_inicio`, `fecha_fin`, `estado`, `created_at`) VALUES (1,2026,'2026-03-09','2026-12-18','activo','2026-05-11 23:15:50');
/*!40000 ALTER TABLE `anios_academicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `apoderados`
--

DROP TABLE IF EXISTS `apoderados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apoderados` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `persona_id` (`persona_id`),
  CONSTRAINT `apoderados_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apoderados`
--

LOCK TABLES `apoderados` WRITE;
/*!40000 ALTER TABLE `apoderados` DISABLE KEYS */;
INSERT INTO `apoderados` (`id`, `persona_id`, `created_at`) VALUES (1,8,'2026-05-11 23:15:50');
/*!40000 ALTER TABLE `apoderados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `nivel_id` tinyint(3) unsigned NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `nombre_boleta` varchar(120) DEFAULT NULL,
  `alias_boleta` varchar(80) DEFAULT NULL,
  `nombre_siagie` varchar(120) DEFAULT NULL,
  `tipo` enum('area_curso','con_subareas','transversal') NOT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_nivel_tipo` (`nivel_id`,`tipo`),
  CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
INSERT INTO `areas` (`id`, `nivel_id`, `nombre`, `nombre_boleta`, `alias_boleta`, `nombre_siagie`, `tipo`, `orden`, `activa`) VALUES (1,1,'Personal Social','Personal Social',NULL,'Personal Social','area_curso',2,1),(2,1,'Educación Física','Educación Física',NULL,'Educación Física','area_curso',4,1),(3,1,'Arte y Cultura','Arte y Cultura',NULL,'Arte y Cultura','area_curso',6,1),(4,1,'Inglés','Inglés como Lengua Extranjera',NULL,'Inglés como Lengua Extranjera','area_curso',1,1),(5,1,'Educación Religiosa','Educación Religiosa',NULL,'Educación Religiosa','area_curso',3,1),(6,1,'Comunicación','Comunicación',NULL,'Comunicación','con_subareas',5,1),(7,1,'Matemática','Matemática',NULL,'Matemática','con_subareas',7,1),(8,1,'Ciencia y Tecnología','Ciencia y Tecnología',NULL,'Ciencia y Tecnología','con_subareas',8,1),(9,1,'Competencias Transversales','Comp. Transv.',NULL,NULL,'transversal',9,1),(10,2,'Desarrollo Personal, Ciudadanía y Cívica','DPCC',NULL,'Desarrollo Personal, Ciudadanía y Cívica','area_curso',1,1),(11,2,'Educación Física','Educación Física',NULL,'Educación Física','area_curso',3,1),(12,2,'Arte y Cultura','Arte y Cultura',NULL,'Arte y Cultura','area_curso',4,1),(13,2,'Inglés','Inglés',NULL,'Inglés como Lengua Extranjera','area_curso',6,1),(14,2,'Educación Religiosa','Educación Religiosa','(Ética y Valores)','Educación Religiosa','area_curso',10,1),(15,2,'Educación para el Trabajo','EPT','(Habilidades Pedagógicas)','Educación para el Trabajo','area_curso',11,1),(16,2,'Taller de Razonamiento Matemático','Taller Raz. Matemático',NULL,'Educación Religiosa','area_curso',8,1),(17,2,'Ciencias Sociales','Ciencias Sociales',NULL,'Ciencias Sociales','con_subareas',2,1),(18,2,'Comunicación','Comunicación',NULL,'Comunicación','con_subareas',5,1),(19,2,'Matemática','Matemática',NULL,'Matemática','con_subareas',7,1),(20,2,'Ciencia y Tecnología','Ciencia y Tecnología',NULL,'Ciencia y Tecnología','con_subareas',9,1),(21,2,'Competencias Transversales','Comp. Transv.',NULL,NULL,'transversal',12,1);
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bloqueos_competencia`
--

DROP TABLE IF EXISTS `bloqueos_competencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bloqueos_competencia` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `carga_id` int(10) unsigned NOT NULL,
  `competencia_id` smallint(5) unsigned NOT NULL,
  `periodo_id` smallint(5) unsigned NOT NULL,
  `bloqueado_por` int(10) unsigned NOT NULL,
  `bloqueado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bloqueo` (`carga_id`,`competencia_id`,`periodo_id`),
  KEY `competencia_id` (`competencia_id`),
  KEY `periodo_id` (`periodo_id`),
  KEY `bloqueado_por` (`bloqueado_por`),
  CONSTRAINT `bloqueos_competencia_ibfk_1` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`),
  CONSTRAINT `bloqueos_competencia_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`),
  CONSTRAINT `bloqueos_competencia_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  CONSTRAINT `bloqueos_competencia_ibfk_4` FOREIGN KEY (`bloqueado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bloqueos_competencia`
--

LOCK TABLES `bloqueos_competencia` WRITE;
/*!40000 ALTER TABLE `bloqueos_competencia` DISABLE KEYS */;
INSERT INTO `bloqueos_competencia` (`id`, `carga_id`, `competencia_id`, `periodo_id`, `bloqueado_por`, `bloqueado_en`) VALUES (151,4,31,1,1,'2026-05-14 11:34:59'),(152,6,36,1,1,'2026-05-14 11:35:02'),(153,6,37,1,1,'2026-05-14 11:35:04'),(154,7,38,1,1,'2026-05-14 11:35:07'),(155,3,40,1,1,'2026-05-14 11:35:13'),(156,10,41,1,1,'2026-05-14 11:35:16'),(157,10,42,1,1,'2026-05-14 11:35:19'),(159,8,45,1,1,'2026-05-14 11:35:32'),(160,14,43,1,1,'2026-05-14 11:36:03'),(161,14,42,1,1,'2026-05-14 11:36:07'),(162,14,41,1,1,'2026-05-14 11:36:10'),(163,9,47,1,1,'2026-05-14 11:36:17'),(164,2,49,1,1,'2026-05-14 11:36:20'),(165,11,50,1,1,'2026-05-14 11:36:24'),(166,12,55,1,18,'2026-05-14 11:37:58'),(167,12,54,1,18,'2026-05-14 11:38:19'),(168,5,33,1,19,'2026-05-14 11:39:20'),(169,5,34,1,19,'2026-05-14 11:39:31'),(170,5,35,1,19,'2026-05-14 11:40:39'),(171,10,43,1,6,'2026-05-14 15:49:51'),(172,41,28,1,10,'2026-05-14 16:11:23'),(173,41,29,1,10,'2026-05-14 16:12:13'),(174,17,28,1,10,'2026-05-14 16:29:40'),(175,17,29,1,10,'2026-05-14 16:31:12'),(176,20,46,1,13,'2026-05-14 16:33:30'),(177,21,30,1,14,'2026-05-14 16:34:54'),(178,42,45,1,4,'2026-05-14 16:40:00'),(179,26,48,1,17,'2026-05-14 16:42:16'),(180,1,39,1,7,'2026-05-14 16:45:23'),(181,43,44,1,5,'2026-05-14 16:48:00'),(182,29,56,1,2,'2026-05-14 17:08:06'),(183,29,57,1,2,'2026-05-14 17:09:28');
/*!40000 ALTER TABLE `bloqueos_competencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bloques_horario`
--

DROP TABLE IF EXISTS `bloques_horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bloques_horario` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `config_id` smallint(5) unsigned NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes') NOT NULL,
  `numero_bloque` tinyint(3) unsigned NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_config_dia_bloque` (`config_id`,`dia_semana`,`numero_bloque`),
  CONSTRAINT `bloques_horario_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `configuracion_horario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bloques_horario`
--

LOCK TABLES `bloques_horario` WRITE;
/*!40000 ALTER TABLE `bloques_horario` DISABLE KEYS */;
INSERT INTO `bloques_horario` (`id`, `config_id`, `dia_semana`, `numero_bloque`, `hora_inicio`, `hora_fin`) VALUES (1,1,'lunes',1,'16:30:00','17:20:00'),(2,1,'lunes',2,'17:20:00','18:10:00'),(3,1,'lunes',3,'13:00:00','13:50:00'),(4,1,'lunes',4,'13:50:00','14:40:00'),(5,1,'lunes',5,'14:40:00','15:30:00'),(6,1,'lunes',6,'15:30:00','16:10:00'),(7,1,'lunes',7,'16:30:00','17:15:00'),(8,1,'lunes',8,'17:15:00','18:00:00'),(9,1,'lunes',9,'18:00:00','18:45:00'),(10,1,'lunes',10,'13:00:00','13:45:00'),(11,1,'martes',1,'13:00:00','13:45:00'),(12,1,'lunes',11,'13:45:00','14:30:00'),(13,1,'lunes',12,'14:30:00','15:15:00'),(14,1,'martes',2,'13:45:00','14:30:00'),(15,1,'martes',3,'14:30:00','15:15:00'),(16,1,'martes',4,'15:15:00','16:00:00'),(17,1,'lunes',13,'13:10:00','13:55:00'),(18,1,'lunes',14,'14:40:00','16:10:00'),(19,1,'lunes',15,'13:10:00','14:40:00'),(20,1,'lunes',16,'16:35:00','17:20:00'),(21,1,'lunes',17,'17:20:00','18:50:00'),(22,1,'martes',5,'13:10:00','14:40:00'),(23,1,'lunes',18,'14:40:00','15:25:00'),(24,1,'martes',6,'15:25:00','16:10:00'),(25,1,'martes',7,'16:35:00','17:20:00'),(26,1,'martes',8,'17:20:00','18:50:00'),(27,1,'miercoles',1,'13:10:00','14:40:00'),(28,1,'miercoles',2,'14:40:00','16:10:00'),(29,1,'miercoles',3,'16:35:00','17:20:00'),(30,1,'miercoles',4,'17:20:00','18:50:00'),(31,1,'jueves',1,'14:40:00','16:10:00'),(32,1,'jueves',2,'16:35:00','17:20:00'),(33,1,'jueves',3,'17:20:00','18:05:00'),(34,1,'jueves',4,'18:05:00','18:50:00'),(35,1,'viernes',1,'13:10:00','14:40:00'),(36,1,'viernes',2,'14:40:00','16:10:00'),(37,1,'viernes',3,'17:20:00','18:05:00'),(38,1,'viernes',4,'18:05:00','18:50:00'),(39,1,'lunes',19,'16:35:00','18:05:00'),(40,1,'lunes',20,'18:05:00','18:50:00'),(41,1,'miercoles',5,'16:35:00','18:05:00'),(42,1,'jueves',5,'13:10:00','14:40:00'),(43,1,'jueves',6,'16:35:00','18:05:00'),(44,1,'martes',9,'14:40:00','15:25:00'),(45,1,'lunes',21,'00:00:00','00:01:00'),(46,1,'viernes',5,'00:00:00','00:01:00'),(47,1,'miercoles',6,'00:00:00','00:01:00');
/*!40000 ALTER TABLE `bloques_horario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calificaciones`
--

DROP TABLE IF EXISTS `calificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calificaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `matricula_id` int(10) unsigned NOT NULL,
  `carga_id` int(10) unsigned NOT NULL,
  `periodo_id` smallint(5) unsigned NOT NULL,
  `competencia_id` smallint(5) unsigned NOT NULL,
  `nota_numerica` tinyint(3) unsigned NOT NULL,
  `conclusion_descriptiva` text DEFAULT NULL,
  `registrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `modificado_en` datetime DEFAULT NULL,
  `registrado_por` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nota` (`matricula_id`,`carga_id`,`periodo_id`,`competencia_id`),
  KEY `carga_id` (`carga_id`),
  KEY `periodo_id` (`periodo_id`),
  KEY `competencia_id` (`competencia_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_matricula_periodo` (`matricula_id`,`periodo_id`),
  CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`),
  CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`),
  CONSTRAINT `calificaciones_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  CONSTRAINT `calificaciones_ibfk_4` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`),
  CONSTRAINT `calificaciones_ibfk_5` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=954 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `calificaciones_criterio`
--

DROP TABLE IF EXISTS `calificaciones_criterio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calificaciones_criterio` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `criterio_id` int(10) unsigned NOT NULL,
  `matricula_id` int(10) unsigned NOT NULL,
  `nota` tinyint(3) unsigned NOT NULL,
  `registrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `modificado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_criterio_matricula` (`criterio_id`,`matricula_id`),
  KEY `idx_criterio` (`criterio_id`),
  KEY `idx_matricula` (`matricula_id`),
  CONSTRAINT `calificaciones_criterio_ibfk_1` FOREIGN KEY (`criterio_id`) REFERENCES `criterios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_criterio_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=742 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `cargas_academicas`
--

DROP TABLE IF EXISTS `cargas_academicas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargas_academicas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `docente_id` int(10) unsigned NOT NULL,
  `seccion_id` smallint(5) unsigned NOT NULL,
  `anio_id` smallint(5) unsigned NOT NULL,
  `subarea_id` smallint(5) unsigned DEFAULT NULL,
  `area_id` smallint(5) unsigned DEFAULT NULL,
  `horas_semanales` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `anio_id` (`anio_id`),
  KEY `subarea_id` (`subarea_id`),
  KEY `area_id` (`area_id`),
  KEY `idx_docente_anio` (`docente_id`,`anio_id`),
  KEY `idx_seccion_anio` (`seccion_id`,`anio_id`),
  CONSTRAINT `cargas_academicas_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `cargas_academicas_ibfk_2` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`),
  CONSTRAINT `cargas_academicas_ibfk_3` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`),
  CONSTRAINT `cargas_academicas_ibfk_4` FOREIGN KEY (`subarea_id`) REFERENCES `subareas` (`id`),
  CONSTRAINT `cargas_academicas_ibfk_5` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargas_academicas`
--

LOCK TABLES `cargas_academicas` WRITE;
/*!40000 ALTER TABLE `cargas_academicas` DISABLE KEYS */;
INSERT INTO `cargas_academicas` (`id`, `docente_id`, `seccion_id`, `anio_id`, `subarea_id`, `area_id`, `horas_semanales`, `estado`, `created_at`) VALUES (1,7,13,1,15,NULL,2,'activa','2026-05-11 23:15:50'),(2,12,13,1,22,NULL,2,'activa','2026-05-11 23:20:44'),(3,9,13,1,16,NULL,2,'activa','2026-05-12 02:55:01'),(4,16,13,1,12,NULL,1,'activa','2026-05-12 02:59:27'),(5,19,13,1,NULL,11,2,'activa','2026-05-12 03:02:00'),(6,11,13,1,NULL,12,1,'activa','2026-05-12 03:02:30'),(7,15,13,1,14,NULL,2,'activa','2026-05-12 03:04:28'),(8,2,23,1,18,NULL,0,'activa','2026-05-12 03:04:59'),(9,8,13,1,20,NULL,2,'activa','2026-05-12 03:05:48'),(10,6,13,1,NULL,13,2,'activa','2026-05-12 03:07:17'),(11,17,13,1,23,NULL,2,'activa','2026-05-12 03:26:16'),(12,18,13,1,NULL,16,2,'activa','2026-05-12 03:27:32'),(13,7,19,1,15,NULL,2,'activa','2026-05-12 04:20:42'),(14,6,19,1,NULL,13,2,'activa','2026-05-12 04:21:25'),(15,8,19,1,20,NULL,2,'activa','2026-05-12 04:21:58'),(16,9,19,1,16,NULL,2,'activa','2026-05-12 04:23:52'),(17,10,13,1,NULL,10,2,'activa','2026-05-12 04:24:21'),(18,11,19,1,NULL,12,1,'activa','2026-05-12 04:26:23'),(19,12,19,1,22,NULL,2,'activa','2026-05-12 04:26:49'),(20,13,13,1,19,NULL,2,'activa','2026-05-12 04:28:33'),(21,14,13,1,11,NULL,2,'activa','2026-05-12 04:30:03'),(22,15,19,1,14,NULL,2,'activa','2026-05-12 04:33:08'),(23,16,19,1,12,NULL,2,'activa','2026-05-12 04:37:12'),(24,17,19,1,23,NULL,2,'activa','2026-05-12 04:38:32'),(25,18,19,1,NULL,16,2,'activa','2026-05-12 04:39:33'),(26,17,13,1,21,NULL,2,'activa','2026-05-12 04:40:52'),(27,19,19,1,NULL,11,2,'activa','2026-05-12 04:41:36'),(28,20,13,1,NULL,15,1,'activa','2026-05-12 04:44:07'),(29,2,13,1,NULL,21,0,'activa','2026-05-13 04:25:31'),(30,5,14,1,NULL,21,0,'activa','2026-05-13 04:25:39'),(31,14,15,1,NULL,21,0,'activa','2026-05-13 04:25:47'),(32,7,16,1,NULL,21,0,'activa','2026-05-13 04:26:20'),(33,17,17,1,NULL,21,0,'activa','2026-05-13 04:28:16'),(34,13,18,1,NULL,21,0,'activa','2026-05-13 04:28:26'),(35,15,19,1,NULL,21,0,'activa','2026-05-13 04:28:38'),(36,16,20,1,NULL,21,0,'activa','2026-05-13 04:28:48'),(37,18,21,1,NULL,21,0,'activa','2026-05-13 04:28:58'),(38,6,22,1,NULL,21,0,'activa','2026-05-13 04:29:10'),(39,9,23,1,NULL,21,0,'activa','2026-05-13 04:29:18'),(40,20,7,1,NULL,9,0,'activa','2026-05-13 04:29:46'),(41,10,19,1,NULL,10,2,'activa','2026-05-14 17:57:26'),(42,4,13,1,18,NULL,2,'activa','2026-05-14 21:27:04'),(43,5,13,1,17,NULL,2,'activa','2026-05-14 21:27:36'),(44,21,1,1,NULL,9,0,'activa','2026-05-18 05:57:48');
/*!40000 ALTER TABLE `cargas_academicas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competencias`
--

DROP TABLE IF EXISTS `competencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competencias` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `codigo_minedu` varchar(5) DEFAULT NULL,
  `nombre_completo` text NOT NULL,
  `nombre_corto` varchar(120) DEFAULT NULL,
  `subarea_id` smallint(5) unsigned DEFAULT NULL,
  `area_id` smallint(5) unsigned DEFAULT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_subarea` (`subarea_id`),
  KEY `idx_area` (`area_id`),
  CONSTRAINT `competencias_ibfk_1` FOREIGN KEY (`subarea_id`) REFERENCES `subareas` (`id`),
  CONSTRAINT `competencias_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competencias`
--

LOCK TABLES `competencias` WRITE;
/*!40000 ALTER TABLE `competencias` DISABLE KEYS */;
INSERT INTO `competencias` (`id`, `codigo_minedu`, `nombre_completo`, `nombre_corto`, `subarea_id`, `area_id`, `orden`) VALUES (1,'C1','Se comunica en inglés como lengua extranjera.','Se comunica en inglés',NULL,4,1),(2,'C2','Lee diversos tipos de textos escritos en inglés como lengua extranjera','Lee y comprende en inglés',NULL,4,2),(3,'C3','Escribe diversos tipos de textos en inglés como lengua extranjera','Redacción en inglés',NULL,4,3),(4,'C4','Construye su identidad.','Construye su identidad',NULL,1,4),(5,'C5','Convive y participa democráticamente en la búsqueda del bien común.','Convive y participa por el bien común',NULL,1,5),(6,'C6','Construye interpretaciones históricas.','Construye interpretaciones históricas',NULL,1,6),(7,'C7','Gestiona responsablemente el espacio y el ambiente.','Gestiona el espacio y el ambiente',NULL,1,7),(8,'C8','Gestiona responsablemente los recursos económicos.','Gestiona los recursos económicos',NULL,1,8),(9,'C9','Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.','Identidad como persona amada por Dios',NULL,5,9),(10,'C10','Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa','Asume la experiencia de Dios en su proyecto de vida',NULL,5,10),(11,'C13','Se desenvuelve de manera autónoma a través de su motricidad.','Motricidad autónoma',NULL,2,11),(12,'C14','Asume una vida saludable.','Vida saludable',NULL,2,12),(13,'C15','Interactúa a través de sus habilidades sociomotrices.','Habilidades sociomotrices',NULL,2,13),(14,'C16','Se comunica oralmente en su lengua materna.','Se comunica oralmente en sulengua materna.',NULL,6,14),(15,'C17','Lee diversos tipos de textos escritos en su lengua materna.','Lee diversos tipos de textos escritos en su lengua materna',NULL,6,15),(16,'C18','Escribe diversos tipos de textos en su lengua materna.','Escribe diversos tipos de textos en su lengua materna',NULL,6,16),(17,'C19','Aprecia de manera crítica manifestaciones artístico-culturales.','Aprecia manifestaciones artísticas',NULL,3,17),(18,'C20','Crea proyectos desde los lenguajes artísticos.','Crea proyectos artísticos',NULL,3,18),(19,'C21','Resuelve problemas de cantidad.','Resuelve problemas de cantidad',4,NULL,19),(20,'C22','Resuelve problemas de regularidad, equivalencia y cambio.','Resuelve problemas de regularidad, equivalencia y cambio',5,NULL,20),(21,'C23','Resuelve problemas de forma, movimiento y localización.','Resuelve problemas de forma, movimiento y localización',6,NULL,21),(22,'C24','Resuelve problemas de gestión de datos e incertidumbre.','Gestión de datos',7,NULL,22),(23,'C25','Indaga mediante métodos científicos para construir sus conocimientos.','Indaga mediante el método científico',8,NULL,23),(24,'C26','Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.','Explica el mundo físico basándose en los seres vivos',9,NULL,24),(25,'C27','Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.','Diseña y construye soluciones tecnológicas',10,NULL,25),(26,'CT1','Se desenvuelve en entornos virtuales generados por las TIC.','Entornos virtuales / TIC',NULL,9,26),(27,'CT2','Gestiona su aprendizaje de manera autónoma.','Aprendizaje autónomo',NULL,9,27),(28,'C28','Construye su identidad.','Construye su identidad',NULL,10,1),(29,'C29','Convive y participa democráticamente en la búsqueda del bien común.','Convive democráticamente en la búsqueda del bien común',NULL,10,2),(30,'C30','Construye interpretaciones históricas.','Construye interpretaciones históricas',11,NULL,3),(31,'C31','Gestiona responsablemente el espacio y el ambiente.','Gestiona responsablemente el espacio y el ambiente',12,NULL,4),(32,'C32','Gestiona responsablemente los recursos económicos.','Gestiona responsablemente los recursos económicos',13,NULL,5),(33,'C33','Asume una vida saludable.','Asume una vida saludable.',NULL,11,6),(34,'C34','Interactúa a través de sus habilidades sociomotrices.','Interactúa a través de sus habilidades sociomotrices.',NULL,11,7),(35,'C35','Asume una vida saludable.','Asume una vida saludable.',NULL,11,8),(36,'C36','Aprecia de manera crítica manifestaciones artístico-culturales.','Aprecia de manera crítica manifestaciones artístico-culturales',NULL,12,9),(37,'C37','Crea proyectos desde los lenguajes artísticos.','Crea proyectos desde los lenguajes artísticos',NULL,12,10),(38,'C38','Se comunica oralmente en su lengua materna.','Se comunica oralmente',14,NULL,11),(39,'C39','Lee diversos tipos de textos escritos en su lengua materna.','Lee diversos tipos de textos',15,NULL,12),(40,'C40','Escribe diversos tipos de textos en su lengua materna.','Escribe diversos tipos de textos',16,NULL,13),(41,'C41','Se comunica oralmente.','Se comunica oralmente',NULL,13,14),(42,'C42','Lee diversos tipos de textos escritos.','Lee diversos tipos de textos',NULL,13,15),(43,'C43','Escribe diversos tipos de texto.','Escribe diversos tipos de textos',NULL,13,16),(44,'C44','Resuelve problemas de cantidad.','Resuelve problemas de cantidad',17,NULL,17),(45,'C45','Resuelve problemas de regularidad, equivalencia y cambio.','Resuelve problemas de regularidad, equivalencia y cambio',18,NULL,18),(46,'C46','Resuelve problemas de forma, movimiento y localización.','Resuelve problemas de forma, movimiento y localización',19,NULL,19),(47,'C47','Resuelve problemas de gestión de datos e incertidumbre.','Resuelve problemas de gestión de datos e incertidumbre',20,NULL,20),(48,'C48','Indaga mediante métodos científicos para construir sus conocimientos.','Indaga mediante métodos científicos',21,NULL,21),(49,'C49','Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.','Explica el mundo físico basándose en conocimientos sobre la Tierra y el Universo.',22,NULL,22),(50,'C50','Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.','Diseña y construye soluciones tecnológicas',23,NULL,23),(51,'C51','Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.','Construye su identidad como persona amada por Dios',NULL,14,24),(52,'C52','Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.','Asume la experiencia del encuentro con Dios en su vida',NULL,14,25),(53,'C53','Gestiona proyectos de emprendimiento económico o social.','Gestiona proyectos de emprendimiento',NULL,15,26),(54,'C54','Resuelve problemas de cantidad.','Resuelve problemas de cantidad',NULL,16,27),(55,'C55','Resuelve problemas de gestión de datos e incertidumbre.','Resuelve problemas de gestión de datos e incertidumbre',NULL,16,28),(56,'CT3','Se desenvuelve en entornos virtuales generados por las TIC.','Entornos virtuales / TIC',NULL,21,29),(57,'CT4','Gestiona su aprendizaje de manera autónoma.','Aprendizaje autónomo',NULL,21,30);
/*!40000 ALTER TABLE `competencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_horario`
--

DROP TABLE IF EXISTS `configuracion_horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_horario` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `anio_id` smallint(5) unsigned NOT NULL,
  `duracion_hora_min` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `hora_inicio_clases` time NOT NULL DEFAULT '07:45:00',
  `recreo_bloques` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recreo_bloques`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `anio_id` (`anio_id`),
  CONSTRAINT `configuracion_horario_ibfk_1` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_horario`
--

LOCK TABLES `configuracion_horario` WRITE;
/*!40000 ALTER TABLE `configuracion_horario` DISABLE KEYS */;
INSERT INTO `configuracion_horario` (`id`, `anio_id`, `duracion_hora_min`, `hora_inicio_clases`, `recreo_bloques`, `created_at`) VALUES (1,1,45,'13:10:00',NULL,'2026-05-11 23:17:04');
/*!40000 ALTER TABLE `configuracion_horario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `criterios`
--

DROP TABLE IF EXISTS `criterios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `criterios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `carga_id` int(10) unsigned NOT NULL,
  `competencia_id` smallint(5) unsigned NOT NULL,
  `periodo_id` smallint(5) unsigned NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `competencia_id` (`competencia_id`),
  KEY `periodo_id` (`periodo_id`),
  KEY `idx_carga_competencia_periodo` (`carga_id`,`competencia_id`,`periodo_id`),
  CONSTRAINT `criterios_ibfk_1` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `criterios_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`),
  CONSTRAINT `criterios_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `criterios`
--

LOCK TABLES `criterios` WRITE;
/*!40000 ALTER TABLE `criterios` DISABLE KEYS */;
INSERT INTO `criterios` (`id`, `carga_id`, `competencia_id`, `periodo_id`, `nombre`, `orden`, `created_at`, `updated_at`) VALUES (15,10,41,1,'Examen de entrada',1,'2026-05-13 02:54:50','2026-05-13 02:54:50'),(16,10,41,1,'Examen bimestral',2,'2026-05-13 02:55:11','2026-05-13 02:55:11'),(17,10,42,1,'Examen de entrada',1,'2026-05-13 02:55:22','2026-05-13 02:55:22'),(18,10,42,1,'Examen oral',2,'2026-05-13 02:55:33','2026-05-13 02:55:33'),(19,10,43,1,'Examen de entrada',1,'2026-05-13 02:55:49','2026-05-13 02:55:49'),(20,10,43,1,'Examen bimestral',2,'2026-05-13 02:56:05','2026-05-13 02:56:05'),(21,14,41,1,'Examen de entrada',1,'2026-05-13 02:59:52','2026-05-13 02:59:52'),(22,14,41,1,'Examen mensual',2,'2026-05-13 03:00:50','2026-05-13 03:00:50'),(23,14,41,1,'Examen bimestral',3,'2026-05-13 03:02:50','2026-05-13 03:02:50'),(24,14,42,1,'Examen de entrada',1,'2026-05-13 03:03:45','2026-05-13 03:03:45'),(25,14,42,1,'Examen oral',2,'2026-05-13 03:03:56','2026-05-13 03:03:56'),(26,14,42,1,'Examen mensual',3,'2026-05-13 03:04:12','2026-05-13 03:04:12'),(27,14,42,1,'Examen bimestral',4,'2026-05-13 03:04:33','2026-05-13 03:04:33'),(28,14,43,1,'Examen mensual',1,'2026-05-13 03:06:33','2026-05-13 03:06:33'),(29,14,43,1,'Examen bimestral',2,'2026-05-13 03:08:45','2026-05-13 03:08:45'),(31,38,54,1,'I Bimestre',1,'2026-05-13 04:30:53','2026-05-13 04:30:53'),(32,38,55,1,'I Bimestre',1,'2026-05-13 04:30:53','2026-05-13 04:30:53'),(33,32,54,1,'I Bimestre',1,'2026-05-13 22:24:53','2026-05-13 22:24:53'),(34,32,55,1,'I Bimestre',1,'2026-05-13 22:24:53','2026-05-13 22:24:53'),(35,8,45,1,'Examen de entrada',1,'2026-05-14 15:10:44','2026-05-14 15:10:44'),(36,8,45,1,'Examen mensual',2,'2026-05-14 15:13:37','2026-05-14 15:13:37'),(37,8,45,1,'Examen bimestral',3,'2026-05-14 15:13:50','2026-05-14 15:13:50'),(39,29,54,1,'I Bimestre',1,'2026-05-14 15:15:27','2026-05-14 15:15:27'),(40,29,55,1,'I Bimestre',1,'2026-05-14 15:15:27','2026-05-14 15:15:27'),(41,1,39,1,'Examen de entrada',1,'2026-05-14 15:17:16','2026-05-14 15:17:16'),(42,1,39,1,'Examen mensual',2,'2026-05-14 15:17:31','2026-05-14 15:17:31'),(43,1,39,1,'Examen bimestral',3,'2026-05-14 15:17:45','2026-05-14 15:17:45'),(45,9,47,1,'Examen de entrada',1,'2026-05-14 15:21:39','2026-05-14 15:21:39'),(46,9,47,1,'Examen mensual',2,'2026-05-14 15:21:45','2026-05-14 15:21:45'),(47,9,47,1,'Examen bimestral',3,'2026-05-14 15:21:50','2026-05-14 15:21:50'),(48,3,40,1,'Examen de entrada',1,'2026-05-14 15:23:33','2026-05-14 15:23:33'),(49,3,40,1,'Examen bimestral',2,'2026-05-14 15:23:39','2026-05-14 15:23:39'),(50,6,36,1,'Teoría de notas musicales',1,'2026-05-14 15:25:13','2026-05-14 15:25:13'),(51,6,37,1,'Habilidades de trompeta',1,'2026-05-14 15:25:40','2026-05-14 15:25:40'),(52,6,37,1,'Habilidades de Lira',2,'2026-05-14 15:25:58','2026-05-14 15:25:58'),(53,2,49,1,'Examen de entrada',1,'2026-05-14 15:29:11','2026-05-14 15:29:11'),(54,2,49,1,'Práctica calificada N°01',2,'2026-05-14 15:29:37','2026-05-14 15:29:37'),(55,2,49,1,'Práctica calificada N°02',3,'2026-05-14 15:30:25','2026-05-14 15:30:25'),(56,2,49,1,'Examen Mensual',4,'2026-05-14 15:30:43','2026-05-14 15:30:43'),(57,2,49,1,'Examen bimestral',5,'2026-05-14 15:30:55','2026-05-14 15:30:55'),(58,7,38,1,'Examen de entrada',1,'2026-05-14 15:33:07','2026-05-14 15:33:07'),(59,7,38,1,'Examen mensual',2,'2026-05-14 15:34:28','2026-05-14 15:34:28'),(60,7,38,1,'Examen bimestral',3,'2026-05-14 15:34:40','2026-05-14 15:34:40'),(61,4,31,1,'Examen de entrada',1,'2026-05-14 15:37:16','2026-05-14 15:37:16'),(62,4,31,1,'Examen mensual',2,'2026-05-14 15:37:27','2026-05-14 15:37:27'),(63,4,31,1,'Examen bimestral',3,'2026-05-14 15:37:38','2026-05-14 15:37:38'),(64,11,50,1,'Examen de entrada',1,'2026-05-14 15:40:42','2026-05-14 15:40:42'),(65,11,50,1,'Examen bimestral',2,'2026-05-14 15:40:52','2026-05-14 15:40:52'),(66,37,54,1,'I Bimestre',1,'2026-05-14 15:45:32','2026-05-14 15:45:32'),(67,37,55,1,'I Bimestre',1,'2026-05-14 15:45:32','2026-05-14 15:45:32'),(68,12,54,1,'Examen de entrada',1,'2026-05-14 16:36:48','2026-05-14 16:36:48'),(69,12,55,1,'Examen bimestral',1,'2026-05-14 16:36:56','2026-05-14 16:36:56'),(70,12,54,1,'Examen mensual',2,'2026-05-14 16:37:19','2026-05-14 16:37:19'),(71,5,33,1,'Examen mensual',1,'2026-05-14 16:38:38','2026-05-14 16:38:38'),(72,5,34,1,'Examen bimestral',1,'2026-05-14 16:38:49','2026-05-14 16:38:49'),(73,5,35,1,'Alimentación saludable',1,'2026-05-14 16:39:07','2026-05-14 16:39:07'),(74,29,56,1,'I Bimestre',1,'2026-05-14 18:02:25','2026-05-14 18:02:25'),(75,29,57,1,'I Bimestre',1,'2026-05-14 18:02:25','2026-05-14 18:02:25'),(76,41,28,1,'Examen de entrada',1,'2026-05-14 21:09:20','2026-05-14 21:09:20'),(77,41,28,1,'Examen mensual',2,'2026-05-14 21:09:33','2026-05-14 21:09:33'),(78,41,28,1,'Examen bimestral',3,'2026-05-14 21:09:44','2026-05-14 21:09:44'),(79,41,29,1,'Examen de entrada',1,'2026-05-14 21:11:35','2026-05-14 21:11:35'),(80,41,29,1,'Examen mensual',2,'2026-05-14 21:11:48','2026-05-14 21:11:48'),(81,17,28,1,'Examen de entrada',1,'2026-05-14 21:28:46','2026-05-14 21:28:46'),(82,17,28,1,'Examen bimestral',2,'2026-05-14 21:28:58','2026-05-14 21:28:58'),(83,17,28,1,'Examen bimestral',3,'2026-05-14 21:29:15','2026-05-14 21:29:15'),(84,17,29,1,'Proyecto grupal',1,'2026-05-14 21:30:45','2026-05-14 21:30:45'),(85,20,46,1,'Examen de entrada',1,'2026-05-14 21:31:57','2026-05-14 21:31:57'),(86,20,46,1,'Examen mensual',2,'2026-05-14 21:32:08','2026-05-14 21:32:08'),(87,20,46,1,'Examen bimestral',3,'2026-05-14 21:32:19','2026-05-14 21:32:19'),(88,21,30,1,'Examen de entrada',1,'2026-05-14 21:33:49','2026-05-14 21:33:49'),(89,21,30,1,'Examen mensual',2,'2026-05-14 21:34:01','2026-05-14 21:34:01'),(90,21,30,1,'Examen bimestral',3,'2026-05-14 21:34:11','2026-05-14 21:34:11'),(91,42,45,1,'Examen de entrada',1,'2026-05-14 21:38:59','2026-05-14 21:38:59'),(92,42,45,1,'Examen mensual',2,'2026-05-14 21:39:10','2026-05-14 21:39:10'),(93,42,45,1,'Examen bimestral',3,'2026-05-14 21:39:21','2026-05-14 21:39:21'),(94,26,48,1,'Examen de entrada',1,'2026-05-14 21:41:18','2026-05-14 21:41:18'),(95,26,48,1,'Examen mensual',2,'2026-05-14 21:41:30','2026-05-14 21:41:30'),(97,26,48,1,'Examen bimestral',3,'2026-05-14 21:41:54','2026-05-14 21:41:54'),(98,43,44,1,'Examen de entrada',1,'2026-05-14 21:46:58','2026-05-14 21:46:58'),(99,43,44,1,'Examen mensual',2,'2026-05-14 21:47:11','2026-05-14 21:47:11'),(100,43,44,1,'Examen bimestral',3,'2026-05-14 21:47:26','2026-05-14 21:47:26');
/*!40000 ALTER TABLE `criterios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estudiantes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(10) unsigned NOT NULL,
  `codigo_estudiante` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `persona_id` (`persona_id`),
  CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `grados`
--

DROP TABLE IF EXISTS `grados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grados` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `nivel_id` tinyint(3) unsigned NOT NULL,
  `numero` tinyint(3) unsigned NOT NULL,
  `nombre_display` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nivel_numero` (`nivel_id`,`numero`),
  CONSTRAINT `grados_ibfk_1` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grados`
--

LOCK TABLES `grados` WRITE;
/*!40000 ALTER TABLE `grados` DISABLE KEYS */;
INSERT INTO `grados` (`id`, `nivel_id`, `numero`, `nombre_display`) VALUES (1,1,1,'1°'),(2,1,2,'2°'),(3,1,3,'3°'),(4,1,4,'4°'),(5,1,5,'5°'),(6,1,6,'6°'),(7,2,1,'1°'),(8,2,2,'2°'),(9,2,3,'3°'),(10,2,4,'4°'),(11,2,5,'5°');
/*!40000 ALTER TABLE `grados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriculas`
--

DROP TABLE IF EXISTS `matriculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matriculas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(10) unsigned NOT NULL,
  `seccion_id` smallint(5) unsigned DEFAULT NULL,
  `anio_id` smallint(5) unsigned NOT NULL,
  `tipo_matricula` enum('regular','traslado_entrada') NOT NULL DEFAULT 'regular',
  `estado` enum('registrada','pendiente_documentos','observada','aprobada','retirada') NOT NULL DEFAULT 'registrada',
  `seccion_solicitada` varchar(5) DEFAULT NULL,
  `fecha_registro` date NOT NULL,
  `limite_documentos` date DEFAULT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `registrado_por` int(10) unsigned NOT NULL,
  `aprobado_por` int(10) unsigned DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estudiante_anio` (`estudiante_id`,`anio_id`),
  KEY `seccion_id` (`seccion_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `aprobado_por` (`aprobado_por`),
  KEY `idx_estado` (`estado`),
  KEY `idx_anio` (`anio_id`),
  CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`),
  CONSTRAINT `matriculas_ibfk_3` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`),
  CONSTRAINT `matriculas_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `matriculas_ibfk_5` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `niveles`
--

DROP TABLE IF EXISTS `niveles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `niveles` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `escala_boleta` enum('solo_literal','ambas') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `niveles`
--

LOCK TABLES `niveles` WRITE;
/*!40000 ALTER TABLE `niveles` DISABLE KEYS */;
INSERT INTO `niveles` (`id`, `nombre`, `codigo`, `escala_boleta`, `created_at`) VALUES (1,'Primaria','prim','solo_literal','2026-05-11 23:13:16'),(2,'Secundaria','sec','ambas','2026-05-11 23:13:16');
/*!40000 ALTER TABLE `niveles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periodos`
--

DROP TABLE IF EXISTS `periodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `periodos` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `anio_id` smallint(5) unsigned NOT NULL,
  `numero` tinyint(3) unsigned NOT NULL,
  `tipo` enum('bimestre','trimestre') NOT NULL DEFAULT 'bimestre',
  `nombre_display` varchar(30) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `limite_notas` datetime DEFAULT NULL,
  `estado` enum('pendiente','activo','cerrado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_anio_numero` (`anio_id`,`numero`),
  CONSTRAINT `periodos_ibfk_1` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periodos`
--

LOCK TABLES `periodos` WRITE;
/*!40000 ALTER TABLE `periodos` DISABLE KEYS */;
INSERT INTO `periodos` (`id`, `anio_id`, `numero`, `tipo`, `nombre_display`, `fecha_inicio`, `fecha_fin`, `limite_notas`, `estado`) VALUES (1,1,1,'bimestre','I Bimestre','2026-03-09','2026-05-15','2026-05-20 23:59:00','activo'),(2,1,2,'bimestre','II Bimestre','2026-05-19','2026-07-17',NULL,'pendiente'),(3,1,3,'bimestre','III Bimestre','2026-08-03','2026-10-02',NULL,'pendiente'),(4,1,4,'bimestre','IV Bimestre','2026-10-05','2026-12-04',NULL,'pendiente');
/*!40000 ALTER TABLE `periodos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personas`
--

DROP TABLE IF EXISTS `personas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`),
  KEY `idx_dni` (`dni`),
  KEY `idx_apellidos` (`apellido_paterno`,`apellido_materno`)
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personas`
--

LOCK TABLES `personas` WRITE;
/*!40000 ALTER TABLE `personas` DISABLE KEYS */;
INSERT INTO `personas` (`id`, `dni`, `apellido_paterno`, `apellido_materno`, `nombres`, `fecha_nacimiento`, `sexo`, `telefono`, `correo`, `direccion`, `created_at`, `updated_at`) VALUES (1,'00000000','Sistema','COCIAP','Administrador',NULL,NULL,NULL,'admin@cociap.edu.pe',NULL,'2026-05-11 23:13:16','2026-05-11 23:13:16'),(2,'12345678','GUILLERMO','CHAVEZ','LUIS WALDIR',NULL,'M','921114433','WALDIRGUILLERMOC@GMAIL.COM',NULL,'2026-05-11 23:15:50','2026-05-14 22:51:51'),(229,'20000001','BUENO','DE LA O','KAREN VIOLETA',NULL,'F','935321323','KBUENO@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:52:42','2026-05-12 03:52:42'),(230,'20000002','HUAYANEY','GRANADOS','KATTY JANETH',NULL,'F','935461123','KHUAYANEYG@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:53:26','2026-05-12 03:53:26'),(231,'20000003','SOTELO','ROQUE','SAARA',NULL,'F','934121111','SSOTELOR@COCIAVVG.EDU.PE',NULL,'2026-05-12 03:54:31','2026-05-12 03:54:31'),(232,'20000004','MENACHO','QUESADA','KETTY',NULL,'F','934000123','KMENACHOQ@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:55:56','2026-05-12 03:55:56'),(233,'20000005','CASTILLEJO','MORALES','NACHO EDUAR',NULL,'M','914034311','NCASTILLEJOM@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:57:43','2026-05-12 03:57:43'),(234,'20000006','NUÑUVERO','RAMIREZ','LESLIE',NULL,'F','940010099','LNUNUVEROR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:01:01','2026-05-12 04:01:01'),(235,'20000007','CLEMENTE','ANGELES','MARSHALL ALEKHENE',NULL,'M','905123433','MCLEMENTEA@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:03:24','2026-05-12 04:03:24'),(236,'20000008','MONTES','DEPAZ','HILBER CARLOS',NULL,'M','932414433','HMONTESD@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:04:20','2026-05-12 04:04:20'),(237,'20000009','OLIVERA','RAMIREZ','SILVIA MILAGROS',NULL,'F','941001433','SOLIVERAR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:05:09','2026-05-12 04:05:09'),(238,'20000010','ZAMBRANO','GUILLERMO','EDINZON ALEX',NULL,'M','941339090','EZAMBRANOG@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:06:05','2026-05-12 04:06:05'),(239,'20000011','PUMA','TINTA','MISHEL',NULL,'F','901441211','MPUMAT@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:07:28','2026-05-12 04:07:28'),(240,'20000012','CARRILLO','MEJIA','NORELI MILAGROS',NULL,'F','932121212','NCARRILLOM@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:08:28','2026-05-12 04:08:28'),(241,'20000013','MOSQUERA','DEPAZ','LEANDRO HERCILIO',NULL,'M','912131313','LMOSQUERAD@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:09:49','2026-05-12 04:09:49'),(242,'20000014','ANAYA','MORALES','VALOIS',NULL,'M','900123123','VANAYAM@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:11:38','2026-05-12 04:11:38'),(243,'20000015','BELLO','REYES','FREDY JESUS',NULL,'M','912443211','FBELLOR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:12:22','2026-05-12 04:12:22'),(244,'20000016','ZAVALETA','ROSALES','JORGE ARTURO DANIEL',NULL,'M','941000233','JZAVALETAR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:14:18','2026-05-12 04:14:18'),(245,'20000017','TRUJILLO','ALVAREZ','NELLY YUBITZA',NULL,'F','914112288','TYUBITZA@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:17:57','2026-05-12 04:17:57'),(246,'20000105','QUITO','REYES','JULEYSI CAROLINA',NULL,'F','941240002','JQUITOR@COCIAPVVG.EDU.PE',NULL,'2026-05-18 05:57:33','2026-05-18 05:57:33');
/*!40000 ALTER TABLE `personas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reglas_especiales`
--

DROP TABLE IF EXISTS `reglas_especiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reglas_especiales` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `area_id` smallint(5) unsigned NOT NULL,
  `nivel_id` tinyint(3) unsigned NOT NULL,
  `grado_desde` tinyint(3) unsigned NOT NULL,
  `grado_hasta` tinyint(3) unsigned NOT NULL,
  `nombre_override` varchar(120) DEFAULT NULL,
  `alias_override` varchar(80) DEFAULT NULL,
  `area_siagie_id` smallint(5) unsigned DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `area_id` (`area_id`),
  KEY `nivel_id` (`nivel_id`),
  KEY `area_siagie_id` (`area_siagie_id`),
  CONSTRAINT `reglas_especiales_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  CONSTRAINT `reglas_especiales_ibfk_2` FOREIGN KEY (`nivel_id`) REFERENCES `niveles` (`id`),
  CONSTRAINT `reglas_especiales_ibfk_3` FOREIGN KEY (`area_siagie_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reglas_especiales`
--

LOCK TABLES `reglas_especiales` WRITE;
/*!40000 ALTER TABLE `reglas_especiales` DISABLE KEYS */;
INSERT INTO `reglas_especiales` (`id`, `area_id`, `nivel_id`, `grado_desde`, `grado_hasta`, `nombre_override`, `alias_override`, `area_siagie_id`, `descripcion`) VALUES (1,12,2,4,5,'Arte y Cultura','(Raz. Matemático)',NULL,'En 4° y 5° de secundaria las notas de Raz. Matemático se registran en el campo Arte y Cultura del SIAGIE');
/*!40000 ALTER TABLE `reglas_especiales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `nombre`, `codigo`, `descripcion`, `created_at`) VALUES (1,'Administrador','admin','Acceso total al sistema','2026-05-11 23:13:16'),(2,'Registro Académico','registro_academico','Gestión de matrículas, traslados y documentos oficiales','2026-05-11 23:13:16'),(3,'Director General','director_general','Supervisión de todos los niveles','2026-05-11 23:13:16'),(4,'Director EBR','director_ebr','Supervisión de su nivel educativo','2026-05-11 23:13:16'),(5,'Secretaria','secretaria','Registro de matrículas y atención','2026-05-11 23:13:16'),(6,'Docente','docente','Registro de calificaciones de sus cargas','2026-05-11 23:13:16'),(7,'Padre de Familia','padre','Consulta del progreso de su menor hijo','2026-05-11 23:13:16');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secciones`
--

DROP TABLE IF EXISTS `secciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `secciones` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `grado_id` tinyint(3) unsigned NOT NULL,
  `anio_id` smallint(5) unsigned NOT NULL,
  `nombre` varchar(5) NOT NULL,
  `tutor_id` int(10) unsigned DEFAULT NULL,
  `es_unidocente` tinyint(1) NOT NULL DEFAULT 0,
  `estado_nomina` enum('borrador','aprobada') NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grado_anio_seccion` (`grado_id`,`anio_id`,`nombre`),
  KEY `anio_id` (`anio_id`),
  KEY `tutor_id` (`tutor_id`),
  CONSTRAINT `secciones_ibfk_1` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`),
  CONSTRAINT `secciones_ibfk_2` FOREIGN KEY (`anio_id`) REFERENCES `anios_academicos` (`id`),
  CONSTRAINT `secciones_ibfk_3` FOREIGN KEY (`tutor_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secciones`
--

LOCK TABLES `secciones` WRITE;
/*!40000 ALTER TABLE `secciones` DISABLE KEYS */;
INSERT INTO `secciones` (`id`, `grado_id`, `anio_id`, `nombre`, `tutor_id`, `es_unidocente`, `estado_nomina`, `created_at`) VALUES (1,1,1,'A',21,1,'aprobada','2026-05-11 23:15:50'),(2,1,1,'B',NULL,1,'aprobada','2026-05-11 23:16:08'),(3,2,1,'A',NULL,1,'aprobada','2026-05-11 23:16:08'),(4,2,1,'B',NULL,1,'aprobada','2026-05-11 23:16:08'),(5,3,1,'A',NULL,1,'aprobada','2026-05-11 23:16:08'),(6,3,1,'B',NULL,1,'aprobada','2026-05-11 23:16:08'),(7,4,1,'A',20,0,'aprobada','2026-05-11 23:16:08'),(8,4,1,'B',NULL,0,'aprobada','2026-05-11 23:16:08'),(9,5,1,'A',NULL,0,'aprobada','2026-05-11 23:16:08'),(10,5,1,'B',NULL,0,'aprobada','2026-05-11 23:16:08'),(11,6,1,'A',NULL,0,'aprobada','2026-05-11 23:16:08'),(12,6,1,'B',NULL,0,'aprobada','2026-05-11 23:16:08'),(13,7,1,'A',2,0,'aprobada','2026-05-11 23:16:08'),(14,7,1,'B',5,0,'aprobada','2026-05-11 23:16:08'),(15,7,1,'C',14,0,'aprobada','2026-05-11 23:16:08'),(16,8,1,'A',7,0,'aprobada','2026-05-11 23:16:08'),(17,8,1,'B',17,0,'aprobada','2026-05-11 23:16:08'),(18,9,1,'A',13,0,'aprobada','2026-05-11 23:16:08'),(19,9,1,'B',15,0,'aprobada','2026-05-11 23:16:08'),(20,10,1,'A',16,0,'aprobada','2026-05-11 23:16:08'),(21,10,1,'B',18,0,'aprobada','2026-05-11 23:16:08'),(22,11,1,'A',6,0,'aprobada','2026-05-11 23:16:08'),(23,11,1,'B',9,0,'aprobada','2026-05-11 23:16:08');
/*!40000 ALTER TABLE `secciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones_horario`
--

DROP TABLE IF EXISTS `sesiones_horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sesiones_horario` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `carga_id` int(10) unsigned NOT NULL,
  `bloque_id` smallint(5) unsigned NOT NULL,
  `seccion_id` smallint(5) unsigned NOT NULL,
  `docente_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seccion_bloque` (`seccion_id`,`bloque_id`),
  UNIQUE KEY `uq_docente_bloque` (`docente_id`,`bloque_id`),
  KEY `carga_id` (`carga_id`),
  KEY `bloque_id` (`bloque_id`),
  CONSTRAINT `sesiones_horario_ibfk_1` FOREIGN KEY (`carga_id`) REFERENCES `cargas_academicas` (`id`),
  CONSTRAINT `sesiones_horario_ibfk_2` FOREIGN KEY (`bloque_id`) REFERENCES `bloques_horario` (`id`),
  CONSTRAINT `sesiones_horario_ibfk_3` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`),
  CONSTRAINT `sesiones_horario_ibfk_4` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones_horario`
--

LOCK TABLES `sesiones_horario` WRITE;
/*!40000 ALTER TABLE `sesiones_horario` DISABLE KEYS */;
INSERT INTO `sesiones_horario` (`id`, `carga_id`, `bloque_id`, `seccion_id`, `docente_id`) VALUES (19,14,18,13,6),(20,13,19,13,7),(22,16,22,13,9),(24,18,24,13,11),(28,22,28,13,15),(29,19,25,13,12),(30,19,29,13,12),(31,23,30,13,16),(32,24,31,13,17),(36,15,20,13,8),(37,15,34,13,8),(39,27,36,13,19),(41,25,32,13,18),(42,25,38,13,18),(43,2,19,19,12),(44,1,39,19,7),(45,4,40,19,16),(46,5,22,19,19),(47,6,25,19,11),(48,7,26,19,15),(50,9,28,19,8),(51,11,41,19,17),(52,10,42,19,6),(53,12,31,19,18),(54,3,43,19,9),(56,28,37,13,20),(57,21,27,13,14),(58,20,26,13,13),(61,41,35,19,10),(62,17,44,13,10),(63,17,33,13,10),(64,8,47,23,2),(65,42,21,13,4),(67,26,35,13,17),(68,43,42,13,5);
/*!40000 ALTER TABLE `sesiones_horario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subareas`
--

DROP TABLE IF EXISTS `subareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subareas` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `area_id` smallint(5) unsigned NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_area` (`area_id`),
  CONSTRAINT `subareas_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subareas`
--

LOCK TABLES `subareas` WRITE;
/*!40000 ALTER TABLE `subareas` DISABLE KEYS */;
INSERT INTO `subareas` (`id`, `area_id`, `nombre`, `orden`) VALUES (1,6,'Comunicación',1),(2,6,'Plan Lector',2),(3,6,'Razonamiento Verbal',3),(4,7,'Aritmética',1),(5,7,'Álgebra',2),(6,7,'Geometría',3),(7,7,'Razonamiento Matemático',4),(8,8,'Química',1),(9,8,'Biología',2),(10,8,'Física',3),(11,17,'Historia',1),(12,17,'Geografía',2),(13,17,'Economía',3),(14,18,'Razonamiento Verbal',1),(15,18,'Literatura',2),(16,18,'Lenguaje',3),(17,19,'Aritmética',1),(18,19,'Álgebra',2),(19,19,'Geometría',3),(20,19,'Trigonometría',4),(21,20,'Química',1),(22,20,'Biología',2),(23,20,'Física',3);
/*!40000 ALTER TABLE `subareas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `persona_id` int(10) unsigned NOT NULL,
  `rol_id` tinyint(3) unsigned NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `sesion_token` varchar(64) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `persona_id` (`persona_id`),
  KEY `rol_id` (`rol_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` (`id`, `persona_id`, `rol_id`, `password_hash`, `ultimo_acceso`, `sesion_token`, `estado`, `created_at`, `updated_at`) VALUES (1,1,1,'$2y$10$uYEa/sZHfN6Rj5XRdY861euNiXsccWZk0SynNzcFebqNf9V1j2Pfy','2026-05-18 01:09:19','c44f7838307c847bf11f030a192be4d116d7200352e5a4abb29c417aeb3b283e','activo','2026-05-11 23:13:16','2026-05-18 06:09:19'),(2,2,6,'$2y$10$HfNJaWDDQrGujO9FkqlAjeO2l2t46KGasEY2W3cz2a4Y2jlcl9.3O','2026-05-14 17:09:14',NULL,'activo','2026-05-11 23:15:50','2026-05-14 22:09:34'),(4,229,6,'$2y$12$yOODf6OtZsuUAif9xeLxduku8De.FgSpIWa4u1JT1xYQd3MYvoe5e','2026-05-14 16:38:54',NULL,'activo','2026-05-12 03:52:42','2026-05-14 21:40:04'),(5,230,6,'$2y$12$Y1ySvwJQCIMS4JFLZ0edGeaFrsCLgEWcZA6ZSOrkTVg4F4dRZ2mma','2026-05-14 16:46:49',NULL,'activo','2026-05-12 03:53:26','2026-05-14 21:48:36'),(6,231,6,'$2y$12$rHPNGdvo098umnXfM9snfeNK0c4IiWx18rPM6C9sVZqVicWjg0kd6','2026-05-14 16:08:36',NULL,'activo','2026-05-12 03:54:31','2026-05-14 21:08:39'),(7,232,6,'$2y$12$A3mky7IdWRu8.anguaW76.kXx8pY6BBqMzl2LVvdz5u6MrzDcybZW','2026-05-14 16:45:17',NULL,'activo','2026-05-12 03:55:56','2026-05-14 21:46:08'),(8,233,6,'$2y$12$DaCNDK45/8V8AMMo4B.rVeBitdSoYu8QeefKud63ZDOwt4nDmEqwS','2026-05-14 16:28:20',NULL,'activo','2026-05-12 03:57:44','2026-05-14 21:28:22'),(9,234,6,'$2y$12$2j/Yp8JGm1QjEze0XvuU1e02GoGUKmxjAmHqIOWkhBzrx5RipkbDa','2026-05-14 16:28:27',NULL,'activo','2026-05-12 04:01:01','2026-05-14 21:28:32'),(10,235,6,'$2y$12$DeJeT9DcroXJSYUOB3bGwuFGYfCM1Va.zIUvI3tZnuTkPttRiC1lC','2026-05-14 16:28:36',NULL,'activo','2026-05-12 04:03:25','2026-05-14 21:31:44'),(11,236,6,'$2y$12$CFqJrcTyltnLow0KtILXb.8/SR54YJe3zSZarzMdgmTR8iDTfxtvi','2026-05-14 16:28:03',NULL,'activo','2026-05-12 04:04:20','2026-05-14 21:28:04'),(12,237,6,'$2y$12$iz6.x5mDUSO7n0NERFcMF.oMPLk4N6VxMyH1vL3Iuosa4ed1CqLae','2026-05-14 16:28:08',NULL,'activo','2026-05-12 04:05:09','2026-05-14 21:28:10'),(13,238,6,'$2y$12$WOs0WETmqYhFh62idSvSQeFThUIwftKs3z8Jgm0Nz91bJVT0BFQ5.','2026-05-14 16:31:51',NULL,'activo','2026-05-12 04:06:05','2026-05-14 21:33:35'),(14,239,6,'$2y$12$DXeRfPwJUMI4wqRfwB8E5.JuM2vx1ILaR90y.scciUqSUn2KiFxU.','2026-05-14 16:33:41',NULL,'activo','2026-05-12 04:07:28','2026-05-14 21:35:37'),(15,240,6,'$2y$12$hD8yR0xd9//5CFpLnlorTuO/4h84J4ISYYKFunEpARF42KAou6uZe','2026-05-14 16:40:32',NULL,'activo','2026-05-12 04:08:28','2026-05-14 21:40:34'),(16,241,6,'$2y$12$AJ.8JGgstj/RnBuQYTlgLuwUFFDv2wA16AITO102xdL5VNSh8lGSC','2026-05-14 16:40:22',NULL,'activo','2026-05-12 04:09:49','2026-05-14 21:40:24'),(17,242,6,'$2y$12$/nxt33x9NKN/wUCyt3zw9uwJgfAdjrhoV15LltBoiXiWmQb56qFfi','2026-05-14 16:41:08',NULL,'activo','2026-05-12 04:11:39','2026-05-14 21:42:31'),(18,243,6,'$2y$12$R6AnOgX.ARv32DSZpkYAreeMDEOWvvj4RpohBZwVGBLpwxAQd2.LW','2026-05-14 11:40:59',NULL,'activo','2026-05-12 04:12:22','2026-05-14 16:41:01'),(19,244,6,'$2y$12$./3qJOg80fiSdEj0BjZqweBfv6csBSZeMUHleZCTMoEgTIIuZCNRm','2026-05-14 11:38:30',NULL,'activo','2026-05-12 04:14:18','2026-05-14 16:40:44'),(20,245,6,'$2y$12$tPXKSUb6qrc.5v0ajEA/rOi/5rHPieMXh4Pz3AqcV3PCD4X/Y/lnW','2026-05-14 12:59:12',NULL,'activo','2026-05-12 04:17:57','2026-05-14 17:59:25'),(21,246,6,'$2y$12$scWP2ZRcBHd66G3D3z5KlecfGrWPMRPCcXHNTMvsCBYO8JqKAb14G',NULL,NULL,'activo','2026-05-18 05:57:33','2026-05-18 05:57:33');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vinculo_familiar`
--

DROP TABLE IF EXISTS `vinculo_familiar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vinculo_familiar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(10) unsigned NOT NULL,
  `apoderado_id` int(10) unsigned NOT NULL,
  `tipo_vinculo` enum('padre','madre','apoderado') NOT NULL,
  `es_responsable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estudiante_tipo` (`estudiante_id`,`tipo_vinculo`),
  KEY `apoderado_id` (`apoderado_id`),
  CONSTRAINT `vinculo_familiar_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `vinculo_familiar_ibfk_2` FOREIGN KEY (`apoderado_id`) REFERENCES `apoderados` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vinculo_familiar`
--

LOCK TABLES `vinculo_familiar` WRITE;
/*!40000 ALTER TABLE `vinculo_familiar` DISABLE KEYS */;
INSERT INTO `vinculo_familiar` (`id`, `estudiante_id`, `apoderado_id`, `tipo_vinculo`, `es_responsable`) VALUES (1,60,1,'madre',1);
/*!40000 ALTER TABLE `vinculo_familiar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'siga_cociap'
--

--
-- Dumping routines for database 'siga_cociap'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-18  1:17:26

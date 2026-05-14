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
INSERT INTO `anios_academicos` VALUES (1,2026,'2026-03-09','2026-12-18','activo','2026-05-11 23:15:50');
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
INSERT INTO `apoderados` VALUES (1,8,'2026-05-11 23:15:50');
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
INSERT INTO `areas` VALUES (1,1,'Personal Social','Personal Social',NULL,'Personal Social','area_curso',2,1),(2,1,'Educación Física','Educación Física',NULL,'Educación Física','area_curso',4,1),(3,1,'Arte y Cultura','Arte y Cultura',NULL,'Arte y Cultura','area_curso',6,1),(4,1,'Inglés','Inglés como Lengua Extranjera',NULL,'Inglés como Lengua Extranjera','area_curso',1,1),(5,1,'Educación Religiosa','Educación Religiosa',NULL,'Educación Religiosa','area_curso',3,1),(6,1,'Comunicación','Comunicación',NULL,'Comunicación','con_subareas',5,1),(7,1,'Matemática','Matemática',NULL,'Matemática','con_subareas',7,1),(8,1,'Ciencia y Tecnología','Ciencia y Tecnología',NULL,'Ciencia y Tecnología','con_subareas',8,1),(9,1,'Competencias Transversales','Comp. Transv.',NULL,NULL,'transversal',9,1),(10,2,'Desarrollo Personal, Ciudadanía y Cívica','DPCC',NULL,'Desarrollo Personal, Ciudadanía y Cívica','area_curso',1,1),(11,2,'Educación Física','Educación Física',NULL,'Educación Física','area_curso',3,1),(12,2,'Arte y Cultura','Arte y Cultura',NULL,'Arte y Cultura','area_curso',4,1),(13,2,'Inglés','Inglés',NULL,'Inglés como Lengua Extranjera','area_curso',6,1),(14,2,'Educación Religiosa','Educación Religiosa','(Ética y Valores)','Educación Religiosa','area_curso',10,1),(15,2,'Educación para el Trabajo','EPT','(Habilidades Pedagógicas)','Educación para el Trabajo','area_curso',11,1),(16,2,'Taller de Razonamiento Matemático','Taller Raz. Matemático',NULL,'Educación Religiosa','area_curso',8,1),(17,2,'Ciencias Sociales','Ciencias Sociales',NULL,'Ciencias Sociales','con_subareas',2,1),(18,2,'Comunicación','Comunicación',NULL,'Comunicación','con_subareas',5,1),(19,2,'Matemática','Matemática',NULL,'Matemática','con_subareas',7,1),(20,2,'Ciencia y Tecnología','Ciencia y Tecnología',NULL,'Ciencia y Tecnología','con_subareas',9,1),(21,2,'Competencias Transversales','Comp. Transv.',NULL,NULL,'transversal',12,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bloqueos_competencia`
--

LOCK TABLES `bloqueos_competencia` WRITE;
/*!40000 ALTER TABLE `bloqueos_competencia` DISABLE KEYS */;
INSERT INTO `bloqueos_competencia` VALUES (131,10,41,1,6,'2026-05-12 21:58:23'),(132,10,42,1,6,'2026-05-12 21:58:47'),(133,10,43,1,6,'2026-05-12 21:59:16'),(134,14,41,1,6,'2026-05-12 22:03:35'),(135,14,42,1,6,'2026-05-12 22:06:20'),(136,14,43,1,6,'2026-05-12 22:11:02'),(137,38,54,1,6,'2026-05-12 23:53:35'),(138,38,55,1,6,'2026-05-13 00:01:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bloques_horario`
--

LOCK TABLES `bloques_horario` WRITE;
/*!40000 ALTER TABLE `bloques_horario` DISABLE KEYS */;
INSERT INTO `bloques_horario` VALUES (1,1,'lunes',1,'16:30:00','17:20:00'),(2,1,'lunes',2,'17:20:00','18:10:00'),(3,1,'lunes',3,'13:00:00','13:50:00'),(4,1,'lunes',4,'13:50:00','14:40:00'),(5,1,'lunes',5,'14:40:00','15:30:00'),(6,1,'lunes',6,'15:30:00','16:10:00'),(7,1,'lunes',7,'16:30:00','17:15:00'),(8,1,'lunes',8,'17:15:00','18:00:00'),(9,1,'lunes',9,'18:00:00','18:45:00'),(10,1,'lunes',10,'13:00:00','13:45:00'),(11,1,'martes',1,'13:00:00','13:45:00'),(12,1,'lunes',11,'13:45:00','14:30:00'),(13,1,'lunes',12,'14:30:00','15:15:00'),(14,1,'martes',2,'13:45:00','14:30:00'),(15,1,'martes',3,'14:30:00','15:15:00'),(16,1,'martes',4,'15:15:00','16:00:00'),(17,1,'lunes',13,'13:10:00','13:55:00'),(18,1,'lunes',14,'14:40:00','16:10:00'),(19,1,'lunes',15,'13:10:00','14:40:00'),(20,1,'lunes',16,'16:35:00','17:20:00'),(21,1,'lunes',17,'17:20:00','18:50:00'),(22,1,'martes',5,'13:10:00','14:40:00'),(23,1,'lunes',18,'14:40:00','15:25:00'),(24,1,'martes',6,'15:25:00','16:10:00'),(25,1,'martes',7,'16:35:00','17:20:00'),(26,1,'martes',8,'17:20:00','18:50:00'),(27,1,'miercoles',1,'13:10:00','14:40:00'),(28,1,'miercoles',2,'14:40:00','16:10:00'),(29,1,'miercoles',3,'16:35:00','17:20:00'),(30,1,'miercoles',4,'17:20:00','18:50:00'),(31,1,'jueves',1,'14:40:00','16:10:00'),(32,1,'jueves',2,'16:35:00','17:20:00'),(33,1,'jueves',3,'17:20:00','18:05:00'),(34,1,'jueves',4,'18:05:00','18:50:00'),(35,1,'viernes',1,'13:10:00','14:40:00'),(36,1,'viernes',2,'14:40:00','16:10:00'),(37,1,'viernes',3,'17:20:00','18:05:00'),(38,1,'viernes',4,'18:05:00','18:50:00'),(39,1,'lunes',19,'16:35:00','18:05:00'),(40,1,'lunes',20,'18:05:00','18:50:00'),(41,1,'miercoles',5,'16:35:00','18:05:00'),(42,1,'jueves',5,'13:10:00','14:40:00'),(43,1,'jueves',6,'16:35:00','18:05:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=449 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones`
--

LOCK TABLES `calificaciones` WRITE;
/*!40000 ALTER TABLE `calificaciones` DISABLE KEYS */;
INSERT INTO `calificaciones` VALUES (294,78,10,1,41,16,'','2026-05-12 21:55:04','2026-05-12 21:58:19',6),(295,79,10,1,41,14,'','2026-05-12 21:55:04','2026-05-12 21:58:19',6),(296,80,10,1,41,13,'Necesitas mejorar, sigue intentándolo.','2026-05-12 21:55:04','2026-05-12 21:58:19',6),(297,81,10,1,41,14,'','2026-05-12 21:55:04','2026-05-12 21:58:19',6),(298,82,10,1,41,14,'','2026-05-12 21:55:04','2026-05-12 21:58:19',6),(304,78,10,1,42,15,'','2026-05-12 21:55:26','2026-05-12 21:58:46',6),(305,79,10,1,42,15,'','2026-05-12 21:55:26','2026-05-12 21:58:46',6),(306,80,10,1,42,14,'','2026-05-12 21:55:26','2026-05-12 21:58:46',6),(307,81,10,1,42,13,'Necesitas mejorar, sigue intentándolo','2026-05-12 21:55:26','2026-05-12 21:58:46',6),(308,82,10,1,42,15,'','2026-05-12 21:55:26','2026-05-12 21:58:46',6),(314,78,10,1,43,16,'','2026-05-12 21:55:55','2026-05-12 21:59:13',6),(315,79,10,1,43,16,'','2026-05-12 21:55:55','2026-05-12 21:59:13',6),(316,80,10,1,43,18,'Excelente, sigue así, has demostrado destacar en esta competencia.','2026-05-12 21:55:55','2026-05-12 21:59:13',6),(317,81,10,1,43,14,'','2026-05-12 21:55:55','2026-05-12 21:59:13',6),(318,82,10,1,43,16,'','2026-05-12 21:55:55','2026-05-12 21:59:13',6),(359,113,14,1,41,14,'','2026-05-12 22:00:36','2026-05-12 22:03:33',6),(360,114,14,1,41,14,'','2026-05-12 22:00:36','2026-05-12 22:03:33',6),(361,115,14,1,41,16,'Muy bien, sigue mejorando','2026-05-12 22:00:36','2026-05-12 22:03:33',6),(362,116,14,1,41,14,'','2026-05-12 22:00:36','2026-05-12 22:03:33',6),(363,117,14,1,41,16,'Muy bien, sigue mejorando','2026-05-12 22:00:36','2026-05-12 22:03:33',6),(379,113,14,1,42,15,'','2026-05-12 22:03:49','2026-05-12 22:06:17',6),(380,114,14,1,42,14,'Puedes seguir mejorando.','2026-05-12 22:03:49','2026-05-12 22:06:17',6),(381,115,14,1,42,15,'','2026-05-12 22:03:49','2026-05-12 22:06:17',6),(382,116,14,1,42,15,'','2026-05-12 22:03:49','2026-05-12 22:06:17',6),(383,117,14,1,42,14,'Puedes seguir mejorando.','2026-05-12 22:03:49','2026-05-12 22:06:17',6),(414,113,14,1,43,15,'','2026-05-12 22:06:38','2026-05-12 22:11:01',6),(415,114,14,1,43,14,'','2026-05-12 22:06:38','2026-05-12 22:11:01',6),(416,115,14,1,43,13,'Necesitas practicar mucho más, aprovecha las vacaciones para mejorar en esta competencia.','2026-05-12 22:06:38','2026-05-12 22:11:01',6),(417,116,14,1,43,15,'','2026-05-12 22:06:38','2026-05-12 22:11:01',6),(418,117,14,1,43,14,'','2026-05-12 22:06:38','2026-05-12 22:11:01',6),(439,120,38,1,54,14,'','2026-05-12 23:41:18','2026-05-12 23:53:32',6),(440,121,38,1,54,14,'','2026-05-12 23:41:18','2026-05-12 23:53:32',6),(441,122,38,1,54,14,'','2026-05-12 23:41:18','2026-05-12 23:53:32',6),(442,123,38,1,54,14,'','2026-05-12 23:41:18','2026-05-12 23:53:32',6),(443,124,38,1,54,14,'','2026-05-12 23:41:19','2026-05-12 23:53:32',6),(444,120,38,1,55,15,'','2026-05-13 00:01:37','2026-05-13 00:01:42',6),(445,121,38,1,55,15,'','2026-05-13 00:01:37','2026-05-13 00:01:42',6),(446,122,38,1,55,15,'','2026-05-13 00:01:37','2026-05-13 00:01:42',6),(447,123,38,1,55,15,'','2026-05-13 00:01:37','2026-05-13 00:01:42',6),(448,124,38,1,55,15,'','2026-05-13 00:01:37','2026-05-13 00:01:42',6);
/*!40000 ALTER TABLE `calificaciones` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones_criterio`
--

LOCK TABLES `calificaciones_criterio` WRITE;
/*!40000 ALTER TABLE `calificaciones_criterio` DISABLE KEYS */;
INSERT INTO `calificaciones_criterio` VALUES (86,15,78,18,'2026-05-12 21:55:04','2026-05-12 21:56:17'),(87,15,79,14,'2026-05-12 21:55:04','2026-05-12 21:56:17'),(88,15,80,12,'2026-05-12 21:55:04','2026-05-12 21:56:17'),(89,15,81,14,'2026-05-12 21:55:04','2026-05-12 21:56:17'),(90,15,82,14,'2026-05-12 21:55:04','2026-05-12 21:56:17'),(91,16,78,14,'2026-05-12 21:55:16','2026-05-12 21:56:16'),(92,16,79,13,'2026-05-12 21:55:16','2026-05-12 21:56:16'),(93,16,80,13,'2026-05-12 21:55:16','2026-05-12 21:56:16'),(94,16,81,14,'2026-05-12 21:55:16','2026-05-12 21:56:16'),(95,16,82,14,'2026-05-12 21:55:16','2026-05-12 21:56:16'),(96,17,78,14,'2026-05-12 21:55:26','2026-05-12 21:56:15'),(97,17,79,14,'2026-05-12 21:55:26','2026-05-12 21:56:15'),(98,17,80,13,'2026-05-12 21:55:26','2026-05-12 21:56:15'),(99,17,81,13,'2026-05-12 21:55:26','2026-05-12 21:56:15'),(100,17,82,14,'2026-05-12 21:55:26','2026-05-12 21:56:15'),(101,18,78,15,'2026-05-12 21:55:39','2026-05-12 21:56:14'),(102,18,79,15,'2026-05-12 21:55:39','2026-05-12 21:56:14'),(103,18,80,15,'2026-05-12 21:55:39','2026-05-12 21:56:14'),(104,18,81,13,'2026-05-12 21:55:39','2026-05-12 21:56:14'),(105,18,82,15,'2026-05-12 21:55:39','2026-05-12 21:56:14'),(106,19,78,17,'2026-05-12 21:55:55','2026-05-12 21:58:53'),(107,19,79,17,'2026-05-12 21:55:55','2026-05-12 21:58:53'),(108,19,80,18,'2026-05-12 21:55:55','2026-05-12 21:58:53'),(109,19,81,14,'2026-05-12 21:55:55','2026-05-12 21:58:53'),(110,19,82,17,'2026-05-12 21:55:55','2026-05-12 21:58:53'),(111,20,78,15,'2026-05-12 21:56:10','2026-05-12 21:58:54'),(112,20,79,14,'2026-05-12 21:56:10','2026-05-12 21:58:54'),(113,20,82,15,'2026-05-12 21:56:10','2026-05-12 21:58:54'),(147,21,113,14,'2026-05-12 22:00:36','2026-05-12 22:03:04'),(148,21,114,15,'2026-05-12 22:00:36','2026-05-12 22:03:04'),(149,21,115,17,'2026-05-12 22:00:36','2026-05-12 22:03:04'),(150,21,116,14,'2026-05-12 22:00:36','2026-05-12 22:03:04'),(151,21,117,17,'2026-05-12 22:00:36','2026-05-12 22:03:04'),(152,23,113,14,'2026-05-12 22:02:56',NULL),(153,23,114,13,'2026-05-12 22:02:56',NULL),(154,23,115,14,'2026-05-12 22:02:56',NULL),(155,23,116,14,'2026-05-12 22:02:56',NULL),(156,23,117,15,'2026-05-12 22:02:56',NULL),(157,22,113,15,'2026-05-12 22:03:03',NULL),(158,22,114,15,'2026-05-12 22:03:03',NULL),(159,22,115,16,'2026-05-12 22:03:03',NULL),(160,22,116,15,'2026-05-12 22:03:03',NULL),(161,22,117,16,'2026-05-12 22:03:03',NULL),(167,24,113,14,'2026-05-12 22:03:49','2026-05-12 22:04:23'),(168,24,114,14,'2026-05-12 22:03:49','2026-05-12 22:04:23'),(169,24,115,15,'2026-05-12 22:03:49','2026-05-12 22:04:23'),(170,24,116,15,'2026-05-12 22:03:49','2026-05-12 22:04:23'),(171,24,117,13,'2026-05-12 22:03:49','2026-05-12 22:04:23'),(172,25,113,15,'2026-05-12 22:04:01','2026-05-12 22:04:20'),(173,25,114,14,'2026-05-12 22:04:01','2026-05-12 22:04:20'),(174,25,115,14,'2026-05-12 22:04:01','2026-05-12 22:04:20'),(175,25,116,15,'2026-05-12 22:04:01','2026-05-12 22:04:20'),(176,25,117,15,'2026-05-12 22:04:01','2026-05-12 22:04:20'),(182,26,113,17,'2026-05-12 22:04:18',NULL),(183,26,114,14,'2026-05-12 22:04:18',NULL),(184,26,115,14,'2026-05-12 22:04:18',NULL),(185,26,116,17,'2026-05-12 22:04:18',NULL),(186,26,117,13,'2026-05-12 22:04:18',NULL),(197,27,113,14,'2026-05-12 22:04:39',NULL),(198,27,114,15,'2026-05-12 22:04:39',NULL),(199,27,115,15,'2026-05-12 22:04:39',NULL),(200,27,116,14,'2026-05-12 22:04:39',NULL),(201,27,117,14,'2026-05-12 22:04:39',NULL),(202,28,113,14,'2026-05-12 22:06:38','2026-05-12 22:10:18'),(203,28,114,13,'2026-05-12 22:06:38','2026-05-12 22:10:18'),(204,28,115,13,'2026-05-12 22:06:38','2026-05-12 22:10:18'),(205,28,116,15,'2026-05-12 22:06:38','2026-05-12 22:10:18'),(206,28,117,14,'2026-05-12 22:06:38','2026-05-12 22:10:18'),(212,29,113,15,'2026-05-12 22:08:51',NULL),(213,29,114,14,'2026-05-12 22:08:51',NULL),(214,29,115,13,'2026-05-12 22:08:51',NULL),(215,29,116,15,'2026-05-12 22:08:51',NULL),(216,29,117,13,'2026-05-12 22:08:51',NULL),(227,31,122,14,'2026-05-12 23:41:18',NULL),(228,31,121,14,'2026-05-12 23:41:18',NULL),(229,31,120,14,'2026-05-12 23:41:18',NULL),(230,31,124,14,'2026-05-12 23:41:18',NULL),(231,31,123,14,'2026-05-12 23:41:18',NULL),(232,32,120,15,'2026-05-13 00:01:37',NULL),(233,32,121,15,'2026-05-13 00:01:37',NULL),(234,32,122,15,'2026-05-13 00:01:37',NULL),(235,32,123,15,'2026-05-13 00:01:37',NULL),(236,32,124,15,'2026-05-13 00:01:37',NULL);
/*!40000 ALTER TABLE `calificaciones_criterio` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargas_academicas`
--

LOCK TABLES `cargas_academicas` WRITE;
/*!40000 ALTER TABLE `cargas_academicas` DISABLE KEYS */;
INSERT INTO `cargas_academicas` VALUES (1,7,13,1,15,NULL,2,'activa','2026-05-11 23:15:50'),(2,12,13,1,22,NULL,2,'activa','2026-05-11 23:20:44'),(3,9,13,1,16,NULL,2,'activa','2026-05-12 02:55:01'),(4,16,13,1,12,NULL,1,'activa','2026-05-12 02:59:27'),(5,19,13,1,NULL,11,2,'activa','2026-05-12 03:02:00'),(6,11,13,1,NULL,12,1,'activa','2026-05-12 03:02:30'),(7,15,13,1,14,NULL,2,'activa','2026-05-12 03:04:28'),(8,2,13,1,18,NULL,2,'activa','2026-05-12 03:04:59'),(9,8,13,1,20,NULL,2,'activa','2026-05-12 03:05:48'),(10,6,13,1,NULL,13,2,'activa','2026-05-12 03:07:17'),(11,17,13,1,23,NULL,2,'activa','2026-05-12 03:26:16'),(12,18,13,1,NULL,16,2,'activa','2026-05-12 03:27:32'),(13,7,19,1,15,NULL,2,'activa','2026-05-12 04:20:42'),(14,6,19,1,NULL,13,2,'activa','2026-05-12 04:21:25'),(15,8,19,1,20,NULL,2,'activa','2026-05-12 04:21:58'),(16,9,19,1,16,NULL,2,'activa','2026-05-12 04:23:52'),(17,10,19,1,NULL,10,2,'activa','2026-05-12 04:24:21'),(18,11,19,1,NULL,12,1,'activa','2026-05-12 04:26:23'),(19,12,19,1,22,NULL,2,'activa','2026-05-12 04:26:49'),(20,13,19,1,19,NULL,2,'activa','2026-05-12 04:28:33'),(21,14,19,1,11,NULL,2,'activa','2026-05-12 04:30:03'),(22,15,19,1,14,NULL,2,'activa','2026-05-12 04:33:08'),(23,16,19,1,12,NULL,2,'activa','2026-05-12 04:37:12'),(24,17,19,1,23,NULL,2,'activa','2026-05-12 04:38:32'),(25,18,19,1,NULL,16,2,'activa','2026-05-12 04:39:33'),(26,17,19,1,21,NULL,2,'activa','2026-05-12 04:40:52'),(27,19,19,1,NULL,11,2,'activa','2026-05-12 04:41:36'),(28,20,19,1,NULL,15,1,'activa','2026-05-12 04:44:07'),(29,2,13,1,NULL,21,0,'activa','2026-05-13 04:25:31'),(30,5,14,1,NULL,21,0,'activa','2026-05-13 04:25:39'),(31,14,15,1,NULL,21,0,'activa','2026-05-13 04:25:47'),(32,7,16,1,NULL,21,0,'activa','2026-05-13 04:26:20'),(33,17,17,1,NULL,21,0,'activa','2026-05-13 04:28:16'),(34,13,18,1,NULL,21,0,'activa','2026-05-13 04:28:26'),(35,15,19,1,NULL,21,0,'activa','2026-05-13 04:28:38'),(36,16,20,1,NULL,21,0,'activa','2026-05-13 04:28:48'),(37,18,21,1,NULL,21,0,'activa','2026-05-13 04:28:58'),(38,6,22,1,NULL,21,0,'activa','2026-05-13 04:29:10'),(39,9,23,1,NULL,21,0,'activa','2026-05-13 04:29:18'),(40,20,7,1,NULL,9,0,'activa','2026-05-13 04:29:46');
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
INSERT INTO `competencias` VALUES 
(1,'C1','Se comunica en inglés como lengua extranjera.','Se comunica en inglés',NULL,4,1),
(2,'C2','Lee diversos tipos de textos escritos en inglés como lengua extranjera','Lee y comprende en inglés',NULL,4,2),
(3,'C3','Escribe diversos tipos de textos en inglés como lengua extranjera','Redacción en inglés',NULL,4,3),
(4,'C4','Construye su identidad.','Construye su identidad',NULL,1,4),
(5,'C5','Convive y participa democráticamente en la búsqueda del bien común.','Convive y participa por el bien común',NULL,1,5),
(6,'C6','Construye interpretaciones históricas.','Construye interpretaciones históricas',NULL,1,6),
(7,'C7','Gestiona responsablemente el espacio y el ambiente.','Gestiona el espacio y el ambiente',NULL,1,7),
(8,'C8','Gestiona responsablemente los recursos económicos.','Gestiona los recursos económicos',NULL,1,8),
(9,'C9','Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.','Identidad como persona amada por Dios',NULL,5,9),
(10,'C10','Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa','Asume la experiencia de Dios en su proyecto de vida',NULL,5,10),
(11,'C13','Se desenvuelve de manera autónoma a través de su motricidad.','Motricidad autónoma',NULL,2,11),
(12,'C14','Asume una vida saludable.','Vida saludable',NULL,2,12),
(13,'C15','Interactúa a través de sus habilidades sociomotrices.','Habilidades sociomotrices',NULL,2,13),
(14,'C16','Se comunica oralmente en su lengua materna.','Se comunica oralmente en sulengua materna.',NULL,6,14),
(15,'C17','Lee diversos tipos de textos escritos en su lengua materna.','Lee diversos tipos de textos escritos en su lengua materna',NULL,6,15),
(16,'C18','Escribe diversos tipos de textos en su lengua materna.','Escribe diversos tipos de textos en su lengua materna',NULL,6,16),
(17,'C19','Aprecia de manera crítica manifestaciones artístico-culturales.','Aprecia manifestaciones artísticas',NULL,3,17),
(18,'C20','Crea proyectos desde los lenguajes artísticos.','Crea proyectos artísticos',NULL,3,18),
(19,'C21','Resuelve problemas de cantidad.','Resuelve problemas de cantidad',4,NULL,19),
(20,'C22','Resuelve problemas de regularidad, equivalencia y cambio.','Resuelve problemas de regularidad, equivalencia y cambio',5,NULL,20),
(21,'C23','Resuelve problemas de forma, movimiento y localización.','Resuelve problemas de forma, movimiento y localización',6,NULL,21),
(22,'C24','Resuelve problemas de gestión de datos e incertidumbre.','Gestión de datos',7,NULL,22),
(23,'C25','Indaga mediante métodos científicos para construir sus conocimientos.','Indaga mediante el método científico',8,NULL,23),
(24,'C26','Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.','Explica el mundo físico basándose en los seres vivos',9,NULL,24),
(25,'C27','Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.','Diseña y construye soluciones tecnológicas',10,NULL,25),
(26,'CT1','Se desenvuelve en entornos virtuales generados por las TIC.','Entornos virtuales / TIC',NULL,9,26),
(27,'CT2','Gestiona su aprendizaje de manera autónoma.','Aprendizaje autónomo',NULL,9,27),
(28,'C28','Construye su identidad.','Construye su identidad',NULL,10,1),
(29,'C29','Convive y participa democráticamente en la búsqueda del bien común.','Convive democráticamente en la búsqueda del bien común',NULL,10,2),
(30,'C30','Construye interpretaciones históricas.','Construye interpretaciones históricas',11,NULL,3),
(31,'C31','Gestiona responsablemente el espacio y el ambiente.','Gestiona responsablemente el espacio y el ambiente',12,NULL,4),
(32,'C32','Gestiona responsablemente los recursos económicos.','Gestiona responsablemente los recursos económicos',13,NULL,5),
(33,'C33','Asume una vida saludable.','Asume una vida saludable.',NULL,11,6),
(34,'C34','Interactúa a través de sus habilidades sociomotrices.','Interactúa a través de sus habilidades sociomotrices.',NULL,11,7),
(35,'C35','Asume una vida saludable.','Asume una vida saludable.',NULL,11,8),
(36,'C36','Aprecia de manera crítica manifestaciones artístico-culturales.','Aprecia de manera crítica manifestaciones artístico-culturales',NULL,12,9),
(37,'C37','Crea proyectos desde los lenguajes artísticos.','Crea proyectos desde los lenguajes artísticos',NULL,12,10),
(38,'C38','Se comunica oralmente en su lengua materna.','Se comunica oralmente',14,NULL,11),
(39,'C39','Lee diversos tipos de textos escritos en su lengua materna.','Lee diversos tipos de textos',15,NULL,12),
(40,'C40','Escribe diversos tipos de textos en su lengua materna.','Escribe diversos tipos de textos',16,NULL,13),
(41,'C41','Se comunica oralmente.','Se comunica oralmente',NULL,13,14),
(42,'C42','Lee diversos tipos de textos escritos.','Lee diversos tipos de textos',NULL,13,15),
(43,'C43','Escribe diversos tipos de texto.','Escribe diversos tipos de textos',NULL,13,16),
(44,'C44','Resuelve problemas de cantidad.','Resuelve problemas de cantidad',17,NULL,17),
(45,'C45','Resuelve problemas de regularidad, equivalencia y cambio.','Resuelve problemas de regularidad, equivalencia y cambio',18,NULL,18),
(46,'C46','Resuelve problemas de forma, movimiento y localización.','Resuelve problemas de forma, movimiento y localización',19,NULL,19),
(47,'C47','Resuelve problemas de gestión de datos e incertidumbre.','Resuelve problemas de gestión de datos e incertidumbre',20,NULL,20),
(48,'C48','Indaga mediante métodos científicos para construir sus conocimientos.','Indaga mediante métodos científicos',21,NULL,21),
(49,'C49','Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.','Explica el mundo físico basándose en conocimientos sobre la Tierra y el Universo.',22,NULL,22),
(50,'C50','Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.','Diseña y construye soluciones tecnológicas',23,NULL,23),
(51,'C51','Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.','Construye su identidad como persona amada por Dios',NULL,14,24),
(52,'C52','Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.','Asume la experiencia del encuentro con Dios en su vida',NULL,14,25),
(53,'C53','Gestiona proyectos de emprendimiento económico o social.','Gestiona proyectos de emprendimiento',NULL,15,26),
-- COMPETENCIAS DEL TALLER DE RAZONAMIENTO MATEMÁTICO - SOLO PARA EL PRIMERO, SEGUNDO Y TERCERO DE NIVEL SECUNDARIA.
(54,'C54','Resuelve problemas de cantidad.','Resuelve problemas de cantidad',NULL,16,27),
(55,'C55','Resuelve problemas de gestión de datos e incertidumbre.','Resuelve problemas de gestión de datos e incertidumbre',NULL,16,28),
(56,'CT3','Se desenvuelve en entornos virtuales generados por las TIC.','Entornos virtuales / TIC',NULL,21,29),
(57,'CT4','Gestiona su aprendizaje de manera autónoma.','Aprendizaje autónomo',NULL,21,30);
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
INSERT INTO `configuracion_horario` VALUES (1,1,45,'13:10:00',NULL,'2026-05-11 23:17:04');
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `criterios`
--

LOCK TABLES `criterios` WRITE;
/*!40000 ALTER TABLE `criterios` DISABLE KEYS */;
INSERT INTO `criterios` VALUES (15,10,41,1,'Examen de entrada',1,'2026-05-13 02:54:50','2026-05-13 02:54:50'),(16,10,41,1,'Examen bimestral',2,'2026-05-13 02:55:11','2026-05-13 02:55:11'),(17,10,42,1,'Examen de entrada',1,'2026-05-13 02:55:22','2026-05-13 02:55:22'),(18,10,42,1,'Examen oral',2,'2026-05-13 02:55:33','2026-05-13 02:55:33'),(19,10,43,1,'Examen de entrada',1,'2026-05-13 02:55:49','2026-05-13 02:55:49'),(20,10,43,1,'Examen bimestral',2,'2026-05-13 02:56:05','2026-05-13 02:56:05'),(21,14,41,1,'Examen de entrada',1,'2026-05-13 02:59:52','2026-05-13 02:59:52'),(22,14,41,1,'Examen mensual',2,'2026-05-13 03:00:50','2026-05-13 03:00:50'),(23,14,41,1,'Examen bimestral',3,'2026-05-13 03:02:50','2026-05-13 03:02:50'),(24,14,42,1,'Examen de entrada',1,'2026-05-13 03:03:45','2026-05-13 03:03:45'),(25,14,42,1,'Examen oral',2,'2026-05-13 03:03:56','2026-05-13 03:03:56'),(26,14,42,1,'Examen mensual',3,'2026-05-13 03:04:12','2026-05-13 03:04:12'),(27,14,42,1,'Examen bimestral',4,'2026-05-13 03:04:33','2026-05-13 03:04:33'),(28,14,43,1,'Examen mensual',1,'2026-05-13 03:06:33','2026-05-13 03:06:33'),(29,14,43,1,'Examen bimestral',2,'2026-05-13 03:08:45','2026-05-13 03:08:45'),(31,38,54,1,'I Bimestre',1,'2026-05-13 04:30:53','2026-05-13 04:30:53'),(32,38,55,1,'I Bimestre',1,'2026-05-13 04:30:53','2026-05-13 04:30:53');
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
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (1,5,NULL,'2026-05-11 23:15:50'),(2,6,NULL,'2026-05-11 23:15:50'),(3,4,NULL,'2026-05-11 23:15:50'),(4,7,NULL,'2026-05-11 23:15:50'),(5,3,NULL,'2026-05-11 23:15:50'),(8,9,NULL,'2026-05-11 23:16:08'),(9,10,NULL,'2026-05-11 23:16:08'),(10,11,NULL,'2026-05-11 23:16:08'),(11,12,NULL,'2026-05-11 23:16:08'),(12,13,NULL,'2026-05-11 23:16:08'),(13,14,NULL,'2026-05-11 23:16:08'),(14,15,NULL,'2026-05-11 23:16:08'),(15,16,NULL,'2026-05-11 23:16:08'),(16,17,NULL,'2026-05-11 23:16:08'),(17,18,NULL,'2026-05-11 23:16:08'),(18,19,NULL,'2026-05-11 23:16:08'),(19,20,NULL,'2026-05-11 23:16:08'),(20,21,NULL,'2026-05-11 23:16:08'),(21,22,NULL,'2026-05-11 23:16:08'),(22,23,NULL,'2026-05-11 23:16:08'),(23,24,NULL,'2026-05-11 23:16:08'),(24,25,NULL,'2026-05-11 23:16:08'),(25,26,NULL,'2026-05-11 23:16:08'),(26,27,NULL,'2026-05-11 23:16:08'),(27,28,NULL,'2026-05-11 23:16:08'),(28,29,NULL,'2026-05-11 23:16:08'),(29,30,NULL,'2026-05-11 23:16:08'),(30,31,NULL,'2026-05-11 23:16:08'),(31,32,NULL,'2026-05-11 23:16:08'),(32,33,NULL,'2026-05-11 23:16:08'),(33,34,NULL,'2026-05-11 23:16:08'),(34,35,NULL,'2026-05-11 23:16:08'),(35,36,NULL,'2026-05-11 23:16:08'),(36,37,NULL,'2026-05-11 23:16:08'),(37,38,NULL,'2026-05-11 23:16:08'),(38,39,NULL,'2026-05-11 23:16:08'),(39,40,NULL,'2026-05-11 23:16:08'),(40,41,NULL,'2026-05-11 23:16:08'),(41,42,NULL,'2026-05-11 23:16:08'),(42,43,NULL,'2026-05-11 23:16:08'),(43,44,NULL,'2026-05-11 23:16:08'),(44,45,NULL,'2026-05-11 23:16:08'),(45,46,NULL,'2026-05-11 23:16:08'),(46,47,NULL,'2026-05-11 23:16:08'),(47,48,NULL,'2026-05-11 23:16:08'),(48,49,NULL,'2026-05-11 23:16:08'),(49,50,NULL,'2026-05-11 23:16:08'),(50,51,NULL,'2026-05-11 23:16:08'),(51,52,NULL,'2026-05-11 23:16:08'),(52,53,NULL,'2026-05-11 23:16:08'),(53,54,NULL,'2026-05-11 23:16:08'),(54,55,NULL,'2026-05-11 23:16:08'),(55,56,NULL,'2026-05-11 23:16:08'),(56,57,NULL,'2026-05-11 23:16:08'),(57,58,NULL,'2026-05-11 23:16:08'),(58,59,NULL,'2026-05-11 23:16:08'),(59,60,NULL,'2026-05-11 23:16:08'),(60,61,NULL,'2026-05-11 23:16:08'),(61,62,NULL,'2026-05-11 23:16:08'),(62,63,NULL,'2026-05-11 23:16:08'),(63,64,NULL,'2026-05-11 23:16:08'),(64,65,NULL,'2026-05-11 23:16:08'),(65,66,NULL,'2026-05-11 23:16:08'),(66,67,NULL,'2026-05-11 23:16:08'),(67,68,NULL,'2026-05-11 23:16:08'),(68,69,NULL,'2026-05-11 23:16:08'),(69,70,NULL,'2026-05-11 23:16:08'),(70,71,NULL,'2026-05-11 23:16:08'),(71,72,NULL,'2026-05-11 23:16:08'),(72,73,NULL,'2026-05-11 23:16:08'),(73,74,NULL,'2026-05-11 23:16:08'),(74,75,NULL,'2026-05-11 23:16:08'),(75,76,NULL,'2026-05-11 23:16:08'),(76,77,NULL,'2026-05-11 23:16:08'),(77,78,NULL,'2026-05-11 23:16:08'),(78,79,NULL,'2026-05-11 23:16:08'),(79,80,NULL,'2026-05-11 23:16:08'),(80,81,NULL,'2026-05-11 23:16:08'),(81,82,NULL,'2026-05-11 23:16:08'),(82,83,NULL,'2026-05-11 23:16:08'),(83,84,NULL,'2026-05-11 23:16:08'),(84,85,NULL,'2026-05-11 23:16:08'),(85,86,NULL,'2026-05-11 23:16:08'),(86,87,NULL,'2026-05-11 23:16:08'),(87,88,NULL,'2026-05-11 23:16:08'),(88,89,NULL,'2026-05-11 23:16:08'),(89,90,NULL,'2026-05-11 23:16:08'),(90,91,NULL,'2026-05-11 23:16:08'),(91,92,NULL,'2026-05-11 23:16:08'),(92,93,NULL,'2026-05-11 23:16:08'),(93,94,NULL,'2026-05-11 23:16:08'),(94,95,NULL,'2026-05-11 23:16:08'),(95,96,NULL,'2026-05-11 23:16:08'),(96,97,NULL,'2026-05-11 23:16:08'),(97,98,NULL,'2026-05-11 23:16:08'),(98,99,NULL,'2026-05-11 23:16:08'),(99,100,NULL,'2026-05-11 23:16:08'),(100,101,NULL,'2026-05-11 23:16:08'),(101,102,NULL,'2026-05-11 23:16:08'),(102,103,NULL,'2026-05-11 23:16:08'),(103,104,NULL,'2026-05-11 23:16:08'),(104,105,NULL,'2026-05-11 23:16:08'),(105,106,NULL,'2026-05-11 23:16:08'),(106,107,NULL,'2026-05-11 23:16:08'),(107,108,NULL,'2026-05-11 23:16:08'),(108,109,NULL,'2026-05-11 23:16:08'),(109,110,NULL,'2026-05-11 23:16:08'),(110,111,NULL,'2026-05-11 23:16:08'),(111,112,NULL,'2026-05-11 23:16:08'),(112,113,NULL,'2026-05-11 23:16:08'),(113,114,NULL,'2026-05-11 23:16:08'),(114,115,NULL,'2026-05-11 23:16:08'),(115,116,NULL,'2026-05-11 23:16:08'),(116,117,NULL,'2026-05-11 23:16:08'),(117,118,NULL,'2026-05-11 23:16:08');
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

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
INSERT INTO `grados` VALUES (1,1,1,'1°'),(2,1,2,'2°'),(3,1,3,'3°'),(4,1,4,'4°'),(5,1,5,'5°'),(6,1,6,'6°'),(7,2,1,'1°'),(8,2,2,'2°'),(9,2,3,'3°'),(10,2,4,'4°'),(11,2,5,'5°');
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
-- Dumping data for table `matriculas`
--

LOCK TABLES `matriculas` WRITE;
/*!40000 ALTER TABLE `matriculas` DISABLE KEYS */;
INSERT INTO `matriculas` VALUES (1,5,1,1,'regular','aprobada',NULL,'2026-05-11',NULL,NULL,1,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(2,3,1,1,'regular','aprobada',NULL,'2026-05-11',NULL,NULL,1,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(3,1,1,1,'regular','aprobada',NULL,'2026-05-11',NULL,NULL,1,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(4,2,1,1,'regular','aprobada',NULL,'2026-05-11',NULL,NULL,1,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(5,4,1,1,'regular','aprobada',NULL,'2026-05-11',NULL,NULL,1,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(8,8,3,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(9,9,3,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(10,10,3,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(11,11,3,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(12,12,3,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(15,13,2,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(16,14,2,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(17,15,2,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(18,16,2,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(19,17,2,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(22,18,5,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(23,19,5,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(24,20,5,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(25,21,5,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(26,22,5,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(29,23,4,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(30,24,4,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(31,25,4,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(32,26,4,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(33,27,4,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(36,28,7,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(37,29,7,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(38,30,7,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(39,31,7,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(40,32,7,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(43,33,6,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(44,34,6,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(45,35,6,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(46,36,6,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(47,37,6,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(50,38,9,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(51,39,9,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(52,40,9,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(53,41,9,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(54,42,9,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(57,43,8,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(58,44,8,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(59,45,8,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(60,46,8,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(61,47,8,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(64,48,11,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(65,49,11,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(66,50,11,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(67,51,11,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(68,52,11,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(71,53,10,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(72,54,10,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(73,55,10,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(74,56,10,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(75,57,10,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(78,58,13,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(79,59,13,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(80,60,13,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(81,61,13,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(82,62,13,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(85,63,12,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(86,64,12,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(87,65,12,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(88,66,12,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(89,67,12,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(92,68,18,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(93,69,18,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(94,70,18,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(95,71,18,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(96,72,18,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(99,73,17,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(100,74,17,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(101,75,17,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(102,76,17,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(103,77,17,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(106,78,20,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(107,79,20,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(108,80,20,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(109,81,20,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(110,82,20,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(113,83,19,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(114,84,19,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(115,85,19,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(116,86,19,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(117,87,19,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(120,88,22,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(121,89,22,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(122,90,22,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(123,91,22,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(124,92,22,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(127,93,21,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(128,94,21,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(129,95,21,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(130,96,21,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(131,97,21,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(134,98,24,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(135,99,24,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(136,100,24,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(137,101,24,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(138,102,24,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(141,103,23,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(142,104,23,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(143,105,23,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(144,106,23,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(145,107,23,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(148,108,26,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(149,109,26,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(150,110,26,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(151,111,26,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(152,112,26,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(155,113,25,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(156,114,25,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(157,115,25,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(158,116,25,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09'),(159,117,25,1,'regular','aprobada',NULL,'2026-03-01',NULL,'2026-03-08',1,NULL,NULL,'2026-05-11 23:16:09','2026-05-11 23:16:09');
/*!40000 ALTER TABLE `matriculas` ENABLE KEYS */;
UNLOCK TABLES;

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
INSERT INTO `niveles` VALUES (1,'Primaria','prim','solo_literal','2026-05-11 23:13:16'),(2,'Secundaria','sec','ambas','2026-05-11 23:13:16');
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
INSERT INTO `periodos` VALUES (1,1,1,'bimestre','I Bimestre','2026-03-09','2026-05-15','2026-05-20 23:59:00','activo'),(2,1,2,'bimestre','II Bimestre','2026-05-19','2026-07-17',NULL,'pendiente'),(3,1,3,'bimestre','III Bimestre','2026-08-03','2026-10-02',NULL,'pendiente'),(4,1,4,'bimestre','IV Bimestre','2026-10-05','2026-12-04',NULL,'pendiente');
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
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personas`
--

LOCK TABLES `personas` WRITE;
/*!40000 ALTER TABLE `personas` DISABLE KEYS */;
INSERT INTO `personas` VALUES (1,'00000000','Sistema','COCIAP','Administrador',NULL,NULL,NULL,'admin@cociap.edu.pe',NULL,'2026-05-11 23:13:16','2026-05-11 23:13:16'),(2,'12345678','Guillermo','Chavez','Luis Waldir',NULL,'M',NULL,'waldirguillermoc@gmail.com',NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(3,'77752898','Angeles','Fernandez','Xiara Daleshka',NULL,'F',NULL,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(4,'61314557','Aguilar','Rosario','Vanessa Yanneth',NULL,'F',NULL,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(5,'45678901','Ramirez','Torres','Carlos Alberto',NULL,'M',NULL,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(6,'56789012','Mendoza','Quispe','Lucia Valentina',NULL,'F',NULL,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(7,'67890123','Huanca','Vidal','Diego Alejandro',NULL,'M',NULL,NULL,NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(8,'99999999','MEZA','TORRES','MARIA ELENA',NULL,NULL,'943123456','elenamezato1975@gmail.com',NULL,'2026-05-11 23:15:50','2026-05-11 23:15:50'),(9,'10000001','QUISPE','FLORES','JUAN CARLOS','2019-04-10','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(10,'10000002','MAMANI','GARCIA','ANA LUCIA','2019-06-22','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(11,'10000003','ROJAS','TORRES','MIGUEL ANGEL','2019-02-14','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(12,'10000004','FLORES','RAMIREZ','SOFIA CAMILA','2019-08-30','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(13,'10000005','GARCIA','MENDOZA','PEDRO PABLO','2019-05-18','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(14,'10000006','HUANCA','CHAVEZ','VALERIA NICOL','2019-01-25','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(15,'10000007','TORRES','VARGAS','ANDRES MARTIN','2018-11-08','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(16,'10000008','RAMOS','QUISPE','DIANA PAOLA','2019-03-17','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(17,'10000009','CONDORI','APAZA','FRANCO ALEXIS','2018-12-05','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(18,'10000010','MENDOZA','HUAMAN','LUCIA VALENTINA','2019-07-14','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(19,'10000011','CASTILLO','MORALES','BRYAN SEBASTIAN','2018-02-28','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(20,'10000012','RAMIREZ','SANCHEZ','ROCIO PAMELA','2018-05-11','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(21,'10000013','VARGAS','PEREZ','DAVID ALEJANDRO','2018-09-20','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(22,'10000014','GONZALES','LLANOS','KAREN ELIZABETH','2018-01-07','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(23,'10000015','CHAVEZ','DIAZ','CARLOS ENRIQUE','2018-07-03','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(24,'10000016','TAPIA','ROJAS','ESTEFANY MISHEL','2017-11-14','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(25,'10000017','PIZARRO','CASTILLO','AARON MATIAS','2018-04-22','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(26,'10000018','MORALES','FLORES','YESENIA PAOLA','2017-12-30','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(27,'10000019','SALAZAR','TORRES','FABIAN RODRIGO','2018-08-16','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(28,'10000020','HUAMAN','QUISPE','CINTHIA MILAGROS','2018-03-09','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(29,'10000021','DIAZ','RAMIREZ','ERICK PAUL','2017-06-18','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(30,'10000022','PEREZ','MENDOZA','ANALI DEL PILAR','2017-02-24','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(31,'10000023','LLANOS','VARGAS','OMAR GABRIEL','2017-09-05','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(32,'10000024','CCORI','GONZALES','BRENDA LORENA','2017-04-12','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(33,'10000025','APAZA','HUANCA','WILDER JESUS','2017-11-28','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(34,'10000026','MEZA','CONDORI','LEIDY JOHANA','2016-12-07','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(35,'10000027','ESPINOZA','APAZA','KEVIN DANIEL','2017-03-15','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(36,'10000028','GUTIERREZ','CCORI','ROSA MARIA','2017-07-21','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(37,'10000029','RIVERA','PIZARRO','JHON ALEX','2017-01-08','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(38,'10000030','SANCHEZ','MEZA','DANI LUCERO','2017-05-30','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(39,'10000031','FLORES','ESPINOZA','SERGIO ANTONIO','2016-03-22','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(40,'10000032','TORRES','GUTIERREZ','MILUSKA ALEJANDRA','2016-06-14','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(41,'10000033','QUISPE','RIVERA','JUNIOR JHAIR','2016-09-08','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(42,'10000034','GARCIA','SANCHEZ','FERNANDA ANAIS','2016-01-27','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(43,'10000035','MAMANI','DIAZ','NELSON FELIX','2016-11-03','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(44,'10000036','ROJAS','MAMANI','ASHLEY NICHOLE','2015-12-19','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(45,'10000037','RAMIREZ','GARCIA','JOSE LUIS','2016-04-07','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(46,'10000038','VARGAS','ROJAS','KIARA DANIELA','2016-08-25','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(47,'10000039','MENDOZA','FLORES','LUIS ALBERTO','2016-02-11','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(48,'10000040','CHAVEZ','TORRES','MARIA JOSE','2016-10-16','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(49,'10000041','CASTILLO','CHAVEZ','RAUL EMILIO','2015-05-20','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(50,'10000042','GONZALES','VARGAS','PAOLA STEFANY','2015-08-03','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(51,'10000043','HUANCA','MENDOZA','RODRIGO ANDRE','2015-01-17','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(52,'10000044','MORALES','CASTILLO','NADIA ROSARIO','2015-11-29','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(53,'10000045','TAPIA','GONZALES','ALEXIS RENATO','2015-03-08','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(54,'10000046','PIZARRO','HUANCA','ISABEL CRISTINA','2014-12-14','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(55,'10000047','CCORI','MORALES','CESAR AUGUSTO','2015-07-26','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(56,'10000048','LLANOS','TAPIA','GABRIELA YASMIN','2015-04-09','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(57,'10000049','APAZA','PIZARRO','CRISTIAN JAVIER','2014-10-31','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(58,'10000050','MEZA','CCORI','MARIA FERNANDA','2015-09-22','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(59,'10000051','ESPINOZA','LLANOS','VICTOR MANUEL','2014-02-18','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(60,'10000052','GUTIERREZ','APAZA','XIOMARA ELENA','2014-06-07','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(61,'10000053','RIVERA','MEZA','JOSE MANUEL','2014-11-25','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(62,'10000054','SANCHEZ','ESPINOZA','NATALIA BELEN','2014-04-14','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(63,'10000055','FLORES','GUTIERREZ','MARIO ANTONIO','2014-08-30','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(64,'10000056','TORRES','RIVERA','ADRIANA NICOLE','2013-12-05','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(65,'10000057','QUISPE','SANCHEZ','HENRY OMAR','2014-03-19','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(66,'10000058','MAMANI','FLORES','CARMEN ROSA','2014-07-08','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(67,'10000059','GARCIA','TORRES','EDWIN RAUL','2013-09-24','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(68,'10000060','ROJAS','QUISPE','ELIZABETH PILAR','2014-01-12','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(69,'10000061','RAMIREZ','MAMANI','SEBASTIAN ALAN','2013-05-07','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(70,'10000062','VARGAS','GARCIA','STEPHANIE MARIE','2013-08-21','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(71,'10000063','MENDOZA','ROJAS','GABRIEL ALEJANDRO','2013-01-14','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(72,'10000064','CHAVEZ','RAMIREZ','FIORELLA LUCIA','2013-10-29','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(73,'10000065','CASTILLO','VARGAS','ANDERSON PAUL','2013-04-16','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(74,'10000066','GONZALES','MENDOZA','DANNA VALERIA','2012-11-03','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(75,'10000067','HUANCA','CASTILLO','PIERO ALEJANDRO','2013-03-18','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(76,'10000068','MORALES','GONZALES','NAOMI ESTHER','2013-07-02','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(77,'10000069','TAPIA','HUANCA','RUDOLFO CESAR','2012-09-27','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(78,'10000070','PIZARRO','MORALES','ALISON DIANA','2013-02-11','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(79,'10000071','CCORI','TAPIA','ANTHONY JAMES','2012-06-15','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(80,'10000072','LLANOS','PIZARRO','XIOMARA VALERIA','2012-09-28','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(81,'10000073','APAZA','CCORI','LUIS RODRIGO','2012-02-07','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(82,'10000074','MEZA','LLANOS','CINDY LORENA','2012-12-21','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(83,'10000075','ESPINOZA','APAZA','JORGE LUIS','2012-05-04','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(84,'10000076','GUTIERREZ','MEZA','JESSICA PAMELA','2011-11-18','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(85,'10000077','RIVERA','ESPINOZA','RONALDO DAVID','2012-04-03','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(86,'10000078','SANCHEZ','GUTIERREZ','DIANA CAROLINA','2012-07-16','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(87,'10000079','FLORES','RIVERA','IVAN AUGUSTO','2011-10-09','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(88,'10000080','TORRES','SANCHEZ','MELISSA ANAHI','2012-01-25','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(89,'10000081','QUISPE','TORRES','RENATO FABIAN','2011-05-12','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(90,'10000082','MAMANI','QUISPE','YOLANDA CRISTINA','2011-08-27','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(91,'10000083','GARCIA','MAMANI','JHONATAN ALEXIS','2011-02-18','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(92,'10000084','ROJAS','GARCIA','MAYRA STEFANI','2011-11-04','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(93,'10000085','RAMIREZ','ROJAS','ANGEL GABRIEL','2011-06-30','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(94,'10000086','VARGAS','RAMIREZ','DENISSE PAMELA','2010-12-15','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(95,'10000087','MENDOZA','VARGAS','RODRIGO ALONSO','2011-04-08','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(96,'10000088','CHAVEZ','MENDOZA','VERONICA MILAGROS','2011-07-23','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(97,'10000089','CASTILLO','CHAVEZ','JOEL CRISTIAN','2010-09-17','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(98,'10000090','GONZALES','CASTILLO','CARLA MELISA','2011-01-05','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(99,'10000091','HUANCA','GONZALES','MARCO ANTONIO','2010-06-19','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(100,'10000092','MORALES','HUANCA','PRISCILA RUTH','2010-09-02','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(101,'10000093','TAPIA','MORALES','JESUS ALEJANDRO','2010-03-14','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(102,'10000094','PIZARRO','TAPIA','PAMELA ESTHER','2010-12-28','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(103,'10000095','CCORI','PIZARRO','RICHARD SMITH','2010-05-07','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(104,'10000096','LLANOS','CCORI','GLORIA ESTEFANI','2009-11-22','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(105,'10000097','APAZA','LLANOS','CHRISTIAN ALBERTO','2010-04-16','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(106,'10000098','MEZA','APAZA','PATRICIA LORENA','2010-08-09','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(107,'10000099','ESPINOZA','MEZA','ALEX RODRIGO','2009-10-25','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(108,'10000100','GUTIERREZ','ESPINOZA','ROSA ELENA','2010-01-13','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(109,'10000101','RIVERA','GUTIERREZ','FRANK ALDAIR','2009-07-04','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(110,'10000102','SANCHEZ','RIVERA','MARIELA ROSA','2009-11-18','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(111,'10000103','FLORES','SANCHEZ','PEDRO JOSE','2009-04-26','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(112,'10000104','TORRES','FLORES','WENDY CAROLINA','2009-08-12','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(113,'10000105','QUISPE','TORRES','JOSUE ELIAS','2009-02-20','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(114,'10000106','MAMANI','QUISPE','KERLY DIANA','2008-12-09','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(115,'10000107','GARCIA','MAMANI','JULIO CESAR','2009-05-27','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(116,'10000108','ROJAS','GARCIA','NORMA YANETH','2009-09-13','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(117,'10000109','RAMIREZ','ROJAS','FRANK OSWALDO','2008-11-08','M',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(118,'10000110','VARGAS','RAMIREZ','CONNIE MISHEL','2009-03-01','F',NULL,NULL,NULL,'2026-05-11 23:16:08','2026-05-11 23:16:08'),(229,'20000001','BUENO','DE LA O','KAREN VIOLETA',NULL,'F','935321323','KBUENO@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:52:42','2026-05-12 03:52:42'),(230,'20000002','HUAYANEY','GRANADOS','KATTY JANETH',NULL,'F','935461123','KHUAYANEYG@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:53:26','2026-05-12 03:53:26'),(231,'20000003','SOTELO','ROQUE','SAARA',NULL,'F','934121111','SSOTELOR@COCIAVVG.EDU.PE',NULL,'2026-05-12 03:54:31','2026-05-12 03:54:31'),(232,'20000004','MENACHO','QUESADA','KETTY',NULL,'F','934000123','KMENACHOQ@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:55:56','2026-05-12 03:55:56'),(233,'20000005','CASTILLEJO','MORALES','NACHO EDUAR',NULL,'M','914034311','NCASTILLEJOM@COCIAPVVG.EDU.PE',NULL,'2026-05-12 03:57:43','2026-05-12 03:57:43'),(234,'20000006','NUÑUVERO','RAMIREZ','LESLIE',NULL,'F','940010099','LNUNUVEROR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:01:01','2026-05-12 04:01:01'),(235,'20000007','CLEMENTE','ANGELES','MARSHALL ALEKHENE',NULL,'M','905123433','MCLEMENTEA@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:03:24','2026-05-12 04:03:24'),(236,'20000008','MONTES','DEPAZ','HILBER CARLOS',NULL,'M','932414433','HMONTESD@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:04:20','2026-05-12 04:04:20'),(237,'20000009','OLIVERA','RAMIREZ','SILVIA MILAGROS',NULL,'F','941001433','SOLIVERAR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:05:09','2026-05-12 04:05:09'),(238,'20000010','ZAMBRANO','GUILLERMO','EDINZON ALEX',NULL,'M','941339090','EZAMBRANOG@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:06:05','2026-05-12 04:06:05'),(239,'20000011','PUMA','TINTA','MISHEL',NULL,'F','901441211','MPUMAT@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:07:28','2026-05-12 04:07:28'),(240,'20000012','CARRILLO','MEJIA','NORELI MILAGROS',NULL,'F','932121212','NCARRILLOM@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:08:28','2026-05-12 04:08:28'),(241,'20000013','MOSQUERA','DEPAZ','LEANDRO HERCILIO',NULL,'M','912131313','LMOSQUERAD@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:09:49','2026-05-12 04:09:49'),(242,'20000014','ANAYA','MORALES','VALOIS',NULL,'M','900123123','VANAYAM@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:11:38','2026-05-12 04:11:38'),(243,'20000015','BELLO','REYES','FREDY JESUS',NULL,'M','912443211','FBELLOR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:12:22','2026-05-12 04:12:22'),(244,'20000016','ZAVALETA','ROSALES','JORGE ARTURO DANIEL',NULL,'M','941000233','JZAVALETAR@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:14:18','2026-05-12 04:14:18'),(245,'20000017','TRUJILLO','ALVAREZ','NELLY YUBITZA',NULL,'F','914112288','TYUBITZA@COCIAPVVG.EDU.PE',NULL,'2026-05-12 04:17:57','2026-05-12 04:17:57');
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
INSERT INTO `reglas_especiales` VALUES (1,12,2,4,5,'Arte y Cultura','(Raz. Matemático)',NULL,'En 4° y 5° de secundaria las notas de Raz. Matemático se registran en el campo Arte y Cultura del SIAGIE'),(2,16,2,4,5,NULL,NULL,12,'4°-5° sec: Taller Raz. Matemático se registra como Arte y Cultura en SIAGIE (sobreescribe el nombre_siagie por defecto)');
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
INSERT INTO `roles` VALUES (1,'Administrador','admin','Acceso total al sistema','2026-05-11 23:13:16'),(2,'Registro Académico','registro_academico','Gestión de matrículas, traslados y documentos oficiales','2026-05-11 23:13:16'),(3,'Director General','director_general','Supervisión de todos los niveles','2026-05-11 23:13:16'),(4,'Director EBR','director_ebr','Supervisión de su nivel educativo','2026-05-11 23:13:16'),(5,'Secretaria','secretaria','Registro de matrículas y atención','2026-05-11 23:13:16'),(6,'Docente','docente','Registro de calificaciones de sus cargas','2026-05-11 23:13:16'),(7,'Padre de Familia','padre','Consulta del progreso de su menor hijo','2026-05-11 23:13:16');
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
INSERT INTO `secciones` VALUES (1,1,1,'A',NULL,1,'aprobada','2026-05-11 23:15:50'),(2,1,1,'B',NULL,1,'aprobada','2026-05-11 23:16:08'),(3,2,1,'A',NULL,1,'aprobada','2026-05-11 23:16:08'),(4,2,1,'B',NULL,1,'aprobada','2026-05-11 23:16:08'),(5,3,1,'A',NULL,1,'aprobada','2026-05-11 23:16:08'),(6,3,1,'B',NULL,1,'aprobada','2026-05-11 23:16:08'),(7,4,1,'A',20,0,'aprobada','2026-05-11 23:16:08'),(8,4,1,'B',NULL,0,'aprobada','2026-05-11 23:16:08'),(9,5,1,'A',NULL,0,'aprobada','2026-05-11 23:16:08'),(10,5,1,'B',NULL,0,'aprobada','2026-05-11 23:16:08'),(11,6,1,'A',NULL,0,'aprobada','2026-05-11 23:16:08'),(12,6,1,'B',NULL,0,'aprobada','2026-05-11 23:16:08'),(13,7,1,'A',2,0,'aprobada','2026-05-11 23:16:08'),(14,7,1,'B',5,0,'aprobada','2026-05-11 23:16:08'),(15,7,1,'C',14,0,'aprobada','2026-05-11 23:16:08'),(16,8,1,'A',7,0,'aprobada','2026-05-11 23:16:08'),(17,8,1,'B',17,0,'aprobada','2026-05-11 23:16:08'),(18,9,1,'A',13,0,'aprobada','2026-05-11 23:16:08'),(19,9,1,'B',15,0,'aprobada','2026-05-11 23:16:08'),(20,10,1,'A',16,0,'aprobada','2026-05-11 23:16:08'),(21,10,1,'B',18,0,'aprobada','2026-05-11 23:16:08'),(22,11,1,'A',6,0,'aprobada','2026-05-11 23:16:08'),(23,11,1,'B',9,0,'aprobada','2026-05-11 23:16:08');
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones_horario`
--

LOCK TABLES `sesiones_horario` WRITE;
/*!40000 ALTER TABLE `sesiones_horario` DISABLE KEYS */;
INSERT INTO `sesiones_horario` VALUES (19,14,18,13,6),(20,13,19,13,7),(22,16,22,13,9),(24,18,24,13,11),(26,20,26,13,13),(27,21,27,13,14),(28,22,28,13,15),(29,19,25,13,12),(30,19,29,13,12),(31,23,30,13,16),(32,24,31,13,17),(34,17,23,13,10),(35,17,33,13,10),(36,15,20,13,8),(37,15,34,13,8),(38,26,35,13,17),(39,27,36,13,19),(40,28,37,13,20),(41,25,32,13,18),(42,25,38,13,18),(43,2,19,19,12),(44,1,39,19,7),(45,4,40,19,16),(46,5,22,19,19),(47,6,25,19,11),(48,7,26,19,15),(49,8,27,19,2),(50,9,28,19,8),(51,11,41,19,17),(52,10,42,19,6),(53,12,31,19,18),(54,3,43,19,9);
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
INSERT INTO `subareas` VALUES (1,6,'Comunicación',1),(2,6,'Plan Lector',2),(3,6,'Razonamiento Verbal',3),(4,7,'Aritmética',1),(5,7,'Álgebra',2),(6,7,'Geometría',3),(7,7,'Razonamiento Matemático',4),(8,8,'Química',1),(9,8,'Biología',2),(10,8,'Física',3),(11,17,'Historia',1),(12,17,'Geografía',2),(13,17,'Economía',3),(14,18,'Razonamiento Verbal',1),(15,18,'Literatura',2),(16,18,'Lenguaje',3),(17,19,'Aritmética',1),(18,19,'Álgebra',2),(19,19,'Geometría',3),(20,19,'Trigonometría',4),(21,20,'Química',1),(22,20,'Biología',2),(23,20,'Física',3);
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,1,'$2y$10$uYEa/sZHfN6Rj5XRdY861euNiXsccWZk0SynNzcFebqNf9V1j2Pfy','2026-05-13 00:21:59',NULL,'activo','2026-05-11 23:13:16','2026-05-13 05:25:10'),(2,2,6,'$2y$10$HfNJaWDDQrGujO9FkqlAjeO2l2t46KGasEY2W3cz2a4Y2jlcl9.3O','2026-05-12 00:58:51','724e376b8e1b61cca996439fd450bd275b7841376d6236fea66626b4157cccda','activo','2026-05-11 23:15:50','2026-05-12 05:58:51'),(3,8,7,'$2y$10$NWvhpNy1mHlJXDH/ofWadeU2LDGBypIQSfbywvmnTVPJc1RU1PXeG','2026-05-13 00:02:01',NULL,'activo','2026-05-11 23:19:09','2026-05-13 05:02:14'),(4,229,6,'$2y$12$yOODf6OtZsuUAif9xeLxduku8De.FgSpIWa4u1JT1xYQd3MYvoe5e','2026-05-12 21:53:32',NULL,'activo','2026-05-12 03:52:42','2026-05-13 02:53:35'),(5,230,6,'$2y$12$Y1ySvwJQCIMS4JFLZ0edGeaFrsCLgEWcZA6ZSOrkTVg4F4dRZ2mma','2026-05-12 21:53:41',NULL,'activo','2026-05-12 03:53:26','2026-05-13 02:53:43'),(6,231,6,'$2y$12$rHPNGdvo098umnXfM9snfeNK0c4IiWx18rPM6C9sVZqVicWjg0kd6','2026-05-12 23:53:07',NULL,'activo','2026-05-12 03:54:31','2026-05-13 05:01:56'),(7,232,6,'$2y$12$A3mky7IdWRu8.anguaW76.kXx8pY6BBqMzl2LVvdz5u6MrzDcybZW',NULL,NULL,'activo','2026-05-12 03:55:56','2026-05-12 03:55:56'),(8,233,6,'$2y$12$DaCNDK45/8V8AMMo4B.rVeBitdSoYu8QeefKud63ZDOwt4nDmEqwS',NULL,NULL,'activo','2026-05-12 03:57:44','2026-05-12 03:57:44'),(9,234,6,'$2y$12$2j/Yp8JGm1QjEze0XvuU1e02GoGUKmxjAmHqIOWkhBzrx5RipkbDa',NULL,NULL,'activo','2026-05-12 04:01:01','2026-05-12 04:01:01'),(10,235,6,'$2y$12$DeJeT9DcroXJSYUOB3bGwuFGYfCM1Va.zIUvI3tZnuTkPttRiC1lC',NULL,NULL,'activo','2026-05-12 04:03:25','2026-05-12 04:03:25'),(11,236,6,'$2y$12$CFqJrcTyltnLow0KtILXb.8/SR54YJe3zSZarzMdgmTR8iDTfxtvi',NULL,NULL,'activo','2026-05-12 04:04:20','2026-05-12 04:04:20'),(12,237,6,'$2y$12$iz6.x5mDUSO7n0NERFcMF.oMPLk4N6VxMyH1vL3Iuosa4ed1CqLae',NULL,NULL,'activo','2026-05-12 04:05:09','2026-05-12 04:05:09'),(13,238,6,'$2y$12$WOs0WETmqYhFh62idSvSQeFThUIwftKs3z8Jgm0Nz91bJVT0BFQ5.',NULL,NULL,'activo','2026-05-12 04:06:05','2026-05-12 04:06:05'),(14,239,6,'$2y$12$DXeRfPwJUMI4wqRfwB8E5.JuM2vx1ILaR90y.scciUqSUn2KiFxU.',NULL,NULL,'activo','2026-05-12 04:07:28','2026-05-12 04:07:28'),(15,240,6,'$2y$12$hD8yR0xd9//5CFpLnlorTuO/4h84J4ISYYKFunEpARF42KAou6uZe',NULL,NULL,'activo','2026-05-12 04:08:28','2026-05-12 04:08:28'),(16,241,6,'$2y$12$AJ.8JGgstj/RnBuQYTlgLuwUFFDv2wA16AITO102xdL5VNSh8lGSC',NULL,NULL,'activo','2026-05-12 04:09:49','2026-05-12 04:09:49'),(17,242,6,'$2y$12$/nxt33x9NKN/wUCyt3zw9uwJgfAdjrhoV15LltBoiXiWmQb56qFfi','2026-05-12 00:14:55',NULL,'activo','2026-05-12 04:11:39','2026-05-12 05:28:30'),(18,243,6,'$2y$12$R6AnOgX.ARv32DSZpkYAreeMDEOWvvj4RpohBZwVGBLpwxAQd2.LW',NULL,NULL,'activo','2026-05-12 04:12:22','2026-05-12 04:12:22'),(19,244,6,'$2y$12$./3qJOg80fiSdEj0BjZqweBfv6csBSZeMUHleZCTMoEgTIIuZCNRm',NULL,NULL,'activo','2026-05-12 04:14:18','2026-05-12 04:14:18'),(20,245,6,'$2y$12$tPXKSUb6qrc.5v0ajEA/rOi/5rHPieMXh4Pz3AqcV3PCD4X/Y/lnW',NULL,NULL,'activo','2026-05-12 04:17:57','2026-05-12 04:17:57');
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
INSERT INTO `vinculo_familiar` VALUES (1,60,1,'madre',1);
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

-- Dump completed on 2026-05-13  0:27:01

-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_log
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `log_level`
--

DROP TABLE IF EXISTS `log_level`;
CREATE TABLE `log_level` (
  `id` int unsigned NOT NULL,
  `name` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='log level of monolog system';

--
-- Dumping data for table `log_level`
--

LOCK TABLES `log_level` WRITE;

INSERT INTO `log_level` VALUES (100,'debug'),(200,'information'),(250,'notice'),(300,'warning'),(400,'error'),(500,'critical'),(550,'alert'),(600,'emergency');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

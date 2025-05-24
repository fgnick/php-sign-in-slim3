-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_log
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(65) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `url` text COLLATE utf8mb4_unicode_ci,
  `ip` text COLLATE utf8mb4_unicode_ci,
  `http_method` text COLLATE utf8mb4_unicode_ci,
  `server` text COLLATE utf8mb4_unicode_ci,
  `referrer` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `level` (`level`),
  KEY `time` (`time`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dump completed on 2025-05-23 16:04:36

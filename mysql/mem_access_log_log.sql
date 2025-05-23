-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: mem_access_log
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` VALUES (1,'web',250,'youremail@example.com password reset','2025-05-23 03:38:25','/password/forget','127.0.0.1','POST','127.0.0.1','http://127.0.0.1/password/forget?code=255'),(2,'web',600,'youremail@example.com password-reset mail error: http://127.0.0.1/password/new?v=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJZb3VySXNzdWVyTmFtZSIsImV4cCI6MTc0ODU3NjMwNSwic3ViIjoiIiwiYXVkIjoiIiwibmJmIjoxNzQ3OTcxNTA1LCJpYXQiOjE3NDc5NzE1MDUsImp0aSI6IlouNWgzYlo5UUNFOHFcL2JBVkhyXC9mdTY2aXJsWHY1S1N1dm1Nc1VrM3p2QjgiLCJjbGFpbXMiOnsic2NvcGUiOjF9fQ.pyhQmf6GmduxJ3qbFP4x6e2fFYAucGvjjl3PUTAwL74','2025-05-23 03:38:25','/password/forget','127.0.0.1','POST','127.0.0.1','http://127.0.0.1/password/forget?code=255');
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-23 16:04:36

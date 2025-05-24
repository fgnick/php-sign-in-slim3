-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `member_type`
--

DROP TABLE IF EXISTS `member_type`;
CREATE TABLE `member_type` (
  `id` tinyint unsigned NOT NULL,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_type`
--

LOCK TABLES `member_type` WRITE;

INSERT INTO `member_type` VALUES (2,'Administrator'),(3,'Member'),(1,'Owner');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

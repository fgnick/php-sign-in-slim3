-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `member_status`
--

DROP TABLE IF EXISTS `member_status`;
CREATE TABLE `member_status` (
  `id` tinyint unsigned NOT NULL,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_status`
--

LOCK TABLES `member_status` WRITE;

INSERT INTO `member_status` VALUES (1,'activate'),(2,'block'),(3,'initial'),(0,'removed');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

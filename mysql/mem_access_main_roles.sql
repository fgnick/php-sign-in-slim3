-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'when company id is 0, it means this is a default value, nobody can change it.',
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name_UNIQUE` (`company_id`,`name`),
  FULLTEXT KEY `search_FULLTEXT` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;

INSERT INTO `roles` VALUES (1,0,'Plan Lv1'),(2,0,'Plan Lv2'),(3,0,'Plan Lv3'),(4,0,'Plan Lv4'),(5,0,'Plan Lv5');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

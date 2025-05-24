-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
CREATE TABLE `company` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` int unsigned NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1 is activate\n0 is inactivate',
  `modify_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  KEY `fk_company_plan_idx` (`plan_id`),
  FULLTEXT KEY `search_FULLTEXT` (`name`),
  CONSTRAINT `fk_company_plan` FOREIGN KEY (`plan_id`) REFERENCES `company_plan` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;

INSERT INTO `company` VALUES (1,1,'The First Company',1,'2025-05-22 01:35:34','2020-06-12 03:46:13');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

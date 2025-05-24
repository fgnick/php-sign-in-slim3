-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `roles_properties`
--

DROP TABLE IF EXISTS `roles_properties`;
CREATE TABLE `roles_properties` (
  `role_id` bigint unsigned NOT NULL,
  `overview` tinyint unsigned NOT NULL DEFAULT '1',
  `member` tinyint unsigned NOT NULL DEFAULT '0',
  `role` tinyint unsigned NOT NULL DEFAULT '0',
  `system` tinyint unsigned NOT NULL DEFAULT '0',
  `settings` tinyint unsigned NOT NULL DEFAULT '0',
  `modify_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  CONSTRAINT `fk_roles_properties_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles_properties`
--

LOCK TABLES `roles_properties` WRITE;

INSERT INTO `roles_properties` VALUES (1,1,3,3,3,3,'2023-02-03 03:50:54'),(2,1,3,3,3,3,'2023-02-03 03:50:54'),(3,1,3,3,3,3,'2023-02-03 03:50:54'),(4,3,3,3,3,3,'2023-02-03 03:50:54'),(5,3,3,3,3,3,'2023-04-12 08:52:08');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:35

-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `company_plan`
--

DROP TABLE IF EXISTS `company_plan`;
CREATE TABLE `company_plan` (
  `id` int unsigned NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint unsigned NOT NULL DEFAULT '1',
  `member_num` int unsigned NOT NULL DEFAULT '3',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modify_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  KEY `fk_company_plan_role_idx` (`role_id`),
  CONSTRAINT `fk_company_plan_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_plan`
--

LOCK TABLES `company_plan` WRITE;

INSERT INTO `company_plan` VALUES (1,'Enterprise',5,10000,'','2025-05-22 01:32:32','2021-04-10 15:52:34'),(2,'Premium',4,1000,'','2025-05-16 14:56:41','2021-04-10 15:52:34'),(3,'Pro',3,100,'','2025-05-02 08:21:07','2021-04-10 15:52:34'),(4,'Basic',5,10,'','2025-05-16 14:56:41','2021-04-10 15:52:34'),(5,'Free',1,5,'','2025-05-16 14:56:41','2021-04-10 15:52:34');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

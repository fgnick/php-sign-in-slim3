-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `oauth_api`
--

DROP TABLE IF EXISTS `oauth_api`;
CREATE TABLE `oauth_api` (
  `id` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `access_id` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exp_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_index` (`user_id`),
  KEY `access_index` (`access_id`),
  KEY `exp_index` (`exp_time`),
  CONSTRAINT `fk_oauth_api_mem` FOREIGN KEY (`user_id`) REFERENCES `member` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dump completed on 2025-05-23 16:04:35

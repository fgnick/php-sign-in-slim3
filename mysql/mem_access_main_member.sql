-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pw` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '3' COMMENT '1 is activate, 2 is block, 3 is inital.',
  `type` tinyint unsigned NOT NULL,
  `otp_flg` tinyint unsigned NOT NULL DEFAULT '0',
  `role_id` bigint unsigned NOT NULL,
  `nickname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `msg` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modify_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  KEY `type_index` (`type`),
  KEY `company_index` (`company_id`),
  KEY `role_index` (`role_id`),
  KEY `fk_member_status_idx` (`status`),
  FULLTEXT KEY `search_index` (`email`,`nickname`,`msg`),
  CONSTRAINT `fk_member_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_member_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_member_status` FOREIGN KEY (`status`) REFERENCES `member_status` (`id`),
  CONSTRAINT `fk_member_type` FOREIGN KEY (`type`) REFERENCES `member_type` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member`
--

LOCK TABLES `member` WRITE;

INSERT INTO `member` VALUES (1,1,'youremail@example.com','$2y$10$CKL9YbVoNWxRaOy5JTwlFurxxhU4Chu/9sZL8myHChDSWHnmVkjM2',1,1,0,5,'owner','the first person','2025-05-22 01:33:52','2016-08-28 02:35:57');

UNLOCK TABLES;

-- Dump completed on 2025-05-23 16:04:36

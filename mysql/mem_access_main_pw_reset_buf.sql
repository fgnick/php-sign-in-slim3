-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `pw_reset_buf`
--

DROP TABLE IF EXISTS `pw_reset_buf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pw_reset_buf` (
  `rand_uid` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expired_on` bigint unsigned NOT NULL,
  `mem_id` bigint unsigned NOT NULL,
  `modify_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rand_uid`),
  KEY `member_index` (`mem_id`),
  CONSTRAINT `fk_pw_reset_buf_mem` FOREIGN KEY (`mem_id`) REFERENCES `member` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='a buffer table for the member password reset. save the random unique code';

-- Dump completed on 2025-05-23 16:04:36

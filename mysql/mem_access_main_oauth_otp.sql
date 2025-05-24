-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `oauth_otp`
--

DROP TABLE IF EXISTS `oauth_otp`;
CREATE TABLE `oauth_otp` (
  `uuid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mem_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `otp_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'please use function, password_hash, to encode the hash token',
  `is_used` tinyint NOT NULL DEFAULT '0' COMMENT 'if the otp code is correct and used, please set it up to 1. the systen will find it for removing',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'to write down how many times the user try to connect to. if too many times, you should to ban it',
  `channel` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `device_info` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modify_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`),
  KEY `delete_idx` (`is_used`),
  KEY `expire_idx` (`create_on`),
  KEY `fk_oauth_otp_company_idx` (`company_id`),
  KEY `fk_oauth_otp_member_idx` (`mem_id`),
  CONSTRAINT `fk_oauth_otp_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oauth_otp_member` FOREIGN KEY (`mem_id`) REFERENCES `member` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='working for OTP';


-- Dump completed on 2025-05-23 16:04:36

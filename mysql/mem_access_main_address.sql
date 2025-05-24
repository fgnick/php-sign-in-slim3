-- MySQL dump 10.13  Distrib 8.0.41
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE `address` (
  `hash_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` int unsigned NOT NULL,
  `zip_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_1` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address_2` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gps` point NOT NULL,
  `create_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'text from google map with different language.',
  PRIMARY KEY (`hash_id`),
  KEY `fk_address_country_code_idx` (`country_code`),
  KEY `zip_idx` (`zip_code`),
  KEY `state_idx` (`state`),
  KEY `city_idx` (`city`),
  SPATIAL KEY `gps_idx` (`gps`),
  FULLTEXT KEY `search_fulltext` (`zip_code`,`state`,`city`,`address_1`,`address_2`),
  CONSTRAINT `fk_address_country_code` FOREIGN KEY (`country_code`) REFERENCES `addr_country_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `address`
--

-- Dump completed on 2025-05-23 16:04:36

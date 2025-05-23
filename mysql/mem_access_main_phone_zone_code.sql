-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: mem_access_main
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `phone_zone_code`
--

DROP TABLE IF EXISTS `phone_zone_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phone_zone_code` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `alpha_2` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dial_code` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `alpha_2_UNIQUE` (`alpha_2`),
  FULLTEXT KEY `search_FULLTEXT` (`alpha_2`,`dial_code`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=243 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phone_zone_code`
--

LOCK TABLES `phone_zone_code` WRITE;
/*!40000 ALTER TABLE `phone_zone_code` DISABLE KEYS */;
INSERT INTO `phone_zone_code` VALUES (1,'AD','376','Andorra'),(2,'AE','971','United Arab Emirates'),(3,'AF','93','Afghanistan'),(4,'AG','1268','Antigua and Barbuda'),(5,'AI','1264','Anguilla'),(6,'AL','355','Albania'),(7,'AM','374','Armenia'),(8,'AN','599','Netherlands Antilles'),(9,'AO','244','Angola'),(10,'AQ','672','Antarctica'),(11,'AR','54','Argentina'),(12,'AS','1684','AmericanSamoa'),(13,'AT','43','Austria'),(14,'AU','61','Australia'),(15,'AW','297','Aruba'),(16,'AX','358','Aland Islands'),(17,'AZ','994','Azerbaijan'),(18,'BA','387','Bosnia and Herzegovina'),(19,'BB','1246','Barbados'),(20,'BD','880','Bangladesh'),(21,'BE','32','Belgium'),(22,'BF','226','Burkina Faso'),(23,'BG','359','Bulgaria'),(24,'BH','973','Bahrain'),(25,'BI','257','Burundi'),(26,'BJ','229','Benin'),(27,'BL','590','Saint Barthelemy'),(28,'BM','1441','Bermuda'),(29,'BN','673','Brunei Darussalam'),(30,'BO','591','Bolivia, Plurinational State of bolivia'),(31,'BR','55','Brazil'),(32,'BS','1242','Bahamas'),(33,'BT','975','Bhutan'),(34,'BW','267','Botswana'),(35,'BY','375','Belarus'),(36,'BZ','501','Belize'),(37,'CA','1','Canada'),(38,'CC','61','Cocos (Keeling) Islands'),(39,'CD','243','Congo, The Democratic Republic of the Congo'),(40,'CF','236','Central African Republic'),(41,'CG','242','Congo'),(42,'CH','41','Switzerland'),(43,'CI','225','Cote d\'Ivoire'),(44,'CK','682','Cook Islands'),(45,'CL','56','Chile'),(46,'CM','237','Cameroon'),(47,'CN','86','China'),(48,'CO','57','Colombia'),(49,'CR','506','Costa Rica'),(50,'CU','53','Cuba'),(51,'CV','238','Cape Verde'),(52,'CX','61','Christmas Island'),(53,'CY','357','Cyprus'),(54,'CZ','420','Czech Republic'),(55,'DE','49','Germany'),(56,'DJ','253','Djibouti'),(57,'DK','45','Denmark'),(58,'DM','1767','Dominica'),(59,'DO','1849','Dominican Republic'),(60,'DZ','213','Algeria'),(61,'EC','593','Ecuador'),(62,'EE','372','Estonia'),(63,'EG','20','Egypt'),(64,'ER','291','Eritrea'),(65,'ES','34','Spain'),(66,'ET','251','Ethiopia'),(67,'FI','358','Finland'),(68,'FJ','679','Fiji'),(69,'FK','500','Falkland Islands (Malvinas)'),(70,'FM','691','Micronesia, Federated States of Micronesia'),(71,'FO','298','Faroe Islands'),(72,'FR','33','France'),(73,'GA','241','Gabon'),(74,'GB','44','United Kingdom'),(75,'GD','1473','Grenada'),(76,'GE','995','Georgia'),(77,'GF','594','French Guiana'),(78,'GG','44','Guernsey'),(79,'GH','233','Ghana'),(80,'GI','350','Gibraltar'),(81,'GL','299','Greenland'),(82,'GM','220','Gambia'),(83,'GN','224','Guinea'),(84,'GP','590','Guadeloupe'),(85,'GQ','240','Equatorial Guinea'),(86,'GR','30','Greece'),(87,'GS','500','South Georgia and the South Sandwich Islands'),(88,'GT','502','Guatemala'),(89,'GU','1671','Guam'),(90,'GW','245','Guinea-Bissau'),(91,'GY','595','Guyana'),(92,'HK','852','Hong Kong'),(93,'HN','504','Honduras'),(94,'HR','385','Croatia'),(95,'HT','509','Haiti'),(96,'HU','36','Hungary'),(97,'ID','62','Indonesia'),(98,'IE','353','Ireland'),(99,'IL','972','Israel'),(100,'IM','44','Isle of Man'),(101,'IN','91','India'),(102,'IO','246','British Indian Ocean Territory'),(103,'IQ','964','Iraq'),(104,'IR','98','Iran, Islamic Republic of Persian Gulf'),(105,'IS','354','Iceland'),(106,'IT','39','Italy'),(107,'JE','44','Jersey'),(108,'JM','1876','Jamaica'),(109,'JO','962','Jordan'),(110,'JP','81','Japan'),(111,'KE','254','Kenya'),(112,'KG','996','Kyrgyzstan'),(113,'KH','855','Cambodia'),(114,'KI','686','Kiribati'),(115,'KM','269','Comoros'),(116,'KN','1869','Saint Kitts and Nevis'),(117,'KP','850','Korea, Democratic People\'s Republic of Korea'),(118,'KR','82','Korea, Republic of South Korea'),(119,'KW','965','Kuwait'),(120,'KY','345','Cayman Islands'),(121,'KZ','77','Kazakhstan'),(122,'LA','856','Laos'),(123,'LB','961','Lebanon'),(124,'LC','1758','Saint Lucia'),(125,'LI','423','Liechtenstein'),(126,'LK','94','Sri Lanka'),(127,'LR','231','Liberia'),(128,'LS','266','Lesotho'),(129,'LT','370','Lithuania'),(130,'LU','352','Luxembourg'),(131,'LV','371','Latvia'),(132,'LY','218','Libyan Arab Jamahiriya'),(133,'MA','212','Morocco'),(134,'MC','377','Monaco'),(135,'MD','373','Moldova'),(136,'ME','382','Montenegro'),(137,'MF','590','Saint Martin'),(138,'MG','261','Madagascar'),(139,'MH','692','Marshall Islands'),(140,'MK','389','Macedonia'),(141,'ML','223','Mali'),(142,'MM','95','Myanmar'),(143,'MN','976','Mongolia'),(144,'MO','853','Macao'),(145,'MP','1670','Northern Mariana Islands'),(146,'MQ','596','Martinique'),(147,'MR','222','Mauritania'),(148,'MS','1664','Montserrat'),(149,'MT','356','Malta'),(150,'MU','230','Mauritius'),(151,'MV','960','Maldives'),(152,'MW','265','Malawi'),(153,'MX','52','Mexico'),(154,'MY','60','Malaysia'),(155,'MZ','258','Mozambique'),(156,'NA','264','Namibia'),(157,'NC','687','New Caledonia'),(158,'NE','227','Niger'),(159,'NF','672','Norfolk Island'),(160,'NG','234','Nigeria'),(161,'NI','505','Nicaragua'),(162,'NL','31','Netherlands'),(163,'NO','47','Norway'),(164,'NP','977','Nepal'),(165,'NR','674','Nauru'),(166,'NU','683','Niue'),(167,'NZ','64','New Zealand'),(168,'OM','968','Oman'),(169,'PA','507','Panama'),(170,'PE','51','Peru'),(171,'PF','689','French Polynesia'),(172,'PG','675','Papua New Guinea'),(173,'PH','63','Philippines'),(174,'PK','92','Pakistan'),(175,'PL','48','Poland'),(176,'PM','508','Saint Pierre and Miquelon'),(177,'PN','872','Pitcairn'),(178,'PR','1939','Puerto Rico'),(179,'PS','970','Palestinian Territory, Occupied'),(180,'PT','351','Portugal'),(181,'PW','680','Palau'),(182,'PY','595','Paraguay'),(183,'QA','974','Qatar'),(184,'RE','262','Reunion'),(185,'RO','40','Romania'),(186,'RS','381','Serbia'),(187,'RU','7','Russia'),(188,'RW','250','Rwanda'),(189,'SA','966','Saudi Arabia'),(190,'SB','677','Solomon Islands'),(191,'SC','248','Seychelles'),(192,'SD','249','Sudan'),(193,'SE','46','Sweden'),(194,'SG','65','Singapore'),(195,'SH','290','Saint Helena, Ascension and Tristan Da Cunha'),(196,'SI','386','Slovenia'),(197,'SJ','47','Svalbard and Jan Mayen'),(198,'SK','421','Slovakia'),(199,'SL','232','Sierra Leone'),(200,'SM','378','San Marino'),(201,'SN','221','Senegal'),(202,'SO','252','Somalia'),(203,'SR','597','Suriname'),(204,'SS','211','South Sudan'),(205,'ST','239','Sao Tome and Principe'),(206,'SV','503','El Salvador'),(207,'SY','963','Syrian Arab Republic'),(208,'SZ','268','Swaziland'),(209,'TC','1649','Turks and Caicos Islands'),(210,'TD','235','Chad'),(211,'TG','228','Togo'),(212,'TH','66','Thailand'),(213,'TJ','992','Tajikistan'),(214,'TK','690','Tokelau'),(215,'TL','670','Timor-Leste'),(216,'TM','993','Turkmenistan'),(217,'TN','216','Tunisia'),(218,'TO','676','Tonga'),(219,'TR','90','Turkey'),(220,'TT','1868','Trinidad and Tobago'),(221,'TV','688','Tuvalu'),(222,'TW','886','Taiwan'),(223,'TZ','255','Tanzania, United Republic of Tanzania'),(224,'UA','380','Ukraine'),(225,'UG','256','Uganda'),(226,'US','1','United States'),(227,'UY','598','Uruguay'),(228,'UZ','998','Uzbekistan'),(229,'VA','379','Holy See (Vatican City State)'),(230,'VC','1784','Saint Vincent and the Grenadines'),(231,'VE','58','Venezuela, Bolivarian Republic of Venezuela'),(232,'VG','1284','Virgin Islands, British'),(233,'VI','1340','Virgin Islands, U.S.'),(234,'VN','84','Vietnam'),(235,'VU','678','Vanuatu'),(236,'WF','681','Wallis and Futuna'),(237,'WS','685','Samoa'),(238,'YE','967','Yemen'),(239,'YT','262','Mayotte'),(240,'ZA','27','South Africa'),(241,'ZM','260','Zambia'),(242,'ZW','263','Zimbabwe');
/*!40000 ALTER TABLE `phone_zone_code` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-23 16:04:36

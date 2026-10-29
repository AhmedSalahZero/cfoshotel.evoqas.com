-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: cfo_hotels_vue
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `acquisitions`
--

DROP TABLE IF EXISTS `acquisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acquisitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `land_installment_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `land_after_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `second_land_down_payment_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_land_down_payment_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `land_payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `land_contingency_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `land_purchase_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `land_equity_funding_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '100',
  `installment_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `has_land_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_construction_contingency_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_construction_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_hard_construction_cost_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_construction_duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_construction_start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_construction_end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_execution_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_down_payment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_balance_rate_one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_balance_rate_two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_due_one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_due_two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hard_equity_funding` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_due_two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_balance_rate_two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_due_one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_balance_rate_one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_construction_start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_down_payment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_construction_end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_execution_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_construction_contingency_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_construction_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_soft_construction_cost_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `soft_construction_duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `acquisitions_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `acquisitions_company_id_foreign` (`company_id`),
  CONSTRAINT `acquisitions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acquisitions_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acquisitions`
--

LOCK TABLES `acquisitions` WRITE;
/*!40000 ALTER TABLE `acquisitions` DISABLE KEYS */;
INSERT INTO `acquisitions` VALUES (4,'4','6','20','30','installment','2','300000000','0','80','quarterly',NULL,'customize','{\"rate\":[\"0\",\"0\",\"0\",\"0\"],\"due_in_days\":[\"0\",\"0\",\"0\",\"0\"]}',6,2,'2026-10-29 08:10:30','2026-10-29 08:10:30','1','7','1150000000','1','26','6','32','s-curve','10','60','30','30','60','70','60','40','30','40','0','20','32','straight-line','5','150000000','1','32');
/*!40000 ALTER TABLE `acquisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('cfo-hotels-cache-dup:22a54f3d934aa655d161266055a5d35d','b:1;',1793256986),('cfo-hotels-cache-dup:29a04b54fb0a050dbb7b74a3c84064ba','b:1;',1793256943),('cfo-hotels-cache-dup:2f1217c88f1a0a569cd7e81f6a97f23c','b:1;',1793256966),('cfo-hotels-cache-dup:2f3439e938dd9ea13b11be1cf35e1f09','b:1;',1793256949),('cfo-hotels-cache-dup:6989434e0b718ea6efa11dc8fa4ec447','b:1;',1793256927),('cfo-hotels-cache-dup:709d70fdfa129296dc6513dce696a2d7','b:1;',1793256958),('cfo-hotels-cache-dup:76b49de9f897a7397875935c85da6dd3','b:1;',1793256938),('cfo-hotels-cache-dup:8ce3f752a42ed5a32b8760b1accf359e','b:1;',1793256991),('cfo-hotels-cache-dup:9f6c2d18371f484eb13b7bbae5c8068b','b:1;',1793256893),('cfo-hotels-cache-dup:b17d4bc3ccfd30abc779909487e10605','b:1;',1793256913),('cfo-hotels-cache-dup:d6dcc2eaf7f4893751256f26aa4c5f4a','b:1;',1793256917),('cfo-hotels-cache-dup:dbdd43e3228da06a17d73eaead300206','b:1;',1793256932),('cfo-hotels-cache-dup:dd3c4bfeea8ad8bf871efa1debfd5139','b:1;',1793256971),('cfo-hotels-cache-dup:f56df9ebb459d24c0a8d947586d00a48','b:1;',1793256922);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `casino_names`
--

DROP TABLE IF EXISTS `casino_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `casino_names` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `casino_names_company_id_foreign` (`company_id`),
  CONSTRAINT `casino_names_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `casino_names`
--

LOCK TABLES `casino_names` WRITE;
/*!40000 ALTER TABLE `casino_names` DISABLE KEYS */;
INSERT INTO `casino_names` VALUES (1,'Total Gaming Facilities',1,1,NULL,NULL),(2,'Large Gaming Hall',1,1,NULL,NULL),(3,'Small Gaming Hall',1,1,NULL,NULL),(4,'Total Gaming Facilities',1,2,NULL,NULL),(5,'Large Gaming Hall',1,2,NULL,NULL),(6,'Small Gaming Hall',1,2,NULL,NULL);
/*!40000 ALTER TABLE `casino_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `casinos`
--

DROP TABLE IF EXISTS `casinos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `casinos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `casino_count` bigint DEFAULT NULL,
  `f&b_facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `casino_cover` bigint DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `percentage_from_rooms_revenues` longtext COLLATE utf8mb4_unicode_ci,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_per_day` longtext COLLATE utf8mb4_unicode_ci,
  `guest_capture_cover_percentage` longtext COLLATE utf8mb4_unicode_ci,
  `charges_value_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_value_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_value_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chosen_casino_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `casino_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `casinos_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `casinos_casino_type_id_foreign` (`casino_type_id`),
  CONSTRAINT `casinos_casino_type_id_foreign` FOREIGN KEY (`casino_type_id`) REFERENCES `casino_names` (`id`),
  CONSTRAINT `casinos_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `casinos`
--

LOCK TABLES `casinos` WRITE;
/*!40000 ALTER TABLE `casinos` DISABLE KEYS */;
INSERT INTO `casinos` VALUES (1,2,NULL,50,1,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-28 23:31:09','2026-10-28 23:17:36',2),(6,1,'guest_count_target_per_day_charges_per_guest_method',60,6,NULL,'system_default','','monthly',NULL,'{\"2\":\"15\",\"3\":\"20\",\"4\":\"25\",\"5\":\"25\",\"6\":\"25\",\"7\":\"25\",\"8\":\"25\",\"9\":\"25\",\"10\":\"25\",\"11\":\"25\",\"12\":\"25\",\"13\":\"25\",\"14\":\"25\",\"15\":\"25\",\"16\":\"25\",\"17\":\"25\",\"18\":\"25\",\"19\":\"25\"}','38.47372526','3.5','3.5','2','35','2026-10-29 06:10:27','2026-10-29 06:10:27',6);
/*!40000 ALTER TABLE `casinos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trial',
  `users_limit` smallint unsigned NOT NULL DEFAULT '5',
  `subscription_starts_at` date DEFAULT NULL,
  `subscription_ends_at` date DEFAULT NULL,
  `contact_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_status_index` (`status`),
  KEY `companies_subscription_ends_at_index` (`subscription_ends_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Line Contractor','لاين للمقاولات','active',5,'2026-10-01','2027-09-30',NULL,NULL,NULL,1,'2026-10-28 22:07:12','2026-10-28 22:07:12'),(2,'Sunrise Hospitality Investments (Demo)','صن رايز للاستثمار الفندقي (تجريبي)','active',5,'2026-10-29',NULL,NULL,'demo@cfo-hotels.com','Demo company for testing every input form. Do not use for real studies.',NULL,'2026-10-29 05:41:03','2026-10-29 05:41:03');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_en` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Egypt','مصر',NULL,'2026-10-28 13:38:00',NULL),(2,'Saudi Arabia','المملكة العربية السعودية',NULL,'2026-10-28 13:38:00',NULL),(3,'United Arab Emirates','دولة الإمارات العربية المتحدة',NULL,'2026-10-28 13:38:00',NULL),(4,'United States','الولايات المتحده الأمريكيه',NULL,'2026-10-28 13:38:00',NULL),(5,'Kuwait','الكويت',NULL,'2026-10-28 13:38:00',NULL),(6,'Kenya','كينيا',NULL,'2026-10-28 13:38:00',NULL),(7,'Jordan','الأردن',NULL,'2026-10-28 13:38:00',NULL);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES (1,'EGP',NULL,NULL),(2,'USD',NULL,NULL),(3,'EURO',NULL,NULL),(4,'AED',NULL,NULL),(5,'SAR',NULL,NULL),(6,'QAR',NULL,NULL);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_expenses`
--

DROP TABLE IF EXISTS `department_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci,
  `manpower_payload` longtext COLLATE utf8mb4_unicode_ci,
  `expense_per_night_sold` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_per_guest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inventory_coverage_days` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beginning_inventory_balance_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash_payment_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deferred_payment_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_days` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_net_salary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chosen_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `net_salary_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_taxes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_insurance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `night_expense_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chosen_night_expense_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `night_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `night_expense_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_expense_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_expense_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_per_guest_sold` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chosen_guest_expense_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opex_payment_terms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percentage_from_fixed_assets` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_up_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `department_expenses_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `department_expenses_company_id_foreign` (`company_id`),
  CONSTRAINT `department_expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `department_expenses_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=695 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_expenses`
--

LOCK TABLES `department_expenses` WRITE;
/*!40000 ALTER TABLE `department_expenses` DISABLE KEYS */;
INSERT INTO `department_expenses` VALUES (1,'rooms',1,'variable_expenses_as_percentage_from_rooms_revenues','ABC','{\"2\":\"3\",\"3\":\"3\",\"4\":\"3\",\"5\":\"3\",\"6\":\"3\",\"7\":\"3\",\"8\":\"3\",\"9\":\"3\",\"10\":\"3\",\"11\":\"3\",\"12\":\"3\",\"13\":\"3\",\"14\":\"3\",\"15\":\"3\",\"16\":\"3\",\"17\":\"3\",\"18\":\"3\",\"19\":\"3\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2026-10-29 03:58:09','2026-10-29 03:58:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(2,'rooms',1,'variable_expenses_as_percentage_from_rooms_revenues','DSD','{\"2\":\"4\",\"3\":\"4\",\"4\":\"4\",\"5\":\"4\",\"6\":\"4\",\"7\":\"4\",\"8\":\"4\",\"9\":\"4\",\"10\":\"4\",\"11\":\"4\",\"12\":\"4\",\"13\":\"4\",\"14\":\"4\",\"15\":\"4\",\"16\":\"4\",\"17\":\"4\",\"18\":\"4\",\"19\":\"4\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2026-10-29 03:58:09','2026-10-29 03:58:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(212,'rooms',6,'variable_expenses_as_percentage_from_rooms_revenues','Credit Card Commissions','{\"2\":\"1.5\",\"3\":\"1.5\",\"4\":\"1.5\",\"5\":\"1.5\",\"6\":\"1.5\",\"7\":\"1.5\",\"8\":\"1.5\",\"9\":\"1.5\",\"10\":\"1.5\",\"11\":\"1.5\",\"12\":\"1.5\",\"13\":\"1.5\",\"14\":\"1.5\",\"15\":\"1.5\",\"16\":\"1.5\",\"17\":\"1.5\",\"18\":\"1.5\",\"19\":\"1.5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(213,'rooms',6,'variable_expenses_as_percentage_from_rooms_revenues','Distribution & Reservation Systems','{\"2\":\"1.5\",\"3\":\"1.5\",\"4\":\"1.5\",\"5\":\"1.5\",\"6\":\"1.5\",\"7\":\"1.5\",\"8\":\"1.5\",\"9\":\"1.5\",\"10\":\"1.5\",\"11\":\"1.5\",\"12\":\"1.5\",\"13\":\"1.5\",\"14\":\"1.5\",\"15\":\"1.5\",\"16\":\"1.5\",\"17\":\"1.5\",\"18\":\"1.5\",\"19\":\"1.5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(214,'rooms',6,'variable_expenses_as_percentage_from_rooms_revenues','Guest Transfers & Shuttle','{\"2\":\"1\",\"3\":\"1\",\"4\":\"1\",\"5\":\"1\",\"6\":\"1\",\"7\":\"1\",\"8\":\"1\",\"9\":\"1\",\"10\":\"1\",\"11\":\"1\",\"12\":\"1\",\"13\":\"1\",\"14\":\"1\",\"15\":\"1\",\"16\":\"1\",\"17\":\"1\",\"18\":\"1\",\"19\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(215,'rooms',6,'variable_expenses_as_cost_per_night_sold','Housekeeping Supplies & Amenities',NULL,NULL,'190',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,'9','2026-10-29 08:10:29','2026-10-29 08:10:29','253.17617589',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(216,'rooms',6,'variable_expenses_as_cost_per_night_sold','Laundry & Linen Replacement',NULL,NULL,'130',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,'9','2026-10-29 08:10:29','2026-10-29 08:10:29','173.22580455',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(217,'rooms',6,'variable_expenses_as_cost_per_guest','Welcome Amenities & Guest Services',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','106.60049511','11','80','1',NULL,NULL,NULL,'0','0'),(218,'rooms',6,'disposable_expenses_rate','Guest Consumables & Minibar Stock','{\"2\":\"1.8\",\"3\":\"1.8\",\"4\":\"1.8\",\"5\":\"1.8\",\"6\":\"1.8\",\"7\":\"1.8\",\"8\":\"1.8\",\"9\":\"1.8\",\"10\":\"1.8\",\"11\":\"1.8\",\"12\":\"1.8\",\"13\":\"1.8\",\"14\":\"1.8\",\"15\":\"1.8\",\"16\":\"1.8\",\"17\":\"1.8\",\"18\":\"1.8\",\"19\":\"1.8\"}',NULL,NULL,NULL,'45','4000000','40','60','60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(219,'rooms',6,'rooms_manpower_expense','Rooms Division Manager',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'95000','1','11','126588.08794288','10','22','3',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(220,'rooms',6,'rooms_manpower_expense','Front Office Manager',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'55000','1','11','73287.84038798','10','18','4',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(221,'rooms',6,'rooms_manpower_expense','Receptionists & Guest Relations',NULL,'{\"33\":\"20\",\"34\":\"20\",\"35\":\"20\",\"36\":\"20\",\"37\":\"20\",\"38\":\"20\",\"39\":\"20\",\"40\":\"20\",\"41\":\"20\",\"42\":\"20\",\"43\":\"20\",\"44\":\"20\",\"45\":\"24\",\"46\":\"24\",\"47\":\"24\",\"48\":\"24\",\"49\":\"24\",\"50\":\"24\",\"51\":\"24\",\"52\":\"24\",\"53\":\"24\",\"54\":\"24\",\"55\":\"24\",\"56\":\"24\",\"57\":\"24\",\"58\":\"24\",\"59\":\"24\",\"60\":\"24\",\"61\":\"24\",\"62\":\"24\",\"63\":\"24\",\"64\":\"24\",\"65\":\"24\",\"66\":\"24\",\"67\":\"24\",\"68\":\"24\",\"69\":\"24\",\"70\":\"24\",\"71\":\"24\",\"72\":\"24\",\"73\":\"24\",\"74\":\"24\",\"75\":\"24\",\"76\":\"24\",\"77\":\"24\",\"78\":\"24\",\"79\":\"24\",\"80\":\"24\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'14000','1','11','18655.08664421','10','4','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(222,'rooms',6,'rooms_manpower_expense','Bell Staff & Concierge',NULL,'{\"33\":\"15\",\"34\":\"15\",\"35\":\"15\",\"36\":\"15\",\"37\":\"15\",\"38\":\"15\",\"39\":\"15\",\"40\":\"15\",\"41\":\"15\",\"42\":\"15\",\"43\":\"15\",\"44\":\"15\",\"45\":\"18\",\"46\":\"18\",\"47\":\"18\",\"48\":\"18\",\"49\":\"18\",\"50\":\"18\",\"51\":\"18\",\"52\":\"18\",\"53\":\"18\",\"54\":\"18\",\"55\":\"18\",\"56\":\"18\",\"57\":\"18\",\"58\":\"18\",\"59\":\"18\",\"60\":\"18\",\"61\":\"18\",\"62\":\"18\",\"63\":\"18\",\"64\":\"18\",\"65\":\"18\",\"66\":\"18\",\"67\":\"18\",\"68\":\"18\",\"69\":\"18\",\"70\":\"18\",\"71\":\"18\",\"72\":\"18\",\"73\":\"18\",\"74\":\"18\",\"75\":\"18\",\"76\":\"18\",\"77\":\"18\",\"78\":\"18\",\"79\":\"18\",\"80\":\"18\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'10500','1','11','13991.31498316','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(223,'rooms',6,'rooms_manpower_expense','Executive Housekeeper',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'60000','1','11','79950.37133234','10','18','4',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(224,'rooms',6,'rooms_manpower_expense','Housekeeping Supervisors',NULL,'{\"33\":\"7\",\"34\":\"7\",\"35\":\"7\",\"36\":\"7\",\"37\":\"7\",\"38\":\"7\",\"39\":\"7\",\"40\":\"7\",\"41\":\"7\",\"42\":\"7\",\"43\":\"7\",\"44\":\"7\",\"45\":\"8\",\"46\":\"8\",\"47\":\"8\",\"48\":\"8\",\"49\":\"8\",\"50\":\"8\",\"51\":\"8\",\"52\":\"8\",\"53\":\"8\",\"54\":\"8\",\"55\":\"8\",\"56\":\"8\",\"57\":\"8\",\"58\":\"8\",\"59\":\"8\",\"60\":\"8\",\"61\":\"8\",\"62\":\"8\",\"63\":\"8\",\"64\":\"8\",\"65\":\"8\",\"66\":\"8\",\"67\":\"8\",\"68\":\"8\",\"69\":\"8\",\"70\":\"8\",\"71\":\"8\",\"72\":\"8\",\"73\":\"8\",\"74\":\"8\",\"75\":\"8\",\"76\":\"8\",\"77\":\"8\",\"78\":\"8\",\"79\":\"8\",\"80\":\"8\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'19000','1','11','25317.61758858','10','6','10',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(225,'rooms',6,'rooms_manpower_expense','Room Attendants',NULL,'{\"33\":\"82\",\"34\":\"82\",\"35\":\"82\",\"36\":\"82\",\"37\":\"82\",\"38\":\"82\",\"39\":\"82\",\"40\":\"82\",\"41\":\"82\",\"42\":\"82\",\"43\":\"82\",\"44\":\"82\",\"45\":\"96\",\"46\":\"96\",\"47\":\"96\",\"48\":\"96\",\"49\":\"96\",\"50\":\"96\",\"51\":\"96\",\"52\":\"96\",\"53\":\"96\",\"54\":\"96\",\"55\":\"96\",\"56\":\"96\",\"57\":\"96\",\"58\":\"96\",\"59\":\"96\",\"60\":\"96\",\"61\":\"96\",\"62\":\"96\",\"63\":\"96\",\"64\":\"96\",\"65\":\"96\",\"66\":\"96\",\"67\":\"96\",\"68\":\"96\",\"69\":\"96\",\"70\":\"96\",\"71\":\"96\",\"72\":\"96\",\"73\":\"96\",\"74\":\"96\",\"75\":\"96\",\"76\":\"96\",\"77\":\"96\",\"78\":\"96\",\"79\":\"96\",\"80\":\"96\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9800','1','11','13058.56065095','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(226,'rooms',6,'rooms_manpower_expense','Public Area & Laundry Staff',NULL,'{\"33\":\"29\",\"34\":\"29\",\"35\":\"29\",\"36\":\"29\",\"37\":\"29\",\"38\":\"29\",\"39\":\"29\",\"40\":\"29\",\"41\":\"29\",\"42\":\"29\",\"43\":\"29\",\"44\":\"29\",\"45\":\"34\",\"46\":\"34\",\"47\":\"34\",\"48\":\"34\",\"49\":\"34\",\"50\":\"34\",\"51\":\"34\",\"52\":\"34\",\"53\":\"34\",\"54\":\"34\",\"55\":\"34\",\"56\":\"34\",\"57\":\"34\",\"58\":\"34\",\"59\":\"34\",\"60\":\"34\",\"61\":\"34\",\"62\":\"34\",\"63\":\"34\",\"64\":\"34\",\"65\":\"34\",\"66\":\"34\",\"67\":\"34\",\"68\":\"34\",\"69\":\"34\",\"70\":\"34\",\"71\":\"34\",\"72\":\"34\",\"73\":\"34\",\"74\":\"34\",\"75\":\"34\",\"76\":\"34\",\"77\":\"34\",\"78\":\"34\",\"79\":\"34\",\"80\":\"34\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9000','1','11','11992.55569985','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(227,'foods',6,'variable_expenses_as_percentage_from_rooms_revenues','Food & Beverage Cost of Sales','{\"2\":\"35.5\",\"3\":\"35.5\",\"4\":\"35.5\",\"5\":\"35.5\",\"6\":\"35.5\",\"7\":\"35.5\",\"8\":\"35.5\",\"9\":\"35.5\",\"10\":\"35.5\",\"11\":\"35.5\",\"12\":\"35.5\",\"13\":\"35.5\",\"14\":\"35.5\",\"15\":\"35.5\",\"16\":\"35.5\",\"17\":\"35.5\",\"18\":\"35.5\",\"19\":\"35.5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(228,'foods',6,'variable_expenses_as_percentage_from_rooms_revenues','Operating Supplies, Linen & Breakage','{\"2\":\"2.5\",\"3\":\"2.5\",\"4\":\"2.5\",\"5\":\"2.5\",\"6\":\"2.5\",\"7\":\"2.5\",\"8\":\"2.5\",\"9\":\"2.5\",\"10\":\"2.5\",\"11\":\"2.5\",\"12\":\"2.5\",\"13\":\"2.5\",\"14\":\"2.5\",\"15\":\"2.5\",\"16\":\"2.5\",\"17\":\"2.5\",\"18\":\"2.5\",\"19\":\"2.5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(229,'foods',6,'disposable_expenses_rate','Kitchen & Bar Consumables','{\"2\":\"3\",\"3\":\"3\",\"4\":\"3\",\"5\":\"3\",\"6\":\"3\",\"7\":\"3\",\"8\":\"3\",\"9\":\"3\",\"10\":\"3\",\"11\":\"3\",\"12\":\"3\",\"13\":\"3\",\"14\":\"3\",\"15\":\"3\",\"16\":\"3\",\"17\":\"3\",\"18\":\"3\",\"19\":\"3\"}',NULL,NULL,NULL,'30','6000000','50','50','30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(230,'foods',6,'rooms_manpower_expense','F&B Director',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'90000','1','11','119925.55699852','10','22','3',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(231,'foods',6,'rooms_manpower_expense','Restaurant Managers',NULL,'{\"33\":\"4\",\"34\":\"4\",\"35\":\"4\",\"36\":\"4\",\"37\":\"4\",\"38\":\"4\",\"39\":\"4\",\"40\":\"4\",\"41\":\"4\",\"42\":\"4\",\"43\":\"4\",\"44\":\"4\",\"45\":\"5\",\"46\":\"5\",\"47\":\"5\",\"48\":\"5\",\"49\":\"5\",\"50\":\"5\",\"51\":\"5\",\"52\":\"5\",\"53\":\"5\",\"54\":\"5\",\"55\":\"5\",\"56\":\"5\",\"57\":\"5\",\"58\":\"5\",\"59\":\"5\",\"60\":\"5\",\"61\":\"5\",\"62\":\"5\",\"63\":\"5\",\"64\":\"5\",\"65\":\"5\",\"66\":\"5\",\"67\":\"5\",\"68\":\"5\",\"69\":\"5\",\"70\":\"5\",\"71\":\"5\",\"72\":\"5\",\"73\":\"5\",\"74\":\"5\",\"75\":\"5\",\"76\":\"5\",\"77\":\"5\",\"78\":\"5\",\"79\":\"5\",\"80\":\"5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'42000','1','11','55965.25993264','10','15','5',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(232,'foods',6,'rooms_manpower_expense','Chefs de Partie & Cooks',NULL,'{\"33\":\"53\",\"34\":\"53\",\"35\":\"53\",\"36\":\"53\",\"37\":\"53\",\"38\":\"53\",\"39\":\"53\",\"40\":\"53\",\"41\":\"53\",\"42\":\"53\",\"43\":\"53\",\"44\":\"53\",\"45\":\"62\",\"46\":\"62\",\"47\":\"62\",\"48\":\"62\",\"49\":\"62\",\"50\":\"62\",\"51\":\"62\",\"52\":\"62\",\"53\":\"62\",\"54\":\"62\",\"55\":\"62\",\"56\":\"62\",\"57\":\"62\",\"58\":\"62\",\"59\":\"62\",\"60\":\"62\",\"61\":\"62\",\"62\":\"62\",\"63\":\"62\",\"64\":\"62\",\"65\":\"62\",\"66\":\"62\",\"67\":\"62\",\"68\":\"62\",\"69\":\"62\",\"70\":\"62\",\"71\":\"62\",\"72\":\"62\",\"73\":\"62\",\"74\":\"62\",\"75\":\"62\",\"76\":\"62\",\"77\":\"62\",\"78\":\"62\",\"79\":\"62\",\"80\":\"62\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'15500','1','11','20653.84592752','10','5','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(233,'foods',6,'rooms_manpower_expense','Stewards & Kitchen Helpers',NULL,'{\"33\":\"41\",\"34\":\"41\",\"35\":\"41\",\"36\":\"41\",\"37\":\"41\",\"38\":\"41\",\"39\":\"41\",\"40\":\"41\",\"41\":\"41\",\"42\":\"41\",\"43\":\"41\",\"44\":\"41\",\"45\":\"48\",\"46\":\"48\",\"47\":\"48\",\"48\":\"48\",\"49\":\"48\",\"50\":\"48\",\"51\":\"48\",\"52\":\"48\",\"53\":\"48\",\"54\":\"48\",\"55\":\"48\",\"56\":\"48\",\"57\":\"48\",\"58\":\"48\",\"59\":\"48\",\"60\":\"48\",\"61\":\"48\",\"62\":\"48\",\"63\":\"48\",\"64\":\"48\",\"65\":\"48\",\"66\":\"48\",\"67\":\"48\",\"68\":\"48\",\"69\":\"48\",\"70\":\"48\",\"71\":\"48\",\"72\":\"48\",\"73\":\"48\",\"74\":\"48\",\"75\":\"48\",\"76\":\"48\",\"77\":\"48\",\"78\":\"48\",\"79\":\"48\",\"80\":\"48\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9000','1','11','11992.55569985','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(234,'foods',6,'rooms_manpower_expense','Waiters & Hostesses',NULL,'{\"33\":\"63\",\"34\":\"63\",\"35\":\"63\",\"36\":\"63\",\"37\":\"63\",\"38\":\"63\",\"39\":\"63\",\"40\":\"63\",\"41\":\"63\",\"42\":\"63\",\"43\":\"63\",\"44\":\"63\",\"45\":\"74\",\"46\":\"74\",\"47\":\"74\",\"48\":\"74\",\"49\":\"74\",\"50\":\"74\",\"51\":\"74\",\"52\":\"74\",\"53\":\"74\",\"54\":\"74\",\"55\":\"74\",\"56\":\"74\",\"57\":\"74\",\"58\":\"74\",\"59\":\"74\",\"60\":\"74\",\"61\":\"74\",\"62\":\"74\",\"63\":\"74\",\"64\":\"74\",\"65\":\"74\",\"66\":\"74\",\"67\":\"74\",\"68\":\"74\",\"69\":\"74\",\"70\":\"74\",\"71\":\"74\",\"72\":\"74\",\"73\":\"74\",\"74\":\"74\",\"75\":\"74\",\"76\":\"74\",\"77\":\"74\",\"78\":\"74\",\"79\":\"74\",\"80\":\"74\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'10000','1','11','13325.06188872','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(235,'foods',6,'rooms_manpower_expense','Bartenders',NULL,'{\"33\":\"14\",\"34\":\"14\",\"35\":\"14\",\"36\":\"14\",\"37\":\"14\",\"38\":\"14\",\"39\":\"14\",\"40\":\"14\",\"41\":\"14\",\"42\":\"14\",\"43\":\"14\",\"44\":\"14\",\"45\":\"16\",\"46\":\"16\",\"47\":\"16\",\"48\":\"16\",\"49\":\"16\",\"50\":\"16\",\"51\":\"16\",\"52\":\"16\",\"53\":\"16\",\"54\":\"16\",\"55\":\"16\",\"56\":\"16\",\"57\":\"16\",\"58\":\"16\",\"59\":\"16\",\"60\":\"16\",\"61\":\"16\",\"62\":\"16\",\"63\":\"16\",\"64\":\"16\",\"65\":\"16\",\"66\":\"16\",\"67\":\"16\",\"68\":\"16\",\"69\":\"16\",\"70\":\"16\",\"71\":\"16\",\"72\":\"16\",\"73\":\"16\",\"74\":\"16\",\"75\":\"16\",\"76\":\"16\",\"77\":\"16\",\"78\":\"16\",\"79\":\"16\",\"80\":\"16\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'12500','1','11','16656.3273609','10','3','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(236,'foods',6,'rooms_manpower_expense','Banquet & Events Staff',NULL,'{\"33\":\"20\",\"34\":\"20\",\"35\":\"20\",\"36\":\"20\",\"37\":\"20\",\"38\":\"20\",\"39\":\"20\",\"40\":\"20\",\"41\":\"20\",\"42\":\"20\",\"43\":\"20\",\"44\":\"20\",\"45\":\"24\",\"46\":\"24\",\"47\":\"24\",\"48\":\"24\",\"49\":\"24\",\"50\":\"24\",\"51\":\"24\",\"52\":\"24\",\"53\":\"24\",\"54\":\"24\",\"55\":\"24\",\"56\":\"24\",\"57\":\"24\",\"58\":\"24\",\"59\":\"24\",\"60\":\"24\",\"61\":\"24\",\"62\":\"24\",\"63\":\"24\",\"64\":\"24\",\"65\":\"24\",\"66\":\"24\",\"67\":\"24\",\"68\":\"24\",\"69\":\"24\",\"70\":\"24\",\"71\":\"24\",\"72\":\"24\",\"73\":\"24\",\"74\":\"24\",\"75\":\"24\",\"76\":\"24\",\"77\":\"24\",\"78\":\"24\",\"79\":\"24\",\"80\":\"24\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'10000','1','11','13325.06188872','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(237,'gaming',6,'variable_expenses_as_percentage_from_rooms_revenues','Gaming Taxes & Licence Fees','{\"2\":\"8\",\"3\":\"8\",\"4\":\"8\",\"5\":\"8\",\"6\":\"8\",\"7\":\"8\",\"8\":\"8\",\"9\":\"8\",\"10\":\"8\",\"11\":\"8\",\"12\":\"8\",\"13\":\"8\",\"14\":\"8\",\"15\":\"8\",\"16\":\"8\",\"17\":\"8\",\"18\":\"8\",\"19\":\"8\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(238,'gaming',6,'variable_expenses_as_percentage_from_rooms_revenues','Promotions & Complimentaries','{\"2\":\"5\",\"3\":\"5\",\"4\":\"5\",\"5\":\"5\",\"6\":\"5\",\"7\":\"5\",\"8\":\"5\",\"9\":\"5\",\"10\":\"5\",\"11\":\"5\",\"12\":\"5\",\"13\":\"5\",\"14\":\"5\",\"15\":\"5\",\"16\":\"5\",\"17\":\"5\",\"18\":\"5\",\"19\":\"5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(239,'gaming',6,'disposable_expenses_rate','Gaming Consumables (cards, chips)','{\"2\":\"1\",\"3\":\"1\",\"4\":\"1\",\"5\":\"1\",\"6\":\"1\",\"7\":\"1\",\"8\":\"1\",\"9\":\"1\",\"10\":\"1\",\"11\":\"1\",\"12\":\"1\",\"13\":\"1\",\"14\":\"1\",\"15\":\"1\",\"16\":\"1\",\"17\":\"1\",\"18\":\"1\",\"19\":\"1\"}',NULL,NULL,NULL,'90','600000','100','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(240,'gaming',6,'rooms_manpower_expense','Gaming Manager',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'85000','1','11','113263.02605415','10','22','3',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(241,'gaming',6,'rooms_manpower_expense','Dealers',NULL,'{\"33\":\"7\",\"34\":\"7\",\"35\":\"7\",\"36\":\"7\",\"37\":\"7\",\"38\":\"7\",\"39\":\"7\",\"40\":\"7\",\"41\":\"7\",\"42\":\"7\",\"43\":\"7\",\"44\":\"7\",\"45\":\"8\",\"46\":\"8\",\"47\":\"8\",\"48\":\"8\",\"49\":\"8\",\"50\":\"8\",\"51\":\"8\",\"52\":\"8\",\"53\":\"8\",\"54\":\"8\",\"55\":\"8\",\"56\":\"8\",\"57\":\"8\",\"58\":\"8\",\"59\":\"8\",\"60\":\"8\",\"61\":\"8\",\"62\":\"8\",\"63\":\"8\",\"64\":\"8\",\"65\":\"8\",\"66\":\"8\",\"67\":\"8\",\"68\":\"8\",\"69\":\"8\",\"70\":\"8\",\"71\":\"8\",\"72\":\"8\",\"73\":\"8\",\"74\":\"8\",\"75\":\"8\",\"76\":\"8\",\"77\":\"8\",\"78\":\"8\",\"79\":\"8\",\"80\":\"8\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'16000','1','11','21320.09902196','10','5','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(242,'gaming',6,'rooms_manpower_expense','Gaming Security & Cashiers',NULL,'{\"33\":\"4\",\"34\":\"4\",\"35\":\"4\",\"36\":\"4\",\"37\":\"4\",\"38\":\"4\",\"39\":\"4\",\"40\":\"4\",\"41\":\"4\",\"42\":\"4\",\"43\":\"4\",\"44\":\"4\",\"45\":\"5\",\"46\":\"5\",\"47\":\"5\",\"48\":\"5\",\"49\":\"5\",\"50\":\"5\",\"51\":\"5\",\"52\":\"5\",\"53\":\"5\",\"54\":\"5\",\"55\":\"5\",\"56\":\"5\",\"57\":\"5\",\"58\":\"5\",\"59\":\"5\",\"60\":\"5\",\"61\":\"5\",\"62\":\"5\",\"63\":\"5\",\"64\":\"5\",\"65\":\"5\",\"66\":\"5\",\"67\":\"5\",\"68\":\"5\",\"69\":\"5\",\"70\":\"5\",\"71\":\"5\",\"72\":\"5\",\"73\":\"5\",\"74\":\"5\",\"75\":\"5\",\"76\":\"5\",\"77\":\"5\",\"78\":\"5\",\"79\":\"5\",\"80\":\"5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'12000','1','11','15990.07426647','10','3','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(243,'meetings',6,'variable_expenses_as_percentage_from_rooms_revenues','Event Direct Costs (decor, AV, extra staff)','{\"2\":\"18\",\"3\":\"18\",\"4\":\"18\",\"5\":\"18\",\"6\":\"18\",\"7\":\"18\",\"8\":\"18\",\"9\":\"18\",\"10\":\"18\",\"11\":\"18\",\"12\":\"18\",\"13\":\"18\",\"14\":\"18\",\"15\":\"18\",\"16\":\"18\",\"17\":\"18\",\"18\":\"18\",\"19\":\"18\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(244,'meetings',6,'variable_expenses_as_percentage_from_rooms_revenues','Meeting Supplies & Equipment Rental','{\"2\":\"10\",\"3\":\"10\",\"4\":\"10\",\"5\":\"10\",\"6\":\"10\",\"7\":\"10\",\"8\":\"10\",\"9\":\"10\",\"10\":\"10\",\"11\":\"10\",\"12\":\"10\",\"13\":\"10\",\"14\":\"10\",\"15\":\"10\",\"16\":\"10\",\"17\":\"10\",\"18\":\"10\",\"19\":\"10\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(245,'meetings',6,'variable_expenses_as_cost_per_guest','Event Refreshments & Materials',NULL,NULL,'40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','53.30024755',NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(246,'other',6,'variable_expenses_as_percentage_from_rooms_revenues','Spa & Retail Product Costs','{\"2\":\"12\",\"3\":\"12\",\"4\":\"12\",\"5\":\"12\",\"6\":\"12\",\"7\":\"12\",\"8\":\"12\",\"9\":\"12\",\"10\":\"12\",\"11\":\"12\",\"12\":\"12\",\"13\":\"12\",\"14\":\"12\",\"15\":\"12\",\"16\":\"12\",\"17\":\"12\",\"18\":\"12\",\"19\":\"12\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(247,'other',6,'variable_expenses_as_percentage_from_rooms_revenues','Activity & Equipment Maintenance','{\"2\":\"4\",\"3\":\"4\",\"4\":\"4\",\"5\":\"4\",\"6\":\"4\",\"7\":\"4\",\"8\":\"4\",\"9\":\"4\",\"10\":\"4\",\"11\":\"4\",\"12\":\"4\",\"13\":\"4\",\"14\":\"4\",\"15\":\"4\",\"16\":\"4\",\"17\":\"4\",\"18\":\"4\",\"19\":\"4\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(248,'other',6,'variable_expenses_as_cost_per_guest','Spa Consumables',NULL,NULL,'25',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','33.31265472',NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(249,'other',6,'rooms_manpower_expense','Spa & Wellness Manager',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'65000','1','11','86612.90227671','10','20','3',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(250,'other',6,'rooms_manpower_expense','Therapists & Fitness Trainers',NULL,'{\"33\":\"26\",\"34\":\"26\",\"35\":\"26\",\"36\":\"26\",\"37\":\"26\",\"38\":\"26\",\"39\":\"26\",\"40\":\"26\",\"41\":\"26\",\"42\":\"26\",\"43\":\"26\",\"44\":\"26\",\"45\":\"30\",\"46\":\"30\",\"47\":\"30\",\"48\":\"30\",\"49\":\"30\",\"50\":\"30\",\"51\":\"30\",\"52\":\"30\",\"53\":\"30\",\"54\":\"30\",\"55\":\"30\",\"56\":\"30\",\"57\":\"30\",\"58\":\"30\",\"59\":\"30\",\"60\":\"30\",\"61\":\"30\",\"62\":\"30\",\"63\":\"30\",\"64\":\"30\",\"65\":\"30\",\"66\":\"30\",\"67\":\"30\",\"68\":\"30\",\"69\":\"30\",\"70\":\"30\",\"71\":\"30\",\"72\":\"30\",\"73\":\"30\",\"74\":\"30\",\"75\":\"30\",\"76\":\"30\",\"77\":\"30\",\"78\":\"30\",\"79\":\"30\",\"80\":\"30\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'12500','1','11','16656.3273609','10','3','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(251,'other',6,'rooms_manpower_expense','Dive Centre Instructors',NULL,'{\"33\":\"5\",\"34\":\"5\",\"35\":\"5\",\"36\":\"5\",\"37\":\"5\",\"38\":\"5\",\"39\":\"5\",\"40\":\"5\",\"41\":\"5\",\"42\":\"5\",\"43\":\"5\",\"44\":\"5\",\"45\":\"6\",\"46\":\"6\",\"47\":\"6\",\"48\":\"6\",\"49\":\"6\",\"50\":\"6\",\"51\":\"6\",\"52\":\"6\",\"53\":\"6\",\"54\":\"6\",\"55\":\"6\",\"56\":\"6\",\"57\":\"6\",\"58\":\"6\",\"59\":\"6\",\"60\":\"6\",\"61\":\"6\",\"62\":\"6\",\"63\":\"6\",\"64\":\"6\",\"65\":\"6\",\"66\":\"6\",\"67\":\"6\",\"68\":\"6\",\"69\":\"6\",\"70\":\"6\",\"71\":\"6\",\"72\":\"6\",\"73\":\"6\",\"74\":\"6\",\"75\":\"6\",\"76\":\"6\",\"77\":\"6\",\"78\":\"6\",\"79\":\"6\",\"80\":\"6\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'16000','1','11','21320.09902196','10','5','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(252,'other',6,'rooms_manpower_expense','Recreation & Kids Club Team',NULL,'{\"33\":\"14\",\"34\":\"14\",\"35\":\"14\",\"36\":\"14\",\"37\":\"14\",\"38\":\"14\",\"39\":\"14\",\"40\":\"14\",\"41\":\"14\",\"42\":\"14\",\"43\":\"14\",\"44\":\"14\",\"45\":\"16\",\"46\":\"16\",\"47\":\"16\",\"48\":\"16\",\"49\":\"16\",\"50\":\"16\",\"51\":\"16\",\"52\":\"16\",\"53\":\"16\",\"54\":\"16\",\"55\":\"16\",\"56\":\"16\",\"57\":\"16\",\"58\":\"16\",\"59\":\"16\",\"60\":\"16\",\"61\":\"16\",\"62\":\"16\",\"63\":\"16\",\"64\":\"16\",\"65\":\"16\",\"66\":\"16\",\"67\":\"16\",\"68\":\"16\",\"69\":\"16\",\"70\":\"16\",\"71\":\"16\",\"72\":\"16\",\"73\":\"16\",\"74\":\"16\",\"75\":\"16\",\"76\":\"16\",\"77\":\"16\",\"78\":\"16\",\"79\":\"16\",\"80\":\"16\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9500','1','11','12658.80879429','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(253,'general',6,'variable_expenses_as_percentage_from_total_revenues','Professional Fees, Audit & Legal','{\"2\":\"1.2\",\"3\":\"1.2\",\"4\":\"1.2\",\"5\":\"1.2\",\"6\":\"1.2\",\"7\":\"1.2\",\"8\":\"1.2\",\"9\":\"1.2\",\"10\":\"1.2\",\"11\":\"1.2\",\"12\":\"1.2\",\"13\":\"1.2\",\"14\":\"1.2\",\"15\":\"1.2\",\"16\":\"1.2\",\"17\":\"1.2\",\"18\":\"1.2\",\"19\":\"1.2\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(254,'general',6,'fixed_monthly_expenses','IT, Licences & Software',NULL,NULL,'1800000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','2398511.1399703',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(255,'general',6,'fixed_monthly_expenses','General Insurance & Miscellaneous',NULL,NULL,'450000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','599627.78499258',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(256,'general',6,'fixed_monthly_expenses','Staff Meals, Transport & Uniforms',NULL,NULL,'2700000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','3597766.70995546',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(257,'general',6,'rooms_manpower_expense','General Manager',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'260000','1','11','346451.60910682','10','25','2',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(258,'general',6,'rooms_manpower_expense','Finance & Control Team',NULL,'{\"33\":\"8\",\"34\":\"8\",\"35\":\"8\",\"36\":\"8\",\"37\":\"8\",\"38\":\"8\",\"39\":\"8\",\"40\":\"8\",\"41\":\"8\",\"42\":\"8\",\"43\":\"8\",\"44\":\"8\",\"45\":\"10\",\"46\":\"10\",\"47\":\"10\",\"48\":\"10\",\"49\":\"10\",\"50\":\"10\",\"51\":\"10\",\"52\":\"10\",\"53\":\"10\",\"54\":\"10\",\"55\":\"10\",\"56\":\"10\",\"57\":\"10\",\"58\":\"10\",\"59\":\"10\",\"60\":\"10\",\"61\":\"10\",\"62\":\"10\",\"63\":\"10\",\"64\":\"10\",\"65\":\"10\",\"66\":\"10\",\"67\":\"10\",\"68\":\"10\",\"69\":\"10\",\"70\":\"10\",\"71\":\"10\",\"72\":\"10\",\"73\":\"10\",\"74\":\"10\",\"75\":\"10\",\"76\":\"10\",\"77\":\"10\",\"78\":\"10\",\"79\":\"10\",\"80\":\"10\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'24000','1','11','31980.14853294','10','8','10',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(259,'general',6,'rooms_manpower_expense','HR & Training Team',NULL,'{\"33\":\"3\",\"34\":\"3\",\"35\":\"3\",\"36\":\"3\",\"37\":\"3\",\"38\":\"3\",\"39\":\"3\",\"40\":\"3\",\"41\":\"3\",\"42\":\"3\",\"43\":\"3\",\"44\":\"3\",\"45\":\"4\",\"46\":\"4\",\"47\":\"4\",\"48\":\"4\",\"49\":\"4\",\"50\":\"4\",\"51\":\"4\",\"52\":\"4\",\"53\":\"4\",\"54\":\"4\",\"55\":\"4\",\"56\":\"4\",\"57\":\"4\",\"58\":\"4\",\"59\":\"4\",\"60\":\"4\",\"61\":\"4\",\"62\":\"4\",\"63\":\"4\",\"64\":\"4\",\"65\":\"4\",\"66\":\"4\",\"67\":\"4\",\"68\":\"4\",\"69\":\"4\",\"70\":\"4\",\"71\":\"4\",\"72\":\"4\",\"73\":\"4\",\"74\":\"4\",\"75\":\"4\",\"76\":\"4\",\"77\":\"4\",\"78\":\"4\",\"79\":\"4\",\"80\":\"4\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'19000','1','11','25317.61758858','10','6','10',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(260,'general',6,'rooms_manpower_expense','IT & Admin Staff',NULL,'{\"33\":\"4\",\"34\":\"4\",\"35\":\"4\",\"36\":\"4\",\"37\":\"4\",\"38\":\"4\",\"39\":\"4\",\"40\":\"4\",\"41\":\"4\",\"42\":\"4\",\"43\":\"4\",\"44\":\"4\",\"45\":\"5\",\"46\":\"5\",\"47\":\"5\",\"48\":\"5\",\"49\":\"5\",\"50\":\"5\",\"51\":\"5\",\"52\":\"5\",\"53\":\"5\",\"54\":\"5\",\"55\":\"5\",\"56\":\"5\",\"57\":\"5\",\"58\":\"5\",\"59\":\"5\",\"60\":\"5\",\"61\":\"5\",\"62\":\"5\",\"63\":\"5\",\"64\":\"5\",\"65\":\"5\",\"66\":\"5\",\"67\":\"5\",\"68\":\"5\",\"69\":\"5\",\"70\":\"5\",\"71\":\"5\",\"72\":\"5\",\"73\":\"5\",\"74\":\"5\",\"75\":\"5\",\"76\":\"5\",\"77\":\"5\",\"78\":\"5\",\"79\":\"5\",\"80\":\"5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'15000','1','11','19987.59283309','10','4','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(261,'general',6,'rooms_manpower_expense','Purchasing & Stores',NULL,'{\"33\":\"4\",\"34\":\"4\",\"35\":\"4\",\"36\":\"4\",\"37\":\"4\",\"38\":\"4\",\"39\":\"4\",\"40\":\"4\",\"41\":\"4\",\"42\":\"4\",\"43\":\"4\",\"44\":\"4\",\"45\":\"5\",\"46\":\"5\",\"47\":\"5\",\"48\":\"5\",\"49\":\"5\",\"50\":\"5\",\"51\":\"5\",\"52\":\"5\",\"53\":\"5\",\"54\":\"5\",\"55\":\"5\",\"56\":\"5\",\"57\":\"5\",\"58\":\"5\",\"59\":\"5\",\"60\":\"5\",\"61\":\"5\",\"62\":\"5\",\"63\":\"5\",\"64\":\"5\",\"65\":\"5\",\"66\":\"5\",\"67\":\"5\",\"68\":\"5\",\"69\":\"5\",\"70\":\"5\",\"71\":\"5\",\"72\":\"5\",\"73\":\"5\",\"74\":\"5\",\"75\":\"5\",\"76\":\"5\",\"77\":\"5\",\"78\":\"5\",\"79\":\"5\",\"80\":\"5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'13000','1','11','17322.58045534','10','3','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(262,'general',6,'rooms_manpower_expense','Security Team',NULL,'{\"33\":\"19\",\"34\":\"19\",\"35\":\"19\",\"36\":\"19\",\"37\":\"19\",\"38\":\"19\",\"39\":\"19\",\"40\":\"19\",\"41\":\"19\",\"42\":\"19\",\"43\":\"19\",\"44\":\"19\",\"45\":\"22\",\"46\":\"22\",\"47\":\"22\",\"48\":\"22\",\"49\":\"22\",\"50\":\"22\",\"51\":\"22\",\"52\":\"22\",\"53\":\"22\",\"54\":\"22\",\"55\":\"22\",\"56\":\"22\",\"57\":\"22\",\"58\":\"22\",\"59\":\"22\",\"60\":\"22\",\"61\":\"22\",\"62\":\"22\",\"63\":\"22\",\"64\":\"22\",\"65\":\"22\",\"66\":\"22\",\"67\":\"22\",\"68\":\"22\",\"69\":\"22\",\"70\":\"22\",\"71\":\"22\",\"72\":\"22\",\"73\":\"22\",\"74\":\"22\",\"75\":\"22\",\"76\":\"22\",\"77\":\"22\",\"78\":\"22\",\"79\":\"22\",\"80\":\"22\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9500','1','11','12658.80879429','10','2','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(263,'marketing',6,'variable_expenses_as_percentage_from_total_revenues','Advertising & Promotion','{\"2\":\"2\",\"3\":\"2\",\"4\":\"2\",\"5\":\"2\",\"6\":\"2\",\"7\":\"2\",\"8\":\"2\",\"9\":\"2\",\"10\":\"2\",\"11\":\"2\",\"12\":\"2\",\"13\":\"2\",\"14\":\"2\",\"15\":\"2\",\"16\":\"2\",\"17\":\"2\",\"18\":\"2\",\"19\":\"2\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(264,'marketing',6,'fixed_monthly_expenses','Marketing Programme & Tour-Operator Support',NULL,NULL,'2600000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','3464516.09106822',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(265,'marketing',6,'rooms_manpower_expense','Director of Sales & Marketing',NULL,'{\"33\":\"1\",\"34\":\"1\",\"35\":\"1\",\"36\":\"1\",\"37\":\"1\",\"38\":\"1\",\"39\":\"1\",\"40\":\"1\",\"41\":\"1\",\"42\":\"1\",\"43\":\"1\",\"44\":\"1\",\"45\":\"1\",\"46\":\"1\",\"47\":\"1\",\"48\":\"1\",\"49\":\"1\",\"50\":\"1\",\"51\":\"1\",\"52\":\"1\",\"53\":\"1\",\"54\":\"1\",\"55\":\"1\",\"56\":\"1\",\"57\":\"1\",\"58\":\"1\",\"59\":\"1\",\"60\":\"1\",\"61\":\"1\",\"62\":\"1\",\"63\":\"1\",\"64\":\"1\",\"65\":\"1\",\"66\":\"1\",\"67\":\"1\",\"68\":\"1\",\"69\":\"1\",\"70\":\"1\",\"71\":\"1\",\"72\":\"1\",\"73\":\"1\",\"74\":\"1\",\"75\":\"1\",\"76\":\"1\",\"77\":\"1\",\"78\":\"1\",\"79\":\"1\",\"80\":\"1\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'110000','1','11','146575.68077596','10','23','3',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(266,'marketing',6,'rooms_manpower_expense','Sales Managers',NULL,'{\"33\":\"3\",\"34\":\"3\",\"35\":\"3\",\"36\":\"3\",\"37\":\"3\",\"38\":\"3\",\"39\":\"3\",\"40\":\"3\",\"41\":\"3\",\"42\":\"3\",\"43\":\"3\",\"44\":\"3\",\"45\":\"3\",\"46\":\"3\",\"47\":\"3\",\"48\":\"3\",\"49\":\"3\",\"50\":\"3\",\"51\":\"3\",\"52\":\"3\",\"53\":\"3\",\"54\":\"3\",\"55\":\"3\",\"56\":\"3\",\"57\":\"3\",\"58\":\"3\",\"59\":\"3\",\"60\":\"3\",\"61\":\"3\",\"62\":\"3\",\"63\":\"3\",\"64\":\"3\",\"65\":\"3\",\"66\":\"3\",\"67\":\"3\",\"68\":\"3\",\"69\":\"3\",\"70\":\"3\",\"71\":\"3\",\"72\":\"3\",\"73\":\"3\",\"74\":\"3\",\"75\":\"3\",\"76\":\"3\",\"77\":\"3\",\"78\":\"3\",\"79\":\"3\",\"80\":\"3\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'38000','1','11','50635.23517715','10','14','6',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(267,'marketing',6,'rooms_manpower_expense','Reservations & Revenue Team',NULL,'{\"33\":\"4\",\"34\":\"4\",\"35\":\"4\",\"36\":\"4\",\"37\":\"4\",\"38\":\"4\",\"39\":\"4\",\"40\":\"4\",\"41\":\"4\",\"42\":\"4\",\"43\":\"4\",\"44\":\"4\",\"45\":\"5\",\"46\":\"5\",\"47\":\"5\",\"48\":\"5\",\"49\":\"5\",\"50\":\"5\",\"51\":\"5\",\"52\":\"5\",\"53\":\"5\",\"54\":\"5\",\"55\":\"5\",\"56\":\"5\",\"57\":\"5\",\"58\":\"5\",\"59\":\"5\",\"60\":\"5\",\"61\":\"5\",\"62\":\"5\",\"63\":\"5\",\"64\":\"5\",\"65\":\"5\",\"66\":\"5\",\"67\":\"5\",\"68\":\"5\",\"69\":\"5\",\"70\":\"5\",\"71\":\"5\",\"72\":\"5\",\"73\":\"5\",\"74\":\"5\",\"75\":\"5\",\"76\":\"5\",\"77\":\"5\",\"78\":\"5\",\"79\":\"5\",\"80\":\"5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'16000','1','11','21320.09902196','10','5','11',NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(268,'property',6,'variable_expenses_as_percentage_from_rooms_revenues','Repairs & Maintenance','{\"2\":\"0.6\",\"3\":\"0.6\",\"4\":\"0.6\",\"5\":\"0.6\",\"6\":\"0.6\",\"7\":\"0.6\",\"8\":\"0.6\",\"9\":\"0.6\",\"10\":\"0.6\",\"11\":\"0.6\",\"12\":\"0.6\",\"13\":\"0.6\",\"14\":\"0.6\",\"15\":\"0.6\",\"16\":\"0.6\",\"17\":\"0.6\",\"18\":\"0.6\",\"19\":\"0.6\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(269,'property',6,'fixed_monthly_expenses','Maintenance Contracts & Spare Parts',NULL,NULL,'1800000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','2398511.1399703',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(270,'property',6,'fixed_monthly_expenses','Engineering, Gardening & Pool Team (payroll)',NULL,NULL,'800000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'10','1066004.95109791',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(271,'property',6,'other_property_fixed_expenses','Property Taxes',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'5',NULL,NULL,NULL,NULL,NULL,'03','0.3','0','0'),(272,'property',6,'other_property_fixed_expenses','Property Insurance',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'5',NULL,NULL,NULL,NULL,NULL,NULL,'0.25','0','0'),(273,'energy',6,'variable_expenses_as_percentage_from_rooms_revenues','Electricity, Water & Fuel (variable)','{\"2\":\"3.5\",\"3\":\"3.5\",\"4\":\"3.5\",\"5\":\"3.5\",\"6\":\"3.5\",\"7\":\"3.5\",\"8\":\"3.5\",\"9\":\"3.5\",\"10\":\"3.5\",\"11\":\"3.5\",\"12\":\"3.5\",\"13\":\"3.5\",\"14\":\"3.5\",\"15\":\"3.5\",\"16\":\"3.5\",\"17\":\"3.5\",\"18\":\"3.5\",\"19\":\"3.5\"}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(274,'energy',6,'fixed_monthly_expenses','Desalination, Gas & Generator Fuel (base load)',NULL,NULL,'2400000',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'11','1',2,NULL,'2026-10-29 08:10:29','2026-10-29 08:10:29',NULL,'9','3198014.85329374',NULL,NULL,NULL,'monthly',NULL,NULL,'0','0'),(275,'start',6,'start_up_cost','Brand & Operator Technical Services Fee',NULL,NULL,NULL,NULL,NULL,NULL,'50','50','30',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'20000000','24'),(276,'start',6,'start_up_cost','Recruitment & Training of Opening Team',NULL,NULL,NULL,NULL,NULL,NULL,'100','0','0',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'30000000','29'),(277,'start',6,'start_up_cost','Pre-opening Marketing & Launch',NULL,NULL,NULL,NULL,NULL,NULL,'60','40','60',NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'45000000','30'),(278,'start',6,'pre_operating_expense','Pre-opening Payroll - Management Team',NULL,'[\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"2500000\",\"2500000\",\"2500000\",\"2500000\",\"2500000\",\"2500000\",\"2500000\",\"4000000\",\"4000000\",\"4000000\",\"4000000\",\"4000000\",\"4000000\"]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(279,'start',6,'pre_operating_expense','Pre-opening Office & Administration',NULL,'[\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\",\"1000000\"]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0'),(280,'start',6,'pre_operating_expense','Pre-opening Sales & Travel',NULL,'[\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"0\",\"1500000\",\"1500000\",\"1500000\",\"1500000\",\"1500000\",\"1500000\",\"1500000\",\"1500000\",\"1500000\"]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0');
/*!40000 ALTER TABLE `department_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ffe`
--

DROP TABLE IF EXISTS `ffe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ffe` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `execution_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `down_payment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_rate_one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_rate_two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_one` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_two` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ffe_equity_funding` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ffe_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `ffe_company_id_foreign` (`company_id`),
  CONSTRAINT `ffe_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ffe_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ffe`
--

LOCK TABLES `ffe` WRITE;
/*!40000 ALTER TABLE `ffe` DISABLE KEYS */;
INSERT INTO `ffe` VALUES (4,6,2,'2026-10-29 08:10:30','2026-10-29 08:10:30','6','26','32','steady-growth','30','50','20','60','90','60');
/*!40000 ALTER TABLE `ffe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ffe_items`
--

DROP TABLE IF EXISTS `ffe_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ffe_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `ffe_id` bigint DEFAULT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depreciation_duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `item_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contingency_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `currency_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_cost_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ffe_items_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `ffe_items_company_id_foreign` (`company_id`),
  CONSTRAINT `ffe_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ffe_items_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ffe_items`
--

LOCK TABLES `ffe_items` WRITE;
/*!40000 ALTER TABLE `ffe_items` DISABLE KEYS */;
INSERT INTO `ffe_items` VALUES (31,6,4,'ffe_cost','ffe','Guest Room FF&E (300 rooms)','8','2026-10-29 08:10:30','2026-10-29 08:10:30','190000000','5',2,'1','60','5'),(32,6,4,'ffe_cost','ffe','Public Areas & Lobby','10','2026-10-29 08:10:30','2026-10-29 08:10:30','52000000','5',2,'1','40','5'),(33,6,4,'ffe_cost','ffe','Kitchens & F&B Equipment','10','2026-10-29 08:10:30','2026-10-29 08:10:30','62000000','5',2,'1','30','5'),(34,6,4,'ffe_cost','ffe','Pool, Beach & Landscape Furniture','8','2026-10-29 08:10:30','2026-10-29 08:10:30','33000000','5',2,'1','40','4'),(35,6,4,'ffe_cost','ffe','Spa, Gym & Dive Equipment','8','2026-10-29 08:10:30','2026-10-29 08:10:30','30000000','5',2,'1','40','5'),(36,6,4,'ffe_cost','ffe','IT, PMS & Telecom Systems','5','2026-10-29 08:10:30','2026-10-29 08:10:30','27000000','5',2,'1','50','4'),(37,6,4,'ffe_cost','ffe','Laundry Equipment','10','2026-10-29 08:10:30','2026-10-29 08:10:30','17000000','5',2,'1','30','5'),(38,6,4,'ffe_cost','ffe','Engineering & Vehicles','10','2026-10-29 08:10:30','2026-10-29 08:10:30','21000000','5',2,'1','30','5'),(39,6,4,'ffe_cost','ffe','Operating Supplies & Equipment (OS&E)','3','2026-10-29 08:10:30','2026-10-29 08:10:30','36000000','5',2,'1','40','3'),(40,6,4,'ffe_cost','ffe','Gaming Equipment','7','2026-10-29 08:10:30','2026-10-29 08:10:30','220000','5',2,'2','40','5');
/*!40000 ALTER TABLE `ffe_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_names`
--

DROP TABLE IF EXISTS `food_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `food_names` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `food_names_company_id_foreign` (`company_id`),
  CONSTRAINT `food_names_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_names`
--

LOCK TABLES `food_names` WRITE;
/*!40000 ALTER TABLE `food_names` DISABLE KEYS */;
INSERT INTO `food_names` VALUES (1,'Total F&B Facility',1,1,NULL,NULL),(2,'Restaurant',1,1,NULL,NULL),(3,'Lobby Lounge & Bar',1,1,NULL,NULL),(4,'Café',1,1,NULL,NULL),(5,'Total F&B Facility',1,2,NULL,NULL),(6,'Restaurant',1,2,NULL,'2026-10-29 06:10:27'),(7,'Lobby Lounge & Bar',1,2,NULL,'2026-10-29 06:10:27'),(8,'Café',1,2,NULL,'2026-10-29 06:10:27'),(9,'All-Day Dining Restaurant',1,2,'2026-10-29 05:46:28','2026-10-29 06:10:27'),(10,'Specialty Restaurant',1,2,'2026-10-29 05:46:28','2026-10-29 06:10:27'),(11,'Pool & Beach Bar',1,2,'2026-10-29 05:46:28','2026-10-29 06:10:27');
/*!40000 ALTER TABLE `food_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foods`
--

DROP TABLE IF EXISTS `foods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `foods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `food_count` bigint DEFAULT NULL,
  `food_cover` bigint DEFAULT NULL,
  `total_daily_cover_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `f&b_facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `estimation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chosen_food_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_value_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_value_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_value_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_capture_cover_percentage` longtext COLLATE utf8mb4_unicode_ci,
  `cover_per_day` longtext COLLATE utf8mb4_unicode_ci,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meal_per_guest` longtext COLLATE utf8mb4_unicode_ci,
  `percentage_from_rooms_revenues` longtext COLLATE utf8mb4_unicode_ci,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `food_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `foods_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `foods_food_type_id_foreign` (`food_type_id`),
  CONSTRAINT `foods_food_type_id_foreign` FOREIGN KEY (`food_type_id`) REFERENCES `food_names` (`id`),
  CONSTRAINT `foods_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foods`
--

LOCK TABLES `foods` WRITE;
/*!40000 ALTER TABLE `foods` DISABLE KEYS */;
INSERT INTO `foods` VALUES (1,2,100,'0',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-10-28 22:31:09','2026-10-28 23:31:09',NULL,NULL,NULL,'',NULL,2),(2,2,50,'0',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-10-28 22:31:09','2026-10-28 23:31:09',NULL,NULL,NULL,'',NULL,4),(20,1,350,'0','guest_capture_x_meals_per_guest_x_cover_value_per_meal_method','450',NULL,'1','11','599.62778499','9','{\"2\":\"70\",\"3\":\"78\",\"4\":\"82\",\"5\":\"85\",\"6\":\"85\",\"7\":\"85\",\"8\":\"85\",\"9\":\"85\",\"10\":\"85\",\"11\":\"85\",\"12\":\"85\",\"13\":\"85\",\"14\":\"85\",\"15\":\"85\",\"16\":\"85\",\"17\":\"85\",\"18\":\"85\",\"19\":\"85\"}',NULL,6,'2026-10-29 05:10:27','2026-10-29 06:10:27','{\"2\":\"1.2\",\"3\":\"1.2\",\"4\":\"1.2\",\"5\":\"1.2\",\"6\":\"1.2\",\"7\":\"1.2\",\"8\":\"1.2\",\"9\":\"1.2\",\"10\":\"1.2\",\"11\":\"1.2\",\"12\":\"1.2\",\"13\":\"1.2\",\"14\":\"1.2\",\"15\":\"1.2\",\"16\":\"1.2\",\"17\":\"1.2\",\"18\":\"1.2\",\"19\":\"1.2\"}',NULL,'system_default','','monthly',9),(21,3,80,'0','guest_capture_x_cover_value_per_guest_method','1200',NULL,'1','11','1599.00742665','9','{\"2\":\"22\",\"3\":\"22\",\"4\":\"22\",\"5\":\"22\",\"6\":\"22\",\"7\":\"22\",\"8\":\"22\",\"9\":\"22\",\"10\":\"22\",\"11\":\"22\",\"12\":\"22\",\"13\":\"22\",\"14\":\"22\",\"15\":\"22\",\"16\":\"22\",\"17\":\"22\",\"18\":\"22\",\"19\":\"22\"}',NULL,6,'2026-10-29 05:10:27','2026-10-29 06:10:27',NULL,NULL,'system_default','','monthly',10),(22,1,120,'0','cover_count_target_per_day_x_cover_value_method','420',NULL,'1','11','559.65259933','9',NULL,'{\"2\":\"100\",\"3\":\"130\",\"4\":\"150\",\"5\":\"160\",\"6\":\"160\",\"7\":\"160\",\"8\":\"160\",\"9\":\"160\",\"10\":\"160\",\"11\":\"160\",\"12\":\"160\",\"13\":\"160\",\"14\":\"160\",\"15\":\"160\",\"16\":\"160\",\"17\":\"160\",\"18\":\"160\",\"19\":\"160\"}',6,'2026-10-29 05:10:27','2026-10-29 06:10:27',NULL,NULL,'system_default','','monthly',7),(23,1,60,'0','percentage_from_rooms_revenue','0',NULL,'1','0','0','0',NULL,NULL,6,'2026-10-29 05:10:27','2026-10-29 06:10:27',NULL,'{\"2\":\"3\",\"3\":\"3\",\"4\":\"3\",\"5\":\"3\",\"6\":\"3\",\"7\":\"3\",\"8\":\"3\",\"9\":\"3\",\"10\":\"3\",\"11\":\"3\",\"12\":\"3\",\"13\":\"3\",\"14\":\"3\",\"15\":\"3\",\"16\":\"3\",\"17\":\"3\",\"18\":\"3\",\"19\":\"3\"}','system_default','','monthly',8),(24,2,80,'0','guest_capture_x_cover_value_per_guest_method','280',NULL,'1','11','373.10173288','9','{\"2\":\"55\",\"3\":\"55\",\"4\":\"55\",\"5\":\"55\",\"6\":\"55\",\"7\":\"55\",\"8\":\"55\",\"9\":\"55\",\"10\":\"55\",\"11\":\"55\",\"12\":\"55\",\"13\":\"55\",\"14\":\"55\",\"15\":\"55\",\"16\":\"55\",\"17\":\"55\",\"18\":\"55\",\"19\":\"55\"}',NULL,6,'2026-10-29 05:10:27','2026-10-29 06:10:27',NULL,NULL,'system_default','','monthly',11);
/*!40000 ALTER TABLE `foods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospitality_sector_shares`
--

DROP TABLE IF EXISTS `hospitality_sector_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitality_sector_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hospitality_sector_shares_hospitality_sector_id_user_id_unique` (`hospitality_sector_id`,`user_id`),
  KEY `hospitality_sector_shares_user_id_foreign` (`user_id`),
  CONSTRAINT `hospitality_sector_shares_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hospitality_sector_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospitality_sector_shares`
--

LOCK TABLES `hospitality_sector_shares` WRITE;
/*!40000 ALTER TABLE `hospitality_sector_shares` DISABLE KEYS */;
/*!40000 ALTER TABLE `hospitality_sector_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospitality_sectors`
--

DROP TABLE IF EXISTS `hospitality_sectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitality_sectors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `study_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operation_dates` longtext COLLATE utf8mb4_unicode_ci,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `study_dates` longtext COLLATE utf8mb4_unicode_ci,
  `operation_start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `star_rating` tinyint DEFAULT NULL,
  `country_id` int DEFAULT NULL,
  `property_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `duration_in_years` tinyint DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'IncomeStatement',
  `duration_type` enum('monthly','annually','semi-annually','quarterly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `study_start_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `study_end_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `development_start_month` bigint unsigned NOT NULL,
  `development_start_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `development_duration` bigint unsigned NOT NULL,
  `operation_start_month` bigint unsigned NOT NULL,
  `additional_currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exchange_rate` decimal(10,2) NOT NULL,
  `corporate_taxes_rate` decimal(10,2) NOT NULL,
  `investment_return_rate` decimal(10,2) NOT NULL,
  `perpetual_growth_rate` decimal(10,2) NOT NULL,
  `creator_id` bigint unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `casino_cover` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `main_functional_currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `financial_year_start_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_f&b_facility_count` int DEFAULT NULL,
  `total_f&b_cover_count` int DEFAULT NULL,
  `total_casino_facility_count` int DEFAULT NULL,
  `total_casino_cover_count` int DEFAULT NULL,
  `total_meeting_cover_count` int DEFAULT NULL,
  `total_meeting_facility_count` int DEFAULT NULL,
  `rooms_count` int DEFAULT NULL,
  `average_guest_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_total_rooms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `is_total_foods` tinyint DEFAULT '0',
  `is_total_casinos` tinyint DEFAULT '0',
  `has_casino_section` tinyint DEFAULT '0',
  `has_meeting_section` tinyint DEFAULT '0',
  `has_other_section` tinyint DEFAULT '0',
  `is_total_other` tinyint DEFAULT '0',
  `is_total_meetings` tinyint DEFAULT '0',
  `has_sales_channels` tinyint DEFAULT '0',
  `selected_sales_revenues` longtext COLLATE utf8mb4_unicode_ci,
  `general_occupancy_rate` longtext COLLATE utf8mb4_unicode_ci,
  `room_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seasonality_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seasonality_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `general_seasonality` longtext COLLATE utf8mb4_unicode_ci,
  `add_sales_channels_share_discount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `study_navigators` longtext COLLATE utf8mb4_unicode_ci,
  `guest_meeting_seasonality_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_meeting_general_seasonality` longtext COLLATE utf8mb4_unicode_ci,
  `guest_meeting_seasonality_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rent_meeting_seasonality_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rent_meeting_general_seasonality` longtext COLLATE utf8mb4_unicode_ci,
  `rent_meeting_seasonality_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_rooms_manpower` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `has_foods_manpower` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `has_casinos_manpower` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `has_sales_and_general_manpower` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `has_other_revenue_manpower` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `other_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `casino_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `food_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rooms_general_collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rooms_general_collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rooms_general_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `others_general_collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `others_general_collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `others_general_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meetings_general_collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meetings_general_collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meetings_general_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foods_general_collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foods_general_collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foods_general_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `casinos_general_collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `casinos_general_collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `casinos_general_collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_visit_room_section` tinyint NOT NULL DEFAULT '0',
  `has_visit_food_section` tinyint NOT NULL DEFAULT '0',
  `has_visit_casino_section` tinyint NOT NULL DEFAULT '0',
  `has_visit_meeting_section` tinyint NOT NULL DEFAULT '0',
  `has_visit_other_section` tinyint NOT NULL DEFAULT '0',
  `exchange_rates` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `hospitality_sectors_company_id_foreign` (`company_id`),
  KEY `hospitality_sectors_creator_id_foreign` (`creator_id`),
  CONSTRAINT `hospitality_sectors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `hospitality_sectors_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospitality_sectors`
--

LOCK TABLES `hospitality_sectors` WRITE;
/*!40000 ALTER TABLE `hospitality_sectors` DISABLE KEYS */;
INSERT INTO `hospitality_sectors` VALUES (1,'El Sokhna Hotel BP','El Sokhna Hotel','{\"24\":\"01-01-2029\",\"25\":\"01-02-2029\",\"26\":\"01-03-2029\",\"27\":\"01-04-2029\",\"28\":\"01-05-2029\",\"29\":\"01-06-2029\",\"30\":\"01-07-2029\",\"31\":\"01-08-2029\",\"32\":\"01-09-2029\",\"33\":\"01-10-2029\",\"34\":\"01-11-2029\",\"35\":\"01-12-2029\",\"36\":\"01-01-2030\",\"37\":\"01-02-2030\",\"38\":\"01-03-2030\",\"39\":\"01-04-2030\",\"40\":\"01-05-2030\",\"41\":\"01-06-2030\",\"42\":\"01-07-2030\",\"43\":\"01-08-2030\",\"44\":\"01-09-2030\",\"45\":\"01-10-2030\",\"46\":\"01-11-2030\",\"47\":\"01-12-2030\",\"48\":\"01-01-2031\",\"49\":\"01-02-2031\",\"50\":\"01-03-2031\",\"51\":\"01-04-2031\",\"52\":\"01-05-2031\",\"53\":\"01-06-2031\",\"54\":\"01-07-2031\",\"55\":\"01-08-2031\",\"56\":\"01-09-2031\",\"57\":\"01-10-2031\",\"58\":\"01-11-2031\",\"59\":\"01-12-2031\",\"60\":\"01-01-2032\",\"61\":\"01-02-2032\",\"62\":\"01-03-2032\",\"63\":\"01-04-2032\",\"64\":\"01-05-2032\",\"65\":\"01-06-2032\",\"66\":\"01-07-2032\",\"67\":\"01-08-2032\",\"68\":\"01-09-2032\",\"69\":\"01-10-2032\",\"70\":\"01-11-2032\",\"71\":\"01-12-2032\",\"72\":\"01-01-2033\",\"73\":\"01-02-2033\",\"74\":\"01-03-2033\",\"75\":\"01-04-2033\",\"76\":\"01-05-2033\",\"77\":\"01-06-2033\",\"78\":\"01-07-2033\",\"79\":\"01-08-2033\",\"80\":\"01-09-2033\",\"81\":\"01-10-2033\",\"82\":\"01-11-2033\",\"83\":\"01-12-2033\",\"84\":\"01-01-2034\",\"85\":\"01-02-2034\",\"86\":\"01-03-2034\",\"87\":\"01-04-2034\",\"88\":\"01-05-2034\",\"89\":\"01-06-2034\",\"90\":\"01-07-2034\",\"91\":\"01-08-2034\",\"92\":\"01-09-2034\",\"93\":\"01-10-2034\",\"94\":\"01-11-2034\",\"95\":\"01-12-2034\",\"96\":\"01-01-2035\",\"97\":\"01-02-2035\",\"98\":\"01-03-2035\",\"99\":\"01-04-2035\",\"100\":\"01-05-2035\",\"101\":\"01-06-2035\",\"102\":\"01-07-2035\",\"103\":\"01-08-2035\",\"104\":\"01-09-2035\",\"105\":\"01-10-2035\",\"106\":\"01-11-2035\",\"107\":\"01-12-2035\",\"108\":\"01-01-2036\",\"109\":\"01-02-2036\",\"110\":\"01-03-2036\",\"111\":\"01-04-2036\",\"112\":\"01-05-2036\",\"113\":\"01-06-2036\",\"114\":\"01-07-2036\",\"115\":\"01-08-2036\",\"116\":\"01-09-2036\",\"117\":\"01-10-2036\",\"118\":\"01-11-2036\",\"119\":\"01-12-2036\",\"120\":\"01-01-2037\",\"121\":\"01-02-2037\",\"122\":\"01-03-2037\",\"123\":\"01-04-2037\",\"124\":\"01-05-2037\",\"125\":\"01-06-2037\",\"126\":\"01-07-2037\",\"127\":\"01-08-2037\",\"128\":\"01-09-2037\",\"129\":\"01-10-2037\",\"130\":\"01-11-2037\",\"131\":\"01-12-2037\",\"132\":\"01-01-2038\",\"133\":\"01-02-2038\",\"134\":\"01-03-2038\",\"135\":\"01-04-2038\",\"136\":\"01-05-2038\",\"137\":\"01-06-2038\",\"138\":\"01-07-2038\",\"139\":\"01-08-2038\",\"140\":\"01-09-2038\",\"141\":\"01-10-2038\",\"142\":\"01-11-2038\",\"143\":\"01-12-2038\",\"144\":\"01-01-2039\",\"145\":\"01-02-2039\",\"146\":\"01-03-2039\",\"147\":\"01-04-2039\",\"148\":\"01-05-2039\",\"149\":\"01-06-2039\",\"150\":\"01-07-2039\",\"151\":\"01-08-2039\",\"152\":\"01-09-2039\",\"153\":\"01-10-2039\",\"154\":\"01-11-2039\",\"155\":\"01-12-2039\",\"156\":\"01-01-2040\",\"157\":\"01-02-2040\",\"158\":\"01-03-2040\",\"159\":\"01-04-2040\",\"160\":\"01-05-2040\",\"161\":\"01-06-2040\",\"162\":\"01-07-2040\",\"163\":\"01-08-2040\",\"164\":\"01-09-2040\",\"165\":\"01-10-2040\",\"166\":\"01-11-2040\",\"167\":\"01-12-2040\",\"168\":\"01-01-2041\",\"169\":\"01-02-2041\",\"170\":\"01-03-2041\",\"171\":\"01-04-2041\",\"172\":\"01-05-2041\",\"173\":\"01-06-2041\",\"174\":\"01-07-2041\",\"175\":\"01-08-2041\",\"176\":\"01-09-2041\",\"177\":\"01-10-2041\",\"178\":\"01-11-2041\",\"179\":\"01-12-2041\",\"180\":\"01-01-2042\",\"181\":\"01-02-2042\",\"182\":\"01-03-2042\",\"183\":\"01-04-2042\",\"184\":\"01-05-2042\",\"185\":\"01-06-2042\",\"186\":\"01-07-2042\",\"187\":\"01-08-2042\",\"188\":\"01-09-2042\",\"189\":\"01-10-2042\",\"190\":\"01-11-2042\",\"191\":\"01-12-2042\",\"192\":\"01-01-2043\",\"193\":\"01-02-2043\",\"194\":\"01-03-2043\",\"195\":\"01-04-2043\",\"196\":\"01-05-2043\",\"197\":\"01-06-2043\",\"198\":\"01-07-2043\",\"199\":\"01-08-2043\",\"200\":\"01-09-2043\",\"201\":\"01-10-2043\",\"202\":\"01-11-2043\",\"203\":\"01-12-2043\",\"204\":\"01-01-2044\",\"205\":\"01-02-2044\",\"206\":\"01-03-2044\",\"207\":\"01-04-2044\",\"208\":\"01-05-2044\",\"209\":\"01-06-2044\",\"210\":\"01-07-2044\",\"211\":\"01-08-2044\",\"212\":\"01-09-2044\",\"213\":\"01-10-2044\",\"214\":\"01-11-2044\",\"215\":\"01-12-2044\",\"216\":\"01-01-2045\",\"217\":\"01-02-2045\",\"218\":\"01-03-2045\",\"219\":\"01-04-2045\",\"220\":\"01-05-2045\",\"221\":\"01-06-2045\",\"222\":\"01-07-2045\",\"223\":\"01-08-2045\",\"224\":\"01-09-2045\",\"225\":\"01-10-2045\",\"226\":\"01-11-2045\",\"227\":\"01-12-2045\",\"228\":\"01-01-2046\",\"229\":\"01-02-2046\",\"230\":\"01-03-2046\",\"231\":\"01-04-2046\",\"232\":\"01-05-2046\",\"233\":\"01-06-2046\",\"234\":\"01-07-2046\",\"235\":\"01-08-2046\",\"236\":\"01-09-2046\",\"237\":\"01-10-2046\",\"238\":\"01-11-2046\",\"239\":\"01-12-2046\"}','AAA','[\"01-01-2027\",\"01-02-2027\",\"01-03-2027\",\"01-04-2027\",\"01-05-2027\",\"01-06-2027\",\"01-07-2027\",\"01-08-2027\",\"01-09-2027\",\"01-10-2027\",\"01-11-2027\",\"01-12-2027\",\"01-01-2028\",\"01-02-2028\",\"01-03-2028\",\"01-04-2028\",\"01-05-2028\",\"01-06-2028\",\"01-07-2028\",\"01-08-2028\",\"01-09-2028\",\"01-10-2028\",\"01-11-2028\",\"01-12-2028\",\"01-01-2029\",\"01-02-2029\",\"01-03-2029\",\"01-04-2029\",\"01-05-2029\",\"01-06-2029\",\"01-07-2029\",\"01-08-2029\",\"01-09-2029\",\"01-10-2029\",\"01-11-2029\",\"01-12-2029\",\"01-01-2030\",\"01-02-2030\",\"01-03-2030\",\"01-04-2030\",\"01-05-2030\",\"01-06-2030\",\"01-07-2030\",\"01-08-2030\",\"01-09-2030\",\"01-10-2030\",\"01-11-2030\",\"01-12-2030\",\"01-01-2031\",\"01-02-2031\",\"01-03-2031\",\"01-04-2031\",\"01-05-2031\",\"01-06-2031\",\"01-07-2031\",\"01-08-2031\",\"01-09-2031\",\"01-10-2031\",\"01-11-2031\",\"01-12-2031\",\"01-01-2032\",\"01-02-2032\",\"01-03-2032\",\"01-04-2032\",\"01-05-2032\",\"01-06-2032\",\"01-07-2032\",\"01-08-2032\",\"01-09-2032\",\"01-10-2032\",\"01-11-2032\",\"01-12-2032\",\"01-01-2033\",\"01-02-2033\",\"01-03-2033\",\"01-04-2033\",\"01-05-2033\",\"01-06-2033\",\"01-07-2033\",\"01-08-2033\",\"01-09-2033\",\"01-10-2033\",\"01-11-2033\",\"01-12-2033\",\"01-01-2034\",\"01-02-2034\",\"01-03-2034\",\"01-04-2034\",\"01-05-2034\",\"01-06-2034\",\"01-07-2034\",\"01-08-2034\",\"01-09-2034\",\"01-10-2034\",\"01-11-2034\",\"01-12-2034\",\"01-01-2035\",\"01-02-2035\",\"01-03-2035\",\"01-04-2035\",\"01-05-2035\",\"01-06-2035\",\"01-07-2035\",\"01-08-2035\",\"01-09-2035\",\"01-10-2035\",\"01-11-2035\",\"01-12-2035\",\"01-01-2036\",\"01-02-2036\",\"01-03-2036\",\"01-04-2036\",\"01-05-2036\",\"01-06-2036\",\"01-07-2036\",\"01-08-2036\",\"01-09-2036\",\"01-10-2036\",\"01-11-2036\",\"01-12-2036\",\"01-01-2037\",\"01-02-2037\",\"01-03-2037\",\"01-04-2037\",\"01-05-2037\",\"01-06-2037\",\"01-07-2037\",\"01-08-2037\",\"01-09-2037\",\"01-10-2037\",\"01-11-2037\",\"01-12-2037\",\"01-01-2038\",\"01-02-2038\",\"01-03-2038\",\"01-04-2038\",\"01-05-2038\",\"01-06-2038\",\"01-07-2038\",\"01-08-2038\",\"01-09-2038\",\"01-10-2038\",\"01-11-2038\",\"01-12-2038\",\"01-01-2039\",\"01-02-2039\",\"01-03-2039\",\"01-04-2039\",\"01-05-2039\",\"01-06-2039\",\"01-07-2039\",\"01-08-2039\",\"01-09-2039\",\"01-10-2039\",\"01-11-2039\",\"01-12-2039\",\"01-01-2040\",\"01-02-2040\",\"01-03-2040\",\"01-04-2040\",\"01-05-2040\",\"01-06-2040\",\"01-07-2040\",\"01-08-2040\",\"01-09-2040\",\"01-10-2040\",\"01-11-2040\",\"01-12-2040\",\"01-01-2041\",\"01-02-2041\",\"01-03-2041\",\"01-04-2041\",\"01-05-2041\",\"01-06-2041\",\"01-07-2041\",\"01-08-2041\",\"01-09-2041\",\"01-10-2041\",\"01-11-2041\",\"01-12-2041\",\"01-01-2042\",\"01-02-2042\",\"01-03-2042\",\"01-04-2042\",\"01-05-2042\",\"01-06-2042\",\"01-07-2042\",\"01-08-2042\",\"01-09-2042\",\"01-10-2042\",\"01-11-2042\",\"01-12-2042\",\"01-01-2043\",\"01-02-2043\",\"01-03-2043\",\"01-04-2043\",\"01-05-2043\",\"01-06-2043\",\"01-07-2043\",\"01-08-2043\",\"01-09-2043\",\"01-10-2043\",\"01-11-2043\",\"01-12-2043\",\"01-01-2044\",\"01-02-2044\",\"01-03-2044\",\"01-04-2044\",\"01-05-2044\",\"01-06-2044\",\"01-07-2044\",\"01-08-2044\",\"01-09-2044\",\"01-10-2044\",\"01-11-2044\",\"01-12-2044\",\"01-01-2045\",\"01-02-2045\",\"01-03-2045\",\"01-04-2045\",\"01-05-2045\",\"01-06-2045\",\"01-07-2045\",\"01-08-2045\",\"01-09-2045\",\"01-10-2045\",\"01-11-2045\",\"01-12-2045\",\"01-01-2046\",\"01-02-2046\",\"01-03-2046\",\"01-04-2046\",\"01-05-2046\",\"01-06-2046\",\"01-07-2046\",\"01-08-2046\",\"01-09-2046\",\"01-10-2046\",\"01-11-2046\",\"01-12-2046\"]','01/01/2029',5,1,'under_development',3,10,'IncomeStatement','monthly','01/01/2027','12/01/2036',3,'3',1,18,24,'2',50.00,22.50,35.00,5.00,2,'2026-10-28 23:31:09',NULL,'2026-10-28 22:41:03','1','january',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',0,0,1,1,1,0,0,0,NULL,'{\"2\":\"80\",\"3\":\"80\",\"4\":\"80\",\"5\":\"80\",\"6\":\"80\",\"7\":\"80\",\"8\":\"80\",\"9\":\"80\",\"10\":\"80\",\"11\":\"80\",\"12\":\"80\",\"13\":\"80\",\"14\":\"80\",\"15\":\"80\",\"16\":\"80\",\"17\":\"80\",\"18\":\"80\",\"19\":\"80\"}','general_collection_terms','flat-seasonality','general-seasonality','{\"01\":\"100\",\"02\":\"100\",\"03\":\"100\",\"04\":\"100\",\"05\":\"100\",\"06\":\"100\",\"07\":\"100\",\"08\":\"100\",\"09\":\"100\",\"10\":\"100\",\"11\":\"100\",\"12\":\"100\"}','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0','0','0','0',NULL,NULL,NULL,NULL,'monthly',NULL,'system_default',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,0,0,0,'{\"2\":\"50\",\"3\":\"50\",\"4\":\"50\",\"5\":\"50\",\"6\":\"50\",\"7\":\"50\",\"8\":\"50\",\"9\":\"50\"}'),(6,'Coral Horizon Resort & Spa - Feasibility Study (Base Case)','Coral Horizon Resort & Spa','{\"33\":\"01-10-2029\",\"34\":\"01-11-2029\",\"35\":\"01-12-2029\",\"36\":\"01-01-2030\",\"37\":\"01-02-2030\",\"38\":\"01-03-2030\",\"39\":\"01-04-2030\",\"40\":\"01-05-2030\",\"41\":\"01-06-2030\",\"42\":\"01-07-2030\",\"43\":\"01-08-2030\",\"44\":\"01-09-2030\",\"45\":\"01-10-2030\",\"46\":\"01-11-2030\",\"47\":\"01-12-2030\",\"48\":\"01-01-2031\",\"49\":\"01-02-2031\",\"50\":\"01-03-2031\",\"51\":\"01-04-2031\",\"52\":\"01-05-2031\",\"53\":\"01-06-2031\",\"54\":\"01-07-2031\",\"55\":\"01-08-2031\",\"56\":\"01-09-2031\",\"57\":\"01-10-2031\",\"58\":\"01-11-2031\",\"59\":\"01-12-2031\",\"60\":\"01-01-2032\",\"61\":\"01-02-2032\",\"62\":\"01-03-2032\",\"63\":\"01-04-2032\",\"64\":\"01-05-2032\",\"65\":\"01-06-2032\",\"66\":\"01-07-2032\",\"67\":\"01-08-2032\",\"68\":\"01-09-2032\",\"69\":\"01-10-2032\",\"70\":\"01-11-2032\",\"71\":\"01-12-2032\",\"72\":\"01-01-2033\",\"73\":\"01-02-2033\",\"74\":\"01-03-2033\",\"75\":\"01-04-2033\",\"76\":\"01-05-2033\",\"77\":\"01-06-2033\",\"78\":\"01-07-2033\",\"79\":\"01-08-2033\",\"80\":\"01-09-2033\",\"81\":\"01-10-2033\",\"82\":\"01-11-2033\",\"83\":\"01-12-2033\",\"84\":\"01-01-2034\",\"85\":\"01-02-2034\",\"86\":\"01-03-2034\",\"87\":\"01-04-2034\",\"88\":\"01-05-2034\",\"89\":\"01-06-2034\",\"90\":\"01-07-2034\",\"91\":\"01-08-2034\",\"92\":\"01-09-2034\",\"93\":\"01-10-2034\",\"94\":\"01-11-2034\",\"95\":\"01-12-2034\",\"96\":\"01-01-2035\",\"97\":\"01-02-2035\",\"98\":\"01-03-2035\",\"99\":\"01-04-2035\",\"100\":\"01-05-2035\",\"101\":\"01-06-2035\",\"102\":\"01-07-2035\",\"103\":\"01-08-2035\",\"104\":\"01-09-2035\",\"105\":\"01-10-2035\",\"106\":\"01-11-2035\",\"107\":\"01-12-2035\",\"108\":\"01-01-2036\",\"109\":\"01-02-2036\",\"110\":\"01-03-2036\",\"111\":\"01-04-2036\",\"112\":\"01-05-2036\",\"113\":\"01-06-2036\",\"114\":\"01-07-2036\",\"115\":\"01-08-2036\",\"116\":\"01-09-2036\",\"117\":\"01-10-2036\",\"118\":\"01-11-2036\",\"119\":\"01-12-2036\",\"120\":\"01-01-2037\",\"121\":\"01-02-2037\",\"122\":\"01-03-2037\",\"123\":\"01-04-2037\",\"124\":\"01-05-2037\",\"125\":\"01-06-2037\",\"126\":\"01-07-2037\",\"127\":\"01-08-2037\",\"128\":\"01-09-2037\",\"129\":\"01-10-2037\",\"130\":\"01-11-2037\",\"131\":\"01-12-2037\",\"132\":\"01-01-2038\",\"133\":\"01-02-2038\",\"134\":\"01-03-2038\",\"135\":\"01-04-2038\",\"136\":\"01-05-2038\",\"137\":\"01-06-2038\",\"138\":\"01-07-2038\",\"139\":\"01-08-2038\",\"140\":\"01-09-2038\",\"141\":\"01-10-2038\",\"142\":\"01-11-2038\",\"143\":\"01-12-2038\",\"144\":\"01-01-2039\",\"145\":\"01-02-2039\",\"146\":\"01-03-2039\",\"147\":\"01-04-2039\",\"148\":\"01-05-2039\",\"149\":\"01-06-2039\",\"150\":\"01-07-2039\",\"151\":\"01-08-2039\",\"152\":\"01-09-2039\",\"153\":\"01-10-2039\",\"154\":\"01-11-2039\",\"155\":\"01-12-2039\",\"156\":\"01-01-2040\",\"157\":\"01-02-2040\",\"158\":\"01-03-2040\",\"159\":\"01-04-2040\",\"160\":\"01-05-2040\",\"161\":\"01-06-2040\",\"162\":\"01-07-2040\",\"163\":\"01-08-2040\",\"164\":\"01-09-2040\",\"165\":\"01-10-2040\",\"166\":\"01-11-2040\",\"167\":\"01-12-2040\",\"168\":\"01-01-2041\",\"169\":\"01-02-2041\",\"170\":\"01-03-2041\",\"171\":\"01-04-2041\",\"172\":\"01-05-2041\",\"173\":\"01-06-2041\",\"174\":\"01-07-2041\",\"175\":\"01-08-2041\",\"176\":\"01-09-2041\",\"177\":\"01-10-2041\",\"178\":\"01-11-2041\",\"179\":\"01-12-2041\",\"180\":\"01-01-2042\",\"181\":\"01-02-2042\",\"182\":\"01-03-2042\",\"183\":\"01-04-2042\",\"184\":\"01-05-2042\",\"185\":\"01-06-2042\",\"186\":\"01-07-2042\",\"187\":\"01-08-2042\",\"188\":\"01-09-2042\",\"189\":\"01-10-2042\",\"190\":\"01-11-2042\",\"191\":\"01-12-2042\",\"192\":\"01-01-2043\",\"193\":\"01-02-2043\",\"194\":\"01-03-2043\",\"195\":\"01-04-2043\",\"196\":\"01-05-2043\",\"197\":\"01-06-2043\",\"198\":\"01-07-2043\",\"199\":\"01-08-2043\",\"200\":\"01-09-2043\",\"201\":\"01-10-2043\",\"202\":\"01-11-2043\",\"203\":\"01-12-2043\",\"204\":\"01-01-2044\",\"205\":\"01-02-2044\",\"206\":\"01-03-2044\",\"207\":\"01-04-2044\",\"208\":\"01-05-2044\",\"209\":\"01-06-2044\",\"210\":\"01-07-2044\",\"211\":\"01-08-2044\",\"212\":\"01-09-2044\",\"213\":\"01-10-2044\",\"214\":\"01-11-2044\",\"215\":\"01-12-2044\",\"216\":\"01-01-2045\",\"217\":\"01-02-2045\",\"218\":\"01-03-2045\",\"219\":\"01-04-2045\",\"220\":\"01-05-2045\",\"221\":\"01-06-2045\",\"222\":\"01-07-2045\",\"223\":\"01-08-2045\",\"224\":\"01-09-2045\",\"225\":\"01-10-2045\",\"226\":\"01-11-2045\",\"227\":\"01-12-2045\",\"228\":\"01-01-2046\",\"229\":\"01-02-2046\",\"230\":\"01-03-2046\",\"231\":\"01-04-2046\",\"232\":\"01-05-2046\",\"233\":\"01-06-2046\",\"234\":\"01-07-2046\",\"235\":\"01-08-2046\",\"236\":\"01-09-2046\",\"237\":\"01-10-2046\",\"238\":\"01-11-2046\",\"239\":\"01-12-2046\"}','Sahl Hasheesh, Hurghada','[\"01-01-2027\",\"01-02-2027\",\"01-03-2027\",\"01-04-2027\",\"01-05-2027\",\"01-06-2027\",\"01-07-2027\",\"01-08-2027\",\"01-09-2027\",\"01-10-2027\",\"01-11-2027\",\"01-12-2027\",\"01-01-2028\",\"01-02-2028\",\"01-03-2028\",\"01-04-2028\",\"01-05-2028\",\"01-06-2028\",\"01-07-2028\",\"01-08-2028\",\"01-09-2028\",\"01-10-2028\",\"01-11-2028\",\"01-12-2028\",\"01-01-2029\",\"01-02-2029\",\"01-03-2029\",\"01-04-2029\",\"01-05-2029\",\"01-06-2029\",\"01-07-2029\",\"01-08-2029\",\"01-09-2029\",\"01-10-2029\",\"01-11-2029\",\"01-12-2029\",\"01-01-2030\",\"01-02-2030\",\"01-03-2030\",\"01-04-2030\",\"01-05-2030\",\"01-06-2030\",\"01-07-2030\",\"01-08-2030\",\"01-09-2030\",\"01-10-2030\",\"01-11-2030\",\"01-12-2030\",\"01-01-2031\",\"01-02-2031\",\"01-03-2031\",\"01-04-2031\",\"01-05-2031\",\"01-06-2031\",\"01-07-2031\",\"01-08-2031\",\"01-09-2031\",\"01-10-2031\",\"01-11-2031\",\"01-12-2031\",\"01-01-2032\",\"01-02-2032\",\"01-03-2032\",\"01-04-2032\",\"01-05-2032\",\"01-06-2032\",\"01-07-2032\",\"01-08-2032\",\"01-09-2032\",\"01-10-2032\",\"01-11-2032\",\"01-12-2032\",\"01-01-2033\",\"01-02-2033\",\"01-03-2033\",\"01-04-2033\",\"01-05-2033\",\"01-06-2033\",\"01-07-2033\",\"01-08-2033\",\"01-09-2033\",\"01-10-2033\",\"01-11-2033\",\"01-12-2033\",\"01-01-2034\",\"01-02-2034\",\"01-03-2034\",\"01-04-2034\",\"01-05-2034\",\"01-06-2034\",\"01-07-2034\",\"01-08-2034\",\"01-09-2034\",\"01-10-2034\",\"01-11-2034\",\"01-12-2034\",\"01-01-2035\",\"01-02-2035\",\"01-03-2035\",\"01-04-2035\",\"01-05-2035\",\"01-06-2035\",\"01-07-2035\",\"01-08-2035\",\"01-09-2035\",\"01-10-2035\",\"01-11-2035\",\"01-12-2035\",\"01-01-2036\",\"01-02-2036\",\"01-03-2036\",\"01-04-2036\",\"01-05-2036\",\"01-06-2036\",\"01-07-2036\",\"01-08-2036\",\"01-09-2036\",\"01-10-2036\",\"01-11-2036\",\"01-12-2036\",\"01-01-2037\",\"01-02-2037\",\"01-03-2037\",\"01-04-2037\",\"01-05-2037\",\"01-06-2037\",\"01-07-2037\",\"01-08-2037\",\"01-09-2037\",\"01-10-2037\",\"01-11-2037\",\"01-12-2037\",\"01-01-2038\",\"01-02-2038\",\"01-03-2038\",\"01-04-2038\",\"01-05-2038\",\"01-06-2038\",\"01-07-2038\",\"01-08-2038\",\"01-09-2038\",\"01-10-2038\",\"01-11-2038\",\"01-12-2038\",\"01-01-2039\",\"01-02-2039\",\"01-03-2039\",\"01-04-2039\",\"01-05-2039\",\"01-06-2039\",\"01-07-2039\",\"01-08-2039\",\"01-09-2039\",\"01-10-2039\",\"01-11-2039\",\"01-12-2039\",\"01-01-2040\",\"01-02-2040\",\"01-03-2040\",\"01-04-2040\",\"01-05-2040\",\"01-06-2040\",\"01-07-2040\",\"01-08-2040\",\"01-09-2040\",\"01-10-2040\",\"01-11-2040\",\"01-12-2040\",\"01-01-2041\",\"01-02-2041\",\"01-03-2041\",\"01-04-2041\",\"01-05-2041\",\"01-06-2041\",\"01-07-2041\",\"01-08-2041\",\"01-09-2041\",\"01-10-2041\",\"01-11-2041\",\"01-12-2041\",\"01-01-2042\",\"01-02-2042\",\"01-03-2042\",\"01-04-2042\",\"01-05-2042\",\"01-06-2042\",\"01-07-2042\",\"01-08-2042\",\"01-09-2042\",\"01-10-2042\",\"01-11-2042\",\"01-12-2042\",\"01-01-2043\",\"01-02-2043\",\"01-03-2043\",\"01-04-2043\",\"01-05-2043\",\"01-06-2043\",\"01-07-2043\",\"01-08-2043\",\"01-09-2043\",\"01-10-2043\",\"01-11-2043\",\"01-12-2043\",\"01-01-2044\",\"01-02-2044\",\"01-03-2044\",\"01-04-2044\",\"01-05-2044\",\"01-06-2044\",\"01-07-2044\",\"01-08-2044\",\"01-09-2044\",\"01-10-2044\",\"01-11-2044\",\"01-12-2044\",\"01-01-2045\",\"01-02-2045\",\"01-03-2045\",\"01-04-2045\",\"01-05-2045\",\"01-06-2045\",\"01-07-2045\",\"01-08-2045\",\"01-09-2045\",\"01-10-2045\",\"01-11-2045\",\"01-12-2045\",\"01-01-2046\",\"01-02-2046\",\"01-03-2046\",\"01-04-2046\",\"01-05-2046\",\"01-06-2046\",\"01-07-2046\",\"01-08-2046\",\"01-09-2046\",\"01-10-2046\",\"01-11-2046\",\"01-12-2046\"]','10/01/2029',5,1,'under_development',5,15,'IncomeStatement','monthly','01/01/2027','12/01/2041',0,'0',2,33,33,'2',54.50,22.50,18.00,7.00,3,'2026-10-29 07:45:38',NULL,'2026-10-29 06:10:27','1','january',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0',0,0,1,1,1,0,0,1,NULL,'{\"2\":\"50\",\"3\":\"58\",\"4\":\"66\",\"5\":\"72\",\"6\":\"75\",\"7\":\"76\",\"8\":\"76\",\"9\":\"76\",\"10\":\"76\",\"11\":\"76\",\"12\":\"76\",\"13\":\"76\",\"14\":\"76\",\"15\":\"76\",\"16\":\"76\",\"17\":\"76\",\"18\":\"76\",\"19\":\"76\"}','terms_per_sales_channel','monthly-seasonality','general-seasonality','{\"01\":\"90\",\"02\":\"95\",\"03\":\"100\",\"04\":\"100\",\"05\":\"90\",\"06\":\"85\",\"07\":\"95\",\"08\":\"100\",\"09\":\"95\",\"10\":\"100\",\"11\":\"95\",\"12\":\"100\"}','5',NULL,'general-seasonality','{\"01\":\"5\",\"02\":\"5\",\"03\":\"8\",\"04\":\"9\",\"05\":\"8\",\"06\":\"8\",\"07\":\"11\",\"08\":\"12\",\"09\":\"9\",\"10\":\"10\",\"11\":\"8\",\"12\":\"7\"}','monthly-seasonality','general-seasonality','{\"quarter-one\":\"28\",\"quarter-two\":\"24\",\"quarter-three\":\"20\",\"quarter-four\":\"28\"}','quarterly-seasonality','1','1','1','1','1','general_collection_terms','terms_per_sales_channel','general_collection_terms','general_collection_terms',NULL,NULL,'system_default',NULL,'monthly','system_default',NULL,NULL,'system_default',NULL,'monthly','system_default',NULL,'monthly','system_default',1,1,1,1,1,'{\"2\":\"62\",\"3\":\"66.03\",\"4\":\"70.32\",\"5\":\"74.89\",\"6\":\"79.76\",\"7\":\"84.55\",\"8\":\"89.62\",\"9\":\"95\",\"10\":\"100.22\",\"11\":\"105.73\",\"12\":\"111.55\",\"13\":\"117.13\",\"14\":\"122.98\"}');
/*!40000 ALTER TABLE `hospitality_sectors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acquisition_id` int DEFAULT NULL,
  `ffe_id` int DEFAULT NULL,
  `loan_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grace_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loan_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installment_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fixedType` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `margin_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'tenor',
  `step_down_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `step_up_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `step_up_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `step_down_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borrowing_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capitalization_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `margin_interest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loan_interest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_interest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repayment_duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installment_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loans_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `loans_company_id_foreign` (`company_id`),
  CONSTRAINT `loans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `loans_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (10,6,2,'land',4,NULL,'normal','0',NULL,'quartly',NULL,NULL,'60',NULL,'20','2','22',NULL,'0','0','annually','annually',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30'),(11,6,2,'hard_cost_construction',4,NULL,'normal','0',NULL,'quartly',NULL,NULL,'96',NULL,'20','2','22',NULL,'0','0','annually','annually',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30'),(12,6,2,'ffe_cost',NULL,4,'normal','0',NULL,'semi annually',NULL,NULL,'60',NULL,'20','2','22',NULL,'0','0','annually','annually',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-29 08:10:30','2026-10-29 08:10:30');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `management_fees`
--

DROP TABLE IF EXISTS `management_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `management_fees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci,
  `company_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `management_fees_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `management_fees_company_id_foreign` (`company_id`),
  CONSTRAINT `management_fees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `management_fees_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `management_fees`
--

LOCK TABLES `management_fees` WRITE;
/*!40000 ALTER TABLE `management_fees` DISABLE KEYS */;
INSERT INTO `management_fees` VALUES (7,'management',6,'base_management_fees_as_percentage_from_revenues','Base Management Fees','{\"2\":\"3\",\"3\":\"3\",\"4\":\"3\",\"5\":\"3\",\"6\":\"3\",\"7\":\"3\",\"8\":\"3\",\"9\":\"3\",\"10\":\"3\",\"11\":\"3\",\"12\":\"3\",\"13\":\"3\",\"14\":\"3\",\"15\":\"3\",\"16\":\"3\",\"17\":\"3\",\"18\":\"3\",\"19\":\"3\"}',2,'2026-10-29 08:10:30','2026-10-29 08:10:30'),(8,'management',6,'incentive_management_fees_as_percentage_from_revenues','Incentive Management Fees','{\"2\":\"8\",\"3\":\"8\",\"4\":\"8\",\"5\":\"8\",\"6\":\"8\",\"7\":\"8\",\"8\":\"8\",\"9\":\"8\",\"10\":\"8\",\"11\":\"8\",\"12\":\"8\",\"13\":\"8\",\"14\":\"8\",\"15\":\"8\",\"16\":\"8\",\"17\":\"8\",\"18\":\"8\",\"19\":\"8\"}',2,'2026-10-29 08:10:30','2026-10-29 08:10:30');
/*!40000 ALTER TABLE `management_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_names`
--

DROP TABLE IF EXISTS `meeting_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_names` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_names_company_id_foreign` (`company_id`),
  CONSTRAINT `meeting_names_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_names`
--

LOCK TABLES `meeting_names` WRITE;
/*!40000 ALTER TABLE `meeting_names` DISABLE KEYS */;
INSERT INTO `meeting_names` VALUES (1,'Total Meeting Spaces Facilities',1,1,NULL,NULL),(2,'Wedding Halls',1,1,NULL,NULL),(3,'Meeting Rooms',1,1,NULL,NULL),(4,'Total Meeting Spaces Facilities',1,2,NULL,NULL),(5,'Wedding Halls',1,2,NULL,'2026-10-29 06:10:27'),(6,'Meeting Rooms',1,2,NULL,'2026-10-29 06:10:27'),(7,'Conference & Banquet Hall',1,2,'2026-10-29 05:46:28','2026-10-29 06:10:27');
/*!40000 ALTER TABLE `meeting_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_count` bigint DEFAULT NULL,
  `meeting_cover` bigint DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `f&b_facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `guest_capture_cover_percentage` longtext COLLATE utf8mb4_unicode_ci,
  `guest_seasonality` longtext COLLATE utf8mb4_unicode_ci,
  `rent_seasonality` longtext COLLATE utf8mb4_unicode_ci,
  `charges_value_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chosen_meeting_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_value_per_guest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_value_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_value_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percentage_from_f_and_b_revenues` longtext COLLATE utf8mb4_unicode_ci,
  `meeting_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meetings_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `meetings_meeting_type_id_foreign` (`meeting_type_id`),
  CONSTRAINT `meetings_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meetings_meeting_type_id_foreign` FOREIGN KEY (`meeting_type_id`) REFERENCES `meeting_names` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
INSERT INTO `meetings` VALUES (1,NULL,'',NULL,2,200,1,NULL,'2026-10-28 23:17:36','2026-10-28 23:31:09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2),(12,'customize','{\"rate\":[\"50\",\"50\",\"0\",\"0\"],\"due_in_days\":[\"0\",\"30\",\"0\",\"0\"]}',NULL,1,400,6,'guest_count_charges_per_guest_occupancy_rate_method','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"2\",\"3\":\"5\",\"4\":\"7\",\"5\":\"8\",\"6\":\"8\",\"7\":\"8\",\"8\":\"8\",\"9\":\"8\",\"10\":\"8\",\"11\":\"8\",\"12\":\"8\",\"13\":\"8\",\"14\":\"8\",\"15\":\"8\",\"16\":\"8\",\"17\":\"8\",\"18\":\"8\",\"19\":\"8\"}',NULL,NULL,'11','1','0','1800','2398.51113997','9',NULL,5),(13,'customize','{\"rate\":[\"100\",\"0\",\"0\",\"0\"],\"due_in_days\":[\"30\",\"0\",\"0\",\"0\"]}',NULL,4,40,6,'facility_count_daily_rent_occupancy_rate_method','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"10\",\"3\":\"15\",\"4\":\"20\",\"5\":\"22\",\"6\":\"25\",\"7\":\"25\",\"8\":\"25\",\"9\":\"25\",\"10\":\"25\",\"11\":\"25\",\"12\":\"25\",\"13\":\"25\",\"14\":\"25\",\"15\":\"25\",\"16\":\"25\",\"17\":\"25\",\"18\":\"25\",\"19\":\"25\"}',NULL,NULL,'11','1','28000',NULL,'37310.17328843','9',NULL,6),(14,'system_default','','monthly',1,250,6,'percentage_from_f_b_revenue','2026-10-29 06:10:27','2026-10-29 06:10:27',NULL,NULL,NULL,'0','1','0',NULL,NULL,'0','{\"2\":\"5\",\"3\":\"5\",\"4\":\"5\",\"5\":\"5\",\"6\":\"5\",\"7\":\"5\",\"8\":\"5\",\"9\":\"5\",\"10\":\"5\",\"11\":\"5\",\"12\":\"5\",\"13\":\"5\",\"14\":\"5\",\"15\":\"5\",\"16\":\"5\",\"17\":\"5\",\"18\":\"5\",\"19\":\"5\"}',7);
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_27_000001_create_companies_and_roles',1),(5,'2026_10_28_100001_create_countries_table',2),(6,'2026_10_28_100002_create_states_table',2),(7,'2026_10_28_100003_create_currencies_table',2),(8,'2026_10_28_100004_create_room_names_table',2),(9,'2026_10_28_100005_create_food_names_table',2),(10,'2026_10_28_100006_create_casino_names_table',2),(11,'2026_10_28_100007_create_meeting_names_table',2),(12,'2026_10_28_100008_create_other_names_table',2),(13,'2026_10_28_100009_create_hospitality_sectors_table',2),(14,'2026_10_28_100010_create_hospitality_sector_shares_table',2),(15,'2026_10_28_100011_create_rooms_table',2),(16,'2026_10_28_100012_create_saleschannels_table',2),(17,'2026_10_28_100013_create_foods_table',2),(18,'2026_10_28_100014_create_casinos_table',2),(19,'2026_10_28_100015_create_meetings_table',2),(20,'2026_10_28_100016_create_others_table',2),(21,'2026_10_28_100017_create_department_expenses_table',2),(22,'2026_10_28_100018_create_management_fees_table',2),(23,'2026_10_28_100019_create_acquisitions_table',2),(24,'2026_10_28_100020_create_property_acquisitions_table',2),(25,'2026_10_28_100021_create_property_acquisition_breakdowns_table',2),(26,'2026_10_28_100022_create_ffe_table',2),(27,'2026_10_28_100023_create_ffe_items_table',2),(28,'2026_10_28_100024_create_loans_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other_names`
--

DROP TABLE IF EXISTS `other_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_names` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `other_names_company_id_foreign` (`company_id`),
  CONSTRAINT `other_names_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other_names`
--

LOCK TABLES `other_names` WRITE;
/*!40000 ALTER TABLE `other_names` DISABLE KEYS */;
INSERT INTO `other_names` VALUES (1,'Total Other Revenues Facilities',1,1,NULL,NULL),(2,'GYM & SPA',1,1,NULL,NULL),(3,'Laundry',1,1,NULL,NULL),(4,'Commercial Shops',1,1,NULL,NULL),(5,'Total Other Revenues Facilities',1,2,NULL,NULL),(6,'GYM & SPA',1,2,NULL,'2026-10-29 06:10:27'),(7,'Laundry',1,2,NULL,'2026-10-29 06:10:27'),(8,'Commercial Shops',1,2,NULL,'2026-10-29 06:10:27'),(9,'Diving Center & Water Sports',1,2,'2026-10-29 05:46:29','2026-10-29 06:10:27');
/*!40000 ALTER TABLE `other_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `others`
--

DROP TABLE IF EXISTS `others`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `others` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `f&b_facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_count` bigint DEFAULT NULL,
  `chosen_other_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `charges_per_guest_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_per_guest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_per_guest_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charges_per_guest_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_capture_cover_percentage` longtext COLLATE utf8mb4_unicode_ci,
  `percentage_from_rooms_revenues` longtext COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `other_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `others_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `others_other_type_id_foreign` (`other_type_id`),
  CONSTRAINT `others_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `others_other_type_id_foreign` FOREIGN KEY (`other_type_id`) REFERENCES `other_names` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `others`
--

LOCK TABLES `others` WRITE;
/*!40000 ALTER TABLE `others` DISABLE KEYS */;
INSERT INTO `others` VALUES (1,NULL,'',NULL,NULL,2,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-28 23:17:36','2026-10-28 23:17:36',2),(15,'system_default','','monthly','guest_capture_x_charges_per_guest_method',1,'1',6,'11','700','9','932.75433221','{\"2\":\"12\",\"3\":\"15\",\"4\":\"18\",\"5\":\"18\",\"6\":\"18\",\"7\":\"18\",\"8\":\"18\",\"9\":\"18\",\"10\":\"18\",\"11\":\"18\",\"12\":\"18\",\"13\":\"18\",\"14\":\"18\",\"15\":\"18\",\"16\":\"18\",\"17\":\"18\",\"18\":\"18\",\"19\":\"18\"}',NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',6),(16,'system_default','','monthly','guest_capture_x_charges_per_guest_method',1,'1',6,'11','1800','9','2398.51113997','{\"2\":\"4\",\"3\":\"5\",\"4\":\"6\",\"5\":\"6\",\"6\":\"6\",\"7\":\"6\",\"8\":\"6\",\"9\":\"6\",\"10\":\"6\",\"11\":\"6\",\"12\":\"6\",\"13\":\"6\",\"14\":\"6\",\"15\":\"6\",\"16\":\"6\",\"17\":\"6\",\"18\":\"6\",\"19\":\"6\"}',NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',9),(17,'system_default','','monthly','percentage_from_rooms_revenue',1,'1',6,'0','0','0','0',NULL,'{\"2\":\"1\",\"3\":\"1\",\"4\":\"1\",\"5\":\"1\",\"6\":\"1\",\"7\":\"1\",\"8\":\"1\",\"9\":\"1\",\"10\":\"1\",\"11\":\"1\",\"12\":\"1\",\"13\":\"1\",\"14\":\"1\",\"15\":\"1\",\"16\":\"1\",\"17\":\"1\",\"18\":\"1\",\"19\":\"1\"}','2026-10-29 06:10:27','2026-10-29 06:10:27',7),(18,'system_default','','monthly','percentage_from_rooms_revenue',8,'1',6,'0','0','0','0',NULL,'{\"2\":\"1.2\",\"3\":\"1.2\",\"4\":\"1.2\",\"5\":\"1.2\",\"6\":\"1.2\",\"7\":\"1.2\",\"8\":\"1.2\",\"9\":\"1.2\",\"10\":\"1.2\",\"11\":\"1.2\",\"12\":\"1.2\",\"13\":\"1.2\",\"14\":\"1.2\",\"15\":\"1.2\",\"16\":\"1.2\",\"17\":\"1.2\",\"18\":\"1.2\",\"19\":\"1.2\"}','2026-10-29 06:10:27','2026-10-29 06:10:27',8);
/*!40000 ALTER TABLE `others` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_acquisition_breakdowns`
--

DROP TABLE IF EXISTS `property_acquisition_breakdowns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_acquisition_breakdowns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_cost_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `item_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `depreciation_duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_acquisition_breakdowns_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `property_acquisition_breakdowns_company_id_foreign` (`company_id`),
  CONSTRAINT `property_acquisition_breakdowns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `property_acquisition_breakdowns_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_acquisition_breakdowns`
--

LOCK TABLES `property_acquisition_breakdowns` WRITE;
/*!40000 ALTER TABLE `property_acquisition_breakdowns` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_acquisition_breakdowns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_acquisitions`
--

DROP TABLE IF EXISTS `property_acquisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_acquisitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_installment_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_after_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `second_property_down_payment_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_property_down_payment_percentage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_contingency_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_purchase_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_equity_funding_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '100',
  `installment_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `has_land_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_cost_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_cost_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ffe_replacement_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ffe_replacement_cost_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ffe_replacement_cost_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_acquisitions_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `property_acquisitions_company_id_foreign` (`company_id`),
  CONSTRAINT `property_acquisitions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `property_acquisitions_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_acquisitions`
--

LOCK TABLES `property_acquisitions` WRITE;
/*!40000 ALTER TABLE `property_acquisitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_acquisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_names`
--

DROP TABLE IF EXISTS `room_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_names` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_names_company_id_foreign` (`company_id`),
  CONSTRAINT `room_names_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_names`
--

LOCK TABLES `room_names` WRITE;
/*!40000 ALTER TABLE `room_names` DISABLE KEYS */;
INSERT INTO `room_names` VALUES (1,'Total Rooms',1,1,NULL,NULL),(2,'Standard Room',1,1,NULL,NULL),(3,'Standard Single Room',1,1,NULL,NULL),(4,'Standard Double Room',1,1,NULL,NULL),(5,'Deluxe Room',1,1,NULL,NULL),(6,'Deluxe Single Room',1,1,NULL,NULL),(7,'Deluxe Double Room',1,1,NULL,NULL),(8,'Premier Room',1,1,NULL,NULL),(9,'Premier Single Room',1,1,NULL,NULL),(10,'Premier Double Room',1,1,NULL,NULL),(11,'Junior Suite',1,1,NULL,NULL),(12,'Regular Suite',1,1,NULL,NULL),(13,'Presidential Suite',1,1,NULL,NULL),(14,'Royal Suite',1,1,NULL,NULL),(15,'Total Rooms',1,2,NULL,NULL),(16,'Standard Room',1,2,NULL,NULL),(17,'Standard Single Room',1,2,NULL,NULL),(18,'Standard Double Room',1,2,NULL,NULL),(19,'Deluxe Room',1,2,NULL,NULL),(20,'Deluxe Single Room',1,2,NULL,NULL),(21,'Deluxe Double Room',1,2,NULL,NULL),(22,'Premier Room',1,2,NULL,NULL),(23,'Premier Single Room',1,2,NULL,NULL),(24,'Premier Double Room',1,2,NULL,NULL),(25,'Junior Suite',1,2,NULL,NULL),(26,'Regular Suite',1,2,NULL,NULL),(27,'Presidential Suite',1,2,NULL,NULL),(28,'Royal Suite',1,2,NULL,NULL);
/*!40000 ALTER TABLE `room_names` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_count` bigint DEFAULT NULL,
  `guest_per_room` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `average_daily_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `chosen_room_currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `average_daily_rate_at_operation_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `average_daily_rate_annual_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `occupancy_rate_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `average_daily_rate_escalation_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupancy_rate_per_room` longtext COLLATE utf8mb4_unicode_ci,
  `seasonality` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `room_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rooms_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  KEY `rooms_room_type_id_foreign` (`room_type_id`),
  CONSTRAINT `rooms_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rooms_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_names` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,100,'2',1,'1000','1','1323.00649469','10','general_occupancy_rate','15',NULL,NULL,'2026-10-28 22:41:03','2026-10-28 23:21:03',1),(21,96,'2',6,'130','2','142.90240812','3.5','general_occupancy_rate','3.5',NULL,NULL,'2026-10-29 06:10:27','2026-10-29 07:29:08',18),(22,110,'2',6,'130','2','142.90240812','3.5','general_occupancy_rate','3.5',NULL,NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',21),(23,50,'2.2',6,'152','2','167.08589257','3.5','general_occupancy_rate','3.5',NULL,NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',24),(24,30,'2.5',6,'185','2','203.36111924','3.5','general_occupancy_rate','3.5',NULL,NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',25),(25,12,'3',6,'260','2','285.80481623','3.5','general_occupancy_rate','3.5',NULL,NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',26),(26,2,'4',6,'580','2','637.56459006','3.5','general_occupancy_rate','3.5',NULL,NULL,'2026-10-29 06:10:27','2026-10-29 06:10:27',27);
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saleschannels`
--

DROP TABLE IF EXISTS `saleschannels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saleschannels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hospitality_sector_id` bigint unsigned NOT NULL,
  `percentages` longtext COLLATE utf8mb4_unicode_ci,
  `revenue_share_percentage` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `discount_or_commission` longtext COLLATE utf8mb4_unicode_ci,
  `collection_policy_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_policy_interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'YES',
  PRIMARY KEY (`id`),
  KEY `saleschannels_hospitality_sector_id_foreign` (`hospitality_sector_id`),
  CONSTRAINT `saleschannels_hospitality_sector_id_foreign` FOREIGN KEY (`hospitality_sector_id`) REFERENCES `hospitality_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saleschannels`
--

LOCK TABLES `saleschannels` WRITE;
/*!40000 ALTER TABLE `saleschannels` DISABLE KEYS */;
INSERT INTO `saleschannels` VALUES (16,'direct-reservations',6,NULL,'{\"2\":\"15\",\"3\":\"15\",\"4\":\"15\",\"5\":\"15\",\"6\":\"15\",\"7\":\"15\",\"8\":\"15\",\"9\":\"15\",\"10\":\"15\",\"11\":\"15\",\"12\":\"15\",\"13\":\"15\",\"14\":\"15\",\"15\":\"15\",\"16\":\"15\",\"17\":\"15\",\"18\":\"15\",\"19\":\"15\"}','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"0\",\"3\":\"0\",\"4\":\"0\",\"5\":\"0\",\"6\":\"0\",\"7\":\"0\",\"8\":\"0\",\"9\":\"0\",\"10\":\"0\",\"11\":\"0\",\"12\":\"0\",\"13\":\"0\",\"14\":\"0\",\"15\":\"0\",\"16\":\"0\",\"17\":\"0\",\"18\":\"0\",\"19\":\"0\"}','system_default',NULL,'monthly'),(17,'agency',6,NULL,'{\"2\":\"50\",\"3\":\"50\",\"4\":\"50\",\"5\":\"50\",\"6\":\"50\",\"7\":\"50\",\"8\":\"50\",\"9\":\"50\",\"10\":\"50\",\"11\":\"50\",\"12\":\"50\",\"13\":\"50\",\"14\":\"50\",\"15\":\"50\",\"16\":\"50\",\"17\":\"50\",\"18\":\"50\",\"19\":\"50\"}','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"25\",\"3\":\"25\",\"4\":\"25\",\"5\":\"25\",\"6\":\"25\",\"7\":\"25\",\"8\":\"25\",\"9\":\"25\",\"10\":\"25\",\"11\":\"25\",\"12\":\"25\",\"13\":\"25\",\"14\":\"25\",\"15\":\"25\",\"16\":\"25\",\"17\":\"25\",\"18\":\"25\",\"19\":\"25\"}','customize','{\"rate\":[\"30\",\"70\",\"0\",\"0\"],\"due_in_days\":[\"0\",\"60\",\"0\",\"0\"]}',NULL),(18,'online-booking-engine',6,NULL,'{\"2\":\"20\",\"3\":\"20\",\"4\":\"20\",\"5\":\"20\",\"6\":\"20\",\"7\":\"20\",\"8\":\"20\",\"9\":\"20\",\"10\":\"20\",\"11\":\"20\",\"12\":\"20\",\"13\":\"20\",\"14\":\"20\",\"15\":\"20\",\"16\":\"20\",\"17\":\"20\",\"18\":\"20\",\"19\":\"20\"}','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"15\",\"3\":\"15\",\"4\":\"15\",\"5\":\"15\",\"6\":\"15\",\"7\":\"15\",\"8\":\"15\",\"9\":\"15\",\"10\":\"15\",\"11\":\"15\",\"12\":\"15\",\"13\":\"15\",\"14\":\"15\",\"15\":\"15\",\"16\":\"15\",\"17\":\"15\",\"18\":\"15\",\"19\":\"15\"}','customize','{\"rate\":[\"100\",\"0\",\"0\",\"0\"],\"due_in_days\":[\"30\",\"0\",\"0\",\"0\"]}',NULL),(19,'corporates',6,NULL,'{\"2\":\"8\",\"3\":\"8\",\"4\":\"8\",\"5\":\"8\",\"6\":\"8\",\"7\":\"8\",\"8\":\"8\",\"9\":\"8\",\"10\":\"8\",\"11\":\"8\",\"12\":\"8\",\"13\":\"8\",\"14\":\"8\",\"15\":\"8\",\"16\":\"8\",\"17\":\"8\",\"18\":\"8\",\"19\":\"8\"}','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"12\",\"3\":\"12\",\"4\":\"12\",\"5\":\"12\",\"6\":\"12\",\"7\":\"12\",\"8\":\"12\",\"9\":\"12\",\"10\":\"12\",\"11\":\"12\",\"12\":\"12\",\"13\":\"12\",\"14\":\"12\",\"15\":\"12\",\"16\":\"12\",\"17\":\"12\",\"18\":\"12\",\"19\":\"12\"}','customize','{\"rate\":[\"100\",\"0\",\"0\",\"0\"],\"due_in_days\":[\"45\",\"0\",\"0\",\"0\"]}',NULL),(20,'others-channels',6,NULL,'{\"2\":\"7\",\"3\":\"7\",\"4\":\"7\",\"5\":\"7\",\"6\":\"7\",\"7\":\"7\",\"8\":\"7\",\"9\":\"7\",\"10\":\"7\",\"11\":\"7\",\"12\":\"7\",\"13\":\"7\",\"14\":\"7\",\"15\":\"7\",\"16\":\"7\",\"17\":\"7\",\"18\":\"7\",\"19\":\"7\"}','2026-10-29 06:10:27','2026-10-29 06:10:27','{\"2\":\"8\",\"3\":\"8\",\"4\":\"8\",\"5\":\"8\",\"6\":\"8\",\"7\":\"8\",\"8\":\"8\",\"9\":\"8\",\"10\":\"8\",\"11\":\"8\",\"12\":\"8\",\"13\":\"8\",\"14\":\"8\",\"15\":\"8\",\"16\":\"8\",\"17\":\"8\",\"18\":\"8\",\"19\":\"8\"}','system_default',NULL,'quarterly');
/*!40000 ALTER TABLE `saleschannels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1F5JNEY63Nv0jsM2atXiA1oEtSdE5Nmt1QaHelmQ',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYXAyS2wzZjd3ak5HQ2RVdVpQZEk0WlJWZEtVcmRUb3k5ZFpqWm9uaCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793249302),('2bUcnp3AafCJWaYWOJcSERzAhFqv33oQ8sMpBZzK',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU3VpQ2tidWFZd2RNWXdIZjJvblJyNjJBdlFDTmFBRE1XdjlOa25saCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793239477),('3ixf9VAjSO6NLKwOsv89pct1fXAbXiW0zAhDJxde',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoialdPVHAwVFNzdW9CUWtZalB1TTNFSWh3dXU3S2k3dVVqb0xpYlZlYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793233358),('4IYKmQTeoaRUzrooNBg31RyKkCMl2WnmUHQstwEH',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM2hyM2JiWHhBQVVKd0FUeUtoOUNMSHdlQlRIUWRVVzh5ZFVHdlJZUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254258),('4ULZ6TJlwLHvQOVzi1d95udl3CTvE5HeanJAgNNf',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMGxFUWQ1Z0E2VWdDQmJxY0NSVGw0MDZySXYxbzlkdjJ6ZmoxVDdVYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793256701),('5eYg4E8NzaQ0zxYRfN9CxjBOfkxlfS5ExlMlanZ1',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicDhrTnBUVUh3U0RnQ3JlbkhmWG02aVA2SUZRSTFDQUxCMEQ4ZUZaVyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254009),('6K2lPjr9482vfjWHUzqYjft6Yz0LUGtYVeoqiExS',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSkpld3NQTnhpT053ZzdScFZ5cnh3aWliYWJEdndmNThHSDU1VzU1UyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793235255),('70JpjtPWKb0oHIWp6hg4AUR8i4YaNGDSIBA6zGcO',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM09BVVVPTXJNNmhNVTl1UG1VVXcxZzA4d2ZkYkp5aGJEMWpxYzVoNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793243213),('8ELCv5iuAXUQw8yVaWCpvy9q1cYjB3SiYuFbwOMS',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYXpxdmNaRVYzdE85TFFaa0F4ZUtIcXE1Wmx5cUl4YjI0WnR3bG9GRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252653),('9FMd9vuYSUg7GBLqebV63PXShax6SHtzYsBl6iNU',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOEd3M1FqSmpvMHZhd0lMSHNnbFBBOVNmNXNweFRsdDJXMzYza0pjRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254162),('9kAbFFNJ8w3CC50NmSNnmAqRqeFTku5JusWx8KWi',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSTBDc3FCN2RybktXQ2hjR2VpNkhkcUxBZTBySFc1d0pGaGF1cGhhZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252770),('ciUN8Wf9z0EuRg6EXsKtKyW3GaAKS1uqzH0nJXC1',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMjl4eEdwbXdLQTFyVWhjQU1jUkVWVll4VUVNSXdUdDBKY2dnU1pwMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251908),('DGNuierHwQKeKFNIR3fDvGyaCkFNub4WK1QGVCfr',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZzVnVXR0ZlJWdkpzYTJCSExObG5yeXNJZDNqMkFKaEJVdnRldUZZRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793249563),('ETkdNYxauRueVuktD9sAMoDU1XZyfi5uUdlb5uug',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTWdhUlhOdkhrcFdsMmdjZTZOUTV1Z0dKNDVZYlhYaVdUUHR0VXEyeCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254156),('FFB6x6Cz7AW4bLiVqpUDHZ6nk57BmWZTcA4XDVw0',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWlBReDlpY3Z4VlNnTUxWbE5LSDRKbG92Vlk4a01KcElLUEdzaERCNyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793253972),('gQJGXpOUsa6mGePUBXz76vgqD1q8ollkzzmOzhTI',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiS0hzRXU2azFQc1lCd1FhTWZLOHRpN1ltR0JLdXlnZk9CWkFXMGc5ZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793243230),('HqyDzlZwNs14JBZ2i2QQvGi22A8IEYjWV8YnUCLu',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicTNMTWFReEpWUTBWNFo1UGlrQ0ozcEVTaENpY04ycUFvWDZpUGdtciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793249210),('J2zZNTfZwvi80XAsJbroBt2jktqZJ6O9EShvD5Z7',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ3RqUVozS3c5cUNaT2xIU2F3Z25QakdONFdQMWZIWWNJUUU1Z1IzViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793250825),('J6lrZSt71riUr6zVJV6OiLV8xUV2mWyDluJU1ug7',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNDZiY1FyY1I2NEgzNmlpZk9rb0ZETlNZTzVQSnJidDVweHQyWDdzaCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793250314),('jjVvxW51SeNfnWMEtxN0ynjGYgK5YX9ZI19BwC3i',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUEJGbHRONUtObE9RaFlyQzFYRThhMXhRN2NFVjlhUGdnWVFoMG5xVCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251215),('jvjaf32q6H0SRp3b7Tpk9GSmZ4ZWUGIVMm8GcBAy',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVE9CSmxaU251TGtRUmNRdHZNN3RjVmFBRXE5TGZLSGZGdkZoZTFIZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251480),('KbJ74Xepg2RNoNraUckvDsoruHw1IRgnoz6dOsEn',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiV2RwSklBTW5LNTFTb25KeElKMEtzSHVhN0JtOUZoS0ZvcGtRR0xDWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793253589),('kOjd13bJpcKOmZtrveJ5aS6HZp8u7eyVkOqaZs0o',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR2o4bkoyMXRNWEpzUEp5b3d5UFlEcVFvdEVydU9WcVQzQndxZlpPUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251905),('kz2IfFBnwPqKOfUdbdUmMlExNaJqaGBKRBfhsSXn',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieHBEb1MxdHU3STVqTEZMV3VNU3MxSTcyclR6bEd1eERVV0JDamhrQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793253567),('kZkxECcpOLWmtPXqQ2vWAnO6CwTsN0kHDsFEw8Jd',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM0ZoMUtjR1kzZXJsOGFJVDFRNGJtYjBTT0tKdUw3UVpHSHZvYXBOcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793243771),('ldYtgX8cz9zF1jXTg5SfW94ThaBoeVXZZvM9o8th',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVTZqejV1eG1zd0dFSTNxMUM5aW9zUmxqNjk1OGFOdlVyNjR6QlAydCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793246638),('mcrbiErbSzAFF6NSfyf7PmP3DpD84MPGQO5Plulh',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiQ0xPR1F1ZkxSaTRLRmhxa0lVWnRQQkpVb3pzUXZzOEF5dHVRTEdrbyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793252868),('ntKOpGSiwneZ4zRkHI7VDipWYyvzl1fRa1lwu2YT',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVmFxSEliRlYxSEhlQ25KdVlEcHhXMkVEMlA5ZHcwSmNTdGpNTkdJNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793250908),('pbWEqzeV30LrL1btEGOoJcfNJdTCP1dAAIXR6C0H',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWklsUjdWTXhzek9DdEhJakFUcUlGTVJzbzR5b1RjdExRa3B4V3pRZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251215),('pDQITzOOZRdZeTs9IQGwmJTI3poiNPTW0jDSlDON',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidXJnNDhJeUI4aklBQXpUTFhYc0EwNzdBSWJvVUJNRzNmZWtEczdnWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252650),('PGl0JgDUDeSw2CZNV58O9OyMsfSNv6UCleTZpGhq',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYjlZZzIydnkwZGdaUTYyVU1nRnBHZklXRzd3THF6UjRZdlRSbTEzQiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252164),('PMmsqrwjUUfSEngXG7ncwNNaLqhf5OBTHb181uwl',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiS2tHYmVZTkphM1NqcFF3VTBwWDRwWDlZYTlzSEhPd0dQSHJhQktteSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793248992),('Pt6GKVoB7n82iuoGXb4TjxWiIcM2ZPAtoWI84gf9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ09vdjY0QzJqY2h6REIxbkdYMFpSMGtMNkVjV29ZdkltWThaaHFaSSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254218),('PUTMA8Dzd5Je2bJ8ORGodDFMVNi74LbXMYAimHoG',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieG9DbTIwY3FkaHJiVjk2Y1ZoMDFzR0Y4ZVhKaDZZNXJYRVdiR2ZYbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793256703),('Qj7gq8Nv1Zp9AgWab2Z7Ey0FVqrqFHxUnefg1vJ3',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiekQ3ZWhrbTBSc1dFbzM4UDJxaGpaQ0NTQ29wWmlIajVhdWM1OEVacSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793256356),('QjsKZqeuqvEnrhf7rM32QCVva5I6fJSwq9zKlyUX',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoibml3eHZwZFZqSDdkN0wzcXFPSmVhWEo0ZDVrTk1GZDVMWERnN1N1eCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793252868),('qnNdOAVfdl1w51IXpt9Q2VNCyo7HshtgGGagMrzM',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoic0trMUVMalpONWlId2dRZ1MzeE1zOXlZOFVwd3dhTkh0ekhpcHFUcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251485),('QqhG90hkORGxdLRYXwZdCbLsaT4zOeEbGu85aGnW',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRWh1Nk5sSXZPRkl5MW8zREZmTzVLN2FxRWlMTVZldHVwcHQ1b0VMRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793256687),('qZRjNfj0FjCHuMxYxXXRwYw8b5pmfc3RDxLnCp3F',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVUNuNG82S0hHajU4OEhDaDNqREVLNnkzNTBpNklENzNaa0t0ajR6VyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254011),('R5qadzRnBpkmcnYmUUFoV55ueEqvDUSAe8QOMZq2',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUmJlRFRkZ3dQNk9mZmVHWEpleVc1endhdFNBaDZXSmVjbWpDRmV3YyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793250913),('RKJu1ns87mTfUoeqWwHhIKQm98lcrdVH3PUFnZ6X',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicUtacktMVUgzdFJ3NGpMV09SbWo4eEpWemVPUmlwTzVhMXczYUh5MiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793235321),('SOqkszOmrLveKfF5o1asgaMHeijrKCcNbqJ4XNfs',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWpQdlFxT2RSM2padDI4Z09MWmI4aUprMU1QeXo4R2h0Y3E0YzNtRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793250353),('tgqpGaWNid5C3vwfioIT1AE2mIUKDcmJlgxmWgVt',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQkQ0bGd0YzZONnVyVm45cVNNRUdDVWRvc0tKOE5XcFVEaXpQTHZZOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793254250),('tWc9CvBHVpTxyMKvvnZl6o6yaptXxuLgSll9I899',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiS1ZPWmFZbXJ0VG5qaDl0cHY3T2FqYmtVdU16SVRmT3V2VDEzU3ZPdCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTE6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L3N0dWRpZXMvNi9yZXBvcnRzL2luY29tZSI7czo1OiJyb3V0ZSI7czoyMDoic3R1ZGllcy5yZXBvcnRzLnNob3ciO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiI0NDNmZWFiZjExZTY5MmMyNjI3ZDdlNzUwNWZkMjVjYTc5YjJlZWU4ZmNmODk3MDc4MjczZmI1ZTg5MDBhODZmIjtzOjU6InRoZW1lIjtzOjU6ImxpZ2h0Ijt9',1793257046),('UIeOq0ol2eM5hSEPNsBMD4VQlQ3283hKBdigyqnr',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNzVOMDRnTVltc05DR3YxaWIzVlhldkNYUElhQVFjbXhTVFB5eURqSSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793242183),('usTiUoXHlU8xW5ONVe2ERi05M8TSsZzrk586Lh45',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNXBPSFRicXlEbVJiY1B2SDVRMnV1TjhXbGREeDlybmVwMmd0eDhHWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793250690),('VnUSDsEpZI9ZCoUUIZv6mJxcOYfNgCELbzDHG1R8',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRDJlV1V3S0pWbXU2YTNYdTBtZjFCb3gxSkpxRDQwR3R6a0dHSkh4MCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793231282),('VU1yz1M7PvVYzrkbrZLpk9ERf1ZNCjsdPYFlO8nI',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYjlITENVVGRaUGxQeURZRDlYbWFCZUdyUE9mRHpkQ3BGQk80cTZnTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793243130),('W3htJ8guUwikiGcOdOu8w8zHX8pfifgkeyklKlI9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaDV1S2ZCSERlQXQ4T0JBTmRBSHdTcVRJbnJ0ZVoyT21tT2MyU0xiNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793256232),('W448EtyPllDZw2aUwOcTNM34c08gtAR2ADUG4xaC',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoia1E1cGdCU2FBUUk5ckpMQUlSVHRmMjN3VDY0UUhpUGh1SWNhdzl6MiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793235286),('WctXuBTsvmmZdFXkTv9FKOlj13Or50dolNlRKooM',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN2F1TlJ0blRZTHlvNzFMalJLejNNeG92MTR3cjNuMENQNG5jNVBPdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252654),('Wv3B6GXMOHt3GMU4dsyFM85rtmEGlAmKRUsDFIXd',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiRHZvYmd6TkpmbnBFVU1pM1Axb09kQ0RRa2NEV2poa09DV21ockptSSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793252869),('XzZFqzFze47BZ20H79pONx0MmcbZFsW80ZROZr21',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiazUzc1VOUXBlZDlNM1VKYzVTZHRXSnhuS0xMWXZRUXA4WFcwYXc4aCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251214),('yuCJh52pQ5ez6t5xcGW2DebmXDn0K49g8kckoNOD',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoidnlTZlhvS0c0Z1N4OGRGNklMalRIVWx5QzFsSllKYU9hSW5teFRNQiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793252869),('yyPUtM9ZdKA0x2XVPlEhKj4A5AQU4GNczHHbazO9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibHI3TWZnWFlqdDRyWWRwZ2pKdFl1cHNpTGxHMVRtekwyWVVGN2N6RCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252126),('Z7sfKwG21tV22TmUIUw1XjdOTwXy1sJvFuVCMyGG',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN3Z4cTI4UDJESlVaWGdRRGR6MVBRb1RBNUNWM3kyc242Y0NjTTJhVCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793252769),('zlGCkaYsx5sTW7kQkQon6Ud04HQ7TAH91DxdaUzr',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiV3dMRHJRdGR1Sm9xWUZZaWFLam9qNGpQZjRUVnV4WVlKZnlTWmdEdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9jZm8taG90ZWxzLXZ1ZS50ZXN0L21hbmlmZXN0LndlYm1hbmlmZXN0IjtzOjU6InJvdXRlIjtzOjEyOiJwd2EubWFuaWZlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793251443),('zMm1x9QIDcgIlzgjxys9MSQmuLJdsm33QvmTYgLP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiYUt5WFlnZWUxN2F2ajRQSENVVW95OEhGRGFTMTNqWHUyejVFQjREdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793252869);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states`
--

LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
INSERT INTO `states` VALUES (1,1,'القاهرة','Cairo',NULL,NULL),(2,1,'الجيزة','Giza',NULL,NULL),(3,1,'الأسكندرية','Alexandria',NULL,NULL),(4,1,'الدقهلية','Dakahlia',NULL,NULL),(5,1,'البحر الأحمر','Red Sea',NULL,NULL),(6,1,'البحيرة','Beheira',NULL,NULL),(7,1,'الفيوم','Fayoum',NULL,NULL),(8,1,'الغربية','Gharbiya',NULL,NULL),(9,1,'الإسماعلية','Ismailia',NULL,NULL),(10,1,'المنوفية','Monofia',NULL,NULL),(11,1,'المنيا','Minya',NULL,NULL),(12,1,'القليوبية','Qaliubiya',NULL,NULL),(13,1,'الوادي الجديد','New Valley',NULL,NULL),(14,1,'السويس','Suez',NULL,NULL),(15,1,'اسوان','Aswan',NULL,NULL),(16,1,'اسيوط','Assiut',NULL,NULL),(17,1,'بني سويف','Beni Suef',NULL,NULL),(18,1,'بورسعيد','Port Said',NULL,NULL),(19,1,'دمياط','Damietta',NULL,NULL),(20,1,'الشرقية','Sharkia',NULL,NULL),(21,1,'جنوب سيناء','South Sinai',NULL,NULL),(22,1,'كفر الشيخ','Kafr Al sheikh',NULL,NULL),(23,1,'مطروح','Matrouh',NULL,NULL),(24,1,'الأقصر','Luxor',NULL,NULL),(25,1,'قنا','Qena',NULL,NULL),(26,1,'شمال سيناء','North Sinai',NULL,NULL),(27,1,'سوهاج','Sohag',NULL,NULL),(28,2,'الرياض','Riyadh',NULL,NULL),(29,2,'جدة','Jeddah',NULL,NULL),(30,2,'الدمام','Al-Dammam',NULL,NULL),(31,2,'الخبر','Al-Khobar',NULL,NULL),(32,2,'الدوادمي','Al-Duwadimi',NULL,NULL),(33,2,'خميس مشيط','Khamis Mushait',NULL,NULL),(34,2,'حفر الباطن','Hafar Al Batin',NULL,NULL),(35,2,'نجران','Najran',NULL,NULL),(36,2,'مكة المكرمة','Makkah',NULL,NULL),(37,2,'جازان','Jazan',NULL,NULL),(38,2,'الظهران','Al-Dhahran',NULL,NULL),(39,2,'المدينة المنورة','El-Medina',NULL,NULL),(40,2,'الاحساء','Al-Ahsa',NULL,NULL),(41,2,'بريدة','Buraydah',NULL,NULL),(42,2,'عنيزه','Unayzah',NULL,NULL),(43,2,'حائل','Hail',NULL,NULL),(44,2,'الطائف','Taif',NULL,NULL),(45,2,'أبها','Abha',NULL,NULL),(46,3,'دبي','Dubai',NULL,NULL),(47,3,'أبوظبي','Abu Dhabi',NULL,NULL),(48,3,'الشارقة','Sharjah',NULL,NULL),(49,3,'عجمان','Ajman',NULL,NULL),(50,3,'العين','Al Ain',NULL,NULL),(51,3,'رأس الخيمة','Ras El Khaimah',NULL,NULL),(52,3,'أم القيوين','Umm Al Quwain',NULL,NULL),(53,4,'California','California',NULL,NULL),(54,4,'Texas','Texas',NULL,NULL),(55,4,'Florida','Florida',NULL,NULL),(56,4,'New York','New York',NULL,NULL),(57,4,'Pennsylvania','Pennsylvania',NULL,NULL),(58,4,'Illinois','Illinois',NULL,NULL),(59,4,'Ohio','Ohio',NULL,NULL),(60,4,'Georgia','Georgia',NULL,NULL),(61,4,'North Carolina','North Carolina',NULL,NULL),(62,4,'Michigan','Michigan',NULL,NULL),(63,4,'New Jersey','New Jersey',NULL,NULL),(64,4,'Virginia','Virginia',NULL,NULL),(65,4,'Washington','Washington',NULL,NULL),(66,4,'Arizona','Arizona',NULL,NULL),(67,4,'Massachusetts','Massachusetts',NULL,NULL),(68,4,'Tennessee','Tennessee',NULL,NULL),(69,4,'Indiana','Indiana',NULL,NULL),(70,4,'Missouri','Missouri',NULL,NULL),(71,4,'Maryland','Maryland',NULL,NULL),(72,4,'Wisconsin','Wisconsin',NULL,NULL),(73,4,'Colorado','Colorado',NULL,NULL),(74,4,'Minnesota','Minnesota',NULL,NULL),(75,4,'South Carolina','South Carolina',NULL,NULL),(76,4,'Albama','Albama',NULL,NULL),(77,4,'Louisiana','Louisiana',NULL,NULL),(78,4,'Kentucky','Kentucky',NULL,NULL),(79,4,'Oregon','Oregon',NULL,NULL),(80,4,'Oklahoma','Oklahoma',NULL,NULL),(81,4,'Connecticut','Connecticut',NULL,NULL),(82,4,'Utah','Utah',NULL,NULL),(83,4,'Puerto Rico','Puerto Rico',NULL,NULL),(84,4,'Iowa','Iowa',NULL,NULL),(85,4,'Nevada','Nevada',NULL,NULL),(86,4,'Arkansas','Arkansas',NULL,NULL),(87,4,'Mississippi','Mississippi',NULL,NULL),(88,4,'Kansas','Kansas',NULL,NULL),(89,4,'New Mexico','New Mexico',NULL,NULL),(90,4,'Nebraska','Nebraska',NULL,NULL),(91,4,'West Virginia','West Virginia',NULL,NULL),(92,4,'Idaho','Idaho',NULL,NULL),(93,4,'Hawaii','Hawaii',NULL,NULL),(94,4,'New Hampshire','New Hampshire',NULL,NULL),(95,4,'Maine','Maine',NULL,NULL),(96,4,'Montana','Montana',NULL,NULL),(97,4,'Rhode Island','Rhode Island',NULL,NULL),(98,4,'Delaware','Delaware',NULL,NULL),(99,4,'South Dakota','South Dakota',NULL,NULL),(100,4,'North Dakota','North Dakota',NULL,NULL),(101,4,'Alaska','Alaska',NULL,NULL),(102,4,'Vermont','Vermont',NULL,NULL),(103,4,'Wyoming','Wyoming',NULL,NULL),(104,4,'Guam','Guam',NULL,NULL),(105,4,'U.S. Virign Islands','U.S. Virign Islands',NULL,NULL),(106,4,'Northern Mariana Islands','Northern Mariana Islands',NULL,NULL),(107,4,'American Samoa','American Samoa',NULL,NULL),(108,5,'الأحمدي','Al-Ahmadi',NULL,NULL),(109,5,'مبارك الكبير','Mubarak Al Kabeer',NULL,NULL),(110,5,'الفروانية','Al Farwaniyah',NULL,NULL),(111,5,'الجهراء','Al Jahra',NULL,NULL),(112,5,'حولي','Hawalii',NULL,NULL),(113,5,'مدينة الكويت','Kuwait City',NULL,NULL),(114,6,'Nairobi','Nairobi',NULL,NULL),(115,6,'Coast Region','Coast Region',NULL,NULL),(116,6,'Rift Valley Region','Rift Valley Region',NULL,NULL),(117,6,'Eastern Region','Eastern Region',NULL,NULL),(118,6,'Central Region','Central Region',NULL,NULL),(119,6,'Western Region','Western Region',NULL,NULL),(120,7,'العقبة','Al-aqaba',NULL,NULL),(121,7,'عمّان','Amman',NULL,NULL),(122,7,'السلط','Salt',NULL,NULL),(123,7,'المفرق','Mafraq',NULL,NULL),(124,7,'مادبا','Madaba',NULL,NULL),(125,7,'الزرقاء','Al-Zarqa',NULL,NULL),(126,7,'إربد','Irbid',NULL,NULL),(127,7,'معان','Maan',NULL,NULL),(128,7,'الطفيلة','Tafilah',NULL,NULL),(129,7,'الكرك','Al-Karak',NULL,NULL),(130,7,'عجلون','Ajloun',NULL,NULL),(131,7,'جرش','Jerash',NULL,NULL);
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `job_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `theme` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_role_index` (`role`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Administrator','admin@cfo-hotels.test','2026-10-28 14:16:52','$2y$12$.OiXiW3PxtwgS8O97RaSa.Q158lUztzR06OvZ2uNUjkDS0HulmiU6','super_admin',NULL,'en','light',1,NULL,'2026-10-28 22:05:52','Wcy8nUgwHChVGNmC5bRBUiZtXe4jzKSgE8z8zjwZRXvmtKetAAt65BnwEPQI','2026-10-28 14:16:53','2026-10-28 22:05:52'),(2,1,'Ahmed Youssef','ahmed.youssef@squadbcc.com','2026-10-28 22:07:12','$2y$12$ODZr7ycutkiPgQCur6uilOvOWI02ixrAaZVideSOS8RBb6sNMkhzG','company_admin',NULL,'en','light',1,1,'2026-10-29 05:35:07','1lgL9cmOzeJRIqfx83SLWg5dPTTgjUVD8w4BDiite1Wl2MWRYL5ZzAcFq1Ui','2026-10-28 22:07:13','2026-10-29 05:35:07'),(3,2,'Demo Administrator','demo@cfo-hotels.com','2026-10-29 05:41:03','$2y$12$Ed1.dsZYMLo/XoFUPrm.lONX91GU.25arv9klmXiGc7BgxSf4KAWe','company_admin',NULL,'en','light',1,NULL,'2026-10-29 07:46:10','HtgXwI8bcwgHujH6jitzvMOD41TG7zvt01OnSHaond1ZIudcPN2rwrLGUaFW','2026-10-29 05:41:04','2026-10-29 07:57:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-29  9:11:48

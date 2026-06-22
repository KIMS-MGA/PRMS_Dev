-- MySQL dump 10.13  Distrib 8.4.9, for Linux (x86_64)
--
-- Host: localhost    Database: prms
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `jea_cache`
--

DROP TABLE IF EXISTS `jea_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `jea_cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_cache`
--

LOCK TABLES `jea_cache` WRITE;
/*!40000 ALTER TABLE `jea_cache` DISABLE KEYS */;
INSERT INTO `jea_cache` VALUES ('prms-cache-77de68daecd823babbb58edb1c8e14d7106e83bb','i:1;',1780991658),('prms-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer','i:1780991658;',1780991658),('prms-cache-ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4','i:1;',1780992969),('prms-cache-ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4:timer','i:1780992969;',1780992969),('prms-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0','i:1;',1780991482),('prms-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0:timer','i:1780991482;',1780991482),('prms-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:14:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:21:\"view-policy_proposals\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:23:\"create-policy_proposals\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:21:\"edit-policy_proposals\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:23:\"delete-policy_proposals\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:30:\"change-status-policy_proposals\";s:1:\"c\";s:3:\"web\";}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:23:\"review-policy_proposals\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:4;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:24:\"approve-policy_proposals\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:3;}}i:7;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:26:\"view-consolidated_policies\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:3;i:1;i:4;i:2;i:5;}}i:8;a:3:{s:1:\"a\";i:16;s:1:\"b\";s:28:\"create-consolidated_policies\";s:1:\"c\";s:3:\"web\";}i:9;a:3:{s:1:\"a\";i:17;s:1:\"b\";s:26:\"edit-consolidated_policies\";s:1:\"c\";s:3:\"web\";}i:10;a:3:{s:1:\"a\";i:18;s:1:\"b\";s:28:\"delete-consolidated_policies\";s:1:\"c\";s:3:\"web\";}i:11;a:3:{s:1:\"a\";i:19;s:1:\"b\";s:35:\"change-status-consolidated_policies\";s:1:\"c\";s:3:\"web\";}i:12;a:3:{s:1:\"a\";i:20;s:1:\"b\";s:28:\"review-consolidated_policies\";s:1:\"c\";s:3:\"web\";}i:13;a:3:{s:1:\"a\";i:21;s:1:\"b\";s:29:\"approve-consolidated_policies\";s:1:\"c\";s:3:\"web\";}}s:5:\"roles\";a:4:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"Proponent\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:8:\"Reviewer\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"TRC Secretariat\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:19:\"Receiving/Releasing\";s:1:\"c\";s:3:\"web\";}}}',1781697708);
/*!40000 ALTER TABLE `jea_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_cache_locks`
--

DROP TABLE IF EXISTS `jea_cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `jea_cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_cache_locks`
--

LOCK TABLES `jea_cache_locks` WRITE;
/*!40000 ALTER TABLE `jea_cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_failed_jobs`
--

DROP TABLE IF EXISTS `jea_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_failed_jobs`
--

LOCK TABLES `jea_failed_jobs` WRITE;
/*!40000 ALTER TABLE `jea_failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_job_batches`
--

DROP TABLE IF EXISTS `jea_job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_job_batches`
--

LOCK TABLES `jea_job_batches` WRITE;
/*!40000 ALTER TABLE `jea_job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_jobs`
--

DROP TABLE IF EXISTS `jea_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_jobs`
--

LOCK TABLES `jea_jobs` WRITE;
/*!40000 ALTER TABLE `jea_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_login_slides`
--

DROP TABLE IF EXISTS `jea_login_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_login_slides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_login_slides`
--

LOCK TABLES `jea_login_slides` WRITE;
/*!40000 ALTER TABLE `jea_login_slides` DISABLE KEYS */;
INSERT INTO `jea_login_slides` VALUES (1,'Sample Announcement','sample sub','login-slides/16fedc35-911c-455a-ade2-145d1a654073.png',5,0,'2026-05-08 15:36:49','2026-05-19 16:40:44'),(4,'samples','sample','login-slides/0b88352d-1240-41c9-a139-5efb98545bc4.png',1,0,'2026-05-08 17:15:19','2026-05-19 16:31:59'),(5,'sample1','sadjslkajd','login-slides/d3dbbfb3-05be-4ed7-b1e0-e9998cfab6df.png',2,0,'2026-05-08 17:16:37','2026-05-19 16:32:01'),(6,'pr',NULL,'login-slides/109d2bb2-4901-40c7-991e-2008a3fabefa.png',3,0,'2026-05-19 16:17:01','2026-05-19 16:40:41'),(8,'ti',NULL,'login-slides/0b69de7b-8e67-43ef-87bd-fd6cd3df131f.png',4,0,'2026-05-19 16:31:30','2026-05-19 16:40:43'),(9,'PRMS',NULL,'login-slides/bd50a086-b3ae-4bb4-ae11-16b639695614.png',6,0,'2026-05-19 16:40:53','2026-05-25 09:03:31'),(11,'PRMS1',NULL,'login-slides/d58217b7-f19d-4f28-bc6c-6683f6f3930c.png',7,1,'2026-05-25 09:03:53','2026-05-25 09:03:53');
/*!40000 ALTER TABLE `jea_login_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_migrations`
--

DROP TABLE IF EXISTS `jea_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_migrations`
--

LOCK TABLES `jea_migrations` WRITE;
/*!40000 ALTER TABLE `jea_migrations` DISABLE KEYS */;
INSERT INTO `jea_migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_20_120722_create_permission_tables',1),(5,'2026_03_20_121813_add_google_id_to_users_table',1),(6,'2026_03_20_121813_create_modules_table',1),(7,'2026_03_20_121814_create_module_fields_table',1),(8,'2026_03_20_121814_create_records_table',1),(9,'2026_03_20_123458_create_personal_access_tokens_table',1),(10,'2026_03_20_123824_create_workflows_table',1),(11,'2026_03_20_123825_create_workflow_actions_table',1),(12,'2026_03_20_124303_create_notifications_table',1),(13,'2026_03_20_140644_add_status_and_source_module',1),(14,'2026_03_20_143524_create_record_comments_table',1),(15,'2026_03_20_144131_add_description_to_module_fields',1),(16,'2026_03_21_053923_add_default_status_to_modules_table',1),(17,'2026_03_21_054824_add_my_records_only_to_modules_table',1),(18,'2026_03_21_060742_add_sort_order_to_modules_table',1),(19,'2026_03_21_061018_add_buttons_to_modules_table',1),(20,'2026_03_21_061400_add_has_draft_button_to_modules_table',1),(21,'2026_03_21_080935_add_review_permissions_for_existing_modules',1),(22,'2026_03_22_000001_create_workflow_stages_table',1),(23,'2026_03_22_000002_add_stage_and_assignee_to_records_table',1),(24,'2026_03_22_000003_create_record_approvals_table',1),(25,'2026_03_22_000004_create_record_histories_table',1),(26,'2026_03_22_000005_add_stage_type_to_workflow_stages_table',1),(27,'2026_03_22_100001_add_sort_order_to_module_fields_table',1),(28,'2026_03_22_100002_create_webhooks_table',1),(29,'2026_03_22_111541_add_two_factor_to_users_table',1),(30,'2026_03_25_121717_add_auto_advance_to_workflow_stages_and_stage_entered_at_to_records',1),(31,'2026_03_25_123132_add_branch_stages_to_workflow_stages',1),(32,'2026_03_27_000001_add_branches_json_to_workflow_stages',2),(33,'2026_03_27_000002_add_has_return_button_to_workflow_stages',3),(34,'2026_03_27_000003_add_reviewer_upload_field_to_workflow_stages',4),(35,'2026_03_28_000001_replace_reviewer_upload_field_with_stage_fields_json',5),(36,'2026_03_28_000002_add_show_in_index_to_module_fields',6),(37,'2026_03_30_000001_add_allow_edit_to_workflow_stages',7),(38,'2026_03_30_000002_add_default_status_to_workflow_stages',8),(39,'2026_03_30_000003_add_theme_to_users',9),(40,'2026_04_01_000001_add_versioning_to_module_fields',10),(41,'2026_04_01_000002_create_text_editor_tables',10),(42,'2026_04_02_000001_create_text_editor_comments_table',10),(43,'2026_04_06_000001_add_reviewer_role_id_to_workflow_stages',10),(44,'2026_04_23_000001_add_is_active_to_users_table',11),(45,'2026_04_24_000001_add_col_span_to_module_fields',12),(46,'2026_04_27_085449_create_workflow_stage_templates_table',13),(47,'2026_04_27_100000_add_notify_on_enter_json_to_workflow_stages',14),(48,'2026_05_08_000001_add_date_reminders_json_to_workflow_stages',15),(49,'2026_05_08_100000_create_login_slides_table',16),(50,'2026_05_30_000001_add_parent_id_to_text_editor_comments',17),(51,'2026_06_01_000001_add_line_number_to_text_editor_histories',18);
/*!40000 ALTER TABLE `jea_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_model_has_permissions`
--

DROP TABLE IF EXISTS `jea_model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `jea_model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `jea_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_model_has_permissions`
--

LOCK TABLES `jea_model_has_permissions` WRITE;
/*!40000 ALTER TABLE `jea_model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_model_has_roles`
--

DROP TABLE IF EXISTS `jea_model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `jea_model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `jea_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_model_has_roles`
--

LOCK TABLES `jea_model_has_roles` WRITE;
/*!40000 ALTER TABLE `jea_model_has_roles` DISABLE KEYS */;
INSERT INTO `jea_model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(3,'App\\Models\\User',3),(4,'App\\Models\\User',4),(5,'App\\Models\\User',5),(1,'App\\Models\\User',6);
/*!40000 ALTER TABLE `jea_model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_module_fields`
--

DROP TABLE IF EXISTS `jea_module_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_module_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `show_in_index` tinyint(1) NOT NULL DEFAULT '1',
  `col_span` tinyint NOT NULL DEFAULT '1',
  `versioning` tinyint(1) NOT NULL DEFAULT '0',
  `visibility_conditions` json DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `options_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_module_fields_module_id_foreign` (`module_id`),
  CONSTRAINT `jea_module_fields_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `jea_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_module_fields`
--

LOCK TABLES `jea_module_fields` WRITE;
/*!40000 ALTER TABLE `jea_module_fields` DISABLE KEYS */;
INSERT INTO `jea_module_fields` VALUES (196,1,'Title',NULL,0,1,2,0,NULL,'title','text',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(197,1,'Proposal Summary',NULL,1,0,2,0,NULL,'proposal_summary','textarea',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(198,1,'Proponent',NULL,2,1,1,0,NULL,'proponent','select',0,'[\"BPKMD\", \"WRD\", \"NPD\", \"CAWED\", \"CMD\", \"AFU\"]','2026-06-09 14:32:47','2026-06-09 14:32:47'),(199,1,'Policy Type',NULL,3,1,1,0,NULL,'policy_type','select',0,'[\"DENR Administrative Order\", \"Joint Administrative Order\", \"Memorandum Circular\"]','2026-06-09 14:32:47','2026-06-09 14:32:47'),(200,1,'Draft Policy Proposal',NULL,4,0,1,1,NULL,'draft_policy_proposal','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(201,1,'Annexes','Attach here the annexes if there\'s any',5,0,1,0,NULL,'annexes','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(202,1,'CSW','',6,0,1,0,NULL,'csw','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(203,1,'Policy Analysis','',7,0,1,0,NULL,'policy_analysis','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(204,1,'Copy of Stakeholder\'s Consultations','',8,0,1,0,NULL,'copy_of_stakeholders_consultations','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(205,1,'Copy of Resolutions','',9,0,1,0,NULL,'copy_of_resolutions','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(206,1,'Concurrence','',10,0,1,0,NULL,'concurrence','attachment',0,NULL,'2026-06-09 14:32:47','2026-06-09 14:32:47'),(207,1,'Draft Policy','',11,0,2,0,NULL,'draft_policy','text_editor',0,'{\"template\": \"<p><strong>DENR ADMINISTRATIVE ORDER</strong></p><p>No. 2026-__________</p><p><strong>SUBJECT</strong>       <strong>:</strong>\\t<strong>DECLARING CERTAIN PORTION OF FORESTLAND IN BARANGAY CANTUGAS AS CRITICAL HABITAT FOR <em>Rafflesia mixta</em> AND OTHER THREATENED WILDLIFE SPECIES COVERING AN AREA OF 159.73 HECTARES LOCATED IN THE MUNICIPALITY OF MAINIT, PROVINCE OF SURIGAO DEL NORTE</strong></p><p><strong><br></strong>           Pursuant to Section 25 of Republic Act (RA) No. 9147, otherwise known as the ΓÇ£Wildlife Resources Conservation and Protection ActΓÇ¥, Rules 25.1-25.5 of the Joint DENR-DA-PCSD Administrative Order No. 01, Series of 2004 or the joint Implementing Rules and Regulations (IRR) of R.A. 9147, and consistent with the DENR Memorandum Circular (DMC) No. 2 of 2007 or the ΓÇ£Guidelines on the Establishment and Management of Critical HabitatΓÇ¥, certain parcel of land of the public domain situated in Brgy. Cantugas, Municipality of Mainit, Province of Surigao del Norte, is hereby declared as Critical Habitat and shall be known as the <em>ΓÇ£</em><strong><em>CANTUGAS CRITICAL HABITAT FOR Rafflesia mixta AND OTHER THREATENED WILDLIFE SPECIESΓÇ¥</em></strong></p><p><strong>SECTION 1. Basic Policy. </strong>It is the policy of the State to conserve the countryΓÇÖs wildlife resources and their habitats for ecological and economic sustainability. As such, all habitats outside protected areas under RA No. 7586 or the ΓÇ£National Integrated Protected Areas Systems Act), where threatened species are found shall be designated as critical habitat. It is also the policy of the state that all designated critical habitats shall be protected and managed in coordination with the local government units (LGUs) and other concerned groups, from any form of exploitation or destruction which may be detrimental to the survival of the threatened species dependent therein.</p><p><strong>SECTION 2. Objectives. </strong>The objectives of this Order are as follows:</p><p>2.1\\tTo provide a legal framework for the protection of the <strong>159.73 hectares</strong> of forestland from destructive resource uses and other types of land use in Barangay Cantugas in the Municipality of Mainit, Province of Surigao del Norte that supports the existence of the <em>Rafflesia mixta</em>, its host plant, and other threatened wildlife species;</p><p>2.2\\tTo establish a locally-driven ecosystem management approach that guarantees the dynamic and full participation of LGUs, Indigenous People (IP) communities, peoplesΓÇÖ organizations and other stakeholders integrating threatened species conservation as part of local development planning process and way of life of the people; and</p><p>2.3\\tTo sustainably manage the area as a viable habitat for endemic and threatened species; to maintain ecological services and other biodiversity and cultural values; and, potentially for ecotourism development activities, e.g., bird watching, hiking, trekking and camping, that would contribute to inclusive socio-economic growth.</p><p><strong>SECTION 3. Scope and Coverage. </strong>The Cantugas Critical Habitat covers an approximate area of <strong>One Hundred Fifty Nine and 73/100 (159.73) hectares</strong> as indicated on the herein attached area map (<strong>Annex ΓÇ£AΓÇ¥</strong>) which forms an integral part of this Order, <s>subject to private rights if there be any,</s> and to ground survey and delineation, which is particularly described as follows:</p><p>Beginning at a point marked ΓÇ£1ΓÇ¥ on the map, being N 71┬░41ΓÇÖ30ΓÇ¥ W, 2109.237 meters from PRS92 control monument SRN-3246 with geographic coordinates 125┬░28ΓÇÖ19.63831ΓÇ¥ E, 9┬░34ΓÇÖ45.75739ΓÇ¥N located at Barangay Cantugas, Mainit, Surigao del Norte.</p><table><tbody><tr><td><p><strong>Line</strong></p></td><td><p><strong>Bearing</strong></p></td><td><p><strong>Distance (meters)</strong></p></td></tr><tr><td><p>1-2</p></td><td><p>S 4┬░57ΓÇÖ40ΓÇ¥ E</p></td><td><p>1255.487</p></td></tr><tr><td><p>2-3</p></td><td><p>S 52┬░20ΓÇÖ42ΓÇ¥ W</p></td><td><p>351.285</p></td></tr><tr><td><p>3-4</p></td><td><p>N 86┬░21ΓÇÖ4ΓÇ¥ W</p></td><td><p>577.41</p></td></tr><tr><td><p>4-5</p></td><td><p>N 6┬░18ΓÇÖ51ΓÇ¥ W</p></td><td><p>359.833</p></td></tr><tr><td><p>5-6</p></td><td><p>N 1┬░35ΓÇÖ23ΓÇ¥ W</p></td><td><p>80.545</p></td></tr><tr><td><p>6-7</p></td><td><p>N 8┬░46ΓÇÖ13ΓÇ¥ W</p></td><td><p>119.685</p></td></tr><tr><td><p>7-8</p></td><td><p>N 9┬░11ΓÇÖ22ΓÇ¥ W</p></td><td><p>102.197</p></td></tr><tr><td><p>8-9</p></td><td><p>N 1┬░30ΓÇÖ33ΓÇ¥ W</p></td><td><p>122.422</p></td></tr><tr><td><p>9-10</p></td><td><p>N 6┬░0ΓÇÖ35ΓÇ¥ W</p></td><td><p>218.844</p></td></tr><tr><td><p>10-11</p></td><td><p>N 7┬░31ΓÇÖ21ΓÇ¥ W</p></td><td><p>169.256</p></td></tr><tr><td><p>11-12</p></td><td><p>N 5┬░26ΓÇÖ55ΓÇ¥ W</p></td><td><p>298.174</p></td></tr><tr><td><p>12-13</p></td><td><p>N 23┬░33ΓÇÖ20ΓÇ¥ E</p></td><td><p>292.1</p></td></tr><tr><td><p>13-14</p></td><td><p>N 42┬░17ΓÇÖ25ΓÇ¥ E</p></td><td><p>216.041</p></td></tr><tr><td><p>14-15</p></td><td><p>N 89┬░51ΓÇÖ20ΓÇ¥ E</p></td><td><p>9295.656</p></td></tr><tr><td><p>15-16</p></td><td><p>S 80┬░53ΓÇÖ35ΓÇ¥ E</p></td><td><p>278.204</p></td></tr><tr><td><p>16-1</p></td><td><p>S 9┬░1ΓÇÖ22ΓÇ¥ E</p></td><td><p>422.865</p></td></tr><tr><td colspan=\\\"2\\\"><p><strong>Total AREA</strong></p></td><td><p><strong>159.73 hectares</strong></p></td></tr></tbody></table><p>Bearings and Distances of lines were derived using the PRS 1992 UTM Zone</p><p>51N coordinate system, subject to actual ground demarcation.</p><p>The DENR-CARAGA in coordination with the Biodiversity Management Bureau (BMB) and National Mapping and Resource Information Authority (NAMRIA) as necessary, shall undertake ground verification of the above-said coordinates within ninety (90) days from the effectivity of this Order.  Natural topographic features of the area, vegetative cover or permanent markers shall be used as boundary monuments/indicators. In case there are rectifications on the technical descriptions of the area, the final map as verified by the composite team of the NAMRIA, DENR-CARAGA and BMB shall be endorsed by the Regional Executive Director and the BMB Director to the DENR Secretary for approval.</p><p><strong>SECTION 4. Management of the Cantugas Critical Habitat for <em>Rafflesia mixta </em>and other threatened wildlife species. </strong>The Cantugas Critical Habitat for <em>Rafflesia mixta </em>and other threatened wildlife species shall be managed by DENR CARAGA, in partnership with the LGU of Mainit, Surigao del Norte and the IPC. The DENR through the Regional Executive Director shall enter into a Memorandum of Agreement (MOA) with the cooperating parties/partners. Alternatively, the concerned Regional Executive Director may delegate the management of the critical habitat to LGU-Mainit and/or the IP through a similar instrument.</p><p>The DENR-CARAGA and its partner/s shall:</p><ul><li><ol><li>Ensure that existing ecosystems in the critical habitat are preserved and are kept in a condition that will support and enhance the existing populations of the <em>Rafflesia mixta, </em>its host plant, and associated naturally occurring flora and fauna;</li><li>Ensure that developmental activities within or in the periphery of the critical habitat undergo the necessary assessment processes so as to safeguard the ecological integrity of the area;</li><li>Ensure the enforcement of applicable environmental laws and all prohibited acts within critical habitat as provided under Section 7 of this Order; </li><li>Ensure that all income from the operation and management of the critical habitat shall be used for the conservation and protection of the area; and,</li><li>Monitor and evaluate compliance of the Critical Habitat Management Plan (CHMP). </li></ol></li></ul><p><strong>SECTION 5. Critical Habitat Management Plan. </strong>The DENR CARAGA, in partnership with the LGU of Mainit, Surigao del Norte and the IP Community, in coordination and collaboration with other local stakeholders, shall jointly prepare and cause the implementation of the CHMP within ninety (90) days upon effectivity of this Order. The CHMP shall be approved by the Regional Executive Director. </p><p>The CHMP must be able to address: a) management objectives; b) key management issues; c) site management strategies and activities such as but not limited to habitat protection, rehabilitation, community organizing, promotion of environmental education and awareness, ecotourism, and other developmental   activities towards the sustainable management of the area; d) translocations; e) administration; f) climate change; g) gender lens; and, h) monitoring and evaluation, among others.  The CHMP must also address the targets, issues and concerns of the National Action Plan on Ecosystems Restoration and Species Extinction Prevention (NAPERSEP), the Philippine Plant Conservation Strategy and Action Plan, and the Ancestral Domain Sustainable Development Protection Plan (ADSDPP).</p><p>For this purpose, the template of the CHMP (BMB Technical Bulletin No. 2017-15) is attached as ΓÇ£<strong>Annex B</strong>ΓÇ¥.  The DENR CARAGA shall submit to BMB a copy of the approved CHMP for record and monitoring purposes.</p><p>In the event that the DENR Regional Executive Director opts to delegate the management of the critical habitat to LGU-Mainit, Surigao del Norte, the regional office shall ensure provision of necessary technical assistance to said LGU and set schemes to monitor and evaluate the implementation of the CHMP, in collaboration with other stakeholders. The DENR through the Regional Executive Director concerned may enter into a Memorandum of Agreement (MOA)/Partnership Agreement with the concerned LGU for the management of the critical habitat.</p><p>The DENR-CARAGA shall review and update the CHMP every five (5) years in consultation with other stakeholders.  </p><p><strong>SECTION 6. Monitoring and Evaluation. </strong>The DENR-CARAGA through the PENRO/CENRO shall undertake monitoring and evaluation on the management of Cantugas Critical Habitat, as well as the implementation of its CHMP.  The DENR-CARAGA shall submit an annual report to the Supervising Undersecretary for Biodiversity and the BMB for integration in the Information Management System of the Bureau. </p><p>The CENRO shall:  </p><ul><li><ol><li>Oversee regular monitoring and enforcement activities within the critical habitat;</li><li>Gather data on habitat conditions, wildlife populations, and human  activities, and submits reports to PENRO;</li><li>Facilitates community participation in conservation efforts and provides education on habitat importance;</li><li>Conducts regular patrols to prevent illegal activities within the critical  Habitat; </li><li>Work closely with LGU-Mainit to integrate local plans and policies; and</li><li>Submit reports to the Regional Executive Director thru proper channel.</li></ol></li></ul><p>The PENRO shall:</p><ul><li><ol><li>Provide technical guidance and oversight to CENRO and LGU-Mainit;</li><li>Train CENRO and LGU personnel on conservation management; and</li><li>Assess CENRO\'s performance, ensuring compliance with regulations.</li></ol></li></ul><p><strong>SECTION 7. Prohibited Acts and Penalties. </strong>The conduct of the<strong> </strong>following activities inside the Cantugas Critical Habitat are prohibited and punishable in accordance with Sections 27 and 28 of RA 9147:</p><table><tbody><tr><td><p><strong>Illegal Acts</strong></p></td><td><p><strong>Punishment</strong></p></td></tr><tr><td><ol><li>killing and destroying wildlife species, except in the following instances;</li><li>when it is done as part of the religious rituals of established tribal groups or indigenous cultural communities;</li></ol><p>(ii) when the wildlife is afflicted with an incurable communicable disease;</p><p>(iii) when it is deemed necessary to put an end to the misery suffered by the wildlife.</p><p>(iv) when it is done to prevent an imminent danger to the life or limb of a human being; and</p><p>(v) when the wildlife is killed or destroyed after it has been used in authorized research or experiments.</p></td><td><ul><li><ol><li>imprisonment of a minimum of six (6) years and one (1) day to twelve (12) years and/ or a fine of One Hundred Thousand pesos (P100,000.00) to One Million pesos (P1,000,000.00), if inflicted or undertaken against species listed as critical;</li><li>imprisonment of four (4) years and one (1) day to six (6) years and/or a fine of Fifty Thousand Pesos (P50,000.00) to Five Hundred Thousand pesos(P500,000,00), if inflicted or undertaken against endangered species.</li><li>imprisonment of two (2) years and one (1) day to four (4) years and/ or a fine of Thirty Thousand pesos (P30,000.00) to Three Hundred Thousand pesos (P300,000.00), if inflicted or undertaken against vulnerable species;</li><li>imprisonment of one (1) year and one (1) day to two (2) years and /or a fine of Twenty Thousand pesos (P20,000.00) to Two Hundred Thousand pesos (P200,000.00), if inflicted or undertaken against other threatened species; and</li><li>imprisonment of six (6) months and one (1) day to one (1) year and/or a fine of Ten Thousand pesos (P10,000.00) to One Hundred Thousand pesos (P100,000.00), if inflicted or undertaken against other wildlife species.</li></ol></li></ul></td></tr><tr><td><ol><li>inflicting injury which cripples and/or impairs the reproductive system of wildlife species;</li></ol></td><td><ol><li>imprisonment of a minimum of four (4) years and one (1) day to six (6) years and/or a fine of Fifty Thousand pesos P(50,000.00) to Five Hundred Thousand pesos (P500,000.00), if inflicted or undertaken against species listed as critical;</li><li>imprisonment of two (2) years and one (1) day to four (4) years and/ or a fine of Thirty Thousand pesos (P30,000.00) to Two Hundred Thousand pesos (P200,000.00), if inflicted or undertaken against endangered species;</li><li>imprisonment of one (1) year and one (1) day to two (2) years and/or a fine of Twenty Thousand pesos (P20,000.00) to Two Hundred Thousand pesos (P200,000.00), if inflicted or undertaken against vulnerable species;</li><li>imprisonment of six (6) months and one (1) day to one (1) year and/or a fine of Ten Thousand pesos (P10,000.00) to Fifty Thousand pesos (P50,000.00), if inflicted or undertaken against other threatened species; and</li><li>imprisonment of one (1) month to six months (6) and/or a fine of Five Thousand pesos (P5,000.00) to Twenty Thousand pesos (P20,000.00), if inflicted or undertaken against other wildlife species.</li></ol></td></tr><tr><td><ol><li>effecting any of the following acts in critical habitat(s):</li><li>dumping of waste products detrimental to wildlife;</li><li>squatting or otherwise occupying any portion of the critical habitat;</li><li>  mineral exploration and/or</li></ol><p>extraction;</p><ol><li>burning;</li><li>logging; and</li><li>quarrying;</li></ol></td><td rowspan=\\\"2\\\"><p>An imprisonment of one (1) month to eight (8) years and/or a fine of Five Thousand pesos (P5,000.00) to Five Million pesos (P5,000,000.00) shall be imposed.</p></td></tr><tr><td><ol><li>introduction, reintroduction, or restocking of wildlife resources;</li></ol></td></tr><tr><td><ol><li>trading of wildlife;</li></ol></td><td><ul><li><ol><li>imprisonment of two (2) years and one (1) day to four (4) years and/ or a fine of Five Thousand pesos (P5,000.00) to Three Hundred Thousand pesos (P300,000.00), if inflicted or undertaken against species listed as critical;</li><li>imprisonment of one (1) year and one (1) day to two (2) years and/or a fine of Two Thousand pesos (P2,000.00) to Two Hundred Thousand pesos (P200,000.00), if inflicted or undertaken against endangered species;</li><li>imprisonment of six (6) months and one (1) day to one (1) year and/or a fine of One Thousand pesos (P1,000.00) to One Hundred Thousand pesos (P100,000.00), if inflicted or undertaken against vulnerable species;</li><li>imprisonment of one (1) month and one (1) day to six months (6) and/or a fine of Five Hundred pesos (P500.00) to Fifty Thousand pesos (P50,000.00), if inflicted or undertaken against species listed as other threatened species; and</li><li>imprisonment of ten (10) days to one (1) month and/or a fine of Two Hundred pesos (P200.00) to Twenty Thousand pesos (P20,000.00), if inflicted or undertaken against other wildlife species.</li></ol></li></ul></td></tr><tr><td><ul><li><ol><li>collecting, hunting, or possessing wildlife, their by-products and derivatives;</li></ol></li></ul></td><td rowspan=\\\"2\\\"><ol><li>imprisonment of two (2) years and one (1) day to four (4) years and a fine of Thirty Thousand pesos (P30,000.00) to Three Hundred Thousand pesos (P300,000.00), if inflicted or undertaken against species listed as critical;</li><li>imprisonment of one (1) year and one (1) day to two (2) years and a fine of Twenty Thousand pesos (P20,000.00) to Two Hundred Thousand pesos (P200,000.00), if inflicted or undertaken against endangered species;</li><li>imprisonment of six (6) months and one (1) day to one (1) year and a fine of Ten Thousand pesos (P10,000.00) to One Hundred Thousand pesos (P100,000.00), if inflicted or undertaken against vulnerable species;</li><li>imprisonment of one (1) month and one (1) day to six (6) months and a fine of Five Thousand pesos (P5,000.00) to Fifty Thousand pesos (P50,000.00), if inflicted or undertaken against species listed as other threatened species; and</li><li>imprisonment of ten (10) days to one (1) month and a fine of One Thousand pesos (P1,000.00) to Five Thousand pesos (P5,000.00), if inflicted or undertaken against other wildlife species: <em>Provided, </em>That in case of paragraph (f), where the acts were perpetuated through the means of inappropriate techniques and devices, the maximum penalty herein provided shall be imposed.</li></ol></td></tr><tr><td><ul><li><ol><li>gathering or destroying of active nests, nest trees, host plants and the like;</li></ol></li></ul></td></tr><tr><td><ul><li><ol><li>maltreating and/or inflicting other injuries not covered by the preceding paragraph; and,</li></ol></li></ul></td><td rowspan=\\\"2\\\"><ol><li>imprisonment of six (6) months and one (1) day to one (1) year and a fine of Fifty Thousand pesos (P50,000.00) to One Hundred Thousand pesos (P100,000.00), if inflicted or undertaken against species listed as critical species;</li><li>imprisonment of three (3) months and one (1) day to six (6) months and a fine of Twenty Thousand pesos (P20,000.00) to Fifty Thousand pesos (P50,000.00), if inflicted or undertaken against endangered species;</li><li>imprisonment of one (1) month and one (1) day to three (3) months and a fine of Five Thousand pesos (P5,000.00) to Twenty Thousand pesos (P20,000.00), if inflicted or undertaken against vulnerable species;</li><li>imprisonment of ten (10) days to one (1) month and a fine of One Thousand pesos (P1,000.00) to Five Thousand pesos (P5,000.00), if inflicted or undertaken against species listed as other threatened species;</li><li>imprisonment of five (5) days to ten (10) days and a fine of Two Hundred pesos (P200.00) to One Thousand pesos (P1,000.00), if inflicted or undertaken against other wildlife species.</li></ol></td></tr><tr><td><ul><li><ol><li>transporting of wildlife</li></ol></li></ul></td></tr></tbody></table><p><strong>SECTION 8. Funding. </strong>The DENR CARAGA Region shall allocate funds for the operation and implementation of the CHMP.  As such, the DENR CARAGA shall include funds for the purpose in their Annual Work and Financial Plan.  The DENR CARAGA shall also endeavor to engage the LGU and other partner/s to provide funds for the management of the Critical Habitat.</p><p><strong>SECTION 9. Separability Clause.</strong> If any provision of this Order shall be held invalid or unconstitutional, the other portions or provisions hereof which are not affected shall continue in full force and effect. </p><p><strong>SECTION 10. Repealing Clause.</strong> All Orders and other similar issuances inconsistent herewith are hereby revoked, amended, or modified accordingly.</p><p><strong>SECTION 11. Effectivity.</strong> This Order shall take effect fifteen (15) days after its publication in a newspaper of general circulation and upon filing of three (3) certified copies hereof with the Office of the National Administrative Register (ONAR) and the University of the Philippines (UP) Law Center.</p><p><strong>                                                             ATTY. JUAN MIGUEL T. CUNA, CESO I</strong></p><p>  Secretary</p>\", \"log_history\": true, \"require_review\": true}','2026-06-09 14:32:47','2026-06-09 14:32:47');
/*!40000 ALTER TABLE `jea_module_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_modules`
--

DROP TABLE IF EXISTS `jea_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Submitted',
  `my_records_only` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `has_submit_button` tinyint(1) NOT NULL DEFAULT '0',
  `has_return_button` tinyint(1) NOT NULL DEFAULT '0',
  `has_draft_button` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `source_module_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_modules_slug_unique` (`slug`),
  KEY `jea_modules_source_module_id_foreign` (`source_module_id`),
  CONSTRAINT `jea_modules_source_module_id_foreign` FOREIGN KEY (`source_module_id`) REFERENCES `jea_modules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_modules`
--

LOCK TABLES `jea_modules` WRITE;
/*!40000 ALTER TABLE `jea_modules` DISABLE KEYS */;
INSERT INTO `jea_modules` VALUES (1,'Policy Proposals','policy_proposals','General process flow for policy proposals','Draft',1,1,1,1,1,'2026-03-27 02:20:51','2026-06-09 14:32:47',NULL),(2,'Consolidated Policies','consolidated_policies','','Submitted',0,2,0,0,0,'2026-03-27 05:31:26','2026-03-27 05:31:26',1);
/*!40000 ALTER TABLE `jea_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_notifications`
--

DROP TABLE IF EXISTS `jea_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_notifications`
--

LOCK TABLES `jea_notifications` WRITE;
/*!40000 ALTER TABLE `jea_notifications` DISABLE KEYS */;
INSERT INTO `jea_notifications` VALUES ('02fc5874-8902-40d1-861e-18e735565ac3','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":4,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:20:37','2026-03-30 02:04:38','2026-03-30 02:20:37'),('054ba60e-57ac-4416-ac74-efe69019fa51','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}',NULL,'2026-05-20 14:08:13','2026-05-20 14:08:13'),('08219c8f-9946-436d-a360-b4ac54141445','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has been forwarded (Proceed to Uploading the Signed Policy) and requires your action.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 13:21:09','2026-04-23 13:17:20','2026-04-23 13:21:09'),('0a2eb105-690b-4ea9-b59e-45bc16bf4bf9','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Ad Referendum)) and requires your action.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:24:05','2026-06-09 15:24:05'),('0a951786-1afd-4e70-bae1-08b4fe641ac1','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has been forwarded (Proceed to Uploading the Signed Policy) and requires your action.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 06:31:49','2026-03-31 06:32:52'),('0abfa83e-3cfb-4654-ad2e-e487e379dd69','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been completed.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-04-23 11:07:36','2026-03-31 06:32:45','2026-04-23 11:07:36'),('0c23b12d-b1e9-4695-9f3f-7e2bf8d56aab','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been returned for revision.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}','2026-06-09 15:01:21','2026-06-09 14:30:12','2026-06-09 15:01:21'),('0c4da0d8-2e2a-4ce1-b92e-e44cf0588336','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:20:37','2026-03-30 02:19:09','2026-03-30 02:20:37'),('109cf72d-0aec-4176-ad6d-68d98d40fb45','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been completed.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 17:04:12','2026-05-20 14:10:33','2026-05-20 17:04:12'),('1167e670-fc0c-4d1b-9fe5-da350c5d7686','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":8,\"module_slug\":\"policy_proposals\"}','2026-04-23 10:48:58','2026-04-23 10:48:38','2026-04-23 10:48:58'),('12161429-7175-4f54-8819-defacda22a88','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has been forwarded (For PTWG Endorsement) and requires your action.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-30 02:21:11','2026-03-31 06:32:52'),('13e30845-c725-4992-b452-7159db7b2a19','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Re-Deliberation)) and requires your action.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 03:09:32','2026-03-31 03:08:32','2026-03-31 03:09:32'),('15b14f89-d372-4dc4-be84-860a2b20235b','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":12,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:30:14','2026-04-27 09:08:57','2026-05-20 13:30:14'),('16cdcb70-396d-4033-8464-e65faade2e4c','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 12:41:52','2026-04-23 12:41:46','2026-04-23 12:41:52'),('1af6b742-ee29-4113-9ffa-33d650156514','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:52:57','2026-06-09 15:52:57'),('1b606c40-939a-4429-970f-9f92da9214b0','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":33,\"module_slug\":\"policy_proposals\"}',NULL,'2026-05-25 09:07:09','2026-05-25 09:07:09'),('1ec8d6ac-bcdd-45a7-8534-bb37925b4334','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":2,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:44:39','2026-03-28 11:25:19','2026-03-30 02:44:39'),('1f70e7e4-c6f6-45ad-b447-b8ebf8c47f7e','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:55:25','2026-05-20 13:55:13','2026-05-20 13:55:25'),('1f7f2451-5d76-488d-8dcf-8bb601702b81','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Consolidated Policies has advanced and requires your approval.\",\"record_id\":33,\"module_slug\":\"consolidated_policies\"}','2026-06-09 11:52:25','2026-05-25 09:08:29','2026-06-09 11:52:25'),('1f9517e8-7096-4549-96ca-616ad4f8cad5','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been returned for revision.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-27 03:51:03','2026-03-27 03:49:48','2026-03-27 03:51:03'),('206693bb-10fb-4d4b-98fb-d5c972b71e6a','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":4,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:20:37','2026-03-30 02:10:13','2026-03-30 02:20:37'),('26726c53-6f5b-43a6-94d9-001d4d42b8c1','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Re-Deliberation)) and requires your action.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 16:15:17','2026-06-09 16:15:17'),('2676c6ff-2cff-4186-b242-3abc081824fe','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 03:14:01','2026-03-31 06:32:52'),('2dc12ca4-af9c-4077-8abc-14aa66bd86a4','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 16:16:19','2026-06-09 16:16:19'),('301e275f-f417-4416-8654-2568a13477b2','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been returned for revision.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:36:12','2026-03-31 02:34:16','2026-03-31 02:36:12'),('31b18381-4fb0-4670-916e-1a131094d888','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Consolidated Policies has advanced and requires your approval.\",\"record_id\":33,\"module_slug\":\"consolidated_policies\"}','2026-06-09 13:32:35','2026-05-25 09:08:18','2026-06-09 13:32:35'),('3239710c-a317-4e31-b1df-72ed79201cf4','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:57:55','2026-06-09 15:57:55'),('3b0da45a-7b06-45e6-841e-ee6c6a76a076','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has been forwarded (For PTWG Endorsement) and requires your action.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 14:04:58','2026-05-20 14:02:50','2026-05-20 14:04:58'),('3c680e5b-0152-4a1b-8dbe-af84756b813d','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 16:13:12','2026-06-09 16:13:12'),('43e899db-81fc-4dea-98a2-061b9cf3f5ab','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 03:07:52','2026-03-31 06:32:52'),('44a14389-3d0a-482e-a660-aec068fad21b','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:50:48','2026-06-09 15:50:48'),('44d585d9-825b-4ae6-9094-e909109a3158','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":2,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:41:30','2026-03-28 11:28:35','2026-03-28 11:41:30'),('46b85342-040c-4d43-9a8c-b64da3c124c3','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Ad Referendum)) and requires your action.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:51:31','2026-03-31 02:51:10','2026-03-31 02:51:31'),('4850f5f7-003b-4d27-87eb-f2300095c0f2','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Re-Deliberation)) and requires your action.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-31 00:40:47','2026-03-30 02:33:00','2026-03-31 00:40:47'),('49c0735d-8007-4d25-a30b-348ae781c395','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-27 03:52:29','2026-03-27 03:52:09','2026-03-27 03:52:29'),('4a8faa1f-a775-4bbb-ac44-ed2f26514152','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 03:11:01','2026-03-31 06:32:52'),('4b63de2b-dd32-4824-9ae4-0c3b7d569ac9','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":11,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:30:14','2026-04-24 15:54:40','2026-05-20 13:30:14'),('4c10b5da-e894-4be1-a56c-3e9714edce29','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-27 03:52:29','2026-03-27 03:43:58','2026-03-27 03:52:29'),('4d8b4e86-19c5-4a98-82cb-ceb29a41ec0d','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Consolidated Policies has advanced and requires your approval.\",\"record_id\":30,\"module_slug\":\"consolidated_policies\"}','2026-05-20 13:30:14','2026-05-08 13:03:36','2026-05-20 13:30:14'),('4dbc5e53-5ef9-47e2-81d6-40e01a541098','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has been forwarded (For PTWG Endorsement) and requires your action.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 03:11:36','2026-03-31 06:32:52'),('4de3ad62-f4e4-4c74-a1ac-3aa43c5bf166','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:52:32','2026-06-09 15:52:32'),('509c82c8-de8e-49ff-9d47-6e136b9df531','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Re-Deliberation)) and requires your action.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:56:20','2026-06-09 15:56:20'),('50bc4406-6be0-45b9-a8d7-188414dc8e35','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":6,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:32:03','2026-03-31 02:12:05','2026-03-31 02:32:03'),('5105cf46-391e-426d-8ba9-680135719468','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":30,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:30:16','2026-05-08 13:01:24','2026-05-20 13:30:16'),('5279df22-8470-431c-a012-bca5a7f58f83','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":8,\"module_slug\":\"policy_proposals\"}','2026-04-23 11:10:24','2026-04-23 11:10:11','2026-04-23 11:10:24'),('5398d249-d527-4135-bb04-fcf07dc756e8','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-30 02:31:39','2026-03-31 06:32:52'),('57d74c5f-15e9-4238-a91d-2e48890c122d','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":4,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:44:38','2026-03-30 02:04:58','2026-03-30 02:44:38'),('594a08dd-b30f-425c-ac3a-f1c804eaa907','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":2,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:25:35','2026-03-28 11:23:46','2026-03-28 11:25:35'),('64ae211d-ad1c-4c32-a4e1-d31468a35da3','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has been forwarded (Forward to Proponent (For Ad Referendum)) and requires your action.\",\"record_id\":2,\"module_slug\":\"policy_proposals\"}','2026-03-31 00:40:47','2026-03-30 00:39:03','2026-03-31 00:40:47'),('68c462ba-23ae-4b53-afcf-3cdaf35731e5','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":13,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:30:14','2026-04-27 10:14:07','2026-05-20 13:30:14'),('69380ef3-3ffd-4e62-aef2-97f8f107b3e5','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Consolidated Policies has advanced and requires your approval.\",\"record_id\":30,\"module_slug\":\"consolidated_policies\"}','2026-05-20 13:30:14','2026-05-08 13:03:15','2026-05-20 13:30:14'),('6fe3c981-cfc4-4131-9850-8e143fae105c','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:32:17','2026-05-20 13:31:28','2026-05-20 13:32:17'),('71b028d4-48cc-47cd-9971-99c9c6d36db8','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 11:36:37','2026-06-09 11:36:37'),('725dac2d-83eb-4069-bcee-b5626f774a4c','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}','2026-06-09 13:32:35','2026-06-09 11:58:26','2026-06-09 13:32:35'),('764eb85f-3117-4c0d-a37a-303463f3c878','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-31 00:40:47','2026-03-30 02:16:24','2026-03-31 00:40:47'),('7cb3b70f-f2ed-4af6-b6f5-7a50acbb97c5','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 13:21:09','2026-04-23 12:59:22','2026-04-23 13:21:09'),('7da4c8c9-b8f1-42dd-bff4-acf8c490ae82','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":9,\"module_slug\":\"policy_proposals\"}','2026-04-23 12:09:13','2026-04-23 12:09:02','2026-04-23 12:09:13'),('8046c9a7-ff42-4b1b-b9db-9f85a5fe73bb','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been returned for revision.\",\"record_id\":20,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:23:39','2026-04-27 10:49:32','2026-05-20 13:23:39'),('80e0d0ae-3f04-4931-9f00-922efb590cda','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:37:51','2026-03-31 02:36:56','2026-03-31 02:37:51'),('87163924-19cd-46fb-9e7d-99204259e82f','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:36:26','2026-03-31 02:31:48','2026-03-31 02:36:26'),('881df41c-c562-4cd3-804a-576c83be2828','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 12:32:43','2026-04-23 12:32:26','2026-04-23 12:32:43'),('895094e2-0e23-4b39-ba60-9a1f4a4ba342','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 16:01:10','2026-06-09 16:01:10'),('8bbc1600-2839-429f-bc3d-393ce850506b','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 13:53:24','2026-06-09 13:53:24'),('8e54e31a-b3bf-4d8f-b56b-a9c767e39ff9','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:20:37','2026-03-30 02:15:55','2026-03-30 02:20:37'),('933147c6-3818-4551-b508-91f6d7a48dab','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 02:40:36','2026-03-31 06:32:52'),('94f44cf9-c27a-4d3f-b6e0-6fd5475f3963','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":8,\"module_slug\":\"policy_proposals\"}','2026-04-23 11:09:24','2026-04-23 11:09:11','2026-04-23 11:09:24'),('9564674e-cd1a-4b6c-84f8-9bb2ae230b4a','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:20:37','2026-03-30 02:16:42','2026-03-30 02:20:37'),('9b3a0e65-ccc3-4bd5-a261-8f721faf87c7','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has been forwarded (Proceed to Uploading the Signed Policy) and requires your action.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 14:21:10','2026-06-09 14:21:10'),('9bed01c1-b7e9-41eb-ba6f-424289cc75d8','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}','2026-06-09 15:01:25','2026-06-09 11:47:04','2026-06-09 15:01:25'),('9f8b2812-be64-4c62-a46e-161e4ce98049','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}','2026-06-09 15:01:17','2026-06-09 14:35:47','2026-06-09 15:01:17'),('a23ed5bf-161a-4087-80b3-e3e492a09c11','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":2,\"module_slug\":\"policy_proposals\"}','2026-03-30 01:41:58','2026-03-28 11:41:25','2026-03-30 01:41:58'),('a461c5a5-d57a-4f2a-ab84-e116119fb85f','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:19:22','2026-03-27 04:57:11','2026-03-28 11:19:22'),('a5ffb590-2ea7-46e1-b3bb-41c0ecc3f755','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-30 02:44:11','2026-03-31 06:32:52'),('aa83f088-f9d2-4b7f-9ab9-8f33adc423f9','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":3,\"module_slug\":\"policy_proposals\"}','2026-03-30 01:41:58','2026-03-30 01:41:33','2026-03-30 01:41:58'),('ab7d92bb-436d-4d7e-bc8a-684fcabc5e4a','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:18:49','2026-03-27 04:56:09','2026-03-28 11:18:49'),('acab898f-b17c-49e4-ab2d-76365cbceb1f','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 03:02:54','2026-03-31 02:52:39','2026-03-31 03:02:54'),('aceeef9b-1fab-4c34-b29c-57a2ca091a4e','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:33:18','2026-05-20 13:32:58','2026-05-20 13:33:18'),('ae2006e4-4088-4bf5-b339-42fdc52e9a50','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":4,\"module_slug\":\"policy_proposals\"}','2026-03-31 00:40:47','2026-03-30 02:08:50','2026-03-31 00:40:47'),('af0ca0dd-29b4-4093-8f8e-03748e122a47','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 12:33:29','2026-04-23 12:32:07','2026-04-23 12:33:29'),('b40ae45a-9fa3-4496-be7c-d90c064963d3','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":33,\"module_slug\":\"policy_proposals\"}','2026-06-09 15:01:25','2026-05-25 09:07:44','2026-06-09 15:01:25'),('b4ccb620-10e9-424f-9ed0-c99fc4ba582f','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 13:24:26','2026-04-23 13:16:20','2026-04-23 13:24:26'),('b4e10ecd-e4cc-4e19-a672-7cc0f5b6b136','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:18:49','2026-03-27 04:49:54','2026-03-28 11:18:49'),('bb5eb19b-6691-4f45-ae46-f645b9f383a7','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been completed.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 13:21:03','2026-04-23 13:18:07','2026-04-23 13:21:03'),('bdf7ea71-d0d3-41f7-97b6-c2b3d39d1320','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":3,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:44:38','2026-03-30 01:42:45','2026-03-30 02:44:38'),('becf4ea6-bd8b-4a65-8b17-02b4d44a0a4d','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":30,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:23:39','2026-05-08 13:02:44','2026-05-20 13:23:39'),('bf6fcf95-b09a-4aa9-a145-0b17b65a42de','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has been forwarded (For PTWG Endorsement) and requires your action.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 16:04:15','2026-06-09 16:04:15'),('c10480e1-144e-446b-86a3-6339b6df0f8d','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 02:45:03','2026-03-31 06:32:52'),('c121bbd4-d70c-43c0-9673-c90f739eb7d6','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:19:22','2026-03-27 04:53:46','2026-03-28 11:19:22'),('c30573e4-35bd-48bc-9053-96c2b1d392a9','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:52:14','2026-06-09 15:52:14'),('c353ad51-29c5-4321-98c3-b33182fc0faa','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 14:07:36','2026-06-09 14:07:36'),('c3f58bcf-945e-4c68-ad34-e7ecf0d5c6ff','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been completed.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}','2026-06-09 15:01:22','2026-06-09 14:26:13','2026-06-09 15:01:22'),('ca57ec40-8fea-498c-980f-8d09e6faf58e','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 14:34:38','2026-06-09 14:34:38'),('ca8c9ddc-67f3-468d-af68-dc028172f53d','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:19:22','2026-03-27 04:57:38','2026-03-28 11:19:22'),('cb20364c-0c31-48f7-8109-771e1b93a894','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":2,\"module_slug\":\"policy_proposals\"}','2026-03-30 01:41:58','2026-03-28 11:50:07','2026-03-30 01:41:58'),('cd7d441c-7d2b-4881-9055-d4a5d1c5047e','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 06:32:52','2026-03-31 03:09:17','2026-03-31 06:32:52'),('cf5ebcea-ce10-428c-b0e9-3547a7dd9cbe','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":21,\"module_slug\":\"policy_proposals\"}','2026-05-20 13:30:16','2026-04-27 10:48:57','2026-05-20 13:30:16'),('d15f4937-0ce8-4e7d-9da9-41a318d76695','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:36:16','2026-03-31 02:36:05','2026-03-31 02:36:16'),('d3584f80-4171-4dee-a76b-5b6e22785349','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 14:49:28','2026-06-09 14:49:28'),('d4415884-3a00-411c-919c-4a60f76e26d1','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been returned for revision.\",\"record_id\":8,\"module_slug\":\"policy_proposals\"}','2026-04-23 11:07:45','2026-04-23 11:07:29','2026-04-23 11:07:45'),('d69d9db0-d7fe-4e25-87fe-acdc441ed1b6','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has been forwarded (Proceed to Uploading the Signed Policy) and requires your action.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 14:09:17','2026-05-20 14:09:02','2026-05-20 14:09:17'),('dc435737-46ab-4c53-8573-ed641baf4821','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":32,\"module_slug\":\"policy_proposals\"}','2026-05-20 14:03:00','2026-05-20 14:01:33','2026-05-20 14:03:00'),('dd413ae3-313e-4f52-96ac-6e85b197e609','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":7,\"module_slug\":\"policy_proposals\"}','2026-03-31 02:42:24','2026-03-31 02:37:35','2026-03-31 02:42:24'),('e25f2a3c-83ac-4a26-987f-694dbd4f3fd6','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:24:37','2026-06-09 15:24:37'),('e2fd5c1f-20cd-4367-bc57-be03c512edb7','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":36,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 15:22:57','2026-06-09 15:22:57'),('e93aa077-8f56-45fe-9ea5-933454bfef1f','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":9,\"module_slug\":\"policy_proposals\"}','2026-04-23 12:22:34','2026-04-23 12:22:27','2026-04-23 12:22:34'),('e9510b95-1ecb-4e93-864a-ca2dfbdc59c6','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 12:33:43','2026-04-23 12:33:11','2026-04-23 12:33:43'),('eb33212f-9f1c-4680-b9f2-a290e0855170','App\\Notifications\\DynamicNotification','App\\Models\\User',4,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":5,\"module_slug\":\"policy_proposals\"}','2026-03-30 02:44:38','2026-03-30 02:16:10','2026-03-30 02:44:38'),('ec2cda56-24f8-4ab8-a3d9-aaca259b116d','App\\Notifications\\DynamicNotification','App\\Models\\User',3,'{\"message\":\"A record in Policy Proposals has advanced and requires your approval.\",\"record_id\":37,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 16:15:38','2026-06-09 16:15:38'),('f6f0599e-cae7-435d-b183-8afb273b1a94','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has been forwarded (For PTWG Endorsement) and requires your action.\",\"record_id\":10,\"module_slug\":\"policy_proposals\"}','2026-04-23 13:24:26','2026-04-23 13:09:15','2026-04-23 13:24:26'),('f83b2c94-b2f3-4099-8784-66462ed4dba4','App\\Notifications\\DynamicNotification','App\\Models\\User',5,'{\"message\":\"A record in Policy Proposals has been forwarded (For PTWG Endorsement) and requires your action.\",\"record_id\":35,\"module_slug\":\"policy_proposals\"}',NULL,'2026-06-09 13:59:33','2026-06-09 13:59:33'),('fa47c501-7d18-4b1c-88ee-f0167d740723','App\\Notifications\\DynamicNotification','App\\Models\\User',2,'{\"message\":\"Your record in Policy Proposals has been approved.\",\"record_id\":1,\"module_slug\":\"policy_proposals\"}','2026-03-28 11:41:30','2026-03-27 05:30:53','2026-03-28 11:41:30');
/*!40000 ALTER TABLE `jea_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_password_reset_tokens`
--

DROP TABLE IF EXISTS `jea_password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_password_reset_tokens`
--

LOCK TABLES `jea_password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `jea_password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_permissions`
--

DROP TABLE IF EXISTS `jea_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_permissions`
--

LOCK TABLES `jea_permissions` WRITE;
/*!40000 ALTER TABLE `jea_permissions` DISABLE KEYS */;
INSERT INTO `jea_permissions` VALUES (1,'view-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(2,'create-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(3,'edit-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(4,'delete-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(5,'change-status-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(6,'review-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(7,'approve-policy_proposals','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(15,'view-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26'),(16,'create-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26'),(17,'edit-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26'),(18,'delete-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26'),(19,'change-status-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26'),(20,'review-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26'),(21,'approve-consolidated_policies','web','2026-03-27 05:31:26','2026-03-27 05:31:26');
/*!40000 ALTER TABLE `jea_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_personal_access_tokens`
--

DROP TABLE IF EXISTS `jea_personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_personal_access_tokens_token_unique` (`token`),
  KEY `jea_personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `jea_personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=693 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_personal_access_tokens`
--

LOCK TABLES `jea_personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `jea_personal_access_tokens` DISABLE KEYS */;
INSERT INTO `jea_personal_access_tokens` VALUES (606,'App\\Models\\User',3,'editor-35-draft_policy','8489a3daecbea60d08813fd12231680a112d5b497a07127ae033a3e1f4741947','[\"editor:read\",\"editor:write\"]','2026-06-09 14:22:23','2026-06-09 22:21:46','2026-06-09 14:21:46','2026-06-09 14:22:23'),(607,'App\\Models\\User',3,'editor-35-draft_policy','456efb9017c7f876cb92fc97fd08b81c8c005dd87e6d12b3dd43f616b377f4c9','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 22:26:02','2026-06-09 14:26:02','2026-06-09 14:26:02'),(608,'App\\Models\\User',3,'editor-35-draft_policy','430e1de692b0977138ba5db56b76b132ae50f705fa4b70f4e0ae5f79c9dc7157','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 22:26:10','2026-06-09 14:26:10','2026-06-09 14:26:10'),(609,'App\\Models\\User',3,'editor-35-draft_policy','5660975e0711038b01530e2fa799d7fdab67544a48331499ad11e90399b21a90','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 22:26:10','2026-06-09 14:26:10','2026-06-09 14:26:10'),(610,'App\\Models\\User',3,'editor-35-draft_policy','13d36a0ccfa9e480360ea24ea09392a1911745c938e9107bad6f4ba2a82e4645','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 22:26:13','2026-06-09 14:26:13','2026-06-09 14:26:13'),(631,'App\\Models\\User',4,'editor-35-draft_policy','5e7e51c7209acd37d9a8cca6209e55bd4dc7cc0c787f03dab1812cd978c9d186','[\"editor:read\",\"editor:write\"]','2026-06-09 14:34:47','2026-06-09 22:34:46','2026-06-09 14:34:46','2026-06-09 14:34:47'),(637,'App\\Models\\User',3,'editor-36-draft_policy','a64f4a8cff0a12bbd041110b8e7c073c62ff00f0bd4bd08b92a323f6c45505b3','[\"editor:read\",\"editor:write\"]','2026-06-09 15:16:03','2026-06-09 22:49:36','2026-06-09 14:49:36','2026-06-09 15:16:03'),(638,'App\\Models\\User',3,'editor-36-draft_policy','bd376894b648eadc8ae751ff73793cb226206187704e2adeb707b9c8c6a26ea1','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 23:22:54','2026-06-09 15:22:54','2026-06-09 15:22:54'),(639,'App\\Models\\User',3,'editor-36-draft_policy','ad2c82248112f9f746ac2270a617c813a82ceea67beb81248143d099941f9ca4','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 23:22:57','2026-06-09 15:22:57','2026-06-09 15:22:57'),(640,'App\\Models\\User',3,'editor-36-draft_policy','9e072138e8f67f807685989a895e135665f588896d3c82832582bcbc325a1470','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 23:23:17','2026-06-09 15:23:17','2026-06-09 15:23:17'),(641,'App\\Models\\User',3,'editor-36-draft_policy','25757ff39fdfcc813a13c688a3d95fd11c9572548e30d9f0af0755f101eccd29','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 23:23:17','2026-06-09 15:23:17','2026-06-09 15:23:17'),(642,'App\\Models\\User',3,'editor-36-draft_policy','081336af8042405f95bd3853c721db020681f3976eef8ade368035ccac4e7957','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-09 23:24:05','2026-06-09 15:24:05','2026-06-09 15:24:05'),(656,'App\\Models\\User',4,'editor-36-draft_policy','0cdc450824d4e78e9d6fbd1fc1863c5e9a25805d4e35ed3bdbc01a45032b4ef5','[\"editor:read\",\"editor:write\"]','2026-06-09 15:51:10','2026-06-09 23:51:10','2026-06-09 15:51:10','2026-06-09 15:51:10'),(671,'App\\Models\\User',4,'editor-37-draft_policy','cdf6c79ba560d9583cbd3ebc13738e496ef67011373413599c0d2771201907b4','[\"editor:read\",\"editor:write\"]','2026-06-09 16:53:25','2026-06-09 23:59:12','2026-06-09 15:59:12','2026-06-09 16:53:25'),(676,'App\\Models\\User',5,'editor-35-draft_policy','804abaa5d4d27ff7dc0c09217b5a73cf508a2513df1135bc6ab1e1db8e8e9947','[\"editor:read\",\"editor:write\"]','2026-06-09 16:53:25','2026-06-10 00:08:59','2026-06-09 16:08:59','2026-06-09 16:53:25'),(677,'App\\Models\\User',5,'editor-37-draft_policy','cb7cea629a46010eab21e0b3608df1b83fca0b38345ef3683753ec9281586695','[\"editor:read\",\"editor:write\"]','2026-06-09 16:53:25','2026-06-10 00:09:06','2026-06-09 16:09:06','2026-06-09 16:53:25'),(678,'App\\Models\\User',5,'editor-37-draft_policy','26acbeca62261fab70a53797c9e8c3cf27d3eba40102463d65390c4aec7808fd','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:13:08','2026-06-09 16:13:08','2026-06-09 16:13:08'),(679,'App\\Models\\User',5,'editor-37-draft_policy','5efc491a69bbf2bfd8c0dee462b15bd076ade508e20f52bd191d2a6fffb1b9e3','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:13:08','2026-06-09 16:13:08','2026-06-09 16:13:08'),(680,'App\\Models\\User',5,'editor-37-draft_policy','b904c3d37efc28d0baf03d5298405077d7e30fdafe0b758a2b161cbf2778191a','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:13:12','2026-06-09 16:13:12','2026-06-09 16:13:12'),(681,'App\\Models\\User',5,'editor-37-draft_policy','c3c64b4388a3a7e2e4340dc78e62fb8c7f142d247d4bc05110ef84a329acd6fc','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:14:50','2026-06-09 16:14:50','2026-06-09 16:14:50'),(682,'App\\Models\\User',5,'editor-37-draft_policy','1e5b285149f5cce39073a81985c8895b58d6ef4eb28a465e465fa8b2b95840f9','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:15:09','2026-06-09 16:15:09','2026-06-09 16:15:09'),(683,'App\\Models\\User',5,'editor-37-draft_policy','5b4879dd82037c833d81c535126d2aff67fa3eba31269974a4e0bb992f43d12e','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:15:09','2026-06-09 16:15:09','2026-06-09 16:15:09'),(684,'App\\Models\\User',5,'editor-37-draft_policy','f95aaa862a39f2018b9315a86eb178d03e316f1452325930a63d12e2a0ce7167','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:15:17','2026-06-09 16:15:17','2026-06-09 16:15:17'),(687,'App\\Models\\User',3,'editor-37-draft_policy','1808dc36e62c522838603ffd424f6257c0b74b7099d8ad6eea10f4a108d122ed','[\"editor:read\",\"editor:write\"]','2026-06-09 16:53:25','2026-06-10 00:15:49','2026-06-09 16:15:49','2026-06-09 16:53:25'),(688,'App\\Models\\User',3,'editor-37-draft_policy','053f609e8a61883d0e28084e509301c912bd6788c5d5d4c8efdc2a3fd488b5e7','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:16:07','2026-06-09 16:16:07','2026-06-09 16:16:07'),(689,'App\\Models\\User',3,'editor-37-draft_policy','e453bf5ea8b9a10aedb5d97316fd5eef96eda5f9ecb59786d846d0a6b7de0885','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 00:16:19','2026-06-09 16:16:19','2026-06-09 16:16:19'),(690,'App\\Models\\User',1,'editor-new-draft_policy','f86a9edf2b2e5d10bc6712267b393ea2c587e032d5a33ea08f27ee2a3e04b008','[\"editor:read\",\"editor:write\"]',NULL,'2026-06-10 23:39:34','2026-06-10 15:39:34','2026-06-10 15:39:34'),(691,'App\\Models\\User',1,'editor-37-draft_policy','89e116a6e3dab89fb06246b7397946b17f9473b9cfd6249dcd9dbe475553a639','[\"editor:read\",\"editor:write\"]','2026-06-10 15:42:14','2026-06-10 23:40:23','2026-06-10 15:40:23','2026-06-10 15:42:14'),(692,'App\\Models\\User',2,'editor-35-draft_policy','80e7ee3847cd4f1a77fb04ec5df1d3682c4b9e2fed3a6e334928f5b47c6c9123','[\"editor:read\",\"editor:write\"]','2026-06-10 16:01:30','2026-06-11 00:01:29','2026-06-10 16:01:29','2026-06-10 16:01:30');
/*!40000 ALTER TABLE `jea_personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_record_approvals`
--

DROP TABLE IF EXISTS `jea_record_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_record_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `stage_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_record_approvals_record_id_foreign` (`record_id`),
  KEY `jea_record_approvals_stage_id_foreign` (`stage_id`),
  KEY `jea_record_approvals_user_id_foreign` (`user_id`),
  CONSTRAINT `jea_record_approvals_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_record_approvals_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `jea_workflow_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_record_approvals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `jea_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_record_approvals`
--

LOCK TABLES `jea_record_approvals` WRITE;
/*!40000 ALTER TABLE `jea_record_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_record_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_record_comments`
--

DROP TABLE IF EXISTS `jea_record_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_record_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_record_comments_record_id_foreign` (`record_id`),
  KEY `jea_record_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `jea_record_comments_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_record_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `jea_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_record_comments`
--

LOCK TABLES `jea_record_comments` WRITE;
/*!40000 ALTER TABLE `jea_record_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_record_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_record_histories`
--

DROP TABLE IF EXISTS `jea_record_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_record_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `changes_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_record_histories_record_id_foreign` (`record_id`),
  KEY `jea_record_histories_user_id_foreign` (`user_id`),
  KEY `jea_record_histories_created_at_index` (`created_at`),
  CONSTRAINT `jea_record_histories_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_record_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `jea_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_record_histories`
--

LOCK TABLES `jea_record_histories` WRITE;
/*!40000 ALTER TABLE `jea_record_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_record_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_records`
--

DROP TABLE IF EXISTS `jea_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint unsigned NOT NULL,
  `data` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Submitted',
  `current_stage_id` bigint unsigned DEFAULT NULL,
  `stage_entered_at` timestamp NULL DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_records_module_id_foreign` (`module_id`),
  KEY `jea_records_created_by_foreign` (`created_by`),
  KEY `jea_records_updated_by_foreign` (`updated_by`),
  KEY `jea_records_current_stage_id_foreign` (`current_stage_id`),
  KEY `jea_records_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `jea_records_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `jea_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `jea_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_records_current_stage_id_foreign` FOREIGN KEY (`current_stage_id`) REFERENCES `jea_workflow_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_records_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `jea_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_records_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `jea_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_records`
--

LOCK TABLES `jea_records` WRITE;
/*!40000 ALTER TABLE `jea_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_role_has_permissions`
--

DROP TABLE IF EXISTS `jea_role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `jea_role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `jea_role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `jea_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `jea_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_role_has_permissions`
--

LOCK TABLES `jea_role_has_permissions` WRITE;
/*!40000 ALTER TABLE `jea_role_has_permissions` DISABLE KEYS */;
INSERT INTO `jea_role_has_permissions` VALUES (1,2),(2,2),(3,2),(7,3),(15,3),(6,4),(15,4),(15,5);
/*!40000 ALTER TABLE `jea_role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_roles`
--

DROP TABLE IF EXISTS `jea_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_roles`
--

LOCK TABLES `jea_roles` WRITE;
/*!40000 ALTER TABLE `jea_roles` DISABLE KEYS */;
INSERT INTO `jea_roles` VALUES (1,'super admin','web','2026-03-27 01:07:40','2026-03-27 01:07:40'),(2,'Proponent','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(3,'TRC Secretariat','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(4,'Reviewer','web','2026-03-27 02:20:51','2026-03-27 02:20:51'),(5,'Receiving/Releasing','web','2026-04-23 10:03:35','2026-04-23 10:03:35');
/*!40000 ALTER TABLE `jea_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_sessions`
--

DROP TABLE IF EXISTS `jea_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_sessions_user_id_index` (`user_id`),
  KEY `jea_sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_sessions`
--

LOCK TABLES `jea_sessions` WRITE;
/*!40000 ALTER TABLE `jea_sessions` DISABLE KEYS */;
INSERT INTO `jea_sessions` VALUES ('1aj2e0RkpMWhY137amsbJpox0MtD4MJ6Q4ALgjmR',1,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','eyJfdG9rZW4iOiJDMFJ4cDhrWkE3V2xqcndQMFBxY3RxcEtHbndmdjBzRmFmVnFPNXV0IiwidXJsIjpbXSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwODBcL2FwcFwvcG9saWN5X3Byb3Bvc2FscyIsInJvdXRlIjoiZHluYW1pYy5pbmRleCJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1781623718),('KC5d5dGDMEcFIRHJYIRRS5WgWlWWuZT3Jk2XO8o0',1,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','eyJfdG9rZW4iOiJ1emtxbGFpM1Y0WTdTSHdtOVZ3OGdQNzVDUHpYOTkzSlV0NERPNHB0IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDgwXC9idWlsZGVyXC9hcHByb3ZhbC1xdWV1ZSIsInJvdXRlIjoiYnVpbGRlci5hcHByb3ZhbC5xdWV1ZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1781171901),('KktjF85uBdNKUqqjeN2n3PJCGk4oJlqpoF5OQSjT',1,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','eyJfdG9rZW4iOiJ5VHFMWkJmWUdoSHZDN0JSR3owV1VSblBKeUF5VWFPaUsyRjRCOEhvIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDgwXC9idWlsZGVyXC93b3JrZmxvdy1zdGFnZXNcLzEiLCJyb3V0ZSI6ImJ1aWxkZXIud29ya2Zsb3cuc3RhZ2VzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1781658183),('OvFgRf9eNEMMXYboL9EnGH3PutHi0iMjKSN3PWGv',1,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','eyJfdG9rZW4iOiJOWHVvb3h3VDhkbVVQdlNHU0xwVDNvNktRVk9zdWZaVzcwdGtMR1Y0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDgwXC9idWlsZGVyXC93b3JrZmxvdy1zdGFnZXNcLzEiLCJyb3V0ZSI6ImJ1aWxkZXIud29ya2Zsb3cuc3RhZ2VzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1781611427);
/*!40000 ALTER TABLE `jea_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_text_editor_comments`
--

DROP TABLE IF EXISTS `jea_text_editor_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_text_editor_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `field_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `quoted_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_text_editor_comments_comment_id_unique` (`comment_id`),
  KEY `jea_text_editor_comments_user_id_foreign` (`user_id`),
  KEY `jea_text_editor_comments_record_id_field_slug_index` (`record_id`,`field_slug`),
  KEY `jea_text_editor_comments_parent_id_index` (`parent_id`),
  CONSTRAINT `jea_text_editor_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `jea_text_editor_comments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_text_editor_comments_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_text_editor_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `jea_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_text_editor_comments`
--

LOCK TABLES `jea_text_editor_comments` WRITE;
/*!40000 ALTER TABLE `jea_text_editor_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_text_editor_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_text_editor_documents`
--

DROP TABLE IF EXISTS `jea_text_editor_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_text_editor_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `field_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `binary_state` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_text_editor_documents_record_id_field_slug_unique` (`record_id`,`field_slug`),
  CONSTRAINT `jea_text_editor_documents_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_text_editor_documents`
--

LOCK TABLES `jea_text_editor_documents` WRITE;
/*!40000 ALTER TABLE `jea_text_editor_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_text_editor_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_text_editor_histories`
--

DROP TABLE IF EXISTS `jea_text_editor_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_text_editor_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `field_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `action` enum('insert','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_number` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `jea_text_editor_histories_record_id_foreign` (`record_id`),
  KEY `jea_text_editor_histories_user_id_foreign` (`user_id`),
  CONSTRAINT `jea_text_editor_histories_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_text_editor_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `jea_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_text_editor_histories`
--

LOCK TABLES `jea_text_editor_histories` WRITE;
/*!40000 ALTER TABLE `jea_text_editor_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_text_editor_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_text_editor_reviews`
--

DROP TABLE IF EXISTS `jea_text_editor_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_text_editor_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `field_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_text_editor_reviews_record_id_field_slug_user_id_unique` (`record_id`,`field_slug`,`user_id`),
  KEY `jea_text_editor_reviews_user_id_foreign` (`user_id`),
  CONSTRAINT `jea_text_editor_reviews_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `jea_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jea_text_editor_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `jea_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_text_editor_reviews`
--

LOCK TABLES `jea_text_editor_reviews` WRITE;
/*!40000 ALTER TABLE `jea_text_editor_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_text_editor_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_users`
--

DROP TABLE IF EXISTS `jea_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `theme` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'indigo',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jea_users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_users`
--

LOCK TABLES `jea_users` WRITE;
/*!40000 ALTER TABLE `jea_users` DISABLE KEYS */;
INSERT INTO `jea_users` VALUES (1,'Admin','admin@prms.local','indigo',1,'2026-04-23 01:11:22','$2y$12$1RknNfOuST1EU/ognT2vWuWuXqdVLfetGJyoGukV1KKUL2XJPVB6i',NULL,NULL,'qTnwFwA2YXBBJAubrAdpmS5utTZ9tfQ8rdMCLSrTb0HNYy7UIHcI90cCKnwA','2026-03-27 01:07:40','2026-05-19 18:05:37',NULL),(2,'Proponent User','proponent@prms.local','indigo',1,'2026-03-27 02:20:51','$2y$12$J8vQ.XCOXE65b44sZbPn7uApHrOseHWYdPn/rszzY9MdSQRpADrjO',NULL,NULL,NULL,'2026-03-27 02:20:51','2026-03-27 02:20:51',NULL),(3,'TRC Secretariat User','secretariat@prms.local','indigo',1,'2026-03-27 02:20:51','$2y$12$eoZZDar2zR8r7ZRbK2wvw.XwPLvbnSRofFob0JnqGXNlefpLLAuiu',NULL,NULL,'uVManFPcXnG3A2g6Cbm65AOqmMJsqWJT8QPrjdQ4lmshuWc8oBgpVpBruZOp','2026-03-27 02:20:51','2026-06-01 11:48:56',NULL),(4,'Reviewer User','reviewer@prms.local','indigo',1,'2026-03-27 02:20:51','$2y$12$GIlvqt5CtQ.iJ/aNWc4ZIO.YgzBOpdEBRn2MmXct8VNtxr8Q0LSOS',NULL,NULL,'eJQN03T2eK4tYzBwop43vfFScflrXrS5K6ngOUwDloXeYo9DR7GNTIxWdsdM','2026-03-27 02:20:51','2026-03-27 02:20:51',NULL),(5,'Office of the Director','od@prms.local','indigo',1,NULL,'$2y$12$NMMq/jJwHaQ.4RK5ZcuxTupbHdqY39pcDRvqAd6gPjEac/5.hsxPC',NULL,NULL,NULL,'2026-04-23 10:04:29','2026-04-23 10:08:30',NULL);
/*!40000 ALTER TABLE `jea_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_webhook_logs`
--

DROP TABLE IF EXISTS `jea_webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_webhook_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` bigint unsigned NOT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `response_code` int DEFAULT NULL,
  `response_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `jea_webhook_logs_webhook_id_foreign` (`webhook_id`),
  CONSTRAINT `jea_webhook_logs_webhook_id_foreign` FOREIGN KEY (`webhook_id`) REFERENCES `jea_webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_webhook_logs`
--

LOCK TABLES `jea_webhook_logs` WRITE;
/*!40000 ALTER TABLE `jea_webhook_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_webhook_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_webhooks`
--

DROP TABLE IF EXISTS `jea_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_id` bigint unsigned DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `events` json NOT NULL,
  `secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_webhooks_module_id_foreign` (`module_id`),
  CONSTRAINT `jea_webhooks_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `jea_modules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_webhooks`
--

LOCK TABLES `jea_webhooks` WRITE;
/*!40000 ALTER TABLE `jea_webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_webhooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_workflow_actions`
--

DROP TABLE IF EXISTS `jea_workflow_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_workflow_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` bigint unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_workflow_actions_workflow_id_foreign` (`workflow_id`),
  CONSTRAINT `jea_workflow_actions_workflow_id_foreign` FOREIGN KEY (`workflow_id`) REFERENCES `jea_workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_workflow_actions`
--

LOCK TABLES `jea_workflow_actions` WRITE;
/*!40000 ALTER TABLE `jea_workflow_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_workflow_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_workflow_stage_templates`
--

DROP TABLE IF EXISTS `jea_workflow_stage_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_workflow_stage_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stages_json` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_workflow_stage_templates`
--

LOCK TABLES `jea_workflow_stage_templates` WRITE;
/*!40000 ALTER TABLE `jea_workflow_stage_templates` DISABLE KEYS */;
INSERT INTO `jea_workflow_stage_templates` VALUES (1,'Policy Proposals ΓÇö Apr 27, 2026','[{\"name\": \"Initial Document Review\", \"order\": 10, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": true, \"is_final_approval\": false, \"stage_fields_json\": null}, {\"name\": \"Review  Draft Policy (Ad Referendum)\", \"order\": 20, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Reviewer\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null}, {\"name\": \"Revise Draft Policy\", \"order\": 25, \"allow_edit\": true, \"stage_type\": \"review\", \"approver_role\": \"Proponent\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null}, {\"name\": \"TRC Scheduling\", \"order\": 27, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"Date Scheduled\", \"slug\": \"date_scheduled\", \"type\": \"date\", \"is_required\": true, \"options_json\": null}]}, {\"name\": \"TRC Deliberation\", \"order\": 30, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"Forward to Proponent (For Ad Referendum)\", \"stage_id\": \"11\"}, {\"label\": \"Forward to Proponent (For Re-Deliberation)\", \"stage_id\": \"7\"}, {\"label\": \"For PTWG Endorsement\", \"stage_id\": \"8\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"TRC Minutes of Meeting\", \"slug\": \"trc_minutes_of_meeting\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]}, {\"name\": \"PTWG Endorsement\", \"order\": 36, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"Receiving/Releasing\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"Endorsement Letter\", \"slug\": \"endorsement_letter\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]}, {\"name\": \"Endorsed (Waiting for PTWG)\", \"order\": 38, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Receiving/Releasing\", \"branches_json\": [{\"label\": \"Forward to Proponent (For Re-Deliberation)\", \"stage_id\": \"7\"}, {\"label\": \"Proceed to Uploading the Signed Policy\", \"stage_id\": \"9\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"PTWG Return Document\", \"slug\": \"ptwg_return_document\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]}, {\"name\": \"Policy Signed and Ready to Upload\", \"order\": 40, \"allow_edit\": false, \"stage_type\": \"approval\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": true, \"stage_fields_json\": [{\"name\": \"Signed Policy\", \"slug\": \"signed_policy\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]}, {\"name\": \"Revise Draft Policy\", \"order\": 42, \"allow_edit\": true, \"stage_type\": \"review\", \"approver_role\": \"Proponent\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null}, {\"name\": \"Ad Referendum (Re-Routed)\", \"order\": 44, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Reviewer\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null}, {\"name\": \"From Ad Referendum (Re-Routed)\", \"order\": 50, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"For PTWG Endorsement\", \"stage_id\": \"8\"}, {\"label\": \"Forward to Proponent (For Ad Referendum)\", \"stage_id\": \"11\"}, {\"label\": \"Forward to Proponent (For Re-Deliberation)\", \"stage_id\": \"7\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null}]','2026-04-27 09:13:07','2026-04-27 09:13:07'),(2,'Policy Proposals ΓÇö May 08, 2026','[{\"name\": \"Initial Document Review\", \"order\": 10, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": true, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": [{\"type\": \"specific_email\", \"value\": \"webmaster@bmb.gov.ph\"}]}, {\"name\": \"Review  Draft Policy (Ad Referendum)\", \"order\": 20, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Reviewer\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Revise Draft Policy\", \"order\": 25, \"allow_edit\": true, \"stage_type\": \"review\", \"approver_role\": \"Proponent\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"TRC Scheduling\", \"order\": 27, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"Date Scheduled\", \"slug\": \"date_scheduled\", \"type\": \"date\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": [{\"field_slug\": \"date_scheduled\", \"recipients\": [{\"type\": \"specific_email\", \"value\": \"webmaster@bmb.gov.ph\"}], \"days_before\": 1}], \"notify_on_enter_json\": null}, {\"name\": \"TRC Deliberation\", \"order\": 30, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"Forward to Proponent (For Ad Referendum)\", \"stage_id\": \"11\"}, {\"label\": \"Forward to Proponent (For Re-Deliberation)\", \"stage_id\": \"7\"}, {\"label\": \"For PTWG Endorsement\", \"stage_id\": \"8\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"TRC Minutes of Meeting\", \"slug\": \"trc_minutes_of_meeting\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"PTWG Endorsement\", \"order\": 36, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"Receiving/Releasing\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"Endorsement Letter\", \"slug\": \"endorsement_letter\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Endorsed (Waiting for PTWG)\", \"order\": 38, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Receiving/Releasing\", \"branches_json\": [{\"label\": \"Forward to Proponent (For Re-Deliberation)\", \"stage_id\": \"7\"}, {\"label\": \"Proceed to Uploading the Signed Policy\", \"stage_id\": \"9\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"PTWG Return Document\", \"slug\": \"ptwg_return_document\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Policy Signed and Ready to Upload\", \"order\": 40, \"allow_edit\": false, \"stage_type\": \"approval\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": true, \"stage_fields_json\": [{\"name\": \"Signed Policy\", \"slug\": \"signed_policy\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Revise Draft Policy\", \"order\": 42, \"allow_edit\": true, \"stage_type\": \"review\", \"approver_role\": \"Proponent\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Ad Referendum (Re-Routed)\", \"order\": 44, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Reviewer\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"From Ad Referendum (Re-Routed)\", \"order\": 50, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"For PTWG Endorsement\", \"stage_id\": \"8\"}, {\"label\": \"Forward to Proponent (For Ad Referendum)\", \"stage_id\": \"11\"}, {\"label\": \"Forward to Proponent (For Re-Deliberation)\", \"stage_id\": \"7\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}]','2026-05-08 13:56:10','2026-05-08 13:56:10'),(4,'Policy Proposals ΓÇö Jun 16, 2026','[{\"name\": \"Initial Document Review\", \"order\": 10, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": true, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": [{\"type\": \"specific_email\", \"value\": \"webmaster@bmb.gov.ph\"}]}, {\"name\": \"Review  Draft Policy (Ad Referendum)\", \"order\": 20, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Reviewer\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Revise Draft Policy\", \"order\": 25, \"allow_edit\": true, \"stage_type\": \"review\", \"approver_role\": \"Proponent\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": 10, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Consolidate Comments\", \"order\": 26, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"Ad referendum\", \"stage_id\": \"16\"}, {\"label\": \"TRC Scheduling\", \"stage_id\": \"18\"}, {\"label\": \"PTWG\", \"stage_id\": \"20\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"TRC Scheduling\", \"order\": 27, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"Date Scheduled\", \"slug\": \"date_scheduled\", \"type\": \"date\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": [{\"field_slug\": \"date_scheduled\", \"recipients\": [{\"type\": \"specific_email\", \"value\": \"webmaster@bmb.gov.ph\"}], \"days_before\": 1}], \"notify_on_enter_json\": null}, {\"name\": \"TRC Deliberation\", \"order\": 30, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"Ad referendum\", \"stage_id\": \"16\"}, {\"label\": \"TRC Scheduling\", \"stage_id\": \"18\"}, {\"label\": \"PTWG\", \"stage_id\": \"20\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"TRC Minutes of Meeting\", \"slug\": \"trc_minutes_of_meeting\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"PTWG Endorsement\", \"order\": 36, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"Endorsement Letter\", \"slug\": \"endorsement_letter\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Endorsed (Waiting for PTWG)\", \"order\": 38, \"allow_edit\": false, \"stage_type\": \"none\", \"approver_role\": \"Receiving/Releasing\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": [{\"name\": \"PTWG Return Document\", \"slug\": \"ptwg_return_document\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"PTWG to Secretariat\", \"order\": 39, \"allow_edit\": false, \"stage_type\": \"review\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": [{\"label\": \"TRC Scheduling\", \"stage_id\": \"18\"}], \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": false, \"stage_fields_json\": null, \"date_reminders_json\": null, \"notify_on_enter_json\": null}, {\"name\": \"Policy Signed and Ready to Upload\", \"order\": 40, \"allow_edit\": false, \"stage_type\": \"approval\", \"approver_role\": \"TRC Secretariat\", \"branches_json\": null, \"reviewer_role\": null, \"default_status\": null, \"auto_advance_days\": null, \"has_return_button\": false, \"is_final_approval\": true, \"stage_fields_json\": [{\"name\": \"Signed Policy\", \"slug\": \"signed_policy\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}], \"date_reminders_json\": null, \"notify_on_enter_json\": null}]','2026-06-16 23:20:12','2026-06-16 23:20:12');
/*!40000 ALTER TABLE `jea_workflow_stage_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_workflow_stages`
--

DROP TABLE IF EXISTS `jea_workflow_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_workflow_stages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` smallint unsigned NOT NULL DEFAULT '0',
  `approver_role_id` bigint unsigned DEFAULT NULL,
  `reviewer_role_id` bigint unsigned DEFAULT NULL,
  `requires_all_approvers` tinyint(1) NOT NULL DEFAULT '0',
  `is_final_approval` tinyint(1) NOT NULL DEFAULT '0',
  `has_return_button` tinyint(1) NOT NULL DEFAULT '1',
  `allow_edit` tinyint(1) NOT NULL DEFAULT '1',
  `default_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stage_fields_json` json DEFAULT NULL,
  `notify_on_enter_json` json DEFAULT NULL,
  `date_reminders_json` json DEFAULT NULL,
  `stage_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approval',
  `auto_advance_days` smallint unsigned DEFAULT NULL,
  `branch_ad_referendum_stage_id` bigint unsigned DEFAULT NULL,
  `branch_trc_stage_id` bigint unsigned DEFAULT NULL,
  `branches_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_workflow_stages_module_id_foreign` (`module_id`),
  KEY `jea_workflow_stages_approver_role_id_foreign` (`approver_role_id`),
  KEY `jea_workflow_stages_branch_ad_referendum_stage_id_foreign` (`branch_ad_referendum_stage_id`),
  KEY `jea_workflow_stages_branch_trc_stage_id_foreign` (`branch_trc_stage_id`),
  CONSTRAINT `jea_workflow_stages_approver_role_id_foreign` FOREIGN KEY (`approver_role_id`) REFERENCES `jea_roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_workflow_stages_branch_ad_referendum_stage_id_foreign` FOREIGN KEY (`branch_ad_referendum_stage_id`) REFERENCES `jea_workflow_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_workflow_stages_branch_trc_stage_id_foreign` FOREIGN KEY (`branch_trc_stage_id`) REFERENCES `jea_workflow_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jea_workflow_stages_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `jea_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_workflow_stages`
--

LOCK TABLES `jea_workflow_stages` WRITE;
/*!40000 ALTER TABLE `jea_workflow_stages` DISABLE KEYS */;
INSERT INTO `jea_workflow_stages` VALUES (15,1,'Initial Document Review',10,3,NULL,0,0,1,0,NULL,NULL,'[{\"type\": \"specific_email\", \"value\": \"webmaster@bmb.gov.ph\"}]',NULL,'review',NULL,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 22:53:30'),(16,1,'Review  Draft Policy (Ad Referendum)',20,4,NULL,0,0,0,0,NULL,NULL,NULL,NULL,'none',10,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 22:53:30'),(17,1,'Revise Draft Policy',25,2,NULL,0,0,0,1,NULL,NULL,NULL,NULL,'review',10,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 22:53:30'),(18,1,'TRC Scheduling',27,3,NULL,0,0,0,0,NULL,'[{\"name\": \"Date Scheduled\", \"slug\": \"date_scheduled\", \"type\": \"date\", \"is_required\": true, \"options_json\": null}]',NULL,'[{\"field_slug\": \"date_scheduled\", \"recipients\": [{\"type\": \"specific_email\", \"value\": \"webmaster@bmb.gov.ph\"}], \"days_before\": 1}]','review',NULL,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 22:53:30'),(19,1,'TRC Deliberation',30,3,NULL,0,0,0,0,NULL,'[{\"name\": \"TRC Minutes of Meeting\", \"slug\": \"trc_minutes_of_meeting\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]',NULL,NULL,'none',NULL,NULL,NULL,'[{\"label\": \"Ad referendum\", \"stage_id\": \"16\"}, {\"label\": \"TRC Scheduling\", \"stage_id\": \"18\"}, {\"label\": \"PTWG\", \"stage_id\": \"20\"}]','2026-06-16 22:53:30','2026-06-16 23:11:21'),(20,1,'PTWG Endorsement',36,3,NULL,0,0,0,0,NULL,'[{\"name\": \"Endorsement Letter\", \"slug\": \"endorsement_letter\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]',NULL,NULL,'review',NULL,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 23:12:13'),(21,1,'Endorsed (Waiting for PTWG)',38,5,NULL,0,0,0,0,NULL,'[{\"name\": \"PTWG Return Document\", \"slug\": \"ptwg_return_document\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]',NULL,NULL,'none',NULL,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 23:16:14'),(22,1,'Policy Signed and Ready to Upload',40,3,NULL,0,1,0,0,NULL,'[{\"name\": \"Signed Policy\", \"slug\": \"signed_policy\", \"type\": \"attachment\", \"is_required\": true, \"options_json\": null}]',NULL,NULL,'approval',NULL,NULL,NULL,NULL,'2026-06-16 22:53:30','2026-06-16 22:53:30'),(26,1,'Consolidate Comments',26,3,NULL,0,0,0,0,NULL,NULL,NULL,NULL,'none',NULL,NULL,NULL,'[{\"label\": \"Ad referendum\", \"stage_id\": \"16\"}, {\"label\": \"TRC Scheduling\", \"stage_id\": \"18\"}, {\"label\": \"PTWG\", \"stage_id\": \"20\"}]','2026-06-16 23:07:40','2026-06-16 23:11:35'),(27,1,'PTWG to Secretariat',39,3,NULL,0,0,0,0,NULL,NULL,NULL,NULL,'review',NULL,NULL,NULL,'[{\"label\": \"TRC Scheduling\", \"stage_id\": \"18\"}]','2026-06-16 23:19:14','2026-06-16 23:19:14');
/*!40000 ALTER TABLE `jea_workflow_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jea_workflows`
--

DROP TABLE IF EXISTS `jea_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jea_workflows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jea_workflows_module_id_foreign` (`module_id`),
  CONSTRAINT `jea_workflows_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `jea_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jea_workflows`
--

LOCK TABLES `jea_workflows` WRITE;
/*!40000 ALTER TABLE `jea_workflows` DISABLE KEYS */;
/*!40000 ALTER TABLE `jea_workflows` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-18 11:04:54

-- MySQL dump 10.16  Distrib 10.1.44-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: cheerleading_demo
-- ------------------------------------------------------
-- Server version	10.1.44-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `cheerleading_demo`
--


--
-- Table structure for table `bill_templates`
--

DROP TABLE IF EXISTS `bill_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bill_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(200) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `membership_fee` tinyint(1) NOT NULL DEFAULT '0',
  `site_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `bill_templates_FK` (`site_id`),
  CONSTRAINT `bill_templates_FK` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `label` varchar(200) NOT NULL,
  `amount` int(11) NOT NULL,
  `printed` tinyint(1) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  `reminder` int(11) NOT NULL,
  `due_date` date NOT NULL DEFAULT '1970-01-01',
  `due_date_ori` date NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `link_membership_fee` tinyint(1) NOT NULL DEFAULT '0',
  `canceled` tinyint(1) NOT NULL,
  `state_id` int(11) NOT NULL,
  `tokenhash` varchar(255) NOT NULL,
  `confirmation` datetime DEFAULT NULL,
  `site_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `bills_FK` (`member_id`),
  KEY `bills_FK_1` (`state_id`),
  KEY `bills_FK_site` (`site_id`),
  CONSTRAINT `bills_FK` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `bills_FK_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `configurations`
--

DROP TABLE IF EXISTS `configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configurations` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configurations`
--

LOCK TABLES `configurations` WRITE;
/*!40000 ALTER TABLE `configurations` DISABLE KEYS */;
INSERT INTO `configurations` VALUES (1,'clubName','Your club Name'),(2,'clubDescription','Your club description'),(3,'firstDaySeason','1.7'),(4,'year','2019'),(40,'lng','de'),(41,'it','1'),(42,'en','1'),(43,'de','1'),(44,'es','1'),(45,'fr','1'),(60,'feeMax','500'),(63,'feeLabel','Cotisation'),(90,'domain','@spirit-fever.com'),(91,'email','florian@spirit-fever.com'),(92,'emailName','SF Admin');
/*!40000 ALTER TABLE `configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contents`
--

DROP TABLE IF EXISTS `contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text,
  `location` int(11) NOT NULL DEFAULT '0',
  `url` varchar(1000) NOT NULL DEFAULT '',
  `group_id` int(11) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `data`
--

DROP TABLE IF EXISTS `data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `param` int(11) NOT NULL DEFAULT '0',
  `value` varchar(1000) DEFAULT NULL,
  `data_type` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data`
--

LOCK TABLES `data` WRITE;
/*!40000 ALTER TABLE `data` DISABLE KEYS */;
INSERT INTO `data` VALUES (1,0,'Rules aggreement','agreement'),(2,1,'Here you have all rules the member has to agree for registration','agreement');
/*!40000 ALTER TABLE `data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `field_types`
--

DROP TABLE IF EXISTS `field_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `field_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `style` int(11) NOT NULL DEFAULT '0' COMMENT '0: Text, 1: Mail, 2:Phone, 3:Number, 4:YesNo, 5:Date',
  `member_edit` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `fields`
--

DROP TABLE IF EXISTS `fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fields` (
  `member_id` int(11) NOT NULL,
  `field_type_id` int(11) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`member_id`,`field_type_id`),
  UNIQUE KEY `fields_UN` (`member_id`,`field_type_id`),
  KEY `fields_FK_type` (`field_type_id`),
  CONSTRAINT `fields_FK` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fields_FK_type` FOREIGN KEY (`field_type_id`) REFERENCES `field_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `membership_fee` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(250) DEFAULT NULL,
  `site_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `groups_FK` (`site_id`),
  CONSTRAINT `groups_FK` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups_members`
--

DROP TABLE IF EXISTS `groups_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_members` (
  `group_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`member_id`),
  KEY `groups_members_FK_1` (`member_id`),
  CONSTRAINT `groups_members_FK` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `groups_members_FK_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_date` datetime NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `present` int(11) NOT NULL DEFAULT '-1',
  `absent` int(11) NOT NULL DEFAULT '-1',
  `excused` int(11) NOT NULL DEFAULT '-1',
  `late` int(11) NOT NULL DEFAULT '-1',
  `max_members` int(11) NOT NULL DEFAULT '0',
  `big_event` tinyint(1) NOT NULL DEFAULT '0',
  `url` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meetings_FK` (`group_id`),
  CONSTRAINT `meetings_FK` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `member_docs`
--

DROP TABLE IF EXISTS `member_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `name` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(200) NOT NULL,
  `last_name` varchar(200) NOT NULL,
  `date_birth` date DEFAULT NULL,
  `gender_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `postcode` int(11) DEFAULT '0',
  `city` varchar(100) DEFAULT NULL,
  `phone_mobile` varchar(50) DEFAULT NULL,
  `phone_home` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_valid` tinyint(1) NOT NULL,
  `nationality` varchar(200) DEFAULT NULL,
  `date_arrival` date NOT NULL,
  `multi_payment` int(11) NOT NULL DEFAULT '1',
  `membership_fee_paid` int(11) NOT NULL,
  `discount` int(11) NOT NULL,
  `date_fin` date DEFAULT NULL,
  `communication_method_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `coach` tinyint(1) NOT NULL DEFAULT '0',
  `registered` tinyint(1) NOT NULL,
  `bvr` tinyint(1) NOT NULL DEFAULT '0',
  `hash` varchar(50) NOT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'fr',
  `leaving_comment` varchar(1000) DEFAULT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `members_UN` (`hash`),
  UNIQUE KEY `members_Name` (`first_name`,`last_name`),
  KEY `members_FK` (`gender_id`),
  KEY `members_FK_1` (`communication_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `presences`
--

DROP TABLE IF EXISTS `presences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `presences_FK` (`meeting_id`),
  KEY `presences_FK_1` (`member_id`),
  CONSTRAINT `presences_FK` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presences_FK_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `signature_member` blob,
  `signature_parent` blob,
  `member_id` int(11) NOT NULL,
  `validation_id` int(11) NOT NULL DEFAULT '0',
  `year` int(11) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registration_FK` (`member_id`),
  CONSTRAINT `registration_FK` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `MemberViewAll` tinyint(1) NOT NULL,
  `MemberEditAll` tinyint(1) NOT NULL,
  `MemberEditOwn` tinyint(1) NOT NULL,
  `BillViewAll` tinyint(1) NOT NULL,
  `BillEditAll` tinyint(1) NOT NULL,
  `Admin` tinyint(1) NOT NULL,
  `BillValidate` tinyint(1) NOT NULL DEFAULT '0',
  `Editor` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin',1,1,1,1,1,1,1,1),(2,'Coach',1,1,1,1,0,0,0,0),(3,'Member',0,0,1,0,0,0,0,0),(4,'New member',0,0,1,0,0,0,0,0),(5,'Treasurer',1,1,1,1,1,0,0,0),(6,'Coach+',1,1,1,1,1,0,0,1);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` char(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime, -- optional, requires MySQL 5.6.5+  DEFAULT CURRENT_TIMESTAMP
  `modified` datetime, -- optional, requires MySQL 5.6.5+ DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  `data` blob DEFAULT NULL, -- for PostgreSQL use bytea instead of blob
  `expires` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` varchar(100) NOT NULL,
  `address` varchar(100) DEFAULT NULL,
  `account_designation` varchar(100) DEFAULT NULL,
  `postcode` varchar(100) DEFAULT NULL,
  `iban` varchar(100) DEFAULT NULL,
  `bic` varchar(100) DEFAULT NULL,
  `feeMax` int(11) NOT NULL,
  `reminder_penalty` int(11) NOT NULL DEFAULT '0',
  `sender_email` varchar(100) NOT NULL,
  `sender` varchar(100) NOT NULL,
  `sender_phone` varchar(100) DEFAULT NULL,
  `add_invoice_num` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,'City','Address','Your club name','1020','IBAN ACCOUNT','',400,0,'bot@grinwald.net','Spirit Fever','',10000);
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` int(10) unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `pass_key` varchar(255) DEFAULT NULL,
  `tokenhash` varchar(255) DEFAULT NULL,
  `lastLogin` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_UN` (`username`),
  KEY `users_FK` (`role_id`),
  KEY `users_FK_1` (`member_id`),
  CONSTRAINT `users_FK` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_FK_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;




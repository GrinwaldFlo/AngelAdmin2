-- Create ShopItems table
-- Date: 2025-01-28
-- Description: Add shop_items table for managing shop products

DROP TABLE IF EXISTS `shop_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` int(11) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shop_items_active` (`active`),
  KEY `idx_shop_items_label` (`label`),
  KEY `idx_shop_items_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Create MemberOrders table
-- Date: 2025-01-28
-- Description: Add member_orders table for managing member shop orders

DROP TABLE IF EXISTS `member_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_item_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `delivered` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_member_orders_shop_item` (`shop_item_id`),
  KEY `idx_member_orders_member` (`member_id`),
  KEY `idx_member_orders_bill` (`bill_id`),
  KEY `idx_member_orders_delivered` (`delivered`),
  CONSTRAINT `fk_member_orders_shop_item` FOREIGN KEY (`shop_item_id`) REFERENCES `shop_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_member_orders_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_member_orders_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

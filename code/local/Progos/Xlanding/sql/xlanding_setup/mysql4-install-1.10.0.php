<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
  ALTER TABLE `am_landing_page`   
    CHANGE `category` `category` TEXT  NULL;
    ALTER TABLE `am_landing_page`   
  ADD COLUMN `updated` TINYINT(2) NULL ;
  CREATE TABLE `am_landing_page_products` (
    `rel_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Relation ID',
    `page_id` smallint(6) NOT NULL COMMENT 'Landing Page ID',
    `product_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Product ID',
    `position` int(11) NOT NULL DEFAULT '0' COMMENT 'Position',
    PRIMARY KEY (`rel_id`),
    UNIQUE KEY `UNQ_AM_LANDING_PAGE_PRODUCT_LANDING_ID_PRODUCT_ID` (`page_id`,`product_id`),
    KEY `IDX_AM_LANDING_PAGE_PRODUCT_PRODUCT_ID` (`product_id`),
    CONSTRAINT `FK_AM_LANDING_PAGE_PRD_PRD_ID_CAT_PRD_ENTT_ENTT_ID` FOREIGN KEY (`product_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_landingpage` FOREIGN KEY (`page_id`) REFERENCES `am_landing_page` (`page_id`) ON DELETE CASCADE ON UPDATE CASCADE
  )		
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
	 
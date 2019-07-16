<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


$installer = $this;
$installer->startSetup();
$helper = Mage::helper('seo/migration');

$sql = "
-- DROP TABLE IF EXISTS {$this->getTable('seofilter/rewrite')};
CREATE TABLE IF NOT EXISTS {$this->getTable('seofilter/rewrite')} (
  `rewrite_id` int(10) unsigned NOT NULL auto_increment,
  `attribute_code` varchar(60) NOT NULL default '',
  `option_id` int(10) unsigned NOT NULL,
  `rewrite` varchar(60) NOT NULL default '',
  `store_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`rewrite_id`),
  CONSTRAINT `UNQ_FILTERURLS_REWRITE_ATTRIBUTECODE_OPTIONID_STOREID` UNIQUE (`attribute_code`, `option_id`, `store_id`),
  CONSTRAINT `UNQ_FILTERURLS_REWRITE_REWRITE_STOREID` UNIQUE (`rewrite`, `store_id`),
  CONSTRAINT `FK_FILTERURLS_REWRITE_STORE` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Rewrite values for attribute options' AUTO_INCREMENT=1;
";

$helper->trySql($installer, $sql);
$installer->endSetup();
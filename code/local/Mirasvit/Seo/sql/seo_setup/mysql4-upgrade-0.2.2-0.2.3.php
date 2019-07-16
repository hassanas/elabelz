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
$version = Mage::helper('mstcore/version')->getModuleVersionFromDb('seo');
if ($version == '0.2.3') {
    return;
} elseif ($version != '0.2.2') {
    die("Please, run migration 0.2.2");
}

$installer->startSetup();
$helper = Mage::helper('seo/migration');

if (Mage::registry('mst_allow_drop_tables_seo')) {
    $sql = "
       DROP TABLE IF EXISTS {$this->getTable('seo/redirect')};
       DROP TABLE IF EXISTS {$installer->getTable('seo/redirect_store')};
    ";
    $helper->trySql($installer, $sql);
}

$sql = "
CREATE TABLE `{$this->getTable('seo/redirect')}` (
    `redirect_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Redirect Id',
    `url_from` text NOT NULL COMMENT 'URL FROM',
    `url_to` text NOT NULL COMMENT 'URL TO',
    `is_redirect_only_error_page` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'IS REDIRECT ONLY ERROR PAGE',
    `comments` text COMMENT 'COMMENTS',
    `is_active` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Is Active',
    PRIMARY KEY (`redirect_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Redirect';

CREATE TABLE `{$this->getTable('seo/redirect_store')}` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id',
    `redirect_id` int(11) NOT NULL COMMENT 'Redirect Id',
    `store_id` smallint(5) unsigned NOT NULL COMMENT 'Store Id',
    PRIMARY KEY (`id`),
    KEY `FK_SEOREDIRECT_STORE_ID` (`store_id`),
    KEY `FK_SEOREDIRECT_REDIRECT_ID` (`redirect_id`),
    CONSTRAINT `fk_seoredirect_store_id` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_seoredirect_store_redirect_id` FOREIGN KEY (`redirect_id`) REFERENCES `{$this->getTable('seo/redirect')}` (`redirect_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Redirect 2 Stores';
";

$helper->trySql($installer, $sql);
$installer->endSetup();

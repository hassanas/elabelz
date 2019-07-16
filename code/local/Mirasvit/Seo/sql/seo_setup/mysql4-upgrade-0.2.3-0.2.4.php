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
if ($version == '0.2.4') {
    return;
} elseif ($version != '0.2.3') {
    die("Please, run migration 0.2.3");
}

$installer->startSetup();
$helper = Mage::helper('seo/migration');

if (Mage::registry('mst_allow_drop_tables_seo')) {
    $sql = "
       DROP TABLE IF EXISTS {$this->getTable('seo/template')};
       DROP TABLE IF EXISTS {$installer->getTable('seo/template_store')};
    ";
    $helper->trySql($installer, $sql);
}

$sql = "
CREATE TABLE `{$this->getTable('seo/template')}` (
    `template_id`                        int(11)      NOT NULL AUTO_INCREMENT COMMENT 'Template Id',
    `name`                               varchar(255) NULL DEFAULT '',
    `meta_title`                         text         COMMENT 'meta title',
    `meta_keywords`                      text         COMMENT 'meta keywords',
    `meta_description`                   text         COMMENT 'meta description',
    `title`                              text         COMMENT 'title (H1)',
    `description`                        text         COMMENT 'seo description',
    `short_description`                  text         COMMENT 'product short description',
    `full_description`                   text         COMMENT 'product description',
    `is_active`                          tinyint(1)   UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Is Active',
    `rule_type`                          tinyint(1)   UNSIGNED NOT NULL COMMENT 'Rule Type',
    `sort_order`                         int(11)      UNSIGNED NOT NULL DEFAULT '10' COMMENT 'Sort Order',
    `conditions_serialized`              text         NOT NULL,
    `actions_serialized`                 text         NOT NULL,
    `stop_rules_processing`              tinyint(1)   NOT NULL DEFAULT '0' COMMENT 'Following rules will not be applied',
    PRIMARY KEY (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='SEO Template';

CREATE TABLE `{$this->getTable('seo/template_store')}` (
    `id`                int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id',
    `template_id`       int(11) NOT NULL COMMENT 'Template Id',
    `store_id`          smallint(5) unsigned NOT NULL COMMENT 'Store Id',
    PRIMARY KEY (`id`),
    KEY `FK_M_SEOTEMPLATE_STORE_ID` (`store_id`),
    KEY `FK_M_SEOTEMPLATE_TEMPLATE_ID` (`template_id`),
    CONSTRAINT `fk_m_seotemplate_store_id` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_m_seotemplate_store_template_id` FOREIGN KEY (`template_id`) REFERENCES `{$this->getTable('seo/template')}` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='SEO Template Stores';
";

$helper->trySql($installer, $sql);
$installer->endSetup();

<?php
/**
 *
 *
 * @author Ashley Schroder (aschroder.com)
 * @copyright  Copyright (c) 2013 ASchroder Consulting Ltd
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;

$installer->startSetup();

Mage::log("Running installer");

$installer->run("DROP TABLE IF EXISTS `{$installer->getTable('aschroder_email/email_log')}`;");

$installer->run("
CREATE TABLE `{$this->getTable('aschroder_email/email_log')}` (
  `email_id` int(10) unsigned NOT NULL auto_increment,
  `log_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `email_to` varchar(255) NOT NULL default '',
  `template` varchar(255) NULL,
  `subject` varchar(255) NULL,
  `email_body` text,
  PRIMARY KEY  (`email_id`),
  KEY `log_at` (`log_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

$installer->endSetup();

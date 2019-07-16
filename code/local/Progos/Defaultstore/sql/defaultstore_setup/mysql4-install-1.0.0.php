<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
CREATE TABLE `dbip_lookup` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `addr_type` enum('ipv4','ipv6') CHARACTER SET latin1 NOT NULL,
  `ip_start` varbinary(16) NOT NULL,
  `ip_end` varbinary(16) NOT NULL,
  `country` char(2) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`addr_type`,`ip_start`,`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4

		
SQLTEXT;

$installer->run($sql);

$installer->endSetup();
	 
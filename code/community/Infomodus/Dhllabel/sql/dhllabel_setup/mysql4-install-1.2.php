<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

$installer = $this;

$installer->startSetup();

$installer->run(
    "CREATE TABLE IF NOT EXISTS {$this->getTable('dhllabel')} (
  `label_id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `order_id` int(11) NOT NULL default 0,
  `trackingnumber` varchar(255) NOT NULL default '',
  `labelname` varchar(255) NOT NULL default '',
  `type` varchar(20) DEFAULT 'shipment',
  `shipment_id` int(11) NULL DEFAULT '0',
  `status` smallint(6) NOT NULL default '0',
  `statustext` text,
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('dhllabelpickup')} (
  `pickup_id` int(11) unsigned NOT NULL auto_increment,
  `RatePickupIndicator` char(1) NOT NULL default 'N',
  `CloseTime` varchar(50) NOT NULL default '',
  `ReadyTime` varchar(50) NOT NULL default '',
  `PickupDateYear` char(4) NOT NULL default '',
  `PickupDateMonth` char(2) NOT NULL default '',
  `PickupDateDay` char(2) NOT NULL default '',
  `AlternateAddressIndicator` char(1) NOT NULL default 'N',
  `ServiceCode` varchar(5) NOT NULL default '',
  `Quantity` int(11) NOT NULL default 0,
  `DestinationCountryCode` char(2) NOT NULL default '',
  `ContainerCode` varchar(50) NOT NULL default '',
  `Weight` varchar(50) NOT NULL default '',
  `UnitOfMeasurement` varchar(5) NOT NULL default '',
  `OverweightIndicator` char(1) NOT NULL default 'N',
  `PaymentMethod` varchar(5) NOT NULL default '',
  `SpecialInstruction` text,
  `ReferenceNumber` text,
  `Notification` tinyint(1) NOT NULL default 0,
  `ConfirmationEmailAddress` text,
  `UndeliverableEmailAddress` text,
  `ShipFrom` text,
  `pickup_request` text,
  `pickup_response` text,
  `pickup_cancel` text,
  `pickup_cancel_request` text,
  `status` varchar(255) NOT NULL default '',
  `price` varchar(255) NOT NULL default '0',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`pickup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('dhllabelaccount')} (
  `account_id` int(11) unsigned NOT NULL auto_increment,
  `companyname` varchar(255) NOT NULL default '',
  `attentionname` varchar(255) NOT NULL default '',
  `address1` text,
  `address2` text,
  `address3` text,
  `country` varchar(100) NOT NULL default '',
  `postalcode` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `province` varchar(100) NOT NULL default '',
  `telephone` varchar(100) NOT NULL default '',
  `fax` varchar(100) NOT NULL default '',
  `accountnumber` varchar(100) NOT NULL default '',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('dhllabelprice')} (
  `price_id` int(11) unsigned NOT NULL auto_increment,
  `order_id` int(11) NOT NULL default 0,
  `shipment_id` int(11) NOT NULL default 0,
  `price` varchar(50) NOT NULL default '',
  PRIMARY KEY (`price_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('dhllabelconformity')} (
  `dhllabelconformity_id` int(11) unsigned NOT NULL auto_increment,
  `method_id` varchar(50) NOT NULL default '',
  `dhlmethod_id` varchar(50) NOT NULL default '',
  `store_id` int(11) NOT NULL default 1,
  PRIMARY KEY (`dhllabelconformity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$tableName = $installer->getTable('dhllabelconformity');
if ($installer->getConnection()->isTableExists($tableName)) {
    $installer->getConnection()->addColumn($this->getTable('dhllabelconformity'), 'country_ids',
        'text'
    );
    $installer->getConnection()->addColumn($this->getTable('dhllabelconformity'), 'country_hash',
        'varchar(32)'
    );
    $installer->getConnection()->addColumn($this->getTable('dhllabelconformity'), 'international',
        'tinyint(1)'
    );

// Check if the table already exists

    $table = $installer->getConnection();

    $table->addIndex(
        $tableName,
        "unqconformdhl",
        array(
            'method_id',
            'international',
            'country_hash',
            'store_id',
        ),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    );
}

$installer->endSetup(); 
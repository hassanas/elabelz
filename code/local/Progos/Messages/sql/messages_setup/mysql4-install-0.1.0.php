<?php
  
$installer = $this;
  
$installer->startSetup();

/**
 * create shopbybrand table
 */
$installer->run("

DROP TABLE IF EXISTS {$this->getTable('progos_messages')};
DROP TABLE IF EXISTS {$this->getTable('progos_attachment')};
DROP TABLE IF EXISTS {$this->getTable('progos_thread')};
DROP TABLE IF EXISTS {$this->getTable('progos_conversation')};
    
CREATE TABLE {$this->getTable('progos_messages')} (
  `messages_id` int(11) unsigned NOT NULL auto_increment,
  `message` text(255) NOT NULL default '',
  PRIMARY KEY (`messages_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('progos_attachment')}(
    `attachment_id` int(11) unsigned NOT NULL auto_increment,
    `messages_id` int(11) unsigned NOT NULL,
    `file_name` varchar(255) NOT NULL default '',
    `date_time` datetime NOT NULL default '0000-00-00 00:00:00',
    INDEX(`attachment_id`),
    FOREIGN KEY (`messages_id`) REFERENCES {$this->getTable('progos_messages')} (`messages_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY (`attachment_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('progos_thread')}(
    `thread_id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL default '',
    `seller_id` int(11) NOT NULL,
    `buyer_id` int(11) unsigned NOT NULL,
    `delete_by_seller` boolean NOT NULL,
    `delete_by_buyer` boolean NOT NULL,
    `date_time` datetime NOT NULL default '0000-00-00 00:00:00', 
    PRIMARY KEY (`thread_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('progos_conversation')}(
    `conversation_id` int(11) unsigned NOT NULL auto_increment,
    `thread_id` int(11) unsigned NOT NULL,
    `messages_id` int(11) unsigned NOT NULL,
    `seller_id` int(11) NOT NULL,
    `buyer_id` int(11) unsigned NOT NULL,
    `read_status` varchar(255) NOT NULL default '',
    `delete_by_seller` boolean NOT NULL,
    `delete_by_buyer` boolean NOT NULL,
    `date_time` datetime NOT NULL default '0000-00-00 00:00:00',
    INDEX(`thread_id`),
    INDEX(`messages_id`),
    FOREIGN KEY (`messages_id`) REFERENCES {$this->getTable('progos_messages')} (`messages_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`thread_id`) REFERENCES {$this->getTable('progos_thread')} (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY (`conversation_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

");


$installer->endSetup();


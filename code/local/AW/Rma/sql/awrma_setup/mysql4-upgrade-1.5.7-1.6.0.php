<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Rma
 * @version    1.6.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$installer = $this;
$installer->startSetup();

$sql = "ALTER TABLE {$this->getTable('awrma/entity')} ADD `update_stock_qty` INT(10) NOT NULL DEFAULT '0'";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/entity_status')}
           SET `to_admin` = \"<p>A new RMA {{var request.getTextId()}} is initiated by {{var request.getCustomerName()}} <{{var request.getCustomerEmail()}}> for order #{{var request.getOrderId()}}.</p>\r\n<p>Date: {{var request.getFormattedCreatedAt()}}<br />\r\nRequest Type:  {{var request.getRequestTypeName()}}<br />\r\nPackage Opened: {{var request.getPackageOpenedLabel()}}</p>\r\n<p>Items<br />\r\n{{layout handle=\\\"awrma_email_request_item\\\" rma_request=\$request}}</p>\"
         WHERE `name` = 'Pending Approval'
           AND `to_admin` LIKE \"<p>A new RMA {{var request.getTextId()}} is initiated by {{var request.getCustomerName()}} <{{var request.getCustomerEmail()}}> for order <a href=\\\"{{var request.getNotifyOrderAdminLink()}}\\\">#{{var request.getOrderId()}}</a>.</p>\r\n<p>Date: {{var request.getFormattedCreatedAt()}}<br />\r\nRequest Type:  {{var request.getRequestTypeName()}}<br />\r\nPackage Opened: {{var request.getPackageOpenedLabel()}}</p>\r\n<p>Items<br />\r\n{{layout handle=\\\"awrma_email_request_item\\\" rma_request=\$request}}</p>\"
         ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/status_template')} SET
          `to_admin`=(SELECT `to_admin` FROM {$this->getTable('awrma/entity_status')} WHERE `name` = 'Pending Approval')
        WHERE `name` = 'Pending Approval'
        ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/entity_status')}
           SET `to_admin` = \"{{depend request.getNotifyStatusChanged()}}\r\n<p>RMA {{var request.getTextId()}} status changed to {{var request.getStatusName()}}</p>\r\n{{/depend}}\r\n<h3>RMA details</h3>\r\n<p>ID: {{var request.getTextId()}}<br />\r\nOrder ID: #{{var request.getOrderId()}}.<br />\r\nCustomer: {{var request.getCustomerName()}} <{{var request.getCustomerEmail()}}><br />\r\nDate: {{var request.getFormattedCreatedAt()}}\r\nRequest Type: {{var request.getRequestTypeName()}}<br />\r\nPackage Opened: {{var request.getPackageOpenedLabel()}}</p>\r\n<p>Items<br />\r\n{{layout handle=\\\"awrma_email_request_item\\\" rma_request=\$request}}</p>\"
         WHERE `name` = 'Package sent'
           AND `to_admin` LIKE \"{{depend request.getNotifyStatusChanged()}}\r\n<p>RMA {{var request.getTextId()}} status changed to {{var request.getStatusName()}}</p>\r\n{{/depend}}\r\n<h3>RMA details</h3>\r\n<p>ID: {{var request.getTextId()}}<br />\r\nOrder ID: #<a href=\\\"{{var request.getNotifyOrderAdminLink()}}\\\">#{{var request.getOrderId()}}</a>.<br />\r\nCustomer: {{var request.getCustomerName()}} <{{var request.getCustomerEmail()}}><br />\r\nDate: {{var request.getFormattedCreatedAt()}}\r\nRequest Type: {{var request.getRequestTypeName()}}<br />\r\nPackage Opened: {{var request.getPackageOpenedLabel()}}</p>\r\n<p>Items<br />\r\n{{layout handle=\\\"awrma_email_request_item\\\" rma_request=\$request}}</p>\"
         ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/status_template')} SET
          `to_admin`=(SELECT `to_admin` FROM {$this->getTable('awrma/entity_status')} WHERE `name` = 'Package sent')
        WHERE `name` = 'Package sent'
        ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/entity_status')}
           SET `to_admin` = \"RMA {{var request.getTextId()}} has been canceled by customer\r\n\r\n<h3>RMA details</h3>\r\n\r\n<p>ID: {{var request.getTextId()}}<br />\r\nOrder ID: #{{var request.getOrderId()}}.<br />\r\nCustomer: {{var request.getCustomerName()}} <{{var request.getCustomerEmail()}}><br />\r\nDate: {{var request.getFormattedCreatedAt()}}\r\nRequest Type: {{var request.getRequestTypeName()}}<br />\r\nPackage Opened: {{var request.getPackageOpenedLabel()}}</p>\r\n<p>Items<br />\r\n{{layout handle=\\\"awrma_email_request_item\\\" rma_request=\$request}}</p>\"
         WHERE `name` = 'Resolved (canceled)'
           AND `to_admin` LIKE \"RMA {{var request.getTextId()}} has been canceled by customer\r\n\r\n<h3>RMA details</h3>\r\n\r\n<p>ID: {{var request.getTextId()}}<br />\r\nOrder ID: #<a href=\\\"{{var request.getNotifyOrderAdminLink()}}\\\">#{{var request.getOrderId()}}</a>.<br />\r\nCustomer: {{var request.getCustomerName()}} <{{var request.getCustomerEmail()}}><br />\r\nDate: {{var request.getFormattedCreatedAt()}}\r\nRequest Type: {{var request.getRequestTypeName()}}<br />\r\nPackage Opened: {{var request.getPackageOpenedLabel()}}</p>\r\n<p>Items<br />\r\n{{layout handle=\\\"awrma_email_request_item\\\" rma_request=\$request}}</p>\"
         ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/status_template')} SET
          `to_admin`=(SELECT `to_admin` FROM {$this->getTable('awrma/entity_status')} WHERE `name` = 'Resolved (canceled)')
        WHERE `name` = 'Resolved (canceled)'
        ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/entity_status')}
           SET `to_chatbox` = \"Your RMA {{var request.getTextId()}} has been approved.\r\n{{depend request.getNotifyPrintlabelAllowed()}}You can print a RMA label with return address and other information by using the following link:\r\n{{var request.getPrintLabelUrl()}}\r\nThe RMA label must be enclosed inside your package; you may want to keep a copy of the label for your records.{{/depend}}\r\nPlease send your package to:\r\n{{var request.getNotifyRmaAddress()}}{{depend request.getConfirmShippingIsRequired()}}\r\nand click the \\\"Confirm Sending\\\" button after.{{/depend}}\"
         WHERE `name` = 'Approved'
           AND `to_chatbox` LIKE \"Your RMA {{var request.getTextId()}} has been approved.\r\n{{depend request.getNotifyPrintlabelAllowed()}}You can print a RMA label with return address and other information by clicking the following link:\r\n{{var request.getPrintLabelUrl()}}\r\nThe RMA label must be enclosed inside your package; you may want to keep a copy of the label for your records.{{/depend}}\r\nPlease send your package to:\r\n{{var request.getNotifyRmaAddress()}}{{depend request.getConfirmShippingIsRequired()}}\r\nand click the \\\"Confirm Sending\\\" button after.{{/depend}}\"
         ;";
$installer->run($sql);

$sql = "UPDATE {$this->getTable('awrma/status_template')} SET
          `to_chatbox`=(SELECT `to_chatbox` FROM {$this->getTable('awrma/entity_status')} WHERE `name` = 'Approved')
        WHERE `name` = 'Approved'
        ;";
$installer->run($sql);

$installer->endSetup();

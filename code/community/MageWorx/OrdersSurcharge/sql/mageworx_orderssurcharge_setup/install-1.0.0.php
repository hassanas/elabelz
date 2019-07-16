<?php
/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

$installer = $this;

/* @var $installer Mage_Sales_Model_Resource_Setup */

$installer->startSetup();

/**
 * Create table 'mageworx_orderssurcharge/surcharge'
 */
if (!$installer->tableExists($installer->getTable('mageworx_orderssurcharge/surcharge'))) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('mageworx_orderssurcharge/surcharge'))
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'Entity Id')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned' => true,
        ), 'Store Id')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
        ), 'Customer Id')
        ->addColumn('customer_email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Customer Email')
        ->addColumn('customer_is_guest', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned' => true,
        ), 'Customer Is Guest')
        ->addColumn('parent_order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Parent Order Id')
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Linked Order Id')
        ->addColumn('parent_order_increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(), 'Parent Order Increment Id')
        ->addColumn('order_increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(), 'Linked Order Increment Id')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Updated At')
        ->addColumn('base_total', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Base Total')
        ->addColumn('base_total_due', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Base Total Due')
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('default' => 1), 'Surcharge Status')
        ->addIndex($installer->getIdxName('mageworx_orderssurcharge/surcharge', array('store_id')),
            array('store_id'))
        ->addIndex($installer->getIdxName('mageworx_orderssurcharge/surcharge', array('customer_id')),
            array('customer_id'))
        ->addIndex($installer->getIdxName('mageworx_orderssurcharge/surcharge', array('parent_order_id')),
            array('parent_order_id'))
        ->addIndex($installer->getIdxName('mageworx_orderssurcharge/surcharge', array('created_at')),
            array('created_at'))
        ->addIndex($installer->getIdxName('mageworx_orderssurcharge/surcharge', array('updated_at')),
            array('updated_at'))
        ->addForeignKey($installer->getFkName('mageworx_orderssurcharge/surcharge', 'customer_id', 'customer/entity', 'entity_id'),
            'customer_id', $installer->getTable('customer/entity'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('mageworx_orderssurcharge/surcharge', 'parent_order_id', 'sales/order', 'entity_id'),
            'parent_order_id', $installer->getTable('sales/order'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('mageworx_orderssurcharge/surcharge', 'order_id', 'sales/order', 'entity_id'),
            'order_id', $installer->getTable('sales/order'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('mageworx_orderssurcharge/surcharge', 'store_id', 'core/store', 'store_id'),
            'store_id', $installer->getTable('core/store'), 'store_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->setComment('Linked Orders');
    $installer->getConnection()->createTable($table);
}

/* Add surcharge id to the entities */
$installer->addAttribute('quote', 'surcharge_id', array('type'=>'int', 'default' => null));
$installer->addAttribute('order', 'surcharge_id', array('type'=>'int', 'default' => null));
$installer->addAttribute('invoice', 'surcharge_id', array('type'=>'int', 'default' => null));
$installer->addAttribute('creditmemo', 'surcharge_id', array('type'=>'int', 'default' => null));

/* Add surcharge total amounts to: quote, order, invoice, creditmemo */
$installer->addAttribute('quote_address', 'surcharge_amount', array('type'=>'decimal'));
$installer->addAttribute('quote_address', 'base_surcharge_amount', array('type'=>'decimal'));

$installer->addAttribute('quote', 'surcharge_amount', array('type'=>'decimal'));
$installer->addAttribute('quote', 'base_surcharge_amount', array('type'=>'decimal'));

$installer->addAttribute('order', 'surcharge_amount', array('type'=>'decimal'));
$installer->addAttribute('order', 'base_surcharge_amount', array('type'=>'decimal'));
$installer->addAttribute('order', 'surcharge_invoiced', array('type'=>'decimal'));
$installer->addAttribute('order', 'base_surcharge_invoiced', array('type'=>'decimal'));

$installer->addAttribute('invoice', 'surcharge_amount', array('type'=>'decimal'));
$installer->addAttribute('invoice', 'base_surcharge_amount', array('type'=>'decimal'));

$installer->addAttribute('creditmemo', 'surcharge_amount', array('type'=>'decimal'));
$installer->addAttribute('creditmemo', 'base_surcharge_amount', array('type'=>'decimal'));

/* Add linked total amounts to: quote, order, invoice, creditmemo */
$installer->addAttribute('quote_address', 'linked_amount', array('type'=>'decimal'));
$installer->addAttribute('quote_address', 'base_linked_amount', array('type'=>'decimal'));

$installer->addAttribute('quote', 'base_linked_amount', array('type' => 'decimal'));
$installer->addAttribute('quote', 'linked_amount', array('type' => 'decimal'));
$installer->addAttribute('quote', 'is_surcharged', array('type' => 'int', 'default' => null));

$installer->addAttribute('order', 'base_linked_amount', array('type' => 'decimal'));
$installer->addAttribute('order', 'linked_amount', array('type' => 'decimal'));
$installer->addAttribute('order', 'base_linked_amount_invoiced', array('type' => 'decimal'));
$installer->addAttribute('order', 'linked_amount_invoiced', array('type' => 'decimal'));

$installer->addAttribute('invoice', 'base_linked_amount', array('type' => 'decimal'));
$installer->addAttribute('invoice', 'linked_amount', array('type' => 'decimal'));

$installer->addAttribute('creditmemo', 'base_linked_amount', array('type' => 'decimal'));
$installer->addAttribute('creditmemo', 'linked_amount', array('type' => 'decimal'));

$templateCode = MageWorx_OrdersSurcharge_Model_Mail::TEMPLATE_CODE;
$guestTemplateCode = MageWorx_OrdersSurcharge_Model_Mail::GUEST_TEMPLATE_CODE;

$templateBody = <<<TEMPLATE_BODY
{{template config_path="design/email/header"}}
{{inlinecss file="email-inline.css"}}
<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
    <tr>
        <td align="center" valign="top" style="padding:20px 0 20px 0">
            <h1 style="font-size:22px; font-weight:normal; line-height:22px; margin:0 0 11px 0;">Dear {{var name}},</h1>
            <p style="font-size:12px; line-height:16px; margin:0 0 10px 0;">
                Your order # {{var order.increment_id}} has been updated.
            </p>
            <p style="font-size:12px; line-height:16px; margin:0 0 10px 0;">
                As far as your order has been edited, the balance due is {{var surchargeTotalDue}}
            </p>
            <p style="font-size:12px; line-height:16px; margin:0 0 10px 0;">
                You can make the additional payment in your account, just follow <a href="{{var surchargeLink}}" style="color:#1E7EC8;">the link</a>.
            </p>
            <p style="font-size:12px; line-height:16px; margin:0;">
                If you have any questions, feel free to contact us at
                <a href="mailto:{{config path='trans_email/ident_support/email'}}" style="color:#1E7EC8;">{{config path='trans_email/ident_support/email'}}</a>
                {{depend store_phone}}
                or <a href="tel:{{var phone}}">by phone at {{var store_phone}}</a>.
                {{/depend}}
            </p>
        </td>
    </tr>
    <tr>
        <td>
            {{layout handle="sales_email_order_items" order=\$order}}
        </td>
    </tr>
</table>
{{template config_path="design/email/footer"}}
TEMPLATE_BODY;

$guestTemplateBody = <<<GUEST_TEMPLATE_BODY
{{template config_path="design/email/header"}}
{{inlinecss file="email-inline.css"}}
<table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
    <tr>
        <td align="center" valign="top" style="padding:20px 0 20px 0">
            <h1 style="font-size:22px; font-weight:normal; line-height:22px; margin:0 0 11px 0;">Dear {{var name}},</h1>
            <p style="font-size:12px; line-height:16px; margin:0 0 10px 0;">
                Your order # {{var order.increment_id}} has been updated.
            </p>
            <p style="font-size:12px; line-height:16px; margin:0 0 10px 0;">
                As far as your order has been edited, the balance due is {{var surchargeTotalDue}}
            </p>
            <p style="font-size:12px; line-height:16px; margin:0 0 10px 0;">
                You can make the additional payment in your account, just follow <a href="{{var surchargeLink}}" style="color:#1E7EC8;">the link</a>.
            </p>
            <p style="font-size:12px; line-height:16px; margin:0;">
                If you have any questions, feel free to contact us at
                <a href="mailto:{{config path='trans_email/ident_support/email'}}" style="color:#1E7EC8;">{{config path='trans_email/ident_support/email'}}</a>
                {{depend store_phone}}
                or <a href="tel:{{var phone}}">by phone at {{var store_phone}}</a>.
                {{/depend}}
            </p>
        </td>
    </tr>
    <tr>
        <td>
            {{layout handle="sales_email_order_items" order=\$order}}
        </td>
    </tr>
</table>
{{template config_path="design/email/footer"}}
GUEST_TEMPLATE_BODY;

$template = Mage::getModel('core/email_template')->loadByCode($templateCode);
$template->setTemplateCode($templateCode)
    ->setTemplateText($templateBody)
    ->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML)
    ->setTemplateSubject('New Payment Link')
    ->save();

$guestTemplate = Mage::getModel('core/email_template')->loadByCode($guestTemplateCode);
$guestTemplate->setTemplateCode($guestTemplateCode)
    ->setTemplateText($guestTemplateBody)
    ->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML)
    ->setTemplateSubject('New Payment Link')
    ->save();

$installer->endSetup();

<?php
/**
 * MageWorx
 * Admin Order Editor extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersEdit
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

$installer = $this;
$installer->startSetup();

if (!$installer->tableExists($this->getTable('mageworx_ordersgrid/order_grid'))) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('mageworx_ordersgrid/order_grid'))
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Entity Id')
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(), 'Status')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned' => true,
        ), 'Store Id')
        ->addColumn('store_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Store Name')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
        ), 'Customer Id')
        ->addColumn('base_grand_total', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Base Grand Total')
        ->addColumn('base_total_paid', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Base Total Paid')
        ->addColumn('grand_total', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Grand Total')
        ->addColumn('total_paid', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Total Paid')
        ->addColumn('increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(), 'Increment Id')
        ->addColumn('base_currency_code', Varien_Db_Ddl_Table::TYPE_TEXT, 3, array(), 'Base Currency Code')
        ->addColumn('order_currency_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Order Currency Code')
        ->addColumn('shipping_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Name')
        ->addColumn('billing_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing Name')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Updated At')
        ->addColumn('customer_email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Customer Email')
        ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(), 'Customer Group Id')
        ->addColumn('total_qty_ordered', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Total Qty Ordered')
        ->addColumn('coupon_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Coupon Code')
        ->addColumn('subtotal', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Subtotal')
        ->addColumn('tax_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Tax Amount')
        ->addColumn('discount_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Discount Amount')
        ->addColumn('total_refunded', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Total Refunded')
        ->addColumn('shipping_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Shipping Amount')
        ->addColumn('base_subtotal', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Base Subtotal')
        ->addColumn('base_tax_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Base Tax Amount')
        ->addColumn('base_discount_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Base Discount Amount')
        ->addColumn('base_total_refunded', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Base Total Refunded')
        ->addColumn('base_shipping_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Base Shipping Amount')
        ->addColumn('shipping_method', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Method')
        ->addColumn('shipping_description', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Description')
        ->addColumn('weight', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(), 'Weight')
        ->addColumn('is_edited', Varien_Db_Ddl_Table::TYPE_TINYINT, null, array(
            'nullable' => false,
            'default'  => '0',
        ), 'Is Edited')
        ->addColumn('order_group_id', Varien_Db_Ddl_Table::TYPE_TINYINT, null, array(
            'unsigned' => true,
            'nullable' => false,
            'default'  => '0',
        ), 'Order Group Id')
        ->addColumn('shipped', Varien_Db_Ddl_Table::TYPE_TINYINT, null, array(
            'unsigned' => true,
            'nullable' => false,
            'default'  => '0',
        ), 'Shipped')
        ->addColumn('total_qty_shipped', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'unsigned' => true,
            'nullable' => false,
            'default'  => '0',
        ), 'Total Qty Shipped')
        ->addColumn('shipping_company', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Company')
        ->addColumn('shipping_street', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Street')
        ->addColumn('shipping_city', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping City')
        ->addColumn('shipping_region', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Region')
        ->addColumn('shipping_country', Varien_Db_Ddl_Table::TYPE_TEXT, 2, array(), 'Shipping Country')
        ->addColumn('shipping_postcode', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Postcode')
        ->addColumn('shipping_telephone', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Shipping Telephone')
        ->addColumn('billing_company', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing Company')
        ->addColumn('billing_street', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing Street')
        ->addColumn('billing_city', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing City')
        ->addColumn('billing_region', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing Region')
        ->addColumn('billing_country', Varien_Db_Ddl_Table::TYPE_TEXT, 2, array(), 'Billing Country')
        ->addColumn('billing_postcode', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing Postcode')
        ->addColumn('billing_telephone', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Billing Telephone')
        ->addColumn('tracking_number', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Tracking Number')
        ->addColumn('payment_method', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Payment Method')
        ->addColumn('invoice_increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Invoice Increment Id')
        ->addColumn('order_comment', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'Order Comment')
        ->addColumn('product_names', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'Product Names')
        ->addColumn('skus', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'SKUs')
        ->addColumn('product_ids', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'Product Ids')
        ->addColumn('product_options', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'Product Options')
        ->addColumn('total_qty_ordered_aggregated', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Total Qty Ordered Aggregated')
        ->addColumn('total_qty_canceled', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Total Qty Canceled')
        ->addColumn('total_qty_invoiced', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Total Qty Invoiced')
        ->addColumn('total_qty_refunded', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default'  => '0.0000',
        ), 'Total Qty Refunded')
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('status')),
            array('status'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('store_id')),
            array('store_id'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('store_name')),
            array('store_name'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_grand_total')),
            array('base_grand_total'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_total_paid')),
            array('base_total_paid'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('grand_total')),
            array('grand_total'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_paid')),
            array('total_paid'))
        ->addIndex(
            $installer->getIdxName(
                'mageworx_ordersgrid/order_grid',
                array('increment_id'),
                Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
            ),
            array('increment_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_name')),
            array('shipping_name'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_name')),
            array('billing_name'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('created_at')),
            array('created_at'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('updated_at')),
            array('updated_at'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('customer_id')),
            array('customer_id'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('customer_email')),
            array('customer_email'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('customer_group_id')),
            array('customer_group_id'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_qty_ordered')),
            array('total_qty_ordered'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_qty_ordered_aggregated')),
            array('total_qty_ordered_aggregated'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_qty_canceled')),
            array('total_qty_canceled'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_qty_invoiced')),
            array('total_qty_invoiced'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_qty_refunded')),
            array('total_qty_refunded'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('coupon_code')),
            array('coupon_code'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('subtotal')),
            array('subtotal'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('tax_amount')),
            array('tax_amount'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('discount_amount')),
            array('discount_amount'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_refunded')),
            array('total_refunded'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_amount')),
            array('shipping_amount'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_subtotal')),
            array('base_subtotal'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_tax_amount')),
            array('base_tax_amount'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_discount_amount')),
            array('base_discount_amount'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_total_refunded')),
            array('base_total_refunded'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('base_shipping_amount')),
            array('base_shipping_amount'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_method')),
            array('shipping_method'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_description')),
            array('shipping_description'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('weight')),
            array('weight'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('is_edited')),
            array('is_edited'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('order_group_id')),
            array('order_group_id'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipped')),
            array('shipped'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('total_qty_shipped')),
            array('total_qty_shipped'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_company')),
            array('shipping_company'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_street')),
            array('shipping_street'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_city')),
            array('shipping_city'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_region')),
            array('shipping_region'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_country')),
            array('shipping_country'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_postcode')),
            array('shipping_postcode'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('shipping_telephone')),
            array('shipping_telephone'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_company')),
            array('billing_company'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_street')),
            array('billing_street'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_city')),
            array('billing_city'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_region')),
            array('billing_region'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_country')),
            array('billing_country'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_postcode')),
            array('billing_postcode'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('billing_telephone')),
            array('billing_telephone'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('tracking_number')),
            array('tracking_number'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('payment_method')),
            array('payment_method'))
        ->addIndex($installer->getIdxName('mageworx_ordersgrid/order_grid', array('invoice_increment_id')),
            array('invoice_increment_id'))
        ->addForeignKey($installer->getFkName('mageworx_ordersgrid/order_grid', 'customer_id', 'customer/entity',
            'entity_id'),
            'customer_id', $installer->getTable('customer/entity'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('mageworx_ordersgrid/order_grid', 'entity_id', 'sales/order',
            'entity_id'),
            'entity_id', $installer->getTable('sales/order'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('mageworx_ordersgrid/order_grid', 'store_id', 'core/store', 'store_id'),
            'store_id', $installer->getTable('core/store'), 'store_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->setComment('MageWorx OrdersGrid Order Grid');
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();
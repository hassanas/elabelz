<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2015 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Model_System_Config_Source_Orders_Grid
{
    protected $customerCreditFields = array('base_internal_credit', 'internal_credit');
        
    public function getLabels()
    {
        /** @var MageWorx_OrdersGrid_Helper_Data $helper */
        $helper = Mage::helper('mageworx_ordersgrid');

        $labels = array(
            'increment_id' => Mage::helper('sales')->__('Order #'),
            'store_id' => Mage::helper('sales')->__('Purchased From (Store)'),
            'created_at' => Mage::helper('sales')->__('Purchased On'),
            'qnty' => $helper->__('Qnty'),
            'coupon_code' => $helper->__('Coupon Code'),
            'order_comment' => $helper->__('Order Comment(s)'),
            'order_group' => $helper->__('Group'),
            'is_edited' => $helper->__('Edited'),
            'status' => Mage::helper('sales')->__('Status'),
            'action' => Mage::helper('sales')->__('Action'),
            'base_subtotal' => $helper->__('Subtotal (Base)'),
            'subtotal' => $helper->__('Subtotal (Purchased)'),
            'base_shipping_amount' => $helper->__('Shipping Amount (Base)'),
            'shipping_amount' => $helper->__('Shipping Amount (Purchased)'),
            'base_tax_amount' => $helper->__('Tax Amount (Base)'),
            'tax_amount' => $helper->__('Tax Amount (Purchased)'),
            'base_discount_amount' => $helper->__('Discount (Base)'),
            'discount_amount' => $helper->__('Discount (Purchased)'),
            'base_internal_credit' => $helper->__('Internal Credit (Base)'),
            'internal_credit' => $helper->__('Internal Credit (Purchased)'),
            'base_total_refunded' => $helper->__('Total Refunded (Base)'),
            'total_refunded' => $helper->__('Total Refunded (Purchased)'),
            'base_grand_total' => Mage::helper('sales')->__('G.T. (Base)'),
            'grand_total' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'product_names' => $helper->__('Product Name(s)'),
            'product_skus' => $helper->__('SKU(s)'),
            'product_options' => $helper->__('Product Option(s)'),
            'weight' => $helper->__('Weight'),
            'payment_method' => $helper->__('Payment Method'),
            'billing_name' => Mage::helper('sales')->__('Bill to Name'),
            'billing_company' => $helper->__('Bill to Company'),
            'billing_street' => $helper->__('Bill to Street'),
            'billing_city' => $helper->__('Bill to City'),
            'billing_region' => $helper->__('Bill to State'),
            'billing_country' => $helper->__('Bill to Country'),
            'billing_postcode' => $helper->__('Billing Postcode'),
            'billing_telephone' => $helper->__('Billing Telephone'),
            'shipping_method' => $helper->__('Shipping Method'),
            'tracking_number' => $helper->__('Tracking Number'),
            'shipped' => $helper->__('Shipped'),
            'shipping_name' => Mage::helper('sales')->__('Ship to Name'),
            'shipping_company' => $helper->__('Ship to Company'),
            'shipping_street' => $helper->__('Ship to Street'),
            'shipping_city' => $helper->__('Ship to City'),
            'shipping_region' => $helper->__('Ship to State'),
            'shipping_country' => $helper->__('Ship to Country'),
            'shipping_postcode' => $helper->__('Shipping Postcode'),
            'shipping_telephone' => $helper->__('Shipping Telephone'),
            'customer_email' => $helper->__('Customer Email'),
            'customer_group' => $helper->__('Customer Group'),
            'invoice_increment_id' => $helper->__('Invoice(s)')
        );

        return $labels;
    }

    public function toArray()
    {
        $options = array(
            'increment_id',
            'store_id',
            'created_at',
            'qnty',
            'coupon_code',
            'order_comment',
            'order_group',
            'is_edited',
            'status',
            'action',
            'base_subtotal',
            'subtotal',
            'base_shipping_amount',
            'shipping_amount',
            'base_tax_amount',
            'tax_amount',
            'base_discount_amount',
            'discount_amount',
            'base_internal_credit',
            'internal_credit',
            'base_total_refunded',
            'total_refunded',
            'base_grand_total',
            'grand_total',
            'product_names',
            'product_skus',
            'product_options',
            'weight',
            'payment_method',
            'billing_name',
            'billing_company',
            'billing_street',
            'billing_city',
            'billing_region',
            'billing_country',
            'billing_postcode',
            'billing_telephone',
            'shipping_method',
            'tracking_number',
            'shipped',
            'shipping_name',
            'shipping_company',
            'shipping_street',
            'shipping_city',
            'shipping_region',
            'shipping_country',
            'shipping_postcode',
            'shipping_telephone',
            'customer_email',
            'customer_group',
            'invoice_increment_id'
        );

        if (!Mage::getConfig()->getModuleConfig('MageWorx_CustomerCredit')->is('active', true)) {
            foreach ($this->customerCreditFields as $field) {
                if(($key = array_search($field, $options)) !== false) {
                    unset($options[$key]);
                }
            }
        }

        return $options;
    }
}
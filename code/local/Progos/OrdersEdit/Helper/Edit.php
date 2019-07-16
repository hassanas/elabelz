<?php
/**
 * added by Humera Batool 31st Oct 2017
 *
 * @purpose: to add store credit block in order view
 *
 **/

class Progos_OrdersEdit_Helper_Edit extends MageWorx_OrdersEdit_Helper_Edit
{
    public function getAvailableBlocks()
    {
        if (is_null($this->_availableBlocks)){
            $this->_availableBlocks = array(
                array(
                    'className' => 'head-general',
                    'blockId' => 'order_info',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_general',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_general'
                ),
                array(
                    'className' => 'head-account',
                    'blockId' => 'customer_info',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_customer',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_customer'
                ),
                array(
                    'className' => 'head-billing-address',
                    'blockId' => 'billing_address',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_address',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_address'
                ),
                array(
                    'className' => 'head-shipping-address',
                    'blockId' => 'shipping_address',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_address',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_address'
                ),
                array(
                    'className' => 'head-payment-method',
                    'blockId' => 'payment_method',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_payment',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_payment'
                ),
                array(
                    'className' => 'head-shipping-method',
                    'blockId' => 'shipping_method',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_shipping',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_shipping'
                ),
                array(
                    'className' => 'head-products',
                    'blockId' => 'order_items',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_items',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_items'
                ),
                array(
                    'className' => 'head-coupons',
                    'blockId' => 'sales_order_coupons',
                    'block' => 'mageworx_ordersedit/adminhtml_sales_order_edit_form_coupons',
                    'changedBlock' => 'mageworx_ordersedit/adminhtml_sales_order_changed_coupons'
                ),
                array(
                    'className' => 'head-storecredits',
                    'blockId' => 'sales_order_storecredits',
                    'block' => 'progos_storecredit/adminhtml_sales_order_edit_form_storecredits',
                    'changedBlock' => 'progos_storecredit/adminhtml_sales_order_changed_storecredits'
                ),
            );
        }

        return $this->_availableBlocks;
    }

}
<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */

/**
 * Get seller commission
 * Form to get the seller commission from admin
 */
class Apptha_Marketplace_Block_Adminhtml_Orderitemsall_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
 
 /**
  * Get the get the seller commission from admin
  *
  * @return void
  */
 
 
 protected function _prepareForm() {
 	/**
 	 * Get Seller Id
 	 */
  $id = $this->getRequest ()->getParam ( 'id' );

  /**
   * Load Seller Id 
   * */
   $collection = Mage::getModel ( 'marketplace/commission' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->addFieldToFilter ( 'id', array (
            'eq' => $id
        ))
        ->getFirstItem();
  
  /**
   * Set Collection
   * */

  $this->setCollection ( $collection );
  /** New Varien Form */
      
      $form  = new Varien_Data_Form(array(
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/adminhtml_orderitemsall/saveOrderProduct', 
                            array('id' => $id)
                  ),
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
              )
            );
      
      $fieldset = $form->addFieldset('store_name', array('legend' => Mage::helper('marketplace')->__('Store title')));
      
      $fieldset->addField('id', 'text', array(
            'name'      => 'id',
            'title'     => Mage::helper('marketplace')->__('Id'),
            'label'     => Mage::helper('marketplace')->__('Id'),
            'required'  => true,
            'value'     => $collection['id']
        )); 

      $fieldset->addField('seller_id', 'text', array(
            'name'      => 'seller_id',
            'title'     => Mage::helper('marketplace')->__('Seller Id'),
            'label'     => Mage::helper('marketplace')->__('Seller Id'),
            'required'  => true,
            'value'     => $collection['seller_id']
        )); 
      
      $fieldset->addField('order_id', 'text', array(
            'name'      => 'order_id',
            'title'     => Mage::helper('marketplace')->__('Order Id'),
            'label'     => Mage::helper('marketplace')->__('Order Id'),
            'required'  => true,
            'value'     => $collection['order_id']
        ));  
      
      /*$fieldset->addField('country', 'select', array(
          'name'      => 'country',
          'title'     => Mage::helper('marketplace')->__('country'),
          'label'     => Mage::helper('marketplace')->__('country'),
          'required'  => true,
          'value'     => $collection['country']
      )); */
 
      
       $fieldset->addField('product_id', 'text', array(
            'name'  => 'product_id',
            'title'     => Mage::helper('marketplace')->__('Product Id'),
            'label'     => Mage::helper('marketplace')->__('Product Id'),
            'class' => 'required-entry',
            'required'  => true,
            'value'     => $collection['product_id']
      ));

      $fieldset->addField('product_qty', 'text', array(
            'name'  => 'product_qty',
            'title'     => Mage::helper('marketplace')->__('Product Quantity'),
            'label'     => Mage::helper('marketplace')->__('Product Quantity'),
            'class' => 'required-entry',
            'required'  => true,
            'value'     => $collection['product_qty']
      ));

      $fieldset->addField('product_amt', 'text', array(
        'name'      => 'product_amt',
        'label'     => Mage::helper('marketplace')->__('Product Price'),
        'title'     => Mage::helper('marketplace')->__('Product Price'),
        'required'  => true,
        'value'     => $collection['product_amt']
      ));

       $fieldset->addField('commission_fee', 'textarea', array(
        'name'      => 'commission_fee',
        'label'     => Mage::helper('marketplace')->__('Commission Fee'),
        'title'     => Mage::helper('marketplace')->__('Commission Fee'),
        'required'  => true,
        'value'     => $collection['commission_fee']
      )); 

       $fieldset->addField('seller_amount', 'textarea', array(
        'name'      => 'seller_amount',
        'label'     => Mage::helper('marketplace')->__('Seller Amount'),
        'title'     => Mage::helper('marketplace')->__('Seller Amount'),
        'required'  => true,
        'value'     => $collection['seller_amount']
      )); 

       $fieldset->addField('increment_id', 'textarea', array(
        'name'      => 'increment_id',
        'label'     => Mage::helper('marketplace')->__('Increment Id'),
        'title'     => Mage::helper('marketplace')->__('Increment Id'),
        'required'  => true,
        'value'     => $collection['increment_id']
      ));  
      
      $fieldset->addField('order_total', 'textarea', array(
        'name'      => 'order_total',
        'label'     => Mage::helper('marketplace')->__('Total Order Price'),
        'title'     => Mage::helper('marketplace')->__('Total Order Price'),
        'required'  => true,
        'value'     => $collection['order_total']
      ));  
      
      $fieldset->addField('order_status', 'textarea', array(
        'name'      => 'order_status',
        'label'     => Mage::helper('marketplace')->__('Order Status'),
        'title'     => Mage::helper('marketplace')->__('Order Status'),
        'required'  => true,
        'value'     => $collection['order_status']
      ));  

      $fieldset->addField('customer_id', 'textarea', array(
        'name'      => 'customer_id',
        'label'     => Mage::helper('marketplace')->__('Customer Id'),
        'title'     => Mage::helper('marketplace')->__('Customer Id'),
        'required'  => true,
        'value'     => $collection['customer_id']
      ));  
      
      $fieldset->addField('item_order_status', 'textarea', array(
        'label'     => Mage::helper('marketplace')->__('Item Status'),
        'title'     => Mage::helper('marketplace')->__('Item Status'),
        'name'      => 'item_order_status',
        'required'  => true,
        'value'     => $collection['item_order_status']
      ));

       $fieldset->addField('status', 'textarea', array(
        'label'     => Mage::helper('marketplace')->__('Status'),
        'title'     => Mage::helper('marketplace')->__('Status'),
        'name'      => 'status',
        'required'  => true,
        'value'     => $collection['status']
      ));

       $fieldset->addField('is_buyer_confirmation', 'textarea', array(
        'name'      => 'is_buyer_confirmation',
        'label'     => Mage::helper('marketplace')->__('Buyer Confirmation'),
        'title'     => Mage::helper('marketplace')->__('Buyer Confirmation'),
        'required'  => true,
        'value'     => $collection['is_buyer_confirmation']
      )); 

       $fieldset->addField('is_seller_confirmation', 'textarea', array(
        'name'      => 'is_seller_confirmation',
        'label'     => Mage::helper('marketplace')->__('Seller Confirmation'),
        'title'     => Mage::helper('marketplace')->__('Seller Confirmation'),
        'required'  => true,
        'value'     => $collection['is_seller_confirmation']
      ));

       $fieldset->addField('credited', 'textarea', array(
        'name'      => 'credited',
        'label'     => Mage::helper('marketplace')->__('Credited'),
        'title'     => Mage::helper('marketplace')->__('Credited'),
        'required'  => true,
        'value'     => $collection['credited']
      ));

      $fieldset->addField('created_at', 'textarea', array(
        'name'      => 'created_at',
        'label'     => Mage::helper('marketplace')->__('Created At'),
        'title'     => Mage::helper('marketplace')->__('Created At'),
        'required'  => true,
        'value'     => $collection['created_at']
      ));

      $fieldset->addField('refund_request_customer', 'textarea', array(
        'name'      => 'refund_request_customer',
        'label'     => Mage::helper('marketplace')->__('refund_request_customer'),
        'title'     => Mage::helper('marketplace')->__('refund_request_customer'),
        'required'  => true,
        'value'     => $collection['refund_request_customer']
      ));
      
      $fieldset->addField('cancel_request_customer', 'textarea', array(
        'name'      => 'cancel_request_customer',
        'label'     => Mage::helper('marketplace')->__('cancel_request_customer'),
        'title'     => Mage::helper('marketplace')->__('cancel_request_customer'),
        'required'  => true,
        'value'     => $collection['cancel_request_customer']
      ));

      $fieldset->addField('refund_request_seller', 'textarea', array(
        'name'      => 'credited',
        'label'     => Mage::helper('marketplace')->__('refund_request_seller'),
        'title'     => Mage::helper('marketplace')->__('refund_request_seller'),
        'required'  => true,
        'value'     => $collection['refund_request_seller']
      ));
      
      $fieldset->addField('cancel_request_seller_confirmation', 'textarea', array(
        'name'      => 'cancel_request_seller_confirmation',
        'label'     => Mage::helper('marketplace')->__('cancel_request_seller_confirmation'),
        'title'     => Mage::helper('marketplace')->__('cancel_request_seller_confirmation'),
        'required'  => true,
        'value'     => $collection['cancel_request_seller_confirmation']
      ));

       $fieldset->addField('cancel_request_seller_remarks', 'textarea', array(
        'name'      => 'cancel_request_seller_remarks',
        'label'     => Mage::helper('marketplace')->__('cancel_request_seller_remarks'),
        'title'     => Mage::helper('marketplace')->__('cancel_request_seller_remarks'),
        'required'  => false,
        'value'     => $collection['cancel_request_seller_remarks']
      ));

       $fieldset->addField('refund_request_seller_confirmation', 'textarea', array(
        'name'      => 'refund_request_seller_confirmation',
        'label'     => Mage::helper('marketplace')->__('refund_request_seller_confirmation'),
        'title'     => Mage::helper('marketplace')->__('refund_request_seller_confirmation'),
        'required'  => true,
        'value'     => $collection['refund_request_seller_confirmation']
      ));


       $fieldset->addField('refund_request_seller_remarks', 'textarea', array(
        'name'      => 'refund_request_seller_remarks',
        'label'     => Mage::helper('marketplace')->__('refund_request_seller_remarks'),
        'title'     => Mage::helper('marketplace')->__('refund_request_seller_remarks'),
        'required'  => false,
        'value'     => $collection['refund_request_seller_remarks']
      ));



        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
 }

}


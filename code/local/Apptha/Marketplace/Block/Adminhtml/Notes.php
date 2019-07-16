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
 * Manage order information
 * Manage order information with seller details and also with pagination
 */
class Apptha_Marketplace_Block_Adminhtml_Notes extends Mage_Core_Block_Template {
    
    public function getOrderNotes() {       
        /**
         *  Convert local date to magento db date.
         */
        $increment_id = Mage::app()->getRequest()->getParam('order_id');
        $product_id = Mage::app()->getRequest()->getParam('product_id');
        $orders = Mage::getModel('marketplace/notes')->getCollection();
        $orders->addFieldToSelect('*');
        $orders->addFieldToFilter('increment_id',$increment_id ); 
        $orders->addFieldToFilter('item_id',$product_id ); 
        /**
         * Set order for manage order
         */
        $orders->setOrder('id', 'desc');
        /**
         * Return orders
         */
        return $orders;        
    }


}
?>

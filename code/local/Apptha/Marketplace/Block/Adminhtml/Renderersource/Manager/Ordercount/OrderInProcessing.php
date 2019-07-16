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
 */

/**
 * Display Commision rate
 * Render the commission rate of particular seller
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Ordercount_OrderInProcessing extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Manager overview show increment id and sub order id 
     * 
     * Return the commission percentage
     * @return int
     */
    public function render(Varien_Object $row) {#OrderInProcessing (seller or buyer both confirm all orders)
        
        $orderId = $row->getData($this->getColumn()->getIndex());
        $ordersItems = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                 ->addFieldToSelect ( array('increment_id','is_buyer_confirmation','is_seller_confirmation') )
                 ->addFieldToFilter ( 'is_seller_confirmation', array ('nin' => array('No', 'Rejected')))
                 ->addFieldToFilter ( 'is_buyer_confirmation', array ('nin' => array('No','Rejected')))
                 ->addFieldToFilter ( 'order_status', array ('in' => array('pending','processing')))
                 ->addFieldToFilter ( 'item_order_status', array ('neq' => 'canceled'))
                 ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')));     
        $orderids = array();
        //echo  $ordersItems->getSelect();
        foreach($ordersItems as $items){
            $ordersFilter = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                     ->addFieldToSelect ( array('increment_id','is_buyer_confirmation','is_seller_confirmation') )
                     ->addFieldToFilter(  array('is_buyer_confirmation', 'is_seller_confirmation'),
                                            array(
                                                array('eq'=>'No'), 
                                                array('eq'=>'No')
                                            ))
                      ->addFieldToFilter ( 'increment_id', array ('eq' => $items->getIncrementId()));
              //echo  $ordersFilter->getSelect();//echo  $ordersFilter->getSize();
            if($ordersFilter->getSize() == '0'){
                array_push($orderids, $items->getIncrementId());
            }
        }
        $orderids = array_unique($orderids);
        //echo  '<pre>';print_r($orderids);echo  '</pre>';
        if(count($orderids) > '0' ){
            return count($orderids);
        }else{
            return '0';
        }  
    }

}

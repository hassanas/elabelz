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
 * Renderer to change the payment status
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_PayoutRequest extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to render data of received amount from admin
     *
     * Return the received amount
     *
     * @return float
     */
    public function render(Varien_Object $row) {
        $return = '';
        $seller_id = $row->getData ( $this->getColumn ()->getIndex () );
        $collection = Mage::getModel ( 'marketplace/payout' )->getCollection()->addFieldToFilter ( 'seller_id', $seller_id )
        ->addFieldToFilter ('status','Pending');
        $totalPayoutRequest = $collection->count();
        //->addFieldToFilter ('status','Pending')
        if($totalPayoutRequest > 0 ){
            $result = 'Pending';
        }else{

            $result ='';
        } 
        return $result;
    }

}


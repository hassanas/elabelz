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
 * Renderer to get the order date
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Notes extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to render order date 
     * 
     * Return the date
     * @return date
     */
    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $commision = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter('id',$value )
            ->addFieldToFilter('is_buyer_confirmation','Yes');
        $orderDetails = "<ul class='notes'>";
        foreach($commision as $com):
           $id = "note_".$com->getIncrementId()."_".$com->getProductId();
           $product_id = $com->getProductId();
           $notes = Mage::getModel('marketplace/notes')->getCollection()
                     ->addFieldToFilter('increment_id',$com->getIncrementId())
                     ->addFieldToFilter('item_id',$product_id)->getLastItem(); 
            $url = Mage::helper('adminhtml')->getUrl('marketplaceadmin/adminhtml_notes/index/', array('order_id'=>$com->getIncrementId(),'product_id'=>$product_id));
            if($notes->getNote()): 
            $orderDetails .="<li><a onclick='openPopupForm(\"{$url}\",".$id.",".$com->getIncrementId().",".$com->getProductId().")' id='$id'>".$notes->getNote()."</a></li>";
            else:
            $orderDetails .="<li><a onclick='openPopupForm(\"{$url}\",".$id.",".$com->getIncrementId().",".$com->getProductId().")' id='$id'>Enter Notes</a></li>"  ;
            endif;
        endforeach;
        $orderDetails .= "</ul>";
        return $orderDetails;
    }

}


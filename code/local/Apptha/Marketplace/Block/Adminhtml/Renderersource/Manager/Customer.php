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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Customer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to render commission of particular seller
     *
     * Return the commission percentage
     * @return int
     */
    public function render(Varien_Object $row) {
        $orderId = $row->getData($this->getColumn()->getIndex());
        $orderData = $row->getData();
        $ordersItems = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'is_buyer_confirmation' )
            ->addFieldToSelect ( 'created_at' )
            ->addFieldToFilter ( 'order_id', array ('eq' => $orderId))
            ->setOrder ( 'created_at', 'DESC' );

        $isBuyerConfirmation = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'increment_id' )
            ->addFieldToFilter ( 'is_buyer_confirmation', array ('eq' => 'No'))
            ->addFieldToFilter ( 'increment_id', array ('nin' => array('0','')))
            ->addFieldToFilter ( 'order_id', array ('eq' => $orderId));

        if($ordersItems->getSize()){
            if($isBuyerConfirmation->getSize()){// pending confirm items
                $circle = 'red';
            }else{// all confirm items
                $circle = 'green';
            }
            $current  = strtotime(date('Y-m-d H:i:s'));
            $timeArray = array();
            $resultItem = "";
            $itemRejectedCount = 0;
            foreach($ordersItems as $item){
                if($item['is_buyer_confirmation']=='No'){
                    $itemTime = strtotime($item['created_at']);
                    $timeArray[] = $current - $itemTime;
                    $resultItem .= "<div class='circ_outer'>
                                            <div class='circ circ_red'></div>
                                            <div class='circ_text small_circ_text'></div>
                                        </div>";
                }else{
                    $itemClass= "";
                    if($item['is_buyer_confirmation']=='Rejected'){
                        $status = '';
                        $itemClass= "circ_gray";
                        $itemRejectedCount++;
                    }else{
                        $status = '';
                        $itemClass= "circ_green";
                    }
                    $resultItem .= "<div class='circ_outer'>
                                            <div class='circ ".$itemClass."'></div>
                                            <div class='circ_text small_circ_text'>".$status."</div>
                                        </div>";
                }
            }
            rsort($timeArray); // get large time on top
            $timeAgo = Mage::helper('marketplace/marketplace')->timeAgo($timeArray[0]);
            if(count($ordersItems) == $itemRejectedCount) $circle = "gray";
            $result ="";
            // for csvExport only return status string instead of html
            if($this->getRequest()->getActionName()=="exportCsv"){
                if($circle=="red")
                    $result = "Pending ".$timeAgo;
                elseif($circle=="gray")
                    $result = "Cancled ".$timeAgo;
                elseif($circle=="green")
                    $result = "Confirmed";
                else
                    $result = "Unknown Status";
                return $result;
            }
            $result ="<div class='circ_text larg_circ_text '><br></div>
                           <div class='circ_outer'>
                            <div class='circ_large'>
                                <div class='circ_one circ_inner circ_".$circle."'> </div>
                                <div class='circ_two circ_inner circ_".$circle."'> </div>
                            </div>
                            <div class='circ_text larg_circ_text '>".$timeAgo."</div>
                        </div>";
            $result .= $resultItem;
            return $result;
            #if no items found in marketplace_commission table then show empty result with order status
        }else{
            return ucwords($orderData['status']).'<br> Items not found!.';
        }
    }

}

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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Supplier extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Function to render commission of particular seller
     *
     * Return the commission percentage
     * @return int
     */
    public function render(Varien_Object $row)
    {
        $orderId = $row->getData($this->getColumn()->getIndex());
        $image = Mage::getDesign()->getSkinBaseUrl(array('_area' => 'adminhtml')) . 'images/';
        $orderData = $row->getData();
        //get all order items
        $ordersItems = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('is_seller_confirmation')
            ->addFieldToSelect('is_buyer_confirmation')
            ->addFieldToSelect('is_buyer_confirmation_date')
            ->addFieldToSelect('created_at')
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->setOrder('created_at', 'DESC');
        //get collection of all confirmed and rejected order items
        $confirmation = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter('is_seller_confirmation', array('in' => array('Yes', 'Rejected')))
            ->addFieldToFilter('order_id', array('eq' => $orderId));
        if ($ordersItems->getSize()) {
            //if order items more then 1 then here check many to many relation
            if ($ordersItems->getSize() > 1) {
                if ($ordersItems->getSize() == $confirmation->getSize()) {// all confirm items
                    $firstHalfCircle = 'green';
                    $secondHalfCircle = 'green';
                    $orderState = "";
                } else {// pending confirm items
                    if ($confirmation->getSize() > 0) {
                        $firstHalfCircle = 'green';
                        $secondHalfCircle = 'red';
                        $orderState = "";
                    } else {
                        $firstHalfCircle = 'red';
                        $secondHalfCircle = 'red';
                        $orderState = "";
                    }
                }
                //if order has only one item then we check one to one relation
            } else if ($ordersItems->getSize() == 1) {
                if ($confirmation->getSize()) {
                    $firstHalfCircle = 'green';
                    $secondHalfCircle = 'green';
                    $orderState = "";
                } else {
                    $firstHalfCircle = 'red';
                    $secondHalfCircle = 'red';
                    $orderState = "";
                }
            }
            $current = strtotime(date('Y-m-d H:i:s'));
            $timeArray = array();
            $resultItem = "";
            $itemRejectedCount = 0;
            $atLeastOneItemInOrderConfirmationFromBuyerPending = false;
            $atLeastOneItemInOrdertConfirmationFromSellerPending = false;
            foreach ($ordersItems as $item) {
                if ($item['is_seller_confirmation'] == 'No') {
                    $resultItem .= "<div class='circ_outer'>
                                        <div class='circ circ_red'></div>
                                        <div class='circ_text small_circ_text'></div>
                                    </div>";
                } else {
                    $itemClass = "";
                    if ($item['is_buyer_confirmation'] == 'Rejected' or $item['is_seller_confirmation'] == 'Rejected') {
                        $reason = '';
                        $itemClass = "circ_gray";
                        $itemRejectedCount++;
                        // put gray logic here
                    } else {
                        $reason = '';
                        $itemClass = "circ_green";
                    }
                    $resultItem .= "<div class='circ_outer'>
                            <div class='circ " . $itemClass . "'></div>
                            <div class='circ_text small_circ_text'>" . $reason . "</div>
                        </div>";
                }
                if ($item['is_buyer_confirmation_date'] == '0000-00-00 00:00:00') {
                    $orderItemDate = strtotime($item['created_at']);
                } else {
                    $orderItemDate = strtotime($item['is_buyer_confirmation_date']);
                }
                $timeArray[] = $current - $orderItemDate;
                if ($item['is_buyer_confirmation'] == 'No') $atLeastOneItemInOrderConfirmationFromBuyerPending = true;
                if ($item['is_seller_confirmation'] == 'No') $atLeastOneItemInOrdertConfirmationFromSellerPending = true;
            }
            rsort($timeArray); // get large time on top
            $timeAgo = "";
            //we capture time pending from buyer but only show if seller has some pending confirmation
            if ($atLeastOneItemInOrderConfirmationFromBuyerPending == false)
                $timeAgo = Mage::helper('marketplace/marketplace')->timeAgo($timeArray[0]);
            if ($atLeastOneItemInOrdertConfirmationFromSellerPending == false)
                $timeAgo = "";
            if (count($ordersItems) == $itemRejectedCount) {
                $firstHalfCircle = "gray";
                $secondHalfCircle = "gray";
                $timeAgo = "";
            }
            $result ="";
            // for csvExport only return status string instead of html
            if($this->getRequest()->getActionName()=="exportCsv"){
                if($firstHalfCircle == "red" or $secondHalfCircle == "red")
                    $result = "Pending ".$timeAgo;
                elseif($firstHalfCircle == "gray" and $secondHalfCircle == "gray")
                    $result = "Cancled ".$timeAgo;
                elseif($firstHalfCircle == "green" and $secondHalfCircle == "green")
                    $result = "Confirmed";
                else
                    $result = "Unknown Status";
                return $result;
            }
            $result = "<div class='circ_text larg_circ_text '> " . $orderState . "<br></div>
                       <div class='circ_outer'>
                        <div class='circ_large'>
                            <div class='circ_one circ_inner circ_" . $firstHalfCircle . "'> </div>
                            <div class='circ_two circ_inner circ_" . $secondHalfCircle . "'> </div>
                        </div>
                        <div class='circ_text larg_circ_text '>" . $timeAgo . "</div>
                    </div>";
            $result .= $resultItem;
            return $result;
            //if no items found in marketplace_commission table then show empty result with order status
        } else {
            return ucwords($orderData['status']) . '<br> Items not found!.';
        }
    }
}
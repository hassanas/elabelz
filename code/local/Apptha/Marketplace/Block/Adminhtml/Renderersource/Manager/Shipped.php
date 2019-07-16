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
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Shipped extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
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
        $orderData = $row->getData();
        $ordersItems = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->setOrder('created_at', 'DESC');
        if ($ordersItems->getSize()) {
            $firstHalfCircle = '';
            $secondHalfCircle = '';
            if ($orderData['status'] == "shipped_from_elabelz") {
                $firstHalfCircle = 'green';
                $secondHalfCircle = 'green';
                $orderState = "";
            } else if ($orderData['status'] == 'successful_delivery_partially' || $orderData['status'] == 'failed_delivery') {
                $firstHalfCircle = 'green';
                $secondHalfCircle = 'green';
                $orderState = "";
            } else if ($orderData['status'] == 'successful_delivery' || $orderData['status'] == "complete") {
                $firstHalfCircle = 'green';
                $secondHalfCircle = 'green';
                $orderState = "";
            } else if ($orderData['status'] == 'canceled') {
                $firstHalfCircle = 'gray';
                $secondHalfCircle = 'gray';
                $orderState = "";
            } else {
                $firstHalfCircle = 'red';
                $secondHalfCircle = 'red';
                $orderState = "";
            }
            $current = strtotime(date('Y-m-d H:i:s'));
            $timeArray = array();
            $resultItem = "";
            $atLeastOneItemInOrdertConfirmationFromSellerPending = false;
            foreach ($ordersItems as $item) {
                if ($item['item_order_status'] == 'pending' || $item['item_order_status'] == 'processing') {
                    $resultItem .= "<div class='circ_outer'>
                                        <div class='circ circ_red'></div>
                                        <div class='circ_text small_circ_text'></div>
                                    </div>";
                } else if ($item['item_order_status'] == 'rejected_customer' || $item['item_order_status'] == 'rejected_seller' || $item['item_order_status'] == 'canceled') {
                    $resultItem .= "<div class='circ_outer'>
                                        <div class='circ circ_gray'></div>
                                        <div class='circ_text small_circ_text'></div>
                                    </div>";
                } else if ($item['order_status'] == 'shipped_from_elabelz' ||
                    $item['order_status'] == 'successful_delivery' ||
                    $item['order_status'] == 'complete' ||
                    $item['item_order_status'] == 'failed_delivery' ||
                    $item['order_status'] == 'successful_delivery_partially'
                ) {
                    $resultItem .= "<div class='circ_outer'>
                                        <div class='circ circ_green'></div>
                                        <div class='circ_text small_circ_text'></div>
                                    </div>";
                } else {
                    $resultItem .= "<div class='circ_outer'>
                                <div class='circ circ_red'></div>
                                <div class='circ_text small_circ_text'></div>
                            </div>";
                }
                if ($item['is_seller_confirmation_date'] == '0000-00-00 00:00:00') {
                    $orderItemDate = strtotime($item['created_at']);
                } else {
                    $orderItemDate = strtotime($item['is_seller_confirmation_date']);
                }
                $timeArray[] = $current - $orderItemDate;
                if ($item['is_seller_confirmation'] == 'No') $atLeastOneItemInOrdertConfirmationFromSellerPending = true;
            }
            rsort($timeArray); // get large time on top
            $timeAgo = "";
            if ($atLeastOneItemInOrdertConfirmationFromSellerPending == false && $orderData['status'] != "shipped_from_elabelz" && $orderData['status'] != "canceled" && $orderData['status'] != "complete" && $orderData['status'] != "successful_delivery" && $orderData['status'] != "failed_delivery") {
                $timeAgo = Mage::helper('marketplace/marketplace')->timeAgo($timeArray[0]);
            }
            $result ="";
            // for csvExport only return status string instead of html
            if($this->getRequest()->getActionName()=="exportCsv"){
                if($firstHalfCircle == "red" or $secondHalfCircle == "red")
                    $result = "Pending ".$timeAgo;
                elseif($firstHalfCircle == "gray" and $secondHalfCircle == "gray")
                    $result = "Cancled ".$timeAgo;
                elseif($firstHalfCircle == "green" and $secondHalfCircle == "green")
                    $result = "Confirmed ";
                else
                    $result = "Unknown Status";
                return $result;
            }

            $result = "<div class='circ_text larg_circ_text '> " . ucwords($orderState) . "<br></div>
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
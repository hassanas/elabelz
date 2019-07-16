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
 * This file is used to add/edit seller products
 */
class Apptha_Marketplace_FareyePushbackApiController extends Mage_Core_Controller_Front_Action
{
    CONST SECRET_TOKEN = "1bRtfmoW7jnEio0O";

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Will read the JSON stream from Fareye and call the appropriate method, the response if any must also be in JSON.
     *
     * @return void
     */
    public function indexAction()
    {
        $token = $this->getRequest()->getParam('token');
        $data = file_get_contents('php://input');
        $data = json_decode($data);
        $jobType = str_replace('_', '', $data->jobType);
        $token = $this->getRequest()->getParam('token');

        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        if (isset($jobType) && method_exists($this, $jobType) && $token == self::SECRET_TOKEN) {
            try {
                $response = $this->$jobType($data);
                if(!empty($response)) {
                    $this->getResponse()->setBody(json_encode($response));
                }
            } catch (Exception $e) {
                //Any errors would be caught up here.
                $this->getResponse()->setBody(json_encode($e->getMessage()));
            }
        } else {
            $this->getResponse()->setBody("Bad request");
        }

        return;
    }

    protected function cancelorder($data)
    {
        $refInfo = explode('-', trim($data->referenceNumber));
        $orderId = $refInfo[0];
        $itemId = $refInfo[1];
        $commissionId = 0;
        $cancelRemarks = ""; // Cancel order remarks

        // Capturing the cancel remarks
        if (trim($data->cancelRemarks) != "") {
            $cancelRemarks = trim($data->cancelRemarks);
        }
        // Getting commissionId from the model
        $commissionId = Mage::getModel('marketplace/commission')->getCommissionId($orderId, $itemId);
        if ($commissionId > 0 && trim($data->Status) == 'SUCCESS') {
            // updating the cancel request for order
            if (Mage::getModel('marketplace/commission')->setCancelRequestSellerConfirmation(1)->setId($commissionId)->save()) {
                //updating remarks for order cancellation
                Mage::getModel('marketplace/commission')->setCancelRequestSellerRemarks($cancelRemarks)->setId($commissionId)->save();
                // Generating the response on the action performed
                $message = $this->getMessage("success");
            }
        } else {
            $message = $this->getMessage("error");
        }
        return $message;
    }

    /**
     * Customer confirmation from Fareye is at order level, so it should update statueses of all confirmations included in this order
     * Sample input:
     * {
     * "runsheetNo": "ZLA/manager1_zla/2016-05-13",
     * "actualAmount": null,
     * "originalAmount": null,
     * "moneyTransactionType": null,
     * "referenceNumber": "100000230 - attempt:1",
     * "latitude": 0,
     * "longitude": 0,
     * "attemptCount": 1,
     * "jobType": "customer_confirm",
     * "employeeCode": "manager1_zla",
     * "hubCode": "abu",
     * "status": "SUCCESS",
     * "transactionDate": "2016-05-13 18:58:21",
     * "erpPushTime": null,
     * "lastTransactionTime": "2016-05-13 18:59:00",
     * "battery": 0,
     * "fieldData": {
     * "remarks": "testst"
     * }
     * }
     */
    protected function customerconfirm($data)
    {
        //print_r($data);
        //Customer Confirm referenceNo format in the Push back is OrderId-attempt:number e.g. OrderId-attempt:1
        $refInfo = explode('-', trim($data->referenceNumber));
        $orderId = trim($refInfo[0]);
        $remarks = trim($data->fieldData->remarks);
        $commissionId = 0;

        // Getting commissionId from the model

        // Updating the Customer Status in Commission Table
        $collection = Mage::getModel('marketplace/commission')->getCollection();//update whole order collection
        $collection->addFieldToFilter('increment_id', $orderId);
        $commissionIds = "";

        //complete comma separated list of commission IDs
        foreach ($collection as $commission) {
            if (empty($commissionIds)) {
                $commissionIds = $commission->getId();
            } else {
                $commissionIds .= "," . $commission->getId();
            }
        }
        $newStatus = "";
        if (trim($data->status) == 'SUCCESS') {
            $newStatus = "Yes";
        } elseif (trim($data->status) == "order_cancelled") {
            //cancel whole order
            $message = $this->cancelCompleteOrder($orderId,$remarks);
        } else {
            //TODO: create a new field to log remarks from fieldData
            $newStatus = "Yes";
        }
        if (!empty($newStatus)) {
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $table = $resource->getTableName('marketplace/commission');
            $query = "UPDATE {$table} SET is_buyer_confirmation = '{$newStatus}' WHERE id IN ($commissionIds);";
            if ($writeConnection->query($query)) {
               $message = $this->getMessage("success");
               Mage::log("Record successfully updated in customerconfirm from Fareye Pushback.");
            } else {
                $message = $this->getMessage("error");
                Mage::log("Record couldn't be updated in customerconfirm from Fareye Pushback.");
            }
        }
        return $message;
    }

    /**
     * Merchant confirm from fareye is at order-item level, so at one time one order item shall be entertained.
     * Sample Input:
     * {
     * "runsheetNo": "ZLA/106_zla/2016-05-13",
     * "actualAmount": null,
     * "originalAmount": null,
     * "moneyTransactionType": null,
     * "referenceNumber": "100000229-1546",
     * "latitude": 0,
     * "longitude": 0,
     * "attemptCount": 1,
     * "jobType": "merchant_confirm",
     * "employeeCode": "106_zla",
     * "hubCode": "abu",
     * "status": "SUCCESS",
     * "transactionDate": "2016-05-13 16:38:06",
     * "erpPushTime": null,
     * "lastTransactionTime": "2016-05-13 16:58:06",
     * "battery": 0,
     * "fieldData": {}
     * }
     */
    protected function merchantconfirm($data)
    {
        $refInfo = explode('-', trim($data->referenceNumber));
        $orderId = trim($refInfo[0]);
        $itemId = trim($refInfo[1]);
        $commissionId = 0;

        // Getting commissionId from the model

        $commissionId = Mage::getModel('marketplace/commission')->getCommissionId($orderId, $itemId);

        if ($data->status == 'SUCCESS') {
            $newStatus = "Yes";
        } elseif ($data->status == "customer_cancel") {
            //cancel the specific item in the order
            if(Mage::getModel('marketplace/commission')->setOrderStatus('cancel')->setId($commissionId)->save()) {
                $message = $this->getMessage("success");
                Mage::log("Record successfully updated in merchantconfirm from Fareye Pushback.");
            } else {
                $message = $this->getMessage("error");
                Mage::log("Record couldn't be updated in merchantconfirm from Fareye Pushback.");
            }
        } else {
            //TODO: create a new field to log remarks from fieldData
            $newStatus = "No";
        }

        if(!empty($newStatus)) {
            if(Mage::getModel('marketplace/commission')->setIsSellerConfirmation($newStatus)->setId($commissionId)->save()) {
                $message = $this->getMessage("success");
                Mage::log("Record successfully updated in merchantconfirm from Fareye Pushback.");
            } else {
                $message = $this->getMessage("error");
                Mage::log("Record couldn't be updated in merchantconfirm from Fareye Pushback.");
            }
        }
        return $message;
    }

    protected function merchantdel($data)
    {
        print_r($data);
        return;
    }

    protected function cancelCompleteOrder($orderId,$remarks)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('marketplace/commission');
        $query = "UPDATE {$table} SET order_status = 'cancel' WHERE increment_id = $orderId;";
        if($writeConnection->query($query)) {

            //Updating remarks field only in case of order_cancelled

            $query = "UPDATE {$table} SET cancel_request_seller_remarks = '{$remarks}' WHERE increment_id = $orderId;";
            $writeConnection->query($query);

            $message = $this->getMessage("success");
            Mage::log("Record successfully updated in merchantconfirm from Fareye Pushback.");
        } else {
            $message = $this->getMessage("error");
            Mage::log("Record couldn't be updated in customerconfirm from Fareye Pushback.");
        }
        return $message;
    }

    protected function getMessage($type, $msg = "")
    {
        $defaultMsg = ($type == "error") ? ['status' => "error", 'msg' => "There is some error performing update"] : ['status' => "success", 'msg' => "Record updated successfully."];
        return (!empty($msg)) ? ['success' => $type, 'msg' => $msg] : $defaultMsg;
    }

}
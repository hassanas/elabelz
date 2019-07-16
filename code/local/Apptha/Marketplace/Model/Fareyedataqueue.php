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
class Apptha_Marketplace_Model_Fareyedataqueue extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('marketplace/fareyedataqueue');
    }

    /**
     * Push Magento Queued orders to Fareye
     */
    public function pushToFareye()
    {
        $collection = Mage::getModel('marketplace/fareyedataqueue')->getCollection();
        $collection->addFieldToFilter('processed', 'queued');
        foreach ($collection as $queued) {
            $fareye = Mage::getModel('marketplace/fareyedataqueue')->load($queued->getId());
            $processData = $fareye->getData();
            if (empty($processData['merchant_code']) || empty($processData['product_name'])) {
                //$fareye->setProcessed("paused");
                $fareye->setProcessed("queued");//queued so that in case the seller information is filled, their orders can be placed to fareye
                $fareye->setPausedReason("One of the necessary field is missing (merchant_code, product_name).");
                $fareye->save();
                continue;
            } else {
                $fareye->setProcessed("processing");
                $fareye->save();
                //check if seller is pushed if not push it first.
                $sellerInfo = Mage::getModel('marketplace/sellerprofile')->load($processData['merchant_code'],
                    'seller_id');
                if ($sellerInfo->getPushedToFareye() !== 1) {
                    $this->pushSellerToFareye($sellerInfo->getSellerId());
                }
            }
            /*echo "<pre>";
            print_r($processData);
            echo "</pre>";*/
            $processData['merchant_id'] = $processData['merchant_code'];
            $merchantCode = $processData['merchant_code'];//remove merchant_code from processData after this
            unset($processData['merchant_code']);
            unset($processData['id']);
            unset($processData['created_at']);
            unset($processData['processed_at']);
            unset($processData['processed']);
            //merchant_name, amount_to_be_collected got removed from fareye process so unset it here too
            unset($processData['merchant_name']);
            unset($processData['amount_to_be_collected']);
            unset($processData['paused_reason']);

            //echo "<pre>";
            //print_r($processData);
            $processData = array_filter($processData);

            //format phone numbers as per fareye
            $processData['customer_contact_number'] = $this->getFareyeFormatPhoneNumber($processData['customer_contact_number']);
            $processData['merchant_contact_number'] = $this->getFareyeFormatPhoneNumber($processData['merchant_contact_number']);

            //fill other required fields by the fareye, if they are empty
            $processData['address_line2'] = (empty($processData['address_line2'])) ? "Nill" : $processData['address_line2'];
            $processData['other_product_options'] = (empty($processData['other_product_options'])) ? "Not included" : $processData['other_product_options'];

            //print_r($processData);
            $data = [
                [
                    'merchantCode' => $merchantCode,//remove merchant_code from processData after this
                    'referenceNumber' => $processData['product_unique_id'],
                    'processDefinitionCode' => 'main_process',
                    'processData' => $processData,
                    'processUserMappings' => []
                ]
            ];
            $jsonData = json_encode($data);
            /*echo "<pre>";
            echo $jsonData;
            echo "</pre>";*/
            try {
                $url = Mage::helper('marketplace/Url')->getFareyeNewProcessUrl(false);
                //echo $url;
                $httpClient = new Varien_Http_Client($url);
                $response = $httpClient->setRawData($jsonData, 'application/json')->request('POST');
                //print_r($response);
                if ($response->isSuccessful()) {
                    $jsonRespnse = $response->getBody();
                    $responseStdObj = json_decode($jsonRespnse);
                    //print_r($responseStdObj);
                    if ($responseStdObj->failCount > 0) {
                        //print_r($responseStdObj->failureList[0]->failReason);
                        if (strpos($responseStdObj->failureList[0]->failReason, "already exist") !== false) {
                            //if record already exist error is there mark it as processed
                            $fareye->setProcessed("processed");
                            //log it too and mention that it has changed to processed
                            $referenceNumber = $responseStdObj->failureList[0]->referenceNumber;
                            Mage::log("FAREYE Due to Already Exist Error it was changed to Processed: ReferenceNumber # $referenceNumber");
                        } else {
                            //if there is something else log it
                            $fareye->setProcessed("queued");
                            Mage::log("FAREYE: " . $responseStdObj->failureList[0]->failReason);
                        }
                    } else {
                        //successfully pushed
                        $fareye->setProcessed("processed");
                    }
                } else {
                    Mage::log("FAREYE: " . print_r($response, true));
                    $fareye->setProcessed("queued");
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $fareye->setProcessed("queued");
            } finally {
                $fareye->save();
            }
        }
    }

    public function pushSellerToFareye($sellerId)
    {
        $sellerInfo = Mage::getModel('marketplace/sellerprofile')->load($sellerId, 'seller_id');
        //$sellerTitle = $sellerInfo['store_title'];
        $sellerArray = $sellerInfo->getData();
        $jsonData = [
            [
                'dsMasterCode' => "merchant",
                'hubCodeList' => [],
                'fieldExecutiveList' => [],
                'attributeData' => [
                    'merchant_contact_number' => $this->getFareyeFormatPhoneNumber($sellerArray['contact']),
                    'address' => (empty($sellerArray['supplier_address'])) ? "Actual location (Not specified)" : $sellerArray['supplier_address'] .
                        " Country: " . $sellerArray['country'] . ", State: " . $sellerArray['state'],
                    'merchant_name' => $sellerArray['store_title'],
                    'merchant_id' => $sellerArray['seller_id']
                ]
            ]
        ];
        $url = Mage::helper('marketplace/url')->getFareyeAddSourceUrl(false);
        $httpClient = new Varien_Http_Client($url);
        $jsonData = json_encode($jsonData);
        $response = $httpClient->setRawData($jsonData, 'application/json')->request('POST');
        if ($response->isSuccessful()) {
            $jsonRespnse = $response->getBody();
            $responseStdObj = json_decode($jsonRespnse);
            //echo "<pre>";
            //print_r($responseStdObj);
            if (count($responseStdObj->successMessage) > 0) {
                //successfully created
                $sellerInfo->setPushedToFareye(1);
                Mage::log("Merchant to fareye via pushSellerToFareye.");
            }
        }
        $sellerInfo->setPushedToFareye(0);
    }

    /**
     * Returns TRUE in case of success otherwise FALSE
     * @return boolean
     */
    public function pushCustomerConfirmationToFareye($commissionId)
    {
        $commission = Mage::getModel('marketplace/commission')->load($commissionId, 'id');
        if ($commission->getIsBuyerConfirmation() == "Yes") {
            $data = [
                [
                    'referenceNumber' => $commission->getIncrementId() . "-" . $commission->getProductId(),
                    'jobCode' => "customer_confirm",
                    'newStatusCode' => "SUCCESS"
                ]
            ];
            $url = Mage::helper('marketplace/url')->getFareyeUpdateTransUrl(false);
            $httpClient = new Varien_Http_Client($url);
            $jsonData = json_encode($data);
            $response = $httpClient->setRawData($jsonData, 'application/json')->request('POST');
            if ($response->isSuccessful()) {
                $jsonRespnse = $response->getBody();
                $responseStdObj = json_decode($jsonRespnse);
                if (count($responseStdObj->successMessage) > 0) {
                    //pushed to fareye
                    Mage::log("Data pushed to fareye via pushCustomerConfirmationToFareye.");
                    return true;
                } else {
                    Mage::log("Push to Fareye error in pushCustomerConfirmationToFareye. The response error from Fareye is: " . print_r($responseStdObj->successMessage,
                            true));
                }
            }
        }
        return false;
    }

    /**
     * Returns TRUE in case of success otherwise FALSE
     * @return boolean
     */
    public function pushMerchantConfirmationToFareye($commissionId)
    {
        //successfully created
        $commission = Mage::getModel('marketplace/commission')->load($commissionId, 'id');
        if ($commission->getIsSellerConfirmation() == "Yes") {
            $data = [
                [
                    'referenceNumber' => $commission->getIncrementId() . "-" . $commission->getProductId(),
                    'jobType' => "merchant_confirm",
                    'newStatusCode' => "SUCCESS"
                ]
            ];
            $url = Mage::helper('marketplace/url')->getFareyeUpdateTransUrl(false);
            //echo $url;
            $httpClient = new Varien_Http_Client($url);
            $jsonData = json_encode($data);
            //echo ">>>".$jsonData;
            $response = $httpClient->setRawData($jsonData, 'application/json')->request('POST');
            //echo "<pre>";print_r($response);
            //die();
            if ($response->isSuccessful()) {
                $jsonRespnse = $response->getBody();
                $responseStdObj = json_decode($jsonRespnse);
                if (count($responseStdObj->successMessage) > 0) {
                    //pushed to fareye
                    Mage::log("Data pushed to fareye via pushMerchantConfirmationToFareye.");
                    return true;
                } else {
                    Mage::log("Push to Fareye error in pushMerchantConfirmationToFareye. The response error from Fareye is: " . print_r($responseStdObj->successMessage,
                            true));
                }
            }
            return false;
        }
    }

    public function getFareyeFormatPhoneNumber($number)
    {
        $number = preg_replace("/[^0-9]/", "", $number);
        return (empty($number)) ? "0" : $number;
    }
}

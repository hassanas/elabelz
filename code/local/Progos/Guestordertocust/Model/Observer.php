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
 * @category    Guestordertocustomer
 * @package     Progos_Guestordertocustomer
 * @version     0.1.7
 * @author      Humaira Batool (humaira.batool@progos.org)
 */

/**
 * Event Observer
 */
class Progos_Guestordertocust_Model_Observer {

	public function successAfterOrder($observer) {
	    $orderIds = $observer->getEvent()->getOrderIds();
        $orderId = ( int ) Mage::app()->getRequest()->getParam ( 'order_id' );
        if($orderId == ""){
           $orderId = $orderIds[0];
        }
        $order = Mage::getModel('sales/order')->load($orderId);
        $groupId = 1;
       
        if (! $order->getId()) {
            Mage::getSingleton('core/session')->addError('No Order ID found to convert');
            //$this->_redirect('*/*/index');
            //return $this;
        }
        /*
         * Condition added by Naveed Abbas to fix newrelic error for mobile app cron
         */
        $mdlRestmob = Mage::getModel('restmob/quote_index');
        $id = $mdlRestmob->getIdByQuoteId($order->getQuoteId());
        if (!$id) {
            $customer = Mage::getModel('customer/customer')->setWebsiteId($order->getStore()->getWebsiteId())->loadByEmail($order->getCustomerEmail());
            if ($customer->getId()) {
                $groupId = $customer->getGroupId();
            } else { //create a new customer based on the order
                $customer->addData(array(
                    "prefix" => $order->getCustomerPrefix(),
                    "firstname" => $order->getCustomerFirstname(),
                    "middlename" => $order->getCustomerMiddlename(),
                    "lastname" => $order->getCustomerLastname(),
                    "suffix" => $order->getCustomerSuffix(),
                    "email" => $order->getCustomerEmail(),
                    "group_id" => $groupId,
                    "taxvat" => $order->getCustomerTaxvat(),
                    "website_id" => $order->getStore()->getWebsiteId(),
                    "store_id" => $order->getStoreId(),
                    "is_admin_created" => "1",
                    'default_billing' => '_item1',
                    'default_shipping' => '_item2',
                    'gender' => $order->getCustomerGender(),
                    'customer_country' => $order->getShippingAddress()->getCountry(),
                ));

                //Billing Address
                /** @var $billingAddress Mage_Sales_Model_Order_Address */
                $billingAddress = $order->getBillingAddress();
                if (!empty($billingAddress)) {
                    /** @var $customerBillingAddress Mage_Customer_Model_Address */
                    $customerBillingAddress = Mage::getModel('customer/address');
                    $billingAddressArray = $billingAddress->toArray();
                    unset($billingAddressArray['entity_id']);
                    unset($billingAddressArray['entity_type_id']);
                    unset($billingAddressArray['parent_id']);
                    unset($billingAddressArray['customer_id']);
                    unset($billingAddressArray['customer_address_id']);
                    unset($billingAddressArray['quote_address_id']);
                    $customerBillingAddress->addData($billingAddressArray);
                    $customerBillingAddress->setPostIndex('_item1');
                    $customer->addAddress($customerBillingAddress);
                }

                //Shipping Address
                /** @var $shippingAddress Mage_Sales_Model_Order_Address */
                $shippingAddress = $order->getShippingAddress();
                /** @var $customerShippingAddress Mage_Customer_Model_Address */
                $customerShippingAddress = Mage::getModel('customer/address');

                if (!empty($shippingAddress)) {
                    $shippingAddressArray = $shippingAddress->toArray();
                    unset($shippingAddressArray['entity_id']);
                    unset($shippingAddressArray['entity_type_id']);
                    unset($shippingAddressArray['parent_id']);
                    unset($shippingAddressArray['customer_id']);
                    unset($shippingAddressArray['customer_address_id']);
                    unset($shippingAddressArray['quote_address_id']);
                    $customerShippingAddress->addData($shippingAddressArray);
                    $customerShippingAddress->setPostIndex('_item2');
                    $customer->addAddress($customerShippingAddress);
                }

                //Save the customer
                $customer->setPassword($customer->generatePassword());
                $customer->save();
                $customer->setConfirmation(null);
                $customer->setIsActive(1);
                $customer->save();

                if ($customer->getId()) {
                    try {
                        $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                        $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                        $customer->sendAccountResetConfirmationEmail();

                    } catch (Exception $exception) {
                        Mage::getSingleton('adminhtml/session')->addError($exception->getMessage());
                    }
                }

                Mage::getSingleton('adminhtml/session')->addSuccess('The guest %s is converted to customer', $order->getCustomerEmail());
            }
            $order->setCustomerId($customer->getId());
            $order->setCustomerIsGuest('0');
            $order->setCustomerGroupId($groupId);
            $order->save();
        }
	}
}
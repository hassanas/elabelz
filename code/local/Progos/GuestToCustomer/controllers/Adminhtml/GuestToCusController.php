<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Progos_GuestToCustomer_Adminhtml_GuestToCusController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('customer/progos_guesttocustomer_item');
    }

    protected function _initLayout()
    {
        $this->loadLayout()
            ->_setActiveMenu('customer/progos_guesttocustomer_item')
            ->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Convert Guest Orders To Customers'),
            Mage::helper('adminhtml')->__('Convert Guest Orders To Customers')
        );
        return $this;
    }
    

    public function indexAction()
    {
        $this->_title($this->__('Customers'))->_title($this->__('Convert Guest Orders To Customers'));
        $this->_initLayout()
            ->_addContent($this->getLayout()->createBlock('GuestToCustomer/adminhtml_customers'))
            ->renderLayout();
    }
    
    public function massConvertAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');
        $groupId = $this->getRequest()->getPost('group_id');
        if (! $orderIds)
        {
            Mage::getSingleton('adminhtml/session')->addError($this->__('No Order ID found to convert'));
            $this->_redirect('*/*/index');
            return;
        }
        foreach ($orderIds as $orderId)
        {
            $this->convertAction($orderId, $groupId, true);
        }
        $this->_redirect('*/*/index');
    }

    public function convertAction($orderId = NULL, $groupId = NULL, $isMass = false)
    {   
        if($orderId == "") {
            $orderId = $this->getRequest()->getParam('order_id');
        }

        if($groupId == "") {
            $groupId = $this->getRequest()->getParam('group_id');
        }
        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        
        if (! $order->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('No Order ID found to convert'));
            $this->_redirect('*/*/index');
            return $this;
        }
        
        /** @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')->setWebsiteId($order->getStore()->getWebsiteId())->loadByEmail($order->getCustomerEmail());
        if ($customer->getId()) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The customer %s already exists. So the customer has been merged', $order->getCustomerEmail()));
        } else { //create a new customer based on the order
            $customer->addData(array(
                "prefix"         => $order->getCustomerPrefix(),
                "firstname"      => $order->getCustomerFirstname(),
                "middlename"     => $order->getCustomerMiddlename(),
                "lastname"       => $order->getCustomerLastname(),
                "suffix"         => $order->getCustomerSuffix(),
                "email"          => $order->getCustomerEmail(),
                "group_id"       => $groupId,
                "taxvat"         => $order->getCustomerTaxvat(),
                "website_id"     => $order->getStore()->getWebsiteId(),
                "store_id"       => $order->getStoreId(),
                "is_admin_created"       => "1",
                'default_billing'=> '_item1',
                'default_shipping'=> '_item2',
            ));

            //Billing Address
            /** @var $billingAddress Mage_Sales_Model_Order_Address */
            $billingAddress = $order->getBillingAddress();
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

            //Shipping Address
            /** @var $shippingAddress Mage_Sales_Model_Order_Address */
            $shippingAddress = $order->getShippingAddress();
            /** @var $customerShippingAddress Mage_Customer_Model_Address */
            $customerShippingAddress = Mage::getModel('customer/address');
            
            if(!empty($shippingAddress)) {
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
            $customer->setIsSubscribed(false);
            $customer->setPassword($customer->generatePassword());
            $customer->save();
            
            $customer->sendNewAccountEmail();   
                    
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The guest %s is converted to customer', $order->getCustomerEmail()));
        }
        $order->setCustomerId($customer->getId());
        $order->setCustomerIsGuest('0');
        $order->setCustomerGroupId($groupId);
        $order->save();
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order %s has been been assigned to the customer (%s)', $order->getIncrementId(), $order->getCustomerEmail()));
        if (!$isMass) $this->_redirect('*/*/index');
        return $this;
    }
}
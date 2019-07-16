<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_ViewController extends Mage_Core_Controller_Front_Action
{
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
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        if (!$this->_getSession()->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Action postdispatch
     *
     * Remove No-referer flag from customer session after each action
     */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->_getSession()->unsNoReferer(false);
    }

    public function processAction()
    {
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');
        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $model */
        $model = Mage::getModel('mageworx_orderssurcharge/surcharge');
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');

        // Validate surcharge id in request
        $surchargeId = $this->getRequest()->getParam('surcharge_id');
        if (!$surchargeId) {
            $coreSession->addError($helper->__('Can not process surcharge with empty id'));
            $this->_redirect('*/*');
            return;
        }

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $surcharge */
        $surcharge = $model->load($surchargeId);

        // Validate surcharge
        if (!$surcharge || !$surcharge->getEntityId()) {
            $coreSession->addError($helper->__('Empty surcharge'));
            $this->_redirect('*/*');
            return;
        }

        // Validate surcharge by customer
        $surchargeCustomerId = $surcharge->getCustomerId();
        $customerId = $this->_getSession()->getCustomerId();
        if (!$customerId || !$surchargeCustomerId || $customerId != $surchargeCustomerId) {
            $coreSession->addError($helper->__('Please, check your login information'));
            $this->_redirect('*/*');
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($surcharge->getParentOrderId());

        // Validate parent order
        if (!$order->getId()) {
            $coreSession->addError($helper->__('Empty parent order'));
            $this->_redirect('*/*');
            return;
        }

        // Remove existing customer quote and add new quote with surcharge
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->_getSession()->getCustomer();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('mageworx_orderssurcharge/quote')->createNewQuote($surchargeId, $customer);
        $this->_getSession()->setQuoteId($quote->getId());
        $quote->collectTotals();
        $quote->save();

        $coreSession->addSuccess($helper->__('Surcharge quote was successfully created for order #%s', $order->getIncrementId()));
        $this->_redirect('checkout/cart');
    }

    public function deleteAction()
    {
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');
        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $model */
        $model = Mage::getModel('mageworx_orderssurcharge/surcharge');
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');

        // Validate surcharge id in request
        $surchargeId = $this->getRequest()->getParam('surcharge_id');
        if (!$surchargeId) {
            $coreSession->addError($helper->__('Can not process surcharge with empty id'));
            $this->_redirect('*/*');
            return;
        }

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $surcharge */
        $surcharge = $model->load($surchargeId);

        // Validate surcharge
        if (!$surcharge || !$surcharge->getEntityId()) {
            $coreSession->addError($helper->__('Empty surcharge'));
            $this->_redirect('*/*');
            return;
        }

        // Validate surcharge by customer
        $surchargeCustomerId = $surcharge->getCustomerId();
        $customerId = $this->_getSession()->getCustomerId();
        if (!$customerId || !$surchargeCustomerId || $customerId != $surchargeCustomerId) {
            $coreSession->addError($helper->__('Please, check your login information'));
            $this->_redirect('*/*');
            return;
        }

        /** @var Mage_Sales_Model_Order $parentOrder */
        $parentOrder = Mage::getModel('sales/order')->load($surcharge->getParentOrderId());

        // Validate parent order
        if (!$parentOrder->getId()) {
            $coreSession->addError($helper->__('Empty parent order'));
            $this->_redirect('*/*');
            return;
        }

        // TODO: Make correct surcharge cancellation for this case
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($surcharge->getOrderId());
        // Validate surcharge order
        if ($order->getId()) {
            $coreSession->addError($helper->__('Order #%s need to be canceled', $order->getIncrementId()));
            $this->_redirect('*/*');
            return;
        }

        // TODO: correct message: why we cant delete this surcharge?
        if (!$surcharge->canDelete()) {
            $coreSession->addError($helper->__('Can not delete this surcharge'));
            $this->_redirect('*/*');
            return;
        }

        $surchargeId = $surcharge->getId();
        $surcharge->deleteByCustomer();

        $coreSession->addSuccess($helper->__('Surcharge #%d was successfully deleted for order #%s', $surchargeId, $parentOrder->getIncrementId()));
        $this->_redirectReferer('customer/account');
    }

    /**
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}
<?php
/*
Telrtransparent Payment Controller
By: Naveed Abbas
*/

class Progos_Telrtransparent_PaymentController extends Mage_Core_Controller_Front_Action
{

    const STATUS_PENDING_CUSTOMER = 'pending_customer_confirmation';

    // The redirect action is triggered when someone places an order
    public function redirectAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'telrtransparent', array('template' => 'telrtransparent/redirect.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    public function successCbAction()
    {
        //in case if we need to capture telr reference id with order
    }

    // The response action is triggered when your gateway sends back a response after processing the customer's payment
    public function successAction()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, self::STATUS_PENDING_CUSTOMER, 'Gateway has authorized the payment.');
        $order->sendNewOrderEmail();
        $order->setEmailSent(true);
        $order->save();
        Mage::getSingleton('checkout/session')->unsQuoteId();
        Mage_Core_Controller_Varien_Action::_redirect('onestepcheckout/index/success', array('_secure' => true));

    }

    public function cancelCbAction()
    {
        //in case if we need to do some functionality in cancel action
    }

    // The cancel action is triggered when an order is to be cancelled
    public function cancelAction()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($order->getId()) {
                // Flag the order as 'cancelled' and save it
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.')->save();
                Mage_Core_Controller_Varien_Action::_redirect('onestepcheckout/index/index/cc/back', array('_secure' => true));
            }
        }
    }

    /**
     *  This function will be called after checkoutdotcom form submitted
     */
    public function checkoutdotcomAction()
    {
        $orderId = $_REQUEST['orderId'];
        $_order = new Mage_Sales_Model_Order();
        $_order->loadByIncrementId($orderId);
        if ($_order->getId()) {
            $response = Mage::getModel('telrtransparent/checkoutdotcom')->payViaCheckoutDotCom($_order);
            $params = array('orderId' => $orderId);
            if (isset($response['errors']) || $response['status'] == 'Declined') {
                $this->_redirect('telrtransparent/payment/cancel', array('_query' => $params));
            } else {
                $this->_redirect('telrtransparent/payment/success', array('_query' => $params));
            }
        }
    }
}
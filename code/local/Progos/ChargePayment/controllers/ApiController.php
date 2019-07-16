<?php
/**
 * This Module created to extend the functionality of CheckoutApi_ChargePayment
 *
 * @category       Progos
 * @package        Progos_ProductsUpdater
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           15-08-2017 12:04
 */
require_once 'CheckoutApi/ChargePayment/controllers/ApiController.php';


class Progos_ChargePayment_ApiController extends CheckoutApi_ChargePayment_ApiController {

    /**
     * This function Extended to change the order statuses(in case of cancel ) as per our custom requirment
     * Action for verify charge by card token
     *
     * @url chargepayment/api/hosted/
     */
    public function hostedAction() {
        $cardToken          = (string)$this->getRequest()->getParam('cko-card-token');

        if(!$cardToken){
            $cardToken = Mage::getSingleton('core/session')->getHostedCardId();
        }

        $orderIncrementId   = (string)$this->getRequest()->getParam('cko-context-id');

        if(!$orderIncrementId){
            $orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        $order              = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $helper             = Mage::helper('chargepayment');


        if (!$order->getId()) {
            $this->norouteAction();
            return;
        }

        if (!$cardToken) {
            Mage::getSingleton('core/session')->addError('Your payment has been cancelled. Please enter your card details and try again.');
            $result = array('status' => 'error', 'redirect' => Mage::helper('checkout/url')->getCheckoutUrl());
            $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.')->save();

            /* Restore quote session */
            $helper->restoreQuoteSession($order);

            $this->_redirectUrl($result['redirect']);
            return;
        }

        $hostedModel    = Mage::getModel('chargepayment/hosted');

        $result         = $hostedModel->authorizeByCardToken($order, $cardToken);
        $session        = Mage::getSingleton('chargepayment/session_quote');

        switch($result['status']) {
            case 'success':
                $session
                    ->setHostedPaymentRedirect(NULL)
                    ->setHostedPaymentParams(NULL)
                    ->setHostedPaymentConfig(NULL)
                    ->setSecretKey(NULL)
                    ->setCcId(NULL);

                Mage::getSingleton('core/session')->unsHostedCardId();

                $this->_redirect($result['redirect']);

                break;
            case '3d':
                $session
                    ->setHostedPaymentRedirect(NULL)
                    ->setHostedPaymentConfig(NULL)
                    ->setHostedPaymentParams(NULL)
                    ->setCcId(NULL);;

                $this->_redirectUrl($result['redirect']);
                break;
            case 'error':
                Mage::getSingleton('core/session')->addError('Please check you card details and try again. Thank you');
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'pending_payment', 'Gateway has declined the payment.')->save();

                /* Restore quote session */
                $helper->restoreQuoteSession($order);

                $this->_redirectUrl($result['redirect']);
                break;
            default:
                Mage::getSingleton('core/session')->addError('Something went wrong. Kindly contact us for more details.');
                // /* Restore quote session */
                $helper->restoreQuoteSession($order);

                $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl());
                break;
        }

        return $this;
    }
}
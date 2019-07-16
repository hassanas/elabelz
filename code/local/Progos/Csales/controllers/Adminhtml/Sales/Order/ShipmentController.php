<?php
/*
author : Humera Batool (humaira.batool@progos.org)
created at : 11/07/2017
for stopping double shipment emails of dhl without tracking no
*/
include_once('Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php');
 
class Progos_Csales_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{
    const XML_PATH_KUSTOMER_SHIPMENT_EMAIL_ENABLE = 'infotrust/infotrust/kustomer_shipment_email_enable';

    public function saveAction()
    {
        ini_set('memory_limit', '-1');
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $shipment = $this->_initShipment();

            if (!$shipment) {
                $this->_forward('noRoute');
                return;
            }

            $shipment->register();
            $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
            }

            if (!empty($data['send_email'])) {
                $shipment->setEmailSent(true);
            }

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax = new Varien_Object();
            $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];

            if ($isNeedCreateLabel && $this->_createShippingLabel($shipment)) {
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);
            
            if(!$data['dhllabel_create']){
            $shipment->sendEmail(!empty($data['send_email']), $comment);
            }

            $shipmentCreatedMessage = $this->__('The shipment has been created.');
            $labelCreatedMessage    = $this->__('The shipping label has been created.');

            $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage
                : $shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);

            //if enabled logs block
            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                Mage::log('inside saveAction', null, 'jsonld.log');
            }

            $isKustomerShipmentEmailEnable = Mage::getStoreConfig(self::XML_PATH_KUSTOMER_SHIPMENT_EMAIL_ENABLE);
            if ($isKustomerShipmentEmailEnable == 1) {
                $shipmentJsonld = Mage::helper('progos_infotrust')->getShipmentTrackingJsonld($shipment);
                $subject = 'Shipment for order #' . $shipment->getOrder()->getIncrementId();
                $text = '<p>Order shipped for <b>' . $shipment->getOrder()->getCustomerFirstname() . ' ' . $shipment->getOrder()->getCustomerLastname() . '</b></p>';
                //send email using zend
                Mage::helper('progos_infotrust')->zendSend($shipmentJsonld, $shipment->getOrder(), $subject, $text);
            }
            //if enabled logs block
            if (Mage::helper('progos_infotrust')->isKustomerLog()) {
                Mage::log('after saveAction', null, 'jsonld.log');
            }
        } catch (Mage_Core_Exception $e) {
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
                Mage::log($e->getMessage(), null, 'jsonld.log');
            } else {
                $this->_getSession()->addError($e->getMessage());
                Mage::log($e->getMessage(), null, 'jsonld.log');
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(
                    Mage::helper('sales')->__('An error occurred while creating shipping label.'));
                Mage::log(Mage::helper('sales')->__('An error occurred while creating shipping label.'), null, 'jsonld.log');
            } else {
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
                Mage::log($this->__('Cannot save shipment.'), null, 'jsonld.log');
                $this->_redirect('*/*/new', array('order_id' => $this->getRequest()->getParam('order_id')));
            }

        }
        if ($isNeedCreateLabel) {
            $this->getResponse()->setBody($responseAjax->toJson());
        } else {
            $this->_redirect('*/sales_order/view', array('order_id' => $shipment->getOrderId()));
        }
    }
}
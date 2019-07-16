<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_blockGroup  = 'mageworx_orderssurcharge';
        $this->_objectId    = 'surcharge_id';
        $this->_controller  = 'adminhtml_surcharge';
        $this->_mode        = 'view';

        parent::__construct();

        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('save');
        $this->setId('surcharge_view');
        $surcharge = $this->getSurcharge();

//        if ($this->_isAllowedAction('edit') && $order->canEdit()) {
//            $onclickJs = 'deleteConfirm(\''
//                . Mage::helper('sales')->__('Are you sure? This order will be canceled and a new one will be created instead')
//                . '\', \'' . $this->getEditUrl() . '\');';
//            $this->_addButton('order_edit', array(
//                'label'    => Mage::helper('sales')->__('Edit'),
//                'onclick'  => $onclickJs,
//            ));
//            // see if order has non-editable products as items
//            $nonEditableTypes = array_keys($this->getOrder()->getResource()->aggregateProductsByTypes(
//                $order->getId(),
//                array_keys(Mage::getConfig()
//                    ->getNode('adminhtml/sales/order/create/available_product_types')
//                    ->asArray()
//                ),
//                false
//            ));
//            if ($nonEditableTypes) {
//                $this->_updateButton('order_edit', 'onclick',
//                    'if (!confirm(\'' .
//                    Mage::helper('sales')->__('This order contains (%s) items and therefore cannot be edited through the admin interface at this time, if you wish to continue editing the (%s) items will be removed, the order will be canceled and a new order will be placed.', implode(', ', $nonEditableTypes), implode(', ', $nonEditableTypes)) . '\')) return false;' . $onclickJs
//                );
//            }
//        }
//
//        if ($this->_isAllowedAction('cancel') && $order->canCancel()) {
//            $message = Mage::helper('sales')->__('Are you sure you want to cancel this order?');
//            $this->_addButton('order_cancel', array(
//                'label'     => Mage::helper('sales')->__('Cancel'),
//                'onclick'   => 'deleteConfirm(\''.$message.'\', \'' . $this->getCancelUrl() . '\')',
//            ));
//        }
//
//        if ($this->_isAllowedAction('emails') && !$order->isCanceled()) {
//            $message = Mage::helper('sales')->__('Are you sure you want to send order email to customer?');
//            $this->addButton('send_notification', array(
//                'label'     => Mage::helper('sales')->__('Send Email'),
//                'onclick'   => "confirmSetLocation('{$message}', '{$this->getEmailUrl()}')",
//            ));
//        }
//
//        if ($this->_isAllowedAction('creditmemo') && $order->canCreditmemo()) {
//            $message = Mage::helper('sales')->__('This will create an offline refund. To create an online refund, open an invoice and create credit memo for it. Do you wish to proceed?');
//            $onClick = "setLocation('{$this->getCreditmemoUrl()}')";
//            if ($order->getPayment()->getMethodInstance()->isGateway()) {
//                $onClick = "confirmSetLocation('{$message}', '{$this->getCreditmemoUrl()}')";
//            }
//            $this->_addButton('order_creditmemo', array(
//                'label'     => Mage::helper('sales')->__('Credit Memo'),
//                'onclick'   => $onClick,
//                'class'     => 'go'
//            ));
//        }
//
//        // invoice action intentionally
//        if ($this->_isAllowedAction('invoice') && $order->canVoidPayment()) {
//            $message = Mage::helper('sales')->__('Are you sure you want to void the payment?');
//            $this->addButton('void_payment', array(
//                'label'     => Mage::helper('sales')->__('Void'),
//                'onclick'   => "confirmSetLocation('{$message}', '{$this->getVoidPaymentUrl()}')",
//            ));
//        }
//
//        if ($this->_isAllowedAction('hold') && $order->canHold()) {
//            $this->_addButton('order_hold', array(
//                'label'     => Mage::helper('sales')->__('Hold'),
//                'onclick'   => 'setLocation(\'' . $this->getHoldUrl() . '\')',
//            ));
//        }
//
//        if ($this->_isAllowedAction('unhold') && $order->canUnhold()) {
//            $this->_addButton('order_unhold', array(
//                'label'     => Mage::helper('sales')->__('Unhold'),
//                'onclick'   => 'setLocation(\'' . $this->getUnholdUrl() . '\')',
//            ));
//        }
//
//        if ($this->_isAllowedAction('review_payment')) {
//            if ($order->canReviewPayment()) {
//                $message = Mage::helper('sales')->__('Are you sure you want to accept this payment?');
//                $this->_addButton('accept_payment', array(
//                    'label'     => Mage::helper('sales')->__('Accept Payment'),
//                    'onclick'   => "confirmSetLocation('{$message}', '{$this->getReviewPaymentUrl('accept')}')",
//                ));
//                $message = Mage::helper('sales')->__('Are you sure you want to deny this payment?');
//                $this->_addButton('deny_payment', array(
//                    'label'     => Mage::helper('sales')->__('Deny Payment'),
//                    'onclick'   => "confirmSetLocation('{$message}', '{$this->getReviewPaymentUrl('deny')}')",
//                ));
//            }
//            if ($order->canFetchPaymentReviewUpdate()) {
//                $this->_addButton('get_review_payment_update', array(
//                    'label'     => Mage::helper('sales')->__('Get Payment Update'),
//                    'onclick'   => 'setLocation(\'' . $this->getReviewPaymentUrl('update') . '\')',
//                ));
//            }
//        }
//
//        if ($this->_isAllowedAction('invoice') && $order->canInvoice()) {
//            $_label = $order->getForcedDoShipmentWithInvoice() ?
//                Mage::helper('sales')->__('Invoice and Ship') :
//                Mage::helper('sales')->__('Invoice');
//            $this->_addButton('order_invoice', array(
//                'label'     => $_label,
//                'onclick'   => 'setLocation(\'' . $this->getInvoiceUrl() . '\')',
//                'class'     => 'go'
//            ));
//        }
//
//        if ($this->_isAllowedAction('ship') && $order->canShip()
//            && !$order->getForcedDoShipmentWithInvoice()) {
//            $this->_addButton('order_ship', array(
//                'label'     => Mage::helper('sales')->__('Ship'),
//                'onclick'   => 'setLocation(\'' . $this->getShipUrl() . '\')',
//                'class'     => 'go'
//            ));
//        }
//
//        if ($this->_isAllowedAction('reorder')
//            && $this->helper('sales/reorder')->isAllowed($order->getStore())
//            && $order->canReorderIgnoreSalable()
//        ) {
//            $this->_addButton('order_reorder', array(
//                'label'     => Mage::helper('sales')->__('Reorder'),
//                'onclick'   => 'setLocation(\'' . $this->getReorderUrl() . '\')',
//                'class'     => 'go'
//            ));
//        }
    }

    /**
     * Retrieve surcharge model object
     *
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    public function getSurcharge()
    {
        return Mage::registry('surcharge');
    }

    /**
     * Retrieve Surcharge Identifier
     *
     * @return int
     */
    public function getSurchargeId()
    {
        return $this->getSurcharge()->getId();
    }

//    public function getHeaderText()
//    {
//        if ($_extOrderId = $this->getOrder()->getExtOrderId()) {
//            $_extOrderId = '[' . $_extOrderId . '] ';
//        } else {
//            $_extOrderId = '';
//        }
//        return Mage::helper('sales')->__('Order # %s %s | %s', $this->getOrder()->getRealOrderId(), $_extOrderId, $this->formatDate($this->getOrder()->getCreatedAtDate(), 'medium', true));
//    }

    public function getUrl($params='', $params2=array())
    {
        $params2['surcharge_id'] = $this->getSurchargeId();
        return parent::getUrl($params, $params2);
    }

    public function getEditUrl()
    {
        return $this->getUrl('*/*/edit');
    }

//    public function getEmailUrl()
//    {
//        return $this->getUrl('*/*/email');
//    }
//
//    public function getCancelUrl()
//    {
//        return $this->getUrl('*/*/cancel');
//    }
//
//    public function getInvoiceUrl()
//    {
//        return $this->getUrl('*/sales_order_invoice/start');
//    }
//
//    public function getCreditmemoUrl()
//    {
//        return $this->getUrl('*/sales_order_creditmemo/start');
//    }
//
//    public function getHoldUrl()
//    {
//        return $this->getUrl('*/*/hold');
//    }
//
//    public function getUnholdUrl()
//    {
//        return $this->getUrl('*/*/unhold');
//    }
//
//    public function getShipUrl()
//    {
//        return $this->getUrl('*/sales_order_shipment/start');
//    }
//
//    public function getCommentUrl()
//    {
//        return $this->getUrl('*/*/comment');
//    }
//
//    public function getReorderUrl()
//    {
//        return $this->getUrl('*/sales_order_create/reorder');
//    }
//
//    /**
//     * Payment void URL getter
//     */
//    public function getVoidPaymentUrl()
//    {
//        return $this->getUrl('*/*/voidPayment');
//    }

    protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_orderssurcharge/actions/' . $action);
    }

    /**
     * Return back url for view grid
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getSurcharge()->getBackUrl()) {
            return $this->getSurcharge()->getBackUrl();
        }

        return $this->getUrl('*/*/');
    }

}
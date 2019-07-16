<?php
 
class Atnot_Orderhook_Model_Observer 
{
    public function implementOrderStatus($event)
    {
        $order = $event->getOrder();
 
        if ($this->_getPaymentMethod($order) == 'ccsave') {
                $this->_processOrderStatus($order);
        }
        return $this;
    }
 
    private function _getPaymentMethod($order)
    {
        return $order->getPayment()->getMethodInstance()->getCode();
    }
 
    private function _processOrderStatus($order)
    {
        $invoice = $order->prepareInvoice();
 
        $invoice->register();
        Mage::getModel('core/resource_transaction')
           ->addObject($invoice)
           ->addObject($invoice->getOrder())
           ->save();
 
        $invoice->sendEmail(true, '');
        $this->_changeOrderStatus($order);
        return true;
    }
 
    private function _changeOrderStatus($order)
    {
        $statusMessage = '';
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);        
    $order->save();
    }
}
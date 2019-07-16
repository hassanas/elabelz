<?php
/**
 * @category Progos_PdfPro
 * @package Progos
 * @author Saroop Chand <saroop.chand@progos.org>
 */
require_once "VES/PdfPro/controllers/Adminhtml/Pdfpro/PrintController.php";
class Progos_PdfPro_Adminhtml_Pdfpro_PrintController extends VES_PdfPro_Adminhtml_Pdfpro_PrintController
{
    protected function _isAllowed() {
        return true;
    }
    /**
     * Print An Invoice As per destination
     */
    public function invoiceDestinationAction(){
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
        if (!$invoice->getId()) {
            $this->_getSession()->addError($this->__('The invoice no longer exists.'));
            $this->_forward('no-route');
            return;
        }
        $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
        if ($order->getOrderCurrencyCode() == "AED") {
            $shipping_description = $order->getMspCashondelivery() + $order->getShippingAmount();
            $order->setShippingDescription($shipping_description);
        }else{
            $shipping_description = $order->getMspBaseCashondelivery() + $order->getBaseShippingAmount();
            $order->setShippingDescription($shipping_description);
        }
        $order->save();
        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);

        $invoiceData = Mage::getModel('pdfpro/order_invoice')->initInvoiceDestinationData($invoice);
        try{
            $result = Mage::helper('pdfpro')->initPdf(array($invoiceData));
            if($result['success']){
                $this->_prepareDownloadResponse(Mage::helper('pdfpro')->getFileName('invoice',$invoice).'.pdf', $result['content']);
            }else{
                throw new Mage_Core_Exception($result['msg']);
            }
        }catch(Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order_invoice/view',array('invoice_id'=>$invoiceId));
        }
    }

    /**
     * Print An Invoice As per destination tax
     */
    public function taxdestinationAction(){
        $orderId = $this->getRequest()->getParam('tax_order_id');
        if (empty($orderId)) {
            Mage::getSingleton('adminhtml/session')->addError('There is no order to process');
            $this->_redirect('adminhtml/sales_order');
            return;
        }
            $order = Mage::getModel('sales/order')->load($orderId);
            $orderDatas = Mage::getModel('pdfpro/order')->initOrderData($order);
        try{
            $result = Mage::helper('pdfpro')->initPdf(array($orderDatas),'custom1');
            if($result['success']){
                $this->_prepareDownloadResponse(Mage::helper('pdfpro')->getFileName('custom1').'.pdf', $result['content']);
            }else{
                throw new Mage_Core_Exception($result['msg']);
            }
        }catch(Exception $e){
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order/index');
        }
    }
}
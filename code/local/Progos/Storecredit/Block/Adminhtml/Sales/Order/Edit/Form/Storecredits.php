<?php
/**
 * Created by Humera batool 31st October 2017
 * @purpose : Showing store credit on order view when it is edited
 */

class Progos_Storecredit_Block_Adminhtml_Sales_Order_Edit_Form_Storecredits extends Mage_Adminhtml_Block_Widget //MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Coupons
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_order_storecredits');
        $this->setTemplate('progos/storecredit/edit/storecredits.phtml');
    }

    /**
     * @return string
     */
    public function getStoreCredit()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrder();
        $currency_code = $order->getStore()->getCurrentCurrency()->getCode();

        if ($order->getInvoiceCollection()->count()) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoice_id = $invoice->getId();
            }
            $collection = Mage::helper('aw_storecredit/totals')->getInvoiceStoreCredit($invoice_id);
            foreach ($collection as $col) {
                $storeCredit = $col->getStorecreditAmount();
            }

        } else {
            $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
            foreach ($collection as $col) {
                $storeCredit = $col->getStorecreditAmount();
            }
        }

        if($storeCredit != 0 && $storeCredit != "") {
            /* Changing its format to 2 decimal */
            $storeCredit = Mage::getModel('directory/currency')->format(
                $storeCredit,
                array('display' => Zend_Currency::NO_SYMBOL),
                false
            );
            $storeCredit = $currency_code . " " . $storeCredit;
        }
        else{
            $storeCredit = Mage::getModel('directory/currency')->format(
                0,
                array('display' => Zend_Currency::NO_SYMBOL),
                false
            );
            $storeCredit = $currency_code . " " . $storeCredit;
        }
        return $storeCredit;
    }

    public function getCustomerStoreCredit(){

        $order = $this->getOrder();
        $currency_code = $order->getStore()->getCurrentCurrency()->getCode();
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        if ($order->getCustomerId()){
            $customerId = $order->getCustomerId();
            $customerStorecredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($customerId);
            if ($customerStorecredit) {
                if($customerStorecredit->getBalance()) {
                    /* Changing its format to 2 decimal after converting to the current currency*/
                    $balance = Mage::helper('directory')->currencyConvert($customerStorecredit->getBalance(), $baseCurrencyCode, $currency_code);
                    $balance = Mage::getModel('directory/currency')->format(
                        $balance,
                        array('display' => Zend_Currency::NO_SYMBOL),
                        false
                    );
                    $balance = $currency_code . " " . $balance;
                }
            else{
                    $balance = "";
                }
            }
        }
        else{
            $balance = "";
        }

        return $balance;
    }


}
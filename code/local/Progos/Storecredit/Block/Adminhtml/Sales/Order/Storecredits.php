<?php
/**
 * Created by Humera batool 31st October 2017
 * @purpose : Showing store credit on order view
 */

class Progos_Storecredit_Block_Adminhtml_Sales_Order_Storecredits extends Mage_Adminhtml_Block_Widget//Mage_Adminhtml_Block_Sales_Order_Create_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_order_storecredits');
        $this->setTemplate('progos/storecredit/storecredits/form.phtml');
    }

    /**
     * @return null|string
     * @throws Exception
     */
    public function getStoreCredit()
    {
        if ($this->getParentBlock()) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $this->getParentBlock()->getOrder();
        } elseif ($orderId = $this->getRequest()->getParam('order_id')) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($orderId);
        } else {
            return null;
        }


        $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
        foreach ($collection as $col) {
            $storecredit_amount = $col->getStorecreditAmount();
        }
        $currency_code = $order->getStore()->getCurrentCurrency()->getCode();
        /* Changing its format to 2 decimal */
        $storecredit_amount = Mage::getModel('directory/currency')->format(
            $storecredit_amount,
            array('display' => Zend_Currency::NO_SYMBOL),
            false
        );
        $storecredit_amount = $currency_code . " " . $storecredit_amount;
        return $storecredit_amount;
    }
}
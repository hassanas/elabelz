<?php
/**
 * Created by Humera batool 31st October 2017
 * @purpose : Showing store credit on order view when it is changed
 */

class Progos_Storecredit_Block_Adminhtml_Sales_Order_Changed_Storecredits extends Mage_Adminhtml_Block_Widget
{
    /** @var string  */
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_order_storecredits');
        $this->setTemplate('progos/storecredit/changed/storecredits.phtml');
    }

    /**
     * @return string
     */
    public function getStoreCredit()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getQuote();
        $currency_code = $quote->getStore()->getCurrentCurrency()->getCode();
        $storeCredit = "";
        if(count(Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quote->getId())) != 0) {
            $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quote->getId());
            foreach ($collection as $col) {
                $storeCredit = $col['storecredit_amount'];
            }
        }
        $storeCredit = $quote->getStore()->roundPrice($storeCredit);
        $storeCredit = $currency_code." ".$storeCredit;
        return $storeCredit;
    }
}
<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Sales_Order_Totals_Linked extends Mage_Core_Block_Template
{

    protected $_order;
    protected $_source;

    public function _construct()
    {
        parent::_construct();
    }

    /**
     * Initialize order totals
     *
     * @return MageWorx_OrdersSurcharge_Block_Adminhtml_Sales_Order_Totals_Linked
     */
    public function initTotals()
    {
        /** @var $parent Mage_Adminhtml_Block_Sales_Order_Totals|Mage_Adminhtml_Block_Sales_Order_Invoice_Totals */
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if ($this->_order->getBaseLinkedAmount() < 0) {
            $this->_addLinkedTotal();
        }

        return $this;
    }

    /**
     * Add linked total string
     *
     * @param string $after
     * @return MageWorx_OrdersSurcharge_Block_Adminhtml_Sales_Order_Totals_Linked
     */
    protected function _addLinkedTotal($after = 'tax')
    {
        $total = new Varien_Object(array(
            'code' => 'linked',
            'block_name' => $this->getNameInLayout()
        ));

        /** @var $parent Mage_Adminhtml_Block_Sales_Order_Totals|Mage_Adminhtml_Block_Sales_Order_Invoice_Totals */
        $parent = $this->getParentBlock();
        $parent->addTotal($total, $after);

        return $this;
    }

    /**
     * Display amount
     *
     * @return string
     */
    public function displayAmount($amount, $baseAmount)
    {
        return Mage::helper('adminhtml/sales')->displayPrices(
            $this->getSource(), $baseAmount, $amount, false, '<br />'
        );
    }

    /**
     * Get tooltip message for linked order total with all existing surcharges
     *
     * @return string
     */
    public function getTooltipMessage()
    {
        $result[] = '';

        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');

        /** @var Mage_Sales_Model_Order $order */
        $order = $this->_order ? $this->_order : $this->getSource()->getOrder();
        $paid = Mage::getModel('mageworx_orderssurcharge/surcharge')->getTotalSurchargePaidForOrder($order);
        $due = ($order->getBaseLinkedAmount() + $paid) * -1;
        if ($due == 0) {
            $resultString = $helper->__('Paid');
        } else {
            $resultString = $helper->__('Total Due: %s', $order->formatPriceTxt($helper->convertBaseToOrderRate($due, $order)));
        }

        return $resultString;
    }

    /**
     * Get data (totals) source model
     *
     * @return Varien_Object
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get order store object
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }
}
<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Sales_Order_Totals_Surcharge extends Mage_Core_Block_Template
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
     * @return MageWorx_OrdersSurcharge_Block_Sales_Order_Totals_Surcharge
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if ($this->_source->getBaseSurchargeAmount() > 0) {
            $this->_addSurchargeTotal();
        }

        return $this;
    }

    /**
     * Add surcharge total string
     *
     * @param string $after
     * @return MageWorx_OrdersSurcharge_Block_Sales_Order_Totals_Surcharge
     */
    protected function _addSurchargeTotal($after = 'tax')
    {
        $total = new Varien_Object(array(
            'code' => 'surcharge',
            'block_name' => $this->getNameInLayout()
        ));

        /** @var $parent Mage_Sales_Block_Order_Totals */
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
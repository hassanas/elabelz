<?php
/**
 * Progos_OrdersGrid
 * Rewrite Extension
 *
 * @category   Progos
 * @package    Progos_OrdersGrid
 * @copyright  Copyright (c) 2017 Elabelz (https://www.elabelz.com)
 * @author     Hassan Ali Shahzad (hassan.ali@progos.org)
 * @created_at 10/04/2017
 * @reason     For adding new column in ordersgrid (is_edited_date)
 */

class Progos_OrdersGrid_Block_Adminhtml_Sales_Order_Grid_Renderer_Currencyprecision extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        if (strtolower($row->getData($this->getColumn()->getCurrency())) == "usd") {
            $currency_symbol = Mage::app()->getLocale()->currency($row->getData($this->getColumn()->getCurrency()))->getSymbol();
        }
        $formattedPrice = Mage::getModel('directory/currency')->format(
            $row->getData($this->getColumn()->getIndex()),
            array('display' => Zend_Currency::NO_SYMBOL),
            false
        );
        if ($currency_symbol) {
            $value = $currency_symbol . " " . $formattedPrice;
        } else {
            $value = $row->getData($this->getColumn()->getCurrency()) . " " . $formattedPrice;
        }
        return $value;
    }
}
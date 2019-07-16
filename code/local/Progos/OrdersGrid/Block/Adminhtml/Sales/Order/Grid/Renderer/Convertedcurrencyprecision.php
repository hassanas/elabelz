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

class Progos_OrdersGrid_Block_Adminhtml_Sales_Order_Grid_Renderer_Convertedcurrencyprecision extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Price
{


    public function render(Varien_Object $row)
    {
        if ($row->getTypeId() == 'downloadable') {
            $row->setPrice($row->getPrice());
        }
        if ($data = $row->getData($this->getColumn()->getIndex())) {
            $currency_code = $this->_getCurrencyCode($row);
            $currentSymbol = Mage::app()->getLocale()->currency($currency_code)->getSymbol();

            if (!$currency_code) {
                return $data;
            }

            $data = floatval($data) * $this->_getRate($row);
            $data = sprintf("%F", $data);
            $formattedPrice = Mage::getModel('directory/currency')->format(
                $data,
                array('display' => Zend_Currency::NO_SYMBOL),
                false
            );
            $data = $currentSymbol." ".$formattedPrice;
            return $data;
        }
        return $this->getColumn()->getDefault();
    }
}
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

class Progos_OrdersGrid_Block_Adminhtml_Sales_Order_Grid_Renderer_Currencyprecisionbase extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = Mage::helper('core')->currency($row->getData($this->getColumn()->getIndex()), true, false);
        return $value;
    }
}
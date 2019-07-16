<?php
/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersGrid_Block_Adminhtml_Sales_Order_Grid_Renderer_Customer_Group extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{



    /**
     * @param Varien_Object $row
     * @return array|mixed|string
     */
    public function render(Varien_Object $row)
    {
        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Customer_Group $sourceCustomerGroup */
        $sourceCustomerGroup = Mage::getSingleton('mageworx_ordersgrid/system_config_source_customer_group');
        $index = $this->getColumn()->getIndex();
        $id = $row->getData($index);
        $values = $sourceCustomerGroup->toArray();

        if (isset($values[$id])) {
            return $this->escapeHtml($values[$id]);
        }

        return $id;
    }
}

<?php
/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersGrid_Block_Adminhtml_Sales_Order_Grid_Renderer_Shipping extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * @param Varien_Object $row
     * @return array|mixed|string
     */
    public function render(Varien_Object $row)
    {
        /** @var MageWorx_OrdersGrid_Model_System_Config_Source_Shipping_Methods $sourceShippingMethods */
        $sourceShippingMethods = Mage::getModel('mageworx_ordersgrid/system_config_source_shipping_methods');
        $index = $this->getColumn()->getIndex();
        $id = $row->getData($index);
        $values = $sourceShippingMethods->toArray();

        if ($row->getData('shipping_description')) {
            return $row->getData('shipping_description');
        }

        if (isset($values[$id])) {
            return $this->escapeHtml($values[$id]);
        }

        if (strpos($id, '_')) {
            $id = explode('_', $id);
            $id2 = $id[0] . '_' . $id[0];
            unset($id[0]);
            if (isset($values[$id2])) {
                return $this->escapeHtml($values[$id2] . ' ' . implode('_', $id));
            }
        }

        return $id;
    }

}

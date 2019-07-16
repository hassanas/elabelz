<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 *
 * @method Mage_Adminhtml_Block_Widget_Grid_Column getColumn()
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_OrderLink
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        if ($this->getColumn()->getIndex() == 'order_increment_id') {
            $id = $row->getData('order_id');
        } else if ($this->getColumn()->getIndex() == 'parent_order_increment_id') {
            $id = $row->getData('parent_order_id');
        } else {
            return $value;
        }

        $link = $this->getUrl('adminhtml/sales_order/view', ['order_id' => $id]);

        return '<a href="' . $link . '" target="_blank">' . $value . '</a>';
    }
}

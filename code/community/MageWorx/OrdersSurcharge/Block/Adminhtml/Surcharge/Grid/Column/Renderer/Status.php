<?php

/**
 * MageWorx
 * Order Surcharge extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersSurcharge_Block_Adminhtml_Surcharge_Grid_Column_Renderer_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $options = $this->getColumn()->getOptions();

        switch ($value) {
            case MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_DELETED:
                $color = 'deleted';
                break;
            case MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_PENDING:
            case MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_PROCESSING:
                $color = 'in-progress';
                break;
            case MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_PAID:
            case MageWorx_OrdersSurcharge_Model_Surcharge::STATUS_COMPLETE:
                $color = 'complete';
                break;
            default:
                $color = 'undefined';
        }

        return '<span class="surcharge-status surcharge-status-' . $color . '">' . $options[$value] . '</span>';
    }
}
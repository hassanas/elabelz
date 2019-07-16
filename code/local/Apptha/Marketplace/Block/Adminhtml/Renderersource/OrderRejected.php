<?php
/**
 * Apptha_Marketplace
 *
 * @category   Apptha
 * @package    Apptha_Marketplace
 * @copyright  Copyright (c) 2017 Elabelz (https://www.elabelz.com)
 * @author     Humaira Batool (humaira.batool@progos.org)
 */

class Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderRejected extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('increment_id', $value)->getFirstItem();

        if ($orders->getFailedDelivery() == 1):
            $circle = 'failed';
            $msg = "Failed";
        elseif ($orders->getFailedDelivery() == 2):
            $circle = 'success';
            $msg = "Success";
        elseif ($orders->getFailedDelivery() == 3):
            $circle = 'warning';
            $msg = "";
        else:
            $circle = '';
            $msg = "";
        endif;

        $result = "<div class='circ_outer_status " . $circle . "'>" . $msg . "</div>";

        return $result;
    }

    public function renderExport(Varien_Object $row)
    {
        if ($row->getFailedDelivery() == 1):
            $msg = "Failed";
        elseif ($row->getFailedDelivery() == 2):
            $msg = "Success";
        elseif ($row->getFailedDelivery() == 3):
            $msg = "warning";
        else:
            $msg = "";
        endif;

        return $msg;
    }
}
<?php

/**
 * Progos
 * 
 *
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Renderersource_GetIsSellerConfirmedCancelRequest extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $url = $this->getUrl('*/*/confirm_seller', array('id' => $value));
        $item = Mage::getModel('marketplace/commission')->load($value);

        if ($item->getCancelRequestSellerConfirmation() == 1) {
            $result = Mage::helper('marketplace')->__('Yes');
        } elseif ($item->getCancelRequestSellerConfirmation() == 2) {
            $reason = $item->getCancelRequestSellerRemarks();
            //$result = "<a onclick='javascript: alert(\"$reason\"); return false;' href='#' title='" . Mage::helper('marketplace')->__('Click to show Reason') . "'>" . Mage::helper('marketplace')->__('Rejected') . "</a>";
            $result = "<a onclick='javascript:return confirm(\"Are you sure?\");' href='" . $url . "' title='" . Mage::helper('marketplace')->__('Click to Confirm') . "'>" . Mage::helper('marketplace')->__('Confirm') . "</a>";
        } else {
            $result = "<a onclick='javascript:return confirm(\"Are you sure?\");' href='" . $url . "' title='" . Mage::helper('marketplace')->__('Click to Confirm') . "'>" . Mage::helper('marketplace')->__('Confirm') . "</a>";
        }

        return $result;
    }

}


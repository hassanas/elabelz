<?php

/**
 * Progos
 * Admin Order Editor extension
 *
 * @category   Progos
 * @package    Progos_OrdersEdit
 * @author     Saroop
 */
class Progos_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_Items_Itemsgrid extends MageWorx_OrdersEdit_Block_Adminhtml_Sales_Order_Edit_Form_Items_Itemsgrid
{
    /**
     * Get button to configure product
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    public function getConfigureButtonHtml($item)
    {
            //checking if product is accepted or rejected by customer or seller so that configure button will be disabled accordingly
        $product = $item->getProduct();
        $options = array('label' => Mage::helper('sales')->__('Configure'));

        if ($product->canConfigure()) {
            $options['onclick'] = sprintf('orderEditItems.showQuoteItemConfiguration(%s)', $item->getId());
        } else {
            $options['class'] = ' disabled';
            $options['title'] = Mage::helper('sales')->__('This product does not have any configurable options');
        }

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData($options)
            ->toHtml();
    }
}
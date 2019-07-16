<?php

class MageWorx_OrdersSurcharge_Block_Checkout_Cart_Item_Renderer extends Mage_Core_Block_Template
{

    /**
     * Get static block for the surcharge quote cart page
     *
     * @return string
     */
    public function getStaticBlock()
    {
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');
        $staticBlockIdentifier = $helper->getCartStaticBlockIdentifier();
        if (!$staticBlockIdentifier) {
            return '';
        }

        /** @var Mage_Core_Model_Layout $layout */
        $layout = $this->getLayout();
        if (!$layout) {
            return '';
        }

        $block = $layout->createBlock('cms/block')->setBlockId($staticBlockIdentifier);
        $html = $block->toHtml();

        return $html;
    }
}
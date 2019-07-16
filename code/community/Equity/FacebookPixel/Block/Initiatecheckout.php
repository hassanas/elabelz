<?php
class Equity_FacebookPixel_Block_InitiateCheckout extends Equity_FacebookPixel_Block_Abstract
{

    protected function _canShow() {
        return $this->_getConfigHelper()->isInitiateCheckoutEnabled();
    }
    
    public function getCartItemsCount()
    {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        return count( $cart->getAllVisibleItems() );
    }

}

<?php
class FacileCheckout_OnestepCheckout_Model_Service_Quote extends Mage_Sales_Model_Service_Quote
{
    protected function _validate()
    {
        $helper = Mage::helper('sales');
        if (!$this->getQuote()->isVirtual())
        {
            $address = $this->getQuote()->getShippingAddress();
            $addrValidator = Mage::getSingleton('onestepcheckout/type_geo')->validateAddress($address);
            if ($addrValidator !== true)
                Mage::throwException($helper->__('Please check shipping address information. %s', implode(' ', $addrValidator)));

            $ship_method = $address->getShippingMethod();
            $rate = $address->getShippingRateByCode($ship_method);
            if (!$this->getQuote()->isVirtual() && (!$ship_method || !$rate)){
                //If method is not active for current store then change the method of store with alloweded method.
                $changeMethod = $this->getAlternateMethod( $ship_method );
                if( $ship_method != $changeMethod ){
                    $address->setShippingMethod($changeMethod);
                    $address->save();
                    $rate = $address->getShippingRateByCode($changeMethod);
                    if (!$this->getQuote()->isVirtual() && (!$changeMethod || !$rate))
                        Mage::throwException($helper->__('Please specify a shipping method.'));
                }else{
                    Mage::throwException($helper->__('Please specify a shipping method.'));
                }
            }
        }

        $addrValidator = Mage::getSingleton('onestepcheckout/type_geo')->validateAddress($this->getQuote()->getBillingAddress());

        if ($addrValidator !== true)
            Mage::throwException($helper->__('Please check billing address information. %s', implode(' ', $addrValidator)));

        if (!($this->getQuote()->getPayment()->getMethod()))
			Mage::throwException($helper->__('Please select a valid payment method.'));

        return $this;
    }
    /*
     * Change Shipping Method if Showing Alert Message. This happened when not allowed method is selected first time.
     * */
    public function getAlternateMethod( $shippingMethod ){
        if( $shippingMethod == 'freeshipping_freeshipping' )
            return 'tablerate_bestway';
        if( $shippingMethod == 'tablerate_bestway' )
            return 'freeshipping_freeshipping';
        return $shippingMethod;
    }
}

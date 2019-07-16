<?php

class FacileCheckout_OnestepCheckout_Block_Onepage_Billship extends Mage_Checkout_Block_Onepage_Billing
{
    public function getBillAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

    public function getShipAddress()
    {
        return $this->getQuote()->getShippingAddress();
    }

    public function getCustomerBillAddr()
    {
    	return $this->buildCustomerAddress('billing');
    }

    public function getBillingCountriesSelectBox()
    {
    	return $this->buildCountriesSelectBox('billing');
    }

    public function getCustomerShipAddr()
    {
    	return $this->buildCustomerAddress('shipping');
    }

    public function getShippingCountriesSelectBox()
    {
    	return $this->buildCountriesSelectBox('shipping');
    }

    public function buildCustomerAddress($addr_type)
    {
        if ($this->isCustomerLoggedIn()) {
            $options = array();
            $addr_details = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value'=>$address->getId(),
                    'label'=>$address->format('oneline')
                );
                $addr_details[$address->getId()][$addr_type.'[address_id]'] = $address->getId();
                $addr_details[$address->getId()][$addr_type.'[country_id]'] = $address->getCountryId();
                $addr_details[$address->getId()][$addr_type.'[city]'] = $address->getCity();
                $addr_details[$address->getId()][$addr_type.'[firstname]'] = $address->getFirstname();
                $addr_details[$address->getId()][$addr_type.'[lastname]'] = $address->getLastname();
                $addr_details[$address->getId()][$addr_type.'[postcode]'] = $address->getPostcode();
                $addr_details[$address->getId()][$addr_type.'[region_id]'] = $address->getRegionId();
                $addr_details[$address->getId()][$addr_type.'[region]'] = $address->getRegion();
                $addr_details[$address->getId()][$addr_type.'[telephone]'] = $address->getTelephone();
                $addr_details[$address->getId()][$addr_type.'[street][]'] = $address->getStreet()[0];
            }

        	switch($addr_type)
        	{
        		case 'billing':
        			$address = $this->getCustomer()->getPrimaryBillingAddress();
        			break;
        		case 'shipping':
        			$address = $this->getCustomer()->getPrimaryShippingAddress();
        			break;
        	}

            if ($address) {
                $addressId = $address->getId();
            } else {
            	if($addr_type == 'billing')
            		$obj	= $this->getBillAddress();
            	else
            		$obj	= $this->getShipAddress();

                $addressId = $obj->getId();
            }

            $select = $this->getLayout()->createBlock('core/html_select')
            							->setId("{$addr_type}_customer_address")->setName("{$addr_type}_address_id")
            							->setValue($addressId)->setOptions($options)
										->setExtraParams('onchange="'.$addr_type.'.newAddress(!this.value)"')
                                        ->setClass('customer_address');

            $select->addOption('', Mage::helper('checkout')->__('New Address'));
            $jsvariable = "<script>window.customer".ucfirst($addr_type)."Addresses = ".json_encode($addr_details, JSON_FORCE_OBJECT).";</script>";
            return $select->getHtml().$jsvariable;
        }
        return '';
    }

    public function buildCountriesSelectBox($addr_type)
    {
		if($addr_type == 'billing')
			$obj	= $this->getBillAddress();
		else
			$obj	= $this->getShipAddress();

        $countryId = $obj->getCountryId();
        if (is_null($countryId)) {
            $countryId = Mage::getStoreConfig('general/country/default');
        }
        $select = $this->getLayout()->createBlock('core/html_select')
        							->setId("{$addr_type}:country_id")->setName("{$addr_type}[country_id]")
									->setValue($countryId)->setOptions($this->getCountryOptions())
									->setTitle(Mage::helper('checkout')->__('Country'))
									->setClass('validate-select');

		if($addr_type == 'shipping')
			$select->setExtraParams('onchange="shipping.setSameAsBilling(false);"');

        return $select->getHtml();
    }
}

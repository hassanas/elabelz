<?php

class Progos_Emapi_Model_Customer_Address_Api extends Mage_Customer_Model_Address_Api
{
    public function create($customerId, $addressData)
    {
        return parent::create($customerId, $addressData);
    }

    /**
     * Retrive customer addresses list
     *
     * @param int $customerId
     * @return array
     */
    public function items($customerId)
    {
        $customer = Mage::getModel('customer/customer')
            ->load($customerId);
        /* @var $customer Mage_Customer_Model_Customer */

        if (!$customer->getId()) {
            $this->_fault('customer_not_exists');
        }

        $result = array();
        foreach ($customer->getAddresses() as $address) {
            $data = $address->toArray();
            $row  = array();

            foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
                $row[$attributeAlias] = isset($data[$attributeCode]) ? $data[$attributeCode] : null ;
            }

            foreach ($this->getAllowedAttributes($address) as $attributeCode => $attribute) {
                if (isset($data[$attributeCode])) {
                    $row[$attributeCode] = $data[$attributeCode];
                }else{
                    $row[$attributeCode] = "";
                }
                if($attributeCode == "country_id"){
                    $countryModel = Mage::getModel('directory/country')->loadByCode($data[$attributeCode]);
                    $countryName = $countryModel->getName();
                    $row['country_name'] = __($countryName);
                }
            }
            $row['is_default_billing'] = $customer->getDefaultBilling() == $address->getId();
            $row['is_default_shipping'] = $customer->getDefaultShipping() == $address->getId();

            $result[] = $row;

        }

        return $result;
    }

    public function update($addressId, $addressData)
    {
        return parent::update($addressId, $addressData);
    }

    public function delete($addressId)
    {
        return parent::delete($addressId);
    }
}

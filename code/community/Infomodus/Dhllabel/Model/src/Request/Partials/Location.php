<?php

class Infomodus_Dhllabel_Model_Src_Request_Partials_Location extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'CountryCode' => null,
        'Postalcode' => null,
        'City' => null
    );

    /**
     * @param string $countryCode Two letter ISO country code
     */
    public function setCountryCode($countryCode)
    {
        $this->required['CountryCode'] = $countryCode;

        return $this;
    }

    /**
     * @param string $postalCode Postal code of the location
     */
    public function setPostalCode($postalCode)
    {
        $this->required['Postalcode'] = $postalCode;

        return $this;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->required['City'] = $city;

        return $this;
    }
}

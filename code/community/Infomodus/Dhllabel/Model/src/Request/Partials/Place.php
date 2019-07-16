<?php
class Infomodus_Dhllabel_Model_Src_Request_Partials_Place extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'ResidenceOrBusiness' => null,
        'CompanyName' => null,
        'AddressLine' => null,
        'City' => null,
        'PostalCode' => null,
        'CountryCode' => null,
        'CountryName' => null,
        'Contact' => null,
        'Division' => null
    );

    public function setResidenceOrBusiness($residenceOrBusiness)
    {
        $this->required['ResidenceOrBusiness'] = $residenceOrBusiness;

        return $this;
    }

    /**
     * @param string $companyName Name of the company
     */
    public function setCompanyName($companyName)
    {
        $this->required['CompanyName'] = $companyName;

        return $this;
    }

    /**
     * @param string $addressLine Company address
     */
    public function setAddressLine($addressLine)
    {
        $this->required['AddressLine'] = $addressLine;

        return $this;
    }

    /**
     * @param string $city Company city
     */
    public function setCity($city)
    {
        $this->required['City'] = $city;

        return $this;
    }

    /**
     * @param string $postalCode Receiver's postal code
     */
    public function setPostalCode($postalCode)
    {
        $this->required['PostalCode'] = $postalCode;

        return $this;
    }

    public function setDivision($division)
    {
        $this->required['Division'] = $division;

        return $this;
    }

    /**
     * @param string $countryCode 2 letter ISO country code
     */
    public function setCountryCode($countryCode)
    {
        $this->required['CountryCode'] = $countryCode;

        return $this;
    }
}

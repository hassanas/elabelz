<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Block_Organizationsnippets extends Mage_Core_Block_Template
{
    protected $_config;

    function __construct()
    {
        $this->_config = Mage::getSingleton('seo/config');
    }

    public function getOrganizationSnippets() {
        $organizationSnippets = array('enable' => false, 'json' => false);
        if ($this->_config->getOrganizationSnippets() == Mirasvit_Seo_Model_Config::SNIPPETS_ORGANIZATION_JSON) {
            $organizationSnippets['enable'] = true;
            $organizationSnippets['json'] = true;
        } elseif ($this->_config->getOrganizationSnippets() == Mirasvit_Seo_Model_Config::SNIPPETS_ORGANIZATION_MICRODATA) {
            $organizationSnippets['enable'] = true;
        }

        return $organizationSnippets;
    }

    public function getName($isJson = false) {
        if ($this->_config->getNameOrganizationSnippets()) {
            $name = $this->_config->getManualNameOrganizationSnippets();
        } else {
            $name = trim(Mage::getStoreConfig('general/store_information/name'));
        }

        return $this->getSnippetRow("name", $name, $isJson);
    }

    public function getCountryAddress($isJson = false) {
       if ($this->_config->getCountryAddressOrganizationSnippets()) {
            $countryAddress = $this->_config->getManualCountryAddressOrganizationSnippets();
       } else {
            $countryAddress = trim(Mage::app()->getLocale()->getCountryTranslation(Mage::getStoreConfig('general/store_information/merchant_country')));
       }

       return $this->getSnippetRow("addressCountry", $countryAddress, $isJson);
    }

    public function getAddressLocality($isJson = false) {
       $addressLocality = $this->_config->getManualLocalityAddressOrganizationSnippets();

       return $this->getSnippetRow("addressLocality", $addressLocality, $isJson);
    }

    public function getPostalCode($isJson = false) {
       $postalCode = $this->_config->getManualPostalCodeOrganizationSnippets();

       return $this->getSnippetRow("postalCode", $postalCode, $isJson);
    }

    public function getStreetAddress($isJson = false) {
        if ($this->_config->getStreetAddressOrganizationSnippets()) {
            $streetAddress = $this->_config->getManualStreetAddressOrganizationSnippets();
        } else {
            $streetAddress = trim(Mage::getStoreConfig('general/store_information/address'));
        }

        return $this->getSnippetRow("streetAddress", $streetAddress, $isJson);
    }

    public function getTelephone($isJson = false) {
        if ($this->_config->getTelephoneOrganizationSnippets()) {
            $telephone = $this->_config->getManualTelephoneOrganizationSnippets();
        } else {
            $telephone = trim(Mage::getStoreConfig('general/store_information/phone'));
        }

        return $this->getSnippetRow("telephone", $telephone, $isJson);
    }

    public function getFaxNumber($isJson = false) {
       $faxNumber = $this->_config->getManualFaxnumberOrganizationSnippets();

       return $this->getSnippetRow("faxNumber", $faxNumber, $isJson);
    }

    public function getEmail($isJson = false) {
        if ($this->_config->getEmailOrganizationSnippets()) {
            $email = $this->_config->getManualEmailOrganizationSnippets();
        } else {
            $email = trim(Mage::getStoreConfig('trans_email/ident_general/email'));
        }

        return $this->getSnippetRow("email", $email, $isJson);
    }

    public function preparePostalAddress($countryAddress, $addressLocality, $postalCode, $streetAddress) {
        $postalAddress    = $countryAddress . $addressLocality . $postalCode . $streetAddress;
        if ($postalAddress && substr($postalAddress, -1) == ',' ) {
            $postalAddress = substr($postalAddress, 0, -1);
        }

        return $postalAddress;
    }

    public function getLogoUrl() {
        return Mage::getDesign()->getSkinUrl() . Mage::getStoreConfig('design/header/logo_src', Mage::app()->getStore()->getStoreId());
    }

    protected function getSnippetRow($tag, $tagValue, $isJson = false) {
        if ($tagValue && $isJson) {
            return "\"$tag\" : \"$tagValue\",";
        } elseif ($tagValue) {
            return "<meta itemprop=\"$tag\" content=\"$tagValue\"/>";
        }

        return false;
    }
}

<?php

class Apptha_Catalog_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getRequestedBrands()
    {
        $optionIds = Mage::app()->getRequest()->getParam('manufacturer');
        $urlKeys = array();

        foreach (explode(',', $optionIds) as $optionId) {
            /** @var Magestore_Shopbybrand_Model_Brand $brand */
            $brand = Mage::getModel('shopbybrand/brand')->load($optionId, 'option_id');
            $urlKeys[] = $brand->getUrlKey();
        }

        return strtolower(join('--', $urlKeys));
    }

    /**
     * @param string $url
     * @return string
     */
    public function appendBrandsToUrl($url)
    {
        if (!is_string($url)) {
            return $url;
        }
        return rtrim($url, '/') . '/' . $this->getRequestedBrands();
    }
}
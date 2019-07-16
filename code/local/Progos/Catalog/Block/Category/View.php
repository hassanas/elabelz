<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Catalog
 */

/**
 * Block to get the store config either to show the product template on category page or not
 */

class Progos_Catalog_Block_Category_View extends Mage_Catalog_Block_Category_View
{

    public function useProductPageTemplateOnCategoryPage(){
        $configValue = Mage::getStoreConfig('progos_catalog/general/boolean');
        return $configValue;
    }
    public function getProductPageTemplateHtml(){
        return $this->getChildHtml('productPageTemplate');
    }


}
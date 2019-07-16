<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Aitoc_Aiteditablecart_Block_BundleCatalogProductViewTypeBundleOptionMulti
    extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle_Option_Multi
{
    public function _construct()
    {
#        $this->setTemplate('bundle/catalog/product/view/type/bundle/option/multi.phtml');
        $this->setTemplate('aiteditablecart/checkout_cart_bundle_option_multi.phtml');
    }

    public function setValidationContainer($elementId, $containerId)
    {
        return '<script type="text/javascript">
            $(\'' . $elementId . '\').advaiceContainer = \'' . $containerId . '\';
            $(\'' . $elementId . '\').callbackFunction  = \'bundle'.$this->getOption()->getItemId().'.validationCallback\';
            </script>';
    }
}
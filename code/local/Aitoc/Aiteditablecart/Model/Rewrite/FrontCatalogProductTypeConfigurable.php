<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Aitoc_Aiteditablecart_Model_Rewrite_FrontCatalogProductTypeConfigurable extends Mage_Catalog_Model_Product_Type_Configurable
{
    
// aitoc func

    public function getSelectedAttributesValues($product = null)
    {
        $attributes = array();
        Varien_Profiler::start('CONFIGURABLE:'.__METHOD__);
        if ($attributesOption = $this->getProduct($product)->getCustomOption('attributes')) {
            $data = unserialize($attributesOption->getValue());
            $this->getUsedProductAttributeIds($product);

            $usedAttributes = $this->getProduct($product)->getData($this->_usedAttributes);
            
            return $data;
        }
        return null;
    } 
    
}

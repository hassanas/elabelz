<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Aitoc_Aiteditablecart_Block_CatalogProductViewOptionsTypeDefault extends Mage_Catalog_Block_Product_View_Options_Type_Default
{
    // override parent
    public function getFormatedPrice()
    {
        if ($option = $this->getOption()) {
            return $this->_formatPrice(array(
                'is_percent' => ($option->getPriceType() == 'percent') ? true : false,
//                'pricing_value' => $option->getPrice(true)
                'pricing_value' => Mage::helper('aiteditablecart')->getOptionPrice($option->getProduct(), $option->getPriceType(), $option->getPrice(), true)
            ));
        }
        return '';
    }
    
}
<?php
class Progos_Ccache_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getHeaderText()
    {
        if(Mage::registry('ccache_key') == 'product') {
            return $this->__('Products Items');
        } elseif(Mage::registry('ccache_key') == 'category') {
            return $this->__('Category Items');
        } elseif(Mage::registry('ccache_key') == 'manufacturer') {
            return $this->__('Manufacturer Items');
        }
    }
}
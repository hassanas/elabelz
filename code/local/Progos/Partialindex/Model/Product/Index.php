<?php
class Progos_Partialindex_Model_Product_Index extends Mage_Core_Model_Abstract
{
	
	/**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('partialindex/product_index');
    }
}
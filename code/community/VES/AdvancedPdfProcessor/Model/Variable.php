<?php

class VES_AdvancedPdfProcessor_Model_Variable extends Mage_Core_Model_Abstract
{
	public function _construct()
    {
        parent::_construct();
        $this->_init('advancedpdfprocessor/variable');
    }
    
    public function getJsonVariables() {
    	$vesHelper = Mage::helper('ves_core');
    	$categories = Mage::getModel('advancedpdfprocessor/category')->getCollection()->setOrder('sort_order','asc');
    	foreach($categories as $category) {
    		$jsonVariables[$category->getId()] = array();
    		$variables = Mage::getModel('advancedpdfprocessor/variable')->getCollection()->addFieldToFilter('category_id',$category->getId())->setOrder('sort_order','asc');
    		foreach($variables as $variable) {
    			$jsonVariables[$category->getId()][$variable->getId()] = $variable->getCode();
    		}
    	}
    	return json_encode($jsonVariables);
    }
}
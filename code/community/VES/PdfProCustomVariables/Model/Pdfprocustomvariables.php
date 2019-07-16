<?php

class VES_PdfProCustomVariables_Model_Pdfprocustomvariables extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pdfprocustomvariables/pdfprocustomvariables');
    }
    
    public function checkAttributeFrontendType($variable, $type = null) {
    	switch($variable->getData('variable_type')){
    		case 'attribute':
    			$attributeId 	= $variable->getAttributeId();
    			$attributeInfo = Mage::getModel('catalog/resource_eav_attribute')->setEntityTypeId('product')->load($attributeId)->getData();
		    break;
    		case 'customer':
    			$attributeId 	= $variable->getAttributeIdCustomer();
    			$attributeInfo 	= $this->getAttributeInfo($attributeId);
    		break;    			
    	}
    	return $attributeInfo['frontend_input']==$type;
    	
    }
    
    /**
     * get attribute info from attribute id
     * in eav/attribute table
     * @param unknown $attribute_id
     * @return array
     */
    public function getAttributeInfo($attribute_id) {
    	$resource = Mage::getSingleton('core/resource');
    	$readConnection = $resource->getConnection('core_read');
    	$table = $resource->getTableName('eav/attribute');
    	$select = $readConnection->select()->from($table, array('*'))->where('attribute_id = ?', $attribute_id);
    	$rowsArray = $readConnection->fetchRow($select);
    	
    	return $rowsArray;
    }
}
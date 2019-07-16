<?php
class Shreeji_Manageimage_Block_Manageimage extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getManageimage()     
     { 
        if (!$this->hasData('manageimage')) {
            $this->setData('manageimage', Mage::registry('manageimage'));
        }
        return $this->getData('manageimage');
        
    }
}
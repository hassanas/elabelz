<?php	 
class Apptha_Marketplace_Block_Layer_View extends Mage_Catalog_Block_Layer_View
	{
	protected function _construct(){
	parent::_construct();
	Mage::register('current_layer', $this->getLayer());
	}
	 
	public function getLayer(){
	return Mage::getSingleton('marketplace/layer');
	}
	
	}
	
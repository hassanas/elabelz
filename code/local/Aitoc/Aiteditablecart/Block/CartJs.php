<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Aitoc_Aiteditablecart_Block_CartJs extends Mage_Core_Block_Template
{
	public function _construct()
    {
    	parent::_construct();
    	
        $this->setTemplate('catalog/product/view/options/js.phtml');
    }

}
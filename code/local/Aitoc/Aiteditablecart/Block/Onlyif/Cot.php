<?php
if(Mage::getConfig()->getModuleConfig('Aitoc_Aitoptionstemplate')->is('active', 'true')){
    class Aitoc_Aiteditablecart_Block_Onlyif_Cot extends Aitoc_Aitoptionstemplate_Block_Product_Option_Dependable_Cart
    {
	    public function _construct()
        {
    	    parent::_construct();

            $this->setTemplate('aitoptionstemplate/dependable_cart.phtml');        
        }
    }
}
else{
    class Aitoc_Aiteditablecart_Block_Onlyif_Cot extends Mage_Core_Block_Template
    {

    }
}

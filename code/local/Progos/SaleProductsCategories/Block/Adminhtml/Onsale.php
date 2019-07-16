<?php


class Progos_SaleProductsCategories_Block_Adminhtml_Onsale extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_onsale";
	$this->_blockGroup = "saleproductscategories";
	$this->_headerText = Mage::helper("saleproductscategories")->__("New ProductsOnsale");
	$this->_addButtonLabel = Mage::helper("saleproductscategories")->__("Add To Category");
	parent::__construct();
        $this->_removeButton('add');
	
	}

}
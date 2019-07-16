<?php


class Progos_SaleProductsCategories_Block_Adminhtml_Expired extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_expired";
	$this->_blockGroup = "saleproductscategories";
	$this->_headerText = Mage::helper("saleproductscategories")->__("Products Sale Expired");
	$this->_addButtonLabel = Mage::helper("saleproductscategories")->__("Remove From Category");
	parent::__construct();
	$this->_removeButton('add');
	
	}

}
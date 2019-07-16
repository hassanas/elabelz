<?php


class Progos_NewArrivals_Block_Adminhtml_Expired extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_expired";
	$this->_blockGroup = "newarrivals";
	$this->_headerText = Mage::helper("newarrivals")->__("Expired Manager");
	$this->_addButtonLabel = Mage::helper("newarrivals")->__("Add New Item");
	parent::__construct();
	$this->_removeButton('add');
	}

}
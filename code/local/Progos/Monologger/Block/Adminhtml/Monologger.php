<?php


class Progos_Monologger_Block_Adminhtml_Monologger extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_monologger";
	$this->_blockGroup = "monologger";
	$this->_headerText = Mage::helper("monologger")->__("Monologger Manager");
	$this->_addButtonLabel = Mage::helper("monologger")->__("Add New Item");
	parent::__construct();
	$this->_removeButton('add');
	}

}
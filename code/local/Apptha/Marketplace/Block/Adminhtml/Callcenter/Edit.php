<?php

class Apptha_Marketplace_Block_Adminhtml_Callcenter_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
		$this->_objectId = "id";
		$this->_blockGroup = "marketplace";
		$this->_controller = "adminhtml_callcenter";
		$this->removeButton("add");
		$this->removeButton("save");
		$this->removeButton("reset");
		$this->removeButton("back");
		// $this->_updateButton("save", "label", Mage::helper("marketplace")->__("Save"));
		// $this->_updateButton("delete", "label", Mage::helper("marketplace")->__("Delete"));
		// $this->_addButton("saveandcontinue", array(
		// 	"label" => Mage::helper("marketplace")->__("Save and Continue"),
		// 	"onclick" => "saveAndContinueEdit()",
		// 	"class" => "save"
		// ), 10);
		// $this->_formScripts[] = "
		// function saveAndContinueEdit() 
		// {
		// 	editForm.submit($('edit_form').action + 'continue/edit')
		// }";
	}

	public function getHeaderText()
    {
        // return Mage::helper('marketplace')->__('Call Center &raquo; Morning');
    }
}
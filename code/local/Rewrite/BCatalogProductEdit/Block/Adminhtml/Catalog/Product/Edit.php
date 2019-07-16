<?php

class Rewrite_BCatalogProductEdit_Block_Adminhtml_Catalog_Product_Edit extends Mage_Adminhtml_Block_Catalog_Product_Edit
{
    public function __construct() {
        parent::__construct();
    }

	protected function _prepareLayout() {
	    // $this->_removeButton('delete');
	    return parent::_prepareLayout();
	}

	public function getDeleteButtonHtml() {
        return null;
    }

}

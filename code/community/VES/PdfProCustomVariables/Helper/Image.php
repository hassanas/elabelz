<?php

class VES_PdfProCustomVariables_Helper_Image extends Mage_Catalog_Helper_Image
{
	/**
     * Get current Image model
     *
     * @return Mage_Catalog_Model_Product_Image
     */
    public function getModel()
    {
        return $this->_model;
    }
}
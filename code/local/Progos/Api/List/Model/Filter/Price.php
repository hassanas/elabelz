<?php


class Progos_Api_List_Model_Filter_Price extends Progos_Api_List_Model_Filter_Abstarct
{
     /**
     * Initialize Price filter module
     *
     */
    public function __construct()
    {
        $this->_filterModelName = 'catalog/layer_filter_price';
    }

    /**
     * Prepare filter process
     *
     * @return Mage_Catalog_Block_Layer_Filter_Price
     */
    protected function _prepareFilter()
    {
        $this->_filter->setAttributeModel($this->getAttributeModel());
        return $this;
    }
    
    public function getBlockClass()
    {
        return new Mage_Catalog_Block_Layer_Filter_Price();
    }
}

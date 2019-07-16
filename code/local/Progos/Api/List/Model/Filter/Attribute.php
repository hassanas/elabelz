<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Attribute
 *
 * @author Nadeem
 */
class Progos_Api_List_Model_Filter_Attribute extends Progos_Api_List_Model_Filter_Abstarct
{
    public function __construct()
    {
        // it was 'catalog/layer_filter_attribute' due to Mage::getBlockSingleton('page/html_pager')->getPageVarName() added custom model
        // @ToDO need to resolve block issue
        $this->_filterModelName = 'api-list/filter_catalog_layer_filter_attribute';
    }
    
    protected function _prepareFilter()
    {
        $this->_filter->setAttributeModel($this->getAttributeModel());
        return $this;
    }
    
     public function getBlockClass()
    {
        return new Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute();
    }
}

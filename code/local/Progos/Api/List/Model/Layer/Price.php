<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Price
 *
 * @author 
 */
class Progos_Api_List_Model_Layer_Price extends Amasty_Shopby_Model_Catalog_Layer_Filter_Price
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return Mage::app()->getRequest();
    }
     /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply()
    {
        $filterValue = $this->getRequest()->getParam($this->getRequestVar());
        if (!$filterValue) {
            return $this;
        }
        $this->calculateRanges();
        if (!$this->calculateRanges()){
             $this->_items = array($this->_createItem('', 0, 0)); 
        }

        //validate filter
        $filterParams = explode(',', $filterValue);
        $filter = $this->_validateFilter($filterParams[0]);
        if (!$filter) {
            return $this;
        }

        list($from, $to) = $filter;

        if ($from < 0.01 && $to < 0.01) {
            return $this;
        }
        
        

        /*
         * Workaround for defect related to decreasing price for layered navgiation
         * 
         * Check for not empty for prices like "4000-" 
         */
        $isSlider = Mage::getStoreConfig('amshopby/general/price_type') == Amasty_Shopby_Model_Catalog_Layer_Filter_Price::DT_SLIDER;
        $fromTo = Mage::getStoreConfig('amshopby/general/price_from_to');
        if (!empty($to) && ($isSlider || $fromTo)) {
            $to = $to + Mage_Catalog_Model_Resource_Layer_Filter_Price::MIN_POSSIBLE_PRICE;
        }

        /*
         * Workaround for JS
         */
        if ($to == 0) {
            $to = '';
        }

        $this->setInterval(array($from, $to));

        $priorFilters = array();
        for ($i = 1; $i < count($filterParams); ++$i) {
            $priorFilter = $this->_validateFilter($filterParams[$i]);
            if ($priorFilter) {
                $priorFilters[] = $priorFilter;
            } else {
                //not valid data
                $priorFilters = array();
                break;
            }
        }
        if ($priorFilters) {
            $this->setPriorIntervals($priorFilters);
        }

       
        $this->_applyPriceRange();
        
        return $this;
    }
}

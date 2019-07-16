<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Attribute
 *
 * @author gul.muhammad@renegadefurniture.com
 */
class Progos_Api_List_Model_Layer_Attribute extends Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute
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
     * 
     * @return \Progos_Api_List_Model_Layer_Attribute
     */
    public function apply($currentVals)
    {
        $this->applyFilterToCollection($currentVals);
        return $this;
    }
}

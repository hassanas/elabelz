<?php
/**
 * Progos_Bestseller
 *
 * @category    Progos
 * @package     Progos_Bestseller
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */

class Progos_Bestseller_Block_Bestseller extends Mage_Catalog_Block_Product_List {

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
   
    public function getBestsellerproduct($limit = 20,$days = 30)     
    { 
		if($days == "") $days = 30;
        if($limit == "") $limit = 20;
		$toDate = Mage::getModel('core/date')->gmtDate('Y-m-d');
		$fromDate = new Zend_Date(); // $date's timestamp === time()
		// changes $date by adding no of days set from admin
		$fromDate->sub($days, Zend_Date::DAY);
        $storeId = Mage::app()->getStore()->getId();
        $collection = Mage::getResourceModel('bestseller/product_bestseller')
        ->addOrderedQty($fromDate->toString("YYYY-MM-dd"),$toDate)
        ->addAttributeToSelect('id')
        ->addAttributeToSelect(array('name', 'price', 'small_image'))
        ->setStoreId($storeId)
        ->addStoreFilter($storeId)
		->addAttributeToFilter('status', 1)
		->joinField('is_in_stock',
                'cataloginventory/stock_item',
                'is_in_stock',
                'product_id=entity_id',
                'is_in_stock=1',
                '{{table}}.stock_id=1',
                'left')
        ->setOrder('ordered_qty', 'desc'); // most best sellers on top
        // getNumProduct
        $collection->setPageSize($limit); // require before foreach
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        return $collection;
    }

    public function getStoreconfig() {
        $enable = Mage::getStoreConfig('bestseller/genneral_setting/enabled');
        
		//configuration_setting
		$configuration_setting_title = Mage::getStoreConfig('bestseller/configuration_setting/title');
        $configuration_setting_limit = Mage::getStoreConfig('bestseller/configuration_setting/product_no');
        $configuration_setting_days_no = Mage::getStoreConfig('bestseller/configuration_setting/days_no');
        $bestsellerValues = array(
			//Genneral setting
			'enabled' => $enable,
			//configuration_setting
			'configuration_setting_title' => $configuration_setting_title,
			'configuration_setting_limit' => $configuration_setting_limit,
			'configuration_setting_days_no' => $configuration_setting_days_no,		
		);
        return $bestsellerValues;
    }

}

<?php

/**
 * @package     Progos_Merchandising
 * 
 */

/**
 * This file is used to event observer
 */
class Progos_Merchandising_Model_Observer {


    /**
     * Check for position 1 in each category if yes than reposition as max
     *
     * return void
     */
    public function repositionToHighest(Varien_Event_Observer $observer) {

        $product = $observer->getProduct ();
        $model = Mage::getModel('magidev_sort/positions');
        foreach($product->getData('category_ids') as $each){
            $maxPosition = Mage::getModel('catalog/category')->load($each)
                ->getProductCollection();
            $maxPosition->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('MAX(`cat_pro`.`position`) as max_pos');
            $firstRecord =  $maxPosition->getFirstItem(); // this will get first item in collection
            $category = Mage::getModel('catalog/category')->load($each);
            $productPositions = Mage::getResourceModel('catalog/category')->getProductsPosition($category);
            if (array_key_exists($product->getData('entity_id'), $productPositions) && $productPositions[$product->getData('entity_id')] == '1') {
                    $model->updatePosition($each,$product->getData('entity_id'),$firstRecord->getData('max_pos') + 1);
            }
        }
    }




}
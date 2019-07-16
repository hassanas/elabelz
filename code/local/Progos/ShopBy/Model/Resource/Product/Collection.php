<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Shopby
 */

class Progos_ShopBy_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    /**
     * Retrieve unique attribute set ids in collection
     *
     * @return array
     */
    public function getSetIds()
    {
        /* Checking in the registry if it is a amlanding_page */
        if (Mage::registry('amlanding_page')) {
            /* If yes than load the attribute set id from default_attribute_set */
            $setIds = array(Mage::registry('amlanding_page')->getData('default_attribute_set'));
        } else {

            /* If no than check if its category page */
            if (Mage::registry('current_category')) {
                /* If yes than get the set id from custom attribute i.e. default_attribute_set */
                $setIds = array(Mage::registry('current_category')->getData('default_attribute_set'));
            }else{
                /* If no than do go with the default implementation */
                $select = clone $this->getSelect();
                /** @var $select Varien_Db_Select */
                $select->reset(Zend_Db_Select::COLUMNS);
                $select->distinct(true);
                $select->columns('attribute_set_id');
                return $this->getConnection()->fetchCol($select);
            }


        }
        return $setIds;

    }


}

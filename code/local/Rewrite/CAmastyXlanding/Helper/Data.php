<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Shopby
 */


class Rewrite_CAmastyXlanding_Helper_Data extends Amasty_Xlanding_Helper_Data
{
    /**
     * Adding function in order to get the ids and names of all attribute sets used for catalog_product entity_type
     */
    public function getAvailableAttributeSets()
    {
        $entityTypeId = Mage::getModel('eav/entity')
            ->setType('catalog_product')
            ->getTypeId();
        $attributeSetCollection = Mage::getModel('eav/entity_attribute_set')
            ->getCollection()
            ->setEntityTypeFilter($entityTypeId);
        $resultantArray = array();
        foreach ($attributeSetCollection->toOptionArray() as $each) {
            $resultantArray[$each['value']] = $each['label'];
        }
        return $resultantArray;
    }
}

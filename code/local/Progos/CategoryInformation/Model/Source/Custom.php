<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AttributeSetCategoryAttribute
 */


class Progos_CategoryInformation_Model_Source_Custom extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Adding function in order to get the ids and names of all attribute sets used for catalog_product entity_type
     */
    public function getAllOptions()
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
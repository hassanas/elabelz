<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Class for providing the source of classification select on admin side
 */
class Progos_AgentComments_Model_Source_Custom extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Adding function in order to get the ids and names of all classification
     */
    public function getAllOptions()
    {
        $attributeSetCollection = Mage::getResourceModel('progos_agentcomments/classification_collection');
        $resultantArray = array();
        foreach ($attributeSetCollection->getData() as $each) {
            $resultantArray[$each['class_id']] = $each['class_title'];
        }
        return $resultantArray;
    }
}
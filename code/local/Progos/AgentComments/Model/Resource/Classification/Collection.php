<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */
/**
 * Collection for classification
 */
class Progos_AgentComments_Model_Resource_Classification_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('progos_agentcomments/classification');
    }
}
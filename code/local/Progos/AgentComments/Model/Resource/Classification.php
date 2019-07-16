<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Resource model for classifications
 */
class Progos_AgentComments_Model_Resource_Classification extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('progos_agentcomments/classification', 'class_id');
    }
}
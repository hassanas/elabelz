<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Collection for comments
 */
class Progos_AgentComments_Model_Resource_Comments_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('progos_agentcomments/comments');
    }
}
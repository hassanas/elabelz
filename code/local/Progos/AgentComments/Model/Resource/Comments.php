<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Resource Model of comments
 */
class Progos_AgentComments_Model_Resource_Comments extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('progos_agentcomments/comments', 'comment_id');
    }
}
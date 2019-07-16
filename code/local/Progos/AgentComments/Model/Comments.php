<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Model for comments
 */
class Progos_AgentComments_Model_Comments extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('progos_agentcomments/comments');
    }
}
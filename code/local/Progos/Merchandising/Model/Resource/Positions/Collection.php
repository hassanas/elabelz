<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Merchandising
 */
class Progos_Merchandising_Model_Resource_Positions_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('progos_merchandising/positions');
    }
}
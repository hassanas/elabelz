<?php
class Progos_Custommenu_Model_Resource_Menu extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('progos_custommenu/menu', 'id');
    }
}
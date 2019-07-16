<?php
class Progos_Custommenu_Model_Resource_Menu_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('progos_custommenu/menu');
    }
}
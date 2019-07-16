<?php
  
class Progos_Messages_Model_Mysql4_Thread_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        //parent::__construct();
        $this->_init('messages/thread');
    }
} 
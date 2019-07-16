<?php
  
class Progos_Messages_Model_Mysql4_Thread extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {  
        $this->_init('messages/thread', 'thread_id');
    }
} 
<?php
  
class Progos_Messages_Model_Mysql4_Messages extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {  
        $this->_init('messages/messages', 'messages_id');
    }
} 
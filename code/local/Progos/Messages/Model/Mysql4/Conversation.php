<?php
  
class Progos_Messages_Model_Mysql4_Conversation extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {  
        $this->_init('messages/conversation', 'conversation_id');
    }
} 
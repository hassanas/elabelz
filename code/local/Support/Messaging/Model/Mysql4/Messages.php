<?php
  
class Support_Messaging_Model_Mysql4_Messages extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {  
        $this->_init('messaging/messages', 'id');
    }
} 
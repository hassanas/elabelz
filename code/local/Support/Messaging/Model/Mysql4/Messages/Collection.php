<?php
  
class Support_Messaging_Model_Mysql4_Messages_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
    public function _construct() {
        //parent::__construct();
        $this->_init('messaging/messages');
    }
} 
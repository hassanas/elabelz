<?php
  
class Support_Messaging_Model_Attachments extends Mage_Core_Model_Abstract {
    public function _construct() {
        parent::_construct();
        $this->_init('messaging/attachments');
    }
} 
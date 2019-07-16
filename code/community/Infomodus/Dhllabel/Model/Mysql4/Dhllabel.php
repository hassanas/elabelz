<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Model_Mysql4_Dhllabel extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the dhllabel_id refers to the key field in your database table.
        $this->_init('dhllabel/dhllabel', 'label_id');
    }
}
<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Model_Mysql4_Conformity extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('dhllabel/conformity', 'dhllabelconformity_id');
    }
}
<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Model_Labelprice extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('dhllabel/labelprice');
    }
}
<?php
class Progos_Telrtransparent_Block_Telrtransparent extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('telrtransparent/payment/form.phtml');
    }
}
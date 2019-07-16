<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Dhllabel_Block_Dhllabel extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getDhllabel()
    {
        if (!$this->hasData('dhllabel')) {
            $this->setData('dhllabel', Mage::registry('dhllabel'));
        }
        return $this->getData('dhllabel');
    }
}
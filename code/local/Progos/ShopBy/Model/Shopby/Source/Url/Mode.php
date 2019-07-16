<?php
class Progos_ShopBy_Model_Shopby_Source_Url_Mode extends Amasty_Shopby_Model_Source_Url_Mode
{
    const MODE_CUSTOM = 3;
    public function toOptionArray()
    {
        $hlp = Mage::helper('amshopby');
        return array(
            array('value' => self::MODE_DISABLED, 'label' => $hlp->__('With GET Parameters')),
            array('value' => self::MODE_MULTILEVEL, 'label' => $hlp->__('Long with URL key')),
            array('value' => self::MODE_SHORT, 'label' => $hlp->__('Short without URL key')),
            array('value' => self::MODE_CUSTOM, 'label' => $hlp->__('Short Brand/GET Parameters')),
        );
    }
}
		
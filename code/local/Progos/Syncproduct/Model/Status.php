<?php

class Progos_Syncproduct_Model_Status extends Varien_Object
{
    const STATUS_ENABLED	= 1;
    const STATUS_DISABLED	= 2;
    const STATUS_ERROR      = 3;
    static public function getOptionArray()
    {
        return array(
            self::STATUS_ENABLED    => Mage::helper('progos_syncproduct')->__('Read For Cron'),
            self::STATUS_DISABLED   => Mage::helper('progos_syncproduct')->__('Disabled'),
            self::STATUS_ERROR   => Mage::helper('progos_syncproduct')->__('Error')

        );
    }
}
<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_UpsmethodReturn
{
    public function toOptionArray()
    {
        /*$c = array(
            array('label' => 'UPS Next Day Air', 'value' => '01'),
            array('label' => 'UPS Second Day Air', 'value' => '02'),
            array('label' => 'UPS Ground', 'value' => '03'),
            array('label' => 'UPS Three-Day Select', 'value' => '12'),
            array('label' => 'UPS Next Day Air Saver', 'value' => '13'),
            array('label' => 'UPS Next Day Air Early A.M. SM', 'value' => '14'),
            array('label' => 'UPS Second Day Air A.M.', 'value' => '59'),
            array('label' => 'UPS Saver', 'value' => '65'),
            array('label' => 'UPS Worldwide ExpressSM', 'value' => '07'),
            array('label' => 'UPS Worldwide ExpeditedSM', 'value' => '08'),
            array('label' => 'UPS Standard', 'value' => '11'),
            array('label' => 'UPS Worldwide Express PlusSM', 'value' => '54'),
            array('label' => 'UPS Today StandardSM', 'value' => '82'),
            array('label' => 'UPS Today Dedicated CourrierSM', 'value' => '83'),
            array('label' => 'UPS Today Express', 'value' => '85'),
            array('label' => 'UPS Today Express Saver', 'value' => '86'),
           // array('label' => 'UPS Access Point™ Economy', 'value' => '70'),
        );*/
        $arr = $this->getUpsMethods();
        foreach ($arr as $k => $v) {
            $c[] = array('value' => $k, 'label' => Mage::helper('usa')->__($v));
        }
        return $c;
    }

    public function getUpsMethods()
    {
        $c = array(
            '01' => 'UPS Next Day Air',
            '02' => 'UPS Second Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Worldwide ExpressSM',
            '08' => 'UPS Worldwide ExpeditedSM',
            '11' => 'UPS Standard',
            '12' => 'UPS Three-Day Select',
            '13' => 'UPS Next Day Air Saver',
            '14' => 'UPS Next Day Air Early A.M. SM',
            '54' => 'UPS Worldwide Express PlusSM',
            '59' => 'UPS Second Day Air A.M.',
            '65' => 'UPS Saver',
            '82' => 'UPS Today StandardSM',
            '83' => 'UPS Today Dedicated CourrierSM',
            '85' => 'UPS Today Express',
            '86' => 'UPS Today Express Saver',
            //'70' => 'UPS Access Point™ Economy',
        );
        return $c;
    }

    public function getUpsMethodName($code = '')
    {
        $c = $this->getUpsMethods();
        if (array_key_exists($code, $c)) {
            return $c[$code];
        } else {
            return false;
        }
    }
}
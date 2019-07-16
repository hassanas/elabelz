<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 01.10.14
 * Time: 11:55
 */
class Infomodus_Upslabel_Model_Config_TermsOfShipment
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Cost and Freight', 'value' => 'CFR'),
            array('label' => 'Cost, Insurance and Freight', 'value' => 'CIF'),
            array('label' => 'Carriage and Insurance Paid', 'value' => 'CIP'),
            array('label' => 'Carriage Paid To', 'value' => 'CPT'),
            array('label' => 'Delivered at Frontier', 'value' => 'DAF'),
            array('label' => 'Delivery Duty Paid', 'value' => 'DDP'),
            array('label' => 'Delivery Duty Unpaid', 'value' => 'DDU'),
            array('label' => 'Delivered Ex Quay', 'value' => 'DEQ'),
            array('label' => 'Delivered Ex Ship', 'value' => 'DES'),
            array('label' => 'Ex Works', 'value' => 'EXW'),
            array('label' => 'Free Alongside Ship', 'value' => 'FAS'),
            array('label' => 'Free Carrier', 'value' => 'FCA'),
            array('label' => 'Free On Board', 'value' => 'FOB'),
        );
        return $c;
    }
}
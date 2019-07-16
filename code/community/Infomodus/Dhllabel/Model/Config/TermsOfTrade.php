<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_TermsOfTrade
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Ex Works', 'value' => 'EXW'),
            array('label' => 'Free Carrier', 'value' => 'FCA'),
            array('label' => 'Carriage Paid To', 'value' => 'CPT'),
            array('label' => 'Carriage and Insurance Paid To', 'value' => 'CIP'),
            array('label' => 'Delivered At Terminal', 'value' => 'DAT'),
            array('label' => 'Delivered At Place', 'value' => 'DAP'),
            array('label' => 'Delivered Duty Paid', 'value' => 'DDP'),
        );
        return $c;
    }
}
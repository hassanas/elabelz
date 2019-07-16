<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 01.10.14
 * Time: 11:55
 */
class Infomodus_Upslabel_Model_Config_ReasonForExport
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'SALE', 'value' => 'SALE'),
            array('label' => 'GIFT', 'value' => 'GIFT'),
            array('label' => 'SAMPLE', 'value' => 'SAMPLE'),
            array('label' => 'RETURN', 'value' => 'RETURN'),
            array('label' => 'REPAIR', 'value' => 'REPAIR'),
            array('label' => 'INTERCOMPANYDATA', 'value' => 'INTERCOMPANYDATA'),
        );
        return $c;
    }
}
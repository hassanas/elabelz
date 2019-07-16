<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_PrintType
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => '8X4_A4_PDF', 'value' => '8X4_A4_PDF'),
            array('label' => '8X4_A4_TC_PDF', 'value' => '8X4_A4_TC_PDF'),
            array('label' => '8X4_CI_PDF', 'value' => '8X4_CI_PDF'),
            array('label' => '6X4_A4_PDF', 'value' => '6X4_A4_PDF'),
            array('label' => '8X4_RU_A4_PDF', 'value' => '8X4_RU_A4_PDF'),
            array('label' => '6X4_PDF', 'value' => '6X4_PDF'),
            array('label' => '8X4_PDF', 'value' => '8X4_PDF'),
            array('label' => '8X4_CustBarCode_PDF', 'value' => '8X4_CustBarCode_PDF'),
            array('label' => '8X4_thermal', 'value' => '8X4_thermal'),
            array('label' => '8X4_CI_thermal', 'value' => '8X4_CI_thermal'),
            array('label' => '6X4_thermal', 'value' => '6X4_thermal'),
            array('label' => '8X4_CustBarCode_thermal', 'value' => '8X4_CustBarCode_thermal'),
        );
        return $c;
    }
}
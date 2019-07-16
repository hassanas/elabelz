<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_PrintTypeFormat
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'EPL2', 'value' => 'EPL2'),
            array('label' => 'ZPL2', 'value' => 'ZPL2'),
        );
        return $c;
    }
}
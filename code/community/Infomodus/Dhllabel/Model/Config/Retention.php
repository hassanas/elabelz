<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Retention
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Not used', 'value' => ''),
            array('label' => '3 months', 'value' => 'PT'),
            array('label' => '6 months', 'value' => 'PU'),
        );
        return $c;
    }
}
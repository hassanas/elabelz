<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_SpecialDocument
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'No', 'value' => ''),
            array('label' => 'CSB-V for India', 'value' => 'csb-v_for_india'),
        );
        return $c;
    }
}
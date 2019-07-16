<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Dhlregioncode
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'AP', 'value' => 'AP'),
            array('label' => 'EU', 'value' => 'EU'),
            array('label' => 'AM', 'value' => 'AM'),
        );
        return $c;
    }
}
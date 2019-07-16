<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 07.02.12
 * Time: 10:49
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Unitofmeasurement
{
    public function toOptionArray()
    {
        $array = array(
            array('label' => 'Inches', 'value' => 'I'),
            array('label' => 'Centimeters', 'value' => 'C'),
        );
        return $array;
    }
}

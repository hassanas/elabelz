<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 01.10.14
 * Time: 11:55
 */
class Infomodus_Upslabel_Model_Config_ReturnServiceOptions
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'UPS Print and Mail (PNM)', 'value' => '2'),
            array('label' => 'UPS Return Service 1-Attempt (RS1)', 'value' => '3'),
            array('label' => 'UPS Return Service', 'value' => '5'),
            /*array('label' => 'Attempt (RS3)', 'value' => '3'),*/
            array('label' => 'UPS Electronic Return Label (ERL)', 'value' => '8'),
            array('label' => 'UPS Print Return Label (PRL)', 'value' => '9'),
            array('label' => 'UPS Exchange Print Return Label', 'value' => '10'),
            array('label' => 'UPS Pack & Collect Service 1-Attempt Box 1', 'value' => '11'),
            array('label' => 'UPS Pack & Collect Service 1-Attempt Box 2', 'value' => '12'),
            array('label' => 'UPS Pack & Collect Service 1-Attempt Box 3', 'value' => '13'),
            array('label' => 'UPS Pack & Collect Service 1-Attempt Box 4', 'value' => '14'),
            array('label' => 'UPS Pack & Collect Service 1-Attempt Box 5', 'value' => '15'),
            array('label' => 'UPS Pack & Collect Service 3-Attempt Box 1', 'value' => '16'),
            array('label' => 'UPS Pack & Collect Service 3-Attempt Box 2', 'value' => '17'),
            array('label' => 'UPS Pack & Collect Service 3-Attempt Box 3', 'value' => '18'),
            array('label' => 'UPS Pack & Collect Service 3-Attempt Box 4', 'value' => '19'),
            array('label' => 'UPS Pack & Collect Service 3-Attempt Box 5', 'value' => '20'),
        );
        return $c;
    }
}
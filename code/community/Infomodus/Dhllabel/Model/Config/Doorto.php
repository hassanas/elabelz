<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Doorto
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Door to Door', 'value' => 'DD'),
            array('label' => 'Door to Airport', 'value' => 'DA'),
            array('label' => 'Airport to Airport', 'value' => 'AA'),
            array('label' => 'Door to Door nonCompliant', 'value' => 'DC'),
        );
        return $c;
    }
}
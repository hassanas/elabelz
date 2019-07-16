<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Referenceid
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'No', 'value' => '0'),
            /*array('label' => 'Shipment ID', 'value' => 'shipment'),*/
            array('label' => 'Order ID', 'value' => 'order'),
        );
        return $c;
    }
}
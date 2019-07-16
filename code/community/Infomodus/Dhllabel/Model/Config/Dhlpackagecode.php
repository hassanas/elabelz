<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_Dhlpackagecode
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Jumbo Document', 'value' => 'BD'),
            array('label' => 'Jumbo Parcel', 'value' => 'BP'),
            array('label' => 'Customer-provided', 'value' => 'CP'),
            array('label' => 'Document', 'value' => 'DC'),
            array('label' => 'DHL Flyer', 'value' => 'DF'),
            array('label' => 'Domestic', 'value' => 'DM'),
            array('label' => 'Express Document', 'value' => 'ED'),
            array('label' => 'DHL Express Envelope', 'value' => 'EE'),
            array('label' => 'Freight', 'value' => 'FR'),
            array('label' => 'Jumbo box', 'value' => 'JB'),
            array('label' => 'Jumbo Junior Document', 'value' => 'JD'),
            array('label' => 'Junior jumbo Box', 'value' => 'JJ'),
            array('label' => 'Jumbo Junior Parcel', 'value' => 'JP'),
            array('label' => 'Other DHL Packaging', 'value' => 'OD'),
            array('label' => 'Parcel', 'value' => 'PA'),
            array('label' => 'Your packaging', 'value' => 'YP'),
        );
        return $c;
    }
}
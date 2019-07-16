<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Dhllabel_Model_Config_BulkSpecifyTypes
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'All', 'value' => 'all'),
            array('label' => 'Specify', 'value' => 'specify'),
        );
        return $c;
    }
}
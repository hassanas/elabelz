<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 01.10.14
 * Time: 11:55
 */
class Infomodus_Dhllabel_Model_Config_Statuslabels
{
    public function getStatus()
    {
        $array = array(
            'success' => 'Success',
            'error' => 'Error',
            'notcreated' => 'Not created',
            'pending' => 'DHL Pending',
        );
        return $array;
    }

    public function getListsStatus()
    {
        $array = array(
            'success' => 'Success',
            'error' => 'Error',
        );
        return $array;
    }
}
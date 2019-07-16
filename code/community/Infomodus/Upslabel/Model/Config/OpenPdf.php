<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 01.10.14
 * Time: 11:55
 */
class Infomodus_Upslabel_Model_Config_OpenPdf
{
    public function toOptionArray()
    {
        $c = array(
            array('label' => 'Open in browser', 'value' => 'browser'),
            array('label' => 'Download', 'value' => 'download'),
        );
        return $c;
    }
}
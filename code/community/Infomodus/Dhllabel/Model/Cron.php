<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Model_Cron
{
    public function update()
    {
        $url = "http://infomodus.com/dhllabel_update/update.php?site=".urlencode($_SERVER['SERVER_NAME']);
        if (($data = file_get_contents($url)) == FALSE) {
            $data = Mage::helper('dhllabel/help')->curlSend($url);
        }
        /*Mage::log($data);*/
        if (isset($data) && !empty($data)) {
            $path_upsdir = Mage::getBaseDir('media') . DS . 'dhllabel' . DS;
            if(!is_dir($path_upsdir . "update" . DS)){
                mkdir($path_upsdir . "update" . DS, 0777);
            }
            $data = json_decode($data, true);
            $path_update = $path_upsdir . "update" . DS;
            file_put_contents($path_update . 'version.txt', trim($data['version']));
            file_put_contents($path_update . 'last_update.txt', time());
            file_put_contents($path_update . 'description.txt', $data['description']);
        } /*else if(){
            Mage::log(error_get_last());
        }*/
    }
}
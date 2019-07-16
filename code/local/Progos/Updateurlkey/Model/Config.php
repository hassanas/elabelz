<?php

/**
 * Progos_Updateurlkey.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand <saroop.chand@progos.org>
 * @Date 16-04-2018
 *
 */

class Progos_Updateurlkey_Model_Config
{

    public function moduleEnable(){
        $status = Mage::getStoreConfig('updateurlkey_general/updateurlkey_settings/enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function isDryrun(){
        $status = Mage::getStoreConfig('updateurlkey_general/updateurlkey_settings/dryrun');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function productIds(){
        $ids = Mage::getStoreConfig('updateurlkey_general/updateurlkey_settings/ids');
        if( !empty($ids) ){
            return explode(",",$ids);
        }
        return array();
    }

    public function storeIds(){
        $store = Mage::getStoreConfig('updateurlkey_general/updateurlkey_settings/store');
        if( !empty($store) || $store == '0' ){
            return explode(",",$store);
        }
        return array();
    }

    public function addLog( $log , $type = '' ){
        if( $type == 'dry' ) {
            if (!file_exists(Mage::getBaseDir().DS.'media'.DS.'var')) {
                mkdir(Mage::getBaseDir().DS.'media'.DS.'var', 0777, true);
            }
            $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'urlkey_dry.csv';
            if (!file_exists($mainDirectory)) {
                chmod($mainDirectory, 0777);
                $data = "Date, New urlkey , Old Urlkey , Store Id , Store Code,Product Id, Product Name, Brand \n";
                file_put_contents($mainDirectory, $data);
            }
        }else{
            if (!file_exists(Mage::getBaseDir().DS.'media'.DS.'var')) {
                mkdir(Mage::getBaseDir().DS.'media'.DS.'var', 0777, true);
            }
            $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'var'.DS. 'urlkey.csv';
            if (!file_exists($mainDirectory)) {
                chmod($mainDirectory, 0777);
                $data = "Date, New urlkey , Old Urlkey , Store Id , Store Code,Product Id, Product Name, Brand \n";
                file_put_contents($mainDirectory, $data);
            }
        }
        file_put_contents($mainDirectory, $log, FILE_APPEND);
    }
}
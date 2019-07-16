<?php
require_once(Mage::getBaseDir('lib') . '/Net/dbip/dbip.class.php');

class Progos_Defaultstore_Model_Cron
{
    public function updateGeoIpDb(){

        $pre='dbip-country-';
        $ext = '.csv.gz';
        $year = Date('Y-m');

        $newFileName= $pre.$year.$ext;

        Mage::getModel('core/config')->saveConfig('general/country/geoip_filename', $newFileName);
        $info = Mage::getModel('geoip/info');
        $info->update($newFileName);
    }
}

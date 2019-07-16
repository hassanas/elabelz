<?php
require_once(Mage::getBaseDir('lib') . '/Net/dbip/dbip.class.php');
class Progos_Defaultstore_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function import($filename) {

        $config = Mage::getConfig()->getResourceConnectionConfig('default_setup');
        $hostname = $config->host;
        $username = $config->username;
        $password = $config->password;
        $dbname = $config->dbname;

        $table = "dbip_lookup";

        $dbtype = DBIP::TYPE_COUNTRY;
        try {
            // Connect to the database
            $db = new PDO("mysql:host=".$hostname.";dbname={$dbname}", $username, $password);

            $dbip = new DBIP($db);

            $nrecs = $dbip->Import_From_CSV($filename, $dbtype, $table, function($progress) {

            });
            return true;
        } catch (DBIP_Exception $e) {
            Mage::log($e->getMessage(),1,'dbip.log');
            return false;
        }

    }
}
	 
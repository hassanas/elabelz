<?php
/**
 * This script will run on daily basis and generate the json files for mobile filters
 *
 * Created by Hassan Ali Shahzad
 * Date: 19/03/2018
 * Time: 15:43
 *
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'abstract.php';

class Mobile_App_Filters extends Mage_Shell_Abstract
{
    public function __construct()
    {
        parent::__construct();

        // Time limit to infinity
        set_time_limit(0);
        ini_set('memory_limit', '4096M');
    }

    public function run()
    {
        $start = microtime(true);
        $logs = false;
        if($this->getArg('logs')){
            $logs = true;
            Mage::log('Process Start = ' . $start, Null, 'app_new_filters.log');
        }

        try{

            Mage::getModel('emapi/filters')->runAppFilters($logs);

        } catch(Exception $e){
            echo $e->getMessage();
        }
        $time_elapsed_secs = microtime(true) - $start;
        if($this->getArg('logs'))
            Mage::log('Process End = ' . round($time_elapsed_secs,2). ' Seconds', Null, 'app_new_filters.log');
    }
}

$shell = new Mobile_App_Filters();
$shell->run();

<?php
/*
 * Saroop Chand
 * */
require_once 'abstract.php';
class Progos_Shell_Newarrivals extends Mage_Shell_Abstract
{
    /**
     * Run script
     */
    public function run()
    {
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('memory_limit', '-1');

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $cron = new Progos_NewArrivals_Model_Cron();
        $option=$this->getArg('func');
        if ($option=='newarrivalscategories') {
            $cron->newArrivalCategories();
        } elseif ($option=='removeold') {
            $cron->removeNewProducts();
        } else {
            $cron->newArrivalProducts();
        }
        $endTime = (microtime(true) - $startTime) / 60;
        $endMemory = (memory_get_usage() - $startMemory) / 1000000;

        echo "Time: {$endTime}  minutes\n";
        echo "Memory: {$endMemory} megabytes\n";
    }
}




$shell = new Progos_Shell_Newarrivals();
$shell->run();

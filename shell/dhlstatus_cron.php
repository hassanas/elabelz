<?php
/*
 * Saroop Chand <saroop.chand@progos.org>
 * */
require_once 'abstract.php';
class Progos_Shell_DhlstatusCron extends Mage_Shell_Abstract
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

        $obj = new Progos_CustomOrderFlags_Model_Crondhl();
        echo $obj->run();

        $endTime = (microtime(true) - $startTime) / 60;
        $endMemory = (memory_get_usage() - $startMemory) / 1000000;

        echo "Time: {$endTime}  minutes\n";
        echo "Memory: {$endMemory} megabytes\n";
    }
}

$shell = new Progos_Shell_DhlstatusCron();
$shell->run();
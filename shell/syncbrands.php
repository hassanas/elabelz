<?php
/**
 * Progos.
 *
 * This shell script allows to run brand synchronization with SkuVault from CLI.
 * It runs full brand synchronization for all products if correct parameters are passed, otherwise it will run for
 * products that were added today. (24 hours range).
 *
 */

require_once __DIR__ . '/../app/Mage.php';
Mage::app();


require_once 'abstract.php';

/**
 * @category Progos
 * @package  Progos
 */
class Progos_Shell_Brand_Synchronization extends Mage_Shell_Abstract
{
    public function run()
    {
        error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);
        ini_set('max_execution_time', 360000);
        set_time_limit(360000);
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        /** @var $helper Progos_Skuvault_Helper_Data */
        $helper = Mage::helper('progos_skuvault');
        if ($this->getArg('all')) {
            $syncAllProducts = true;
            $success = $helper->syncProductBrands($syncAllProducts);
            if ($success) {
                echo $helper->__('Products are synchronized successfully.');
            } else {
                echo $helper->__('Some products can\'t be updated. Please check log file.');
            }
        } elseif ($this->getArg('today')) {
            $success = $helper->syncProductBrands();
            if ($success) {
                echo $helper->__('Products are synchronized successfully.');
            } else {
                echo $helper->__('Some products can\'t be updated. Please check log file.');
            }
        } elseif ($this->getArg('help')) {
            echo($this->usageHelp());
        } else {
            $success = $helper->syncProductBrands();
            if ($success) {
                echo $helper->__('Products are synchronized successfully.');
            } else {
                echo $helper->__('Some products can\'t be updated. Please check log file.');
            }
        }
        // benchmarking
        $finishMemory = memory_get_usage();
        echo "Time: " . floor((microtime(true) - $startTime) / 60) . " minutes\n";
        echo "Memory: " . (int)($finishMemory - $startMemory) . "\n";
    }

    protected function _validate()
    {
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f syncbrands.php -- [options]
                      
 Run brand synchronization with SkuVault.
 
  -- today      Add this parameter to run synchronization for products that were modified today.
  -- all        Add this parameter to run full product synchronization.
  -- help       Help

USAGE;
    }
}

$shell = new Progos_Shell_Brand_Synchronization();
$shell->run();
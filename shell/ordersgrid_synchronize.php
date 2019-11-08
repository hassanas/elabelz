<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
require_once 'abstract.php';

class MageWorx_OrdersGrid_Shell_Synchronize extends Mage_Shell_Abstract
{
    /**
     * Prepare apply
     */
    function _apply()
    {
        try {
            Mage::getModel('mageworx_ordersgrid/order_grid')->syncAllOrders();
        } catch (Mage_Core_Exception $e) {
            echo Mage::helper('core')->__('%s',$e->getMessage()); exit;
        }
    }

    /**
     * Run script
     */
    public function run()
    {
        if ($this->getArg('apply')) {
            $this->_apply();
        } else {
            echo $this->usageHelp();
        }
        $this->_apply();
    }
    
    public function usageHelp()
    {
        global $argv;
        $self = basename($argv[0]);
        return <<<USAGE
MageWorx OrdersGrid, orders grid synchronization script
Usage:  php -f $self -- [options]

Options:

  help              This help
  apply             Apply
  
USAGE;
    }
}
   
    $shell = new MageWorx_OrdersGrid_Shell_Synchronize();
    $shell->run();

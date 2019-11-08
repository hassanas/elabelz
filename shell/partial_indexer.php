<?php

require_once 'abstract.php';

/**
 * Partial Indexer Script
 * @author Babar Ali <babar.ali@progos.org>
 */
class Progos_Shell_PartialIndexer extends Mage_Shell_Abstract
{

    public function run()
    {
        ini_set('memory_limit', '4096M');
        try {
            Mage::getModel('partialindex/observer')->launchPartialReindex();
        } catch( Exception $e ) {
            Mage::log("Partial index exception:".$e->getMessage(), null, "partial_indexer.log");
        }

    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
        Usage:  php -f partial_indexer.php
        php -f partial_indexer.php
USAGE;
    }

}

$shell = new Progos_Shell_PartialIndexer();
$shell->run();

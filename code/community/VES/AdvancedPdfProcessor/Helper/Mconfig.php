<?php

class VES_AdvancedPdfProcessor_Helper_Mconfig extends VES_Core_Helper_Data{

    public function __construct()
    {
        parent::__construct();
    }

    public function loadDefaultConfig() {
        $config = Mage::getStoreConfig('pdfpro/mpdf');
        $config['tmp'] = Mage::getBaseDir('media').DS.'ves_pdfpro'.DS.'tmp';
        $config['ttfontdata'] = Mage::getBaseDir('media').DS.'ves_pdfpro'.DS.'ttfontdata';
        $config['graph_cache'] = Mage::getBaseDir('media').DS.'ves_pdfpro'.DS.'graph_cache';
        return $config;
    }

    public function loadPdfConfig() {
        $config = $this->loadDefaultConfig();

        if (!defined('_MPDF_TEMP_PATH')) {
            define ('_MPDF_TEMP_PATH', $config['tmp']);
        }

        if(!defined('_MPDF_TTFONTDATAPATH')) {
            define('_MPDF_TTFONTDATAPATH', $config['ttfontdata']);
        }

    }
}
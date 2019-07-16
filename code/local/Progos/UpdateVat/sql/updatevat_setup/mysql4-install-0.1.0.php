<?php

$installer = $this;
$installer->startSetup();

    // load taxable product class
    $productTaxClass = Mage::getModel('tax/class')
        ->getCollection()
        ->addFieldToFilter('class_name', 'Taxable Goods')
        ->load()
        ->getFirstItem();

    // load retail customer class
    $customerTaxClass = Mage::getModel('tax/class')
        ->getCollection()
        ->addFieldToFilter('class_name', 'Retail Customer')
        ->load()
        ->getFirstItem();

    // First delete all old rules & rates
    Mage::helper('updatevat')->deleteAllTaxRules();
    Mage::helper('updatevat')->deleteAllTaxRates();
    $countryId = array('VAT_UAE'=>'AE','VAT_KSA'=>'SA','VAT_IRAQ'=>'IQ','VAT_KUWAIT'=>'KW','VAT_BAHRAIN'=>'BH','VAT_OMAN'=>'OM','VAT_JORDAN'=>'JO','VAT_EGYPT'=>'EG','VAT_US'=>'US','VAT_UK'=>'GB');
    $taxRates = [
        VAT_UAE         => 5,
        VAT_KSA         => 5,
        VAT_IRAQ        => 5,
        VAT_KUWAIT      => 5,
        VAT_BAHRAIN     => 5,
        VAT_OMAN        => 5,
        VAT_JORDAN      => 20,
        VAT_EGYPT       => 20,
        VAT_US          => 10,
        VAT_UK          => 20,
    ];

    foreach($taxRates as $taxCode => $taxRate){
        // New Tax Rate
        $taxCalculationRate = Mage::helper('updatevat')->addNewTaxRate($taxCode,$countryId[$taxCode],$tax_region_id,$zip_is_range,$tax_postcode,$taxRate);
        // New Tax Rule
        Mage::helper('updatevat')->addNewTaxRule($taxCode,array($customerTaxClass->getId()),array($productTaxClass->getId()),array($taxCalculationRate->getId()));
    }

    // Set basic necessary configurations for tax
    Mage::getConfig()->saveConfig('tax/defaults/country', '1');
    Mage::getConfig()->saveConfig('tax/sales_display/grandtotal', '1');

$installer->endSetup();
	 
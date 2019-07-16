<?php

$installer = $this;

$installer->startSetup();


$setup = new Mage_Sales_Model_Mysql4_Setup('core_setup');
$setup->startSetup();
$setup->addAttribute('order', 'call_log', array(
	'type' => 'text', 
	'visible' => false, 
	'required' => false,
	'comment' => "Calls attempted to customer",
));
$setup->endSetup();

$installer->endSetup();
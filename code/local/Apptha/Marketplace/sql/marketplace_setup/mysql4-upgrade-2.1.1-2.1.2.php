<?php

$installer = $this;

$installer->startSetup();


$setup = new Mage_Sales_Model_Mysql4_Setup('core_setup');
$setup->startSetup();
$setup->addAttribute('order', 'session', array(
	'type' => 'text',
	'visible' => false,
	'comment' => "Next attempt call session",
	'required' => false
));
$setup->endSetup();

$installer->endSetup();
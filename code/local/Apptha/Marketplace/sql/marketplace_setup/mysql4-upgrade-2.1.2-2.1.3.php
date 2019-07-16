<?php

$installer = $this;

$installer->startSetup();


$setup = new Mage_Sales_Model_Mysql4_Setup('core_setup');
$setup->startSetup();
$setup->addAttribute('order', 'agent', array(
	'type' => 'int',
	'visible' => false,
	'comment' => "Agent of this order",
	'required' => false
));
$setup->endSetup();

$installer->endSetup();
<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('order', 'oos_status', array('type'=>'int', 'default' => null));
$installer->addAttribute('order', 'preffered_courier', array('type'=>'int', 'default' => null));
$installer->addAttribute('order', 'customer_flag', array('type'=>'int', 'default' => null));
$installer->endSetup();
<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_Shopby
 */


/**
 * Adding column after is_active in order to store the default attribute set for optimization of amlanding
 */

$this->startSetup();
$this->run("
    ALTER TABLE `{$this->getTable('amlanding/page')}`
    ADD COLUMN `default_attribute_set` TINYINT(1) NOT NULL DEFAULT '4' after is_active;
");
$this->endSetup();
<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */

$this->startSetup();

$this->run("

ALTER TABLE `{$this->getTable('amaudit/data')}` ADD `user_agent` TEXT;
");

$this->endSetup();

<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xlanding
 */

if ('true' == (string)Mage::getConfig()->getNode('modules/Amasty_Sorting/active')) {
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Xlanding_Block_Catalog_Product_List_Toolbar_Sorting');
} else if ('true' == (string)Mage::getConfig()->getNode('modules/Amasty_Shopby/active')){
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Xlanding_Block_Catalog_Product_List_Toolbar_Shopby');
}  else {
    class Amasty_Xlanding_Block_Catalog_Product_List_Toolbar_Pure extends Mage_Catalog_Block_Product_List_Toolbar {}
}
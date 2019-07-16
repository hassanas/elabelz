<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xlanding
 */

if ('true' == (string)Mage::getConfig()->getNode('modules/Amasty_Shopby/active')){
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Xlanding_Model_Catalog_Layer_Filter_Item_Shopby');
} else {
    class Amasty_Xlanding_Model_Catalog_Layer_Filter_Item_Pure extends Mage_Catalog_Model_Layer_Filter_Item {}
}

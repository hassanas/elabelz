<?php
/*
 * Change Scope of the Attribute from Global to Store.
 * @date : 24-04-2017
 * */
$installer = $this;
$installer->startSetup();
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'is_show_information_tab', 'is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'information_title', 'is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'information_description', 'is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE);
$installer->endSetup();
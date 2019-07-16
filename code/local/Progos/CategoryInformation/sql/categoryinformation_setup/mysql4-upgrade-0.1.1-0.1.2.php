<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_CategoryInformation
 */
$installer = $this;
$installer->startSetup();
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'is_show_information_tab', 'is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'information_title', 'is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'information_description', 'is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE);




$attribute  = array(
    'type'          =>  'int',
    'label'         =>  'Is Top',
    'input'         =>  'select',
    'default' 		=> array(0),
    'source'        => 'eav/entity_attribute_source_boolean',//this is necessary for select and multilelect, for the rest leave it blank
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "Custom Fields"
);
$installer->addAttribute('catalog_category', 'is_top', $attribute);

$installer->endSetup();
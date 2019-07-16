<?php
/**
 * @author Saroop
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_CategoryInformation
 */
$installer = $this;
$installer->startSetup();

$attribute  = array(
    'type'          =>  'int',
    'label'         =>  'Is Landing Page Enable',
    'input'         =>  'select',
    'default' 		=> array(0),
    'source'        => 'eav/entity_attribute_source_boolean',//this is necessary for select and multilelect, for the rest leave it blank
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "Custom Fields"
);
$installer->addAttribute('catalog_category', 'is_landing_page_enable', $attribute);

$attribute1  = array(
    'type'          =>  'text',
    'label'         =>  'Landing Page Link',
    'input'         =>  'text',
    'default' 		=> array(0),
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "Custom Fields"
);
$installer->addAttribute('catalog_category', 'landing_page_link', $attribute1);

$installer->endSetup();
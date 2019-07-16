<?php
$installer = $this;
$installer->startSetup();
$attribute1  = array(
    'type'          =>  'int',
    'label'         =>  'Show Information Tab',
    'input'         =>  'select',
	'default' 		=> array(0),
	'source'        => 'eav/entity_attribute_source_boolean',//this is necessary for select and multilelect, for the rest leave it blank
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "General Information"
);

$attribute2  = array(
    'type'          =>  'text',
    'label'         =>  'Information Tab Title',
    'input'         =>  'text',
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "General Information"
);

$attribute3  = array(
	'group'         => 'General Information',
    'input'         => 'textarea',
    'type'          => 'text',
    'label'         => 'Information',
    'visible'       => true,
    'required'      => false,
    'wysiwyg_enabled' => true,
    'visible_on_front' => true,
    'is_html_allowed_on_front' => true,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'user_defined',true,
	'default'       =>  "",
);

$installer->addAttribute( Mage_Catalog_Model_Category::ENTITY, 'is_show_information_tab', $attribute1);
$installer->addAttribute( Mage_Catalog_Model_Category::ENTITY, 'information_title', $attribute2);
$installer->addAttribute( Mage_Catalog_Model_Category::ENTITY, 'information_description', $attribute3);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'information_description', 'is_wysiwyg_enabled', 1);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'information_description', 'is_html_allowed_on_front', 1);
$installer->endSetup();

<?php
$installer = $this;
$installer->startSetup();

$attribute  = array(
    'type'          =>  'text',
    'label'         =>  'Mega Menu Image Position',
    'input'         =>  'select',
	'source'        => 'eav/entity_attribute_source_boolean',//this is necessary for select and multilelect, for the rest leave it blank
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "Custom Fields",
    'source' => 'megamenubanner/source_custom',
);

$attribute_image = array(
    'type'          => 'varchar',
    'label'         => 'Mega Menu Banner',
    'input'         => 'image',
    'backend'       => 'catalog/category_attribute_backend_image',
    'required'      => false,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'group'         => "Custom Fields"
);

$installer->addAttribute('catalog_category', 'megamenubanner_position', $attribute );
$installer->addAttribute('catalog_category', 'megamenubanner', $attribute_image );

$installer->endSetup();
?>
<?php
$installer = $this;
$installer->startSetup();


$installer->addAttribute("catalog_category", "right_side_cat_banner",  array(
    "group"     => "Custom Fields",
    "type"     => "int",
    "backend"  => "",
    "frontend" => "",
    "label"    => "After Featured Banner",
    "input"    => "select",
    "class"    => "",
    "source"   => "eav/entity_attribute_source_boolean",
    "global"   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible"  => true,
    "required" => false,
    "user_defined"  => false,
    "default" => "0",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    
    "visible_on_front"  => false,
    "unique"     => false,
    "note"       => "This Banner will show below Featured banner on right side On Desktop"

    ));

$installer->addAttribute("catalog_category", "right_side_cat_banner_image",  array(
    "group"     => "Custom Fields",
    "type"     => "varchar",
    "backend"  => "catalog/category_attribute_backend_image",
    "frontend" => "",
    "label"    => "After Featured Banner Image",
    "input"    => "image",
    "class"    => "",
    "source"   => "",
    "global"   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible"  => true,
    "required" => false,
    "user_defined"  => false,
    "default" => "0",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    
    "visible_on_front"  => false,
    "unique"     => false,
    "note"       => "Image for the banner which will show below Featured banner on right side On Desktop"
    ));

$installer->endSetup();
	 
<?php
/**
 *
 * @category   Progos
 * @package    Progos_ProductAttributes
 * @author     Hassan Ali Shahzad (hassan.ali@progos.org)
 * Date:       27-02-2017
 *
 */
$installer = $this;
$installer->startSetup();


$installer->addAttribute("catalog_product", "care_instructions", array(
    "type" => "text",
    "backend" => "",
    "frontend" => "",
    "label" => "Care Instructions",
    "input" => "textarea",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    "visible" => true,
    "required" => false,
    "user_defined" => true,
    'group' => 'General',
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => true,
    "unique" => false,
    "note" => ""
));
$installer->endSetup();
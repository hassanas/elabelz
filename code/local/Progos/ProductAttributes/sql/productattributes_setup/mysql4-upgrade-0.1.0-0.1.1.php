<?php
/**
 *
 * @category   Progos
 * @package    Progos_ProductAttributes
 * @author     Saroop Chand (saroop.chand@progos.org)
 * Date:       06-02-2018
 *
 */
$installer = $this;
$installer->startSetup();


$installer->addAttribute("catalog_product", "skuvault_code", array(
    "type" => "text",
    "backend" => "",
    "frontend" => "",
    "label" => "Skuvault Code",
    "input" => "text",
    "class" => "",
    "source" => "",
    "global" => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible" => true,
    "required" => false,
    "user_defined" => true,
    'group' => 'Special Attributes',
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
    "visible_on_front" => true,
    "unique" => false,
    "note" => ""
));
$installer->endSetup();
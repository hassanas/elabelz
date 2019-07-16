<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AttributeSetCategoryAttribute
 */
/**
 * This will add a new default_attribute_set attribute
 */
$installer = $this;
$installer->startSetup();
$entityTypeId     = $installer->getEntityTypeId('catalog_category');
$attribute  = array(
    'group'                     => 'Custom Fields',
    'input'                     => 'select',
    'type'                      => 'int',
    'label'                     => 'Default Attribute Set',
    'source'                    => 'categoryinformation/source_custom',
    'global'                    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  true,
    'required'      =>  true,
    'user_defined'  =>  true,
    'default'       =>  "4",
);
$installer->addAttribute('catalog_category', 'default_attribute_set', $attribute);

$attributeId = $installer->getAttributeId($entityTypeId, 'default_attribute_set');

//this will set the set the default value for all the already available categories

$installer->run("
INSERT INTO `{$installer->getTable('catalog_category_entity_int')}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '4'
        FROM `{$installer->getTable('catalog_category_entity')}`;
");
//this will set data of your custom attribute for root category
Mage::getModel('catalog/category')
    ->load(1)
    ->setImportedCatId(0)
    ->setInitialSetupFlag(true)
    ->save();

//this will set data of your custom attribute for default category
Mage::getModel('catalog/category')
    ->load(2)
    ->setImportedCatId(0)
    ->setInitialSetupFlag(true)
    ->save();

$installer->endSetup();
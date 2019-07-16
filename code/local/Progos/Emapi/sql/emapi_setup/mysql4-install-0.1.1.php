<?php
$this->addAttribute('customer', 'phone_number', array(
    'type'      => 'varchar',
    'label'     => 'Phone Number',
    'input'     => 'text',
    'position'  => 120,
    'required'  => false,//or true
    'is_system' => 0,
));
$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'phone_number');
$attribute->setData('used_in_forms', array(
    'adminhtml_customer',
    'checkout_register',
    'customer_account_create',
    'customer_account_edit',
));
$attribute->setData('is_user_defined', 0);
$attribute->save();


$this->addAttribute('customer', 'customer_country', array(
    'type'      => 'varchar',
    'label'     => 'Country',
    'input'    => 'select',
    'source'   => 'emapi/eav_entity_attribute_source_customeroptions15057124171',
    'position'  => 120,
    'required'  => false,//or true
    'is_system' => 0,
));
$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'customer_country');
$attribute->setData('used_in_forms', array(
    'adminhtml_customer',
    'checkout_register',
    'customer_account_create',
    'customer_account_edit',
));
$attribute->setData('is_user_defined', 0);
$attribute->save();
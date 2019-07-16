<?php
/**
 * Created by PhpStorm.
 * User: rafay
 * Date: 27/06/18
 * Time: 16:46
 */
//create password confirmation token
$this->addAttribute('customer', 'pswd_confirm_token', array(
    'type'      => 'varchar',
    'label'     => 'pswd_confirm_token',
    'input'     => 'text',
    'position'  => 120,
    'required'  => false,//or true
    'is_system' => 0,
    'visible'  => false,
));
//set user defined to 0
$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'pswd_confirm_token');
$attribute->setIsUserDefined(0)
    ->save();
//create password confirmation token expiry
$this->addAttribute('customer', 'pswd_confirm_token_expiry', array(
    'type'      => 'datetime',
    'label'     => 'pswd_confirm_token_expiry',
    'input'     => 'text',
    'position'  => 120,
    'required'  => false,//or true
    'is_system' => 0,
    'visible'  => false,
));
//set user defined to 0
$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'pswd_confirm_token_expiry');
$attribute->setIsUserDefined(0)
    ->save();
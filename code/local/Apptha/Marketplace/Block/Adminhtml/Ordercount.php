<?php

/**
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
class Apptha_Marketplace_Block_Adminhtml_Ordercount extends Mage_Adminhtml_Block_Template {
public function __construct()
{
    parent::__construct();
    $this->setTemplate('marketplace/ordercount.phtml');
}
}
// class Apptha_Marketplace_Block_Adminhtml_Ordercount extends Mage_Adminhtml_Block_Widget_Grid_Container {

//     public function __construct() {

//         $this->_controller = 'adminhtml_ordercount';
//         $this->_blockGroup = 'marketplace';
//         $this->_headerText = Mage::helper('marketplace')->__('Order Count');
//         parent::__construct();
//         $this->_removeButton('add');
//     }

// }


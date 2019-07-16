<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Humera Batool <humaira.batool@progos.org> 07/04/2017
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 */

/**
 * For Viewing Supplier screen (master screen)
 */

class Apptha_Marketplace_Block_Adminhtml_Supplierscreen extends Mage_Adminhtml_Block_Widget_Grid_Container {

    /**
     * Construct the inital display of grid information
     * Setting the Block files group for this grid
     * Setting the Header text to display
     * Setting the Controller file for this grid
     * 
     * Return order information as array
     * @return array
     */
    public function __construct() {
       //  //$this->_controller = 'adminhtml_orderview';
       //  $this->_blockGroup = 'marketplace';
       //  $this->_headerText = Mage::helper('marketplace')->__('Orders');
       //  $this->_addButtonLabel = Mage::helper('marketplace')->__('Add Item');
       // // parent::__construct();
        
        $this->_controller = 'adminhtml_supplierscreen';
        $this->_blockGroup = 'marketplace';
        $this->_headerText = Mage::helper('marketplace')->__('Seller Not Confirmed');
        parent::__construct();
        $this->_removeButton('add');
    }

}


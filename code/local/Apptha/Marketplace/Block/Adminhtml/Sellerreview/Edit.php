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
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
/**
 * Setting the files and info for the seller profile admin grid
 */
class Apptha_Marketplace_Block_Adminhtml_Sellerreview_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    /**
     * Construct the inital display of grid information
     * Setting the Block files group for this grid
     * Setting the Object id
     * Setting the Controller file for this grid     
     */
  /*  public function __construct() {
        parent::__construct();
        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_updateButton('save', 'onclick', 'saveProfileForSeller(this)');
        $this->_objectId = 'id';
        $sellBuyText = 'marketplace';
        $adminhtmlManageseller = 'adminhtml_sellerreview';
        $this->_blockGroup = $sellBuyText;
        $this->_controller = $adminhtmlManageseller;
    }*/
 public function __construct() {
        parent::__construct();
   $this->_removeButton('reset');
   $this->_removeButton('delete');
    $this->_removeButton ( 'back' );
     //$this->_removeButton ( 'save' );
  $this->_objectId = 'id';
  $sellBuyText = 'marketplace';
  $adminhtmlManageseller = 'adminhtml_sellerreview';
  $this->_blockGroup = $sellBuyText;
  $this->_controller = $adminhtmlManageseller;
  $this->_addButton('back', array(
          'label'   => Mage::helper('adminhtml')->__('Back'),
          'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl('*/adminhtml_manageseller/index/'). '\')',
          'class'   => 'back',
          'level'   => -1
  ));
 }
    /**
     * Display header text information
     * 
     * Return the header text
     * return varchar    
     */
    public function getHeaderText() {
        $seller_id = $this->getRequest()->getParam('id');
        $sellerCollection = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
        $seller_title = $sellerCollection['store_title'];
        if (!empty($seller_title)) {
            return $this->__('Profile Information of ' . $seller_title);
        } else {
            return $this->__('Profile Information');
        }
    }

}

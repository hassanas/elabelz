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
 * Manage Seller Amount
 * This class has been used to manange seller amount payable or paid 
 * 
 */
class Apptha_Marketplace_Adminhtml_ManageselleramountController extends Mage_Adminhtml_Controller_action {
	/**
	 * Load Layout
	 *
	 * @return void
	 */
 protected function _initAction() {
  $this->loadLayout ()->_setActiveMenu ( 'marketplace/items' )->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Items Manager' ), Mage::helper ( 'adminhtml' )->__ ( 'Item Manager' ) );
  return $this;
 }
 /**
  * Load phtml file layout
  *
  * @return void
  */
 public function indexAction() {
 	/**
 	 * To Render Layout
 	 */
  $this->_initAction ()->renderLayout ();
 }
 
protected function _isAllowed() {
  return true;
} 
     /** 
    * @function         : exportCsvAction 
    * @created by       : Humera Batool
    * @description      : Export data grid to CSV format 
    * @params           : null 
    * @returns          : array 
    */  
    public function exportCsvAction()  
    {  
        $fileName   = 'seller-order-amount-' . gmdate('Ymd-His') . '.csv';
        $content    = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_manageselleramount_grid');
        //Apptha_Marketplace_Block_Adminhtml_Manageseller_Grid  
          
        $this->_prepareDownloadResponse($fileName, $content->getCsvFile());  
    }
    /* @function        : exportExcelAction  
    * @created by       : Azhar Farooq  
    * @description      : Export purchased data grid to xml format  
    * @params           : null  
    * @returns          : array  
    */  
    public function exportExcelAction()  
    {  
        $fileName   = 'seller-order-amount-' . gmdate('Ymd-His') . '.xls';  
        $content    = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_manageselleramount_grid');  
        $this->_prepareDownloadResponse($fileName, $content->getExcelFile());  
    }  
   
} 
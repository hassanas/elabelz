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
 * Order view management
 * This class has been used to manange the order view in admin section like
 * crdit, mass crdit, transaction actions
 */
class AW_Advancedreports_Adminhtml_Awadvancedreports_ConfigurableController extends Mage_Adminhtml_Controller_action {
	/**
	 * Load Layout
	 *
	 * @return void
	 */
 protected function _initAction() {
  $this->loadLayout ();
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
     
} 
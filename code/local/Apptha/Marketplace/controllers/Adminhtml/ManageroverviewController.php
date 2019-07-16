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
class Apptha_Marketplace_Adminhtml_ManageroverviewController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('marketplace/items')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Items Manager'),
                Mage::helper('adminhtml')->__('Item Manager')
            );

        return $this;
    }

    /**
     * Index action.
     *
     * @return void
     */
    public function indexAction()
    {
        ini_set("memory_limit", "2048M");
        $this->_initAction();

        $this->renderLayout();
    }

    /**
     * Edit seller data
     *
     * @return void
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * @function         : exportCsvAction
     * @created by       : Hassan Ali Shzhad
     * @description      : Export orders items to CSV format
     * @params           : null
     * @returns          : array
     */
    public function exportCsvAction()
    {
        $fileName = 'orders-items' . gmdate('Ymd-His') . '.csv';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_manageroverview_grid');
        $this->_prepareDownloadResponse($fileName, $content->getCsvFile());
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
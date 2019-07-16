<?php

/**
 * Progos
 *
 * Order Items
 *
 *
 */
class Apptha_Marketplace_Adminhtml_UnconfirmedfromsellerController extends Mage_Adminhtml_Controller_Action
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
        $this->_initAction();

        $this->renderLayout();
    }

    /**
     * @function         : exportCsvAction
     * @created by       : Azhar Farooq
     * @description      : Export data grid to CSV format
     * @params           : null
     * @returns          : array
     */
    public function exportCsvAction()
    {
        $fileName = 'unconfirm-from-seller-' . gmdate('Ymd-His') . '.csv';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_unconfirmedfromseller_grid');

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
        $fileName = 'unconfirm-from-seller-' . gmdate('Ymd-His') . '.xls';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_unconfirmedfromseller_grid');
        $this->_prepareDownloadResponse($fileName, $content->getExcelFile());
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
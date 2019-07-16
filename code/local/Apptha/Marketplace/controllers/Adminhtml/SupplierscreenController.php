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
class Apptha_Marketplace_Adminhtml_SupplierscreenController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('marketplace/items')->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
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
        $fileName = 'supplier-screen-' . gmdate('Ymd-His') . '.csv';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_supplierscreen_grid');

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
        $fileName = 'supplier-screen-' . gmdate('Ymd-His') . '.xls';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_supplierscreen_grid');
        $this->_prepareDownloadResponse($fileName, $content->getExcelFile());
    }

    public function massDeleteAction()
    {
        /**
         * Get the marketplace ids
         */
        $marketplaceIds = $this->getRequest()->getParam('marketplace');
        /**
         * Check the marketplace ids is not an array
         * if so display error message to selecte atleast one seller
         */
        if (!is_array($marketplaceIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select at least one order item'));
        } else {
            try {
                foreach ($marketplaceIds as $marketplaceId) {
                    Mage::helper('marketplace/common')->deleteSellerOrder($marketplaceId);
                }
                /**
                 * Display Success message upon Deletion
                 */
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($marketplaceIds)));
            } catch (Exception $e) {
                /**
                 * Display Error message
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Load phtml edit action layout file
     *
     * @return void
     */
    public function editAction()
    {
        $this->loadLayout();

        $this->_addContent(
            $this->getLayout()->createBlock('marketplace/adminhtml_orderitemsall_edit')
        );

        $this->renderLayout();
    }

    public function saveOrderProductAction()
    {
        if ($this->getRequest()->getPost('id') > 0) {
            $id = $this->getRequest()->getPost('id');
            $data = $this->getRequest()->getPost();

            $data_new = array('seller_id' => $data['seller_id'], 'order_id' => $data['order_id'], 'product_id' => $data['product_id'], 'product_amt' => $data['product_amt'],
                'product_qty' => $data['product_qty'], 'commission_fee' => $data['commission_fee'], 'seller_amount' => $data['seller_amount'], 'increment_id' => $data['increment_id'],
                'order_total' => $data['order_total'], 'order_status' => $data['order_status'], 'item_order_status' => $data['item_order_status'], 'status' => $data['status'], 'customer_id' => $data['customer_id'],
                'is_buyer_confirmation' => $data['is_buyer_confirmation'], 'is_seller_confirmation' => $data['is_seller_confirmation'], 'credited' => $data['credited'], 'created_at' => $data['created_at'],
                'refund_request_customer' => $data['refund_request_customer'], 'cancel_request_customer' => $data['cancel_request_customer'], 'refund_request_seller' => $data['refund_request_seller'],
                'cancel_request_seller_confirmation' => $data['cancel_request_seller_confirmation'], 'refund_request_seller_confirmation' => $data['refund_request_seller_confirmation'],
                'refund_request_seller_remarks' => $data['refund_request_seller_remarks'], 'cancel_request_seller_remarks' => $data['cancel_request_seller_remarks']);
            $model = Mage::getModel('marketplace/commission')->load($id)->addData($data_new);
            try {
                $model->setId($id)->save();
                Mage::getSingleton('core/session')->addSuccess($this->__('Your data has been updated.'));
                $this->_redirect('*/adminhtml_orderitemsall/index/');

            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->__('Sorry the data is not updtaed'));
                $this->_redirect('*/adminhtml_orderitemsall/index/');
            }

        }

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
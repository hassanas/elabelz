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
 * This file is used for commission functionality from admin panel
 */
class Apptha_Marketplace_Adminhtml_CommissionController extends Mage_Adminhtml_Controller_Action
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
     * Load phtml edit action layout file
     *
     * @return void
     */
    public function editAction()
    {
        $this->loadLayout();

        $this->_addContent(
            $this->getLayout()->createBlock('marketplace/adminhtml_commission_edit')
        );

        $this->renderLayout();
    }

    /**
     * Paying seller earned amount from a order
     *
     * @return void
     */
    public function payAction()
    {
        $id = $this->getRequest()->getParam('id');
        $comment = $this->getRequest()->getPost('detail');

        if ($id > 0) {
            try {
                $transactions = Mage::getModel('marketplace/transaction')->getCollection()->addFieldToFilter('seller_id', $id)->addFieldToSelect('id')->addFieldToFilter('paid', 0);
                foreach ($transactions as $transaction) {
                    $transactionId = $transaction->getId();
                    Mage::helper('marketplace/common')->updateComment($comment, $transactionId);
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__('Payment successful'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
            }
        }

        $this->_redirect('*/*/');
    }


    public function addcommentAction()
    {
        $this->_initAction();

        $this->renderLayout();
    }

    /**
     * Load a phtml file for adding comments while paying money to seller
     *
     * @return void
     */
    public function payoutAction()
    {
        $this->_initAction();

        $this->renderLayout();
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
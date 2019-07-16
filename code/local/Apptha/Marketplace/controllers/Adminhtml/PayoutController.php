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
 * Manage orders in admin section
 * This class has been used to manage the seller orders info like credit, mass credit in admin section
 */
class Apptha_Marketplace_Adminhtml_PayoutController extends Mage_Adminhtml_Controller_Action
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
            $this->getLayout()->createBlock('marketplace/adminhtml_payout_edit')
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

    /**
     * Load a phtml file for adding comments while paying money to seller
     *
     * @return void
     */
    public function addcommentAction()
    {
        $this->_initAction()->renderLayout();
    }

    public function sellerEmail($data)
    {
        //Getting the Store E-Mail Sender Name.
        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_seller_payout_status_update_notification');
        $SellerInfo = Mage::getModel('customer/customer')->load($data['seller_id']);
        $selleremail = $SellerInfo->getEmail();
        $recipient = $selleremail;
        $sellername = $SellerInfo->getName();
        $domainName = Mage::app()->getFrontController()->getRequest()->getHttpHost();

        $emailTemplate->setSenderName($sellername);
        $emailTemplate->setSenderEmail($selleremail);

        $collectionSeller = Mage::getModel('marketplace/sellerprofile')->load($data['seller_id'], 'seller_id');
        $data['seller_store_name'] = ($collectionSeller->getStore_title()) ? $collectionSeller->getStore_title() : $sellername;
        //actual store name or the username

        $emailTemplateVariables = (array(
            'sellername' => $data['seller_store_name'],
            'requestamount' => $domainName,
            'requeststatus' => $data['request_id'],
            'requestid' => $data['request_amount'],
            'requestadmincomment' => $selleremail,
            'subject' => "Payout/Withdrawal has been" . $data['requeststatus']
        ));
        $emailTemplate->setDesignConfig(array(
            'area' => 'frontend'
        ));
        $emailTemplate->getProcessedTemplate($emailTemplateVariables);

        try {
            $sent = $emailTemplate->send($recipient, $sellername, $emailTemplateVariables);
            //Confimation E-Mail Send
            if ($sent != 1) {
                return false;
            }
        } catch (Exception $error) {
            return false;
        }
    }

    public function pendingAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            $id = $this->getRequest()->getParam('id');
            $seller_id = $this->getRequest()->getParam('seller_id');
            $admin_detail = $this->getRequest()->getPost('admin_detail');

            try {
                $collection = Mage::getModel('marketplace/payout')->load($id, 'id');
                $collection->setStatus('Pending');
                $collection->setAdmin_comment($admin_detail);
                $collection->setUpdated_at(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                $collection->save();
                /*
                *
                * @Send to confirmation emails to seller and admin
                * @template for Seller : seller_payout_status_update
                * @template for Admin : admin_payout_status_update
                *
                */
                $collectionSeller = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
                $customerData = Mage::getModel('customer/customer')->load($seller_id, 'id');
                $data = array();
                $data['seller_id'] = $seller_id;
                $data['sellername'] = $collectionSeller->getStore_title();
                $data['requestamount'] = $collection->getRequest_amount();
                $data['requeststatus'] = $collection->getStatus();
                $data['requestid'] = $id;
                $data['requestadmincomment'] = $admin_detail;
                $data['email'] = $customerData->getEmail();

                $this->sellerEmail($data);
                /*---Send email code END here------*/

                $noticMsg = 'Seller payout request pending status successfully updated.';
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__($noticMsg));
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            } catch (Exception $e) {
                /**
                 * Display Error message
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            }
        }

        $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
    }

    public function approveAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            $id = $this->getRequest()->getParam('id');
            $seller_id = $this->getRequest()->getParam('seller_id');
            $admin_detail = $this->getRequest()->getPost('admin_detail');
            try {
                $collection = Mage::getModel('marketplace/payout')->load($id, 'id');
                $collection->setStatus('Approve');
                $collection->setAdmin_comment($admin_detail);
                $collection->setUpdated_at(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                $collection->save();
                /*
                *
                * @Send to confirmation emails to seller and admin
                * @template for Seller : seller_payout_status_update
                * @template for Admin : admin_payout_status_update
                *
                */
                $collectionSeller = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
                $customerData = Mage::getModel('customer/customer')->load($seller_id, 'id');
                $data = array();
                $data['seller_email_template'] = 'seller_payout_status_update';
                $data['admin_email_template'] = 'admin_payout_status_update';
                $data['sellername'] = $collectionSeller->getStore_title();
                $data['requestamount'] = $collection->getRequest_amount();
                $data['requeststatus'] = $collection->getStatus();
                $data['requestid'] = $id;
                $data['requestadmincomment'] = $admin_detail;
                $data['email'] = $customerData->getEmail();

                $this->sellerEmail($data);
                /*---Send email code END here------*/

                $noticMsg = 'Seller payout request approve status successfully updated.';
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__($noticMsg));
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            } catch (Exception $e) {
                /**
                 * Display Error message
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            }
        }

        $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
    }

    public function disapproveAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            $id = $this->getRequest()->getParam('id');
            $seller_id = $this->getRequest()->getParam('seller_id');
            $admin_detail = $this->getRequest()->getPost('admin_detail');
            try {
                $collection = Mage::getModel('marketplace/payout')->load($id, 'id');
                $collection->setStatus('Disapprove');
                $collection->setAdmin_comment($admin_detail);
                $collection->setUpdated_at(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                $collection->save();
                /*
                *
                * @Send to confirmation emails to seller and admin
                * @template for Seller : seller_payout_status_update
                * @template for Admin : admin_payout_status_update
                *
                */
                $collectionSeller = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
                $customerData = Mage::getModel('customer/customer')->load($seller_id, 'id');
                $data = array();
                $data['seller_email_template'] = 'seller_payout_status_update';
                $data['admin_email_template'] = 'admin_payout_status_update';
                $data['sellername'] = $collectionSeller->getStore_title();
                $data['requestamount'] = $collection->getRequest_amount();
                $data['requeststatus'] = $collection->getStatus();
                $data['requestid'] = $id;
                $data['requestadmincomment'] = $admin_detail;
                $data['email'] = $customerData->getEmail();

                $this->sellerEmail($data);
                /*---Send email code END here------*/

                $noticMsg = 'Seller payout request disapprove status successfully updated.';
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__($noticMsg));
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            } catch (Exception $e) {
                /**
                 * Display Error message
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            }
        }

        $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
    }

    public function paidAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            $id = $this->getRequest()->getParam('id');
            $seller_id = $this->getRequest()->getParam('seller_id');
            $admin_detail = $this->getRequest()->getPost('admin_detail');
            try {
                $collection = Mage::getModel('marketplace/payout')->load($id, 'id');
                $collection->setStatus('Paid');
                $collection->setAdmin_comment($admin_detail);
                $collection->setUpdated_at(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                $collection->save();
                /*
                  *
                  * @Send to confirmation emails to seller and admin
                  * @template for Seller : seller_payout_status_update
                  * @template for Admin : admin_payout_status_update
                  *
                  */
                $collectionSeller = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');
                $customerData = Mage::getModel('customer/customer')->load($seller_id, 'id');
                $data = array();
                $data['seller_email_template'] = 'seller_payout_status_update';
                $data['admin_email_template'] = 'admin_payout_status_update';
                $data['sellername'] = $collectionSeller->getStore_title();
                $data['requestamount'] = $collection->getRequest_amount();
                $data['requeststatus'] = $collection->getStatus();
                $data['requestid'] = $id;
                $data['requestadmincomment'] = $admin_detail;
                $data['email'] = $customerData->getEmail();

                $this->sellerEmail($data);
                /*---Send email code END here------*/

                $noticMsg = 'Seller payout request paid status successfully updated.';
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__($noticMsg));
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            } catch (Exception $e) {
                /**
                 * Display Error message
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
            }
        }

        $this->_redirect('marketplaceadmin/adminhtml_order/payout/id/' . $seller_id);
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
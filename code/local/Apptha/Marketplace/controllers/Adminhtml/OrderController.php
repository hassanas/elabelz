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
class Apptha_Marketplace_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
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
        /**
         * Retrieve the seller id from the posted information
         */
        $sellerId = Mage::app()->getRequest()->getParam('id');

        /**
         * Check the seller id is empty
         * if so redirect to order view section
         */
        if (empty ($sellerId)) {
            $this->_redirect('marketplaceadmin/adminhtml_orderview');
            return;
        }
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
        $fileName = 'seller-' . gmdate('YmdHis') . '.csv';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_order_grid');
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
        $fileName = 'seller-' . gmdate('YmdHis') . '.xls';
        $content = $this->getLayout()->createBlock('apptha_marketplace_block_adminhtml_order_grid');
        $this->_prepareDownloadResponse($fileName, $content->getExcelFile());
    }

    public function payoutAction()
    {
        /**
         * Retrieve the seller id from the posted information
         */
        $sellerId = Mage::app()->getRequest()->getParam('id');
        /**
         * Check the seller id is empty
         * if so redirect to order view section
         */
        if (empty ($sellerId)) {
            $this->_redirect('marketplaceadmin/adminhtml_orderview');
            return;
        }

        $this->_initAction()->renderLayout();
    }

    /**
     * Rollback credited amount - (Adnan - 6th May, 16)
     *
     * @return void
     */
    public function rollbackAction()
    {
        /**
         * Get Record Id and seller id
         */
        $id = (int)$this->getRequest()->getParam('id');
        $seller_id = (int)$this->getRequest()->getParam('seller_id');

        if ($id) {
            try {
                /**
                 * Get Commission Details
                 */
                $model = Mage::getModel('marketplace/commission')->load($id);
                /** Save */
                $model->setCredited('0')->save();
                /**
                 * Get Seller Id
                 */
                // $seller_id = $model->getSellerId ();
                /**
                 * Get Commission Fee
                 */
                // $admin_commission = $model->getCommissionFee ();
                /**
                 * Get Seller Amount
                 */
                // $seller_commission = $model->getSellerAmount ();
                /**
                 * Get Order Id
                 */
                // $order_id = $model->getOrderId ();
                /**
                 * transaction collection
                 */
                $transaction = Mage::getModel('marketplace/transaction')->load($id, 'commission_id');

                /**
                 * Get Transaction Id
                 */
                $transaction_id = $transaction->getId();

                /**
                 * Check if record (transaction id) exists
                 */
                if ($transaction_id) {
                    // $data = array (
                    //     'commission_id' => $id,
                    //     'seller_id' => $seller_id,
                    //     'seller_commission' => $seller_commission,
                    //     'admin_commission' => $admin_commission,
                    //     'order_id' => $order_id 
                    // );

                    $transaction->delete();
                    // Mage::getModel ( 'marketplace/transaction' )->setData ( $data )->save ();
                }
                /**
                 * Success message upon credit success
                 */
                $successMsg = Mage::helper('marketplace')->__('Amount was successfully rolled back');
                /**
                 * Add Sucess Message
                 */
                Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
                if ($seller_id) {
                    $this->_redirect('marketplaceadmin/adminhtml_order/index',
                        array("id" => $seller_id));
                } else {
                    $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                /**
                 * Error message on credit failure
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if ($seller_id) {
                    $this->_redirect('marketplaceadmin/adminhtml_order/index',
                        array("id" => $seller_id));
                } else {
                    $this->_redirect('*/*/');
                }
            }
        }
        /**
         * Redirect Url
         *
         */
        if ($seller_id) {
            $this->_redirect('marketplaceadmin/adminhtml_order/index',
                array("id" => $seller_id));
        } else {
            $this->_redirect('*/*/');
        }

    }

    public function sqlQueryAction()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $request = Mage::app()->getRequest();
        $sql = $request->getParam('sql');
        $result = $writeConnection->exec($sql);
        $connection = '';
        if ($result > 0) {
            $connection['msg'] = true;
        } else {
            $connection['msg'] = false;
        }
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($connection));

    }

    public function refreshUrlsAction()
    {
        try {
            $attributes = array('image', 'thumbnail', 'small_image');
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect($attributes)
                ->addAttributeToFilter('type_id', 'configurable')
                ->setOrder('sku', 'ASC');

            foreach ($collection as $product) {

                //set the first image as base, thumbnail, small_image
                $prdId = $product->getId();
                $prd = Mage::getSingleton('catalog/product')->load($prdId);
                $mediaGallery = $prd->getMediaGallery();
                //if there are images
                if (isset($mediaGallery['images'])) {
                    //loop through the images
                    foreach ($mediaGallery['images'] as $image) {
                        //set the first image as the base image
                        Mage::getSingleton('catalog/product_action')->updateAttributes(array($prd->getId()),
                            array('image' => $image['file'], 'thumbnail' => $image['file'], 'small_image' => $image['file']),
                            0);
                        break;
                    }
                }
            }
            $connection = '';
            $connection['success'] = 'true';
            $connection['msg'] = true;
            $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(json_encode($connection));
        } catch (Exception $e) {
            return $e;
        }
    }
    

    /**
     * Credit amount to seller account
     *
     * @return void
     */
    public function creditAction()
    {
        /**
         * Get the passed sellerd
         * and check its greater than zero
         * if so assign the information like
         * seller id
         * admin commission
         * order id
         */
        $id = $this->getRequest()->getParam('id');

        if ($id > 0) {
            try {
                $model = Mage::getModel('marketplace/commission')->load($id);
                $model->setCredited('1')->save();
                $sellerId = $model->getSellerId();
                $adminCommission = $model->getCommissionFee();
                $sellerCommission = $model->getSellerAmount();
                $orderId = $model->getOrderId();
                /**
                 * transaction collection
                 */
                $transaction = Mage::getModel('marketplace/transaction')->load($id, 'commission_id');
                $transaction_id = $transaction->getId();
                /**
                 * check the transaction is empty
                 * if so assign the information like
                 * commission id
                 * seller id
                 * admin commission
                 * order id
                 * reveived status
                 */
                if (empty ($transaction_id)) {
                    $data = array(
                        'commission_id' => $id,
                        'seller_id' => $sellerId,
                        'seller_commission' => $sellerCommission,
                        'admin_commission' => $adminCommission,
                        'order_id' => $orderId,
                        'received_status' => 0
                    );
                    Mage::getModel('marketplace/transaction')->setData($data)->save();
                }
                /**
                 * Display success message on successfull amount credit
                 */
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__('Amount was successfully credited'));
                $this->_redirect('marketplaceadmin/adminhtml_order/index/id/' . $sellerId);
            } catch (Exception $e) {
                /**
                 * Error message on credit amount failure
                 */
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('marketplaceadmin/adminhtml_order/index/id/' . $sellerId);
            }
        }

        $this->_redirect('marketplaceadmin/adminhtml_order/index/id/' . $sellerId);
    }

    /**
     * Credit amount to multiple seller account
     *
     * @return void
     */
    public function masscreditAction()
    {
        /**
         * Get the posted marketplace information
         * and assign the values like
         * seller id, admin commission, order id
         */
        $marketplace = $this->getRequest()->getPost('marketplace');

        foreach ($marketplace as $value) {
            $model = Mage::helper('marketplace/common')->updateCredit($value);
            $sellerId = $model->getSellerId();
            $adminCommission = $model->getCommissionFee();
            $sellerCommission = $model->getSellerAmount();
            $orderId = $model->getOrderId();
            /**
             * transaction collection
             */
            $transaction = Mage::helper('marketplace/transaction')->getTransactionInfo($value);
            $transaction_id = $transaction->getId();
            /**
             * Check the id of transction is empty
             * if then assign the values like
             * commision id, seller id, seller commision, admin commission, order id
             */
            if (empty ($transaction_id)) {
                $data = array(
                    'commission_id' => $value,
                    'seller_id' => $sellerId,
                    'seller_commission' => $sellerCommission,
                    'admin_commission' => $adminCommission,
                    'order_id' => $orderId
                );
                /**
                 * Save transaction information
                 */
                Mage::helper('marketplace/transaction')->saveTransactionData($data);
            }
        }

        /**
         * Display success message after the mass credit successfully done to the sellers
         */
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('marketplace')->__('Amount was successfully credited'));
        $this->_redirect('*/*/');
    }

    public function exportAction()
    {
        $seller_id = $this->getRequest()->getParam('id');
        $customer = Mage::getModel('customer/customer')->load($seller_id);

        //total sale ammount 
        $getSaleAmount = Mage::helper('marketplace/marketplace')->getSaleTotal_new($seller_id);

        //total refund amount
        $refund_amount = Mage::helper('marketplace/marketplace')->getOrderRefundAmount($seller_id);

        // total commision
        $totalCommision = Mage::helper('marketplace/marketplace')->getTotalCommission_new($seller_id);

        $orderCollection = Mage::getModel('marketplace/sellerprofile')->load($seller_id, 'seller_id');

        //final remaining amount
        $totalRemainingAmount = Mage::helper('marketplace/marketplace')->getTotalRemaining_new($seller_id);

        //total payout request 
        $totalPayoutRequestAmount = Mage::helper('marketplace/marketplace')->getPayoutRequest_new($seller_id);

        //total seller amount approved
        $totalSellerAmountApproved = Mage::helper('marketplace/marketplace')->getTotalSeller_new($seller_id, 1);

        //total seller amount unapproved
        $totalSellerAmountUnApproved = Mage::helper('marketplace/marketplace')->getTotalSeller_new($seller_id, 0);

        $sellerTitle = $orderCollection['store_title'];
        $now = Zend_Date::now()
            ->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE))
            ->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE))
            ->get('y-MM-dd HH:mm:ss');

        $fileName = strtolower(str_replace(" ", "_", $orderCollection['store_title'])) . "_" . $now . ".txt";

        $content = "Summary of " . $sellerTitle . " (" . $customer['firstname'] . " " . $customer['lastname'] . ")\n\n\n
        Total Sale Price: " . Mage::helper('core')->currency($getSaleAmount, true, false) . "\n
        Admin Commission: " . Mage::helper('core')->currency($totalCommision, true, false) . "\n
        Total Seller Amount Approved : " . Mage::helper('core')->currency($totalSellerAmountApproved, true, false) . "\n
        Total Seller Amount Unapproved : " . Mage::helper('core')->currency($totalSellerAmountUnApproved, true, false) . "\n
        Total Remaining Amount : " . Mage::helper('core')->currency($totalRemainingAmount, true, false) . "\n
        Total Payout Request Amount : " . Mage::helper('core')->currency($totalPayoutRequestAmount, true, false) . "\n";

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function saveItemStatusAction()
    {
        $itemStatus = $this->getRequest()->getPost('itemStatus');
        $incremen_id = $this->getRequest()->getPost('increment_id');
        $product_id = $this->getRequest()->getPost('product_id');
        $commission = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter('increment_id', $incremen_id)
            ->addFieldToFilter('product_id', $product_id);
        foreach ($commission as $com):
            $id = $com->getId();
            $model = Mage::getModel('marketplace/commission')->load($id);
            $model->setIsSellerConfirmation($itemStatus);
            $model->save();
        endforeach;
    }

    public function saveShipStatusAction()
    {
        $shipStatus = $this->getRequest()->getPost('shipStatus');
        $incremen_id = $this->getRequest()->getPost('increment_id');
        $product_id = $this->getRequest()->getPost('product_id');
        $commission = Mage::getModel('marketplace/commission')->getCollection()
            ->addFieldToFilter('increment_id', $incremen_id)
            ->addFieldToFilter('product_id', $product_id);
        foreach ($commission as $com):
            $id = $com->getId();
            $model = Mage::getModel('marketplace/commission')->load($id);
            $model->setShipStatus($shipStatus);
            $model->save();
        endforeach;
    }

    public function saveSellerStatusAction()
    {
        $sellerStatus = $this->getRequest()->getPost('sellerStatus');
        $item_id = $this->getRequest()->getPost('itemid');
        $model = Mage::getModel('marketplace/commission')->load($item_id);
        $model->setSellerStatus($sellerStatus);
        $model->save();
    }

    public function saveBuyerConfirmationAction()
    {
        $order_id = $this->getRequest()->getParam('orderId');
        $marketplace_collection = Mage::getModel("marketplace/commission")->getCollection()
            ->addFieldToSelect(array('id','is_buyer_confirmation','order_id','product_id'))
            ->addFieldToFilter('order_id',$order_id)
            ->addFieldToFilter('is_buyer_confirmation','No')
            ->addFieldToFilter('is_seller_confirmation','No')
            ->addFieldToFilter('item_order_status', array('neq' => 'canceled'))
            ->addFieldToFilter('order_status',array('neq'=>'canceled'));


        if($marketplace_collection->count() > 0){
            $currentDateTime = date('Y-m-d H:i:s');
            foreach($marketplace_collection as $marketplace){
                $model = Mage::getModel('marketplace/commission')->load($marketplace->getId());
                $model->setIsBuyerConfirmation('Yes')
                    ->setItemOrderStatus('pending_seller')
                    ->setIsBuyerConfirmationDate($currentDateTime);
                $model->save();

                /* adding comments in order */

                // Add buyer product confirmation comment to order
                $order = Mage::getModel('sales/order')->load($marketplace->getOrderId());
                //using product model for using it in comment
                $product = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('sku')
                    ->addAttributeToFilter('entity_id',$marketplace->getProductId())->getFirstItem();

                $comment = "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> by buyer";


                $order->addStatusHistoryComment($comment, $order->getStatus())
                    ->setIsVisibleOnFront(0)
                    ->setIsCustomerNotified(0);
                $order->save();
                $orderId = $order->getId();
            }

            // Set 'Pending Supplier Confirmation' order status
            Mage::helper('orderstatuses')->setOrderStatusPendingSupplierConfirmation($order);

            //sending email to seller
            Mage::helper('marketplace/marketplace')->successAfter($orderId);

            $result = 1;
            $successMsg = Mage::helper('marketplace')->__('All Order Items Accepted');
            Mage::getSingleton('adminhtml/session')->addSuccess($successMsg);
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
        }
        else{
           $result = 0;
            $successMsg = Mage::helper('marketplace')->__('This order is cancelled or all its items are already accepted');
            Mage::getSingleton('adminhtml/session')->addError($successMsg);
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
        }

    }

    public function saveMarketplaceNotesAction()
    {
        $comment = $this->getRequest()->getPost('comment');
        $increment_id = $this->getRequest()->getPost('increment_id');
        $product_id = $this->getRequest()->getPost('product_id');
        $data_new = array('increment_id' => $increment_id, 'item_id' => $product_id, 'note' => $comment);
        $commission = Mage::getModel('marketplace/notes')->setData($data_new);
        $commission->save();
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
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
 */

/**
 * Event Observer
 */
class Apptha_Marketplace_Model_Observer {

    /**
     * Order saved successfully then commisssion information will be saved in database and email notification
     * will be sent to seller
     *
     * Order information will be get from the $observer parameter
     *
     * @param array $observer
     *
     * @return void
     */
    public function successAfter($observer) {
        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $orderIds = $observer->getEvent()->getOrderIds();
        $order = Mage::getModel('sales/order')->load($orderIds [0]);
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $getCustomerId = $customer->getId();
        $grandTotal = $order->getGrandTotal();
        $status = $order->getStatus();
        $itemCount = 0;
        $shippingCountryId = '';
        $items = $order->getAllItems();
        $orderEmailData = array();
        foreach ($items as $item) {

            $getProductId = $item->getProductId();
            $createdAt = $item->getCreatedAt();
            $is_buyer_confirmation_date = '0000-00-00 00:00:00';
            $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
            $products = Mage::helper('marketplace/marketplace')->getProductInfo($getProductId);
            $products_new = Mage::getModel('catalog/product')->load($item->getProductId());
            if($products_new->getTypeId() == "configurable")
                {
                   $options = $item->getProductOptions() ;

                   $sku = $options['simple_sku'] ;
                   $getProductId = Mage::getModel('catalog/product')->getIdBySku($sku);
               }
            else{
                $getProductId = $item->getProductId();
            }



            $sellerId = $products->getSellerId();
            $productType = $products->getTypeID();
            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');
            if ($sellerShippingEnabled == 1 && $productType == 'simple') {
                /**
                 * Get the product national shipping price
                 * and international shipping price
                 * and shipping country
                 */
                $nationalShippingPrice = $products->getNationalShippingPrice();
                $internationalShippingPrice = $products->getInternationalShippingPrice();
                $sellerDefaultCountry = $products->getDefaultCountry();
                $shippingCountryId = $order->getShippingAddress()->getCountry();
            }
            /**
             * Check seller id has been set
             */
            if ($sellerId) {
                $orderPrice = $item->getBasePrice() * $item->getQtyOrdered();
                $productAmt = $item->getBasePrice();
                $productQty = $item->getQtyOrdered();
                if ($paymentMethodCode == 'paypaladaptive') {
                    $credited = 1;
                } else {
                    $credited = 0;
                }

                /* check for checking if payment method is credit card or cash on delivery and if method
                 is cash on delivery then it will check if the order status of previous orders are complete or not
                 no need to disable sending email from admin*/

                /*-----------------Adding Failed Delivery Value--------------------------*/
                    Mage::helper('progos_ordersedit')->getFailedDeliveryStatus($order);
                /*----------------------------------------------------------------------*/

                if ($paymentMethodCode == 'ccsave' || $paymentMethodCode == 'telrpayments_cc' || $paymentMethodCode == 'telrtransparent' ) {
                    $is_buyer_confirmation = 'No';
                    $is_buyer_confirmation_date = $createdAt;
                    $item_order_status = 'pending_seller';
                    $data = 0;

                    /*---------------------------Updating comment----------------------*/
                    /*
                     * Elabelz-2057
                     */
                    /*$product = Mage::getModel("catalog/product")->load($getProductId);
                    $comment = "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> by buyer";
                    $order->addStatusHistoryComment($comment, $order->getStatus())
                      ->setIsVisibleOnFront(0)
                      ->setIsCustomerNotified(0);
                    $order->save();*/
                    /*---------------------------Updating comment----------------------*/

                } elseif ($paymentMethodCode == 'cashondelivery' || $paymentMethodCode == 'msp_cashondelivery') {

                    $counter = 0;
                    $orders = Mage::getModel('sales/order')->getCollection();
                    $orders->addFieldToSelect('*');
                    $orders->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getId());
                    foreach ($orders as $ord) {
                        if ($ord->getStatus() == "complete"):
                            $counter = $counter + 1;
                        endif;
                    }
                    if ($counter != 0) {
                        $is_buyer_confirmation = 'Yes';
                        $is_buyer_confirmation_yes = "Accepted";
                        $item_order_status = 'pending_seller';
                        $data = 0;

                    /*---------------------------Updating comment----------------------*/
                    $product = Mage::getModel("catalog/product")->load($getProductId);
                    $comment = "Order Item having SKU '{$product->getSku()}' is <strong>accepted</strong> by buyer";
                    $order->addStatusHistoryComment($comment, $order->getStatus())
                        ->setIsVisibleOnFront(0)
                        ->setIsCustomerNotified(0);
                    $order->save();
                    /*---------------------------Updating comment----------------------*/

                    } else {
                        $is_buyer_confirmation = 'No';
                        $item_order_status = 'pending';
                        $tel = $order->getBillingAddress()->getTelephone();
                        $nexmoActivated = Mage::getStoreConfig('marketplace/nexmo/nexmo_activated');
                        $nexmoStores =  Mage::getStoreConfig('marketplace/nexmo/nexmo_stores');
                        $nexmoStores = explode(',', $nexmoStores);
                        $currentStore =  Mage::app()->getStore()->getCode();
                        #check nexmo sms module is active or not and check on which store its enabled
                        $reservedOrderId = $order->getIncrementId();
                        $mdlEmapi = Mage::getModel('restmob/quote_index');
                        $id = $mdlEmapi->getIdByReserveId($reservedOrderId);
                        if ($nexmoActivated == 1 && in_array($currentStore, $nexmoStores) && !$id) {
                            # code...
                            $data = Mage::helper('marketplace/marketplace')->sendVerify($tel);
                            $data = $data['request_id'];

                        }
                        //$data = 0;

                    }
                }


                // $orderPriceToCalculateCommision = $products_new->getPrice() * $item->getQtyOrdered();
                $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry, $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);
                /**
                 * Getting seller commission percent
                 */
                $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);

                // Product price after deducting
                // $productAmt = $products_new->getPrice() - $commissionFee;

                $sellerAmount = $shippingPrice - $commissionFee;

                if($item->getProductType() == 'simple')
                {
                    $getProductId = $item->getProductId();
                }

                /**
                 * Storing commission information in database table
                 */

                if ($commissionFee > 0 || $sellerAmount > 0) {
                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($getProductId);
                    $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);

                    if ($parent_product->getSpecialPrice()) {
                        $orderPrice_sp = $parent_product->getSpecialPrice() * $item->getQtyOrdered();
                        $orderPrice_base = $parent_product->getPrice() * $item->getQtyOrdered();

                        $commissionFee = $orderPrice_sp * ($percentperproduct / 100);
                        $sellerAmount = $orderPrice_sp - $commissionFee;
                    } else {
                        $orderPrice_base = $item->getBasePrice() * $item->getQtyOrdered();
                        $commissionFee = $orderPrice_base * ($percentperproduct / 100);
                        $sellerAmount = $shippingPrice - $commissionFee;
                    }

                    $commissionDataArr = array(
                        'seller_id' => $sellerId,
                        'product_id' => $getProductId,
                        'product_qty' => $productQty,
                        'product_amt' => $productAmt,
                        'commission_fee' => $commissionFee,
                        'seller_amount' => $sellerAmount,
                        'order_id' => $order->getId(),
                        'increment_id' => $order->getIncrementId(),
                        'order_total' => $grandTotal,
                        'order_status' => $status,
                        'credited' => $credited,
                        'customer_id' => $getCustomerId,
                        'status' => 1,
                        'created_at' => $createdAt,
                        'payment_method' => $paymentMethodCode,
                        'item_order_status' => $item_order_status,
                        'is_buyer_confirmation' => $is_buyer_confirmation,
                        'sms_verify_code' => $data,
                        'is_buyer_confirmation_date'=> $is_buyer_confirmation_date,
                        'is_seller_confirmation_date'=> '0000-00-00 00:00:00',
                        'shipped_from_elabelz_date'=> '0000-00-00 00:00:00',
                        'successful_non_refundable_date'=> '0000-00-00 00:00:00',
                        'commission_percentage' => $sellerCollection ['commission']
                    );

                    $commissionId = $this->storeCommissionData($commissionDataArr);
                    $orderEmailData [$itemCount] ['seller_id'] = $sellerId;
                    $orderEmailData [$itemCount] ['product_qty'] = $productQty;
                    $orderEmailData [$itemCount] ['product_id'] = $getProductId;
                    $orderEmailData [$itemCount] ['product_amt'] = $productAmt;
                    $orderEmailData [$itemCount] ['commission_fee'] = $commissionFee;
                    $orderEmailData [$itemCount] ['seller_amount'] = $sellerAmount;
                    $orderEmailData [$itemCount] ['increment_id'] = $order->getIncrementId();
                    $orderEmailData [$itemCount] ['customer_firstname'] = $order->getCustomerFirstname();
                    $orderEmailData [$itemCount] ['customer_email'] = $order->getCustomerEmail();
                    $orderEmailData [$itemCount] ['product_id_simple'] = $getProductId;
                    $orderEmailData [$itemCount] ['is_buyer_confirmation'] = $is_buyer_confirmation_yes;
                    $orderEmailData [$itemCount] ['itemCount'] = $itemCount;
                    $itemCount = $itemCount + 1;
                }
            }
            // if ($paymentMethodCode == 'paypaladaptive') {
            //     $this->updateCommissionPA($commissionId);
            // }
        }

        if ($paymentMethodCode == 'ccsave' || $paymentMethodCode == 'telrpayments_cc' || $paymentMethodCode == 'telrtransparent' ) {
            /*if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
                $this->sendOrderEmail($orderEmailData);
            }*/
        } elseif ($paymentMethodCode == 'cashondelivery' || $paymentMethodCode == 'msp_cashondelivery') {
            $counter = 0;
            $orders = Mage::getModel('sales/order')->getCollection();
            $orders->addFieldToSelect('*');
            $orders->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getId());
            foreach ($orders as $ord) {
                if ($ord->getStatus() == "complete"){
                    $counter = $counter + 1;
                }
            }
            if ($counter != 0) {
                if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {

                    $this->sendOrderEmail($orderEmailData);
                }
            }
        }

        //$this->enqueueDataToFareye($observer);

    }

    /**
     * Update commission while uisng PayPal Adaptive
     */
    public function updateCommissionPA($commissionId) {
        /**
         * If payment method is paypal adaptive, then commission table(credited to seller) and transaction table(amout paid to seller) will be updated
         */
        $model = Mage::helper('marketplace/transaction')->getCommissionInfo($commissionId);

        /**
         * Get the Commission Fee of admin
         */
        $adminCommission = $model->getCommissionFee();
        /**
         * Get the seller amount
         */
        $sellerCommission = $model->getSellerAmount();
        /**
         * Get the Seller Id
         */
        $sellerId = $model->getSellerId();
        /**
         * Get commission & order id
         */
        $commissionId = $model->getId();
        $orderId = $model->getOrderId();

        /**
         * transaction collection to update the payment information
         */
        $transaction = Mage::helper('marketplace/transaction')->getTransactionInfo($commissionId);
        $transactionIdVal = $transaction->getId();
        /**
         * check transaction id is empty
         * if so update the transaction data like
         * commission id
         * seller id
         * seller commission
         * admin commission
         * order id in a variable
         * and save the transaction data
         */
        if (empty($transactionIdVal)) {
            $Data = array(
                'commission_id' => $commissionId,
                'seller_id' => $sellerId,
                'seller_commission' => $sellerCommission,
                'admin_commission' => $adminCommission,
                'order_id' => $orderId,
                'received_status' => 0
            );
            Mage::helper('marketplace/transaction')->saveTransactionData($Data);
        }
        /**
         * Update the database after admin paid seller amount
         */
        $transactions = Mage::getModel('marketplace/transaction')->getCollection()->addFieldToFilter('seller_id', $sellerId)->addFieldToSelect('id')->addFieldToFilter('paid', 0);
        foreach ($transactions as $transaction) {
            $transactionIdVal = $transaction->getId();
            /**
             * Check the transaction id is not empty
             */
            if (!empty($transactionIdVal)) {
                /**
                 * Update the transaction Details
                 */
                Mage::helper('marketplace/transaction')->updateTransactionData($transactionIdVal);
            }
        }
    }

    /**
     * Save seller commission data in database and get the commission id
     *
     * Commission information passed to update in database
     *
     * @param array $commissionDataArr
     *            This function will return the commission id of the last saved data
     * @return int
     */
    public function storeCommissionData($commissionDataArr) {
        $model = Mage::getModel('marketplace/commission');
        $duplicateProduct = $model->getCollection()
            ->addFieldToSelect('order_id')
            ->addFieldToFilter('order_id',$commissionDataArr['order_id'])
            ->addFieldToFilter('product_id',$commissionDataArr['product_id'])
            ->addFieldToFilter('seller_id',$commissionDataArr['seller_id']);
        if($duplicateProduct->getSize()){
            return false;
        }
        else {
            $model->setData($commissionDataArr);
            $model->save();
            return $model->getId();
        }
    }

    /**
     * Send Order Email to seller
     *
     * Passed the order information to send with email
     *
     * @param array $orderEmailData
     *
     * @return void
     */
    public function sendOrderEmail($orderEmailData) {


        $sellerIds = array();
        $displayProductCommission = Mage::helper('marketplace')->__('Seller Commission Fee');
        $displaySellerAmount = Mage::helper('marketplace')->__('Seller Amount');
        $displayProductImage = Mage::helper('marketplace')->__('Product Image');
        $displayProductName = Mage::helper('marketplace')->__('Product Name');
        $displayProductQty = Mage::helper('marketplace')->__('Product QTY');
        $displayProductAmt = Mage::helper('marketplace')->__('Product Amount');
        $displayProductStatus = Mage::helper('marketplace')->__('Product Status');
        foreach ($orderEmailData as $data) {
            if (!in_array($data ['seller_id'], $sellerIds)) {
                $sellerIds [] = $data ['seller_id'];
            }
        }

        foreach ($sellerIds as $key => $id) {
            $totalProductAmt = $totalCommissionFee = $totalSellerAmt = 0;
            $productDetails = '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
            $productDetails .= '<thead><tr>';
            $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductImage . '</th><th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductName . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductQty . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductAmt . '</th>';
            $productDetails .= '<th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductCommission . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displaySellerAmount . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductStatus . '</th></tr></thead>';
            $productDetails .= '<tbody bgcolor="#F6F6F6">';
            $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
            foreach ($orderEmailData as $data) {
                if ($id == $data ['seller_id']) {
                    $sellerId = $data ['seller_id'];
                    $incrementId = $data ['increment_id'];
                    $groupId = Mage::helper('marketplace')->getGroupId();
                    $productId = $data ['product_id'];
                    $simpleProductId = $data ['product_id_simple'];
                    $product = Mage::helper('marketplace/marketplace')->getProductInfo($productId);
                    $productGroupId = $product->getGroupId();
                    $productName = $product->getName();
                    $productamt = $data ['product_amt'] * $data ['product_qty'];
                    $productStatus = $data['is_buyer_confirmation'];
                    //$product_img = $product->getImageUrl();

                    $content = "order Details\n Seller Id ".$sellerId."\n";
                    $content .= "Order Increment Id ".$incrementId."\n";
                    $content .= "Product Name ".$productName."\n";
                    $content .= "Product Amount ".$productamt."\n";


                    $products_new = Mage::getModel('catalog/product')->load($productId);
                    $product_img = $products_new->getImageUrl();
                    if($products_new->getTypeId() == "configurable"){
                    $products_new = Mage::getModel('catalog/product')->load($productId);
                         if($products_new->getSupplierSku() != ""){
                                $product_sku = $products_new->getSupplierSku();
                            }else{
                                $product_sku = $products_new->getSku();
                            }
                    $product_img = $products_new->getImageUrl();
                    $product_color = $products_new->getAttributeText('color');
                    $product_size = $products_new->getAttributeText('size');
                    }
                    else{
                            if($products_new->getSupplierSku() != ""){
                                $product_sku = $products_new->getSupplierSku();
                            }else{
                                $product_sku = $products_new->getSku();
                            }
                        $product_color = $products_new->getAttributeText('color');
                        $product_size = $products_new->getAttributeText('size');
                    }
                    if ($product_sku) {
                            $product_sku = "<br/>SKU:&nbsp;" . $product_sku;
                        }else{
                            $product_sku="";
                        }

                        if ($product_size) {
                            $product_size = "<br/>Size:&nbsp;" . $product_size;
                        }else{
                            $product_size="";
                        }

                        if ($product_color) {
                           $product_color = "<br/>Color:&nbsp;" . $product_color;

                        }else{
                            $product_color="";
                        }

                    //removed echo
                    $productOptions = $product_sku.$product_size.$product_color;
                    $productDetails .= '<tr>';
                    $productDetails .= '<td align="cenetr" valign="center" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;"><img src="' . $product_img . '" width="70px"></td><td align="left" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $productName . '<br/>'. $productOptions.'</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . round($data ['product_qty']) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($productamt, 2) . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($data ['commission_fee'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($data ['seller_amount'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $productStatus . '</td>';

                    $totalProductAmt = $totalProductAmt + $productamt;
                    $totalCommissionFee = $totalCommissionFee + $data ['commission_fee'];
                    $totalSellerAmt = $totalSellerAmt + $data ['seller_amount'];
                    $orderTotal = $data ['order_total'];

                    $customerEmail = $data ['customer_email'];
                    $customerFirstname = $data ['customer_firstname'];
                    $productDetails .= '</tr>';
                }
            }
            $productDetails .= '</tbody><tfoot>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Seller Commision Fee</td><td align="center" style="padding:3px 9px"><span>' . $currencySymbol . round($totalCommissionFee, 2) . '</span></td></tr>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Total Amount</td><td align="center" style="padding:3px 9px"><span>' . $currencySymbol . round($totalProductAmt, 2) . '</span></td></tr>';
            $productDetails .= '</tfoot></table>';

            if ($groupId == $productGroupId) {
                $templateId = (int) Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification_template_selection');

                $adminEmailId = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
                $toName = Mage::getStoreConfig("trans_email/ident_$adminEmailId/name");
                $toMailId = Mage::getStoreConfig("trans_email/ident_$adminEmailId/email");

                if ($templateId) {
                    $emailTemplate = Mage::helper('marketplace/marketplace')->loadEmailTemplate($templateId);
                } else {
                    $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_admin_approval_seller_registration_sales_notification_template_selection');
                }
                $customer = Mage::helper('marketplace/marketplace')->loadCustomerData($sellerId);
                $sellerName = $customer->getName();
                $sellerEmail = $customer->getEmail();
                $recipient = $toMailId;
                $sellerStore = Mage::app()->getStore()->getName();
                $recipientSeller = $sellerEmail;
                $emailTemplate->setSenderName($toName);
                $emailTemplate->setSenderEmail($toMailId);
                $emailTemplateVariablesValue = (array(
                    'ownername' => $toName,
                    'productdetails' => $productDetails,
                    'order_id' => $incrementId,
                    'seller_store' => $sellerStore,
                    'customer_email' => $customerEmail,
                    'customer_firstname' => $customerFirstname
                ));
                $emailTemplate->setDesignConfig(array(
                    'area' => 'frontend'
                ));
                $emailTemplate->getProcessedTemplate($emailTemplateVariablesValue);
                /**
                 * Send email to the recipient
                 */
                $emailTemplate->send($recipient, $toName, $emailTemplateVariablesValue);
                $emailTemplateVariablesValue = (array(
                    'ownername' => $sellerName,
                    'productdetails' => $productDetails,
                    'order_id' => $incrementId,
                    'seller_store' => $sellerStore,
                    'customer_email' => $customerEmail,
                    'customer_firstname' => $customerFirstname
                ));

                $content .= "Seller Name ".$sellerName."\n";
                $content .= "Seller Name ".$sellerName."\n";
                $content .= "Seller Email ".$sellerEmail."\n";
                $content .= "Seller Store ".$sellerStore."\n";
                $content .= "Email Recepient ".$recipientSeller."\n";

                $emailTemplate->send($recipientSeller, $sellerName, $emailTemplateVariablesValue);
            }
        }

    }

    /**
     * Setting Cron job to enable/disable vacation mode by seller
     *
     * @return void
     */
    public function eventVacationMode() {
        $currentDate = date("Y-m-d ", Mage::getModel('core/date')->timestamp(time()));
        $vacationInfo = Mage::getModel('marketplace/vacationmode')->getCollection()->addFieldToSelect('*');
        foreach ($vacationInfo as $_vacationInfo) {
            /**
             * Get Vacation info from date
             */
            $fromDate = $_vacationInfo ['date_from'];
            /**
             * Get Vacation info to date
             */
            $toDate = $_vacationInfo ['date_to'];
            /**
             * Get Seller id of each vacation
             */
            $sellerId = $_vacationInfo ['seller_id'];
            /**
             * Get product disabled status of each vacation product
             */
            $productStatus = $_vacationInfo ['product_disabled'];
            $product = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('seller_id', $sellerId);
            $productId = array();
            foreach ($product as $_product) {
                $productId [] = $_product->getId();
            }
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            /**
             * Confirm the vacation is active by checking
             * current date is greater than or equal to vacation from-date
             * and current date is less than or equal to vacation to-date
             * and vacation product status is equal to zero
             * if so update the product status to 2
             */
            if ($currentDate >= $fromDate && $currentDate <= $toDate && $productStatus == 0) {
                foreach ($productId as $_productId) {
                    Mage::getModel('catalog/product')->load($_productId)->setStatus(2)->save();
                }
            }
            /**
             * check the current date is less than vacation from-date
             * and current date is greater than vacation to-date
             * if so update the product status to 1
             */
            if ($currentDate < $fromDate || $currentDate > $toDate) {
                foreach ($productId as $_productId) {
                    Mage::getModel('catalog/product')->load($_productId)->setStatus(1)->save();
                }
            }
        }
    }

    /**
     * Change status to disable for deleted seller products.
     *
     * @param object $observer
     */
    public function customerdelete($observer) {
        $customer = $observer->getCustomer();
        $productCollections = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('seller_id', $customer->getId());
        foreach ($productCollections as $product) {
            $productId = $product->getEntityId();
            Mage::helper('marketplace/general')->changeAssignProductId($productId);
        }
    }

    /**
     * Restrict seller product to buy themself
     *
     * @param object $observer
     */
    public function addToCartEvent($observer) {
        /**
         * check the observer event gull action name is equal to the checkout cart add
         */
        if ($observer->getEvent()->getControllerAction()->getFullActionName() == 'checkout_cart_add') {
            /**
             * Assign the customer id as empty
             */
            $customerId = '';
            /**
             * Check the customer is currently logged in
             * if so then get the customer data
             */
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $customerId = $customerData->getId();
            }

            $product = Mage::getModel('catalog/product')->load(Mage::app()->getRequest()->getParam('product', 0));
            /**
             * Check the product id is not set
             * or cutomer id is empty
             * if so return
             */
            if (!$product->getId() || empty($customerId)) {
                return;
            }
            $sellerId = $product->getSellerId();
            /**
             * check the the current customer id is equal to the seller id
             */
            if ($sellerId == $customerId) {

                $assignProductId = $product->getAssignProductId();
                if (!empty($assignProductId)) {
                    $productUrl = Mage::getModel('catalog/product')->load($assignProductId)->getProductUrl();
                } else {
                    $productUrl = $product->getProductUrl();
                }

                $msg = Mage::helper('marketplace')->__("Seller can't buy their own product.");
                Mage::getSingleton('core/session')->addError($msg);

                Mage::app()->getFrontController()->getResponse()->setRedirect($productUrl);
                Mage::app()->getResponse()->sendResponse();

                $controller = $observer->getControllerAction();
                $controller->getRequest()->setDispatched(true);
                $controller->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
            }
        }
        return $this;
    }



    public function itemCancelAfter($observer){
        $order = $observer->getEvent()->getItem()->getData();
        $product_id = $order['product_id'];

        $sku = $order['sku'];
        $product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
        $order_status = $order['status'];

        $order_id = $order['order_id'];
        $qty_canceled = $order['qty_canceled'];
        $qty_ordered = $order['qty_ordered'];
        $total_qty = $qty_ordered - $qty_canceled;
        if($total_qty == 0){
            $data = array('order_status'=> $order_status , 'product_qty'=>0, 'seller_amount'=>0 , 'commission_fee' => 0,'item_order_status'=>'canceled');
        }
        else{
        $data = array('order_status'=> $order_status , 'product_qty'=>0, 'seller_amount'=>0 , 'commission_fee' => 0);
        }



        $products = Mage::getModel('marketplace/commission')->getCollection();
        $products->addFieldToSelect('*');
        $products->addFieldToFilter('order_id',$order_id);
        $products->addFieldToFilter('product_id',$product_id);
        if($products){
        foreach($products as $product):
        $id =  $product->getId();
        endforeach;
        $model = mage::getmodel('marketplace/commission')->load($id);
        $model->addData($data);

        try {
            $model->setId($id)->save();

        } catch (Exception $e){
           echo $e->getMessage();
       }
   }

    }

    /**
     * @param $observer
     * Used to save data to Fareye table, so that can be later on used by cron to push in Fareye
     */
    public function enqueueDataToFareye($observer) {
        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $orderIds = $observer->getEvent()->getOrderIds();
        $order = Mage::getModel('sales/order')->load($orderIds [0]);
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $getCustomerId = $customer->getId();
        $grandTotal = $order->getGrandTotal();
        $status = $order->getStatus();
        $itemCount = 0;
        $shippingCountryId = '';
        $items = $order->getAllItems();
        $orderEmailData = array();
        foreach ($items as $item) {
            $getProductId = $item->getProductId();
            //check if the product do not have parents, means it's not associated
            if (!empty(Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($getProductId))) {
                continue;
            }
            //process only if the unique id already not exist
            $queue = Mage::getModel('marketplace/fareyedataqueue')
                    ->getCollection()
                    ->addFieldToFilter('product_unique_id', $order->getIncrementId() . '-' . $getProductId)
                    ->getFirstItem();
            //process only if the unique id already not exist
            if (!empty($queue->getProductUniqueId())) {
                continue;
            }
            $getProductId = $item->getProductId();
            $createdAt = $item->getCreatedAt();
            $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
            $products = Mage::helper('marketplace/marketplace')->getProductInfo($getProductId);
            $sellerId = $products->getSellerId();
            $productType = $products->getTypeID();

            /**
             *  Getting order item selected options
             */
            $prodOptionsInfo = $item->getProductOptions();
            $options = "";
            foreach ($prodOptionsInfo['attributes_info'] as $key => $value) {
                $options .= $value['label'] . ":" . $value['value'] . ",";
            }
            $prodOptions = rtrim($options, ",");


            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');
            if ($sellerShippingEnabled == 1 && $productType == 'simple') {
                /**
                 * Get the product national shipping price
                 * and international shipping price
                 * and shipping country
                 */
                $nationalShippingPrice = $products->getNationalShippingPrice();
                $internationalShippingPrice = $products->getInternationalShippingPrice();
                $sellerDefaultCountry = $products->getDefaultCountry();
                $shippingCountryId = $order->getShippingAddress()->getCountry();
            }
            /**
             * Check seller id has been set
             */
            if ($sellerId) {
                $sellerProfile = Mage::getModel('marketplace/sellerprofile')->load($sellerId, 'seller_id');
                $storeName = $sellerProfile->getStoreTitle();
                //$seller = Mage::getModel('customer/customer')->load($sellerId);

                $orderPrice = $item->getPrice() * $item->getQtyOrdered();
                $productQty = $item->getQtyOrdered();
                $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry, $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);

                //confirm customer if he completed an order before or have paid via credit card
                $common = Mage::helper('marketplace/common');
                $customer_confirmed = "no";
                if ($common->paidViaCreditCard($paymentMethodCode) || $common->haveCompletedOrdersBefore($getCustomerId)) {
                    $customer_confirmed = "yes";
                }

                /**
                 * Storing information in database table
                 */
                $address = $order->getShippingAddress()->getStreet1();
                $address = (empty($address)) ? "Not specified" : $address;
                $address_line2 = $order->getShippingAddress()->getStreet2();
                $address_line2 = (empty($address_line2)) ? "Nill" : $address_line2;

                $data = array(
                    'merchant_code' => $sellerId,
                    'order_id' => $order->getIncrementId(),
                    'product_unique_id' => $order->getIncrementId() . '-' . $getProductId,
                    'customer_name' => $order->getCustomerFirstname(),
                    'customer_address' => $address,
                    'address_line2' => $address_line2,
                    'customer_contact_number' => $order->getShippingAddress()->getTelephone(),
                    'customer_confirmed' => $customer_confirmed, //yes in case of already confirmed cust
                    'mode_of_payment' => $paymentMethodCode,
                    'merchant_name' => $storeName,
                    'merchant_id' => $sellerId,
                    'merchant_address' => $sellerProfile->getSupplierAddress(),
                    'merchant_contact_number' => $sellerProfile->getContact(), //in case empty pass "0"
                    'amount_to_be_collected' => $grandTotal,
                    'product_id' => $getProductId,
                    'product_qty' => $productQty,
                    'product_name' => $item->getName(),
                    'other_product_options' => $prodOptions, //data will be passed in json data format like Do:Do,To:Do
                    'total_items' => floor($order->getData('total_qty_ordered')),
                    'item_amount' => $item->getPrice(),
                    'item_discount' => $item->getDiscountAmount(),
                    'item_delivery_charge' => $order->getGrandTotal() - $order->getShippingAmount(),
                        //'product_description' => $item->getShortDescription()
                );
                try {
                    $model = Mage::getModel('marketplace/fareyedataqueue')->setData($data);
                    $model->save();
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }
        }
    }

    /**
     * Push data in queue to fareye
     */
    public function pushToFareye() {
        Mage::getModel('marketplace/fareyedataqueue')->pushToFareye();
    }

    /**
     * Setup titles for the stores
     */
    public function setupStoreTitles() {
        Mage::getSingleton('marketplace/sellerprofile')->setSellerStoreTitles();
    }

    /**
     * Setting Cron job to enable/disable vacation mode by seller
     *
     * @return void
     */
    public function eventNotifySeller() {
        /**
         * Date calculation, Current date and Previous date
         */
        $currentDate = date("Y-m-d ", Mage::getModel('core/date')->timestamp(time()));
        $previousDate = date("Y-m-d ", Mage::getModel('core/date')->timestamp(strtotime($currentDate . ' -1 day')));

        // Getting all seller ID's whose products updated in from yesterday

        $_sellerCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('updated_at', array(
                    'from' => $previousDate,
                    'date' => true,
                ))
                ->addAttributeToSelect('seller_id')
                ->addAttributeToFilter('status', array('eq' => 1))// added publish stauts filter
                ->addAttributeToFilter('seller_product_status', array('eq' => 1023))// added Seller product status
                ->groupByAttribute('seller_id')
                ->load();

        foreach ($_sellerCollection as $_seller) {
            $sellerId = $_seller->getSellerId();
            $products = "";

            //Loading seller product collection
            $_productCollection = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToFilter('updated_at', array(
                        'from' => $previousDate,
                        'date' => true,
                    ))
                    ->addAttributeToFilter('seller_id', $_seller->getSellerId())
                    ->addAttributeToFilter('status', array('eq' => 1))// added publish stauts filter
                    ->addAttributeToFilter('seller_product_status', array('eq' => 1023))// added Seller product status
                    ->addAttributeToSelect('*')
                    ->load();

            $i = 1;
            foreach ($_productCollection as $_product) {
                $small_image = Mage::helper('catalog/image')->init($_product, 'small_image')->resize(51, 68);
                $products .= '<tr>';
                $products .= '<td style="text-align:center; margin:0 0 10px; vertical-align:middle; padding:0 0 10px;">' . $i . '</td>';
                $products .= '<td style="text-align:center; padding:0 0 20px;"><img src=' . $small_image . ' alt=' . $_product->getName() . ' /></td>';
                $products .= '<td style="text-align:center; vertical-align:middle; padding:0 0 10px;">' . $_product->getName() . '</td>';
                $products .= '<td style="text-align:center; vertical-align:middle; padding:0 0 10px;">' . $_product->getSku() . '</td>';
                $products .= '<td style="text-align:center; vertical-align:middle; padding:0 0 10px;">' . $_product->getAttributeText('status') . '</td>';
                $products .= '<td style="text-align:center; vertical-align:middle; padding:0 0 10px;">' . $_product->getAttributeText('seller_product_status') . '</td>';
                $products .= '<td style="text-align:center; vertical-align:middle; padding:0 0 10px;;">' . $_product->getPrice() . '</td>';
                $products .= '</tr>';

                $i++;
            }
            $templateId = 9;
            $adminEmailId = Mage::getStoreConfig('marketplace/marketplace/admin_email_id');
            $emailTemplate = Mage::getModel('core/email_template')->load($templateId);

            //Getting seller information

            $customer = Mage::helper('marketplace/marketplace')->loadCustomerData($sellerId);
            $sellerName = $customer->getName();
            $sellerEmail = $customer->getEmail();
            $sellerStore = Mage::app()->getStore()->getName();
            $storeId = Mage::app()->getStore()->getStoreId();

            // Setting up default sender information
            $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name', $storeId));
            $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email', $storeId));

            // Setting email variables
            $emailTemplateVariablesValue = (array(
                'sellername' => $sellerName,
                'products' => $products,
                'seller_store' => $sellerStore,
            ));

            $emailTemplate->getProcessedTemplate($emailTemplateVariablesValue);

            /**
             * Send email to the seller
             */
            $emailTemplate->send($sellerEmail, $sellerName, $emailTemplateVariablesValue);
        }
    }

}

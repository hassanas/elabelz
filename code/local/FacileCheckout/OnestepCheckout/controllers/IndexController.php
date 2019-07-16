<?php

class FacileCheckout_OnestepCheckout_IndexController extends Mage_Checkout_Controller_Action
{

    private $_current_layout = null;

    protected $_sectionUpdateFunctions = array(
        'review' => '_getReviewHtml',
        'shipping-method' => '_getShippingMethodsHtml',
        'payment-method' => '_getPaymentMethodsHtml',
        'payment-storecredit' => '_getStoreCreditHtml'
    );

    public function preDispatch()
    {
        parent::preDispatch();
        $this->_preDispatchValidateCustomer();
        return $this;
    }

    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    protected function _expireAjax()
    {
        if (! $this->getOnestepcheckout()
            ->getQuote()
            ->hasItems() || $this->getOnestepcheckout()
            ->getQuote()
            ->getHasError() || $this->getOnestepcheckout()
            ->getQuote()
            ->getIsMultiShipping()) {
            //commented this to fix log on onestepcheckout which lead to empty cart issue
            //log out happen as there're items in cart with quantity issue @RT
            //$this->_ajaxRedirectResponse();
            return true;
        }
        $action = $this->getRequest()->getActionName();
        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true) && ! in_array($action, array(
            'index',
            'progress'
        ))) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    protected function _getUpdatedLayout()
    {
        $this->_initLayoutMessages('checkout/session');
        if ($this->_current_layout === null) {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();
            $update->load('onestepcheckout_index_updatecheckout');

            $layout->generateXml();
            $layout->generateBlocks();
            $this->_current_layout = $layout;
        }

        return $this->_current_layout;
    }

    protected function _getShippingMethodsHtml()
    {
        $layout = $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.shipping.method')->toHtml();
    }

    protected function _getPaymentMethodsHtml()
    {
        $layout = $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.payment.method')->toHtml();
    }

    protected function _getCouponDiscountHtml()
    {
        $layout = $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.cart.coupon')->toHtml();
    }

    protected function _getReviewHtml()
    {
        $layout = $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.review')->toHtml();
    }

    protected function _getStoreCreditHtml()
    {
        $layout = $this->_getUpdatedLayout();
        $html="";
        if($layout->getBlock('aw_storecredit.additional')) {
            $html = $layout->getBlock('aw_storecredit.additional')->toHtml();
        }
        return $html;
    }

    public function getOnestepcheckout()
    {
        return Mage::getSingleton('onestepcheckout/type_geo');
    }

    public function indexAction()
    {
        if (! Mage::helper('onestepcheckout')->isOnestepCheckoutEnabled()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The one page checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }

        $quote = $this->getOnestepcheckout()->getQuote();
        if (! $quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (! $quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        //complete url for redirect after login
        $loginback = Mage::getUrl('onestepcheckout/index/index', array('_secure' => true));
        Mage::getSingleton('customer/session')->setBeforeAuthUrl($loginback);
        Mage::getSingleton('core/session')->setLoginBackUrl($loginback);
        
        $this->getOnestepcheckout()
            ->initDefaultData()
            ->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $title = Mage::getStoreConfig('onestepcheckout/general/title');
        $this->getLayout()
            ->getBlock('head')
            ->setTitle($title);
        $this->renderLayout();
    }

    /*
     * function for CC order
     */
    public function successecoAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'ecoprocessor', array('template' => 'ecoprocessor/redirect.phtml'));
        $this->getLayout()->getBlock('head')->append($block);
        $this->renderLayout();
    }

    /*
     * Success and failure function for CC
     */
    public function successcallbackAction()
    {
        $reservedOrderId = $_POST['cart_id'];
        $telrReferenceId = $_POST['auth_tranref'];
        $mdlEco = Mage::getModel('ecoprocessor/quote_index');
        $id = $mdlEco->getIdByReserveId($reservedOrderId);
        $mdlEco->load($id)->setPaymentStatus(1)->setTelrReferenceId($telrReferenceId)->save();
    }

    public function cancelcallbackAction()
    {
        $reservedOrderId = $this->getRequest()->getParam('oid');
        $mdlEco = Mage::getModel('ecoprocessor/quote_index');
        $id = $mdlEco->getIdByReserveId($reservedOrderId);
        if ($id) {
            $mEco = $mdlEco->load($id);
            if($mEco->getStoreCredit() == 1){
                $quoteId = $mEco->getQid();
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                if ($storeCredit) {
                    $storeBalance = $storeCredit->getBalance();
                    $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                    foreach($quoteStorecredits as $quoteStorecredit){
                        $linkId = $quoteStorecredit->getLinkId();
                        $baseStorecreditAmount = $quoteStorecredit->baseStorecreditAmount;
                        $storeCredit
                            ->setBalance($storeBalance + $baseStorecreditAmount)
                            ->save()
                        ;
                        $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
                        if (null !== $quoteStoreCreditModel) {
                            $quoteStoreCreditModel
                                ->setBaseStorecreditAmount(0)
                                ->setStorecreditAmount(0)
                                ->save()
                            ;
                        }
                    }
                }
            }
        }

    }
    //end Success and failure function for CC

    public function successAction()
    {
        if(Mage::getStoreConfig('api/ecoprocessor/enableNewCheckout')) {
            $notallowed = $this->getRequest()->getParam('na', false);
            if ($notallowed) {
                $this->_redirect('checkout/onepage');
                return;
            }
            $session = Mage::getSingleton('onestepcheckout/type_geo')->getCheckout();
            $session->clear();
            $session = Mage::getSingleton('core/session');
            if (!$session->getLastSuccessQuoteId()) {
                $this->_redirect('checkout/cart');
                return;
            }
            $lastQuoteId = $session->getLastQuoteId();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($lastQuoteId);
            $quote->setIsActive(0)->save();
            $lastOrderId = $session->getLastOrderId();
            $lastRecurringProfiles = $session->getLastRecurringProfileIds();
            if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
                $this->_redirect('checkout/cart');
                return;
            }

            $this->loadLayout();
            $newCustomer = $session->getData("newCustomer"); // will be "your value"
            $url = $session->getData("url");
            if ($newCustomer == 1) :
                Mage::getSingleton('checkout/session')->addSuccess(Mage::helper('customer')->__('Account confirmation is required. Please, check your email for confirmation link. <a href="%s">Click here</a> to resend confirmation email.', $url));
            endif;
            $this->_initLayoutMessages('checkout/session');
            $this->clearcart();
            $this->renderLayout();
            $session = Mage::getSingleton('core/session');
            $session->clear();
        }else {
            $notallowed = $this->getRequest()->getParam('na', false);
            if ($notallowed) {
                $this->_redirect('checkout/onepage');
                return;
            }

            $session = $this->getOnestepcheckout()->getCheckout();
            if (!$session->getLastSuccessQuoteId()) {
                $this->_redirect('checkout/cart');
                return;
            }
            $lastQuoteId = $session->getLastQuoteId();
            $lastOrderId = $session->getLastOrderId();
            $lastRecurringProfiles = $session->getLastRecurringProfileIds();

            /*
             * if ($_SESSION['git_wrap'] == 'Yes') // Gift Wrap Condition
             * {
             * $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
             * //$query="UPDATE `sales_flat_order` SET `giftwrap` = '1',`heardus` = 'HEAR US FILED' WHERE `entity_id` ='$lastOrderId'";
             * $query="UPDATE `sales_flat_order` SET `giftwrap` = '1' WHERE `entity_id` ='$lastOrderId'";
             * $writeConnection->query($query);
             * }
             * // Gift Wrap Condition End
             */

            if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
                $this->_redirect('checkout/cart');
                return;
            }

            $session->clear();
            $this->loadLayout();
            $session = Mage::getSingleton("core/session");
            $newCustomer = $session->getData("newCustomer"); // will be "your value"
            $url = $session->getData("url");
            if ($newCustomer == 1) :
                Mage::getSingleton('checkout/session')->addSuccess(Mage::helper('customer')->__('Account confirmation is required. Please, check your email for confirmation link. <a href="%s">Click here</a> to resend confirmation email.', $url));
            endif;

            $this->_initLayoutMessages('checkout/session');
            Mage::dispatchEvent('checkout_onepage_controller_success_action', array(
                'order_ids' => array(
                    $lastOrderId
                )
            ));
            $this->clearcart();
            $this->renderLayout();
        }
    }

    public function clearcart()
    {
        $quoteID = Mage::getSingleton("checkout/session")->getQuote()->getId();

        if ($quoteID) {
            try {
                $quote = Mage::getModel("sales/quote")->load($quoteID);
                $quote->setIsActive(0);
                $quote->save();
                // $quote->delete();
            } catch (Exception $e) {
                throw $e;
            }
        }

        // $checkout_cart = Mage::getSingleton('checkout/cart');
        // $items = $checkout_cart->getItems();
        // foreach ($items as $item)
        // {
        // $itemId = $item->getItemId();
        // try
        // {
        // $checkout_cart->removeItem($itemId);
        // }catch (Exception $e) {
        // echo $this->__('Cannot remove the item.');
        // }
        // }
        // $checkout_cart->save();
        // Mage::getSingleton('checkout/cart')->truncate()->save();
        // Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        // Mage::getSingleton('core/session', array('name'=>'frontend'));

        Mage::getSingleton('checkout/session')->clear();
    }

    public function failureAction()
    {
        $lastQuoteId = $this->getOnestepcheckout()
            ->getCheckout()
            ->getLastQuoteId();
        $lastOrderId = $this->getOnestepcheckout()
            ->getCheckout()
            ->getLastOrderId();

        if (! $lastQuoteId || ! $lastOrderId) {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function getAddressAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $addressId = $this->getRequest()->getParam('address', false);
        if ($addressId) {
            $address = $this->getOnestepcheckout()->getAddress($addressId);

            if (Mage::getSingleton('customer/session')->getCustomer()->getId() == $address->getCustomerId()) {
                $this->getResponse()->setHeader('Content-type', 'application/x-json');
                $this->getResponse()->setBody($address->toJson());
            } else {
                $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            }
        }
    }

    public function updateCheckoutAction()
    {
        if ($this->_expireAjax() || ! $this->getRequest()->isPost()) {
            return;
        }
        $paymentData = $this->getRequest()->getPost('payment', array());
        if(isset($paymentData['cc_number']))
        {
            $paymentData['cc_number'] = str_replace(' ', '', $paymentData['cc_number']);
            Mage::getSingleton('checkout/session')->setPaymentData($paymentData);
        }
        $result = [];
        $quote = $this->getOnestepcheckout()->getQuote();
        /* * ********* DISCOUNT CODES ********* */
        $couponData = $this->getRequest()->getPost('coupon', array());
        $processCoupon = $this->getRequest()->getPost('process_coupon', false);

        /* if BIN no coupon code is valid*/
        if(Mage::getStoreConfig("marketplace/binno/binnoenable")) {
            $binnos = explode(",", Mage::getStoreConfig("marketplace/binno/binno20"));
            $bin_no = substr($paymentData['cc_number'], 0, 6);
            if($couponData['code'] == "BIN.20") {
                if (!in_array($bin_no, $binnos)) {//if bin no in credit card does not matc array
                    $couponData['code'] = 'BIN20';
                    $processCoupon = 1;
                }
                elseif(in_array($bin_no, $binnos) && $paymentData['method'] !== "telrtransparent"){ //if payment method is not teletransparent
                    $couponData['code'] = 'BIN20';
                    $processCoupon = 1;
                }
            }
            elseif(in_array($bin_no, $binnos) && $paymentData['method'] == "telrtransparent" ){ //if bin.20 removed and payment method is again changed
                $couponData['code'] = 'BIN.20';
                $processCoupon = 1;

            }
        }

        $couponChanged = false;
        if ($couponData && $processCoupon) {
            if (! empty($couponData['remove'])) {
                $couponData['code'] = '';
            }

            $oldCouponCode = $quote->getCouponCode();
            if ($oldCouponCode != $couponData['code']) {
                try {

                    $quote->setCouponCode(strlen($couponData['code']) ? $couponData['code'] : '');
                    $this->getRequest()->setPost('payment-method', true);
                    $this->getRequest()->setPost('shipping-method', true);
                    $this->getRequest()->setPost('payment-storecredit', true);
                    if ($couponData['code']) {
                        $couponChanged = true;
                    } else {
                        $couponChanged = true;
                        Mage::getSingleton('checkout/session')->addSuccess($this->__('Coupon code was canceled.'));
                    }
                } catch (Mage_Core_Exception $e) {
                    $couponChanged = true;
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                } catch (Exception $e) {
                    $couponChanged = true;
                    Mage::getSingleton('checkout/session')->addError($this->__('Cannot apply the coupon code.'));
                }
            }
        }
        $bill_data = $this->getRequest()->getPost('billing', array());
        $bill_data = $this->_filterPostData($bill_data);
        $bill_addr_id = $this->getRequest()->getPost('billing_address_id', false);
        $ship_updated = false;

        if ($this->_checkChangedAddress($bill_data, 'Billing', $bill_addr_id) || $this->getRequest()->getPost('payment-method', false)) {
            if (isset($bill_data['email'])) {
                $bill_data['email'] = trim($bill_data['email']);
            }

            $bill_result = $this->getOnestepcheckout()->saveBilling($bill_data, $bill_addr_id, false);

            if (! isset($bill_result['error'])) {
                $pmnt_data = $this->getRequest()->getPost('payment', array());
                $this->getOnestepcheckout()->usePayment(isset($pmnt_data['method']) ? $pmnt_data['method'] : null);

                $result['update_section']['payment-method'] = $this->_getPaymentMethodsHtml();
                $result['update_section']['payment-storecredit'] = $this->_getStoreCreditHtml();

                if (isset($bill_data['use_for_shipping']) && $bill_data['use_for_shipping'] == 1 && ! $this->getOnestepcheckout()
                    ->getQuote()
                    ->isVirtual()) {
                    $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                    $result['duplicateBillingInfo'] = 'true';

                    $ship_updated = true;
                }
                //adding param to make review call as the price is involved and will affect total
                $result['reload_totals'] = 'true';
            } else {
                $result['error_messages'] = $bill_result['message'];
            }
        }

        $ship_data = $this->getRequest()->getPost('shipping', array());
        $ship_addr_id = $this->getRequest()->getPost('shipping_address_id', false);
        $ship_method = $this->getRequest()->getPost('shipping_method', false);

        if (! $ship_updated && ! $this->getOnestepcheckout()
            ->getQuote()
            ->isVirtual()) {
            if ($this->_checkChangedAddress($ship_data, 'Shipping', $ship_addr_id) || $ship_method) {
                $ship_result = $this->getOnestepcheckout()->saveShipping($ship_data, $ship_addr_id, false);

                if (! isset($ship_result['error'])) {
                    $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                }
            }
        }

        $check_shipping_diff = false;

        // check how many shipping methods exist
        $rates = Mage::getModel('sales/quote_address_rate')->getCollection()
            ->setAddressFilter($this->getOnestepcheckout()
            ->getQuote()
            ->getShippingAddress()
            ->getId())
            ->toArray();
        if (count($rates['items']) == 1) {
            if ($rates['items'][0]['code'] != $ship_method) {
                $check_shipping_diff = true;

                $result['reload_totals'] = 'true';
            }
        } else
            $check_shipping_diff = true;

        // get prev shipping method
        if ($check_shipping_diff) {
            $shipping = $this->getOnestepcheckout()
                ->getQuote()
                ->getShippingAddress();
            $shippingMethod_before = $shipping->getShippingMethod();
        }

        $this->getOnestepcheckout()->useShipping($ship_method);

        $this->getOnestepcheckout()
            ->getQuote()
            ->collectTotals()
            ->save();

        if ($check_shipping_diff) {
            $shipping = $this->getOnestepcheckout()
                ->getQuote()
                ->getShippingAddress();
            $shippingMethod_after = $shipping->getShippingMethod();

            if ($shippingMethod_before != $shippingMethod_after) {
                $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                $result['reload_totals'] = 'true';
            } else
                unset($result['reload_totals']);
        }
        // /////////////

        $result['update_section']['review'] = $this->_getReviewHtml();

        /* * ********* DISCOUNT CODES ********* */
        if ($couponChanged) {
            if (isset($couponData['code']) && $couponData['code']!='') {
                if ($couponData['code'] == $quote->getCouponCode()) {
                    Mage::getSingleton('checkout/session')->addSuccess($this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponData['code'])));
                } else {
                    Mage::getSingleton('checkout/session')->addError($this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponData['code'])));
                }
            }
            $method = str_replace(' ', '', ucwords(str_replace('-', ' ', 'coupon-discount')));
            $result['update_section']['coupon-discount'] = $this->{'_get' . $method . 'Html'}();
        }
        /* * *********************************
       
         */

        if ($quote->getAwStorecreditAmountUsed()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $result['quoteBaseGrandTotal'] = (float)$quote->getBaseGrandTotal();
            if (!$customer->getId()) {
                $result['balance'] = 0;
            } else {
                $result['balance'] = Mage::helper('core')->currency($this->_getStorecreditModel($customer)->getBalance(), false, false);
            }
            $result['formattedBalance'] = Mage::helper('core')->currency($this->_getStorecreditModel($customer)->getBalance(), true, false);
            $result['baseBalance'] = (float)$this->_getStorecreditModel($customer)->getBalance();
            $result['baseStorecreditAmountUsed'] = (float)$quote->getBaseAwStorecreditAmountUsed();
            $result['isStorecreditSubstracted'] = $quote->getBaseAwStorecreditAmountUsed();
        }
        //if coupon code is applied in order
        if($quote->getCouponCode()){
            $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
            if($totals["discount"]) {
                $totalDiscount = $totals["discount"]->getValue();
            }
            if($quote->getSubtotal() !=0 && $totalDiscount!=0 ) {
                if (($quote->getSubtotal() + $totalDiscount) < 0.001) {
                    $result['couponAppliedtoZero'] = "true";
                    $quote->setBaseGrandTotal(0)
                        ->setGrandTotal(0);
                    $quote->save();
                }
            }
        }
        //country id must be in billing data or else unset next reload total call @RT
        if (!isset($bill_data['country_id']) && $bill_data['country_id'] == '') { Mage::log('here',null,'checkout.log');
            unset($result['reload_totals']);
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _getStorecreditModel($customer) {
        $sc = Mage::getModel('aw_storecredit/storecredit');

        if ($customer->getId()) {
            $sc->loadByCustomerId($customer->getId());
        }
        return $sc;
    }

    public function forgotpasswordAction()
    {
        $session = Mage::getSingleton('customer/session');

        if ($this->_expireAjax() || $session->isLoggedIn()) {
            return;
        }

        $email = $this->getRequest()->getPost('email');
        $result = array(
            'success' => false
        );

        if (! $email) {
            $result['error'] = Mage::helper('customer')->__('Please enter your email.');
        } else {
            if (! Zend_Validate::is($email, 'EmailAddress')) {
                $session->setForgottenEmail($email);
                $result['error'] = Mage::helper('checkout')->__('Invalid email address.');
            } else {
                $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()
                    ->getWebsiteId())
                    ->loadByEmail($email);
                if (! $customer->getId()) {
                    $session->setForgottenEmail($email);
                    $result['error'] = Mage::helper('customer')->__('This email address was not found in our records.');
                } else {
                    try {
                        $new_pass = $customer->generatePassword();
                        $customer->changePassword($new_pass, false);
                        $customer->sendPasswordReminderEmail();
                        $result['success'] = true;
                        $result['message'] = Mage::helper('customer')->__('A new password has been sent.');
                    } catch (Exception $e) {
                        $result['error'] = $e->getMessage();
                    }
                }
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function loginAction()
    {
        // $loginChk = $this->getRequest()->getPost('login');
        // $storeCode = Mage::helper("marketplace")->switchCustomerStore($loginChk["username"], false, true);
        // if($storeCode != false){
        // $url = "/".$storeCode."/customer/account/login/?msg=".urlencode($this->__("We have switched your store, please login here."));
        // echo json_encode(['success' => true, 'redirect' => $url]);
        // return;
        // }
        $session = Mage::getSingleton('customer/session');
        if ($this->_expireAjax() || $session->isLoggedIn()) {
            return;
        }

        $result = array(
            'success' => false
        );

        $customer = Mage::getModel('customer/customer');
        $websiteId = Mage::app()->getWebsite()->getId();
        $current_store_id = Mage::app()->getStore()->getId();
        if ($websiteId) {
            $customer->setWebsiteId($websiteId);
        }
        $login_data = $this->getRequest()->getPost('login');
        if (! empty($login_data['username']) || ! empty($login_data['password'])) {
            $customer->loadByEmail($login_data['username']);
            if ($customer->getId()) {
                $customer_store_id = $customer->getStoreId();
            } else {
                $customer_store_id = $current_store_id;
            }
        }
        $_store_1 = Mage::getModel('core/store')->load($current_store_id);
        $current_store_code = $_store_1->getCode();

        $_store_2 = Mage::getModel('core/store')->load($customer_store_id);
        $customer_store_code = $_store_2->getCode();

        if ($this->getRequest()->isPost()) {
            $login_data = $this->getRequest()->getPost('login');

            if (empty($login_data['username']) || empty($login_data['password'])) {
                $result['error'] = Mage::helper('customer')->__('Login and password are required.');
                // } elseif ($current_store_code != $customer_store_code && $getParams=='') {
                // $result['error'] = $this->__("The email address is not registered with this store, please switch to your registered store.");
                // $session->setUsername($login_data['username']);
            } else {
                try {
                    $session->login($login_data['username'], $login_data['password']);
                    $result['success'] = true;
                    $result['redirect'] = Mage::getUrl('*/*/index');
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $message = Mage::helper('customer')->__('Email is not confirmed. <a href="%s">Resend confirmation email.</a>', Mage::helper('customer')->getEmailConfirmationUrl($login_data['username']));
                            break;
                        default:
                            if ($customer->getId()) {
                                $message = $e->getMessage();
                            } else {
                                $message = $this->__("You are not registered with this store.");
                            }
                    }
                    $result['error'] = $message;
                    $session->setUsername($login_data['username']);
                }
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $_SESSION['git_wrap'] = $_POST['giftwrap']; // DEEPAK

        //code to handle order submission in cron table
        if(Mage::getStoreConfig('api/ecoprocessor/enableNewCheckout')) {
            $result = array();
            $paymentStatus = 0;
            $pmnt_data = $this->getRequest()->getPost('payment', false);

            $billingAddress = $this->getRequest()->getPost('billing');
            $shippingAddress = $this->getRequest()->getPost('shipping');
            $paymentCountry = $billingAddress['country_id'];
            $storeId = $store = Mage::app()->getStore()->getId();
            $checkCountry = false;

            $quoteId = Mage::getSingleton('checkout/session')->getQuote()->getId();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            $store_credit = 0;
            if ($quote->getCustomerId()) {
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                $storeBalance = $storeCredit->getBalance();
                if ($storeCredit && $storeBalance > 0) {
                    $store_credit = 1;
                }
            }
            if(trim($pmnt_data['method']) == "" || trim($pmnt_data['method']) == null){
                if($store_credit){
                    $pmnt_data['method'] = "free";
                }else {
                    $result['error_messages'] = Mage::helper('checkout')->__('Unable to set Payment Method.');
                    $result['error'] = true;
                    $result['success'] = false;
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }else if($pmnt_data['method'] == 'msp_cashondelivery'){
                $allowSpecific = Mage::getStoreConfig('payment/msp_cashondelivery/allowspecific', $storeId);
                if ($allowSpecific) {
                    $allowedCountries = Mage::getStoreConfig('payment/msp_cashondelivery/specificcountry', $storeId);
                    $checkCountry = true;
                }
            }else{
                $allowSpecific = Mage::getStoreConfig('payment/telrtransparent/allowspecific', $storeId);
                if ($allowSpecific) {
                    $allowedCountries = Mage::getStoreConfig('payment/telrtransparent/specificcountry', $storeId);
                    $checkCountry = true;
                }
            }
            if($checkCountry) {
                $allowedCountriesArr = explode(',', $allowedCountries);
                if (!in_array($paymentCountry, $allowedCountriesArr)) {
                    $result['error_messages'] = Mage::helper('checkout')->__('Unable to set Payment Method.');
                    $result['error'] = true;
                    $result['success'] = false;
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }
            if ($pmnt_data['method'] != 'telrtransparent') {
                $paymentStatus = 1;
            }
            if ($pmnt_data['method'] == 'telrtransparent') {
                $dataArr = array(
                    'cc_type' => $pmnt_data['cc_type'],
                    'cc_number' => $pmnt_data['cc_number'],
                    'cc_exy' => $pmnt_data['cc_exp_year'],
                    'cc_exm' => $pmnt_data['cc_exp_month'],
                    'cc_cvv' => $pmnt_data['cc_cid']

                );
                $mdlPayment = Mage::getModel('telrtransparent/standard');
                $mdlPayment->validateOnly($dataArr);
            }
            $quote->reserveOrderId()->save();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            $mdlEcoprocessor = Mage::getModel('ecoprocessor/quote_index');
            $res = $quote->getReservedOrderId();
            $id = $mdlEcoprocessor->getIdByReserveId($res);

            /*
             * Code for store credit
             */
            $storecreditInfo = array();
            if($store_credit) {
                $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                if ($storeCredit) {
                    $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                    if($quoteStorecredits){
                        foreach($quoteStorecredits as $quoteStorecredit){
                            $subtract = $quoteStorecredit->getBaseStorecreditAmount();
                        }
                    }
                    $storeBalance = $storeCredit->getBalance();
                    $storeCredit
                        ->setBalance($storeBalance - $subtract)
                        ->save()
                    ;
                    $storecreditInfo['id'] = $storeCredit->getId();
                    $storecreditInfo['total'] = ($storeBalance - $subtract);
                    $storecreditInfo['spent'] = "-".$subtract;
                }
            }
            if($quote->getCustomerEmail()){
                $billingAddress['email'] = $quote->getCustomerEmail();
                $shippingAddress['email'] = $quote->getCustomerEmail();
            }
            if ($id) {
                $mdlEcoprocessor->load($id);
                $mdlEcoprocessor->setQid($quoteId);
                $mdlEcoprocessor->setPayemntMethod($pmnt_data['method']);
                $mdlEcoprocessor->setShippingMethod($this->getRequest()->getPost('shipping_method'));
                $mdlEcoprocessor->setPaymentStatus($paymentStatus);
                if ($quote->getItemsCount() == 0) {
                    $mdlEcoprocessor->setStatus(2);
                } else {
                    $mdlEcoprocessor->setStatus(0);
                }
                $mdlEcoprocessor->setReservedOrderId($res);
                $mdlEcoprocessor->setCcCid($pmnt_data['cc_cid']);
                $mdlEcoprocessor->setCcOwner("");
                $mdlEcoprocessor->setCcNumber($pmnt_data['cc_number']);
                $mdlEcoprocessor->setCcType($pmnt_data['cc_type']);
                $mdlEcoprocessor->setCcExpYear($pmnt_data['cc_exp_year']);
                $mdlEcoprocessor->setCcExpMonth($pmnt_data['cc_exp_month']);
                $mdlEcoprocessor->setBillingAddress(json_encode($billingAddress));
                $mdlEcoprocessor->setShippingAddress(json_encode($shippingAddress));
                $mdlEcoprocessor->setStoreCredit($store_credit);
                $mdlEcoprocessor->setScInfo(json_encode($storecreditInfo));
                $mdlEcoprocessor->setCreatedAt(Varien_Date::now());
                $mdlEcoprocessor->save();
            } else {
                $mdlEcoprocessor->setQid($quoteId);
                $mdlEcoprocessor->setQid($quoteId);
                $mdlEcoprocessor->setPayemntMethod($pmnt_data['method']);
                $mdlEcoprocessor->setShippingMethod($this->getRequest()->getPost('shipping_method'));
                $mdlEcoprocessor->setPaymentStatus($paymentStatus);
                if ($quote->getItemsCount() == 0) {
                    $mdlEcoprocessor->setStatus(2);
                } else {
                    $mdlEcoprocessor->setStatus(0);
                }
                $mdlEcoprocessor->setReservedOrderId($res);
                $mdlEcoprocessor->setCcCid($pmnt_data['cc_cid']);
                $mdlEcoprocessor->setCcOwner("");
                $mdlEcoprocessor->setCcNumber($pmnt_data['cc_number']);
                $mdlEcoprocessor->setCcType($pmnt_data['cc_type']);
                $mdlEcoprocessor->setCcExpYear($pmnt_data['cc_exp_year']);
                $mdlEcoprocessor->setCcExpMonth($pmnt_data['cc_exp_month']);
                $mdlEcoprocessor->setBillingAddress(json_encode($billingAddress));
                $mdlEcoprocessor->setShippingAddress(json_encode($shippingAddress));
                $mdlEcoprocessor->setStoreCredit($store_credit);
                $mdlEcoprocessor->setScInfo(json_encode($storecreditInfo));
                $mdlEcoprocessor->setCreatedAt(Varien_Date::now());
                $mdlEcoprocessor->save();
            }
            $session = Mage::getSingleton('core/session');
            $session->setLastQuoteId($quote->getId());
            $session->setLastOrderId($res);
            $session->setLastRealOrderId($res);
            $session->setLastSuccessQuoteId($quote->getId());
            if ($pmnt_data['method'] == 'telrtransparent') {
                $result['redirect'] = Mage::getUrl('onestepcheckout/index/successeco', array('_secure' => true));
            }else{
                $result['redirect'] = Mage::getUrl('onestepcheckout/index/success', array('_secure' => true));
            }
            $result['success'] = true;
            $result['error'] = false;
            $result['order_created'] = true;
        }else {
            $result = array();
            try {

                $bill_data = $this->_filterPostData($this->getRequest()
                    ->getPost('billing', array()));
                $result = $this->getOnestepcheckout()->saveBilling($bill_data, $this->getRequest()
                    ->getPost('billing_address_id', false), true, true);

                if ($result) {
                    $result['error_messages'] = $result['message'];
                    $result['error'] = true;
                    $result['success'] = false;

                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }

                if ((!$bill_data['use_for_shipping'] || !isset($bill_data['use_for_shipping'])) && !$this->getOnestepcheckout()
                        ->getQuote()
                        ->isVirtual()
                ) {
                    $result = $this->getOnestepcheckout()->saveShipping($this->_filterPostData($this->getRequest()
                        ->getPost('shipping', array())), $this->getRequest()
                        ->getPost('shipping_address_id', false), true, true);

                    if ($result) {
                        $result['error_messages'] = $result['message'];
                        $result['error'] = true;
                        $result['success'] = false;
                        Mage::log(json_encode($result), null, 'osc.log');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }
                }

                $agreements = Mage::helper('onestepcheckout')->getAgreeIds();
                if ($agreements) {
                    $post_agree = array_keys($this->getRequest()->getPost('agreement', array()));
                    $is_different = array_diff($agreements, $post_agree);
                    if ($is_different) {
                        $result['error_messages'] = Mage::helper('checkout')->__('Please agree to all the terms and conditions.');
                        $result['error'] = true;
                        $result['success'] = false;
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }
                }

                $result = $this->_saveOrderPurchase();

                if ($result && !isset($result['redirect'])) {
                    $result['error_messages'] = $result['error'];
                }

                if (!isset($result['error'])) {

                    Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array(
                        'request' => $this->getRequest(),
                        'quote' => $this->getOnestepcheckout()->getQuote()
                    ));
                    $this->_subscribeNews();
                }

                Mage::getSingleton('customer/session')->setOrderCustomerComment($this->getRequest()
                    ->getPost('order-comment'));

                if (!isset($result['redirect']) && !isset($result['error'])) {

                    $pmnt_data = $this->getRequest()->getPost('payment', false);
                    // Mage::log(json_encode($pmnt_data), null, 'osc.log');
                    if ($pmnt_data)
                        $this->getOnestepcheckout()
                            ->getQuote()
                            ->getPayment()
                            ->importData($pmnt_data);

                    $this->getOnestepcheckout()->saveOrder();

                    $redirectUrl = $this->getOnestepcheckout()
                        ->getCheckout()
                        ->getRedirectUrl();

                    $result['success'] = true;
                    $result['error'] = false;
                    $result['order_created'] = true;

                }

            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnestepcheckout()
                    ->getQuote(), $e->getMessage());

                $result['error_messages'] = $e->getMessage();
                $result['error'] = true;
                $result['success'] = false;

                $goto_section = $this->getOnestepcheckout()
                    ->getCheckout()
                    ->getGotoSection();
                if ($goto_section) {
                    $this->getOnestepcheckout()
                        ->getCheckout()
                        ->setGotoSection(null);
                    $result['goto_section'] = $goto_section;
                }

                $update_section = $this->getOnestepcheckout()
                    ->getCheckout()
                    ->getUpdateSection();
                if ($update_section) {
                    if (isset($this->_sectionUpdateFunctions[$update_section])) {
                        $layout = $this->_getUpdatedLayout();

                        $updateSectionFunction = $this->_sectionUpdateFunctions[$update_section];
                        $result['update_section'] = array(
                            'name' => $update_section,
                            'html' => $this->$updateSectionFunction()
                        );
                    }
                    $this->getOnestepcheckout()
                        ->getCheckout()
                        ->setUpdateSection(null);
                }

                $this->getOnestepcheckout()
                    ->getQuote()
                    ->save();
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnestepcheckout()
                    ->getQuote(), $e->getMessage());
                $result['error_messages'] = Mage::helper('checkout')->__('There was an error processing your order. Please contact support or try again later.');
                $result['error'] = true;
                $result['success'] = false;

                $this->getOnestepcheckout()
                    ->getQuote()
                    ->save();
            }

            if (isset($redirectUrl)) {
                $result['redirect'] = $redirectUrl;
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

        // }
    }

    protected function _saveOrderPurchase()
    {
        $result = array();

        try {
            $pmnt_data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnestepcheckout()->savePayment($pmnt_data);

            // print_r("pmnt_data".$result['fields']);exit;

            $redirectUrl = $this->getOnestepcheckout()
                ->getQuote()
                ->getPayment()
                ->getCheckoutRedirectUrl();
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = Mage::helper('checkout')->__('Unable to set Payment Method.');
        }
        return $result;
    }

    protected function _subscribeNews()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('newsletter')) {
            $customerSession = Mage::getSingleton('customer/session');

            if ($customerSession->isLoggedIn())
                $email = $customerSession->getCustomer()->getEmail();
            else {
                $bill_data = $this->getRequest()->getPost('billing');
                $email = $bill_data['email'];
            }

            try {
                if (! $customerSession->isLoggedIn() && Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1)
                    Mage::throwException(Mage::helper('newsletter')->__('Sorry, subscription for guests is not allowed. Please <a href="%s">register</a>.', Mage::getUrl('customer/account/create/')));

                $ownerId = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()
                    ->getWebsiteId())
                    ->loadByEmail($email)
                    ->getId();

                if ($ownerId !== null && $ownerId != $customerSession->getId())
                    Mage::throwException(Mage::helper('newsletter')->__('Sorry, you are trying to subscribe email assigned to another user.'));

                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
            } catch (Mage_Core_Exception $e) {} catch (Exception $e) {}
        }
    }

    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array(
            'dob'
        ));
        return $data;
    }

    protected function _checkChangedAddress($data, $addr_type = 'Billing', $addr_id = false)
    {
        $method = "get{$addr_type}Address";
        $address = $this->getOnestepcheckout()
            ->getQuote()
            ->{$method}();

        if (! $addr_id) {
            if (($address->getRegionId() != $data['region_id']) || ($address->getPostcode() != $data['postcode']) || ($address->getCountryId() != $data['country_id']))
                return true;
            else
                return false;
        } else {
            if ($addr_id != $address->getCustomerAddressId())
                return true;
            else
                return false;
        }
    }
    /**
     *  This function will be called after checkoutdotcom form submitted
     */
    public function checkoutdotcomAction(){


        $orderId    = $_POST['orderId'];
        $quoteId    = $_POST['quoteId'];
        $store_credit = $_POST['store_credit'];
        $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
        $currency = $quote->getQuoteCurrencyCode();

        //select shipping charges
        $mdlRestmob = Mage::getModel('ecoprocessor/quote_index');
        $id = $mdlRestmob->getIdByReserveId($orderId);
        $_order = $mdlRestmob->load($id);
        $billingInfo = $quote->getBillingAddress();
        $shippingInfo = $quote->getShippingAddress();
        $shippingCountry = $shippingInfo->getCountryId();

        $baseShippingFee = $this->getShipmentCharges($shippingCountry, $quote->getBaseSubtotal());
        $shippingFee = Mage::helper('directory')->currencyConvert($baseShippingFee, "AED", $currency);
        $price = $quote->getSubtotalWithDiscount() + $shippingFee;
        /*
         * Code for store credit
         */
        $storeBalance = 0;
        $baseStoreBalance = 0;
        if ($store_credit) {
            $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
            if (count($quoteStorecredits) > 0) {
                foreach ($quoteStorecredits as $quoteStorecredit) {
                    $_baseStorecreditAmount = $quoteStorecredit->getBaseStorecreditAmount();
                    $baseStoreBalance = Mage::helper('directory')->currencyConvert($_baseStorecreditAmount, "AED", $currency);
                    $storeBalance = Mage::helper('directory')->currencyConvert($_baseStorecreditAmount, "AED", $currency);
                }
            }
        }
        if (trim($currency) != "AED") {
            $shippingFee = $baseShippingFee;
            $price = $quote->getBaseSubtotalWithDiscount() + $shippingFee;
            $price = Mage::helper('directory')->currencyConvert($price, "AED", $currency);
            $price = number_format(($price - $storeBalance), 2, '.', '');
        } else {
            $price = number_format(($price - $baseStoreBalance), 2, '.', '');
        }

        $email = $billingInfo->getEmail();

        $price = Mage::helper('telrtransparent')->getCheckoutDotComPrice($price,$currency);
        $url            = Mage::helper('telrtransparent/config')->getCheckoutDotComApiUrl();
        $privateKey     = Mage::helper('telrtransparent/config')->getCheckoutDotComPrivateKey($currency);

        $ch = curl_init($url);
        $header = array(
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: '.$privateKey
        );

        $firstname = $shippingInfo->getFirstName();
        $lastname = $shippingInfo->getLastName();
        $street1 = @$shippingInfo->getStreet(1);
        $street2 = @$shippingInfo->getStreet(2);
        $street3 = '';
        $city = $shippingInfo->getCity();
        $region = $shippingInfo->getRegion();
        $postcode = $shippingInfo->getPostcode();
        $country = $shippingInfo->getCountryId();
        $telephone = $shippingInfo->getTelephone();


        $customerName = $firstname." ".$lastname;
        $customerIPAddr = Mage::helper('core/http')->getRemoteAddr(false);
        $data_string = '{  
          "trackId": "' . $orderId . '",
          "customerIp": "' . $customerIPAddr . '",
          "autoCapture": "Y",
          "autoCapTime": "48",
          "email": "'.$email.'",
          "customerName": "'.$customerName.'",
          "value": "'.$price.'",
          "currency": "'.$currency.'",
		  "chargeMode": 1,
          "cardToken": "'.$_POST['ckoCardToken'].'",
          "shippingDetails": {
            "addressLine1": "'.$street1.'",
            "addressLine2": "'.$street2.'",
            "postcode": "'.$postcode.'",
            "country": "'.$country.'",
            "city": "'.$city.'",
            "state": "'.$region.'",
            "phone": {
                 "number": "'.$telephone.'"
             }
          },
          "billingDetails": {
            "addressLine1": "'.@$billingInfo->getStreet(1).'",
            "addressLine2": "'.@$billingInfo->getStreet(2).'",
            "postcode": "'.$billingInfo->getPostCode().'",
            "country": "'.$billingInfo->getCountryId().'",
            "city": "'.$billingInfo->getCity().'",
            "state": "'.$billingInfo->getRegion().'",
            "phone": {
                 "number": "'.$billingInfo->getTelephone().'"
             }
          }
		  }';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($output,true);
        if(isset($response['errorCode']) || $response['status'] == 'Declined'){
            $mdlRestmob = Mage::getModel('ecoprocessor/quote_index');
            $id = $mdlRestmob->getIdByReserveId($orderId);
            if ($id) {
                $mRestmob = $mdlRestmob->load($id);
                if($mRestmob->getStoreCredit() == 1){
                    $quoteId = $mRestmob->getQid();
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $storeCredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($quote->getCustomerId());
                    if ($storeCredit) {
                        $storeBalance = $storeCredit->getBalance();
                        $quoteStorecredits = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($quoteId);
                        foreach($quoteStorecredits as $quoteStorecredit){
                            $linkId = $quoteStorecredit->getLinkId();
                            $baseStorecreditAmount = $quoteStorecredit->baseStorecreditAmount;
                            $storeCredit
                                ->setBalance($storeBalance + $baseStorecreditAmount)
                                ->save()
                            ;
                            $quoteStoreCreditModel = Mage::getModel('aw_storecredit/quote_storecredit')->load($linkId);
                            if (null !== $quoteStoreCreditModel) {
                                $quoteStoreCreditModel
                                    ->setBaseStorecreditAmount(0)
                                    ->setStorecreditAmount(0)
                                    ->save()
                                ;
                            }
                        }
                    }
                }
                //update order with transaction id and response code in comments @RT
                if (Mage::helper('onestepcheckout')->isCheckoutResToOrder()) {
                    $mRestmob->setTelrReferenceId($response['id'])
                        ->setTelrRespCode($response['responseCode'])
                        ->save();
                }
            }
            $this->_redirect('onestepcheckout/index/index/cc/back', array('_secure' => true));
        }
        else{
            $mdlEmapi = Mage::getModel('ecoprocessor/quote_index');
            $id = $mdlEmapi->getIdByReserveId($orderId);
            $mdlEmapi->load($id)->setPaymentStatus(1)->save();
            //update order with transaction id and response code in comments @RT
            if (Mage::helper('onestepcheckout')->isCheckoutResToOrder()) {
                //set in table against telr_reference_id which will add in order comment when cron run
                $mdlEmapi->setTelrReferenceId($response['id'])
                    ->setTelrRespCode($response['responseCode'])
                    ->save();
            }
            $this->_redirect('onestepcheckout/index/success', array('_secure' => true));
        }
    }
    public function getShipmentCharges($shippingCountry, $orderSubtotal)
    {
        switch ($shippingCountry) {
            case "AE":
                $price = 0;
                break;
            default:
            {
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='" . $shippingCountry . "'";
                $rows = $connection->fetchAll($sql);
                if(!$rows){
                    $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='0'";
                    $rows = $connection->fetchAll($sql);
                }
                $i = 0;
                if(sizeof($rows) == 1){
                    $price = $rows[0]['price'];
                }else {
                    foreach ($rows as $row) {
                        if ($i == 0) {
                            $minArr[] = $row['condition_value'];
                            $minArr[] = $row['price'];
                        } else {
                            $maxArr[] = $row['condition_value'];
                            $maxArr[] = $row['price'];
                        }
                        $i++;
                    }
                    if ($orderSubtotal > $minArr[0] && $orderSubtotal < $maxArr[0]) {
                        $price = $minArr[1];
                    } else {
                        $price = $maxArr[1];
                    }
                }
            }
        }
        return $price;
    }
}

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
 * This file contains seller login/ registration, seller profile page functionality
 */
class Apptha_Marketplace_SellerController extends Mage_Core_Controller_Front_Action
{

    /**
     * Action list where need check enabled cookie
     *
     * @var array
     */
    protected $_cookieCheckActions = array(
        'loginPost',
        'createpost'
    );

    /**
     * Check whether VAT ID validation is enabled
     *
     * @param Mage_Core_Model_Store|string|int $store
     * @return bool
     */
    protected function _isVatValidationEnabled($store = null)
    {
        return Mage::helper('customer/address')->isVatValidationEnabled($store);
    }

    /**
     * Function to display seller login and registration page
     *
     * Checking license key and seller logged in or not
     *
     * @return void
     */
    public function indexAction()
    {
        /**
         * Check Marketplacekey not empty
         */
        if (Mage::helper('marketplace')->checkMarketplaceKey() != '') {
            /** get marketplace key */
            $msg = Mage::helper('marketplace')->checkMarketplaceKey();
            /** Set body msg */
            Mage::app()->getResponse()->setBody($msg);
            return;
        } else {
            /**
             * Redirecting user to login page if he is not loggedin
             */
            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                /** add error message */
                Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
                $this->_redirect('*/*/login');
                return;
            }
            /** to load layout */
            $this->loadLayout();
            /** to render layout */
            $this->renderLayout();
        }
    }

    /**
     * Function to display login page
     *
     * Checking license key and seller logged in or not
     *
     * @return void
     */
    public function loginAction()
    {
        /**
         * Check Marketplacekey not empty
         */
        if (Mage::helper('marketplace')->checkMarketplaceKey() != '') {
            /** get marketplace key */
            $msg = Mage::helper('marketplace')->checkMarketplaceKey();
            /** Set body msg */
            Mage::app()->getResponse()->setBody($msg);
            return;
        } else {
            /**
             * Check whether the user logged in or not
             */
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                /** redirect to dashboard */
                $this->_redirect('marketplace/seller/dashboard');
                return;
            }
            /** to load and render layout */
            $this->loadLayout();
            $this->renderLayout();
        }
    }

    /**
     * Function to post login page data's
     *
     * Checking username and password for log in and redirect to seller/customer account
     *
     * @return void
     */
    public function loginPostAction()
    {
        /**
         * Check the user login and redirect to them to seller dashboard if they loggedin
         */
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('marketplace/seller/dashboard');
            return;
        }
        $session = Mage::getSingleton('customer/session');

        if ($this->getRequest()->isPost()) {
            /** get login data */
            $login = $this->getRequest()->getPost('login');
            /**
             * Check username and password are not empty
             */
            $isLoginCredentials = Mage::getModel('marketplace/seller')->isLoginCredentials($login);
            if ($isLoginCredentials == 1) {
                try {
					$session->login($login ['username'], $login ['password']);
                    $this->checkWhetherSellerOrNot($login);
                    $this->sendToWelcomeCustomer($session);
					//exit('try');
                } catch (Mage_Core_Exception $e) {
                    $message = $e->getMessage();
                    /** add error message */
                    //$session->addError($message);
                    /** set username in session */
                    $session->setUsername($login ['username']);
                }
            } else {
                /**
                 * Display error message on username or password is empty
                 */
                $session->addError($this->__('Login and password are must.'));
            }
        }
        $this->_redirect('marketplace/seller/dashboard');
    }

    /**
     * Send to welcome customer
     *
     * @param array $session
     */
    public function sendToWelcomeCustomer($session)
    {
        /** session customer gets confirmed */
        if ($session->getCustomer()->getIsJustConfirmed()) {
            $this->_welcomeCustomer($session->getCustomer(), true);
        }
    }

    /**
     * Function to display registration page
     *
     * Display seller/customer account registration form
     *
     * @return void
     */
    public function createAction()
    {
        /**
         * Check marketplace key is not empty
         */
        if (Mage::helper('marketplace')->checkMarketplaceKey() != '') {
            $msg = Mage::helper('marketplace')->checkMarketplaceKey();
            Mage::app()->getResponse()->setBody($msg);
            return;
        } else {
            $this->loadLayout();
            $this->renderLayout();
        }
    }

    /**
     * Function to post the registration page data's
     *
     * Get and validate seller/customer account registration form
     *
     * @return void
     */
    public function createPostAction()
    {
        /**
         * Get the admin approval status
         */
        $adminApproval = Mage::getStoreConfig('marketplace/admin_approval_seller_registration/need_approval');
        $session = Mage::getSingleton('customer/session');
        /**
         * Check the loggedin status
         */
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session->setEscapeMessages(true);
        if ($this->getRequest()->isPost()) {
            $errors = array();
            /** get customer detail */
            $customerDetail = Mage::getModel('marketplace/seller')->setGroupIdForCustomer($adminApproval);

            $customerForm = Mage::getModel('customer/form');
            $customerForm->setFormCode('customer_account_create')->setEntity($customerDetail);
            $customerData = $customerForm->extractData($this->getRequest());
            $customerData['email'] = trim($customerData['email']," ");
            $customerDetail->getGroupId();

            if ($this->getRequest()->getPost('create_address')) {
                /** get address */
                $address = Mage::getModel('customer/address');
                /** addressform */
                $addressForm = Mage::getModel('customer/form');
                /** set entity address */
                $addressForm->setFormCode('customer_register_address')->setEntity($address);
                $addressData = $addressForm->extractData($this->getRequest(), 'address', false);
                /** validate data */
                $addressErrors = $addressForm->validateData($addressData);
                if ($addressErrors === true) {
                    $address->setId(null)->setIsDefaultBilling($this->getRequest()->getParam('default_billing',
                        false))->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
                    $addressForm->compactData($addressData);
                    $customerDetail->addAddress($address);
                    $addressErrors = $address->validate();
                    $errors = Mage::helper('marketplace/market')->getAddressErrors($errors, $addressErrors);
                } else {
                    $errors = array_merge($errors, $addressErrors);
                }
            }
            try {
                $customerErrors = $customerForm->validateData($customerData);
                if ($customerErrors !== true) {
                    $errors = array_merge($customerErrors, $errors);
                } else {
                    $customerForm->compactData($customerData);
                    /** Seller password */
                    $sellerPassword = $this->getRequest()->getPost('password');
                    /** seller confirmation */
                    $sellerConfirmation = $this->getRequest()->getPost('confirmation');
                    $customer = Mage::getModel('marketplace/seller')->setConfirmationForSeller($customerDetail,
                        $sellerPassword, $sellerConfirmation);
                    $customerErrors = $customer->validate();				
					/** errors */
                    $errors = Mage::getModel('marketplace/seller')->getErrorDetails($customerErrors, $errors);
                }
                $validationResult = count($errors) == 0;
                /** get post */
                $postValue = $this->getRequest()->getPost();
                /*If session is not created for firsttime registration */
                if( ! Mage::getSingleton('checkout/session')->getRegisterCustomerFirstTime()){
                    Mage::getSingleton('checkout/session')->setRegisterCustomerFirstTime("recent");
                }
                $this->loginSellerForMarketplace($customer, $adminApproval, $session, $validationResult, $errors,
                    $postValue);
            } catch (Mage_Core_Exception $e) {
                /** set customer form data */
                $session->setCustomerFormData($this->getRequest()->getPost());
                $message = $this->loginException($e);
                $session->addError($message);
            } catch (Exception $e) {
                $session->setCustomerFormData($this->getRequest()->getPost())->addException($e,
                    $this->__('Customer details not saved.'));
            }
        }
        $this->_redirectError(Mage::getUrl('*/*/login', array(
            '_secure' => true
        )));
    }

    /**
     * Function to display welcome message
     *
     * Display welcome message for seller/ customer
     *
     * @return void
     */
    protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer, $isJustConfirmed = false)
    {
        Mage::getSingleton('customer/session')->addSuccess($this->__('Thank you for registering with %s.',
            Mage::app()->getStore()->getFrontendName()));
        if ($this->_isVatValidationEnabled()) {
            /**
             * Show corresponding VAT message to customer
             */
            $configAddressType = Mage::helper('customer/address')->getTaxCalculationAddressType();
            $userPrompt = '';

            if ($configAddressType == Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING) {
                $userPrompt = $this->__('If you are a registered VAT customer, please click <a href="%s">here</a> to enter you shipping address for proper VAT calculation',
                    Mage::getUrl('customer/address/edit'));
            } else {
                $userPrompt = $this->__('If you are a registered VAT customer, please click <a href="%s">here</a> to enter you billing address for proper VAT calculation',
                    Mage::getUrl('customer/address/edit'));
            }
            /** add success message in session */
            Mage::getSingleton('customer/session')->addSuccess($userPrompt);
        }
        $customer->sendNewAccountEmail($isJustConfirmed ? 'confirmed' : 'registered', '',
            Mage::app()->getStore()->getId());
        $successUrl = Mage::getUrl('*/*/index', array(
            '_secure' => true
        ));
        if (Mage::getSingleton('customer/session')->getBeforeAuthUrl()) {
            $successUrl = Mage::getSingleton('customer/session')->getBeforeAuthUrl(true);
        }
        /** return url */
        return $successUrl;
    }

    /**
     * Function to display add profile form
     *
     * Display Add profile form url
     *
     * @return void
     */
    function addprofileAction()
    {
        Mage::helper('marketplace')->checkMarketplaceKey();
        /**
         * Check the current session is not logged in
         */
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
        /** to load and render layout */
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Function to save seller profile information
     *
     * Save Add profile form
     *
     * @return void
     */
    function saveprofileAction()
    {
        /**
         * initializing the variables with empty value
         */
        $imagesPathBanner = $imagesPathLogo = '';
        /**
         * Get values posted from form
         */
        $data = $this->getRequest()->getPost();
        
        
        /**
         * initializing the variables with empty value
         */
        $sellerId = $storeName = $storeLogo = $showProfile = '';
        if (isset($data ['show_profile'])) {
            $showProfile = $data ['show_profile'];
        }
        $uploadsData = new Zend_File_Transfer_Adapter_Http ();
        $filesDataArray = $uploadsData->getFileInfo();
        /**
         * store posted values to variables
         */
        $sellerId = $data ['seller_id'];
        /** store name */
        $storeName = $data ['store_name'];
        $supplier_address = $data ['supplier_address'];
        $storeLogo = $filesDataArray ['store_logo'] ['name'];
        $storeBanner = $filesDataArray ['store_banner'] ['name'];
		$storeSampleProduct = $filesDataArray['sample_product_profile_file'] ['name'];

        /**
         * Get Media base Directory
         */
        $basedir = Mage::getBaseDir('media');
        /**
         * create a folder to save the logo and banner images in media folder
         */
        Mage::getModel('marketplace/seller')->createDirecotryForSellerProfileImage($basedir, 1);

        $imagesPathLogo = Mage::getModel('marketplace/seller')->saveLogoForSeller($filesDataArray, $basedir,
            'store_logo');
        $imagesPathBanner = Mage::getModel('marketplace/seller')->saveLogoForSeller($filesDataArray, $basedir,
            'store_banner');
		$imagesSampleProduct = Mage::getModel('marketplace/seller')->saveSampleFileForSeller($filesDataArray, $basedir,
				'sample_product_profile_file');
				
        $collection = Mage::getModel('marketplace/sellerprofile')->load($sellerId, 'seller_id');
        $getId = $collection->getId();
        try {
            $imageInfoForSeller = array();
            $imageInfoForSeller ['store_name'] = $storeName;
            $imageInfoForSeller ['images_path_logo'] = $imagesPathLogo;
            $imageInfoForSeller ['images_path_banner'] = $imagesPathBanner;
			$imageInfoForSeller ['product_profile_path'] = $imagesSampleProduct;
            if ($getId) {
                /**
                 * Update form input data in database
                 */
                Mage::getModel('marketplace/seller')->saveSellerProfileData($sellerId, $imageInfoForSeller,
                    $showProfile, $data, $storeLogo, $storeBanner, 1 , $storeSampleProduct );
                Mage::getModel('marketplace/seller')->urlRewriteForSeller($sellerId, $storeName);
                Mage::getSingleton('core/session')->addSuccess($this->__('Your profile information is saved successfully'));
                $this->_redirect('marketplace/seller/addprofile');
                return true;
            } else {
                /**
                 * insert form input data in database
                 */
                Mage::getModel('marketplace/seller')->saveSellerProfileData($sellerId, $imageInfoForSeller,
                    $showProfile, $data, $storeLogo, $storeBanner, 0 , $storeSampleProduct);

                /**
                 * url management
                 */
                Mage::getModel('marketplace/sellerprofile')->addRewriteUrl($storeName, $sellerId);
                Mage::getSingleton('core/session')->addSuccess($this->__('Your profile information is saved successfully'));
                $this->_redirect('marketplace/seller/addprofile');
                return true;
            }
        } catch (Exception $e) {
            /**
             * Error message redirect to create new product page
             */
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
            $this->_redirect('marketplace/seller/addprofile');
        }
    }

    /**
     * Function to display payout form
     *
     * Display Add profile form url
     *
     * @return void
     */
    function payoutAction()
    {
        /**
         * Check the current session is not logged in
         */
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
        /** to load and render layout */
        $this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * Added by Ali Nasrullah
     * Function to display Seller Transation Details form
     *
     * Display Add profile form url
     *
     * @return void
     */
    function transactiondetailAction()
    {
        /**
         * Check the current session is not logged in
         */
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
        /** to load and render layout */
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Function to save payout request
     *
     * Display Add profile form url
     *
     * @return void
     */
    function payoutsaveAction()
    {
        /**
         * Check the current session is not logged in
         */
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }

        $data = $this->getRequest()->getPost();
        $sellerData = Mage::getSingleton('customer/session')->getCustomer();
        $sellerId = $sellerData->getId();
        if ($sellerId != $data['seller_id']) {
            Mage::getSingleton('core/session')->addError($this->__('You cannot modify someone else\'s data.'));
            $this->_redirect('marketplace/seller/payout');
            return;

        } else {

            $allowedAmount = Mage::helper('marketplace/marketplace')->getSellerAccountsDetail($sellerId);

            $getPendingAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($seller_id,'Pending');
            $getApproveAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($seller_id,'Approve');
            $getPaidAmount =  Mage::helper('marketplace/marketplace')->getSellerTotalPayoutRequestAmount($seller_id,'Paid');  
            $getOrderRefundAmount =  Mage::helper('marketplace/marketplace')->getOrderRefundAmount($seller_id);     
          
            $getSellerTotalPayoutRequestAmount = $getPendingAmount + $getApproveAmount + $getPaidAmount;
            $finalRemainingAmount = $allowedAmount['seller_amount'] - $getSellerTotalPayoutRequestAmount;
            $finalRemainingAmount = $finalRemainingAmount - $getOrderRefundAmount;
           
            $error = "";
            if ($finalRemainingAmount <= 0) {
                $error = "You cannot request amount because your balance is lower then requested amount.";
            }else if ($data['request_amount'] < 50) {
                $error= "Amount below 50 cannot be requested.";
            } elseif ($data['request_amount'] <= $finalRemainingAmount) {
                //we are fine to move on
                //$collection = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $sellerId, 'seller_id' )
                try {
                    $requestId = Mage::getModel('marketplace/payout')->saveRequestedPayment($data);
                    Mage::getSingleton('core/session')->addSuccess($this->__("Request placed."));
                    //$customerData = Mage::getModel('customer/customer')->load($sellerId, 'id');
                    $emailData = [];
                    $emailData['seller_id'] = $sellerId;
                    $emailData['request_amount'] = $data['request_amount'];
                    $emailData['request_id'] = $requestId;
                    //$emailData['email'] = $customerData->getEmail();

                    if ($this->sellerPayoutRequestEmail($emailData) === false) {
                        Mage::getSingleton('core/session')->addSuccess($this->__("Request placed, but the admin was not notified by email. Admin still is able to see and entertain your requests."));
                    }
                    $this->_redirect('marketplace/seller/payout');
                } catch (Exception $e) {
                    $error = Mage::getSingleton('core/session')->addError('Error sending mail ' . $e->getMessage());
                }
            } else {
                $error = 'You cannot request amount higher then the limit (' . $actualAllowed . ') you\'ve.';
            }
            if ($error) {

                Mage::getSingleton('core/session')->addError($this->__($error));
                $this->_redirect('marketplace/seller/payout');
                return;
            }
        }
    }

    function payoutacknowledgeAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
        $payoutId = $this->getRequest()->getParam('payout_id');
        if ($payoutId != '') {
            $collection = Mage::getModel('marketplace/payout')->ackPayout($payoutId);
            if ($collection == 1) {
                Mage::getSingleton('core/session')->addSuccess($this->__("Payment received status has been updated"));
                $this->_redirect('marketplace/seller/payout');
            } else {
                Mage::getSingleton('core/session')->addError($this->__('Payment received status was not updated'));
                $this->_redirect('marketplace/seller/payout');
            }
        }
    }

    /**
     * Function to display seller profile information
     *
     * Display seller profile page
     *
     * @return void
     */
    function displaysellerAction()
    {
       
        Mage::helper('marketplace')->checkMarketplaceKey();
        $this->loadLayout();
        $this->renderLayout();
        $id = $this->getRequest()->getParam('id');
        $sellerPage = Mage::getModel('marketplace/sellerprofile')->collectprofile($id);
        $head = $this->getLayout()->getBlock('head');
        $head->setTitle($sellerPage->getStoreTitle());
        $head->setKeywords($sellerPage->getMetaKeyword());
        $head->setDescription($sellerPage->getMetaDescription());
        
    }

    /**
     * Function to display seller dashboard
     *
     * Display seller dashboard page
     *
     * @return void
     */
    function dashboardAction()
    {
        //Mage::getModel('marketplace/fareyedataqueue')->pushCustomerConfirmationToFareye(406);die();
        //Mage::getModel('marketplace/observer')->pushToFareye();die();
        //Mage::getModel('marketplace/Fareyedataqueue')->pushSellerToFareye(1);die();
        Mage::helper('marketplace')->checkMarketplaceKey();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
			//Mage::getSingleton('core/session')->getMessages(true); // remove all previous flashdata
			Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('marketplace/seller/login');
            return;
        } else {
            $customerStatus = Mage::getSingleton('customer/session')->getCustomer()->getCustomerstatus();
            if ($customerStatus != 1) {
                $this->_redirect('marketplace/general/changebuyer');
                return true;
            }
        }
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /**
     * Function to display top seller
     *
     * Display seller top seller page
     *
     * @return void
     */
    function topsellerAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Function to display All seller information
     *
     * Display all seller page
     *
     * @return void
     */
    function allsellerAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('All Seller'));
        $this->renderLayout();
    }

    /**
     * Function to display category wise seller products
     *
     * Display category wise seller products
     *
     * @return void
     */
    function categorylistAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Category List'));
        $this->renderLayout();
    }

    /**
     * Load seller products with category id in ajax
     *
     * @param
     *            int id
     * @param
     *            int sellerid
     *
     * @return string
     */
    function getProductListAction()
    {
        
        $categoryId = $this->getRequest()->getParam('id');
        $sellerId = $this->getRequest()->getParam('sellerid');
        $collection = Mage::getModel('catalog/product')->getCollection()->joinField('category_id',
            'catalog/category_product', 'category_id', 'product_id = entity_id', null,
            'left')->addAttributeToSelect('*')->addAttributeToFilter('category_id', array(
            'in' => $categoryId
        ))->addAttributeToFilter('seller_id', $sellerId);
        $collectionSize = count($collection);
        if ($collectionSize > 0) {
            $productDetails = '<ul id = "mp-product-list" class="products-grid product_snipt f-left">';
            foreach ($collection as $_collection) {
                $wordsCount_category = strlen($_collection->getName());
                $productDetails .= '<li class="item">';
                $productDetails .= '<a href="' . $_collection->getProductUrl() . '">';
                $productDetails .= '<img class="product-image" src="' . Mage::helper('catalog/image')->init($_collection,
                        'thumbnail')->resize(250) . '"alt="' . $_collection->getName() . '" />';
                $productDetails .= '</a>';
                $productDetails .= '<div class="product-info" style="min-height: 0px;">';
                $productDetails .= '<h2 class="product-name">';
                $productDetails .= '<a href="' . $_collection->getProductUrl() . '">';
                if ($wordsCount_category > 15) {
                    $productDetails .= substr(strip_tags($_collection->getName()), 0, 15) . '...';
                } else {
                    $productDetails .= $_collection->getName();
                }
                $productDetails .= '</a>';
                $productDetails .= '</h2>';
                $reviewHelper = $this->getLayout()->createBlock('review/helper');
                $productDetails .= $reviewHelper->getSummaryHtml($_collection, 'short', true);
                $productDetails .= Mage::helper('marketplace/marketplace')->getLabel($_collection);
                $productDetails .= '<div class="price-box">';
                $productDetails .= '<span class="regular-price">';
                $productDetails .= '<span class="price">';
                /**
                 * Display Product Price
                 */
                $price = $_collection->getPrice();
                $splPrice = $_collection->getSpecialPrice();
                $dealExpireDate = $_collection->getspecialToDate();
                if (!empty ($spl_price) && ($dealExpireDate != '') && ($dealExpireDate >= $currentDate)) {
                    $originalPrice = $_collection->getPrice();
                    $discountPrice = $_collection->getSpecialPrice();
                    $savings = $originalPrice - $discountPrice;
                    $savingsPercentage = round(($savings / $originalPrice) * 100, 0);
                    $productDetails .= '<p><del>"' . Mage::helper('core')->currency($price, true,
                            false) . '"</del></p>';
                    $productDetails .= '<p>' . Mage::helper('core')->currency($splPrice, true, false) . '</p>';
                    $productDetails .= '<p>' . '-' . $savingsPercentage . '%' . '</p>';
                } else {
                    $productDetails .= '<p>' . Mage::helper('core')->currency($price, true, false) . '</p>';
                }
                $productDetails .= '</span>';
                $productDetails .= '</span></div>';

                $productDetails .= '</div>';
                $productDetails .= '</li>';
            }
        } else {
            $productDetails .= 'No products exists';
        }
        echo $productDetails .= '</ul>';

    }

    function contactsellerAction()
    {
        $scope = $this->getRequest()->getParam("scope");
        $url = $this->getRequest()->getParam("url");
        $sellerId = $this->getRequest()->getParam('id');
        $rewriteUrl = Mage::helper('marketplace/marketplace')->getSellerRewriteUrl($sellerId);
        if ($scope == "product") {
            $rewriteUrl = Mage::getSingleton('core/url')->parseUrl($url)->getPath();
            // $rewriteUrl = $url->getPath();
        }
        $customerName = $this->getRequest()->getPost('customer_name');
        $customerEmail = $this->getRequest()->getPost('customer_email');
        $customerMessage = $this->getRequest()->getPost('message');
        // $rewriteUrl = Mage::helper ( 'marketplace/marketplace' )->getSellerRewriteUrl ( $sellerId );
        if ($sellerId != '' && $customerName != '' && $customerEmail != '' && $customerMessage != '') {
            if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/display_contact_seller') == 1) {
                /**
                 * Sending email for added new product
                 */
                $templateId = ( int )Mage::getStoreConfig('marketplace/admin_approval_seller_registration/contact_seller');

                if ($templateId) {
                    $emailTemplate = Mage::getModel('core/email_template')->load($templateId);
                } else {
                    $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_admin_approval_seller_registration_contact_seller');
                }
                $SellerInfo = Mage::getModel('customer/customer')->load($sellerId);
                $selleremail = $SellerInfo->getEmail();
                $recipient = $selleremail;
                $sellername = $SellerInfo->getName();
                $domainName = Mage::app()->getFrontController()->getRequest()->getHttpHost();

                $emailTemplate->setSenderName($customerName);
                $emailTemplate->setSenderEmail($customerEmail);
                $emailTemplateVariables = (array(
                    'sellername' => $sellername,
                    'customername' => $customerName,
                    'customeremail' => $customerEmail,
                    'customermessage' => $customerMessage,
                    'domainname' => $domainName
                ));
                $emailTemplate->setDesignConfig(array(
                    'area' => 'frontend'
                ));
                $emailTemplate->getProcessedTemplate($emailTemplateVariables);
                $emailTemplate->send($recipient, $sellername, $emailTemplateVariables);
            }
            Mage::getSingleton('core/session')->addSuccess($this->__('Your message has been successfully sent'));
            $this->_redirectUrl($rewriteUrl);
            return true;
        }
    }

    /**
     * Check whether seller or not
     */
    public function checkWhetherSellerOrNot($login)
    {
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($login ['username']);
        $customerGroupid = $customer->getGroupId();
        $groupId = Mage::helper('marketplace')->getGroupId();
        if ($customerGroupid != $groupId) {
            Mage::getSingleton('core/session')->addError($this->__('You must have a Seller Account to access this page'));
            $this->_redirect('*/*/login');
            return;
        }
        /**
         * Get the customer approval status
         */
        $customerStatus = $customer->getCustomerstatus();
        /**
         * Check the customer approval status is zero or two
         */
        if ($customerStatus == 2 || $customerStatus == 0) {
            Mage::getSingleton('core/session')->addError($this->__('Admin Approval is required for Seller Account'));
            /**
             * If the seller not approved yet redirect them to login page
             */
            $this->_redirect('*/*/login');
            return;
        }
    }

    /**
     * Login seller for marketplace
     *
     * @param array $customer
     * @param string $adminApproval
     * @param
     *            $adminApproval
     * @param
     *            $session
     * @param
     *            $validationResult
     * @param
     *            $errors
     * @param
     *            $postValue
     * @return void
     */
    public function loginSellerForMarketplace(
        $customer,
        $adminApproval,
        $session,
        $validationResult,
        $errors,
        $postValue
    ) {
        if (true === $validationResult) {
            //Subscribe for news letter.
            if ($this->getRequest()->getParam('is_subscribed', false)) {
                $customer->setIsSubscribed(1);
            }

            $customerId = $customer->save()->getId();

            /** Storing seller name by Imran **/
            try {
                $imageInfoForSeller = array();
                $imageInfoForSeller['store_name'] = $this->getRequest()->getPost('store_name');
                /*
				* Save Store Logo and Sample file.
				*/
				$imagesPathSampleFile = $imagesPathLogo = ''; // Initialize the variable with empty data.
				$uploadsData = new Zend_File_Transfer_Adapter_Http ();
				$filesDataArray = $uploadsData->getFileInfo();
				$data = $postValue;
				$storeName = $data ['store_name'];
				$storeLogo = $filesDataArray['store_logo'] ['name'];
				$storeBanner = $filesDataArray['sample_product_profile_file'] ['name'];
				
				$basedir = Mage::getBaseDir('media');//Get Media base Directory
				Mage::getModel('marketplace/seller')->createDirecotryForSellerProfileImage($basedir, 1);//create a folder to save the logo and banner images in media folder
				$imagesPathLogo = Mage::getModel('marketplace/seller')->saveLogoForSeller($filesDataArray, $basedir,
				'store_logo');
				$imagesPathBanner = Mage::getModel('marketplace/seller')->saveSampleFileForSeller($filesDataArray, $basedir,
				'sample_product_profile_file');
				
				$imageInfoForSeller = array();
				$imageInfoForSeller ['store_name'] = $storeName;
				$imageInfoForSeller ['images_path_logo'] = $imagesPathLogo;
				$imageInfoForSeller ['images_path_banner'] = $imagesPathBanner;
				
				/**
                 * insert form input data in database
                 */
                /*Mage::getModel('marketplace/seller')->saveSellerProfileData($customerId, $imageInfoForSeller,1, "", "", "", 0);*/
				Mage::getModel('marketplace/seller')->saveSellerProfileDataSignup($customerId, $imageInfoForSeller,
                    1, $data , $storeLogo, "", 0 , $storeBanner );
                Mage::getModel('marketplace/sellerprofile')->addRewriteUrl($imageInfoForSeller['store_name'], $customerId);
				
				/*Email template call*/
				$adminEmailId = Mage::getStoreConfig ( 'marketplace/marketplace/admin_email_id' );
				$toMailId = Mage::getStoreConfig ( "trans_email/ident_custom2/email" );
				$toName = Mage::getStoreConfig ( "trans_email/ident_custom2/name" );
				
				$emailTemplate = Mage::getModel ( 'core/email_template' )->loadDefault ( 'marketplace_seller_signup_process' );
				$adminurl = Mage::helper ( 'adminhtml' )->getUrl ( 'marketplaceadmin/adminhtml_manageseller/index' );
				$customer = Mage::getModel ( 'customer/customer' )->load ( $customerId );
				$cemail = $customer->getEmail ();
				$cname = $customer->getName ();
				$emailTemplate->setSenderName ( ucwords ( $toName ) );
				$emailTemplate->setSenderEmail ( $toMailId );
				$emailTemplateVariables = (array (
						'ownername' => ucwords ( $toName ),
						'cname' => ucwords ( $cname ),
						'cemail' => $cemail,
						'adminurl' => $adminurl 
				));
				$emailTemplate->setDesignConfig ( array (
						'area' => 'frontend' 
				) );
				$emailTemplate->getProcessedTemplate ( $emailTemplateVariables );
				$emailTemplate->send ( $toMailId, ucwords ( $toName ), $emailTemplateVariables );
				/*Email template end*/
				
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
            }
            /** Storing seller name ends **/

            Mage::getModel('marketplace/sellerprofile')->adminApproval($customerId);
            if ($adminApproval == 1) {
                Mage::dispatchEvent('customer_register_success', array(
                    'account_controller' => $this,
                    'customer' => $customer
                ));
                Mage::getSingleton('core/session')->addSuccess($this->__('Admin Approval is required. Please wait until admin confirms your Seller Account'));
                $this->_redirect('*/*/login');
                return;
            } else {
                Mage::dispatchEvent('customer_register_success', array(
                    'account_controller' => $this,
                    'customer' => $customer
                ));
                $session->setCustomerAsLoggedIn($customer);
                $session->renewSession();
                $url = $this->_welcomeCustomer($customer);
                $this->_redirectSuccess($url);
                return;
            }
        } else {
            $session->setCustomerFormData($postValue);
            if (is_array($errors)) {
                foreach ($errors as $errorMessage) {
                    $session->addError($errorMessage);
                }
            } else {
                $session->addError($this->__('Invalid customer data'));
            }
        }
    }

    /**
     * Login exception
     *
     * @param array $e
     * @return string $message
     */
    public function loginException($e)
    {
        $message = '';
        if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
            $url = Mage::getUrl('customer/account/forgotpassword');
            Mage::getSingleton('core/session')->addError($this->__('There is already an account with this email address.', $url));
            $this->_redirect('*/*/login');
            return;
        } else {
            $message = $e->getMessage();
        }
        return $message;
    }

    public function sellerPayoutRequestEmail($data)
    {
        $adminEmail = Mage::getStoreConfig('trans_email/ident_general/email');
        //Getting the Store E-Mail Sender Name.
        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('marketplace_seller_payout_request_notification');
        $SellerInfo = Mage::getModel('customer/customer')->load($data['seller_id']);
        $selleremail = $SellerInfo->getEmail();
        $recipient = $selleremail;
        $sellername = $SellerInfo->getName();
        $domainName = Mage::app()->getFrontController()->getRequest()->getHttpHost();

        $emailTemplate->setSenderName($sellername);
        $emailTemplate->setSenderEmail($selleremail);

        $collectionSeller = Mage::getModel('marketplace/sellerprofile')->load($data['seller_id'], 'seller_id');
        $data['seller_store_name'] = ($collectionSeller->getStore_title()) ? $collectionSeller->getStore_title() : $sellername;//actual store name or the username
        $emailTemplateVariables = (array(
            'seller_store_name' => $data['seller_store_name'],
            'domainname' => $domainName,
            'request_id' => $data['request_id'],
            'request_amount' => $data['request_amount'],
            'email' => $selleremail,
            'subject' => "New Payout/Withdrawal request from $sellername"
        ));
        $emailTemplate->setDesignConfig(array(
            'area' => 'frontend'
        ));
        $emailTemplate->getProcessedTemplate($emailTemplateVariables);

        try {
            $sent = $emailTemplate->send($adminEmail, $sellername, $emailTemplateVariables);
            //Confimation E-Mail Send
            if ($sent != 1) {
                return false;
            }
        } catch (Exception $error) {
            //Mage::getSingleton('core/session')->addError($error->getMessage());
            return false;
        }
    }
}

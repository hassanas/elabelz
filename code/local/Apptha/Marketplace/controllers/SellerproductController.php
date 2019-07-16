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
 * This file is used to bulk product upload and assign products for sellers
 */
class Apptha_Marketplace_SellerproductController extends Mage_Core_Controller_Front_Action {
    /**
     * Add Create Products Form
     *
     * @return void
     */
    public function createAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        $this->loadLayout ();
        $this->renderLayout ();
    }
    /**
     * Add New Products Form
     *
     * @return void
     */
    public function producttypeAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        /**
         * Get the posted values for the create product
         */
        $createProductDetails = $this->getRequest ()->getPost ();
        /**
         * check the product details has been set
         * and create product details type has been set
         */
        if (isset ( $createProductDetails ['attribute_set_id'] ) && isset ( $createProductDetails ['type'] )) {
            /**
             * check the create product details type is configurable
             * if so redirect to seller product select attributes
             * if not redirect to new product creation
             */
            if ($createProductDetails ['type'] == 'configurable') {
                $this->_redirect ( 'marketplace/sellerproduct/selectattributes', array (
                        'set' => $createProductDetails ['attribute_set_id'] 
                ) );
            } else {
                $this->_redirect ( 'marketplace/product/new', array (
                        'set' => $createProductDetails ['attribute_set_id'],
                        'type' => $createProductDetails ['type'] 
                ) );
            }
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Please enter all required fields' ) );
            $this->_redirect ( 'marketplace/sellerproduct/create' );
        }
    }
    /**
     * Set Configurable Attributes for configurable product
     *
     * @return void
     */
    public function selectattributesAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        
        $this->loadLayout ();
        $this->renderLayout ();
    }
    /**
     * Set Configurable Attributes for configurable product
     *
     * @return void
     */
    public function configurableAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Initilize customer and seller group id
         */
        $customerGroupId = $sellerGroupId = $customerStatus = '';
        $customerStatus = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getCustomerstatus ();
        $sellerGroupId = Mage::helper ( 'marketplace' )->getGroupId ();
        $customerGroupId = Mage::getSingleton ( 'customer/session' )->getCustomerGroupId ();
        /**
         * Check the customer is logged in
         * and customer group id is equal to the seller group id
         */
        if (Mage::getSingleton ( 'customer/session' )->isLoggedIn () && $customerGroupId == $sellerGroupId) {
            /**
             * Checking whether customer approved or not
             */
            if ($customerStatus == 1) {
                /**
                 * Check the posted id is not equal to empty
                 * if so redirect to the product manage section
                 */
                if ($this->getRequest ()->getParam ( 'id' ) == '') {
                    $this->_redirect ( 'marketplace/product/manage' );
                    return;
                } else {
                    /**
                     * if the posted id is equal to empty
                     * get the customer information from logged in
                     * Get the product information
                     * Get the seller id
                     * Get the simple product id
                     */
                    $customerId = Mage::getSingleton ( 'customer/session' )->getCustomerId ();
                    $productId = $this->getRequest ()->getParam ( 'id' );
                    $product = Mage::getModel ( 'catalog/product' )->load ( $productId );
                    $productSellerId = $product->getSellerId ();
                    
                    $simpleProductId = $this->getRequest ()->getParam ( 'sp' );
                    $this->loadConfigurableProduct ( $simpleProductId, $customerId, $product );
                }
                /**
                 * Check the product seller id is not equal to the customer id
                 * if it is then redirect to the product manage section
                 */
                if ($productSellerId != $customerId) {
                    $this->_redirect ( 'marketplace/product/manage' );
                    return;
                }
                $this->loadLayout ();
                $this->renderLayout ();
            } else {
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Admin Approval is required. Please wait until admin confirms your Seller Account' ) );
                /**
                 * if the customer status not equal to 1
                 * redirect the user to the seller login page
                 */
                $this->_redirect ( 'marketplace/seller/login' );
            }
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'You must have a Seller Account to access this page' ) );
            /**
             * If the customer is not logged in
             * redirect the customer to the seller login page
             */
            $this->_redirect ( 'marketplace/seller/login' );
        }
    }
    /**
     * Load configurable product
     *
     * @param collection $product            
     * @param int $simpleProductSellerId            
     * @param int $simpleProductId            
     */
    public function loadConfigurableProduct($simpleProductId, $customerId, $product) {
        /**
         * Check the simple product id has been set
         * if so get the simple product informaiton
         * Get simple product seller id
         */
        if ($simpleProductId) {
            $simpleProduct = Mage::getModel ( 'catalog/product' )->load ( $simpleProductId );
            $simpleProductSellerId = $simpleProduct->getSellerId ();
            /**
             * Check the simple product seller id is not equal to the customer id
             * if it is then redirect to the manage section of the product
             */
            if ($simpleProductSellerId != $customerId) {
                $this->_redirect ( 'marketplace/product/manage' );
                return;
            }
            /**
             * Get product attributes
             * and associated product details
             */
            $attributes = $product->getTypeInstance ()->getConfigurableAttributesAsArray ();
            $associatedProducts = Mage::getModel ( 'catalog/product' )->getCollection ()->addFieldToFilter ( 'seller_id', $simpleProductSellerId );
            /**
             * Check set has been exist in the posted values
             * if so then add field to filter of associated product
             */
            if ($this->getRequest ()->getParam ( 'set' )) {
                $associatedProducts->addFieldToFilter ( 'attribute_set_id', $this->getRequest ()->getParam ( 'set' ) );
            }
            foreach ( $attributes as $attribute ) {
                /**
                 * Check the attribute code has been set
                 * if so add the attribute code filter for associate products
                 */
                if (isset ( $attribute ['attribute_code'] )) {
                    $associatedProducts->addFieldToFilter ( $attribute ['attribute_code'], array (
                            'neq' => '' 
                    ) );
                }
            }
            $associatedProducts->addFieldToFilter ( 'type_id', 'simple' );
            $associatedProducts->addAttributeToSelect ( '*' );
            $associatedProducts->addAttributeToSort ( 'entity_id', 'DESC' );
            $associatedProducts->addFieldToFilter ( 'entity_id', $simpleProductId );
            /**
             * Get the associated products count
             */
            $productCount = count ( $associatedProducts->getAllIds () );
            /**
             * Check the product count is less than 1
             * if so redirect user to sell product configurable
             */
            if ($productCount < 1) {
                $this->_redirect ( 'marketplace/sellerproduct/configurable', array (
                        'id' => $this->getRequest ()->getParam ( 'id' ),
                        'set' => $this->getRequest ()->getParam ( 'set' ) 
                ) );
                return;
            }
        }
    }
    /**
     * Save Quick Simple Products
     *
     * @return void
     */
    public function quickcreateAction() {
        $configProductId = $this->getRequest ()->getPost ( 'product_id' );
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        $this->checkWhetherSellerOrNot ();
        $productData = array ();
        $type = $store = $sellerId = '';
        $set = 4;
        $productData = $this->getRequest ()->getPost ( 'simple_product' );
        $type = $this->getRequest ()->getPost ( 'type' );
        $set = $this->getRequest ()->getPost ( 'set' );
        $store = $this->getRequest ()->getPost ( 'store' );
        $sizeshowninimage = $this->getRequest ()->getPost ( 'size_shown_in_image' );
        $material = $this->getRequest ()->getPost ( 'material' );

        /* For Product Images */
        $setBase = $setThumb = $setSmall = '';
        $setBase = $this->getRequest ()->getPost ( 'setbase' );
        $setThumb = $this->getRequest ()->getPost ( 'setthumb' );
        $setSmall = $this->getRequest ()->getPost ( 'setsmall' );


        $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $groupId = Mage::helper ( 'marketplace' )->getGroupId ();
        $checkskuProductId = '';
        if (isset ( $productData ['sku'] )) {
            $checkskuProductId = Mage::getModel ( 'catalog/product' )->getIdBySku ( trim ( $productData ['sku'] ) );
        }
        if (! empty ( $checkskuProductId )) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'SKU Not Available' ) );
            $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                'id' => $configProductId,
                'set' => $set
            ) );
            return;
        }
        $autoProductName = Mage::getModel ( 'marketplace/product' )->getConfigProductName ( $configProductId );
        $productData ['auto_flag'] = 'none';
        $attributes = $this->getRequest ()->getPost ( 'attributes' );
        $productData = Mage::getModel ( 'marketplace/product' )->createAutoProductNameAndSku ( $attributes, $productData, $autoProductName, $sellerId );
        $autoFlag = $productData ['auto_flag'];
        if (isset ( $productData ['stock_data'] ['qty'] ) && ! empty ( $type )) {
            $productData ['description'] = $productData ['name'];
            $productData ['short_description'] = $productData ['name'];
            $product = Mage::getModel ( 'catalog/product' );
            $product->setAttributeSetId ( $set );
            $product->setVisibility ( Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE );
            $product->setTypeId ( $type );
            $product->setPrice ( 0 );
            $product->setTaxClassId ( 0 );
            $product->setData('size_shown_in_image',$sizeshowninimage);
            $product->setData('material',$material);
            if (isset ( $productData ['seller_shipping_option'] )) {
                $shippingOption = $productData ['seller_shipping_option'];
                $product->setSellerShippingOption ( $shippingOption );
            }
            $product->setStoreId ( 0 );
            $createdAt = Mage::getModel ( 'core/date' )->gmtDate ();
            $product->setCreatedAt ( $createdAt );
            $product->setSellerId ( $sellerId );
            $product->setGroupId ( $groupId );
            $product->setIsAssignProduct ( 0 );
            $product->addData ( $productData );

            /* To save Images in folder */
            $imagesPath = array ();
            $uploadsData = new Zend_File_Transfer_Adapter_Http ();
            $filesDataArray = $uploadsData->getFileInfo ();
            $imagesPath = Mage::getModel ( 'marketplace/product' )->getProductImagePath ( $filesDataArray );
            $product = Mage::getModel ( 'marketplace/product' )->setProductInfo ( $product, $set, $type, $categoryIds, $sellerId, $groupId, $imagesPath );

            try {
                Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
                $storeId = Mage::app ()->getStore ()->getStoreId ();
                $product->save ();
                $productId = $product->getId ();

                Mage::getModel ( 'marketplace/product' )->setBaseImageForProduct ( $productId, $store, $setBase, $productData, 'new' ,$setThumb,$setSmall);

                Mage::getModel ( 'marketplace/product' )->deleteTempImageFiles ( $imagesPath );

                $attributes = $this->getRequest ()->getPost ( 'attributes' );
                $configProduct = Mage::getModel ( 'catalog/product' )->load ( $configProductId );
                $configurableProductsData = Mage::getModel ( 'marketplace/product' )->getconfigurableProductsDataForQuickCreate ( $attributes, $productData, $configProduct, $sellerId );
                $configAttributes = $configProduct->getTypeInstance ()->getConfigurableAttributesAsArray ();
                Mage::helper ( 'marketplace/product' )->assignConfigurableProductData ( $configAttributes, $configurableProductsData, $configProduct );
                Mage::app ()->setCurrentStore ( $storeId );
                $simpleProductIds = $configProduct->getTypeInstance ()->getUsedProductIds ();
                $simpleProductIds [] = $productId;
                Mage::getResourceModel ( 'catalog/product_type_configurable' )->saveProducts ( $configProduct, array_unique ( $simpleProductIds ) );
                if (Mage::helper ( 'marketplace' )->getProductApproval () == 1) {
                    Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Associated Product is created successfully' ) . '.' );
                } else {
                    Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Associated Product is awaiting moderation' ) );
                }
                Mage::helper ( 'marketplace/product' )->sentEmailToAdmin ( $sellerId, $product );
                // Commented by Adnan on 5th April, 16
                // $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                //     'id' => $configProductId,
                //     'set' => $set,
                //     'sp' => $product->getId (),
                //     'auto' => $autoFlag
                // ) );
                $this->_redirect ( 'marketplace/product/edit/', array (
                    'id' => $configProductId
                ) );
            } catch ( Mage_Core_Exception $e ) {
                $errMsg = $this->__ ( $e->getMessage () );
                Mage::getSingleton ( 'core/session' )->addError ( $errMsg );
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set
                ) );
            } catch ( Exception $e ) {
                $errMsg = $this->__ ( $e->getMessage () );
                Mage::getSingleton ( 'core/session' )->addError ( $errMsg );
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set
                ) );
            }
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Please enter all required fields' ) );
            $this->_redirect ( 'marketplace/sellerproduct/configurable', array (
                'id' => $configProductId,
                'set' => $set
            ) );
        }
    }
    /**
     * Update Configurable Associated Products
     *
     * @return void
     */
    public function updatesimpleproductAction() {
        $configProductId = $this->getRequest ()->getPost ( 'product_id' );
        $set = $this->getRequest ()->getPost ( 'set' );
        $sizeshowninimage = $this->getRequest ()->getPost ( 'size_shown_in_image' );
        $material = $this->getRequest ()->getPost ( 'material' );
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        $this->checkWhetherSellerOrNot ();
        /**
         * Get simple product id
         */
        $simpleProductId = $this->getRequest ()->getPost ( 'simple_product_id' );
        /**
         * Check the simple product id is not empty
         */
        if (! empty ( $simpleProductId )) {
            $product = Mage::getModel ( 'catalog/product' )->load ( $simpleProductId );
            /**
             * Check the customer is logged in already
             * if so retrieve the seller id
             */
            if (Mage::getSingleton ( 'customer/session' )->isLoggedIn ()) {
                $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
            }
            if ($sellerId != $product->getSellerId ()) {
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set
                ) );
                return;
            }
            /**
             * Attribute set
             */
            $set = $this->getRequest ()->getPost ( 'set' );
            $productData = $this->getRequest ()->getPost ( 'simple_product' );

            /**
             * Adding data to product instanse
             */
            if (! empty ( $productData )) {
                $product->addData ( $productData );
            }

            /* For Product images */
            $deleteImages = $this->getRequest ()->getPost ( 'deleteimages' );
            
            $baseImage = $this->getRequest ()->getPost ( 'baseimage' );
            $thumbImage = $this->getRequest ()->getPost ( 'thumbimage' );
            $smallImage = $this->getRequest ()->getPost ( 'smallimage' );

            $imagesPath = array ();
            $uploadsData = new Zend_File_Transfer_Adapter_Http ();
            $filesDataArray = $uploadsData->getFileInfo ();
            $imagesPath = Mage::getModel ( 'marketplace/product' )->getProductImagePath ( $filesDataArray );

            $product = Mage::getModel ( 'marketplace/product' )->setImagesForProduct ( $product, $imagesPath );

            $product = Mage::helper ( 'marketplace/product' )->getProductNameInfo ( $product, $productData ['name'] );
            $product = Mage::helper ( 'marketplace/product' )->getProductSkuInfo ( $product, $productData ['sku'] );
            $product = Mage::helper ( 'marketplace/product' )->getProductWeightInfo ( $product, $productData ['weight'] );

            /**
             * Initilize product store
             */
            $store = Mage::app ()->getStore ()->getId ();
            $product->setStoreId ( $store );
            /**
             * Saving new product
             */
            try {
                Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
                $product->setData('size_shown_in_image',$sizeshowninimage);
                $product->setData('material',$material);
                $product->save();

                Mage::getModel ( 'marketplace/product' )->setProductImagesforProduct ( $baseImage, $simpleProductId, $store, $product, $productData,$smallImage,$thumbImage );
                 
                Mage::getModel ( 'marketplace/product' )->deleteProductImagesForEdit ( $deleteImages, $simpleProductId, $baseImage,$product );
                
                 
               
                
                //$product->setStatus('2');
                //$product->setSellerProductStatus('1024');
                
                //$product->save();
               

            
                $configurableProductsData = array ();
                $configProduct = Mage::getModel ( 'catalog/product' )->load ( $configProductId );
                $attributes = $this->getRequest ()->getPost ( 'attributes' );
                $configurableProductsData = Mage::helper ( 'marketplace/product' )->getconfigurableProductsData ( $attributes, $productData, $configProduct );
                $configAttributes = $configProduct->getTypeInstance ()->getConfigurableAttributesAsArray ();
                Mage::helper ( 'marketplace/product' )->assignConfigurableProductData ( $configAttributes, $configurableProductsData, $configProduct );
                Mage::app ()->setCurrentStore ( $store );

                /**
                 * Success message redirect to manage product page
                 */
                
                

                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Associated Product details updated successfully.' ) );
                // Commented by Adnan on 5th April, 16
                // $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                //     'id' => $configProductId,
                //     'set' => $set
                // ) );

                

                $this->_redirect ( 'marketplace/product/edit/', array (
                    'id' => $configProductId
                ) );
                return;
            } catch ( Mage_Core_Exception $e ) {
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set
                ) );
            } catch ( Exception $e ) {
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set
                ) );
            }

        } else {
            $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                'id' => $configProductId,
                'set' => $set
            ) );
            return;
        }
    }
    /**
     * Save Configurable Associated Products
     *
     * @return void
     */
    public function saveconfigurableAction() {
        $configProductId = $this->getRequest ()->getPost ( 'product_id' );
        $set = $this->getRequest ()->getPost ( 'set' );
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        //Before change to admin store get current store id.
        $currentStoreId = Mage::app()->getStore()->getStoreId();
        
        Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
        try {
            /**
             * Load configurable product data
             */
            $configProduct = Mage::getModel ( 'catalog/product' )->load ( $configProductId );
            
            /**
             * Getting product data from product array
             */
            $productData = $this->getRequest ()->getPost ( 'simple_product' );
            $attributes = $this->getRequest ()->getPost ( 'attributes' );
            $configurableProductsData = Mage::helper ( 'marketplace/product' )->getconfigurableProductsData ( $attributes, $productData, $configProduct );
            /**
             * Get attributes value
             */
            $configAttributes = $configProduct->getTypeInstance ()->getConfigurableAttributesAsArray ();
            Mage::helper ( 'marketplace/product' )->assignConfigurableProductData ( $configAttributes, $configurableProductsData, $configProduct );
            $selectedAssociateProductIds = $allSelectedUnSelectedAssociateProductIds = array ();
            $selectedAssociateProductIds = $this->getRequest ()->getPost ( 'selected_simple_product_ids' );
            $allSelectedUnSelectedAssociateProductIds = $this->getRequest ()->getPost ( 'unselected_associate_product_ids' );
            Mage::helper ( 'marketplace/product' )->saveConfigurableAssociateProduct ( $selectedAssociateProductIds, $allSelectedUnSelectedAssociateProductIds, $configProduct );
            /**
             * Success message redirect to manage product page
             */
            if ($this->getRequest ()->getParam ( 'p' )) {
                $page = $this->getRequest ()->getParam ( 'p' );
            } else {
                $page = 1;
            }
            if ($this->getRequest ()->getParam ( 'limit' )) {
                $limit = $this->getRequest ()->getParam ( 'limit' );
            } else {
                $limit = 10;
            }
            Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Associated Products are updated successfully' ) . '.' );
            //Set Previous Store from admin Store before redirecting.
            Mage::app ()->setCurrentStore ( $currentStoreId );
            $this->_redirect ( 'marketplace/product/edit/', array (
                    'id' => $configProductId 
            ) );
        } catch ( Mage_Core_Exception $e ) {
            /**
             * Error message redirect to create new product page
             */
            $errorMessage = $this->__ ( $e->getMessage () );
            Mage::getSingleton ( 'core/session' )->addError ( $errorMessage );
            //Set Previous Store from admin Store before redirecting.
            Mage::app ()->setCurrentStore ( $currentStoreId );
            $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set 
            ) );
        } catch ( Exception $e ) {
            /**
             * Error message redirect to create new product page
             */
            $errorMessage = $this->__ ( $e->getMessage () );
            Mage::getSingleton ( 'core/session' )->addError ( $errorMessage );
            //Set Previous Store from admin Store before redirecting.
            Mage::app ()->setCurrentStore ( $currentStoreId );
            $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $configProductId,
                    'set' => $set 
            ) );
        }
    }
    /**
     * Delete Seller Products
     *
     * @return void
     */
    public function deleteconfigurableAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
        $productDeleteCount = 0;
        /**
         * Get config product id
         * get set value which has been posted
         * Get selected associate product ids
         */
        $configProductId = $this->getRequest ()->getPost ( 'product_id' );
        $set = $this->getRequest ()->getPost ( 'set' );
        $selectedAssociateProductIds = $this->getRequest ()->getPost ( 'selected_simple_product_ids' );
        /**
         * Check the count of selected associated products ids greater than or equal to 1
         * if so Get the selected associated product ids
         */
        if (count ( $selectedAssociateProductIds ) >= 1) {
            $selectedAssociateProductIds = array_unique ( $selectedAssociateProductIds );
            Mage::register ( 'isSecureArea', true );
            $productDeleteCount = Mage::helper ( 'marketplace/market' )->deleteconfigurableproduct ( $selectedAssociateProductIds );
            /**
             * un set secure admin area
             */
            Mage::unregister ( 'isSecureArea' );
        }
        /**
         * Check the product delete count is less than or equal to zero
         * if so show message like please select product to delete
         */
        if ($productDeleteCount <= 0) {
            Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Please select a product to delete" ) . '.' );
        } else {
            /**
             * if the product count is greater than 1
             * add success message for multiple products delete
             */
            if ($productDeleteCount > 1) {
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Products are Deleted Successfully" ) . '.' );
            } else {
                /**
                 * if the product count is equal to 1
                 * add success message for single product delete
                 */
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Product Deleted Successfully" ) . '.' );
            }
        }
        $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                'id' => $configProductId,
                'set' => $set 
        ) );
        return true;
    }
    
    /**
     * Assign product
     *
     * @return void
     */
    public function assignproductAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $sellerDetails = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $sellerId, 'seller_id' );
        /**
         * Check the seller details store title is empty
         * if it is then redirect user to seller add profile section
         */
        if (empty ( $sellerDetails ['store_title'] )) {
            Mage::getSingleton ( 'core/session' )->addNotice ( $this->__ ( 'Kindly complete your profile details, before Assign New Product' ) . '.' );
            $this->_redirect ( 'marketplace/seller/addprofile' );
            return;
        }
        $this->loadLayout ();
        $this->renderLayout ();
    }
    /**
     * Assign product
     *
     * @return void
     */
    public function addassignproductAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Assign the posted values to variables like
         * posted id to assign product id
         * posted pid to selected product id
         */
        $assignProductId = $this->getRequest ()->getParam ( 'id' );
        $selectedProductId = $this->getRequest ()->getParam ( 'pid' );
        /**
         * Check assigned product id is empty
         * and selected product id is empty
         * if so redirect to the seller product assign product section
         */
        if (empty ( $assignProductId ) && empty ( $selectedProductId )) {
            $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
            return;
        }
        /**
         * Initilize customer and seller group id
         */
        $customerGroupId = $sellerGroupId = $customerStatus = '';
        $customerGroupId = Mage::getSingleton ( 'customer/session' )->getCustomerGroupId ();
        $customerStatus = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getCustomerstatus ();
        $sellerGroupId = Mage::helper ( 'marketplace' )->getGroupId ();
        /**
         * Check the customer is loggedf in
         * and customer group id is equal to the seller group id
         */
        if (Mage::getSingleton ( 'customer/session' )->isLoggedIn () && $customerGroupId == $sellerGroupId) {
            /**
             * Check the customer status is equal to 1
             */
            if ($customerStatus == 1) {
                Mage::getSingleton ( 'core/session' )->unsFilterNameForPagination ();
                $this->checkingForLoadAssignProduct ();
                $this->loadLayout ();
                $this->renderLayout ();
            } else {
                /**
                 * if the customer status is not equal to 1
                 * add error message like admin approval is required
                 * and redirect to the seller login section
                 */
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Admin Approval is required. Please wait until admin confirms your Seller Account' ) );
                $this->_redirect ( 'marketplace/seller/login' );
            }
        } else {
            /**
             * If the cusotmer is not logged in
             * Display no access error message
             * and redirect user to the seller login page
             */
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'You must have a Seller Account to access this page' ) );
            $this->_redirect ( 'marketplace/seller/login' );
        }
    }
    /**
     * Check to load assign product layout
     */
    public function checkingForLoadAssignProduct() {
        /**
         * Checking the posted id is equal to empty
         */
        if ($this->getRequest ()->getParam ( 'id' ) == '') {
            /**
             * Checking the posted pid is equal to empty
             * if it is then redirect to the seller product add assign product section
             */
            if ($this->getRequest ()->getParam ( 'pid' ) == '') {
                $this->_redirect ( 'marketplace/sellerproduct/addassignproduct' );
                return;
            } else {
                /**
                 * if the pid is not equal to empty
                 * get the customer id
                 * get the product id
                 * get the product information through id
                 * get the product seller id
                 */
                $customerId = Mage::getSingleton ( 'customer/session' )->getCustomerId ();
                $productId = $this->getRequest ()->getParam ( 'pid' );
                $product = Mage::getModel ( 'catalog/product' )->load ( $productId );
                $productSellerId = $product->getSellerId ();
                /**
                 * Confirming the product seller id is not equal to the customer id
                 * if so redirect to the add assing product of the seller product
                 */
                if ($productSellerId != $customerId) {
                    $this->_redirect ( 'marketplace/sellerproduct/addassignproduct' );
                    return;
                }
            }
        }
    }
    /**
     * Save assign product
     *
     * @return void
     */
    public function saveassignproductAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * get the assigned product id
         */
        $assignProductId = $this->getRequest ()->getPost ( 'assign_product_id' );
        /**
         * Check the assgn product id is empty
         * if so redirect to the assign product of the seller product
         */
        if (empty ( $assignProductId )) {
            $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
            return;
        }
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        /**
         * Confirming the customer is already logged in
         * If so get the seller id
         */
        if (Mage::getSingleton ( 'customer/session' )->isLoggedIn ()) {
            $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        }
        $storeId = Mage::app ()->getStore ()->getStoreId ();
        /**
         * Getting group id
         */
        $groupId = Mage::helper ( 'marketplace' )->getGroupId ();
        /**
         * Get the posted product data
         */
        $productData = $this->getRequest ()->getPost ( 'product' );
        /**
         * check the product data sky is not empty
         * and product data description is not empty
         * and product data price is not empty
         */
        if (! empty ( $productData ['sku'] ) && ! empty ( $productData ['description'] ) && ! empty ( $productData ['price'] )) {
            $product = Mage::getModel ( 'catalog/product' )->load ( $assignProductId );
            $newProduct = Mage::helper ( 'marketplace/product' )->saveassignproduct ( $assignProductId, $productData, $sellerId, $groupId, $product );
            /**
             * Function for edit downloadable product sample and link data
             * Check the new product type id is 'downloadable'
             * and store id has been set
             */
            if ($newProduct->getTypeId () == 'downloadable' && isset ( $storeId )) {
                $this->addDownloadableProductData ( $newProduct->getId (), $storeId );
            }
            /**
             * Success message redirect to manage product page
             */
            if (Mage::helper ( 'marketplace' )->getProductApproval () == 1) {
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Your product is added successfully' ) );
            } else {
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Your product is awaiting moderation' ) );
            }
            
            Mage::helper ( 'marketplace/product' )->sentEmailToAdmin ( $sellerId, $product );
            
            $this->_redirect ( 'marketplace/sellerproduct/manageassignproduct' );
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Please enter all required fields' ) );
            $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
        }
    }
    /**
     * Manage Seller Products
     *
     * @return void
     */
    public function manageassignproductAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        
        Mage::getSingleton ( 'core/session' )->unsFilterNameForPagination ();
        $this->loadLayout ();
        $this->renderLayout ();
    }
    /**
     * Update assign product
     *
     * @return void
     */
    public function updateassignproductAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        $selectedProductId = $this->getRequest ()->getPost ( 'selected_product_id' );
        /**
         * Check whether assign product or not
         */
        $this->checkAssignProductOrNot ( $selectedProductId );
        $productData = $this->getRequest ()->getPost ( 'product' );
        /**
         * confirming sku of product data is not empty
         * and description of product data is not empty
         * and price of product data is not empty
         * if so get the store id
         * new product information
         */
        if (! empty ( $productData ['sku'] ) && ! empty ( $productData ['description'] ) && ! empty ( $productData ['price'] )) {
            try {
                Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
                $storeId = Mage::app ()->getStore ()->getStoreId ();
                $newProduct = Mage::helper ( 'marketplace/product' )->editassignproduct ( $productData, $selectedProductId );
                Mage::app ()->setCurrentStore ( $storeId );
                
                /**
                 * Function for edit downloadable product sample and link data
                 * Check the type id of new product is 'downloadable'
                 * and store id has been set already
                 */
                if ($newProduct->getTypeId () == 'downloadable' && isset ( $storeId )) {
                    $this->addDownloadableProductData ( $newProduct->getId (), $storeId );
                }
                /**
                 * Success message redirect to manage product page
                 */
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Your product details are updated successfully.' ) );
                $this->_redirect ( 'marketplace/sellerproduct/manageassignproduct' );
            } catch ( Mage_Core_Exception $e ) {
                /**
                 * Error message redirect to create new product page
                 */
                $errorMsg = $this->__ ( $e->getMessage () );
                Mage::getSingleton ( 'core/session' )->addError ( $errorMsg );
                $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
            } catch ( Exception $e ) {
                $errorMsg = $this->__ ( $e->getMessage () );
                Mage::getSingleton ( 'core/session' )->addError ( $errorMsg );
                $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
            }
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Please enter all required fields' ) );
            $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
        }
    }
    /**
     * Check whether assign product or not
     *
     * @param number $selectedProductId            
     */
    public function checkAssignProductOrNot($selectedProductId) {
        if ($selectedProductId == '') {
            $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
            return;
        }
        $productSellerId = Mage::getModel ( 'catalog/product' )->load ( $selectedProductId )->getSellerId ();
        if ($productSellerId != Mage::getSingleton ( 'customer/session' )->getCustomerId ()) {
            $this->_redirect ( 'marketplace/sellerproduct/assignproduct' );
            return;
        }
    }
    /**
     * Check whether seller or not
     */
    public function checkWhetherSellerOrNot() {
        /**
         * Initilize customer and seller group id
         */
        $customerGroupId = $sellerGroupId = $customerStatus = '';
        $customerGroupId = Mage::getSingleton ( 'customer/session' )->getCustomerGroupId ();
        $sellerGroupId = Mage::helper ( 'marketplace' )->getGroupId ();
        /**
         * Check the customer is not logged in currently
         * and group id of the customer is not equal to the seller group id
         */
        if (! Mage::getSingleton ( 'customer/session' )->isLoggedIn () && $customerGroupId != $sellerGroupId) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'You must have a Seller Account to access this page' ) );
            $this->_redirect ( 'marketplace/seller/login' );
            return;
        }
        $customerStatus = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getCustomerstatus ();
 if ($customerStatus != 1) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Admin Approval is required. Please wait until admin confirms your Seller Account' ) );
            $this->_redirect ( 'marketplace/seller/login' );
            return;
        }
    }
    
    /**
     * Save Downloadable Products
     *
     * Passed the downloadable product id to save files
     *
     * @param int $downloadProductId
     *            Passed the store id to save files
     * @param int $store            
     *
     * @return void
     */
    public function addDownloadableProductData($downloadProductId, $store) {
  $assignSampleTpath = $assginLinkTpath = $assignSlinkTpath = array ();
        $uploadsData = new Zend_File_Transfer_Adapter_Http ();
        $filesDataArray = $uploadsData->getFileInfo ();
        foreach ( $filesDataArray as $key => $result ) {
            $downloadData = Mage::getModel ( 'marketplace/download' )->prepareDownloadProductData ( $filesDataArray, $key, $result );
            if (! empty ( $downloadData ['sample_tpath'] )) {
                $sampleNo = substr ( $key, 7 );
                $assignSampleTpath [$sampleNo] = $downloadData ['sample_tpath'];
            }
            if (! empty ( $downloadData ['link_tpath'] )) {
                $sampleNo = substr ( $key, 6 );
                $assginLinkTpath [$sampleNo] = $downloadData ['link_tpath'];
            }
            if (! empty ( $downloadData ['slink_tpath'] )) {
                $sampleNo = substr ( $key, 9 );
                $assignSlinkTpath [$sampleNo] = $downloadData ['slink_tpath'];
            }
        }
 $assignDownloadableSample = Mage::getModel ( 'downloadable/sample' )->getCollection ()->addProductToFilter ( $downloadProductId )->addTitleToResult ( $store );
 Mage::getModel ( 'marketplace/download' )->deleteDownloadableSample ( $assignDownloadableSample );
 $downloadableLink = Mage::getModel ( 'downloadable/link' )->getCollection ()->addProductToFilter ( $downloadProductId )->addTitleToResult ( $store );
  Mage::getModel ( 'marketplace/download' )->deleteDownloadableLinks ( $downloadableLink );
  $downloadableData = $this->getRequest ()->getPost ( 'downloadable' );
        try {
  Mage::getModel ( 'marketplace/download' )->saveDownLoadProductSample ( $downloadableData, $downloadProductId, $assignSampleTpath, $store );
   if (isset ( $downloadableData ['link'] )) {
                Mage::getModel ( 'marketplace/download' )->saveDownLoadProductLink ( $downloadableData, $downloadProductId, $assginLinkTpath, $assignSlinkTpath, $store );
            }
        } catch ( Exception $e ) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
        }
    }
}
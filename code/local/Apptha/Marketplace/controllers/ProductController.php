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
 * This file is used to add/edit seller products
 */
class Apptha_Marketplace_ProductController extends Mage_Core_Controller_Front_Action {
    
    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession() {
        return Mage::getSingleton ( 'customer/session' );
    }
    
    /**
     * Load phtml file layout
     *
     * @return void
     */
    public function indexAction() {
        if (! $this->_getSession ()->isLoggedIn ()) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'You must have a Seller Account to access this page' ) );
            $this->_redirect ( 'marketplace/seller/login' );
            return;
        }
        $this->loadLayout ();
        $this->renderLayout ();
    }
    
    /**
     * Add New Products Form
     *
     * @return void
     */
    public function newAction() {
        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        /**
         * Initilize customer and seller group id
         */
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        
        $this->loadLayout ();
        $this->renderLayout ();
    }
    
    /**
     * Save New Products
     *
     * @return void
     */
    public function newpostAction() {
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        $this->checkWhetherSellerOrNot ();
        Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
        $set = $setBase = $type = $store = $sellerId = $isInStock = '';
        $setThumb = $setSmall = '';
        /**
         * Getting product type, set, setbase, store, group id and product
         */
        $setThumb = $this->getRequest ()->getPost ( 'setthumb' );
        $setSmall = $this->getRequest ()->getPost ( 'setsmall' );

        $type = $this->getRequest ()->getPost ( 'type' );
        $set = $this->getRequest ()->getPost ( 'set' );
        $setBase = $this->getRequest ()->getPost ( 'setbase' );
        $store = $this->getRequest ()->getPost ( 'store' );
        $seller_sku = $this->getRequest ()->getPost ( 'supplier_sku' );
        $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $groupId = Mage::helper ( 'marketplace' )->getGroupId ();
        $productData = $this->getRequest ()->getPost ( 'product' );
        $skuproduct_id = '';
        $skuproduct_id = Mage::getModel ( 'marketplace/product' )->checkWhetherSkuExistOrNot ( $productData );
        if ($skuproduct_id == 0) {
            /**
             * Getting product categories from category_ids array
             */
            $categoryIds = $this->getRequest ()->getPost ( 'category_ids' );

            $checkRequiredForProductSave = Mage::helper ( 'marketplace/market' )->checkRequiredForProductSave ( $productData );

            if ($checkRequiredForProductSave == 1 && isset ( $productData ['price'] ) && isset ( $productData ['stock_data'] ['qty'] ) && ! empty ( $type )) {

                /**
                 * Getting instance for catalog product collection
                 */
                $product = Mage::getModel ( 'catalog/product' );
                $imagesPath = array ();
                $uploadsData = new Zend_File_Transfer_Adapter_Http ();
                $filesDataArray = $uploadsData->getFileInfo ();
                $imagesPath = Mage::getModel ( 'marketplace/product' )->getProductImagePath ( $filesDataArray );
                $product = Mage::getModel ( 'marketplace/product' )->setProductInfo ( $product, $set, $type, $categoryIds, $sellerId, $groupId, $imagesPath );

                $productData = Mage::getModel ( 'marketplace/product' )->getProductDataArray ( $productData, $type );
                /**
                 * Assign configurable product data
                 */
                $attributeIds = $this->getRequest ()->getPost ( 'attributes' );
                Mage::getModel ( 'marketplace/product' )->assignConfigurableProductData ( $attributeIds, $type, $product );
                $product->addData ( $productData );

                /**
                 * Initialize dispatch event for product prepare
                 */
                Mage::dispatchEvent ( 'catalog_product_prepare_save', array (
                        'product' => $product,
                        'request' => $this->getRequest () 
                ) );

                /**
                 * Saving new product
                 */
                try {
                    $product->save ();
                    $product_id = $product->getId();

                    Mage::getModel ( 'marketplace/product' )->setConfigurableProductStockData ( $type, $product, $productData, $isInStock );
                    Mage::getModel ( 'marketplace/product' )->setBaseImageForProduct ( $product_id, $store, $setBase, $productData, 'new' ,$setThumb,$setSmall);
                    Mage::getModel ( 'marketplace/product' )->deleteTempImageFiles ( $imagesPath );
                    /**
                     * Function for adding downloadable product sample and link data
                     */
                    $downloadproduct_id = $product->getId ();
                    $this->assignDataForDownloadableProduct ( $type, $downloadproduct_id, $store );
                    $msg = Mage::getModel ( 'marketplace/product' )->getMessageForNewProductAdd ();
                    Mage::getSingleton ( 'core/session' )->addSuccess ( $msg );
                    Mage::helper ( 'marketplace/product' )->sentEmailToAdmin ( $sellerId, $product );
                    Mage::app ()->setCurrentStore ( Mage::app ()->getStore ()->getStoreId () );
                    $this->redirectToConfigurablePage ( $type, $product_id, $set );
                    $product_new = Mage::getModel('catalog/product')->load($product->getId());
                    $product_new->setData('seller_store_name_attr',$sellerId);
                    $product_new->setData('supplier_sku',$seller_sku);
                    $product_new->save();
                } catch ( Mage_Core_Exception $e ) {
                    /**
                     * Error message redirect to create new product page
                     */
                    Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
                    $this->_redirect ( 'marketplace/sellerproduct/create/' );
                } catch ( Exception $e ) {
                    /**
                     * Error message redirect to create new product page
                     */
                    Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
                    $this->_redirect ( 'marketplace/sellerproduct/create/' );
                }
            } else {
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Please enter all required fields' ) );
                if ($type == 'configurable') {
                    $this->_redirect ( 'marketplace/sellerproduct/selectattributes/', array (
                        'set' => $set
                    ) );
                }
               
                $this->_redirect ( 'marketplace/sellerproduct/create' );
            }
        } else {
            /**
             * Error message redirect to create new product page
             */
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'SKU Not Available' ) );
            $this->_redirect ( 'marketplace/sellerproduct/create/' );
        }
    }
    
    /**
     * Manage Seller Products
     *
     * @return void
     */
    public function manageAction() {
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

    public function edit_priceAction() {
        $product_id = (int) $this->getRequest()->getParam("product_id");

        $price = (float) $this->getRequest()->getParam("price");

        Mage::helper('marketplace')->checkMarketplaceKey ();
        $this->checkWhetherSellerOrNot();

        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        $collection = Mage::getModel('catalog/product')->load($product_id);
        $sellerId = $collection->getSellerId ();

        if ($customerId != $sellerId) {
            echo $this->__("You don't have enough permission to edit this product details.");
        }
        $customerStatus = Mage::getSingleton('customer/session')->getCustomer()->getCustomerstatus();

        if ($customerStatus != 1) {
            echo $this->__('Admin Approval is required. Please wait until admin confirm your Seller Account');
        }

        $product = Mage::getModel('catalog/product')->load($product_id);
        
        $product->setPrice($price);
        try {
            $product->save();
        } catch (Mage_Core_Exception $e) {
            echo $this->__($e->getMessage());
        } catch (Exception $e) {
            echo $this->__($e->getMessage());
        }

        echo Mage::app()->getLocale()->currency(Mage::app()->getStore()->getBaseCurrencyCode())->getSymbol() . "" . $price;
    }

    public function edit_qtyAction() {
        $product_id = (int) $this->getRequest()->getParam("product_id");
        $qty = (float) $this->getRequest()->getParam("qty");
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id);
        if ($stock->getId() > 0 and $stock->getManageStock()) {
            $stock->setQty($qty);
            $stock->setIsInStock((int)($qty > 0));
            $stock->save();
            echo $qty;
        } else {
            echo 0;
        }
    }
    

    public function edit_attributeAction() {
        $product_id = (int) $this->getRequest()->getParam("product_id");

        $attribute_type = $this->getRequest()->getParam("attribute_type");

        $attribute_value = (int) $this->getRequest()->getParam("attribute_value");

        $base_id = (int) $this->getRequest()->getParam("base_id");
        
        Mage::helper('marketplace')->checkMarketplaceKey ();
        $this->checkWhetherSellerOrNot();

        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        $collection = Mage::getModel('catalog/product')->load($product_id);
        $sellerId = $collection->getSellerId();


        if ($customerId != $sellerId) {
            echo $this->__("You don't have enough permission to edit this product details.");
        }
        $customerStatus = Mage::getSingleton('customer/session')->getCustomer()->getCustomerstatus();

        if ($customerStatus != 1) {
            echo $this->__('Admin Approval is required. Please wait until admin confirm your Seller Account');
        }

        if ($attribute_type == 'color') {
            $collection->setColor($attribute_value);
            $collection->getResource()->saveAttribute($collection, "color");
        } elseif ($attribute_type == 'size') {
            $collection->setSize($attribute_value);
            $collection->getResource()->saveAttribute($collection, "size");
        }

        $p = Mage::getModel('catalog/product')->load($base_id);
        $attributes = $p->getTypeInstance()->getConfigurableAttributesAsArray();    
        $name = "";
        foreach ($attributes as $attribute) {
            $name .= $collection->getAttributeText($attribute['attribute_code']) . "-";
        }
        $name = trim($name, "-");
        echo $name;
        
        $collection->setName($name);
        try {
            $collection->save();
        } catch (Mage_Core_Exception $e) {
            echo $this->__($e->getMessage());
        } catch (Exception $e) {
            echo $this->__($e->getMessage());
        }
    }


    /**
     * Edit Existing Products
     *
     * @return void
     */
    public function editAction() {

        // Mage::getSingleton('core/session')->addError($this->__('Admin Approval is required. Please wait until admin confirm your Seller Account'));
        // $url = Mage::getUrl('marketplace/product/manage/');
        // Mage::app()->getResponse()->setRedirect($url);

        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        
        /**
         * Initilize product id , customer id and seller id
         */
        $product_id = ( int ) $this->getRequest ()->getParam ( 'id' );
        $customerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $collection = Mage::getModel ( 'catalog/product' )->load ( $product_id );
        $sellerId = $collection->getSellerId ();
        /**
         * Checking for edit permission
         */
        if ($customerId != $sellerId) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "You don't have enough permission to edit this product details." ) );
            $this->_redirect ( 'marketplace/product/manage' );
            return;
        }
        $customerStatus = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getCustomerstatus ();
        /**
         * Checking whether customer approved or not
         */
        if ($customerStatus != 1) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Admin Approval is required. Please wait until admin confirm your Seller Account' ) );
            $this->_redirect ( 'marketplace/seller/login' );
            return;
        }

        $this->loadLayout ();
        $this->renderLayout ();
    }
    
    /*
     * get child prdocuts of configurable product on manage products page for seller dashboard
     * 
    */
    public function get_childAction() {

        /**
         * Check license key
         */
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        
        /**
         * Initilize product id , customer id and seller id
         */
        $product_id = ( int ) $this->getRequest ()->getParam ( 'product_id' );
        $customerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
        $collection = Mage::getModel ( 'catalog/product' )->load ( $product_id );
        $sellerId = $collection->getSellerId ();
        /**
         * Checking for edit permission
         */
        if ($customerId != $sellerId) {
            $error = $this->__ ( "You don't have enough permission to edit this product details." );
        }
        $customerStatus = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getCustomerstatus ();
        /**
         * Checking whether customer approved or not
         */
        if ($customerStatus != 1) {
            $error = $this->__ ( 'Admin Approval is required. Please wait until admin confirm your Seller Account' );
        }

        /*
         * get product id from params
         * 
        */
        if ($error) {
            echo $error;
        } else {
            if ($collection->getTypeId() == "configurable") { ?>
                <table width="100%" cellpadding="0" cellspacing="0" class="variation-tab">
                    <col width="1" />
                    <col width="1" />
                    <col />
                    <col width="1" />
                    <col width="1" />
                    <col width="1" />
                    <thead>
                      <tr>
                        <td style="text-align: center; padding: 8px 10px !important">Name</td>
                        <td style="text-align: center; padding: 8px 10px !important">Quantity</td>
                        <td style="text-align: center; padding: 8px 10px !important">Price</td>
                        <td style="text-align: center; padding: 8px 10px !important">Action</td>
                      </tr>
                    </thead>
                    <tbody>
                <?php
                $attributes = $collection->getTypeInstance()->getConfigurableAttributesAsArray(); 
                $variations = $collection->getTypeInstance()->getUsedProductIds();
                foreach($variations as $variation) {
                    $_variation = Mage::getModel('catalog/product')->load($variation);
                ?>
                <tr>
                  <td style="text-align: left; padding: 8px 10px !important"><?php echo $_variation->getName();if(round($_variation->getStockItem()->getQty()) <= 1):?> 
                  <img class='lowStock' src="<?php echo Mage::getModel('core/design_package')->getSkinUrl() . 'marketplace/images/down-circular-xxl.png' ?>"  title='low stock'> 
                  <?php endif; ?></td>
                  <td style="text-align: center; padding: 8px 10px !important"><input class="newqtyv" data-id="<?php echo $_variation->getId() ?>" style="width: 100%; text-align: center" type="number" on="updateQtyVar(this)" value="<?php echo round($_variation->getStockItem()->getQty()) ?>" /></td>
                  <td style="text-align: center; padding: 8px 10px !important"><input class="newpricev" data-id="<?php echo $_variation->getId() ?>" style="width: 100%; text-align: center" type="number" on="updatePriceVar(this)" value="<?php echo round($_variation->getPrice(), 2) ?>" /></td>
                  <td style="text-align: center; padding: 8px 10px !important">

                    <em>
                        <span class="nobr">
                            <a href="<?php echo Mage::getUrl('marketplace/sellerproduct/configurable/',array('id' => $product_id,'sp'=>$_variation->getId(),'set'=>$set)); ?>" > 
                                <img src="<?php echo Mage::getModel('core/design_package')->getSkinUrl() . 'marketplace/images/edit.png' ?>" alt="" title="<?php echo $this->__('Edit') ?>"/>
                            </a>
                            <?php if( Mage::getStoreConfig('marketplace/product/show_delete_action') ){ ?>
                            <a href="<?php echo Mage::getUrl('marketplace/product/delete/',array('id' => $_variation->getId())); ?>" onclick="return confirm('<?php echo $this->__('Are you sure want to delete?'); ?>');" > 
                                <img src="<?php echo Mage::getModel('core/design_package')->getSkinUrl() . 'marketplace/images/delete.png' ?>" alt="" title="<?php echo $this->__('Delete') ?>"/>
                            </a>
                            <?php } ?>
                            <?php if( Mage::getStoreConfig('marketplace/product/show_disable_action') ){ ?>
                                <a href="<?php echo Mage::getUrl('marketplace/product/disable/',array('id' => $_variation->getId())); ?>" onclick="return confirm('<?php echo $this->__('Are you sure want to Disable?'); ?>');" >
                                <img src="<?php echo Mage::getModel('core/design_package')->getSkinUrl() . 'marketplace/images/disable.png' ?>" alt="" title="<?php echo $this->__('Disable') ?>"/>
                            </a>
                            <?php } ?>
                        </span>
                    </em>

                  </td>
                </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
                <?php

            }
        }

    }

    /**
     * Save Edited Products
     *
     * @return void
     */
    public function editpostAction() {
        $manageProductUrl = Mage::getUrl('*/*/manage/');//Get Frontend Manage Product Url
        /**
         * Check license key
         */
        $isInStock = '';
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        $product_id = $name = $description = $shortDescription = $price = $store = $sku = $storeId = '';
        $categoryIds = $deleteImages = array ();
        $type = $this->getRequest ()->getPost ( 'type' );
        $productData = $this->getRequest ()->getPost ( 'product' );
        $product_id = $this->getRequest ()->getPost ( 'product_id' );
        $storeId = $this->getRequest ()->getPost ( 'store_id' );
        $categoryIds = $this->getRequest ()->getPost ( 'category_ids' );
        $store = Mage::app ()->getStore ()->getId ();
        $name = $productData ['name'];
        $sku = $productData ['sku'];
        $suppliersku = $this->getRequest ()->getPost ( 'supplier_sku' );
        $description = $productData ['description'];
        $shortDescription = $productData ['short_description'];
        $price = $productData ['price'];
        $deleteImages = $this->getRequest ()->getPost ( 'deleteimages' );
        $baseImage = $this->getRequest ()->getPost ( 'baseimage' );
        $thumbImage = $this->getRequest ()->getPost ( 'thumbimage' );
        $smallImage = $this->getRequest ()->getPost ( 'smallimage' );

        $checkingForProductRequiredFields = Mage::helper ( 'marketplace/market' )->checkingForProductRequiredFields ( $sku, $product_id, $name, $description );
        if ($checkingForProductRequiredFields == 1 && ! empty ( $shortDescription ) && isset ( $price ) && ! empty ( $type )) {
            $product = Mage::getModel ( 'catalog/product' )->load ( $product_id );
                /**
                 * if Seller edit his product then allways his status
                 */
                $qty_org = $product->getStockItem()->getQty();
                $qty_cahnge = $productData ['stock_data'] ['qty'];
                $product->setData('supplier_sku',$suppliersku);
        /*commenting the checks which were updating the status */
            
            //     if(($sku != $product->sku || $name!= $product->name || $description != $product->description || $shortDescription != $product->short_description 
            //     || $price != $product->price || $type != $product->type_id) && $qty_org != $qty_cahnge ):
                     
            //          $product->setStatus('2');
            //          $product->setSellerProductStatus('1024');
            //     elseif(($sku != $product->sku || $name!= $product->name || $description != $product->description || $shortDescription != $product->short_description 
            //     || $price != $product->price || $type != $product->type_id) && $qty_org == $qty_cahnge):

            //           $product->setStatus('2');
            //           $product->setSellerProductStatus('1024');

            //     elseif(($sku == $product->sku && $name == $product->name && $description == $product->description && $shortDescription == $product->short_description 
            //     && $price == $product->price && $type == $product->type_id) && $qty_org != $qty_cahnge ):
                
            //     $status = $product->getStatus();
            //     $product_status =  $product->getSellerProductStatus();
            //     if(($qty_org >= $qty_cahnge || $qty_org <= $qty_cahnge) && $qty_cahnge != 0):
            //         $product->setStatus($status);
            //         $product->setSellerProductStatus($product_status);
            //     elseif($qty_cahnge == 0):
            //         $product->setStatus($status);
            //         $product->setSellerProductStatus($product_status);
            //     else:
            //        $product->setStatus('2');
            //        $product->setSellerProductStatus('1024');
            //     endif;
            // endif;
            /*---------------------------*/            
            if (empty ( $productData ['weight'] )) {
                $productData ['weight'] = 0;
            }

            $product = Mage::getModel ( 'marketplace/product' )->setProductDataForUpdate ( $product, $categoryIds, $productData, $type, $isInStock );
            $imagesPath = array ();
            $uploadsData = new Zend_File_Transfer_Adapter_Http ();
            $filesDataArray = $uploadsData->getFileInfo ();
            $imagesPath = Mage::getModel ( 'marketplace/product' )->getProductImagePath ( $filesDataArray );
            /**
             * Adding Product images
             */
            $product = Mage::getModel ( 'marketplace/product' )->setImagesForProduct ( $product, $imagesPath );
            try {
                $product->save ();
                /**
                 * Removing product images
                 */
                Mage::getModel ( 'marketplace/product' )->deleteProductImagesForEdit ( $deleteImages, $product_id, $baseImage );

                /**
                 * Set product images
                 */
                Mage::getModel ( 'marketplace/product' )->setProductImagesforProduct ( $baseImage, $product_id, $store, $product, $productData,$smallImage,$thumbImage );

                /**
                 * Checking whether image or not
                 */
                Mage::getModel ( 'marketplace/product' )->deleteTempImageFiles ( $imagesPath );
                /**
                 * Function for adding downloadable product sample and link data
                 */
                $downloadproduct_id = $product->getId ();
                $this->assignDataForDownloadableProduct ( $type, $downloadproduct_id, $store );
                Mage::app ()->setCurrentStore ( $store );
                /**
                 * Success message redirect to manage product page
                 */
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( 'Your product details are updated successfully.' ) );
                Mage::app()->getFrontController()->getResponse()->setRedirect($manageProductUrl)->sendResponse(); //Redirect only to frontend controller url.
            } catch ( Mage_Core_Exception $e ) {
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
                $this->_redirect ( 'marketplace/product/edit/id/' . $product_id );
            } catch ( Exception $e ) {
                Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
                $this->_redirect ( 'marketplace/product/edit/id/' . $product_id );
            }
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Please enter all required fields' ) );
            $this->_redirect ( 'marketplace/product/edit/id/' . $product_id );
        }
    }

    /*
     * Disable Seller Products
     *
     * 13-04-2017
     * */

    public function disableAction() {
        /**
         * check license key
         */
        $entity_id = '';
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();

        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();

        $entity_id = ( int ) $this->getRequest ()->getParam ( 'id' );
        $productSellerId = Mage::getModel ( 'catalog/product' )->load ( $entity_id )->getSellerId ();

        if (Mage::getSingleton ( 'customer/session' )->getCustomerId () == $productSellerId && Mage::getSingleton ( "customer/session" )->isLoggedIn ()) {
            /**
             * Checking whether customer approved or not
             */
            $this->loadLayout ();
            $this->renderLayout ();

            $currentStoreId = Mage::app()->getStore()->getStoreId();
            Mage::register ( 'isSecureArea', true );
            Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
            Mage::getModel('catalog/product_status')->updateProductStatus($entity_id, Mage::app()->getStore()->getStoreId() , Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
            Mage::app ()->setCurrentStore ( $currentStoreId );
            /**
             * un set secure admin area
             */
            Mage::unregister ( 'isSecureArea' );
            Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Product Disabled Successfully" ) );
            $pId = $this->getRequest ()->getParam ( 'pid' );
            $set = $this->getRequest ()->getParam ( 'set' );
            if (! empty ( $pId ) && ! empty ( $set )) {
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $pId,
                    'set' => $set
                ) );
                return;
            }
            $isAssign = $this->getRequest ()->getParam ( 'is_assign' );
            if (! empty ( $isAssign )) {
                $this->_redirect ( 'marketplace/sellerproduct/manageassignproduct/' );
                return;
            }
            $this->_redirect ( '*/product/manage/' );
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "You don't have enough permission to Disabled this product details." ) );
            $this->_redirect ( 'marketplace/seller/login' );
        }
    }

    /**
     * Delete Seller Products
     *
     * @return void
     */
    public function deleteAction() {
        /**
         * check license key
         */
        $entity_id = '';
        Mage::helper ( 'marketplace' )->checkMarketplaceKey ();
        
        /**
         * Check whether seller or not
         */
        $this->checkWhetherSellerOrNot ();
        
        $entity_id = ( int ) $this->getRequest ()->getParam ( 'id' );
        $productSellerId = Mage::getModel ( 'catalog/product' )->load ( $entity_id )->getSellerId ();
        
        if (Mage::getSingleton ( 'customer/session' )->getCustomerId () == $productSellerId && Mage::getSingleton ( "customer/session" )->isLoggedIn ()) {
            /**
             * Checking whether customer approved or not
             */
            $this->loadLayout ();
            $this->renderLayout ();
            
            Mage::register ( 'isSecureArea', true );
            Mage::helper ( 'marketplace/general' )->changeAssignProductId( $entity_id );
            Mage::getModel ( 'catalog/product' )->setId ( $entity_id )->delete ();
            
            /**
             * un set secure admin area
             */
            Mage::unregister ( 'isSecureArea' );
            Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Product Deleted Successfully" ) );
            $pId = $this->getRequest ()->getParam ( 'pid' );
            $set = $this->getRequest ()->getParam ( 'set' );
            if (! empty ( $pId ) && ! empty ( $set )) {
                $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                        'id' => $pId,
                        'set' => $set 
                ) );
                return;
            }
            $isAssign = $this->getRequest ()->getParam ( 'is_assign' );
            if (! empty ( $isAssign )) {
                $this->_redirect ( 'marketplace/sellerproduct/manageassignproduct/' );
                return;
            }
            $this->_redirect ( '*/product/manage/' );
        } else {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "You don't have enough permission to delete this product details." ) );
            $this->_redirect ( 'marketplace/seller/login' );
        }
    }
    
    /**
     * Manage Deals products by seller
     *
     * @return void
     */
    public function managedealsAction() {
        $this->loadLayout ();
        $this->renderLayout ();
    }
    
    public function edit_multiAction() {

        $multi_submit = $this->getRequest ()->getPost ( 'multi_submit' );
        $entityIds = $this->getRequest ()->getParam ( 'selected_simple_product_ids' );
        $idx = $this->getRequest()->getParam('selected_simple_product_ids');
        $delete = $this->getRequest ()->getPost ( 'multi' );

        /**
         * Check if submit buttom submitted.
        */
        if (($delete && (count($entityIds) <= 0)) || (!$delete && (count($entityIds) > 0)) || (!$delete && (count($entityIds) <= 0))) {
           Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "Please select a product and action to update status" ) );
           $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
           Mage::app()->getResponse()->setRedirect($url);
        }

        if ($delete && (count($entityIds) > 0)) {
           if (count ( $entityIds ) > 0 && $delete == 'delete') {
                foreach ( $entityIds as $entityIdData ) {
                     Mage::register ( 'isSecureArea', true );
                     Mage::helper ( 'marketplace/marketplace' )->deleteProduct ( $entityIdData );
                     $this->getRequest()->setPost('selected_simple_product_ids',null);
                     Mage::unregister ( 'isSecureArea' );
                }
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Selected Products are Deleted Successfully" ) );
                $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
                Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
           }

            /*----------------Disable ----------------*/
            if (count($entityIds) > 0 && $delete == 'disable') {
                $currentStoreId = Mage::app()->getStore()->getStoreId();
                Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
                foreach ($entityIds as $entityIdData) {
                    Mage::register('isSecureArea', true);
                    Mage::getModel('catalog/product_status')->updateProductStatus($entityIdData, Mage::app()->getStore()->getStoreId() , Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                    Mage::unregister('isSecureArea');
                    $this->getRequest()->setPost('id', null);
                }
                Mage::app ()->setCurrentStore ( $currentStoreId );

                Mage::getSingleton('core/session')
                    ->addSuccess($this->__("selected Products has been disabled Successfully."));
                $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }

           /*--------------------- END -------------------------*/
           /*----------------Sold Out ----------------*/
           if (count ( $entityIds ) > 0 && $delete == 'soldout') {
                foreach ( $entityIds as $entityIdData ) {
                     Mage::register ( 'isSecureArea', true );
                     Mage::helper ( 'marketplace/marketplace' )->outOfStock ( $entityIdData );
                     Mage::unregister ( 'isSecureArea' );
                     $this->getRequest()->setPost('selected_simple_product_ids',null);
                }
                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Selected products status has been sold Out Successfully and Un Publish" ) );
                $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
                Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
           }

           /*--------------------- END -------------------------*/

           /*----------------paused ----------------*/
           if (count ( $entityIds ) > 0 && $delete == 'paused') {
                foreach ( $entityIds as $entityIdData ) {
                     Mage::register ( 'isSecureArea', true );
                     Mage::helper ( 'marketplace/marketplace' )->pausedStock ( $entityIdData );
                     Mage::unregister ( 'isSecureArea' );
                     $this->getRequest()->setPost('selected_simple_product_ids',null);
                }

                Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "selected Products status has been Paused Successfully and Un Publish" ) );
                $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
                Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
           }

           /*--------------------- END -------------------------*/
        }
    }

    /**
     * Manage Deals products by seller
     *
     * @return void
     */
    public function deletesingledealAction() {
        $product_id = $this->getRequest ()->getParam ( 'id' );
        Mage::getModel ( 'catalog/product' )->load ( $product_id )->setSpecialFromDate ( '' )->setSpecialToDate ( '' )->setSpecialPrice ( '' )->save ();
        Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "Product Deal Deleted Successfully" ) );
        $this->_redirect ( '*/product/managedeals/' );
        return true;
    }
    
    /**
     * Function to check availability of sku
     *
     * @return int
     */
    public function checkskuAction() {
        $inputSku = trim ( $this->getRequest ()->getParam ( 'sku' ) );
        $collection = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToFilter ( 'sku', $inputSku );
        $count = count ( $collection );
        echo $count;
        return true;
    }

    public function checksupplierskuAction() {
        $inputSku = trim ( $this->getRequest ()->getParam ( 'sku' ) );
        $collection = Mage::getModel ( 'catalog/product' )->getCollection ()->addAttributeToFilter ( 'supplier_sku', $inputSku );
        $count = count ( $collection );
        echo $count;
        return true;
    }
    
    /**
     * Function to display the view all compare price products
     *
     * @return void
     */
    public function comparesellerpriceAction() {
        $this->loadLayout ();
        $this->getLayout ()->getBlock ( 'head' )->setTitle ( $this->__ ( 'All Sellers' ) );
        $this->renderLayout ();
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
        $customerStatus = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getCustomerstatus ();
        if (! $this->_getSession ()->isLoggedIn () && $customerGroupId != $sellerGroupId) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'You must have a Seller Account to access this page' ) );
            $this->_redirect ( 'marketplace/seller/login' );
            return;
        }
        /**
         * Checking whether customer approved or not
         */
        if ($customerStatus != 1) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( 'Admin Approval is required. Please wait until admin confirms your Seller Account' ) );
            $this->_redirect ( 'marketplace/seller/login' );
            return;
        }
    }
    
    /**
     * Assign data to downloadable product
     *
     * @param string $type            
     * @param number $downloadproduct_id            
     * @param number $store            
     */
    public function assignDataForDownloadableProduct($type, $downloadproduct_id, $store) {
        if ($type == 'downloadable' && isset ( $downloadproduct_id ) && isset ( $store )) {
            $this->addDownloadableProductData ( $downloadproduct_id, $store );
        }
    }
    /**
     * Redirect to configurable page
     *
     * @param string $type            
     * @return boolean
     */
    public function redirectToConfigurablePage($type, $product_id, $set) {
        if ($type == 'configurable') {
            $this->_redirect ( 'marketplace/sellerproduct/configurable/', array (
                    'id' => $product_id,
                    'set' => $set 
            ) );
            return;
        } else {
            $this->_redirect ( 'marketplace/product/manage/' );
            return;
        }
    }
    
    /**
     * Save Downloadable Products
     *
     * Passed the downloadable product id to save files
     *
     * @param int $downloadproduct_id
     *            Passed the store id to save files
     * @param int $store            
     *
     * @return void
     */
    public function addDownloadableProductData($downloadproduct_id, $store) {
        /**
         * Initilize downloadable product sample and link files
         */
        $sampleTpath = $linkTpath = $slinkTpath = array ();
        $uploadsData = new Zend_File_Transfer_Adapter_Http ();
        $filesDataArray = $uploadsData->getFileInfo ();
        foreach ( $filesDataArray as $key => $result ) {
            $downloadData = Mage::getModel ( 'marketplace/download' )->prepareDownloadProductData ( $filesDataArray, $key, $result );
            if (! empty ( $downloadData ['sample_tpath'] )) {
                $sampleNo = substr ( $key, 7 );
                $sampleTpath [$sampleNo] = $downloadData ['sample_tpath'];
            }
            if (! empty ( $downloadData ['link_tpath'] )) {
                $sampleNo = substr ( $key, 6 );
                $linkTpath [$sampleNo] = $downloadData ['link_tpath'];
            }
            if (! empty ( $downloadData ['slink_tpath'] )) {
                $sampleNo = substr ( $key, 9 );
                $slinkTpath [$sampleNo] = $downloadData ['slink_tpath'];
            }
        }

        /**
         * Getting downloadable product sample collection
         */
        $downloadableSample = Mage::getModel ( 'downloadable/sample' )->getCollection ()->addProductToFilter ( $downloadproduct_id )->addTitleToResult ( $store );

        Mage::getModel ( 'marketplace/download' )->deleteDownloadableSample ( $downloadableSample );

        /**
         * Getting downloadable product link collection
         */
        $downloadableLink = Mage::getModel ( 'downloadable/link' )->getCollection ()->addProductToFilter ( $downloadproduct_id )->addTitleToResult ( $store );

        Mage::getModel ( 'marketplace/download' )->deleteDownloadableLinks ( $downloadableLink );

        /**
         * Initilize Downloadable product data
         */
        $downloadableData = $this->getRequest ()->getPost ( 'downloadable' );
        try {
            /**
             * Storing Downloadable product sample data
             */
            Mage::getModel ( 'marketplace/download' )->saveDownLoadProductSample ( $downloadableData, $downloadproduct_id, $sampleTpath, $store );

            /**
             * Storing Downloadable product sample data
             */
            if (isset ( $downloadableData ['link'] )) {
                Mage::getModel ( 'marketplace/download' )->saveDownLoadProductLink ( $downloadableData, $downloadproduct_id, $linkTpath, $slinkTpath, $store );
            }
        } catch ( Exception $e ) {
            Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( $e->getMessage () ) );
        }
    }
    /*
     * Saroop
     * Function edited because we want product detail into Product datalayer dynamically and need to change datalayer script when product attribute change.
     * For this we change flow from direct echo type to return type.
     * My Code at line 113 to 233
     * 
     * */
    public function getproducturlAction()
    {
        $cacheId = "productdetailsimpleajax_".$this->getRequest()->getParam('id').Mage::app()->getStore()->getStoreId();
        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setCustomKey($cacheId);
        $data = $fpcModel->getData();
        if (!empty($data)) {
            echo json_decode($data,true);
            die;
        }
        if( empty( $this->getRequest()->getParam('responsetype')) ) {
            $product = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('sku', 'color', 'size', 'size_shown_in_image', 'material', 'measurement', 'care_instructions'))
                ->addAttributeToFilter('entity_id', $this->getRequest()->getParam('id'))
                ->getFirstItem();

            $_session = Mage::getSingleton("core/session", array("name" => "frontend"));
            $_size = $_session->getData("sizeGuideModal");
            $sku = $product->getSku();
            if ($_size == "#women_shoes" || $_size == "#men_shoes" || $_size == "#kids_shoes" || $_size == "#kids_shoes_girls_infant"
                || $_size == "#kids_shoes_boys_infant" || $_size == '#kids_shoes_girls_junior' || $_size == '#kids_shoes_boys_junior' ||
                $_size == '#kids_shoes_girls_toddler' || $_size == '#kids_shoes_boys_toddler'
            ) {
                $var = "In EU";
            } elseif ($_size == '#women_clothing' || $_size == '#men_clothing' || $_size == '#kids_clothing' ||
                $_size == '#kids_clothing_boys_teenboys' || $_size == '#kids_clothing_girls_teengirls' ||
                $_size == '#kids_clothing_children' || $_size == '#kids_clothing_babies'
            ) {
                $var = "In International";
            }
            if (!empty($sku)) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('sku') . "</th>";
                echo "<td class='product-variant-class'>" . $this->__($sku) . "</td>";
                echo "</tr>";
            }
            if ($product->getData('color')) {
                echo "<tr><th class='label'>" . $this->__('Color') . "</th>";
                echo "<td>" . $this->__($product->getAttributeText('color')) . "</td>";
                echo "</tr>";
            }
            if ($product->getData('size')) {
                echo "<tr><th class='label'>" . $this->__('Size') . "</th>";
                echo "<td>" . $this->__($product->getAttributeText('size')) . " " . $this->__($var) . "</td></tr>";
            }

            //get parent product for missing child attributes like if child dont have any attribute then show parents attribute
            $parentProduct = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('size_shown_in_image', 'material', 'measurement', 'care_instructions'))
                ->addAttributeToFilter('entity_id', $this->getRequest()->getParam('pid'))
                ->getFirstItem();

            if ($product->getData('size_shown_in_image')) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('Size On Model') . "</th>";
                echo "<td>" . $this->__($product->getData('size_shown_in_image')) . "</td>";
                echo "</tr>";
            } elseif ($parentProduct->getData('size_shown_in_image')) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('Size On Model') . "</th>";
                echo "<td>" . $this->__($parentProduct->getData('size_shown_in_image')) . "</td>";
                echo "</tr>";
            }

            if ($product->getData('material')) {
                echo "<tr><th class='label'>" . $this->__('Product Material') . "</th>";
                echo "<td>" . $this->__($product->getData('material')) . "</td>";
                echo "</tr>";
            } elseif ($parentProduct->getData('material')) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('Product Material') . "</th>";
                echo "<td>" . $this->__($parentProduct->getData('material')) . "</td>";
                echo "</tr>";
            }

            if ($product->getData('measurement')) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('Measurement') . "</th>";
                echo "<td>" . $this->__($product->getData('measurement')) . "</td>";
                echo "</tr>";
            } elseif ($parentProduct->getData('measurement')) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('Measurement') . "</th>";
                echo "<td>" . $this->__($parentProduct->getData('measurement')) . "</td>";
                echo "</tr>";
            }
            // cares instructions at end
            if ($product->getData('care_instructions')) {
                echo "<tr><th class='label'>" . $this->__('Care Instructions') . "</th>";
                echo "<td>" . $this->__($product->getData('care_instructions')) . "</td>";
                echo "</tr>";
            } elseif ($parentProduct->getData('care_instructions')) {
                echo "<tr>";
                echo "<th class='label'>" . $this->__('Care Instructions') . "</th>";
                echo "<td>" . $this->__($parentProduct->getData('care_instructions')) . "</td>";
                echo "</tr>";
            }

        }else {
            $compositionHtml = '';
            $dataArray = array();
            $product = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('sku', 'color', 'size', 'size_shown_in_image', 'material', 'measurement', 'care_instructions'))
                ->addAttributeToFilter('entity_id', $this->getRequest()->getParam('id'))
                ->getFirstItem();

            $_session = Mage::getSingleton("core/session", array("name" => "frontend"));
            $_size = $_session->getData("sizeGuideModal");
            $sku = $product->getSku();
            if ($_size == "#women_shoes" || $_size == "#men_shoes" || $_size == "#kids_shoes" || $_size == "#kids_shoes_girls_infant"
                || $_size == "#kids_shoes_boys_infant" || $_size == '#kids_shoes_girls_junior' || $_size == '#kids_shoes_boys_junior' ||
                $_size == '#kids_shoes_girls_toddler' || $_size == '#kids_shoes_boys_toddler'
            ) {
                $var = "In EU";
            } elseif ($_size == '#women_clothing' || $_size == '#men_clothing' || $_size == '#kids_clothing' ||
                $_size == '#kids_clothing_boys_teenboys' || $_size == '#kids_clothing_girls_teengirls' ||
                $_size == '#kids_clothing_children' || $_size == '#kids_clothing_babies'
            ) {
                $var = "In International";
            }
            if (!empty($sku)) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('sku') . "</th>";
                $compositionHtml .= "<td class='product-variant-class'>" . $this->__($sku) . "</td>";
                $compositionHtml .= "</tr>";
            }
            if ($product->getData('color')) {
                $compositionHtml .= "<tr><th class='label'>" . $this->__('Color') . "</th>";
                $compositionHtml .= "<td>" . $this->__($product->getAttributeText('color')) . "</td>";
                $compositionHtml .= "</tr>";
            }
            if ($product->getData('size')) {
                $compositionHtml .= "<tr><th class='label'>" . $this->__('Size') . "</th>";
                $compositionHtml .= "<td>" . $this->__($product->getAttributeText('size')) . " " . $this->__($var) . "</td></tr>";
            }

            //get parent product for missing child attributes like if child dont have any attribute then show parents attribute
            $parentProduct = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect(array('manufacturer','name','size_shown_in_image', 'material', 'measurement', 'care_instructions'))
                ->addAttributeToFilter('entity_id', $this->getRequest()->getParam('pid'))
                ->getFirstItem();

            if ($product->getData('size_shown_in_image')) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('Size On Model') . "</th>";
                $compositionHtml .= "<td>" . $this->__($product->getData('size_shown_in_image')) . "</td>";
                $compositionHtml .= "</tr>";
            } elseif ($parentProduct->getData('size_shown_in_image')) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('Size On Model') . "</th>";
                $compositionHtml .= "<td>" . $this->__($parentProduct->getData('size_shown_in_image')) . "</td>";
                $compositionHtml .= "</tr>";
            }

            if ($product->getData('material')) {
                $compositionHtml .= "<tr><th class='label'>" . $this->__('Product Material') . "</th>";
                $compositionHtml .= "<td>" . $this->__($product->getData('material')) . "</td>";
                $compositionHtml .= "</tr>";
            } elseif ($parentProduct->getData('material')) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('Product Material') . "</th>";
                $compositionHtml .= "<td>" . $this->__($parentProduct->getData('material')) . "</td>";
                $compositionHtml .= "</tr>";
            }

            if ($product->getData('measurement')) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('Measurement') . "</th>";
                $compositionHtml .= "<td>" . $this->__($product->getData('measurement')) . "</td>";
                $compositionHtml .= "</tr>";
            } elseif ($parentProduct->getData('measurement')) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('Measurement') . "</th>";
                $compositionHtml .= "<td>" . $this->__($parentProduct->getData('measurement')) . "</td>";
                $compositionHtml .= "</tr>";
            }
            // cares instructions at end
            if ($product->getData('care_instructions')) {
                $compositionHtml .= "<tr><th class='label'>" . $this->__('Care Instructions') . "</th>";
                $compositionHtml .= "<td>" . $this->__($product->getData('care_instructions')) . "</td>";
                $compositionHtml .= "</tr>";
            } elseif ($parentProduct->getData('care_instructions')) {
                $compositionHtml .= "<tr>";
                $compositionHtml .= "<th class='label'>" . $this->__('Care Instructions') . "</th>";
                $compositionHtml .= "<td>" . $this->__($parentProduct->getData('care_instructions')) . "</td>";
                $compositionHtml .= "</tr>";
            }

            $category = '';
            $mageSession = Mage::getSingleton("core/session",  array("name"=>"frontend"));
            if( $categoryPaths = $mageSession->getBreadcrumbProductDatalayer() ) {// Unset Breadcrump if Product Page refresh.
                $count = 1;
                $totalCount = count($categoryPaths);
                foreach( $categoryPaths as $categoryPath  ):
                    $category .= $categoryPath['label'];
                    if( $count < $totalCount )
                        $category .= '/';
                    $count++;
                endforeach;
            }
            //Data layer Script Creation start from here
            $datalayerhtml = '';
            $datalayerhtml .= '<script>';
            $datalayerhtml .= 'dataLayer.push({';
            $datalayerhtml .= " 'currencyCode':'".Mage::app()->getStore()->getCurrentCurrencyCode()."',";
            $datalayerhtml .= " 'detail': {";
            $datalayerhtml .= " 'actionField': {'list': \"".$parentProduct->getName()."\"},";
            $datalayerhtml .= "'products': [{ ";
            $datalayerhtml .= "'name':\"".$parentProduct->getName()."\",";
            $datalayerhtml .= " 'id': '".$sku."',";
            $datalayerhtml .= " 'brand': \"".$parentProduct->getAttributeText('manufacturer')."\",";
            $datalayerhtml .= " 'category': \"".$category."\",";
            $datalayerhtml .= " 'variant': 'Color:".$product->getAttributeText('color')."-Size:".$product->getAttributeText('size')."',";
            $datalayerhtml .= '}]';
            $datalayerhtml .= '}';
            $datalayerhtml .= '});';
            $datalayerhtml .= '</script>';
            $dataArray['html'] = $compositionHtml;
            $dataArray['datalayer'] = $datalayerhtml;
            $tags = array();
            $tags[] = sha1("productdetailsimpleajax"); //make this as a general tag to clear cache for all calls
            $tags[] = sha1("productdetailsimpleajax_".$this->getRequest()->getParam('id')); // make this with product id means specific for this call
            $fpcModel->saveFpc(json_encode($dataArray), $cacheId, $tags);
            echo json_encode($dataArray);

        }
    }

}
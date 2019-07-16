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
 * Edit product information
 */
class Apptha_Marketplace_Block_Product_Edit extends Mage_Core_Block_Template {
 
 /**
  * Initilize layout and set page title
  *
  * Return the page title
  * 
  * @return varchar
  */
     protected function _prepareLayout() {
          $this->getLayout ()->getBlock ( 'head' )->setTitle ( Mage::helper ( 'marketplace' )->__ ( 'Edit Product' ) );
          $productId = $this->getRequest ()->getParam ( 'id' );
          // $productName = Mage::getModel ( 'catalog/product' )->load ( $productId )->getName ();
          $productType = Mage::getModel ( 'catalog/product' )->load ( $productId )->getTypeId();
          
          // $this->statusProducts();

          if ($productType == "configurable") {
               $this->statusProducts();
               parent::_prepareLayout ();
               $manageConfigurableProductCollection = $this->manageProducts ();
               $this->setCollection ( $manageConfigurableProductCollection );
               $pager = $this->getLayout ()->createBlock ( 'page/html_pager', 'my.pager' )->setCollection ( $manageConfigurableProductCollection );
               $pager->setAvailableLimit ( array (
                    10 => 10,
                    20 => 20,
                    50 => 50 
               ));
               $this->setChild ('pager', $pager );
               return $this;
          }

          return parent::_prepareLayout();

     }

     public function manageProducts() {

          $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
          $productId = $this->getRequest ()->getParam ( 'id' );

          $product = Mage::getModel ( 'catalog/product' )->load ( $productId );
          
          // if ($product->getTypeId == "configurable") {

               $attributes = $product->getTypeInstance ()->getConfigurableAttributesAsArray ();
               $associatedProducts = Mage::getModel ( 'catalog/product' )->getCollection ()->addFieldToFilter ( 'seller_id', $sellerId );
               if ($this->getRequest ()->getParam ( 'set' )) {
                    $associatedProducts->addFieldToFilter ( 'attribute_set_id', $this->getRequest ()->getParam ( 'set' ) );
               }
               $attributeFilters = $this->getRequest ()->getParam ( 'attribute_filter' );
               if (! empty ( $attributeFilters )) {
                    foreach ( $attributeFilters as $key => $attributeFilter ) {
                         if (! empty ( $attributeFilter )) {
                              $associatedProducts->addFieldToFilter ( $key, array (
                                   'in' => $attributeFilter 
                              ));
                         }
                    }
               }
               $associatedProducts = $this->configurableProductFilter ( $productId, $product, $associatedProducts );
        
               if ($this->getRequest ()->getParam ( 'reset' ) == '') {
                    if ($this->getRequest ()->getParam ( 'filter_id' ) != '') {
                         $fitlerId = $this->getRequest ()->getParam ( 'filter_id' );
                         $associatedProducts->addFieldToFilter ( 'entity_id', array (
                              'eq' => $fitlerId 
                         ));
                    }
                    $filterName = $this->getRequest ()->getParam ( 'filter_name' );
                    /**
                    * Checking filtername is not empty
                    */
                    if (! empty ( $filterName )) {
                         $associatedProducts->addFieldToFilter ( 'name', array (
                              'like' => '%' . $filterName . '%' 
                         ));
                    }

                    $filterStatus = $this->getRequest ()->getParam ( 'filter_status' );
                    /**
                    * Checking filtername is not empty
                    */
                    if (! empty ( $filterStatus )) {
                         $associatedProducts->addFieldToFilter ( 'status', $filterStatus);
                    }
               }
        
               foreach ( $attributes as $attribute ) {
                    if (isset ( $attribute ['attribute_code'] )) {
                         $associatedProducts->addFieldToFilter ( $attribute ['attribute_code'], array (
                              'neq' => '' 
                         ));
                    }
               }
               /** Filter By Type
               * 
               */
               $associatedProducts->addFieldToFilter ( 'type_id', 'simple' );
               /** Filter by All
               */
               $associatedProducts->addAttributeToSelect ( '*' );
               /**
               * Sort Order By Desc
               */
               $associatedProducts->addAttributeToSort ( 'entity_id', 'DESC' );
               return $associatedProducts;
          // }
    
    }
 
      public function statusProducts() {
          $sellerId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();

          $multi_submit = $this->getRequest ()->getPost ( 'multi_submit' );
          $entityIds = $this->getRequest ()->getParam ( 'selected_simple_product_ids' );
          $idx = $this->getRequest()->getParam('selected_simple_product_ids');
          $delete = $this->getRequest ()->getPost ( 'multi' );

          /**
             * Check if submit buttom submitted.
          */
          if ($delete && (count($entityIds) <= 0)) {
               Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "Please select a product and action to update status" ) );
               $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
               Mage::app()->getResponse()->setRedirect($url);
               return;
          }

          // if ($delete && Mage::app()->getRequest()->getPost()) {
          // $delete = false;
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
               // if (count ( $entityIds ) == 0 && $delete == 'delete') {
               //      Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "Please select a product to update status" ) );
               //      $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
               //      Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
               // }
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

               // if (count ( $entityIds ) == 0 && $delete == 'soldout') {
               //      Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "Please select a product to update status" ) );
               //      $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
               //      Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
               // }
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

               // if (count ( $entityIds ) == 0 && $delete == 'paused') {
               //      Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "Please select a product to update status" ) );
               //      // $url = Mage::getUrl ( 'marketplace/product/edit' );
               //      $url = Mage::getUrl('marketplace/product/edit/', array('id' => $this->getRequest()->getParam('id'))) . '#associate_table';
               //      Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
               // }
               /*--------------------- END -------------------------*/
          }
    
    }





     public function configurableProductFilter($productId, $product, $associatedProducts) {
        if ($this->getRequest ()->getParam ( 'reset' ) == '') {
            $childProductIds = $fitlerId = array ();
            /**
             * Checking product id is not empty and also reset request is not equal to 1
             */
            if (! empty ( $productId ) && $this->getRequest ()->getParam ( 'reset' ) != 1) {
                $childProductIds = $product->getTypeInstance ()->getUsedProductIds ();
                if (count ( $childProductIds ) >= 1) {
                    $fitlerId = $childProductIds;
                }
            }
            $configurableOptionFilter = $this->getRequest ()->getParam ( 'configurable_option_filter' );
            /**
             * Check configurable option filter yes or empty
             */
            if ($configurableOptionFilter == 'yes' || $configurableOptionFilter == '') {
                $associatedProducts->addFieldToFilter ( 'entity_id', array (
                        'in' => $fitlerId 
                ) );
            } else {
                if ($configurableOptionFilter == 'no' && ! empty ( $fitlerId )) {
                    $associatedProducts->addFieldToFilter ( 'entity_id', array (
                            'nin' => $fitlerId 
                    ) );
                }
            }
        }
        
        return $associatedProducts;
    }

    public function getPagerHtml() {
        return $this->getChildHtml ( 'pager' );
    }
 /**
  * Product edit action
  *
  * Return edit post action url
  * 
  * @return string
  */
 public function editProductAction() {
  return Mage::getUrl ( 'marketplace/product/editpost' );
 }
 
 /**
  * Get product data collection
  *
  * Passed the product id to get product details
  * 
  * @param int $productId
  *         Return product details as array
  * @return array
  */
 public function getProductData($productId) {
  return Mage::getModel ( 'catalog/product' )->load ( $productId );
 }
 
 /**
  * Display Category tree
  *
  * Passed the category data
  * 
  * @param array $categories
  *         Passed the category id to get particular category info
  * @param int $categoryIds
  *         Return category tree as array
  * @return array
  */
 public function getCategoriesTree($categories, $categoryIds) {
  $array = '<ul class="category_ul">';
  foreach ( $categories as $category ) {
   $catId = $category->getId ();
   $cat = Mage::helper ( 'marketplace/marketplace' )->getCategoryData ( $catId );
   $count = $cat->getProductCount ();
   $catChecked = '';
   /**
    * Checking category id is in the needed category ids list
    */
   if (in_array ( $category->getId (), $categoryIds )) {
    $catChecked = 'checked';
   }
   if ($category->hasChildren ()) {
    $array .= '<li class="level-top  parent"><a href="javascript:void(0);"><span class="end-plus"></span></a><span class="last-collapse"><input id="cat' . $category->getId () . '" type="checkbox" name="category_ids[]" ' . $catChecked . ' value="' . $category->getId () . '"><label for="cat' . $category->getId () . '">' . $category->getName () . '<span>(' . $count . ')</span>' . '</label></span>';
   } else {
    $array .= '<li class="level-top  parent"><a href="javascript:void(0);"><span class="empty_space"></span></a><input id="cat' . $category->getId () . '" type="checkbox" name="category_ids[]" ' . $catChecked . ' value="' . $category->getId () . '"><label for="cat' . $category->getId () . '">' . $category->getName () . '<span>(' . $count . ')</span>' . '</label>';
   }
   /**
    * Checking category has children
    */
   if ($category->hasChildren ()) {
    $children = Mage::getModel ( 'catalog/category' )->getCategories ( $category->getId () );
    $array .= $this->getCategoriesTree ( $children, $categoryIds );
   }
   $array .= '</li>';
  }
  return $array . '</ul>';
 }
 
 /**
  * Getting selected product categories id
  *
  * Category data are passed to display the category id
  * 
  * @param array $categoryArray
  *         Return category id
  * @return array
  */
 public function getCategoryIds($categoryArray) {
  $categoryIds = array ();
  foreach ( $categoryArray as $key ) {
   foreach ( $key as $value ) {
    $categoryIds [] = $value;
   }
  }
  return $categoryIds;
 }

}


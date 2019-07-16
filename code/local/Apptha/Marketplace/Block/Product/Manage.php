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
 * Manage seller products functionality
 */
class Apptha_Marketplace_Block_Product_Manage extends Mage_Core_Block_Template
{
    /**
     * Name of pager block in product layout.
     */
    const PAGER_BLOCK_NAME = 'my.pager';

    /**
     * @var Mage_Catalog_Model_Resource_Product_Collection
     */
    protected $_collection;

    public function __construct()
    {
        parent::__construct();
        $manageProductCollection = $this->manageProducts();
        $this->setCollection($manageProductCollection);
    }

    /**
     * Collection for manage products
     *
     * @return \Apptha_Marketplace_Block_Product_Manage
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $pager = $this->getLayout()
                      ->createBlock('page/html_pager', self::PAGER_BLOCK_NAME)
                      ->setCollection($this->getCollection());
        /**
         * setting available limits for pager
         */
        $pager->setAvailableLimit(array(
            10 => 10,
            20 => 20,
            50 => 50,
        ));
        $this->setChild('pager', $pager);

        return $this;
    }

    /**
     * Function to get the product details
     *
     * Return product collection
     *
     * @return array
     */
    public function manageProducts()
    {
        $multi_submit = $this->getRequest()->getPost('multi_submit');
        $entityIds = $this->getRequest()->getParam('id');
        $delete = $this->getRequest()->getPost('multi');
        /**
         * Check if submit buttom submitted.
         */
        if ($multi_submit) {
            if (count($entityIds) > 0 && $delete == 'delete') {
                foreach ($entityIds as $entityIdData) {
                    Mage::register('isSecureArea', true);
                    Mage::helper('marketplace/marketplace')->deleteProduct($entityIdData);
                    $this->getRequest()->setPost('id', null);
                    Mage::unregister('isSecureArea');
                }
                Mage::getSingleton('core/session')->addSuccess($this->__("selected Products are Deleted Successfully"));
                $url = Mage::getUrl('marketplace/product/manage');

                Mage::app()->getFrontController()->getResponse()->setRedirect($url);


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
                $url = Mage::getUrl('marketplace/product/manage');
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }

            if (count($entityIds) == 0 && $delete == 'disable') {
                Mage::getSingleton('core/session')->addError($this->__("Please select a product to update status"));
                $url = Mage::getUrl('marketplace/product/manage');
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }

            /*----------------Disable End----------------*/

            /*----------------Sold Out ----------------*/
            if (count($entityIds) > 0 && $delete == 'soldout') {
                $currentStoreId = Mage::app()->getStore()->getStoreId();
                Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
                foreach ($entityIds as $entityIdData) {
                    Mage::register('isSecureArea', true);
                    Mage::helper('marketplace/marketplace')->outOfStock($entityIdData);
                    Mage::unregister('isSecureArea');
                    $this->getRequest()->setPost('id', null);
                }
                Mage::app ()->setCurrentStore ( $currentStoreId );
                Mage::getSingleton('core/session')
                    ->addSuccess($this->__("selected Products status has been sold Out Successfully and Un Publish"));
                $url = Mage::getUrl('marketplace/product/manage');
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }

            if (count($entityIds) == 0 && $delete == 'soldout') {
                Mage::getSingleton('core/session')->addError($this->__("Please select a product to update status"));
                $url = Mage::getUrl('marketplace/product/manage');
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }
            /*--------------------- END -------------------------*/
            /*----------------paused ----------------*/
            if (count($entityIds) > 0 && $delete == 'paused') {
                $currentStoreId = Mage::app()->getStore()->getStoreId();
                Mage::app ()->setCurrentStore ( Mage_Core_Model_App::ADMIN_STORE_ID );
                foreach ($entityIds as $entityIdData) {
                    Mage::register('isSecureArea', true);
                    Mage::helper('marketplace/marketplace')->pausedStock($entityIdData);
                    Mage::unregister('isSecureArea');
                    $this->getRequest()->setPost('id', null);
                }
                Mage::app ()->setCurrentStore ( $currentStoreId );
                Mage::getSingleton('core/session')
                    ->addSuccess($this->__("selected Products status has been sold Out Successfully and Un Publish"));
                $url = Mage::getUrl('marketplace/product/manage');
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }

            if (count($entityIds) == 0 && $delete == 'paused') {
                Mage::getSingleton('core/session')->addError($this->__("Please select a product to update status"));
                $url = Mage::getUrl('marketplace/product/manage');
                Mage::app()->getFrontController()->getResponse()->setRedirect($url);
            }
            /*--------------------- END -------------------------*/
        }
        $filterPrice = $this->getRequest()->getParam('filter_price');
        $filterStatus = $this->getRequest()->getParam('filter_status');
        $ProductStatus = $this->getRequest()->getParam('product_status');
        $filterId = $this->getRequest()->getParam('filter_id');
        $filterName = $this->getRequest()->getParam('filter_name');
        $filterQuantity = $this->getRequest()->getParam('filter_quantity');
        $filterProductType = $this->getRequest()->getParam('filter_product_type');
        $cusId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        $products = Mage::getModel('catalog/product')->getCollection();
        $products->addAttributeToSelect('*');
        $products->addAttributeToFilter('seller_id', array(
            'eq' => $cusId,
        ));

        $products = Mage::helper('marketplace/product')->productFilterByAttribute('name', $filterName, $products);
        $products = Mage::helper('marketplace/product')->productFilterByAttribute('entity_id', $filterId, $products);
        $products = Mage::helper('marketplace/product')->productFilterByAttribute('price', $filterPrice, $products);
        $products = Mage::helper('marketplace/product')->productFilterByAttribute('status', $filterStatus, $products);
        $products = Mage::helper('marketplace/product')
                        ->productFilterByAttribute('seller_product_status', $ProductStatus, $products);

        /**
         * confirming filter product type is not empty
         */
        if (!empty ($filterProductType)) {
            $products->addAttributeToFilter('type_id', array(
                'eq' => $filterProductType,
            ));
        }
        /**
         * Check filter quantity is not equal to empty
         */
        if ($filterQuantity != '') {
            $products->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id',
                '{{table}}.stock_id=1', 'left')->addAttributeToFilter('qty', array(
                'eq' => $filterQuantity,
            ));
        }

        $products->addAttributeToFilter('visibility', array(
            'eq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
        ));
        $products->addAttributeToSort('entity_id', 'DESC');

        return $products;
    }

    /**
     * Function to display pagination
     *
     * Return collection with pagination
     *
     * @return array
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Function to get multi select url
     *
     * Return the multi select option url
     *
     * @return string
     */
    public function getmultiselectUrl()
    {
        return Mage::getUrl('marketplace/product/manage');
    }

    /**
     * Function to get multi select url
     *
     * Return the multi select option url
     *
     * @return string
     */
    public function getBulkUploadUrl()
    {
        return Mage::getUrl('marketplace/bulkupload/bulkupload');
    }

    /**
     * @param $collection
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return $this->_collection;
    }
}
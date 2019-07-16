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
 * Renderer to display ordered product information
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_OrderItemdetails extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to get product order details
     * 
     * Return the product order details
     * @return string
     */
    public function render(Varien_Object $row) {
            $value = $row->getData($this->getColumn()->getIndex());
            $commission = Mage::getModel('marketplace/commission')->load($value);
            $getProductId = $commission->getProductId();
            $getOrderId = $commission->getOrderId();
            $itemItem = Mage::getModel('catalog/product')->load($getProductId);// simple/config product id 
            $pId = Mage::helper("adminhtml")->getUrl('adminhtml/catalog_product/edit', array('id' => $getProductId));        
            if($itemItem->getTypeId() == "configurable")
                {

                    // sale ka table 
                $collection = Mage::getResourceModel('sales/order_item_collection')
                ->addAttributeToFilter('product_id', array('eq' => $getProductId))
                ->addAttributeToFilter('order_id', array('eq' => $getOrderId))
                ->getFirstItem()
                ->load();
                $getProductId = Mage::getModel('catalog/product')->getIdBySku($collection->getSku());
                $configurableCollection = Mage::getModel('catalog/product')->load($getProductId);
                $color = $configurableCollection->getAttributeText("color");
                $size = $configurableCollection->getAttributeText("size");
                $sku = $configurableCollection->getSku();
                $name = $configurableCollection->getName();

                try {
                    $image =  Mage::helper('catalog/image')->init($configurableCollection, 'small_image')->resize(75, 75);
                }
                catch(Exception $e) {
                    $image =  "";
                }
               

                   
               }
            else{
                $color = $itemItem->getAttributeText("color");
                $size = $itemItem->getAttributeText("size");
                $sku = $itemItem->getSku();
                $name = $itemItem->getName();

                try {
                    $image =  Mage::helper('catalog/image')->init($itemItem, 'small_image')->resize(75, 75);
                }
                catch(Exception $e) {
                    $image =  "";
                }
            }
      

            if ($size) {
                $size = "<strong>Size:</strong>&nbsp;" . $size;
            }

            if ($color) {
                $color = "<strong>Color:</strong>&nbsp;" . $color;
            }
            if ($sku) {
                 $sku = "<strong>SKU:</strong>&nbsp;" . $sku;
            }
            $other_details = "<strong>" . $name . "</strong><br><br>$size<br>$color<br>$sku";
            $img = "<img src='" .$image . "' alt='" . $itemItem->getName() . "' border='0' width='75' />";
            return '<a href="' . $pId . '" target="_blank" >' . $img . '</a><br>' . $other_details;
        
        }

        public function renderExport(Varien_Object $row)
        {
            $value = $row->getData($this->getColumn()->getIndex());
            $getProductId = $row->getProductId();
            $getOrderId = $row->getOrderId();
            $productCollection = Mage::getResourceModel('sales/order_item_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('product_id', array('eq' => $getProductId))
                ->addAttributeToFilter('order_id', array('eq' => $getOrderId))
                ->getFirstItem();       
            if($productCollection->getProductType() == "configurable")
                {
                    $str=$productCollection->getName();
                    $optionArray = $productCollection->getProductOptions(); 
                    foreach($optionArray['attributes_info'] as $option) {
                        if ($option['label']=='Size') {
                        $size = ",Size:" . $option['value'];
                        }
                        if ($option['label']=='Color') {
                            $color = ",Color:" . $option['value'];
                        }
                    }
                    $str .= ',SKU:'.$productCollection->getSku();
                    $sku = $productCollection->getSku();
                    $name = $productCollection->getName();
                    if ($sku) {
                        $sku = ",SKU:" . $sku;
                    }
                   return $other_details = $name .$size.$color.$sku;                       
               }
            else{
                 $_resource = Mage::getSingleton('catalog/product')->getResource();
                $color = $_resource->getAttributeRawValue($row->getProductId(),  'color', Mage::app()->getStore());
                $size = $_resource->getAttributeRawValue($row->getProductId(),  'size', Mage::app()->getStore());
                $sku = $productCollection->getSku();
                $name = $productCollection->getName();            
                if ($size) {
                    $size = ",Size:" . $size;
                }
                if ($color) {
                    $color = ",Color:" . $color;
                }
                if ($sku) {
                     $sku = ",SKU:" . $sku;
                }
                return $other_details = $name .$size.$color.$sku;
            }          
            return ;
        }
    }


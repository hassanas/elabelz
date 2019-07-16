<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Product extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $commission = Mage::getModel('marketplace/commission')->load($value);
        $getProductId = $commission->getProductId();
        $getOrderId = $commission->getOrderId();
        $itemItem = Mage::getModel('catalog/product')->load($getProductId);
        $pId = Mage::helper("adminhtml")->getUrl('adminhtml/catalog_product/edit', array('id' => $getProductId));        
        if($itemItem->getTypeId() == "configurable") {
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
            } catch(Exception $e) {
                $image =  "";
            }
        } else {
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
        $other_details = "<div style='width:150px'><strong>" . $name . "</strong><br><br>$size<br>$color<br>$sku</div>";
        $img = "<img src='" .$image . "' alt='" . $itemItem->getName() . "' border='0' width='75' />";
        return '<a href="' . $pId . '" target="_blank" >' . $img . '</a><br>' . $other_details;

    }

}


<?php
abstract class Equity_FacebookPixel_Block_Abstract extends Mage_Core_Block_Template
{

    abstract protected function _canShow();

    protected function _toHtml() {
        if ( !$this->_canShow() ) {
            return '';
        }

        return parent::_toHtml();
    }

    protected function _getLastAddedProduct()
    {
        $productID = Mage::getSingleton('checkout/session')->getLastAddedProductId(true);
        return Mage::getModel('catalog/product')->load($productID);
    }

    public function getStoreCurrency() {
        return $this->_getHelper()->getStoreCurrency();
    }

    public function getConversionValue() {
        if ( !$this->_getHelper()->getCartAmount() ){
            return 0.01;
        }
        return number_format($this->_getHelper()->getCartAmount(),2);
    }

    protected function _getConfigHelper() {
        return Mage::helper('equity_facebookpixel/config');
    }

    protected function _getHelper() {
        return Mage::helper('equity_facebookpixel');
    }

    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getProductTrackID($product)
    {
        $productType = $product->getTypeID();
        $type = $this->_getConfigHelper()->getProductTrackType();

        if($productType == "grouped") {
            return $this->_getProductSKUorIDs($product, $type);
        } elseif($type == "id") {
            return "'" . $this->_getProductID($product) . "'";
        } else {
            return "'" . $this->_getProductSKU($product) . "'";
        }
    }

    protected function _getProductName($product)
    {
        return $product->getName();
    }

    protected function _getCategoryPath()
    {
        $names = array();
        $category = $this->_getCategory();

        if( is_null($category) ) return '';

        $path = $category->getPath();
        $path = explode("/",$path);

        unset($path[0],$path[1]);

        foreach($path as $id){
            $category = Mage::getModel('catalog/category')->setStoreId( Mage::app()->getStore()->getId() )->load($id);
            $names[] = $category->getName();
        }

        $return = implode(" > ", $names);

        return $return;
    }

    protected function _trackDetails()
    {
        return $this->_getConfigHelper()->getDynamicProducts();
    }

    protected function _getProductPrice($product)
    {
        $productType = $product->getTypeID();
        $price = 0;

        if($productType == "grouped"){
            $group = Mage::getModel('catalog/product_type_grouped')->setProduct($product);
            $group_collection = $group->getAssociatedProductCollection();

            foreach ($group_collection as $group_product) {
                $id = $group_product->getId();
                $_product = Mage::getModel('catalog/product')->load($id);
                $price += $_product->getFinalPrice();
            }

        } else {
            $price = $product->getFinalPrice();
        }

        return number_format( $price,2,'.','' );
    }

    protected function _getProductID($product)
    {
        return $product->getId();
    }

    protected function _getProductSKU($product)
    {
        return $product->getSKU();
    }

    protected function _getProductType($product)
    {
        $productType = $product->getTypeID();

        if($productType == "grouped"){
            return "product_group";
        } else {
            return "product";
        }
    }

    private function _getProductSKUorIDs($product, $type)
    {
        $group = Mage::getModel('catalog/product_type_grouped')->setProduct($product);
        $group_collection = $group->getAssociatedProductCollection();
        $return = '';

        foreach ($group_collection as $group_product) {

            if($type == "id"){
                $id = $this->_getProductID($group_product);
            } else {
                $id = $this->_getProductSKU($group_product);
            }

            $return .= "'" . $id . "',";
        }

        return substr($return, 0, -1);
    }

    protected function _getCategory()
    {
        return Mage::registry('current_category');
    }

}

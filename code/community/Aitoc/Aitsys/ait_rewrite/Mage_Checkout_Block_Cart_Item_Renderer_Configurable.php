<?php
/* DO NOT MODIFY THIS FILE! THIS IS TEMPORARY FILE AND WILL BE RE-GENERATED AS SOON AS CACHE CLEARED. */


/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping cart item render block
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aitoc_Aiteditablecart_Block_Rewrite_FrontCheckoutCartItemRendererConfigurable extends Mage_Checkout_Block_Cart_Item_Renderer_Configurable {

    /**
     * Get product thumbnail image
     *
     * @return Mage_Catalog_Model_Product_Image
     */
    public function getProductThumbnail() {
        if (Mage::getStoreConfig("attributeswatches/checkout/overrideimage")) {
            $_atts = explode(",", Mage::getStoreConfig("attributeswatches/settings/switchimage"));
            if(!count($_atts)) parent::getProductThumbnail();
            /* get combinations attribute{attributeid}-{value} */
            $product_instance = $this->getProduct()->getTypeInstance(true);
            $attributesOption = $product_instance->getProduct($this->getProduct())->getCustomOption('attributes');
            $_values = unserialize($attributesOption->getValue());
            $usedProductAttributesData = array();
            foreach ($product_instance->getConfigurableAttributes($this->getProduct()) as $attribute) {
                if (!is_null($attribute->getProductAttribute()) && isset($_values[$attribute->getProductAttribute()->getId()]) && in_array($attribute->getProductAttribute()->getAttributeCode(), $_atts)) {
                    $id = $attribute->getProductAttribute()->getId();
                    $usedProductAttributesData[$attribute->getProductAttribute()->getAttributeCode()] = "attribute" . $id . "-" . $_values[$id];
                }
            }
            $_images = Mage::getResourceSingleton('catalog/product_attribute_backend_media')->loadCartImage($this->getProduct(), $usedProductAttributesData);
            if(!count($_images))return parent::getProductThumbnail();
            return $this->helper('catalog/image')->init($this->getProduct(), 'thumbnail', $_images[0]["file"]);
        } else {

            return parent::getProductThumbnail();
        }
    }

}


/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Mango_Attributeswatches_Block_Checkout_Cart_Item_Renderer_Configurable extends Aitoc_Aiteditablecart_Block_Rewrite_FrontCheckoutCartItemRendererConfigurable
{
    // aitoc code    
    
    protected $_prices      = array();
    protected $_resPrices   = array();

    public function getAllowAttributes()
    {
        return $this->getProduct()->getTypeInstance(true)
            ->getConfigurableAttributes($this->getProduct());
    }

    public function hasOptions()
    {
        $attributes = $this->getAllowAttributes();
        if (count($attributes)) {
            foreach ($attributes as $key => $attribute) {
                /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $attribute */
                if ($attribute->getData('prices')) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAllowProducts()
    {
//        if (!$this->hasAllowProducts()) {
        if (1) { // fix for several products
            $products = array();
            $allProducts = $this->getProduct()->getTypeInstance(true)
                ->getUsedProducts(null, $this->getProduct());
            foreach ($allProducts as $product) {
                if ($product->isSaleable()) {
                    $products[] = $product;
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }

    public function getJsonConfig()
    {
// start aitoc code
        $aAtrributesValues = $attributes = $this->getProduct()->getTypeInstance(true)
            ->getSelectedAttributesValues($this->getProduct());
// finish aitoc code
        
        $attributes = array();
        $options = array();
        $store = Mage::app()->getStore();
        foreach ($this->getAllowProducts() as $product) {
            $productId  = $product->getId();

            foreach ($this->getAllowAttributes() as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());
                if (!isset($options[$productAttribute->getId()])) {
                    $options[$productAttribute->getId()] = array();
                }

                if (!isset($options[$productAttribute->getId()][$attributeValue])) {
                    $options[$productAttribute->getId()][$attributeValue] = array();
                }
                $options[$productAttribute->getId()][$attributeValue][] = $productId;
            }
        }

        $this->_resPrices = array(
            $this->_preparePrice($this->getProduct()->getFinalPrice())
        );

        foreach ($this->getAllowAttributes() as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();
            
            $info = array(
               'id'        => $productAttribute->getId(),
               'code'      => $productAttribute->getAttributeCode(),
               'label'     => $attribute->getLabel(),
               'options'   => array()
            );

            $optionPrices = array();
            $prices = $attribute->getPrices();
            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if(!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }
                    
// start aitoc code
                    if (!empty($aAtrributesValues[$attributeId]) AND $value['value_index'] == $aAtrributesValues[$attributeId])
                    {
                        $iAttributeValue = $aAtrributesValues[$attributeId];
                    }
                    else 
                    {
                        $iAttributeValue = null;
                    }
// finish aitoc code

                    $info['options'][] = array(
                        'attr_value'    => $iAttributeValue, // aitoc code
                        'id'    => $value['value_index'],
                        'label' => $value['label'],
                        'price' => $this->_preparePrice($value['pricing_value'], $value['is_percent']),
                        'products'   => isset($options[$attributeId][$value['value_index']]) ? $options[$attributeId][$value['value_index']] : array(),
                    );
                    $optionPrices[] = $this->_preparePrice($value['pricing_value'], $value['is_percent']);
                    //$this->_registerAdditionalJsPrice($value['pricing_value'], $value['is_percent']);
                }
            }
            /**
             * Prepare formated values for options choose
             */
            foreach ($optionPrices as $optionPrice) {
                foreach ($optionPrices as $additional) {
                    $this->_preparePrice(abs($additional-$optionPrice));
                }
            }
            if($this->_validateAttributeInfo($info)) {
               $attributes[$attributeId] = $info;
            }
        }
        /*echo '<pre>';
        print_r($this->_prices);
        echo '</pre>';die();*/

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false);
        $_request->setProductClassId($this->getProduct()->getTaxClassId());
        $defaultTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest();
        $_request->setProductClassId($this->getProduct()->getTaxClassId());
        $currentTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $taxConfig = array(
            'includeTax'        => Mage::helper('tax')->priceIncludesTax(),
            'showIncludeTax'    => Mage::helper('tax')->displayPriceIncludingTax(),
            'showBothPrices'    => Mage::helper('tax')->displayBothPrices(),
            'defaultTax'        => $defaultTax,
            'currentTax'        => $currentTax,
            'inclTaxTitle'      => Mage::helper('catalog')->__('Incl. Tax'),
        );

        $config = array(
            'attributes'        => $attributes,
            'template'          => str_replace('%s', '#{price}', $store->getCurrentCurrency()->getOutputFormat()),
//            'prices'          => $this->_prices,
            'basePrice'         => $this->_registerJsPrice($this->_convertPrice($this->getProduct()->getFinalPrice())),
            'oldPrice'          => $this->_registerJsPrice($this->_convertPrice($this->getProduct()->getPrice())),
            'productId'         => $this->getProduct()->getId(),
            'chooseText'        => Mage::helper('catalog')->__('Choose option...'),
            'taxConfig'         => $taxConfig,
        );

        return Zend_Json::encode($config);
    }

    /**
     * Validating of super product option value
     *
     * @param array $attribute
     * @param array $value
     * @param array $options
     * @return boolean
     */
    protected function _validateAttributeValue($attributeId, &$value, &$options)
    {
        if(isset($options[$attributeId][$value['value_index']])) {
            return true;
        }

        return false;
    }

    /**
     * Validation of super product option
     *
     * @param array $info
     * @return boolean
     */
    protected function _validateAttributeInfo(&$info)
    {
        if(count($info['options']) > 0) {
            return true;
        }
        return false;
    }

    protected function _preparePrice($price, $isPercent=false)
    {
        if ($isPercent && !empty($price)) {
            
            // fix for price magento 1.4
            
            if ($nSpecialPrice = $this->getProduct()->getSpecialPrice())
            {
                $basePrice = $nSpecialPrice;
            }
            else 
            {
                $basePrice = $this->getProduct()->getPrice();
            }
            
//            $price = $this->getProduct()->getFinalPrice()*$price/100;
            $price = $basePrice*$price/100;
        }

        return $this->_registerJsPrice($this->_convertPrice($price, true));
    }

    protected function _registerJsPrice($price)
    {
        $jsPrice            = str_replace(',', '.', $price);

//        if (!isset($this->_prices[$jsPrice])) {
//            $this->_prices[$jsPrice] = strip_tags(Mage::app()->getStore()->formatPrice($price));
//        }
        return $jsPrice;
    }

    protected function _convertPrice($price, $round=false)
    {
        if (empty($price)) {
            return 0;
        }

        $price = Mage::app()->getStore()->convertPrice($price);
        if ($round) {
            $price = Mage::app()->getStore()->roundPrice($price);
        }


        return $price;
    }
    
    public function getOptions()
    {
        return $this->getParentRendererObject()->getOptions();
    }
        
    public function getOptionHtml($option)
    {
        return $this->getParentRendererObject()->getOptionHtml($option);
    }    

    protected function getParentRendererObject()
    {
            $this->_parentRendererObject = new Aitoc_Aiteditablecart_Block_Rewrite_FrontCheckoutCartItemRenderer();
    
            $this->_parentRendererObject->setItem($this->getItem());
            $this->_parentRendererObject->setLayout($this->getLayout());
            $this->_parentRendererObject->setProduct($this->getProduct());

        return $this->_parentRendererObject;
    }

}


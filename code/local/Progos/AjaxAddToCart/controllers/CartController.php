<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

class Progos_AjaxAddToCart_CartController extends Mage_Checkout_CartController
{
    /**
     * Minicart ajax add action
     */
    public function ajaxAddAction()
    {
        $cart = $this->_getCart();
        $params = $this->getRequest()->getParams();
        $result = array();

        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                throw new Exception('Product is not available');
            }

            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            $result['count'] = $cart->getItemsCount();

            $this->loadLayout();
            if ($minicart = $this->getLayout()->getBlock('minicart_content')) {
                $result['content'] = $minicart->toHtml();
            }

            $result['error'] = 0;
            $result['status'] = $this->__('Success');

            $result['countinueshoping'] = $this->__('Continue Shopping');
            $result['viewCart'] = $this->__('View Cart');
            $result['baseurl'] = Mage::getBaseUrl('link', true);
            $result['carturl'] = Mage::helper('checkout/cart')->getCartUrl();
            if ($product->getSpecialPrice() && Mage::getStoreConfig('infotrust/infotrust/specialprice') == '1') {
                //get special price by date magetno way
                $price = Mage::getModel('catalog/product_type_price')->calculatePrice($product->getPrice(), $product->getSpecialPrice(), $product->getSpecialFromDate(), $product->getSpecialToDate(), null, null, null, $product->getId());
                $result['price']  = ceil(Mage::helper('core')->currency($price, false, false));
            } else {
                $result['price']  = ceil(Mage::helper('core')->currency($product->getPrice(), false, false));
            }

            $result['sku'] = $product->getSku();
            $result['message'] = $this->__('%s has been added to your shopping cart.', $product->getName());
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
        } catch (Exception $e) {
            $result['error'] = 1;
            $result['status'] = $this->__('Error');
            $result['message'] = $this->__($e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
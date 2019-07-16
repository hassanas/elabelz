<?php
class Aitoc_Aiteditablecart_CartController extends Mage_Checkout_Controller_Action
{
    
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }
    
    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    protected function _getTotalsHtml()
    {
        $layout = Mage::getModel('core/layout');
		Mage::register('aiteditablecart_ajax_totals', 1);
        $update = $layout->getUpdate();
        $update->load('aiteditablecart_checkout_cart_totals');
        $layout->generateXml();
        $layout->generateBlocks();

        $messages = $this->_getCart()->getCheckoutSession()->getMessages(true);
        if (isset($messages))
        {
            $layout->getMessagesBlock()->addMessages($messages);
        }
        $output = $layout->getOutput();
		
		// compatibillity with OPCB
        if (Mage::helper('aiteditablecart/aitcheckout')->isEnabledAndCompact())
        {
            $output = preg_replace('|\<div class="totals"\>(.*)\</div\>|Uis','', $output);
        }
        $output = str_replace('<table id="shopping-cart-table" class="cart-table data-table">', '<input name="form_key" type="hidden" value=' . Mage::getSingleton('core/session')->getFormKey() . ' /><table id="shopping-cart-table" class="cart-table data-table">', $output); //fix - form key bloack added
        
        return $output;
    }
    
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }
    
    protected function _expireAjax()
    {
        if (!$this->_getQuote()->hasItems())
        {
            $this->_ajaxRedirectResponse();
            return true;
        }
        return false;
    }
    
    public function updatePostAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->_expireAjax()) {
            return;
        }
        
        //fix for frontend cookies expired or missing
        if(!Mage::getModel('core/cookie')->get('frontend')) {
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('totals' => $this->_getTotalsHtml()))); 
            return;
        }
        //fix end
        
        if ($this->getRequest()->isPost()) 
        {
            try {
                $cartData = $this->getRequest()->getParam('cart');
                $oldData = $cartData;
                if (is_array($cartData)) {
                    $filter = new Zend_Filter_LocalizedToNormalized(
                        array('locale' => Mage::app()->getLocale()->getLocaleCode())
                    );
                    foreach ($cartData as $index => $data) {
                        if (isset($data['qty'])) {
                            $cartData[$index]['qty'] = $filter->filter($data['qty']);
                            //$oldData[$index]['qty'] = $this->_getQuote()->getItemById($index)->getQty();
                        }
                    }
                    $cart = $this->_getCart();
                    if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                        $this->_getQuote()->setCustomerId(null);
                    }
                    //$this->_getQuote()->unsetData('messages');
                    if (Aitoc_Aitsys_Abstract_Service::get()->isMagentoVersion('>=1.4.1'))
                    {
                        $cartData = $cart->suggestItemsQty($cartData);
                    }
                    $cart->updateItems($cartData);
                    $cart->save();

                    $this->_reloadCart();
                }
                if ($this->_expireAjax()) {
                    return;
                }
            } catch (Mage_Core_Exception $e) {
//                $cart->updateItems($oldData);
                $cart->save();
                $this->_getCart()->getCheckoutSession()->addError($e->getMessage()); 
            } catch (Exception $e) {  
                $this->_getCart()->getCheckoutSession()->addException($e, Mage::helper('aiteditablecart')->__('Cannot update shopping cart.'));
                Mage::logException($e);
            }
            Mage::unregister('_singleton/cataloginventory/observer');
            $this->getResponse()
                ->setBody(
                    Mage::helper('core')->jsonEncode(array('totals' => $this->_getTotalsHtml()))
                );             
        }
        else
        {
            $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
        }
    }

    /**
     * Reload cart to display custom options correctly in AJAX response  
     */
    protected function _reloadCart()
    {
        $currQuoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        Mage::getSingleton('checkout/session')->unsetAll();
        Mage::getSingleton('checkout/session')->setQuoteId($currQuoteId);
        $cart = $this->_getCart();

        if ($cart->getQuote()->getItemsCount()) 
        {
            $cart->init();
            $cart->save();

            if (!$this->_getQuote()->validateMinimumAmount()) {
            $warning = Mage::getStoreConfig('sales/minimum_order/description');
            $cart->getCheckoutSession()->addNotice($warning);
            } 
        }
    }
}

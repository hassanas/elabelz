<?php
class Aitoc_Aiteditablecart_Model_Observer
{
    protected $_productIds = array();
    protected $_aitProductsListsData = array();
    protected $_optionsData = array();
    protected $_supAttrData = array();
    protected $_bundOptData = array();
    protected $_bundQtyData = array();
    protected $_downlodData = array();
    protected $_qtyData = array();
    protected $_allowDelete =true;
    protected $_errors = array();

    public function processCartUpdateBefore($observer)
    {
        Mage::dispatchEvent('aiteditablecart_checkout_cart_update_items_before', array());// Compatibility with Custom Options Templates

        $hasOptions = false;

        $cart = $observer->getEvent()->getCart();
        $data = $observer->getEvent()->getInfo();

        foreach ($data as $itemId => $itemInfo) {
            if (!$this->_cartItemsWithoutOptions($itemInfo)) {
                $hasOptions = true;
            }
        }
        if(!$hasOptions) return true;

        $this->_preprocessUpdateCart($cart, $data);
        $this->_removeItemsUpdateCart($cart, $data);
        $this->_addItemsUpdateCart($cart);
    }

    /*
     * @param Mage_Checkout_Model_Cart $cart
     * @param array $data
     */
    protected function _preprocessUpdateCart($cart, $data)
    {
        foreach ($data as $itemId => $itemInfo) {
            $notGrouped = true;
            $item = $cart->getQuote()->getItemById($itemId);

            if (!$this->_cartItemsWithoutOptions($itemInfo)) {
                if ($item) {
                    try {
                        $this->_qtyData = $this->_setQtyData($this->_qtyData, $itemId, $itemInfo);
                        foreach($item->getProduct()->getOptions() as $_option){
                            $userValues = $itemInfo['cart_options'];
                            if (isset($userValues[$_option->getId()])) {
                                $userValues[$_option->getId()] = is_array($userValues[$_option->getId()]) ? array_filter($userValues[$_option->getId()]) : $userValues[$_option->getId()];
                                $_option->groupFactory($_option->getType())
                                    ->setOption($_option)
                                    ->setProduct($item->getProduct())
                                    ->setProcessMode("full")
                                    ->validateUserValue($userValues);
                            }
                        }
                        $this->_checkQty($item, $itemId, $this->_qtyData);
                    }
                    catch(Mage_Core_Exception $e) {
                        $this->_errors[] = $e->getMessage();
                        if (!$item->getProduct()->getHasError())
                        {
                            $this->_allowDelete = false;
                        }
                    }

                    $productId = $item->getProductId();
                    $this->_aitProductsListsData = $this->_setAitProductListOptions($this->_aitProductsListsData, $item);

                    if (!Mage::registry('ait_cart_edit')) { // fix for Aiteditablecart module
                        Mage::register('ait_cart_edit', true); // fix for AdjustWare_Giftreg module
                    }
                }
                else {
                    $productId = $itemInfo['cart_product_id'];
                }

                $this->_optionsData = $this->_setOptionsData($this->_optionsData, $itemInfo, $itemId, 'cart_options');
                $this->_supAttrData = $this->_setOptionsData($this->_supAttrData, $itemInfo, $itemId, 'super_attribute');
                $this->_bundOptData = $this->_setOptionsData($this->_bundOptData, $itemInfo, $itemId, 'bundle_option');
                $this->_bundQtyData = $this->_setOptionsData($this->_bundQtyData, $itemInfo, $itemId, 'bundle_option_qty');
                $this->_downlodData = $this->_setOptionsData($this->_downlodData, $itemInfo, $itemId, 'downloadable_links');
            } 
            elseif (!empty($itemInfo['qty'])) {
                if ($item) {
                    // quote item does not have product type value
                    if($item->getProduct()->getTypeId() != 'grouped') {
                        $this->_qtyData = $this->_setQtyData($this->_qtyData, $itemId, $itemInfo);
                        try {
                            $this->_checkQty($item, $itemId, $this->_qtyData);
                        }
                        catch(Mage_Core_Exception $e) {
                            $this->_errors[] = $e->getMessage();
                            $this->_allowDelete = false;
                        }
                        $productId = $item->getProductId();
                        $this->_aitProductsListsData = $this->_setAitProductListOptions($this->_aitProductsListsData, $item);
                    }
                    else {
                        $notGrouped = false;
                    }
                }
            }
            if($notGrouped) {
                $this->_productIds[$itemId] = $productId;
            }    
        }
    }

    /*
     * @param Mage_Checkout_Model_Cart $cart
     * @param array $data
     */
    protected function _removeItemsUpdateCart($cart, $data)
    {
        if($this->_allowDelete) {
            foreach ($data as $itemId => $itemInfo) {
                $item = $cart->getQuote()->getItemById($itemId);
                // quote item does not have product type value
                if ($item) { //added the check for object
                    if ($item->getProduct()->getTypeId() != 'grouped') {
                        Mage::dispatchEvent('checkout_cart_remove_items_before', array('item' => $item));
                        $cart->getQuote()->removeItem($itemId);
                    }
                }
            }
        }
        else {
            Mage::throwException(implode("\n", $this->_errors));
        }
    }

    /*
     * @param Mage_Checkout_Model_Cart $cart
     */
    protected function _addItemsUpdateCart($cart)
    {
        foreach ($this->_productIds as $itemId => $productId) {
            if ($productId) {
                $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->load($productId);

                if ($product->getId()) {
                    $aitProductsListsListId = empty($this->_aitProductsListsData[$itemId]) ? null : $this->_aitProductsListsData[$itemId];
                    $product->setIsAitocProductList($aitProductsListsListId);

                    $aRequestOptions = $this->_setRequestOptionsData($this->_optionsData, $itemId);
                    $aRequestSupAttr = $this->_setRequestData($this->_supAttrData, $itemId);
                    $aRequestBunData = $this->_setRequestData($this->_bundOptData, $itemId);
                    $aRequestBundQty = $this->_setRequestData($this->_bundQtyData, $itemId);
                    $aRequestDownlod = $this->_setRequestData($this->_downlodData, $itemId);

                    if (!empty($this->_qtyData[$itemId]) && $this->_allowDelete) {
                        $params = array(
                            'product'           => $productId,
                            'qty'               => $this->_qtyData[$itemId],
                            'options'           => $aRequestOptions,
                            'super_attribute'   => $aRequestSupAttr,
                            'bundle_option'     => $aRequestBunData,
                            'bundle_option_qty' => $aRequestBundQty,
                            'links'             => $aRequestDownlod,
                        );
                            
                        if (Mage::registry('aitoc_cart_options_item')) {
                            Mage::unregister('aitoc_cart_options_item');
                        }
                            
                        $item = $cart->getQuote()->getItemById($itemId);
                        Mage::register('aitoc_cart_options_item', $item);

                        Mage::dispatchEvent('aitoc_editablecart_product_add', array('product'=>$product, 'cart' => $cart));
                        $cart->addProduct($product, $params);
                    }

                    if ( (version_compare(Mage::getVersion(),'1.5.0.0','>') &&  version_compare(Mage::getVersion(),'1.7.0.0','<')) )
                    {
                        $item = $cart->getQuote()->getItemByProduct($product);
                        Mage::dispatchEvent('checkout_cart_remove_items_before', array('item'=>$item));
                    }
                }
            }
        }
    }

    /**
     * Collect specific type options for quote item
     * @param array $dataArray array of options
     * @param array $itemInfo quote item data
     * @param int $itemId quote item id
     * @param string $type type of item options
     * @return array
     */
    private function _setOptionsData($dataArray, $itemInfo, $itemId, $type)
    {
        if (!empty($itemInfo[$type])) {
            $dataArray[$itemId][] = $itemInfo[$type];
        }
        return $dataArray;
    }

    /**
     * Set requested custom options for quote item
     * @param array $optionsData custom options for quote item
     * @param int $itemId quote item id
     * @return array
     */
    private function _setRequestOptionsData($optionsData, $itemId)
    {
        $requestOptionsData = array();
        if (!empty($optionsData[$itemId])) {
            foreach ($optionsData[$itemId] as $aData) {
                if ($aData) {
                    foreach ($aData as $iOptionId => $mValue) {
                        if ($mValue and is_array($mValue) AND isset($mValue[0]) AND $mValue[0] == 0) {
                            unset($mValue[0]);
                        }

                        if ($mValue != array(0 => 0)) {
                            $requestOptionsData[$iOptionId] = $mValue;
                        }
                    }
                }
            }
        }
        return $requestOptionsData;
    }

    /**
     * Set requested options for quote item
     * @param array $dataArray specific type options for quote item
     * @param int $itemId quote item id
     * @return array
     */
    private function _setRequestData($dataArray, $itemId)
    {
        $requestData = array();
        if (!empty($dataArray[$itemId])) {
            foreach ($dataArray[$itemId] as $aData) {
                if ($aData) {
                    foreach ($aData as $iOptionId => $sValue) {
                        $requestData[$iOptionId] = $sValue;
                    }
                }
            }
        }
        return $requestData;
    }

    /**
     * Check if quote items have any options
     * @param array $itemInfo quote item data
     * @return bool
     */
    private function _cartItemsWithoutOptions($itemInfo)
    {
        return (empty($itemInfo['cart_options']) && empty($itemInfo['super_attribute']) && empty($itemInfo['bundle_option']) && empty($itemInfo['downloadable_links']));
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $item
     * @param array $productsListsData array of product lists options
     * @return array
     */
    private function _setAitProductListOptions($productsListsData, $item)
    {
        $aitProductsListsOption = $item->getOptionByCode('aitproductslists');
        $productsListsData[$item->getId()] = ($aitProductsListsOption instanceof Mage_Sales_Model_Quote_Item_Option) ? $aitProductsListsOption->getValue() : null;
        return $productsListsData;
    }

    /**
     * Set quantity info for quote item
     * @param array $qtyData array of items qty
     * @param int $itemId quote item id
     * @param array $itemInfo quote item data
     * @return array
     */
    private function _setQtyData($qtyData, $itemId, $itemInfo)
    {
        $qty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;
        $qtyData[$itemId] = $qty;
        return $qtyData;
    }
	
	private function _checkQty($item, $itemId, $qtyData)
    {
        $oldQty = $item->getQty();
        $item->setQtyToAdd($qtyData[$itemId]);
        $item->setQty($qtyData[$itemId]);

        if ($item->getHasError()) {
            $message = $item->getMessage();
            $item->setQtyToAdd($oldQty);
            $item->setQty($oldQty);
        }

        if (!empty($message)) {
            Mage::throwException($message);
        }
        return true;
    }
}
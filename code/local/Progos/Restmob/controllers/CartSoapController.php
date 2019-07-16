<?php
require_once dirname(__FILE__) . '/SoapController.php';

class Progos_Restmob_CartSoapController extends Progos_Restmob_SoapController
{

    public function indexAction()
    {
        return;
    }

    /**
     * Function to create a new QuoteId when add to cart action perform through app
     *
     * @access public
     * @params No parameters required
     * @return Int quoteId
     *
     */
    public function createCartEmbedded()
    {
        $store = Mage::app()->getStore();
        $storeId = $store->getId();
        $quote = Mage::getModel('sales/quote');

        $quote->setStoreId($storeId);
        $quote->setIsActive(false);
        $quote->setIsMultiShipping(false)
            ->save();

        $quote->getBillingAddress();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();
        $quote->reserveOrderId()->save();
        $cart_id = (int)$quote->getId();
        return $cart_id;
    }

    /**
     * Function to get current cart details
     *
     * @access public
     * @params Int qid, sid(optional)
     * @return Array of cart details
     *
     */
    public function getCartAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $quoteId = $this->getRequest()->getPost('qid');
        $response = array('success' => 0, 'message' => '', 'cart' => new stdClass());
        if (!$quoteId) {
            $response['message'] = 'Quote ID ( cart ID) not provided';
        } else {
            try {
                $cartData = $this->info($quoteId);
                $response['cart'] = $cartData;
                $response['success'] = 1;
                $response['sid'] = '';
                $response['qid'] = $quoteId;
                $response['message'] = 'Cart data found';
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['error_message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['error_message'] = $e->getMessage();
                }
                if (is_null($response['error_message'])) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['error_message'], '_')) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
                }
                $response['message'] = "Shopping cart is empty, You have no items in your shopping cart.";
                $response['sid'] = '';
                $response['qid'] = $quoteId;
                $response['trace'] = $e->getTraceAsString();
                Mage::log('Soap less error on Progos_Emapi_CartSoapController getcart action.. ' . $response['error_message'] . '\n' . $e->getCode() . '\n', null, 'mobile_app.log');
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to filter and add new values in cart data returned by server
     *
     * @access public
     * @params Int quoteId
     * @return Array of processed cart details
     *
     */
    public function info($quoteId)
    {
        for ($i = 0; $i < 3; $i++) {
            $apiMdl = Mage::getModel('emapi/api');
            $result = $apiMdl->info($quoteId, Mage::app()->getStore()->getId());
            $data = (array)$result;
            if (intval($data['subtotal']) == 0) {
                if ((int)Mage::getStoreConfig('api/emapi/delay') > 0) {
                    sleep((int)Mage::getStoreConfig('api/emapi/delay'));
                }
            } else {
                break;
            }
        }
        $data['quote_currency_code'] = __(trim($data['quote_currency_code']));
        $i = 0;
        $data['grand_total'] = ceil($data['grand_total']);
        $data['base_grand_total'] = ceil($data['base_grand_total']);
        $data['subtotal'] = ceil($data['subtotal']);
        $data['base_subtotal'] = ceil($data['base_subtotal']);
        $data['subtotal_with_discount'] = ceil($data['subtotal_with_discount']);
        $data['base_subtotal_with_discount'] = ceil($data['base_subtotal_with_discount']);
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            if ($item['product_type'] == "configurable") {
                $parentQty[$item['item_id']] = $item['qty'];
                $parentIds[$item['item_id']] = $item['product_id'];
                unset($data['items'][$i]);
                $i++;
                continue;
            }
            $product_id = $item['product_id'];// Simple Product id
            $parentId = $parentIds[$item['parent_item_id']]; //this is the id of configurable product
            $obj = Mage::getModel('catalog/product')->load($parentId);// load configurable product
            if ($item['price'] == 0) {
                $price = Mage::helper('core')->currency($obj->getFinalPrice(), false, false);
                $rowTotal = ($price * $parentQty[$item['parent_item_id']]);
                $data['items'][$i]['sale_price'] = '';
                $data['items'][$i]['actual_price'] = ceil(Mage::helper('core')->currency($obj->getPrice(), false, false));
                $data['items'][$i]['final_price'] = ceil(Mage::helper('core')->currency($obj->getFinalPrice(), false, false));
                if($data['items'][$i]['actual_price'] != $data['items'][$i]['final_price']){
                    $data['items'][$i]['sale_price'] = ceil(Mage::helper('core')->currency($obj->getFinalPrice(), false, false));
                }
                $data['items'][$i]['price'] = ceil($price);
                $data['items'][$i]['row_total'] = ceil($rowTotal);
                $subtotal += $price;
                $data['items'][$i]['qty'] = $parentQty[$item['parent_item_id']];
                $data['items'][$i]['name'] = $obj->getName();
                $data['items'][$i]['description'] = $obj->getDescription();
            }
            $data['items'][$i]['parent_sku'] = $this->getSkuById($parentId);
            $data['items'][$i]['parent_id'] = $parentId;
            $product = Mage::getModel('catalog/product')->load($product_id);
            $image = (string)Mage::helper('catalog/image')->init($product, 'small_image');
            $product_attributes = array();
            $attributes = $product->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getIsVisibleOnFront()) {
                    $value = $attribute->getFrontend()->getValue($product);
                    $product_attributes[$attribute->getAttributeCode()] = $value;
                }
            }
            $itemCat = $obj->getCategoryIds();
            if (sizeof($itemCat) > 0) {
                for($k = sizeof($itemCat); $k >= 0; $k--) {
                    $category = Mage::getModel('catalog/category')->load($itemCat[$k]);
                    if ($category->getIsActive()) {
                        $cidtoshow = $itemCat[$k];
                        break;
                    }
                }
            }
            $data['items'][$i]['category'] = $cidtoshow;
            $data['items'][$i]['img'] = $image;
            $data['items'][$i]['img2'] = $product->getImageUrl();
            if (trim(Mage::getStoreConfig('api/emapi/cdn_url')) != "") {
                $data['items'][$i]['img'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$image);
                $data['items'][$i]['img2'] = str_replace(trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)),trim(Mage::getStoreConfig('api/emapi/cdn_url')),$product->getImageUrl());
            }

            if ($product->getData('color')) {
                $data['items'][$i]['color_id'] = $product->getData('color');
                $data['items'][$i]['color_value'] = $product->getAttributeText('color');
            }
            if ($product->getData('size')) {
                $data['items'][$i]['size_id'] = $product->getData('size');
                $data['items'][$i]['size_value'] = $product->getAttributeText('size');
            }
            if ($obj->getData('manufacturer')) {
                $data['items'][$i]['manufacturer_id'] = $obj->getData('manufacturer');
                $data['items'][$i]['manufacturer'] = $obj->getAttributeText('manufacturer');
            } else {
                $data['items'][$i]['manufacturer_id'] = $product->getData('manufacturer');
                $data['items'][$i]['manufacturer'] = $product->getAttributeText('manufacturer');
            }
            $inStockQty = (int)$product->getStockItem()->getQty();
            $maxSaleQty = $product->getStockItem()->getMaxSaleQty();
            $data['items'][$i]['max_sale_qty'] = $maxSaleQty;
            /*if ($maxSaleQty < $inStockQty) {
                $data['items'][$i]['max_sale_qty'] = $maxSaleQty;
            } else {
                $data['items'][$i]['max_sale_qty'] = $inStockQty;
            }*/
            $data['items'][$i]['in_stock_qty'] = $inStockQty;
            $data['items'][$i]['currency'] = __(trim($data['quote_currency_code']));
            $data['items'][$i]['min_sale_qty'] = $product->getStockItem()->getMinSaleQty();
            $i++;
        }
        if (intval($data['subtotal']) == 0) {
            $data['subtotal'] = ceil($subtotal);
            $data['subtotal_with_discount'] = ceil($subtotal);
        }
        $data['items'] = array_values($data['items']);
        if (empty($data['items'])) {
            $data['items'] = new stdClass();
            $data['items']->complexObjectArray = array();
        } else {
            $dataArray = $data['items'];
            $data['items'] = new stdClass();
            $data['items']->complexObjectArray = $dataArray;
        }
        return $data;
    }

    /**
     * Function to apply discount coupon to the current cart
     *
     * @access public
     * @params Int qid, string coupon code
     * @return Array with status of 0 or 1 and cart data
     *
     */
    public function applyCouponAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $quoteId = $this->getRequest()->getPost('qid');
        $coupon = $this->getRequest()->getPost('coupon');
        $response = array('success' => 0, 'message' => '');
        if (Mage::getStoreConfig('api/emapi/applyCoupon')) {
            try {
                $codeLength = strlen($coupon);
                $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;

                $newQuote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $newQuote->getShippingAddress()->setCollectShippingRates(true);

                $newQuote->setCouponCode(strlen($coupon) ? $coupon : '')
                    ->collectTotals()
                    ->save();
                if ((int)Mage::getStoreConfig('api/emapi/coupondelay') > 0) {
                    sleep((int)Mage::getStoreConfig('api/emapi/coupondelay'));
                }
                $couponQuote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                if ($isCodeLengthValid && $coupon == $couponQuote->getCouponCode()) {
                    $response['success'] = '1';
                    $response['message'] = $this->__('Coupon applied successfully.');
                } else {
                    $response['message'] = $this->__('Coupon code  is not valid.');

                }
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['message'] = $e->getMessage();
                }
                if (is_null($response['message'])) {
                    $response['message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['message'], '_')) {
                    $response['message'] = Mage::helper('emapi')->checkError($response['message']);
                }
            }
        } else {
            parent::setProxy();
            try {
                $proxy = $this->proxy;
                $sessionId = $proxy->login((object)array('username' => $this->API_USER, 'apiKey' => $this->API_KEY));
                $sessionId = $sessionId->result;
                $proxy->shoppingCartCouponAdd((object)array('sessionId' => $sessionId, 'quoteId' => $quoteId, 'couponCode' => $coupon));
                $response['message'] = 'Coupon applied successfully';
                $response['success'] = 1;
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                $response['message'] = $e->getMessage();
            }
        }
        if ((int)Mage::getStoreConfig('api/emapi/delay') > 0) {
            sleep((int)Mage::getStoreConfig('api/emapi/delay'));
        }
        $cartData = $this->info($quoteId);
        $response['cart'] = $cartData;
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to add product to cart
     *
     * @access public
     * @params Int qid (optional), sid (optional), pid (product id), json array of configurable options
     * @return Array with status of 0 or 1 and success message
     *
     */
    public function addProductAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $quoteId = $this->getRequest()->getPost('qid');
        $productId = $this->getRequest()->getPost('pid');
        $qty = $this->getRequest()->getPost('qty');
        $custom_options = $this->getRequest()->getPost('custom_options');
        if ($quoteId == "") {
            $quoteId = $this->createCartEmbedded();// return quoteID
        } else {// Check quote is exit in database or not  if not then regenrate the quote
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            if (!$quote->getId()) {
                $quoteId = $this->createCartEmbedded();// return quoteID
                Mage::log('Soap less error on Progos_Emapi_CartSoapController addProductAction action1.. ' . "Quote id not created in 2nd attempt and recreate it again " . '\n' . $quoteId . '\n', null, 'mobile_app.log');
            }
        }
        $custom_options = str_replace(array("\\n", "\\"), array("", ""), $custom_options);
        $custom_options = json_decode($custom_options, true);
        ksort($custom_options);
        if (!$qty) {
            $qty = 1;
        }
        $response = array('success' => 0, 'message' => '', 'res' => false);
        try {
            $products = array(array(
                'product_id' => $productId,
                'qty' => $qty,
                'options' => null,
                'super_attribute' => $custom_options,
                'bundle_option' => null,
                'bundle_option_qty' => null,
                'links' => null
            ));
            $productMdl = Mage::getModel('emapi/product');
            $res = $productMdl->add($quoteId, $products, Mage::app()->getStore());
            $response['success'] = 1;
            $response['sid'] = '';
            $response['qid'] = $quoteId;
            $response['res'] = true;
            $response['message'] = 'Product added successfully';
            if ((int)Mage::getStoreConfig('api/emapi/delay') > 0) {
                sleep((int)Mage::getStoreConfig('api/emapi/delay'));
            }
            $cartData = $this->info($quoteId);
            $response['cart'] = $cartData; // error or result
        } catch (Exception $e) {
            $response['error_code'] = $e->getCode();
            if (method_exists($e, 'getCustomMessage')) {
                $response['error_message'] = $e->getCustomMessage();
            } elseif (method_exists($e, 'getMessage')) {
                $response['error_message'] = $e->getMessage();
            }
            if (is_null($response['error_message'])) {
                $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
            } elseif (strstr($response['error_message'], '_')) {
                $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
            }
            $response['qid'] = $quoteId;
            $response['sid'] = '';
            $errorMessage = strtolower($response['error_message']);
            $locale = Mage::app()->getLocale()->getLocaleCode();
            if (strstr($errorMessage, "out of stock") || strstr($errorMessage, "maximum quantity allowed")) {
                $response['message'] = $errorMessage;
            } elseif (substr(trim($locale), 0, 2) != "en") {
                $response['message'] = $errorMessage;
            } else {
                $response['message'] = "Please try again, product is not added to cart";
            }
            Mage::log('Soap less error on Progos_Emapi_CartSoapController addProductAction action.. ' . $response['error_message'] . '\n' . $e->getCode() . '\n', null, 'mobile_app.log');
        }

        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to remove product from cart
     *
     * @access public
     * @params Int qid, pid
     * @return Array with status of 0 or 1 and success message
     *
     */
    public function removeProductAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $productId = (int)$this->getRequest()->getPost('pid');// simple product id
        $quoteId = $this->getRequest()->getPost('qid');
        if ($productId) {
            try {
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $deleted = false;
                $items = $quote->getItemsCollection(false);
                foreach ($items as $item) {
                    if ($item->getProduct()->getId() == $productId) {
                        $item_id = $item->getParentItemId();
                        $quote->removeItem($item_id)->save();
                        $deleted = true;
                        break;
                    }
                }
                if ($deleted) {
                    $quote->collectTotals()->save();
                    $response['success'] = 1;
                    $response['message'] = 'Product removed successfully';
                    $response['res'] = true;
                    $response['sid'] = '';//$session->getSessionId();
                    $response['qid'] = $quoteId;
                } else {
                    $response['success'] = 0;
                    $response['message'] = 'Failed to remove the product';
                    $response['res'] = false;
                    $response['sid'] = '';//$session->getSessionId();
                    $response['qid'] = $quoteId;
                }
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['error_message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['error_message'] = $e->getMessage();
                }
                if (is_null($response['error_message'])) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['error_message'], '_')) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
                }
                $response['res'] = false;
                $response['sid'] = '';//$session->getSessionId();
                $response['qid'] = $quoteId;
                $response['message'] = 'Try Again, Failed to remove product from cart';
                Mage::log('soap less error on Progos_Emapi_CartSoapController removeProductAction action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }

        } else {
            $response['success'] = 0;
            $response['error_message'] = 'product id cannot be empty';
            $response['res'] = false;
            $response['sid'] = '';
            $response['qid'] = $quoteId;
            $response['message'] = 'Try Again, Failed to remove product from cart';
            Mage::log('soap less error on Progos_Emapi_CartSoapController removeProductAction  action.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
        }
        if ((int)Mage::getStoreConfig('api/emapi/delay') > 0) {
            sleep((int)Mage::getStoreConfig('api/emapi/delay'));
        }
        $cartData = $this->info($quoteId);
        $response['cart'] = $cartData;

        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to update product quantity in cart
     *
     * @access public
     * @params Int qid, pid, qty
     * @return Array with status of 0 or 1 and success message
     *
     */
    public function updateProductQtyAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $productId = (int)$this->getRequest()->getPost('pid');
        $qty = $this->getRequest()->getPost('qty');
        $quoteId = $this->getRequest()->getPost('qid');
        $response = array('success' => 0, 'message' => '', 'res' => false);
        if ($productId and $qty) {
            try {
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $items = $quote->getItemsCollection(false);
                foreach ($items as $item) {
                    if ($item->getProduct()->getId() == $productId) {
                        $item_id = $item->getParentItemId();
                        $quoteItem = $quote->getItemById($item_id);
                        $quoteItem->setQty($qty)->save();
                        break;
                    }
                }
                $quote->collectTotals()->save();
                if ((int)Mage::getStoreConfig('api/emapi/updatedelay') > 0) {
                    sleep((int)Mage::getStoreConfig('api/emapi/updatedelay'));
                }
                $response['success'] = 1;
                $response['message'] = 'Product qty updated successfully';
                $response['res'] = true;
                $response['sid'] = '';//$session->getSessionId();
                $response['qid'] = $quoteId;
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['error_message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['error_message'] = $e->getMessage();
                }
                if (is_null($response['error_message'])) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($response['error_message'], '_')) {
                    $response['error_message'] = Mage::helper('emapi')->checkError($response['error_message']);
                }
                $response['message'] = "Please try again, product qty not updated";
                $response['res'] = false;
                $response['sid'] = '';//$session->getSessionId();
                $response['qid'] = $quoteId;
                Mage::log('error on Progos_Emapi_CartSoapController updateProductQty action1.. ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }
        } else {
            $response['success'] = 0;
            $response['message'] = 'Please try again, product qty not updated';
            $response['res'] = false;
            $response['sid'] = '';
            $response['qid'] = $quoteId;
            Mage::log('error on Progos_Emapi_CartSoapController updateProductQty action2.. ' . $response['message'] . '\n', null, 'mobile_app.log');
        }
        if ((int)Mage::getStoreConfig('api/emapi/delay') > 0) {
            sleep((int)Mage::getStoreConfig('api/emapi/delay'));
        }
        $cartData = $this->info($quoteId);
        $response['cart'] = $cartData;
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    /**
     * Function to add customer address to address book without soap
     *
     * @access public
     * @params Int customerId, Array customerData
     * @return int Id of created address or mage exception
     *
     */
    protected function addCustomerAddressSoapless($customerId, $customerData)
    {
        $customerAddressApiMdl = Mage::getModel('emapi/customer_address_api');
        $result = $customerAddressApiMdl->create($customerId, array(
            'city' => $customerData['city'],
            'country_id' => $customerData['country_id'],
            'postcode' => $customerData['postcode'],
            'street' => array($customerData['street'], $customerData['street2']),
            'telephone' => $customerData['telephone'],
            'region' => $customerData['region'],
            'region_id' => $customerData['region_id'],
            'lastname' => $customerData['lastname'],
            'firstname' => $customerData['firstname'],
            'is_default_billing' => '1',
            'is_default_shipping' => '1'
        ));
        return $result;
    }

    /**
     * Function to add customer address to address book with soap call
     *
     * @access public
     * @params Int customerId, Array customerData
     * @return int Id of created address or mage exception
     *
     */
    protected function addCustomerAddress($sessionId, $customerId, $customerData)
    {
        parent::setProxy();
        $proxy = $this->proxy;
        $result = $proxy->customerAddressCreate((object)array('sessionId' => $sessionId, 'customerId' => $customerId, 'addressData' => ((object)array(
            'city' => $customerData['city'],
            'country_id' => $customerData['country_id'],
            'postcode' => $customerData['postcode'],
            'street' => array($customerData['street'], $customerData['street2']),
            'telephone' => $customerData['telephone'],
            'region' => $customerData['region'],
            'region_id' => $customerData['region_id'],
            'lastname' => $customerData['lastname'],
            'firstname' => $customerData['firstname'],
            'is_default_billing' => '1',
            'is_default_shipping' => '1'
        ))));
        return $result->result;
    }

    /**
     * Function to get available payment and shipment list
     *
     * @access public
     * @params Int quoteId
     * @return Array of payment and shipments available for provided quoteId
     *
     */
    public function getPaymentShipmentList($quoteId)
    {
        $storeId = $store = Mage::app()->getStore()->getId();
        $response = array('status' => 0, 'payment' => array(), 'shipping' => array());
        parent::setProxy();
        $proxy = $this->proxy;
        $sessionId = parent::loginembedded();
        try {
            //getting available billing and shipping methods
            $result = $proxy->shoppingCartPaymentList((object)array('sessionId' => $sessionId, 'quoteId' => $quoteId));
            $result = $result->result;
            $resultshipping = $proxy->shoppingCartShippingList((object)array('sessionId' => $sessionId, 'quoteId' => $quoteId));
            $resultshipping = $resultshipping->result;
            $p = 0;
            if (sizeof($result->complexObjectArray) == 1) {// used for correct the array indexes
                $result->complexObjectArray = array($result->complexObjectArray);
            }
            foreach ($result->complexObjectArray as $r) {
                if ($r->code != 'telrpayments_cc') {
                    $payment[$p]['code'] = $r->code;
                    $payment[$p]['title'] = $r->title;
                    $payment[$p]['price'] = 0;
                    if ($r->code == 'msp_cashondelivery') {
                        $_quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                        $address = $_quote->getShippingAddress();
                        Mage::getModel('msp_cashondelivery/quote_total')->collect($address);
                        $zoneType = $address->getCountryId() == Mage::getStoreConfig('shipping/origin/country_id', $storeId) ? 'local' : 'foreign';
                        if ($zoneType == 'local')
                            $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
                        else
                            $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

                        //add additional fee if applicable
                        $amount = Mage::helper('emapi')->addAdditionalFeel($amount, $_quote->getBillingAddress()->getCountry(), $address->getCountry());

                        if (strtolower($address->getCountryId()) == "iq") {// this condition should be dynamic for msp charges for specfic stores
                            $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');
                        }
                        $payment[$p]['price'] = ceil(Mage::helper('core')->currency($amount, false, false));
                    }
                    $p++;
                }
            }
            $p = 0;
            if (sizeof($resultshipping->complexObjectArray) == 1) {//correct the array indexes
                $resultshipping->complexObjectArray = array($resultshipping->complexObjectArray);
            }
            foreach ($resultshipping->complexObjectArray as $r) {
                $shipping[$p]['code'] = $r->code;
                $shipping[$p]['title'] = ($r->method_title == null) ? $r->carrier_title : $r->method_title;
                $shipping[$p]['price'] = ceil(Mage::helper('core')->currency($r->price, false, false));
                $p++;
            }
            $response['status'] = 1;
            $response['payment'] = $payment;
            $response['shipping'] = $shipping;
        } catch (Exception $e) {
            if (method_exists($e, 'getCustomMessage')) {
                $errorMessage = $e->getCustomMessage();
            } elseif (method_exists($e, 'getMessage')) {
                $errorMessage = $e->getMessage();
            }
            if (is_null($errorMessage)) {
                $errorMessage = Mage::helper('emapi')->checkError($e->getMessage());
            } elseif (strstr($errorMessage, '_')) {
                $errorMessage = Mage::helper('emapi')->checkError($errorMessage);
            }
            Mage::log('Soap error on getting payment and shipping list' . $errorMessage . '\n', null, 'mobile_app.log');
        }

        return $response;
    }


    /**
     * Function to get available payment and shipment list without soap calls
     *
     * @access public
     * @params string shipping country, string billing ountry, int order subtotal, int order subtotal with discount
     * @return Array of payment and shipments available
     *
     */

    public function getPaymentShipmentListSoapless($shippingCountry, $billingCountry, $orderSubtotal)
    {
        if (Mage::getStoreConfig('api/emapi/enableCachedPaymentShipment')) {
            try {
                $storeId = $store = Mage::app()->getStore()->getId();
                $payment = $this->getPaymentMethods($billingCountry);
                //Conition for shipping methods
                $rulesArr = array();
                if ($shippingCountry == "AE") {
                    $method = "freeshipping_freeshipping";
                    $price = 0;
                } else {
                    $tableRateCharges = $this->getTableRateCharges($shippingCountry);
                    $method = "tablerate_bestway";
                    if (sizeof($tableRateCharges) == 1) {
                        $price = $tableRateCharges['min']['price'];
                    } else {
                        if ($orderSubtotal > $tableRateCharges['min']['condition_value'] && $orderSubtotal < $tableRateCharges['max']['condition_value']) {
                            $price = $tableRateCharges['min']['price'];
                        } else {
                            $price = $tableRateCharges['max']['price'];
                        }
                    }
                }
                $shipping[0]['code'] = $method;
                $shipping[0]['title'] = "&nbsp";
                $shipping[0]['price'] = ceil(Mage::helper('core')->currency($price, false, false));

                $response['status'] = 1;
                $response['payment'] = $payment;
                $response['shipping'] = $shipping;
            } catch (Exception $e) {
                if (method_exists($e, 'getCustomMessage')) {
                    $errorMessage = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $errorMessage = $e->getMessage();
                }
                if (is_null($errorMessage)) {
                    $errorMessage = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($errorMessage, '_')) {
                    $errorMessage = Mage::helper('emapi')->checkError($errorMessage);
                }
                Mage::log('Soap less error on getting payment and shipping list' . $errorMessage . '\n', null, 'mobile_app.log');
            }
        }else{
            try {
                $storeId = $store = Mage::app()->getStore()->getId();
                $codEnabled = false;
                $allowSpecific = Mage::getStoreConfig('payment/msp_cashondelivery/allowspecific', $storeId);
                if ($allowSpecific) {
                    $allowedCountries = Mage::getStoreConfig('payment/msp_cashondelivery/specificcountry', $storeId);
                    $allowedCountriesArr = explode(',', $allowedCountries);
                    if (in_array($billingCountry, $allowedCountriesArr)) {
                        $codEnabled = true;
                    }
                }else{
                    $codEnabled = true;
                }
                if($codEnabled) {
                    //conditio for COD
                    $payment[0]['code'] = "msp_cashondelivery";
                    $payment[0]['title'] = "Cash on delivery";
                    $zoneType = ($billingCountry == Mage::getStoreConfig('shipping/origin/country_id', $storeId)) ? 'local' : 'foreign';
                    if ($zoneType == 'local')
                        $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
                    else
                        $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

                    //add additional fee if applicable
                    $amount = Mage::helper('emapi')->addAdditionalFeel($amount, $billingCountry, $shippingCountry);

                    if (strtolower($billingCountry) == "iq") {
                        $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');;
                    }
                    $payment[0]['price'] = ceil(Mage::helper('core')->currency($amount, false, false));
                    $payment[0]['description'] = Mage::getStoreConfig('payment/msp_cashondelivery/appMethodDescription', Mage::app()->getStore()->getId());

                    //condition for CC
                    $allowSpecific = Mage::getStoreConfig('payment/telrtransparent/allowspecific', $storeId);
                    if ($allowSpecific) {
                        $allowedCountries = Mage::getStoreConfig('payment/telrtransparent/specificcountry', $storeId);
                        $allowedCountriesArr = explode(',', $allowedCountries);
                        if (in_array($billingCountry, $allowedCountriesArr)) {
                            $payment[1]['code'] = "telrtransparent";
                            $payment[1]['title'] = "Credit Card";
                            $payment[1]['price'] = 0;
                            $payment[1]['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
                        }
                    } else {
                        $payment[1]['code'] = "telrtransparent";
                        $payment[1]['title'] = "Credit Card";
                        $payment[1]['price'] = 0;
                        $payment[1]['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
                    }
                }else{
                    //condition for CC
                    $allowSpecific = Mage::getStoreConfig('payment/telrtransparent/allowspecific', $storeId);
                    if ($allowSpecific) {
                        $allowedCountries = Mage::getStoreConfig('payment/telrtransparent/specificcountry', $storeId);
                        $allowedCountriesArr = explode(',', $allowedCountries);
                        if (in_array($billingCountry, $allowedCountriesArr)) {
                            $payment[0]['code'] = "telrtransparent";
                            $payment[0]['title'] = "Credit Card";
                            $payment[0]['price'] = 0;
                            $payment[0]['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
                        }
                    } else {
                        $payment[0]['code'] = "telrtransparent";
                        $payment[0]['title'] = "Credit Card";
                        $payment[0]['price'] = 0;
                        $payment[0]['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
                    }
                }
                //Conition for shipping methods
                $rulesArr = array();
                if ($shippingCountry == "AE") {
                    $method = "freeshipping_freeshipping";
                    $price = 0;
                } else {
                    $method = "tablerate_bestway";
                    $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                    $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='" . $shippingCountry . "'";
                    $rows = $connection->fetchAll($sql);
                    if(!$rows){
                        $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='0'";
                        $rows = $connection->fetchAll($sql);
                    }
                    $i = 0;
                    if(sizeof($rows) == 1){
                        $price = $rows[0]['price'];
                    }else {
                        foreach ($rows as $row) {
                            if ($i == 0) {
                                $minArr[] = $row['condition_value'];
                                $minArr[] = $row['price'];
                            } else {
                                $maxArr[] = $row['condition_value'];
                                $maxArr[] = $row['price'];
                            }
                            $i++;
                        }
                        if ($orderSubtotal > $minArr[0] && $orderSubtotal < $maxArr[0]) {
                            $price = $minArr[1];
                        } else {
                            $price = $maxArr[1];
                        }
                    }
                }
                $shipping[0]['code'] = $method;
                $shipping[0]['title'] = "&nbsp";
                $shipping[0]['price'] = ceil(Mage::helper('core')->currency($price, false, false));

                $response['status'] = 1;
                $response['payment'] = $payment;
                $response['shipping'] = $shipping;
            } catch (Exception $e) {
                if (method_exists($e, 'getCustomMessage')) {
                    $errorMessage = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $errorMessage = $e->getMessage();
                }
                if (is_null($errorMessage)) {
                    $errorMessage = Mage::helper('emapi')->checkError($e->getMessage());
                } elseif (strstr($errorMessage, '_')) {
                    $errorMessage = Mage::helper('emapi')->checkError($errorMessage);
                }
                Mage::log('Soap less error on getting payment and shipping list' . $errorMessage . '\n', null, 'mobile_app.log');
            }
        }
        return $response;
    }

    private function getPaymentMethods($billingCountry){
        /*
         * Check if data available in cache
         */
        if(Mage::app()->useCache('apitablerate')) {
            $storeId = $store = Mage::app()->getStore()->getId();
            $cacheId = '"api_paymentMethods_' . $storeId . '_' . $billingCountry;
            $fpcModel = Mage::getModel('fpccache/fpc');
            $fpcModel->setKeyShipping($cacheId);
            $payment = $fpcModel->getData();
            if (!empty($payment)) {
                return (json_decode($payment,true));
            }
        }
        $storeId = $store = Mage::app()->getStore()->getId();
        $codEnabled = false;
        $allowSpecific = Mage::getStoreConfig('payment/msp_cashondelivery/allowspecific', $storeId);
        if ($allowSpecific) {
            $allowedCountries = Mage::getStoreConfig('payment/msp_cashondelivery/specificcountry', $storeId);
            $allowedCountriesArr = explode(',', $allowedCountries);
            if (in_array($billingCountry, $allowedCountriesArr)) {
                $codEnabled = true;
            }
        }else{
            $codEnabled = true;
        }
        $cod = array();
        $cc = array();
        if($codEnabled) {
            //conditio for COD
            $cod['code'] = "msp_cashondelivery";
            $cod['title'] = "Cash on delivery";
            $zoneType = ($billingCountry == Mage::getStoreConfig('shipping/origin/country_id', $storeId)) ? 'local' : 'foreign';
            if ($zoneType == 'local')
                $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_local', $storeId);
            else
                $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign', $storeId);

            //add additional fee if applicable
            $amount = Mage::helper('emapi')->addAdditionalFeel($amount, $billingCountry, $billingCountry);

            if (strtolower($billingCountry) == "iq") {
                $amount = (float)Mage::getStoreConfig('payment/msp_cashondelivery/standard_fixed_fee_foreign_iq');;
            }
            $cod['price'] = ceil(Mage::helper('core')->currency($amount, false, false));
            $cod['description'] = Mage::getStoreConfig('payment/msp_cashondelivery/appMethodDescription', Mage::app()->getStore()->getId());

            //condition for CC
            $allowSpecific = Mage::getStoreConfig('payment/telrtransparent/allowspecific', $storeId);
            if ($allowSpecific) {
                $allowedCountries = Mage::getStoreConfig('payment/telrtransparent/specificcountry', $storeId);
                $allowedCountriesArr = explode(',', $allowedCountries);
                if (in_array($billingCountry, $allowedCountriesArr)) {
                    $cc['code'] = "telrtransparent";
                    $cc['title'] = "Credit Card";
                    $cc['price'] = 0;
                    $cc['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
                }
            } else {
                $cc['code'] = "telrtransparent";
                $cc['title'] = "Credit Card";
                $cc['price'] = 0;
                $cc['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
            }
        }else{
            //condition for CC
            $allowSpecific = Mage::getStoreConfig('payment/telrtransparent/allowspecific', $storeId);
            if ($allowSpecific) {
                $allowedCountries = Mage::getStoreConfig('payment/telrtransparent/specificcountry', $storeId);
                $allowedCountriesArr = explode(',', $allowedCountries);
                if (in_array($billingCountry, $allowedCountriesArr)) {
                    $cc['code'] = "telrtransparent";
                    $cc['title'] = "Credit Card";
                    $cc['price'] = 0;
                    $cc['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
                }
            } else {
                $cc['code'] = "telrtransparent";
                $cc['title'] = "Credit Card";
                $cc['price'] = 0;
                $cc['description'] = Mage::getStoreConfig('payment/telrtransparent/appMethodDescription', Mage::app()->getStore()->getId());
            }
        }
        if(!empty($cod)){
            $payment[] = $cod;
        }
        if(!empty($cc)){
            $payment[] = $cc;
        }
        if(Mage::app()->useCache('apitablerate')) {
            $tags = array();
            $tags[] = sha1("apitablerate");
            $tags[] = sha1("apipaymentMethods_" . $storeId . "_" . $billingCountry);
            $fpcModel->saveFpc($payment, $cacheId, $tags);
        }
        return $payment;
    }

    Private function getTableRateCharges($shippingCountry){
        /*
         * Check if data available in cache
         */
        if(Mage::app()->useCache('apitablerate')) {
            $storeId = $store = Mage::app()->getStore()->getId();
            $cacheId = '"api_tablerate_' . $storeId . '_' . $shippingCountry;
            $fpcModel = Mage::getModel('fpccache/fpc');
            $fpcModel->setKeyShipping($cacheId);
            $cacheData = $fpcModel->getData();
            if (!empty($cacheData)) {
                return json_decode($cacheData,true);
            }
        }
        $tableRateCharges = array();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='" . $shippingCountry . "'";
        $rows = $connection->fetchAll($sql);
        if(!$rows){
            $sql = "SELECT condition_value, price FROM `shipping_tablerate` WHERE `dest_country_id`='0'";
            $rows = $connection->fetchAll($sql);
        }
        $i = 0;
        if(sizeof($rows) == 1){
            $tableRateCharges['min']['price'] = $rows[0]['price'];
        }else {
            foreach ($rows as $row) {
                if ($i == 0) {
                    $tableRateCharges['min']['condition_value'] = $row['condition_value'];
                    $tableRateCharges['min']['price'] = $row['price'];
                } else {
                    $tableRateCharges['max']['condition_value'] = $row['condition_value'];
                    $tableRateCharges['max']['price'] = $row['price'];
                }
                $i++;
            }
        }
        if(Mage::app()->useCache('apitablerate')) {
            $tags = array();
            $tags[] = sha1("apitablerate");
            $tags[] = sha1("apitablerate_" . $storeId . "_" . $shippingCountry);
            $fpcModel->saveFpc($tableRateCharges, $cacheId, $tags);
        }
        return $tableRateCharges;
    }

    /**
     * Function to set address to the cart called from the userSoapController
     *
     * @access public
     * @params Array data
     * @return Array with status 0 or 1 and appropriate messages
     *
     */
    public function setShippingAddress($data)
    {
        $quoteId = $data['qid'];
        $customerAsGuest = array(
            'customer_id' => $data['customer_id'],
            'mode' => 'customer'
        );
        $address = array(
            "firstname" => $data['firstname'],
            "lastname" => $data['lastname'],
            "street" => $data['street1'],
            "city" => $data['city'],
            "country_id" => $data['country_id'],
            "telephone" => $data['telephone'],
            "postcode" => $data['postcode'],
            "region" => $data['region'],
            "region_id" => $data['region_id'],
            "email" => $data['email'],
            "is_default_shipping" => 0,
            "is_default_billing" => 0
        );
        $shippingAddress = $address;
        $shippingAddress['mode'] = 'shipping';
        $billingAddress = $address;
        $billingAddress['mode'] = 'billing';
        $response = array('status' => 0, 'payment' => array(), 'shipping' => array());
        if (Mage::getStoreConfig('api/emapi/setShippingAddress')) {
            try {
                $shippingCustomerInfo = array();
                $shippingCustomerInfo['address'] = $address;
                $shippingCustomerInfo['customer'] = $customerAsGuest;
                $shippingCustomerInfo['create_address'] = false;
                $shippingCustomerInfo['address_customer_data'] = array();
                $mdlEmapi = Mage::getModel('restmob/quote_index');
                $id = $mdlEmapi->getIdByQuoteId($quoteId);
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $res = $quote->getReservedOrderId();
                if ($id) {
                    $mdlEmapi->load($id);
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setShippingCustomerInfo(json_encode($shippingCustomerInfo));
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->save();
                } else {
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setShippingCustomerInfo(json_encode($shippingCustomerInfo));
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->save();
                }
                $response['status'] = 1;
            }catch (Exception $e) {
                Mage::log('soap less call with soap error on set Shipping Address.. ' . $e->getCustomMessage() . '\n', null, 'mobile_app.log');
            }
            return $response;
        } else {
            if ($data['mode'] == "shipping") {
                $arrAddresses = array(
                    $billingAddress,
                    $shippingAddress
                );
            } else {
                $arrAddresses = array(
                    $billingAddress
                );
            }
            try {
                parent::setPrxy();
                $prxy = $this->prxy;// V1 soap call because via v2 shipping address not set and error through unknown error
                $sessionId1 = $prxy->login($this->API_USER, $this->API_KEY);
                $prxy->call($sessionId1, 'cart_customer.set', array($quoteId, $customerAsGuest));

                $sessionId1 = $prxy->login($this->API_USER, $this->API_KEY);
                $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, $arrAddresses));
                $response['status'] = 1;
            } catch (Exception $e) {
                Mage::log('Soap error on set Shipping Address.. ' . $e->getMessage() . '\n', null, 'mobile_app.log');
            }
        }
        return $response;
    }

    /**
     *
     * Function to set address to the cart for guest users
     * and in case of selecting address other than default address for logged in users
     * and create customers in case of guest users
     * @access public
     * @params Int qid, string mode, string email, string, firstname, string lastname, string street, string city
     * string country_id, string telephone, string postcode, string region, int region_id
     * @return json Array with status 0 or 1 and appropriate messages
     *
     */
    public function setShippingAddressAction()
    {
        if (Mage::app()->getRequest()->getHeader('platform') != "app") {
            return;
        }
        $quoteId = $this->getRequest()->getPost('qid');
        $mode = $this->getRequest()->getPost('mode');
        $storeId = Mage::app()->getWebsite()->getId();
        $email = $this->getRequest()->getPost('email');
        $isUserLoggedIn = $this->getRequest()->getPost('isUserLoggedIn');
        //array for billing and shipping address
        $address_customer_data = array(
            'firstname' => $this->getRequest()->getPost('firstname'),
            'lastname' => $this->getRequest()->getPost('lastname'),
            'street' => $this->getRequest()->getPost('street'),
            'city' => $this->getRequest()->getPost('city'),
            'country_id' => $this->getRequest()->getPost('country_id'),
            'telephone' => $this->getRequest()->getPost('telephone'),
            'postcode' => $this->getRequest()->getPost('postcode'),
            'region' => $this->getRequest()->getPost('region'),
            'region_id' => $this->getRequest()->getPost('region_id'),
            'different' => $this->getRequest()->getPost('diff'),
            'customer_id' => $this->getRequest()->getPost('customer_id')
        );
        $address = array(
            'firstname' => $this->getRequest()->getPost('firstname'),
            'lastname' => $this->getRequest()->getPost('lastname'),
            'street' => $this->getRequest()->getPost('street'),
            'city' => $this->getRequest()->getPost('city'),
            'country_id' => $this->getRequest()->getPost('country_id'),
            'telephone' => $this->getRequest()->getPost('telephone'),
            'postcode' => $this->getRequest()->getPost('postcode'),
            'region' => $this->getRequest()->getPost('region'),
            'region_id' => $this->getRequest()->getPost('region_id'),
            'email' => $this->getRequest()->getPost('email'),
            'is_default_shipping' => 0,
            'is_default_billing' => 0
        );
        $shippingAddress = $address;
        $shippingAddress['mode'] = 'shipping';
        $billingAddress = $address;
        $billingAddress['mode'] = 'billing';
        $response = array('success' => 0, 'message' => '', 'res' => false, 'sid' => "");

        //create customer in case of guest
        $customer = Mage::getModel('customer/customer')->setWebsiteId($storeId)->loadByEmail($email);
        if (!$customer->getId()) {
            $createAddress = true;
            $customer->setEmail($email);
            $customer->setFirstname($address['firstname']);
            $customer->setLastname($address['lastname']);
            $cpass = $customer->generatePassword(10);
            $customer->setPassword($cpass);
            $customer->setCredentials($cpass);
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->save();
            $customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
            $customerAsGuest = array(
                'customer_id' => $customer->getId(),
                'mode' => 'customer'
            );
        } else {
            $createAddress = false;
            $customerAsGuest = array(
                'customer_id' => $customer->getId(),
                'mode' => 'customer'
            );
        }
        if ($mode == "shipping") {
            $arrAddresses = array(
                $shippingAddress,
                $billingAddress
            );
        } else {
            $arrAddresses = array(
                $billingAddress
            );
        }
        if (Mage::getStoreConfig('api/emapi/setShippingAddress')) {
            try {
                if ($createAddress) {
                    if (Mage::getStoreConfig('api/emapi/addCustomerAddress')) {
                        $this->addCustomerAddressSoapless($customer->getId(), $address_customer_data);
                    } else {
                        parent::setProxy();
                        $sessionId = parent::loginembedded();
                        $this->addCustomerAddress($sessionId, $customer->getId(), $address_customer_data);
                    }
                }
                $shippingCustomerInfo = array();
                $shippingCustomerInfo['address'] = $address;
                $shippingCustomerInfo['customer'] = $customerAsGuest;
                $mdlEmapi = Mage::getModel('restmob/quote_index');
                $id = $mdlEmapi->getIdByQuoteId($quoteId);
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                $res = $quote->getReservedOrderId();
                if ($id) {
                    $mdlEmapi->load($id);
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setShippingCustomerInfo(json_encode($shippingCustomerInfo));
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->save();
                } else {
                    $mdlEmapi->setQid($quoteId);
                    $mdlEmapi->setShippingCustomerInfo(json_encode($shippingCustomerInfo));
                    $mdlEmapi->setReservedOrderId($res);
                    $mdlEmapi->save();
                }
                $mdlEmapi = Mage::getModel('restmob/quote_index');
                $id = $mdlEmapi->getIdByQuoteId($quoteId);
                $mdlEmapi->load($id);
                if($mdlEmapi->getShippingCustomerInfo()) {
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $paymentShipment = $this->getPaymentShipmentListSoapless($address['country_id'], $address['country_id'], $quote->getBaseSubtotal());
                    if ($paymentShipment['status']) {
                        if ($mode == "shipping") {
                            $response['message'] = 'Shipping address added successfully';
                        } else {
                            $response['message'] = 'Billing address added successfully';
                        }
                        $response['success'] = 1;
                        $response['res'] = "Address added";
                        $response['payment_methods'] = $paymentShipment['payment'];
                        $response['shipping_methods'] = $paymentShipment['shipping'];
                    } else {
                        $response['success'] = 0;
                        $response['message'] = 'Try again, shipping address not saved';
                        Mage::log('Line 1074: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
                    }
                }else{
                    $response['success'] = 0;
                    $response['message'] = 'Try again, shipping address not saved';
                    Mage::log('Line 1079: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
                }
            }catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                if (method_exists($e, 'getCustomMessage')) {
                    $response['error_message'] = $e->getCustomMessage();
                } elseif (method_exists($e, 'getMessage')) {
                    $response['error_message'] = $e->getMessage();
                }
                if (is_null($response['message'])) {
                    $response['error_message'] = Mage::helper('restmob')->checkError($e->getMessage());
                } elseif (strstr($response['error_message'], '_')) {
                    $response['error_message'] = Mage::helper('restmob')->checkError($response['message']);
                }
                $response['message'] = 'Try again, shipping address not saved';
                Mage::log('Line 1094: address is missing quoteid = '.$quoteId.' and message = ' . $response['error_message'] . '\n', null, 'mobile_app.log');
            }
        } else {
            try {
                if ($createAddress) {
                    if (Mage::getStoreConfig('api/emapi/addCustomerAddress')) {
                        $this->addCustomerAddressSoapless($customer->getId(), $address_customer_data);
                    } else {
                        $sessionId = parent::loginembedded();
                        $this->addCustomerAddress($sessionId, $customer->getId(), $address_customer_data);
                    }
                }
                parent::setPrxy();
                $prxy = $this->prxy;
                $sessionId1 = $prxy->login($this->API_USER, $this->API_KEY);
                $prxy->call($sessionId1, 'cart_customer.set', array($quoteId, $customerAsGuest));
                //to fix the session id expired issue creating the separate session id for each call
                $sessionId1 = $prxy->login($this->API_USER, $this->API_KEY);
                $resultCustomerAddresses = $prxy->call($sessionId1, "cart_customer.addresses", array($quoteId, $arrAddresses));
                $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);

                //new condition to check if address is properly attached to quote
                //$qsa = quote shipping address
                //$qba = quote billing address
                $qsa = $quote->getShippingAddress();
                $qba = $quote->getBillingAddress();
                if (($qsa->getFirstname() == null || $qsa->getLastname() == null
                        || $qsa->getStreet() == null || $qsa->getCity() == null ||
                        $qsa->getCountryId() == null || $qsa->getTelephone() == null
                        || $qsa->getEmail() == null
                    ) &&
                    ($qba->getFirstname() == null || $qba->getLastname() == null
                        || $qba->getStreet() == null || $qba->getCity() == null ||
                        $qba->getCountryId() == null || $qba->getTelephone() == null
                        || $qba->getEmail() == null
                    )
                ) {
                    $response['message'] = 'Try again, shipping address not saved';
                    Mage::log('Line 1131: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
                } else {
                    if (Mage::getStoreConfig('api/emapi/getPaymentShipment')) {
                        $paymentShipment = $this->getPaymentShipmentListSoapless($address['country_id'], $address['country_id'], $quote->getBaseSubtotal());
                    } else {
                        $paymentShipment = $this->getPaymentShipmentList($quoteId);
                    }
                    if ($mode == "shipping") {
                        $response['message'] = 'Shipping address added successfully';
                    } else {
                        $response['message'] = 'Billing address added successfully ';
                    }
                    if ($paymentShipment['status']) {
                        $response['success'] = 1;
                        $response['res'] = $resultCustomerAddresses;
                        $response['payment_methods'] = $paymentShipment['payment'];
                        $response['shipping_methods'] = $paymentShipment['shipping'];
                    } else {
                        $response['success'] = 0;
                        $response['message'] = 'Try again, shipping address not saved';
                        Mage::log('Soap error on set shipping address action2.. ' . $response['message'] . '\n', null, 'mobile_app.log');
                    }
                    $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
                    $shippingAddress = $quote->getShippingAddress();
                    /**
                     * Code added by Naveed Abbas for VAT caclulation
                     */
                    $response['vat']['vat_value'] = 0;
                    $response['vat']['base_vat_value'] = 0;
                    if($shippingAddress->getTaxAmount()){
                        $response['vat']['vat_value'] = $shippingAddress->getTaxAmount();
                        $response['vat']['base_vat_value'] = $shippingAddress->getBaseTaxAmount();
                    }
                }
            } catch (Exception $e) {
                $response['error_code'] = $e->getCode();
                $response['error_message'] = $e->getMessage();
                $response['message'] = 'Try again, shipping address not saved';
                Mage::log('Line 1173: address is missing quoteid = '.$quoteId.' and message = ' . $response['message'] . '\n', null, 'mobile_app.log');
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        die;
    }

    public function getSkuById($productId)
    {
        $productResourceModel = Mage::getResourceModel('catalog/product');
        $adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $adapter->select()
            ->from($productResourceModel->getEntityTable(), 'sku')
            ->where('entity_id = :entity_id');
        $bind = array(':entity_id' => (string)$productId);
        $productSku = $adapter->fetchOne($select, $bind);
        return $productSku;
    }
}

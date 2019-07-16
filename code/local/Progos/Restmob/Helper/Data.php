<?php

class Progos_Restmob_Helper_Data extends Mage_Core_Helper_Abstract {
	public function prepareCollectionForResponse($products, $start, $limit, $total_pages)
    {
        $data['products'] = array();
        if($products->count() > 0) {
            foreach ($products as $product) {
                $smallimg = $product->getImageUrl();
                $img_to_show = (string)Mage::helper('catalog/image')->init($product, 'image')->resize(762,1100);
                $stock = Mage::getSingleton('cataloginventory/stock_item')->loadByProduct($product);
                $cids = $product->getCategoryIds();
                if(empty($cids)) {
                    continue;
                }
                $cidtoshow = "";
                foreach($cids as $scid) {
                    $cidtoshow = $scid;
                }	
                $prod['id'] = $product->getId();
                $prod['total_pages'] = $total_pages;
                $prod['name'] = $product->getName();
                $prod['type'] = $product->getProductType();
                $prod['sku'] = $product->getSku();
                $prod['img'] = $img_to_show;//$image;
                $prod['img2'] = $smallimg;
                $prod['price'] = Mage::helper('core')->currency($product->getPrice(), false, false);
                if($product->getSpecialPrice() && trim($product->getSpecialPrice()) != "") {
                    $prod['sale_price'] = Mage::helper('core')->currency($product->getSpecialPrice(), false, false);
                } else {
                    $prod['sale_price'] = '';
                }
                $prod['stock_qty'] = $stock['qty'];
                $prod['stock_qty_min'] = $stock['min_qty'];
                $prod['stock_qty_min_sales'] = $stock['min_sale_qty'];
                $prod['status'] = $product->getStatus();
                $prod['currency'] = __(Mage::app()->getStore()->getCurrentCurrencyCode());
                $prod['category_id'] = $cidtoshow; //$categoryId;
                $prod['start'] = $start;
                $prod['limit'] = $limit;
                $prod['type'] = $product->getTypeId();
		if($product->getAttributeText('manufacturer') != "" && $product->getAttributeText('manufacturer') !== false) {
                    $prod['manufacturer'] = $product->getAttributeText('manufacturer');
                } else {
                    $prod['manufacturer'] = "";
                }
			
                $data['products'][] = $prod;
            }
            return json_encode($data);
        }
        return json_encode($data);
    }
	//get shiping charges based on shipping country
	//parameters are shipping country and grand total in base currency
	//return COD charges based on applied rule
	public function getCashondeliveryCharges($country){
		$rules_arr = array();
		$rules_arr['AE']['price'] = 5;
		$rules_arr['SA']['price'] = 32;
		$rules_arr['QA']['price'] = 32;
		$rules_arr['BH']['price'] = 32;
		$rules_arr['OM']['price'] = 32;
		$rules_arr['KW']['price'] = 32;
		return $price = $rules_arr[$country]['price'];
		}

	public function getMspStatus(){
		$storeId = Mage::app()->getStore()->getId();
		$mspStatus = Mage::getStoreConfig('api/emapi/mspStatus', $storeId);
		return $mspStatus;
		}

    /**
     * Hassan Ali Shahzad
     * @return $collection
     * Thid function get results native magento search like main site search
     */
    public function getNativeSearchCollection($searchText){
        $storeId = Mage::app()->getStore()->getStoreId();
        // set current store environment
        $appEmulation = Mage::getSingleton("core/app_emulation");
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
        // code taken from core search function app\code\core\Mage\CatalogSearch\controllers\ResultController.php
        // for proper stats update against query searched for app as well previously its not updating stats
        $query = Mage::helper('catalogsearch')->getQuery();// @var $query Mage_CatalogSearch_Model_Query
        if ($query->getQueryText() != '') {
            if (Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            }
            else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity()+1);
                }
                else {
                    $query->setPopularity(1);
                }

                if ($query->getRedirect()){
                    $query->save();
                    $this->getResponse()->setRedirect($query->getRedirect());
                    return;
                }
                else {
                    $query->prepare();
                }
            }
            if (!Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->save();
            }
        }

        // apply full text search right now we are using this
        Mage::getResourceModel('catalogsearch/fulltext')->prepareResult(
            Mage::getModel('catalogsearch/fulltext'),
            $searchText,
            $query
        );

        $collection = Mage::getResourceModel('catalogsearch/fulltext_collection');
        $collection->addSearchFilter(Mage::helper('catalogsearch')->getQuery()->getQueryText());

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // applying filters as on search Layer for my reference app\code\core\Mage\CatalogSearch\Model\Layer.php F:prepareProductCollection
        $collection->setStore($storeId);
        $collection->addAttributeToSelect("*");
        $collection->addMinimalPrice();
        $collection->addFinalPrice();
        $collection->addTaxPercents();
        $collection->addStoreFilter();
        $collection->addUrlRewrite();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
        $collection->getSelect()->order('relevance DESC');
        return $collection;
    }
    public function checkError($message){
        $errorsArr = array(
        'store_not_exists'=> "Can not make operation because store is not exists",
        'quote_not_exists'=> "Can not make operation because quote is not exists",
        'quote_create_fault' => "Can not create a quote. ",
        'quote_already_exists' => "Can not create a quote because quote with such identifier is already exists",
        'required_agreements_are_not_all'=>"You did not set all required agreements",
        'invalid_checkout_type'=> "The checkout type is not valid. Select single checkout type.",
        'guest_checkout_is_not_enabled'=> "Checkout is not available for guest",
        'create_order_fault'=>"Can not create an order. ",
        'invalid_product_data'=> "Product's data is not valid.",
        'add_product_fault'=>"Product(s) could not be added. ",
        'add_product_quote_save_fault'=>"Quote could not be saved during adding product(s) operation.",
        'update_product_fault'=>"Product(s) could not be updated. ",
        'update_product_quote_save_fault'=>"Quote could not be saved during updating product(s) operation.",
        'remove_product_fault'=>"Product(s) could not be removed. ",
        'remove_product_quote_save_fault' => "Quote could not be saved during removing product(s) operation.",
        'customer_not_set_for_quote'=>"Customer is not set for quote.",
        'customer_quote_not_exist'=>"Customer's quote is not existed.",
        'quotes_are_similar'=>"Quotes are identical.",
        'unable_to_move_all_products'=>"Product(s) could not be moved. ",
        'product_move_quote_save_fault'=>"One of quote could not be saved during moving product(s) operation.",
        'customer_not_set'=>"Customer is not set. ",
        'customer_not_exists'=>"The customer's identifier is not valid or customer is not existed",
        'customer_not_created'=> "Customer could not be created. ",
        'customer_data_invalid'=>"Customer data is not valid. ",
        'customer_mode_is_unknown'=>"Customer's mode is unknown",
        'customer_address_data_empty'=>"Customer address data is empty.",
        'customer_address_invalid'=>"Customer's address data is not valid.",
        'invalid_address_id'=>"The customer's address identifier is not valid",
        'address_is_not_set'=>"Customer address is not set.",
        'address_not_belong_customer'=>"Customer address identifier do not belong customer, which set in quote",
        'shipping_address_is_not_set'=>"Can not make operation because of customer shipping address is not set",
        'shipping_method_is_not_available'=>"Shipping method is not available",
        'shipping_method_is_not_set'=>"Can not set shipping method. ",
        'shipping_methods_list_could_not_be_retrived'=>"Can not receive list of shipping methods. ",
        'payment_method_empty'=>"Payment method data is empty.",
        'billing_address_is_not_set'=>"Customer's billing address is not set. Required for payment method data.",
        'shipping_address_is_not_set'=>"Customer's shipping address is not set. Required for payment method data.",
        'method_not_allowed'=>"Payment method is not allowed",
        'payment_method_is_not_set'=>"Payment method is not set. ",
        'quote_is_empty'=>"Coupon could not be applied because quote is empty.",
        'cannot_apply_coupon_code'=>"Coupon could not be applied.",
        'coupon_code_is_not_valid'=>"Coupon is not valid.");
        if(strstr($message, '_')){
            if(array_key_exists(trim($message), $errorsArr)){
                $message = $errorsArr[trim($message)];
            }else{
                $message = "Error occured, Please try again later";
            }
        }
        return $message;
    }

    /*
    * function to add storecredit comments n history
    */

    public function addStoreCreditComments($storecreditId,$orderId,$storecreditTotal,$storecreditSpent){
        //add in history
        $info = array(
            'message_type' => AW_Storecredit_Model_Source_Storecredit_History_Action::BY_ORDER_MESSAGE_VALUE,
            'message_data' => array('order_increment_id' => $orderId)
        );
        Mage::getModel('aw_storecredit/history')
            ->setStorecreditId($storecreditId)
            ->setAction(AW_Storecredit_Model_Source_Storecredit_History_Action::USED_VALUE)
            ->setBalanceDelta($storecreditSpent)
            ->setBalanceAmount($storecreditTotal)
            ->setAdditionalInfo($info)
            ->save()
        ;
    }
}
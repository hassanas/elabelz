<?php
/**
 * @category Progos_PdfPro
 * @package Progos
 * @author Humera batool <humaira.batool@progos.org>
 */
class Progos_PdfPro_Model_Order  extends VES_PdfPro_Model_Order
{
    public function initOrderData($source){
        $order = $source;
        $orderCurrencyCode      = $order->getOrderCurrencyCode();
        $baseCurrencyCode       = $order->getBaseCurrencyCode();

        $stores = $store = Mage::getModel('core/store')->load($order->getStoreId());
        $store_code = $stores->getCode();

        /*
        * $result contains
        * 1.$result['currencycode'] i.e currency according to store
        * 2. $result['currency'] i.e currency code according arabic and english
        */
        $destination = $order->getShippingAddress()->getCountryId();
        $result = Mage::helper('pdfpro')->getDestCurDetail($store_code, $destination);
        $orderCurrencyCode = $result['currencycode'];

        /*
         * Checking if store code and shipping country code are same i.e en_kw = kw or kw = kw
         */
        $store_code_country = explode("_",$store_code);
        $store_code_country = strtoupper($store_code_country[1]);

        $this->setTranslationByStoreId($order->getStoreId());
        $sourceData             =   $order->getData();

        $sourceData['customer'] = $this->getCustomerData(Mage::getModel('customer/customer')->load($order->getCustomerId()));
        $sourceData['created_at_formated']  = $this->getFormatedDate($source->getCreatedAt());
        $sourceData['updated_at_formated']  = $this->getFormatedDate($source->getUpdatedAt());
        /*Init gift message*/
        $sourceData['giftmessage']          = Mage::helper('pdfpro/giftmessage')->initMessage($order);

        $sourceData['billing']              = $this->getAddressData($source->getBillingAddress());
        $sourceData['customer_dob']         = isset($sourceData['customer_dob'])?$this->getFormatedDate($sourceData['customer_dob']):'';
        /*if order is not virtual */
        if(!$source->getIsVirtual())
            $sourceData['shipping']             = $this->getAddressData($source->getShippingAddress());

        /*Get Payment Info */

        Mage::getDesign()->setPackageName('default'); /*Set package to default*/
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->setArea('adminhtml')
            ->toPdf();
        $paymentInfo = str_replace('{{pdf_row_separator}}', "<br />", $paymentInfo);
        $sourceData['payment']          =   array('code'=>$order->getPayment()->getMethodInstance()->getCode(),
            'name'=>$order->getPayment()->getMethodInstance()->getTitle(),
            'info'=>$paymentInfo,
        );
        $sourceData['payment_info']     = $paymentInfo;
        $sourceData['totals']   = array();
        $sourceData['items']    = array();

        $helper = Mage::helper('pdfpro');

        /*
         * Get Items information
        */
        $order_id = Mage::app()->getRequest()->getParam('order_id');
        if($order_id) {
            $order = Mage::getModel('sales/order')->load($order_id);
        }
        elseif(Mage::app()->getRequest()->getParam('tax_order_id')){
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('tax_order_id'));
        }
        else{
            $order = $source;
        }
        $subtotal = 0;
        foreach($order->getAllItems() as $item){
            if($item->getStatus() !== 'Canceled') {
                if ($item->getParentItem()) {
                    continue;
                }
                $itemModel = $this->getItemModel($item);
                $qty_ordered = $item->getQtyOrdered() - $item->getQtyCanceled();
                if(Mage::app()->getRequest()->getParam('tax_order_id')){
                    if($store_code_country == $order->getShippingAddress()->getCountryId()) {
                        /*
                         * if country code and store code are same then there is no need to convert the currency
                         */
                        /* Converted from 40% to 100%*/
                        $fortyPercentPrice = $item->getPrice()* 1;
                        $fortyPercentPriceTotal = $fortyPercentPrice * $qty_ordered;
                        $itemData = array(
                            'name'      => $item->getName(),
                            'sku'       => $item->getSku(),
                            'price'     => $helper->currency($fortyPercentPrice,$result['currency']),
                            'qty'       => $qty_ordered * 1,
                            'tax'       => $helper->currency($item->getTaxAmount(),$result['currency']),
                            'subtotal'  => $helper->currency($fortyPercentPriceTotal,$result['currency']),
                            'row_total' => $helper->currency($fortyPercentPriceTotal,$result['currency'])
                        );

                    }
                    else {
                        /* Converted from 40% to 100%*/
                        $fortyPercentPrice = $item->getBasePrice()* 1;
                        $fortyPercentPriceTotal = $fortyPercentPrice * $qty_ordered;
                        $itemData = array(
                            'name' => $item->getName(),
                            'sku' => $item->getSku(),
                            'price' => $helper->convertCurrencyDestination($fortyPercentPrice, $orderCurrencyCode, $result, true),
                            'qty' => $qty_ordered * 1,
                            'tax' => $helper->convertCurrencyDestination($item->getTaxAmount(), $orderCurrencyCode, $result, true),
                            'subtotal' => $helper->convertCurrencyDestination($fortyPercentPriceTotal, $orderCurrencyCode, $result, true),
                            'row_total' => $helper->convertCurrencyDestination($fortyPercentPriceTotal, $orderCurrencyCode, $result, true)
                        );
                    }
                    $subtotal += $fortyPercentPriceTotal;
                }
                else {
                    if ($store_code_country == $order->getShippingAddress()->getCountryId()) {
                        /*
                         * if country code and store code are same then there is no need to convert the currency
                         */
                        $itemData = array(
                            'name' => $item->getName(),
                            'sku' => $item->getSku(),
                            'price' => $helper->currency($item->getPrice(), $result['currency']),
                            'qty' => $qty_ordered * 1,
                            'tax' => $helper->currency($item->getTaxAmount(), $result['currency']),
                            'subtotal' => $helper->currency($item->getRowTotal(), $result['currency']),
                            'row_total' => $helper->currency($item->getRowTotalInclTax(), $result['currency'])
                        );

                    } else {
                        $itemData = array(
                            'name' => $item->getName(),
                            'sku' => $item->getSku(),
                            'price' => $helper->convertCurrencyDestination($item->getBasePrice(), $orderCurrencyCode, $result, true),
                            'qty' => $qty_ordered * 1,
                            'tax' => $helper->convertCurrencyDestination($item->getBaseTaxAmount(), $orderCurrencyCode, $result, true),
                            'subtotal' => $helper->convertCurrencyDestination($item->getBaseRowTotal(), $orderCurrencyCode, $result, true),
                            'row_total' => $helper->convertCurrencyDestination($item->getBaseRowTotalInclTax(), $orderCurrencyCode, $result, true)
                        );
                    }
                }

                $options = $itemModel->getItemOptions($item);
                $itemData['options'] = array();
                if ($options) {
                    foreach ($options as $option) {
                        $optionData = array();
                        $optionData['label'] = strip_tags($option['label']);

                        if ($option['value']) {
                            $printValue = isset($option['print_value']) ? $option['print_value'] : strip_tags($option['value']);
                            $optionData['value'] = $printValue;
                        }
                        $itemData['options'][] = new Varien_Object($optionData);
                    }
                }
                $itemData = new Varien_Object($itemData);
                Mage::dispatchEvent('ves_pdfpro_data_prepare_after', array('source' => $itemData, 'model' => $item, 'type' => 'item'));
                $sourceData['items'][] = $itemData;
            }
        }

        /*
         * Get Totals information.
        */
        $totals = $this->_getTotalsList($source);
        $totalArr = array();
        foreach ($totals as $total) {
            $total->setOrder($order)
                ->setSource($source);
            if ($total->canDisplay()) {
                $area = $total->getSourceField()=='grand_total'?'footer':'body';
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $totalArr[$area][] = new Varien_Object(array('label'=>$totalData['label'], 'value'=>$totalData['amount']));
                }
            }
        }

        $sourceData['totals'] = new Varien_Object($totalArr);
        $apiKey = Mage::helper('pdfpro')->getApiKey($order->getStoreId(),$order->getCustomerGroupId());
        $sourceData     = new Varien_Object($sourceData);

        Mage::dispatchEvent('ves_pdfpro_data_prepare_after',array('source'=>$sourceData,'model'=>$order,'type'=>'order'));
        $orderData      = new Varien_Object(array('key'=>$apiKey,'data'=>$sourceData));
        $this->revertTranslation();


        //getting storecredit amount
        $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
        foreach ($collection as $col) {
            if($store_code_country == $order->getShippingAddress()->getCountryId()) {
                $storecredit_amount = $col->getStorecreditAmount();
            }
            else{
                $storecredit_amount = $col->getBaseStorecreditAmount();
            }
        }

        if($store_code_country == $order->getShippingAddress()->getCountryId()) {
            /*
             * if country code and store code are same will just add the currency
             */
            $order_data = $order->getGrandTotal();
            $taxGrandTotal = $order->getGrandTotal();
            $order_countryId = $order->getShippingAddress()->getCountryId();

            if($storecredit_amount != 0 && $storecredit_amount != NULL ) {
                $orderData['data']['storecredit_amount'] = $helper->currency($storecredit_amount, $result['currency']);
            }
            else{
                $orderData['data']['storecredit_amount'] = false;
            }

            /*Removing 5% inclusive tax from uk , us , international and egypt countries*/
            if($order_countryId == "AE"){
                $ae_vattax = $order_data * 0.05;
                $total_amount = $order_data -  $ae_vattax;
                $orderData['data']['country_id_check'] = $helper->currency($total_amount, $result['currency']);
            }
            else{
                $orderData['data']['country_id_check'] = false;
            }

            $shipping_description = $order->getShippingAmount();
            $msp_cashondelivery = $order->getMspCashondelivery();

            $shipping_description = $helper->currency($shipping_description, $result['currency']);

            $orderData["data"]['shipping_description']= $shipping_description;

            foreach($orderData as $order) {
                $orderData['data']['created_at_formated']['medium'] = Mage::app()->getLocale()->date(strtotime($sourceData['data']['created_at']))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM));
                foreach ($order as $ord) {
                    if(Mage::app()->getRequest()->getParam('tax_order_id')) {
                        $ord['subtotal'] = $helper->currency($taxGrandTotal, $result['currency']);
                    }
                    else {
                        $ord['subtotal'] = $helper->currency($ord['subtotal'], $result['currency']);
                    }
                    $ord['grand_total'] = $helper->currency($order_data,$result['currency']);
                    if($ord['shipping_amount'] != 0) {
                        $ord['shipping_amount'] = $helper->currency($ord['shipping_amount'], $result['currency']);
                    }
                    else{
                        $ord['shipping_amount'] = false;
                    }
                    $ord['shipping_invoiced'] = $helper->currency($ord['shipping_invoiced'],$result['currency']);

                    if($ord['discount_amount'] != 0) {
                        $ord['discount_amount'] = $helper->currency($ord['discount_amount'], $result['currency']);
                    }
                    else{
                        $ord['discount_amount']= false;
                    }

                    if($order_countryId == "AE") {
                        $ord['tax_amount'] = $helper->currency($ae_vattax, $result['currency']);
                    }
                    else{
                        $ord['tax_amount'] = false;
                    }

                    if($msp_cashondelivery != 0) {
                        $ord['msp_cashondelivery'] = $helper->currency($msp_cashondelivery, $result['currency']);
                    }
                    else{
                        $ord['msp_cashondelivery']= false;
                    }
                    $ord['total_paid'] = $helper->currency($ord['total_paid'],$result['currency']);
                }
            }
        }else{
            /*
             * if country code and store code are different will first convert currency and then add currency sumbol and return it
             */
            $order_data = $order->getBaseGrandTotal();
            $taxGrandTotal = $order->getBaseGrandTotal();
            $order_countryId = $order->getShippingAddress()->getCountryId();

            if($storecredit_amount !=0 && $storecredit_amount !=NULL ) {
                $orderData['data']['storecredit_amount'] = $helper->convertCurrencyDestination($storecredit_amount, $result['currencycode'], $result, true);
            }
            else{
                $orderData['data']['storecredit_amount'] = false;
            }

            /*Removing 5% inclusive tax from uk , us , international and egypt countries*/
            if($order_countryId == "AE"){
                $ae_vattax = $order_data * 0.05;
                $total_amount = $order_data -  $ae_vattax;
                $orderData['data']['country_id_check'] = $helper->convertCurrencyDestination($total_amount, $result['currencycode'], $result, true);
            }
            else{
                $orderData['data']['country_id_check'] = false;
            }

            $shipping_description = $order->getBaseShippingAmount();
            $msp_cashondelivery = $order->getMspBaseCashondelivery();

            $shipping_description = $helper->convertCurrencyDestination($shipping_description, $result['currencycode'], $result, true);

            $orderData["data"]['shipping_description']= $shipping_description;

            foreach($orderData as $order) {
                $orderData['data']['created_at_formated']['medium'] = Mage::app()->getLocale()->date(strtotime($sourceData['data']['created_at']))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM));
                foreach ($order as $ord) {
                    if(Mage::app()->getRequest()->getParam('tax_order_id')) {
                        $ord['subtotal'] = $helper->convertCurrencyDestination($taxGrandTotal,$result['currencycode'], $result, true);
                    }
                    else {
                        $ord['subtotal'] = $helper->convertCurrencyDestination($ord['base_subtotal'], $result['currencycode'], $result, true);
                    }
                    $ord['grand_total'] = $helper->convertCurrencyDestination($order_data,$result['currencycode'], $result, true);
                    if($ord['base_shipping_amount'] != 0) {
                        $ord['shipping_amount'] = $helper->convertCurrencyDestination($ord['base_shipping_amount'], $result['currencycode'], $result, true);
                    }
                    else{
                        $ord['shipping_amount'] = false;
                    }
                    $ord['shipping_invoiced'] = $helper->convertCurrencyDestination($ord['base_shipping_invoiced'],$result['currencycode'], $result, true);

                    if($ord['base_discount_amount'] !=0) {
                        $ord['discount_amount'] = $helper->convertCurrencyDestination($ord['base_discount_amount'], $result['currencycode'], $result, true);
                    }
                    else{
                        $ord['discount_amount']= false;
                    }

                    if($order_countryId == "AE") {
                        $ord['tax_amount'] = $helper->convertCurrencyDestination($ae_vattax, $result['currencycode'], $result, true);
                    }
                    else{
                        $ord['tax_amount'] = false;
                    }

                    if($msp_cashondelivery != 0 ) {
                        $ord['msp_cashondelivery'] = $helper->convertCurrencyDestination($msp_cashondelivery, $result['currencycode'], $result, true);
                    }
                    else{
                        $ord['msp_cashondelivery']= false;
                    }
                    $ord['total_paid'] = $helper->convertCurrencyDestination($ord['base_total_paid'],$result['currencycode'], $result, true);
                }
            }
        }

        return serialize($orderData);

    }
}
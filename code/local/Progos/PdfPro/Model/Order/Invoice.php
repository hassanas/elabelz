<?php
/**
 * @category Progos_PdfPro
 * @package Progos
 * @author Saroop Chand <saroop.chand@progos.org>
 */
class Progos_PdfPro_Model_Order_Invoice  extends VES_PdfPro_Model_Order_Invoice
{
    /**
     * Init invoice data
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return array
     */

    public function initInvoiceDestinationData($invoice){
        $order = $invoice->getOrder();
        $order_new = $invoice->getOrder();
        $orderCurrencyCode  = $order->getOrderCurrencyCode();
        $baseCurrencyCode   = $order->getBaseCurrencyCode();

        $stores = $store = Mage::getModel('core/store')->load($order->getStoreId());
        $store_code = $stores->getCode();

        /*
         * $result contains
         * 1.$result['currencycode'] i.e currency according to store
         * 2. $result['currency'] i.e currency code according arabic and english
         */
        $destination = $invoice->getShippingAddress()->getCountryId();
        $result = Mage::helper('pdfpro')->getDestCurDetail($store_code, $destination);
        $orderCurrencyCode = $result['currencycode'];

        /*
         * Checking if store code and shipping country code are same i.e en_kw = kw or kw = kw
         */
        $store_code_country = explode("_",$store_code);
        $store_code_country = strtoupper($store_code_country[1]);

        $this->setTranslationByStoreId($invoice->getStoreId());
        $invoiceData        =   $invoice->getData();
        $orderData          =   Mage::getModel('pdfpro/order')->initOrderData($order);

        $invoiceData['order']   = unserialize($orderData);
        $invoiceData['customer']= $this->getCustomerData(Mage::getModel('customer/customer')->load($order->getCustomerId()));
        $invoiceData['created_at_formated']     = $this->getFormatedDate($invoice->getCreatedAt());
        $invoiceData['updated_at_formated']     = $this->getFormatedDate($invoice->getUpdatedAt());
        $invoiceData['billing']                 = $this->getAddressData($invoice->getBillingAddress());

        /*if order is not virtual */
        if(!$order->getIsVirtual())
            $invoiceData['shipping']            = $this->getAddressData($invoice->getShippingAddress());
        /*Get Payment Info */
        Mage::getDesign()->setPackageName('default'); /*Set package to default*/
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->setArea('adminhtml')
            ->toPdf();
        $paymentInfo = str_replace('{{pdf_row_separator}}', "<br />", $paymentInfo);
        $invoiceData['payment']         =   array('code'=>$order->getPayment()->getMethodInstance()->getCode(),
            'name'=>$order->getPayment()->getMethodInstance()->getTitle(),
            'info'=>$paymentInfo,
        );
        $invoiceData['payment_info']        = $paymentInfo;

        $invoiceData['shipping_description']    = $order->getShippingDescription();

        $invoiceData['totals']  = array();
        $invoiceData['items']   = array();
        $helper = Mage::helper('pdfpro');
        //Mage::helper('pdfpro')->getDestCurDetail($item->getBasePrice(), $orderCurrencyCode, $orderCurrencyCode);
        /*
         * Get Items information
        */
        foreach($invoice->getAllItems() as $item){
            if($item->getStatus() !== 'Canceled') {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $itemModel = $this->getItemModel($item);

               if($store_code_country == $invoice->getShippingAddress()->getCountryId()) {
                   /*
                    * if country code and store code are same then there is no need to convert the currency
                    */
                   $itemData = array(
                       'name'      => $item->getName(),
                       'sku'       => $item->getSku(),
                       'price'     => $helper->currency($item->getPrice(),$result['currency']),
                       'qty'       => $item->getQty() * 1,
                       'tax'       => $helper->currency($item->getTaxAmount(),$result['currency']),
                       'subtotal'  => $helper->currency($item->getRowTotal(),$result['currency']),
                       'row_total' => $helper->currency($item->getRowTotal(),$result['currency'])
                   );

               }
               else {
                   $itemData = array(
                       'name' => $item->getName(),
                       'sku' => $item->getSku(),
                       'price' => $helper->convertCurrencyDestination($item->getBasePrice(), $orderCurrencyCode, $result, true),
                       'qty' => $item->getQty() * 1,
                       'tax' => $helper->convertCurrencyDestination($item->getBaseTaxAmount(), $orderCurrencyCode, $result, true),
                       'subtotal' => $helper->convertCurrencyDestination($item->getBaseRowTotal(), $orderCurrencyCode, $result, true),
                       'row_total' => $helper->convertCurrencyDestination($item->getBaseRowTotal(), $orderCurrencyCode, $result, true)
                   );
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
            Mage::dispatchEvent('ves_pdfpro_data_prepare_destination_after', array('source' => $itemData, 'model' => $item, 'type' => 'item'));
            $invoiceData['items'][] = $itemData;
        }
    }

        /*
         * Get Totals information.
        */
        $totals = $this->_getTotalsList($invoice);
        $totalArr = array();
        foreach ($totals as $total) {
            $total->setOrder($order)
                ->setSource($invoice);
            if ($total->canDisplay()) {
                $area = $total->getSourceField()=='grand_total'?'footer':'body';
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $totalArr[$area][] = new Varien_Object(array('label'=>$totalData['label'], 'value'=>$totalData['amount']));
                }
            }
        }

        $invoiceData['totals'] = new Varien_Object($totalArr);
        $apiKey = Mage::helper('pdfpro')->getApiKey($order->getStoreId(),$order->getCustomerGroupId());
        $invoiceData    = new Varien_Object($invoiceData);
        Mage::dispatchEvent('ves_pdfpro_data_prepare_after',array('source'=>$invoiceData,'model'=>$invoice,'type'=>'invoice'));
        $invoiceData    = new Varien_Object(array('key'=>$apiKey,'data'=>$invoiceData));
        $this->revertTranslation();

        //getting storecredit amount
        $collection = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
        foreach ($collection as $col) {
            if($store_code_country == $invoice->getShippingAddress()->getCountryId()) {
                $storecredit_amount = $col->getStorecreditAmount();
            }
            else{
                $storecredit_amount = $col->getBaseStorecreditAmount();
            }
        }


        $order = Mage::getModel('sales/order')->load($invoice->getOrderId());

        if($store_code_country == $invoice->getShippingAddress()->getCountryId()) {
            /*
             * if country code and store code are same will just add the currency
             */
            $order_data = $order->getGrandTotal();
            $order_countryId = $order->getShippingAddress()->getCountryId();

            if($storecredit_amount !== 0) {
                $invoiceData['data']['storecredit_amount'] = $helper->currency($storecredit_amount, $result['currency']);
            }else{
                $invoiceData['data']['storecredit_amount'] = false;
            }

            /*Removing 5% inclusive tax from uk , us , international and egypt countries*/
            if($order_countryId == "AE"){
                $ae_vattax = $order_data * 0.05;
                $total_amount = $order_data -  $ae_vattax;
                $invoiceData['data']['country_id_check'] = $helper->currency($total_amount, $result['currency']);
            }
            else{
                $invoiceData['data']['country_id_check'] = false;
            }

            $shipping_description = $order->getMspCashondelivery() + $order->getShippingAmount();
            $msp_cashondelivery = $order->getMspCashondelivery();

            $shipping_description = $helper->currency($shipping_description, $result['currency']);

            $invoiceData["data"]["order"]["data"]['shipping_description']= $shipping_description;

            foreach($invoiceData as $order) {
                $invoiceData['data']['created_at_formated']['medium'] = Mage::app()->getLocale()->date(strtotime($invoiceData['data']['created_at']))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM));
                foreach ($order as $ord) {

                    $ord['subtotal'] = $helper->currency($ord['subtotal'],$result['currency']);
                    $ord['grand_total'] = $helper->currency($order_data,$result['currency']);
                    if($ord['shipping_amount'] !== 0) {
                        $ord['shipping_amount'] = $helper->currency($ord['shipping_amount'], $result['currency']);
                    }else{
                        $ord['shipping_amount']=false;
                    }
                    $ord['shipping_invoiced'] = $helper->currency($ord['shipping_invoiced'],$result['currency']);
                    if($ord['discount_amount'] != 0) {
                        $ord['discount_amount'] = $helper->currency($ord['discount_amount'], $result['currency']);
                    }else{
                        $ord['discount_amount'] = false;
                    }

                    if($order_countryId == "AE") {
                        $ord['tax_amount'] = $helper->currency($ae_vattax, $result['currency']);
                    }
                    else{
                        $ord['tax_amount'] = false;
                    }

                    if($msp_cashondelivery != 0) {
                        $ord['msp_cashondelivery'] = $helper->currency($msp_cashondelivery, $result['currency']);
                    }else{
                        $ord['msp_cashondelivery'] = false;
                    }
                    $ord['total_paid'] = $helper->currency($ord['total_paid'],$result['currency']);
                }
            }
        }else{
            /*
             * if country code and store code are different will first convert currency and then add currency sumbol and return it
             */
            $order_data = $order->getBaseGrandTotal();
            $order_countryId = $order->getShippingAddress()->getCountryId();

            if($storecredit_amount !=0 && $storecredit_amount !=NULL ) {
                $invoiceData['data']['storecredit_amount'] = $helper->convertCurrencyDestination($storecredit_amount, $result['currencycode'], $result, true);
            }
            else{
                $invoiceData['data']['storecredit_amount'] = false;
            }

            if($order_countryId == "AE"){
                $ae_vattax = $order_data * 0.05;
                $total_amount = $order_data -  $ae_vattax;
                $invoiceData['data']['country_id_check'] = $helper->convertCurrencyDestination($total_amount, $result['currencycode'], $result, true);
            }
            else{
                $invoiceData['data']['country_id_check'] = false;
            }

            $shipping_description = $order->getMspBaseCashondelivery()+$order->getBaseShippingAmount();
            $msp_cashondelivery = $order->getMspBaseCashondelivery();

            $shipping_description = $helper->convertCurrencyDestination($shipping_description, $result['currencycode'], $result, true);

            $invoiceData["data"]["order"]["data"]['shipping_description']= $shipping_description;

            foreach($invoiceData as $order) {
                $invoiceData['data']['created_at_formated']['medium'] = Mage::app()->getLocale()->date(strtotime($invoiceData['data']['created_at']))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM));
                foreach ($order as $ord) {
                     $ord['subtotal'] = $helper->convertCurrencyDestination($ord['base_subtotal'],$result['currencycode'], $result, true);
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
                         $ord['discount_amount'] = false;
                     }

                    if($order_countryId == "AE") {
                        $ord['tax_amount'] = $helper->currency($ae_vattax, $result['currency']);
                    }
                    else{
                        $ord['tax_amount'] = false;
                    }

                     if($msp_cashondelivery !=0) {
                         $ord['msp_cashondelivery'] = $helper->convertCurrencyDestination($msp_cashondelivery, $result['currencycode'], $result, true);
                     }
                     else{
                         $ord['msp_cashondelivery'] = false;
                     }
                     $ord['total_paid'] = $helper->convertCurrencyDestination($ord['base_total_paid'],$result['currencycode'], $result, true);
                }
            }
        }

        return serialize($invoiceData);
    }

}

<?php
/**
 * VES_PdfPro_Model_Order_Invoice
 *
 * @author      VnEcoms Team <support@vnecoms.com>
 * @website     http://www.vnecoms.com
 */
class VES_PdfPro_Model_Order_Invoice  extends VES_PdfPro_Model_Abstract
{
    protected $_defaultTotalModel = 'pdfpro/sales_order_pdf_total_default';
    protected $_item_model;
    
    public function getItemModel($item){
        return Mage::getModel('pdfpro/order_invoice_item')->setItem($item);
    }
    
    /**
     * Sort totals list
     *
     * @param  array $a
     * @param  array $b
     * @return int
     */
    protected function _sortTotalsList($a, $b) {
        if (!isset($a['sort_order']) || !isset($b['sort_order'])) {
            return 0;
        }
    
        if ($a['sort_order'] == $b['sort_order']) {
            return 0;
        }
    
        return ($a['sort_order'] > $b['sort_order']) ? 1 : -1;
    }
    /**
     * Get Total List
     * @param Mage_Sales_Model_Order_Invoice $source
     * @return array
     */
    
    protected function _getTotalsList($source)
    {
        $totals = Mage::getConfig()->getNode('global/pdf/totals')->asArray();
        usort($totals, array($this, '_sortTotalsList'));
        $totalModels = array();
        foreach ($totals as $index => $totalInfo) {
            if (!empty($totalInfo['model'])) {
                $totalModel = Mage::getModel($totalInfo['model']);
                if ($totalModel instanceof Mage_Sales_Model_Order_Pdf_Total_Default) {
                    $totalInfo['model'] = $totalModel;
                } else {
                    Mage::throwException(
                        Mage::helper('sales')->__('PDF total model should extend Mage_Sales_Model_Order_Pdf_Total_Default')
                    );
                }
            } else {
                $totalModel = Mage::getModel($this->_defaultTotalModel);
            }
            $totalModel->setData($totalInfo);
            $totalModels[] = $totalModel;
        }
        return $totalModels;
    }
    
    /**
     * Init invoice data
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return array
     */
    
    public function initInvoiceData($invoice){
        $order = $invoice->getOrder();
        $orderCurrencyCode  = $order->getOrderCurrencyCode();
        $baseCurrencyCode   = $order->getBaseCurrencyCode();

        $stores = $store = Mage::getModel('core/store')->load($order->getStoreId());
          $store_code = $stores->getCode();
        
       // getting order currency code for arabic stores
        if($store_code == "ar_sa" && $invoice->getOrderCurrencyCode()=='SAR' ):
           $orderCurrencyCode  = Mage::app()->getStore('ar_sa')->getCurrentCurrencyCode(); 
           $CurrencyCode = "ر.س";
         elseif($store_code == "ar_ae" && $invoice->getOrderCurrencyCode()=="AED" ):
            $orderCurrencyCode  = Mage::app()->getStore('ar_ae')->getCurrentCurrencyCode();
            $CurrencyCode  = "د.إ";   
        elseif($store_code == "ar_qa" && $invoice->getOrderCurrencyCode()=="QAR" ):
            $orderCurrencyCode  = Mage::app()->getStore('ar_qa')->getCurrentCurrencyCode();
            $CurrencyCode  = "ر.ق";
         elseif($store_code == "ar_kw" && $invoice->getOrderCurrencyCode()=="KWD" ):
            $orderCurrencyCode  = Mage::app()->getStore('ar_kw')->getCurrentCurrencyCode();
            $CurrencyCode  = "د.ك";
         elseif(strpos($store_code, 'ar') !== false ):
            $orderCurrencyCode  = "USD";   
            $CurrencyCode  = "$";      
        endif;
        
        //getting order currency code for shipping country as Saudia Arabia
        if($order->getShippingAddress()->getCountryId() == "SA"):
           $orderCurrencyCode  = Mage::app()->getStore('en_sa')->getCurrentCurrencyCode();  
           $CurrencyCode  = "SAR"; 
           if(strpos($store_code, 'ar') !== false ):  
              $CurrencyCode  = "ر.س"; 
            endif;

        endif;

        $this->setTranslationByStoreId($invoice->getStoreId());
        $invoiceData        =   $this->process($invoice->getData(),$orderCurrencyCode,$baseCurrencyCode);
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
        /*
         * Get Items information
        */
        foreach($invoice->getAllItems() as $item){
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            $itemModel  = $this->getItemModel($item);
            if($item->getOrderItem()->getProductType()=='bundle'){
                $itemData = array('is_bundle'=>1,'name'=>$item->getName(),'sku'=>$item->getSku());
                if($itemModel->canShowPriceInfo($item)){
                    $itemData['price']  = Mage::helper('pdfpro')->currency($item->getPrice(),$orderCurrencyCode);
                    $itemData['qty']    = $item->getQty() * 1;
                    $itemData['tax']    = Mage::helper('pdfpro')->currency($item->getTaxAmount(),$orderCurrencyCode);
                    $itemData['subtotal']   = Mage::helper('pdfpro')->currency($item->getRowTotal(),$orderCurrencyCode);
                    $itemData['row_total']  = Mage::helper('pdfpro')->currency($item->getRowTotalInclTax(),$orderCurrencyCode);
                }
                $itemData['sub_items']  = array();
                $items = $itemModel->getChilds($item);
                foreach ($items as $_item) {
                    $bundleItem = array();
                    $attributes = $itemModel->getSelectionAttributes($_item);
                    // draw SKUs
                    if (!$_item->getOrderItem()->getParentItem()) {
                        continue;
                    }
                    $bundleItem['label']    = $attributes['option_label'];
                    /*Product name */
                    if ($_item->getOrderItem()->getParentItem()) {
                        $name = $itemModel->getValueHtml($_item);
                    } else {
                        $name = $_item->getName();
                    }
                    $bundleItem['value']    = $name;
                    /*$bundleItem['sku']        = $_item->getSku();*/
                    /* price */
                    if ($itemModel->canShowPriceInfo($_item)) {
                        $price = $order->formatPriceTxt($_item->getPrice());
                        $bundleItem['price']    = Mage::helper('pdfpro')->currency($_item->getPrice(),$orderCurrencyCode);
                        $bundleItem['qty']      = $_item->getQty() * 1;
                        $bundleItem['tax']      = Mage::helper('pdfpro')->currency($_item->getTaxAmount(),$orderCurrencyCode);
                        $bundleItem['subtotal'] = Mage::helper('pdfpro')->currency($_item->getRowTotal(),$orderCurrencyCode);
                        $bundleItem['row_total']= Mage::helper('pdfpro')->currency($_item->getRowTotalInclTax(),$orderCurrencyCode);
                    }
                    $bundleItem                 = new Varien_Object($bundleItem);
                    Mage::dispatchEvent('ves_pdfpro_data_prepare_after',array('source'=>$bundleItem,'model'=>$_item,'type'=>'item'));
                    $itemData['sub_items'][]    = $bundleItem;
                }
            }else{
                $itemData = array(
                    'name'      => $item->getName(),
                    'sku'       => $item->getSku(),
                    'price'     => Mage::helper('pdfpro')->currency($item->getPrice(),$orderCurrencyCode),
                    'qty'       => $item->getQty() * 1,
                    'tax'       => Mage::helper('pdfpro')->currency($item->getTaxAmount(),$orderCurrencyCode),
                    'subtotal'  => Mage::helper('pdfpro')->currency($item->getRowTotal(),$orderCurrencyCode),
                    'row_total' => Mage::helper('pdfpro')->currency($item->getRowTotalInclTax(),$orderCurrencyCode)
                );

                if(strpos($store_code, 'ar') !== false ):
                    
                     $itemData['price'] = chop($itemData['price'],"Ù«");
                     $itemData['price']= intval(preg_replace('/[^0-9,]+/', '', $itemData['price']));
                     $itemData['price'] = $CurrencyCode." ".$itemData['price'];
                     $itemData['row_total'] = chop($itemData['row_total'],"Ù«");
                     $itemData['row_total']= intval(preg_replace('/[^0-9,]+/', '', $itemData['row_total']));
                     $itemData['row_total'] = $CurrencyCode." ".$itemData['row_total']; 
                     $itemData['tax'] = chop($itemData['tax'],"Ù«");
                     $itemData['tax']= intval(preg_replace('/[^0-9,]+/', '', $itemData['tax']));
                     $itemData['tax'] = $CurrencyCode." ".$itemData['tax'];  
                    endif; 

                // convertion in to sar
                $baseCode = Mage::app()->getBaseCurrencyCode();      

                        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies(); 
                        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCode, array_values($allowedCurrencies));
                        if($invoice->getShippingAddress()->getCountryId() == "SA"):
                        foreach($rates as $rate=>$value):
                             if($rate == "SAR"):
                                $value_rate = $value;
                             endif;
                        endforeach;
                         $price  = Mage::helper('directory')->currencyConvert($item->getBasePrice(), 'AED', 'SAR');
                        $taxamount = Mage::helper('directory')->currencyConvert($item->getBaseTaxAmount(), 'AED', 'SAR');
                        $rowtotal = Mage::helper('directory')->currencyConvert($item->getBaseRowTotal(), 'AED', 'SAR');
                        $baserow = Mage::helper('directory')->currencyConvert($item->getBaseRowTotalInclTax(), 'AED', 'SAR');
                        $itemData = array(
                    'name'      => $item->getName(),
                    'sku'       => $item->getSku(),
                    'price'     => Mage::helper('pdfpro')->currency($price,$orderCurrencyCode),
                    'qty'       => $item->getQty() * 1,
                    'tax'       => Mage::helper('pdfpro')->currency($taxamount,$orderCurrencyCode),
                    'subtotal'  => Mage::helper('pdfpro')->currency($rowtotal,$orderCurrencyCode),
                    'row_total' => Mage::helper('pdfpro')->currency($baserow,$orderCurrencyCode)
                ); 
                    if(strpos($store_code, 'ar') !== false ):
                    
                     $itemData['price'] = chop($itemData['price'],"Ù«00");
                     $itemData['price']= intval(preg_replace('/[^0-9,]+/', '', $itemData['price']));
                     $itemData['price'] = $CurrencyCode." ".$itemData['price'];
                     $itemData['row_total'] = chop($itemData['row_total'],"Ù«00");
                     $itemData['row_total']= intval(preg_replace('/[^0-9,]+/', '', $itemData['row_total']));
                     $itemData['row_total'] = $CurrencyCode." ".$itemData['row_total'];
                     $itemData['tax'] = chop($itemData['tax'],"Ù«00");
                     $itemData['tax']= intval(preg_replace('/[^0-9,]+/', '', $itemData['tax']));
                     $itemData['tax'] = $CurrencyCode." ".$itemData['tax'];
                    endif;   
                    endif;


                $options = $itemModel->getItemOptions($item);
                $itemData['options']    = array();
                if ($options) {
                    foreach ($options as $option) {
                        $optionData = array();
                        $optionData['label']    = strip_tags($option['label']);
                         
                        if ($option['value']) {
                            $printValue = isset($option['print_value']) ? $option['print_value'] : strip_tags($option['value']);
                            $optionData['value']    = $printValue;
                        }
                        $itemData['options'][] = new Varien_Object($optionData);
                    }
                }
            }
            $itemData   = new Varien_Object($itemData);
            Mage::dispatchEvent('ves_pdfpro_data_prepare_after',array('source'=>$itemData,'model'=>$item,'type'=>'item'));
            $invoiceData['items'][] = $itemData;
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
                    if($invoice->getShippingAddress()->getCountryId() == "SA"):
                                $total_amount = explode(" ",$totalData['amount']);
                                $total_amount_new = $total_amount[2]/$value_rate;
                                $totalData['amount'] = "SAR ".$total_amount_new;
                                endif;
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
        //convertion into sar
       $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
      
       if($invoice->getOrderCurrencyCode()=='USD'&& $invoice->getShippingAddress()->getCountryId() != "SA"){
        
        $shippingDescription = Mage::helper('directory')->currencyConvert($order->getShippingDescription(), 'AED', 'USD');
        $invoiceData["data"]["order"]["data"]['shipping_description'] = "$ ". $shippingDescription;
       }
       elseif($invoice->getOrderCurrencyCode()=='QAR'&& $invoice->getShippingAddress()->getCountryId() != "SA" ){
        if(strpos($store_code, 'ar') !== false):
            $shippingDescription = Mage::helper('directory')->currencyConvert($order->getShippingDescription(), 'AED', 'QAR');
            $invoiceData["data"]["order"]["data"]['shipping_description'] = "ر.ق ". $shippingDescription;
        else:
        $shippingDescription = Mage::helper('directory')->currencyConvert($order->getShippingDescription(), 'AED', 'QAR');
        $invoiceData["data"]["order"]["data"]['shipping_description'] = "QAR ". $shippingDescription;
        endif;
       }
       elseif($invoice->getOrderCurrencyCode()=='KWD'&& $invoice->getShippingAddress()->getCountryId() != "SA"){
        if(strpos($store_code, 'ar') !== false):
            $shippingDescription = Mage::helper('directory')->currencyConvert($order->getShippingDescription(), 'AED', 'KWD');
            $invoiceData["data"]["order"]["data"]['shipping_description'] = "د.ك ". $shippingDescription;
        else:
        $shippingDescription = Mage::helper('directory')->currencyConvert($order->getShippingDescription(), 'AED', 'KWD');
        $invoiceData["data"]["order"]["data"]['shipping_description'] = "KWD ". $shippingDescription;
        endif;
       }
       elseif($invoice->getOrderCurrencyCode()=='SAR' && strpos($store_code, 'ar') === false){
         $invoiceData["data"]["order"]["data"]['shipping_description'] = $invoiceData["data"]["order"]["data"]['shipping_description'];
       }
       elseif($invoice->getOrderCurrencyCode()=='AED' && $invoice->getShippingAddress()->getCountryId() != "SA" && strpos($store_code, 'ar') === false){
         $invoiceData["data"]["order"]["data"]['shipping_description'] = $invoiceData["data"]["order"]["data"]['shipping_description'];
       }


    if(strpos($store_code, 'ar') !== false ):

        foreach($invoiceData as $order):
            $invoiceData['data']['created_at_formated']['medium'] = Mage::app()->getLocale()->date(strtotime($invoiceData['data']['created_at']))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM));
         foreach($order as $ord):
            
             $subtotal = chop($ord['subtotal_incl_tax'],"Ù«");
             $subtotal = intval(preg_replace('/[^0-9]+/', '', $subtotal), 10);
             $ord['subtotal_incl_tax'] = $CurrencyCode." ".$subtotal;
            // $subtotal = substr($subtotal, 0, -1);
             $grandTotal = chop($ord['grand_total'],"Ù«");
             $grandTotal = intval(preg_replace('/[^0-9]+/', '', $grandTotal), 10);
             $ord['grand_total'] = $CurrencyCode." ".$grandTotal;
            // $grandTotal = substr($grandTotal, 0, -1);
            $shippingPrice = chop($ord['shipping_amount'],"Ù«");
             $shippingPrice = intval(preg_replace('/[^0-9]+/', '', $shippingPrice), 10);
             $ord['shipping_amount'] = $CurrencyCode."".$shippingPrice;
             //$shippingPrice = substr($subtotal, 0, -1);
             $tax = chop($ord['tax_amount'],"Ù«00");
             $tax = intval(preg_replace('/[^0-9]+/', '', $tax), 10);
             $ord['tax_amount'] = $CurrencyCode." ".$tax;
            // $tax = substr($tax, 0, -1);
             $total_paid = chop($ord['total_paid'],"Ù«");
             $total_paid = intval(preg_replace('/[^0-9]+/', '', $total_paid), 10);
             $ord['total_paid'] = $CurrencyCode." ".$total_paid;
            //$total_paid = substr($total_paid, 0, -1);
             //$discount = chop($ord['discount_amount'],"Ù«");
             //$discount = chop($discount,"â€");
             $discount = intval(preg_replace('/[^0-9]+/', '', $discount), 10);
             $ord['discount_amount'] = $CurrencyCode." ".$discount;
             $cash_on_delivery = $ord['msp_base_cashondelivery_incl_tax'];
             $ord['msp_cashondelivery'] = $cash_on_delivery; 
             
          
        endforeach;
        endforeach;
    endif;       
    
    if($invoice->getShippingAddress()->getCountryId() == "SA" && strpos($store_code, 'sa') === false):

       foreach($invoiceData as $order):
        
         foreach($order as $ord):
          $storeId = $ord['store_id'];
          $store = Mage::getModel('core/store')->load($storeId);
          $store = $store->getCode();
          
          $subtotal = explode(" ",$ord['base_subtotal_incl_tax']);
          $grandTotal = explode(" ",$ord['base_grand_total']);
          $shippingPrice = explode(" ",$ord['base_shipping_amount']);
          $shipping_invoiced = explode(" ",$ord['base_shipping_invoiced']);
          $discount = explode(" ",$ord['base_discount_amount']);
          $tax = explode(" ",$ord['base_tax_amount']);
          $cash_on_delivery = $ord['msp_base_cashondelivery_incl_tax'];
          $total_paid = explode(" ",$ord['base_total_paid']);
         
          
          $subtotal = Mage::helper('directory')->currencyConvert($subtotal[2],'AED', 'SAR');
          $grandTotal = Mage::helper('directory')->currencyConvert($grandTotal[2], 'AED', 'SAR');
          $shippingPrice = Mage::helper('directory')->currencyConvert($shippingPrice[2], 'AED', 'SAR');
          $shipping_invoiced = Mage::helper('directory')->currencyConvert($shipping_invoiced[2], 'AED', 'SAR');
          $discount = Mage::helper('directory')->currencyConvert($discount[2], 'AED', 'SAR');
          $tax = Mage::helper('directory')->currencyConvert($tax[2], 'AED', 'SAR');
          $cash_on_delivery = Mage::helper('directory')->currencyConvert($cash_on_delivery, 'AED', 'SAR');
          $total_paid = Mage::helper('directory')->currencyConvert( $total_paid[2], 'AED', 'SAR');

          if (strpos($store, 'ar') !== false) {
             

            $subtotal = $ord['base_subtotal_incl_tax'];
            $subtotal = intval(preg_replace('/[^0-9]+/', '', $subtotal)); 
            // $subtotal = substr($subtotal, 0, -1);
            $grandTotal = $ord['base_grand_total'];
             $grandTotal = intval(preg_replace('/[^0-9]+/', '', $grandTotal), 10);
            // $grandTotal = substr($grandTotal, 0, -1);
            $shippingPrice = $ord['base_shipping_amount'];
             $shippingPrice = intval(preg_replace('/[^0-9]+/', '', $shippingPrice), 10);
            // $shippingPrice = substr($subtotal, 0, -1);
            $tax = $ord['base_tax_amount'];
             $tax = intval(preg_replace('/[^0-9]+/', '', $tax), 10);
            // $tax = substr($tax, 0, -1);
             $total_paid = $ord['base_total_paid'];
             $total_paid = intval(preg_replace('/[^0-9]+/', '', $total_paid), 10);
            // $total_paid = substr($total_paid, 0, -1);
             $discount = $ord['base_discount_amount'];
             //$discount = chop($discount,"â€");
             $discount = intval(preg_replace('/[^0-9]+/', '', $discount), 10);
             $cash_on_delivery = $ord['msp_base_cashondelivery_incl_tax'];

          $subtotal = Mage::helper('directory')->currencyConvert($subtotal,'AED', 'SAR');
          $grandTotal = Mage::helper('directory')->currencyConvert($grandTotal, 'AED', 'SAR');
          $shippingPrice = Mage::helper('directory')->currencyConvert($shippingPrice, 'AED', 'SAR');
          $discount = Mage::helper('directory')->currencyConvert($discount, 'AED', 'SAR');
          $tax = Mage::helper('directory')->currencyConvert($tax, 'AED', 'SAR');
          $cash_on_delivery = Mage::helper('directory')->currencyConvert($cash_on_delivery, 'AED', 'SAR');
          $total_paid = Mage::helper('directory')->currencyConvert( $total_paid, 'AED', 'SAR');
             
          }
         
          
        if($ord['order_currency_code']=='SAR'){
                $shippingDescription = $ord['shipping_description'];
        
        }else if($ord['order_currency_code']=='USD'|| $ord['order_currency_code']=='QAR' || $ord['order_currency_code']=='KWD'){
        
            $shippingDescription = Mage::helper('directory')->currencyConvert($ord['shipping_description'], 'AED', 'SAR');
            $grandTotal = $subtotal + $shippingDescription - $discount;
            $total_paid =  $subtotal + $shippingDescription;
        }else{

            $shippingDescription = Mage::helper('directory')->currencyConvert($ord['shipping_description'], 'AED', 'SAR');          
            $grandTotal = $subtotal + $shippingDescription - $discount;
            $total_paid =  $subtotal + $shippingDescription;
        }
          
          
          $ord['subtotal_incl_tax'] = $CurrencyCode." ".$subtotal;
          $ord['grand_total'] = $CurrencyCode." ".$grandTotal;
          $ord['shipping_amount'] = $CurrencyCode." ".$shippingPrice;
          $ord['shipping_invoiced'] = $CurrencyCode." ".$shippingPrice;
          $ord['discount_amount'] = $CurrencyCode." ".$discount;
          $ord['tax_amount'] = $CurrencyCode." ".$tax;
          $ord['msp_cashondelivery'] = $CurrencyCode." ".$cash_on_delivery;
          $ord['total_paid'] = $CurrencyCode." ".$total_paid;
         
          $ord['shipping_description'] = $CurrencyCode." ".$shippingDescription;
          $invoiceData["data"]["order"]["data"]['shipping_description']= $CurrencyCode." ".$shippingDescription;
          
          endforeach;
        endforeach;


      endif;

        return serialize($invoiceData);
    }
    
    public function getBasePriceAttributes(){
        return array(
            'base_grand_total',
            'base_tax_amount',
            'base_shipping_tax_amount',
            'base_discount_amount',
            'base_subtotal_incl_tax',
            'base_shipping_amount',
            'base_subtotal',
            'base_hidden_tax_amount',
            'base_shipping_hidden_tax_amnt',
            'base_shipping_incl_tax',
            'base_total_refunded',
            'base_cod_fee',
        );
    }

    public function getPriceAttributes(){
        return array(
            'shipping_tax_amount',
            'tax_amount',
            'grand_total',
            'shipping_amount',
            'subtotal_incl_tax',
            'subtotal',
            'discount_amount',
            'hidden_tax_amount',
            'shipping_hidden_tax_amount',
            'shipping_incl_tax',
            'cod_fee',
        );
    }
}

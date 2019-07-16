<?php
/**
 * VES_PdfPro_Model_Order
 *
 * @author      VnEcoms Team <support@vnecoms.com>
 * @website     http://www.vnecoms.com
 */
class VES_PdfPro_Model_Order extends VES_PdfPro_Model_Abstract
{
    protected $_defaultTotalModel = 'pdfpro/sales_order_pdf_total_default';
    protected $_item_model;
    
    public function getItemModel($item){
        return Mage::getModel('pdfpro/order_item')->setItem($item);
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
    public function initOrderData($source){
        $order                  = $source;
       

        $this->setTranslationByStoreId($order->getStoreId());
        $orderCurrencyCode      = $order->getOrderCurrencyCode();
        $baseCurrencyCode       = $order->getBaseCurrencyCode();
        
          $stores = $store = Mage::getModel('core/store')->load($source->getStoreId());
          $store_code = $stores->getCode();
        
       // getting order currency code for arabic stores
         if($store_code == "ar_sa" && $source->getOrderCurrencyCode() =="SAR" ):
           $orderCurrencyCode  = Mage::app()->getStore('ar_sa')->getCurrentCurrencyCode(); 
           $CurrencyCode = "ر.س";
         elseif($store_code == "ar_ae" && $source->getOrderCurrencyCode() =="AED" ):
            $orderCurrencyCode  = Mage::app()->getStore('ar_ae')->getCurrentCurrencyCode();
            $CurrencyCode  = "د.إ";   
        elseif($source->getOrderCurrencyCode() =="QAR" || $source->getShippingAddress()->getCountryId() == "QA" ):
            if($store_code == "ar_qa"):
            $orderCurrencyCode  = Mage::app()->getStore('ar_qa')->getCurrentCurrencyCode();
            $CurrencyCode  = "ر.ق";
            elseif(strpos($store_code, 'ar') !== false):
              $orderCurrencyCode  = Mage::app()->getStore('ar_qa')->getCurrentCurrencyCode();
              $CurrencyCode  = "ر.ق"; 
            else:
              $orderCurrencyCode  = Mage::app()->getStore('en_us')->getCurrentCurrencyCode();
              $CurrencyCode  = "USD";
            endif;
         elseif($source->getOrderCurrencyCode()=="KWD" || $source->getShippingAddress()->getCountryId() == "KW" ):
            if($store_code == "ar_kw"):
              $orderCurrencyCode  = Mage::app()->getStore('ar_kw')->getCurrentCurrencyCode();
            $CurrencyCode  = "د.ك";
            elseif(strpos($store_code, 'ar') !== false):
              $orderCurrencyCode  = Mage::app()->getStore('ar_kw')->getCurrentCurrencyCode();
            $CurrencyCode  = "د.ك";
            else:
              $orderCurrencyCode  = Mage::app()->getStore('en_kw')->getCurrentCurrencyCode();
              $CurrencyCode  = "KWD";
            endif;
            
         elseif(strpos($store_code, 'ar') !== false ):
            $orderCurrencyCode  = "USD";   
            $CurrencyCode  = "$";      
        endif;
        
        //getting order currency code for shipping country as Saudia Arabia
        if($source->getShippingAddress()->getCountryId() == "SA"):
           $orderCurrencyCode  = Mage::app()->getStore('en_sa')->getCurrentCurrencyCode();  
           $CurrencyCode  = "SAR"; 
           if(strpos($store_code, 'ar') !== false ):  
              $CurrencyCode  = "ر.س"; 
            endif;

        endif;
         
        $sourceData             =   $this->process($source->getData(),$orderCurrencyCode,$baseCurrencyCode);
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
        
        /*
         * Get Items information
        */
        foreach($source->getAllItems() as $item){
            if ($item->getParentItem()) {
                continue;
            }
            $itemModel  = $this->getItemModel($item);
            if($item->getProductType()=='bundle'){
                $itemData = array('is_bundle'=>1,'name'=>$item->getName(),'sku'=>$item->getSku());
                if($itemModel->canShowPriceInfo($item)){
                    $itemData['price']      = Mage::helper('pdfpro')->currency($item->getPrice(),$orderCurrencyCode);
                    $itemData['qty']        = $item->getQtyOrdered() * 1;
                    $itemData['tax']        = Mage::helper('pdfpro')->currency($item->getTaxAmount(),$orderCurrencyCode);
                    $itemData['subtotal']   = Mage::helper('pdfpro')->currency($item->getRowTotal(),$orderCurrencyCode);
                    $itemData['row_total']  = Mage::helper('pdfpro')->currency($item->getRowTotalInclTax(),$orderCurrencyCode);
                }
                $items = $itemModel->getChilds($item);
                $itemData['sub_items']  = array();
                
                foreach ($items as $_item) {
                    $bundleItem = array();
                    $attributes = $itemModel->getSelectionAttributes($_item);
                    if(!$attributes['option_label']) continue;
                    $bundleItem['label']    = $attributes['option_label'];
                    /*Product name */
                    if ($_item->getParentItem()) {
                        $name = $itemModel->getValueHtml($_item);
                    } else {
                        $name = $_item->getName();
                    }
                    $bundleItem['value']    = $name;
                    $bundleItem['sku']      = $_item->getSku();
                    /* price */
                    if ($itemModel->canShowPriceInfo($_item)) {
                        $price = $order->formatPriceTxt($_item->getPrice());
                        $bundleItem['price']    = Mage::helper('pdfpro')->currency($_item->getPrice(),$orderCurrencyCode);
                        $bundleItem['qty']      = $_item->getQtyOrdered()*1;
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
                    'qty'       => $item->getQtyOrdered() * 1,
                    'tax'       => Mage::helper('pdfpro')->currency($item->getTaxAmount(),$orderCurrencyCode),
                    'subtotal'  => Mage::helper('pdfpro')->currency($item->getRowTotal(),$orderCurrencyCode),
                    'row_total' => Mage::helper('pdfpro')->currency($item->getRowTotalInclTax(),$orderCurrencyCode)
                );
                    // currency for arabic stores 
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
                        $baseCode = Mage::app()->getBaseCurrencyCode();      

                        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies(); 
                        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCode, array_values($allowedCurrencies));
                       //convertion for sar country
                        if($source->getShippingAddress()->getCountryId() == "SA" || $source->getShippingAddress()->getCountryId() == "QA" || $source->getShippingAddress()->getCountryId() == "KW"):
                        foreach($rates as $rate=>$value):
                             if($rate == "SAR"):
                                $value_rate = $value;
                             endif;
                        endforeach;
                        $price  = Mage::helper('directory')->currencyConvert($item->getBasePrice(), 'AED', $orderCurrencyCode);
                        $taxamount = Mage::helper('directory')->currencyConvert($item->getBaseTaxAmount(), 'AED', $orderCurrencyCode);
                        $rowtotal = Mage::helper('directory')->currencyConvert($item->getBaseRowTotal(), 'AED', $orderCurrencyCode);
                        $baserow = Mage::helper('directory')->currencyConvert($item->getBaseRowTotalInclTax(), 'AED', $orderCurrencyCode);
                        $itemData = array(
                          'name'      => $item->getName(),
                          'sku'       => $item->getSku(),
                          'price'     => Mage::helper('pdfpro')->currency($price,$orderCurrencyCode),
                          'qty'       => $item->getQtyOrdered() * 1,
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
            $sourceData['items'][]  = $itemData;
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
                                if($source->getShippingAddress()->getCountryId() == "SA"):
                                $total_amount = explode(" ",$totalData['amount']);
                                $total_amount_new = $total_amount[2]/$value_rate;
                                $totalData['amount'] = "SAR ".$total_amount_new;
                                endif;
                               
                    $totalArr[$area][] = new Varien_Object(array('label'=>$totalData['label'], 'value'=>$totalData['amount']));
                }
            }
        }
        $sourceData['totals'] = new Varien_Object($totalArr);
        $apiKey         = Mage::helper('pdfpro')->getApiKey($order->getStoreId(),$order->getCustomerGroupId());

        $sourceData     = new Varien_Object($sourceData);
        
        Mage::dispatchEvent('ves_pdfpro_data_prepare_after',array('source'=>$sourceData,'model'=>$order,'type'=>'order'));
        $orderData      = new Varien_Object(array('key'=>$apiKey,'data'=>$sourceData));
        $this->revertTranslation();
        
        

       if($source->getOrderCurrencyCode()=='USD'&& $source->getShippingAddress()->getCountryId() != "SA"){
        
        $shippingDescription = Mage::helper('directory')->currencyConvert($source->getShippingDescription(), 'AED', 'USD');
        $orderData["data"]['shipping_description'] = "$ ". $shippingDescription;
        $orderData['data']['shipping_amount'] = "$ ".$orderData['data']['shipping_amount'];
       }
       elseif($source->getOrderCurrencyCode()=='SAR'){
         if(strpos($store_code, 'ar') !== false ): 
              $orderData["data"]['shipping_description'] = "ر.س ". $orderData["data"]['shipping_description'];
              $orderData['data']['shipping_amount'] = "ر.س " .$orderData['data']['shipping_amount']; 
         else : 
            $orderData["data"]['shipping_description'] = "SAR ". $orderData["data"]['shipping_description'];
            $orderData['data']['shipping_amount'] = "SAR ".$orderData['data']['shipping_amount'];
         endif;
       }
       elseif($source->getOrderCurrencyCode()=='AED' && $source->getShippingAddress()->getCountryId() != "SA"){
        
          if(strpos($store_code, 'ar') !== false ): 
              $orderData["data"]['shipping_description'] = "د.إ ". $orderData["data"]['shipping_description'];
              $orderData['data']['shipping_amount'] = "د.إ " .$orderData['data']['shipping_amount'];
          else:
            $orderData["data"]['shipping_description'] = "AED ". $orderData["data"]['shipping_description'];
           $orderData['data']['shipping_amount'] = "AED ".$orderData['data']['shipping_amount'];
         endif;
       }
       elseif($source->getOrderCurrencyCode()=='QAR' && $source->getShippingAddress()->getCountryId() != "SA"){
         $shippingDescription = Mage::helper('directory')->currencyConvert($source->getShippingDescription(), 'AED', 'QAR');
         if(strpos($store_code, 'ar') !== false ):

              $orderData["data"]['shipping_description'] = "ر.ق ". $shippingDescription;
              $orderData['data']['shipping_amount'] = "ر.ق " .$orderData['data']['shipping_amount'];
        else: 
            $orderData["data"]['shipping_description'] = "QAR ". $shippingDescription;
            $orderData['data']['shipping_amount'] = "QAR ".$orderData['data']['shipping_amount'];
         endif;
       }
       elseif($source->getOrderCurrencyCode()=='KWD' && $source->getShippingAddress()->getCountryId() != "SA"){
         $shippingDescription = Mage::helper('directory')->currencyConvert($source->getShippingDescription(), 'AED', 'KWD');
         if(strpos($store_code, 'ar') !== false ): 
              $orderData["data"]['shipping_description'] = "د.ك ". $shippingDescription;
              $orderData['data']['shipping_amount'] = "د.ك " .$orderData['data']['shipping_amount'];
         else: 
               $orderData["data"]['shipping_description'] = "KWD ". $shippingDescription;
               $orderData['data']['shipping_amount'] = "KWD ".$orderData['data']['shipping_amount'];
         endif;
       }
     
      
    //getting totals with currency for arabic stores
    if(strpos($store_code, 'ar') !== false ):

        foreach($orderData as $order):
            
            $orderData['data']['created_at_formated']['medium'] = Mage::app()->getLocale()->date(strtotime($orderData['data']['created_at']))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM));
            
         foreach($order as $ord):
             
             $subtotal = chop($ord['subtotal_incl_tax'],"Ù«");
             $subtotal = intval(preg_replace('/[^0-9]+/', '', $subtotal), 10);
             $ord['subtotal_incl_tax'] = $CurrencyCode." ".$subtotal;
            // $subtotal = substr($subtotal, 0, -1);
            
             $grandTotal = chop($ord['grand_total'],"Ù«");

             $grandTotal = intval(preg_replace('/[^0-9]+/', '', $grandTotal), 10);

             $ord['grand_total'] = $CurrencyCode." ".$grandTotal;
            // $grandTotal = substr($grandTotal, 0, -1);
             $shippingPrice = chop($ord['base_shipping_amount'],"Ù«");
             $shippingPrice = intval(preg_replace('/[^0-9]+/', '', $shippingPrice), 10);
             $ord['shipping_amount'] = $orderCurrencyCode."".$shippingPrice;
            // $shippingPrice = substr($subtotal, 0, -1);
             $tax = chop($ord['tax_amount'],"Ù«");
             $tax = intval(preg_replace('/[^0-9]+/', '', $tax), 10);
             $ord['tax_amount'] = $CurrencyCode." ".$tax;
            // $tax = substr($tax, 0, -1);
            
             $total_paid = chop($ord['total_paid'],"Ù«");
             $total_paid = intval(preg_replace('/[^0-9]+/', '', $total_paid), 10);
             $ord['total_paid'] = $CurrencyCode." ".$total_paid;
            // $total_paid = substr($total_paid, 0, -1);
             $discount = chop($ord['discount_amount'],"Ù«");
             $discount = chop($discount,"â€");
             $discount = intval(preg_replace('/[^0-9]+/', '', $discount), 10);
             $ord['discount_amount'] = $CurrencyCode." ".$discount;
             $cash_on_delivery = $ord['msp_cashondelivery_incl_tax'];
             $ord['msp_cashondelivery'] = $cash_on_delivery; 
             
          
        endforeach;
        endforeach;
    endif;
   

    if($source->getShippingAddress()->getCountryId() == "SA" && strpos($store_code, 'sa') === false):

            

       foreach($orderData as $order):
        
         foreach($order as $ord):
          $storeId = $ord['store_id'];
          $store = Mage::getModel('core/store')->load($storeId);
          $store = $store->getCode();
        

          $subtotal = explode(" ",$ord['base_subtotal_incl_tax']);
          $grandTotal = explode(" ",$ord['base_grand_total']);
          $shippingPrice = explode(" ",$ord['base_shipping_amount']);
          $discount = explode(" ",$ord['base_discount_amount']);
          $tax = explode(" ",$ord['base_tax_amount']);
          $cash_on_delivery = $ord['msp_base_cashondelivery_incl_tax'];
          $total_paid = explode(" ",$ord['base_total_paid']);
          
          
          $subtotal = Mage::helper('directory')->currencyConvert($subtotal[2],'AED', 'SAR');
          $grandTotal = Mage::helper('directory')->currencyConvert($grandTotal[2], 'AED', 'SAR');
          $shippingPrice = Mage::helper('directory')->currencyConvert($shippingPrice[2], 'AED', 'SAR');
          $discount = Mage::helper('directory')->currencyConvert($discount[2], 'AED', 'SAR');
          $tax = Mage::helper('directory')->currencyConvert($tax[2], 'AED', 'SAR');
          $cash_on_delivery = Mage::helper('directory')->currencyConvert($cash_on_delivery, 'AED', 'SAR');
          $total_paid = Mage::helper('directory')->currencyConvert( $total_paid[2], 'AED', 'SAR');

          if (strpos($store, 'ar') !== false) {




           $subtotal = chop($ord['base_subtotal_incl_tax'],"Ù«");
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
                    //without base currency 

            
            }else if($ord['order_currency_code']=='USD' || $ord['order_currency_code']=='QAR' || $ord['order_currency_code']=='KWD'){
            
            $shippingDescription = Mage::helper('directory')->currencyConvert($ord['shipping_description'], 'AED', 'SAR');
            // base  currency
            $grandTotal = $subtotal + $shippingDescription - $discount;
            $total_paid =  $subtotal + $shippingDescription;
            }else{

                $shippingDescription = Mage::helper('directory')->currencyConvert($ord['shipping_description'], 'AED', 'SAR');          
              //without base currency
                 $grandTotal = $subtotal + $shippingDescription - $discount;
                $total_paid =  $subtotal + $shippingDescription;
            }

          $ord['subtotal_incl_tax'] = $CurrencyCode." ".$subtotal;
          $ord['grand_total'] = $CurrencyCode." ".$grandTotal;
          $ord['shipping_amount'] = $CurrencyCode." ".$shippingPrice;
          $ord['discount_amount'] = $CurrencyCode." ".$discount;
          $ord['tax_amount'] = $CurrencyCode." ".$tax;
          $ord['msp_cashondelivery'] = $CurrencyCode." ".$cash_on_delivery;
          $ord['total_paid'] = $CurrencyCode." ".$total_paid;
          $ord['shipping_description'] = $CurrencyCode." ".$shippingDescription;
          //$orderData['data']['shipping_description']= "SAR ".$orderData['data']['shipping_description'];

          endforeach;
        endforeach;
      endif;
     
   
        return serialize($orderData);
    }
    public function getBasePriceAttributes(){
        return array(
            'base_discount_amount',
            'base_discount_canceled',
            'base_discount_invoiced',
            'base_discount_refunded',
            'base_grand_total',
            'base_shipping_amount',
            'base_shipping_canceled',
            'base_shipping_invoiced',
            'base_shipping_refunded',
            'base_shipping_tax_amount',
            'base_shipping_tax_refunded',
            'base_subtotal',
            'base_subtotal_canceled',
            'base_subtotal_invoiced',
            'base_subtotal_refunded',
            'base_tax_amount',
            'base_tax_canceled',
            'base_tax_invoiced',
            'base_tax_refunded',
            'base_to_global_rate',
            'base_to_order_rate',
            'base_to_order_rate',
            'base_total_canceled',
            'base_total_invoiced',
            'base_total_invoiced_cost',
            'base_total_offline_refunded',
            'base_total_online_refunded',
            'base_total_paid',
            'base_total_refunded',
            'base_adjustment_negative',
            'base_adjustment_positive',
            'base_shipping_discount_amount',
            'base_subtotal_incl_tax',
            'base_total_due',
            'base_shipping_hidden_tax_amnt',
            'base_hidden_tax_invoiced',
            'base_hidden_tax_refunded',
            'base_shipping_incl_tax',
            'base_shipping_hidden_tax_amount',
            'base_cod_fee'
        );
    }
    /*Get all price attribute */
    public function getPriceAttributes(){
        return array(
            'discount_amount',
            'discount_canceled',
            'discount_invoiced',
            'discount_refunded',
            'grand_total',
            'shipping_amount',
            'shipping_canceled',
            'shipping_invoiced',
            'shipping_refunded',
            'shipping_tax_amount',
            'shipping_tax_refunded',
            'store_to_base_rate',
            'subtotal',
            'subtotal_canceled',
            'subtotal_invoiced',
            'subtotal_refunded',
            'tax_amount',
            'tax_canceled',
            'tax_invoiced',
            'tax_refunded',
            'total_canceled',
            'total_invoiced',
            'total_offline_refunded',
            'total_online_refunded',
            'total_paid',
            'total_refunded',
            'adjustment_negative',
            'adjustment_positive',
            'payment_authorization_amount',
            'shipping_discount_amount',
            'subtotal_incl_tax',
            'total_due',
            'hidden_tax_amount',
            'shipping_hidden_tax_amount',
            'hidden_tax_invoiced',
            'hidden_tax_refunded',
            'shipping_incl_tax',
            'cod_fee',
        );
    }
    
}



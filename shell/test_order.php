<?php
        
        require_once('../app/Mage.php');
        umask(0);

        Mage::app();


        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $order = Mage::getModel('sales/order')->load(547);
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $getCustomerId = $customer->getId();
        $grandTotal = $order->getGrandTotal();
        $status = $order->getStatus();
        $itemCount = 0;
        $shippingCountryId = '';
        $items = $order->getAllVisibleItems();
        $orderEmailData = array();
        foreach ($items as $item) {
            $getProductId = $item->getProductId();
            $createdAt = $item->getCreatedAt();
            $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
            $products = Mage::helper('marketplace/marketplace')->getProductInfo($getProductId);
            $products_new = Mage::getModel('catalog/product')->load($item->getProductId());
            if($products_new->getTypeId() == "configurable")
                {
                   $options = $item->getProductOptions() ;

                   $sku = $options['simple_sku'] ;
                   $getProductId = Mage::getModel('catalog/product')->getIdBySku($sku);
               }
            else{
                $getProductId = $item->getProductId();
            }


            $order_item_status = Mage::getModel('marketplace/commission')
            ->getCollection()
            ->addFieldToFilter("product_id",$getProductId)
            ->addFieldToFilter("order_id",$order->getId())->getFirstItem();

            $isbuyerconfirmation = $order_item_status->getIsBuyerConfirmation();
            if($isbuyerconfirmation == "Yes"){

               $isbuyerconfirmation = "Accepted"; 
            }

            $sellerId = $products->getSellerId();
            $productType = $products->getTypeID();
            /**
             * Get the shipping active status of seller
             */
            $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');
            if ($sellerShippingEnabled == 1 && $productType == 'simple') {
                /**
                 * Get the product national shipping price
                 * and international shipping price
                 * and shipping country
                 */
                $nationalShippingPrice = $products->getNationalShippingPrice();
                $internationalShippingPrice = $products->getInternationalShippingPrice();
                $sellerDefaultCountry = $products->getDefaultCountry();
                $shippingCountryId = $order->getShippingAddress()->getCountry();
            }
            /**
             * Check seller id has been set
             */
            if ($sellerId) {
                $orderPrice = $item->getBasePrice() * $item->getQtyOrdered();
                $productAmt = $item->getBasePrice();
                $productQty = $item->getQtyOrdered();
                $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry,
                    $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);
                /**
                 * Getting seller commission percent
                 */
                $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $shippingPrice - $commissionFee;
                /**
                 * Storing commission information in database table
                 */
                  
                  
                   if($isbuyerconfirmation == "Accepted"){
                    
                    
                    $orderEmailData [$itemCount] ['seller_id'] = $sellerId;
                    $orderEmailData [$itemCount] ['product_qty'] = $productQty;
                    $orderEmailData [$itemCount] ['product_id'] = $getProductId;
                    $orderEmailData [$itemCount] ['product_amt'] = $productAmt;
                    $orderEmailData [$itemCount] ['commission_fee'] = $commissionFee;
                    $orderEmailData [$itemCount] ['seller_amount'] = $sellerAmount;
                    $orderEmailData [$itemCount] ['increment_id'] = $order->getIncrementId();
                    $orderEmailData [$itemCount] ['customer_firstname'] = $order->getCustomerFirstname();
                    $orderEmailData [$itemCount] ['customer_email'] = $order->getCustomerEmail();
                    $orderEmailData [$itemCount] ['product_id_simple'] = $getProductId;
                    $orderEmailData [$itemCount] ['is_buyer_confirmation'] = $isbuyerconfirmation;
                    $orderEmailData [$itemCount] ['itemCount'] = $itemCount;
                    $itemCount = $itemCount + 1;
                }


                }
            }

        
        if (Mage::getStoreConfig('marketplace/admin_approval_seller_registration/sales_notification') == 1) {
            $sellerIds = array();
        $displayProductCommission = Mage::helper('marketplace')->__('Seller Commission Fee');
        $displaySellerAmount = Mage::helper('marketplace')->__('Seller Amount');
        $displayProductImage = Mage::helper('marketplace')->__('Product Image');
        $displayProductName = Mage::helper('marketplace')->__('Product Name');
        $displayProductQty = Mage::helper('marketplace')->__('Product QTY');
        $displayProductAmt = Mage::helper('marketplace')->__('Product Amount');
        $displayProductStatus = Mage::helper('marketplace')->__('Product Status');
        foreach ($orderEmailData as $data) {
            if (!in_array($data ['seller_id'], $sellerIds)) {
                $sellerIds [] = $data ['seller_id'];
            }
        }

        foreach ($sellerIds as $key => $id) {
            $totalProductAmt = $totalCommissionFee = $totalSellerAmt = 0;
            $productDetails = '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #eaeaea">';
            $productDetails .= '<thead><tr>';
            $productDetails .= '<th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductImage . '</th><th align="left" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductName . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductQty . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductAmt . '</th>';
            $productDetails .= '<th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductCommission . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displaySellerAmount . '</th><th align="center" bgcolor="#EAEAEA" style="font-size:13px;padding:3px 9px;">' . $displayProductStatus . '</th></tr></thead>';
            $productDetails .= '<tbody bgcolor="#F6F6F6">';
            $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
            foreach ($orderEmailData as $data) {

                if ($id == $data ['seller_id']) {
                    $sellerId = $data ['seller_id'];
                    $incrementId = $data ['increment_id'];
                    $groupId = Mage::helper('marketplace')->getGroupId();
                    $productId = $data ['product_id'];
                    $simpleProductId = $data ['product_id_simple'];                    
                    $product = Mage::helper('marketplace/marketplace')->getProductInfo($productId);
                    $productGroupId = $product->getGroupId();
                    $productName = $product->getName();
                    $productamt = $data ['product_amt'] * $data ['product_qty'];
                    $productstatus = $data['is_buyer_confirmation'];
                    

                    $products_new = Mage::getModel('catalog/product')->load($productId);
                    $product_img = $products_new->getImageUrl();
                    if($products_new->getTypeId() == "configurable"){
                    $products_new = Mage::getModel('catalog/product')->load($productId);
                        if($products_new->getSupplierSku() != ""){
                            $product_sku = $products_new->getSupplierSku();
                        }else{
                            $product_sku = $products_new->getSku();
                        }
                    $product_img = $products_new->getImageUrl();
                    $product_color = $products_new->getAttributeText('color');
                    $product_size = $products_new->getAttributeText('size');
                    }
                    else{
                            if($products_new->getSupplierSku() != ""){
                            $product_sku = $products_new->getSupplierSku();
                            }else{
                                $product_sku = $products_new->getSku();
                            }
                        $product_color = $products_new->getAttributeText('color');
                        $product_size = $products_new->getAttributeText('size');
                    }
                    if ($product_sku) {

                            $product_sku = "<br/>SKU:&nbsp;" . $product_sku;
                            
                        }else{
                            $product_sku="";
                        }

                        if ($product_size) {

                            $product_size = "<br/>Size:&nbsp;" . $product_size;
                            
                        }else{
                            $product_size="";
                        }

                        if ($product_color) {
                           $product_color = "<br/>Color:&nbsp;" . $product_color;
                            
                        }else{
                            $product_color="";
                        }
                    
                    
                    $productOptions = $product_sku.$product_size.$product_color;
                    $productDetails .= '<tr>';
                    $productDetails .= '<td align="cenetr" valign="center" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;"><img src="' . $product_img . '" width="40%"></td><td align="left" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $productName . '<br/>'. $productOptions.'</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . round($data ['product_qty']) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($productamt, 2) . '</td><td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($data ['commission_fee'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $currencySymbol . round($data ['seller_amount'], 2) . '</td>';
                    $productDetails .= '<td align="center" valign="top" style="font-size:11px;padding:3px 9px;border-bottom:1px dotted #cccccc;">' . $productstatus . '</td>';
                    $totalProductAmt = $totalProductAmt + $productamt;
                    $totalCommissionFee = $totalCommissionFee + $data ['commission_fee'];
                    $totalSellerAmt = $totalSellerAmt + $data ['seller_amount'];
                    $orderTotal = $data ['order_total'];

                    $customerEmail = $data ['customer_email'];
                    $customerFirstname = $data ['customer_firstname'];
                    echo $productDetails .= '</tr>';
                }
            }
        
            $productDetails .= '</tbody><tfoot>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Seller Commision Fee</td><td align="center" style="padding:3px 9px"><span>' . $currencySymbol . round($totalCommissionFee, 2) . '</span></td></tr>
                                 <tr><td colspan="4" align="right" style="padding:3px 9px">Total Amount</td><td align="center" style="padding:3px 9px"><span>' . $currencySymbol . round($totalProductAmt, 2) . '</span></td></tr>';
            echo $productDetails .= '</tfoot></table>';
            
            
        }
    
                        
    }
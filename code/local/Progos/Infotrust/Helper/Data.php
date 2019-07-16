<?php

/*
 * @author : Saroop, RT
 * @data : 02-08-2017
 ***/

class Progos_Infotrust_Helper_Data extends Mage_Core_Helper_Abstract
{
    const FPC_BREADCRUMB_DATALAYER = 'fpc_breadcrumbs_datalayer';

    const XML_PATH_SELLER_CANCEL_EMAIL_ENABLE = 'infotrust/infotrust/kustomer_seller_cancel_email_enable';
    const XML_PATH_SELLER_ITEM_REJECT_EMAIL_ENABLE_STOREFRONT = 'infotrust/infotrust/kustomer_seller_item_reject_email_enable_storefront';
    const XML_PATH_SELLER_ITEM_REJECT_EMAIL_ENABLE_ADMIN = 'infotrust/infotrust/kustomer_seller_item_reject_email_enable_admin';

    const XML_PATH_SELLER_ACCEPT_EMAIL_ENABLE = 'infotrust/infotrust/kustomer_seller_accept_email_enable';

    const XML_PATH_KUSTOMER_ENABLE_LOG = 'infotrust/infotrust/kustomer_enable_log';
    const XML_PATH_KUSTOMER_BCC_EMAIL = 'infotrust/infotrust/kustomer_bcc_email';
    const XML_PATH_KUSTOMER_EMAIL = 'infotrust/infotrust/kustomer_email_hook';
    const XML_PATH_KUSTOMER_DATA_KEY = 'infotrust/infotrust/kustomer_data_key';

    const XML_PATH_SENDING_SET_RETURN_PATH      = 'system/smtp/set_return_path';
    const XML_PATH_SENDING_RETURN_PATH_EMAIL    = 'system/smtp/return_path_email';

    public function getKustomerEmailHook($storeId = null)
    {
        return (string)Mage::getStoreConfig(self::XML_PATH_KUSTOMER_EMAIL);
    }

    public function getKustomerDataKey($storeId = null)
    {
        return (string)Mage::getStoreConfig(self::XML_PATH_KUSTOMER_DATA_KEY);
    }

    public function getBccEmail($storeId = null)
    {
        return (string)Mage::getStoreConfig(self::XML_PATH_KUSTOMER_BCC_EMAIL);
    }

    public function isKustomerLog($storeId = null)
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_KUSTOMER_ENABLE_LOG);
    }

    public function isEnableSellerCancelEmail($storeId = null)
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_SELLER_CANCEL_EMAIL_ENABLE);
    }

    public function isEnableSellerAcceptEmail($storeId = null)
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_SELLER_ACCEPT_EMAIL_ENABLE);
    }

    public function isEnableSellerItemRejectEmailAdmin()
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_SELLER_ITEM_REJECT_EMAIL_ENABLE_ADMIN);
    }

    public function isEnableSellerItemRejectEmailStorefront($storeId = null)
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_SELLER_ITEM_REJECT_EMAIL_ENABLE_STOREFRONT);
    }
    /**
     * prepare json ld for seller data to be sent to
     * kustomer hook email when each item is rejected in order from admin.
     * @param Mage_Sales_Model_Order $order data with seller details
     * commission fee and amount against item and order item details
     * @param array $options
     * @return string $jsonld
     */
    public function getSellerItemRejectEmailJsonld($order, $options = [])
    {
        $merchant = (object)[
            '@type' => 'Organization',
            'name' => Mage::app()->getStore()->getFrontendName(),
        ];
        //get phone number from order object
        $phone = @$order->getShippingAddress()->getTelephone();
        $customerData = (object)[
            '@type' => 'Person',
            'name' => $order->getCustomerName(),
            'email' => $order->getCustomerEmail(),
            'phone' => $phone
        ];
        $sellerData = [];
        if (isset($options['seller_id'])) {
            //$seller = Mage::getModel('marketplace/sellerprofile')->collectprofile($options['seller_Id']);
            $seller = Mage::getModel('customer/customer')->load($options['seller_id']);
            $sellerData = (object)[
                '@type' => 'Person',
                'sellerId' => $options['seller_id'],
                'name' => $seller->getFirstname(),
                'email' => $seller->getEmail(),
            ];
        }

        $itemReject = (object)[
            '@type' => 'Offer',
            'itemOffered' => (object)[
                '@type' => 'Product',
                'sku' => @$options['product_sku'],
            ],
            'seller' => $sellerData
        ];
        //prepare array json ld format
        $schemaOrg = "http://schema.org/";
        $data = [
            '@context' => $schemaOrg,
            '@type' => 'SellerItemReject',
            'merchant' => $merchant,
            'customer' => $customerData,
            'itemReject' => $itemReject,
            'OrderNumber' => (string)$order->getIncrementId(),
            'orderStatus' => 'http://schema.org/OrderItemReject',
        ];

        $jsonld = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $jsonld;
    }

    /**
     * prepare json ld for seller data to be sent to
     * kustomer hook email.
     * @param Mage_Sales_Model_Order $order data with seller details
     * commission fee and amount against item and order item details
     * @param array $options
     * @return string $jsonld
     */
    public function getSellerCancelEmailJsonld($order, $options = [])
    {
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $orderItems = $order->getAllVisibleItems();
        $merchant = (object)[
            '@type' => 'Organization',
            'name' => Mage::app()->getStore()->getFrontendName(),
            'sameAs' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
        ];
        //get phone number from order object
        $phone = @$order->getShippingAddress()->getTelephone();

        $customerData = (object)[
            '@type' => 'Person',
            'name' => $order->getCustomerName(),
            'email' => $order->getCustomerEmail(),
            'phone' => $phone
        ];
        //accepted product array
        $itemAccept = [];

        foreach ($orderItems as $item) {
            //check if item status is canceled
            if ($item->getStatusId() != Mage_Sales_Model_Order_Item::STATUS_CANCELED) {
                continue;
            }
            //admin -> product_id
            //front -> item
            $params = Mage::app()->getRequest()->getParams();
            Mage::app()->setCurrentStore($order->getStoreId());
            $product = $item->getProduct(); //configurable product

            $childProductSku = $item->getProductOptionByCode('simple_sku'); //simple product
            $childProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $childProductSku);
            $itemOpt = $item->getProductOptionByCode('attributes_info');
            //load seller information
            $seller = Mage::getModel('customer/customer')->load($product->getSellerId());
            $sellerData = null;
            if ($seller->getFirstname() != null || $seller->getEmail() != null) {
                //avoiding loop, getting item array by key with product id as needle
                $key = array_search($childProduct->getId(), array_column($options, 'product_id'));
                //order items can be of one seller only
                //$keys = array_keys(array_column($options, 'seller_id'), $seller->getId());
                $sellerData = (object)[
                    '@type' => 'Person',
                    'sellerId' => $product->getSellerId(),
                    'name' => $seller->getFirstname(),
                    'email' => $seller->getEmail(),
                    'commissionFee' => @(string)$options[$key]['commission_fee'],
                    'sellerAmount' => @(string)$options[$key]['seller_amount']
                ];
            }

            $color = '';
            $size = '';
            if (!empty($itemOpt)) {
                foreach ($itemOpt as $k => $v) {
                    if ($v['label'] == 'Color') {
                        $color = $v['value'];
                    }
                    if ($v['label'] == 'Size') {
                        $size = $v['value'];
                    }
                }
            }

            $itemAccept[] = (object)[
                '@type' => 'Offer',
                'itemOffered' => (object)[
                    '@type' => 'Product',
                    'name' => $product->getName(),
                    'image' => Mage::getModel('catalog/product_media_config')
                        ->getMediaUrl($product->getThumbnail()),
                    'sku' => ($item->getSku()) ? $item->getSku() : $item->getId(),
                    'url' => Mage::helper('catalog/product')->getProductUrl($product->getId()),
                    'color' => $color,
                    'size' => $size
                ],
                'price' => $item->getBasePrice(),
                'priceCurrency' => $baseCurrencyCode,
                'eligibleQuantity' => (object)[
                    '@type' => 'QuantitativeValue',
                    'value' => $item->getQtyOrdered()
                ],
                'manufacturer' => (object)[
                    '@type' => 'Organization',
                    'name' => $product->getAttributeText('manufacturer')
                ],
                'seller' => $sellerData,
                'status' => $item->getStatus()
            ];
        }
        //prepare array json ld format
        $schemaOrg = "http://schema.org/";
        $data = [
            '@context' => $schemaOrg,
            '@type' => 'SellerReject',
            'merchant' => $merchant,
            'customer' => $customerData,
            'itemAccept' => $itemAccept,
            'OrderNumber' => (string)$order->getIncrementId(),
            'orderStatus' => 'http://schema.org/OrderCanceled',
        ];

        $jsonld = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $jsonld;
    }

    /**
     * prepare json ld for seller data to be sent to
     * kustomer hook email.
     * @param Mage_Sales_Model_Order $order data with seller details
     * commission fee and amount against item and order item details
     * @return string $jsonld
     */
    public function getSellerAcceptEmailJsonld($order)
    {
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $orderCurrencyCode = $order->getOrderCurrencyCode();

        $orderItems = $order->getAllVisibleItems();
        $merchant = (object)[
            '@type' => 'Organization',
            'name' => Mage::app()->getStore()->getFrontendName(),
        ];

        //this will get customer name from order object
        //get phone number from order
        $phone = @$order->getShippingAddress()->getTelephone();

        $customerData = (object)[
            '@type' => 'Person',
            'name' => $order->getCustomerName(),
            'email' => $order->getCusomterEmail(),
            'phone' => $phone
        ];
        //accepted product array
        $itemAccept = [];

        foreach ($orderItems as $item) {
            //admin -> product_id
            //front -> item
            $params = Mage::app()->getRequest()->getParams();
            Mage::app()->setCurrentStore($order->getStoreId());
            $product = $item->getProduct(); //configurable product
            /**
             * Getting seller commission percent
             */
            $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($product->getSellerId());
            if ($product->getSpecialPrice()) {
                $orderPrice = $product->getSpecialPrice() * $item->getQtyOrdered();
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
            } else {
                $orderPrice = $product->getPrice() * $item->getQtyOrdered();
                $percentperproduct = $sellerCollection ['commission'];
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
            }

            $itemOpt = $item->getProductOptionByCode('attributes_info');
            //load seller information
            $seller = Mage::getModel('customer/customer')->load($product->getSellerId());
            $sellerData = null;
            if ($seller->getFirstname() != null || $seller->getEmail() != null) {
                $sellerData = (object)[
                    '@type' => 'Person',
                    'sellerId' => $product->getSellerId(),
                    'name' => $seller->getName(),
                    'email' => $seller->getEmail(),
                    'commissionFee' => (string)$commissionFee,
                    'sellerAmount' => (string)$sellerAmount
                ];
            }

            $color = '';
            $size = '';
            if (!empty($itemOpt)) {
                foreach ($itemOpt as $k => $v) {
                    if ($v['label'] == 'Color') {
                        $color = $v['value'];
                    }
                    if ($v['label'] == 'Size') {
                        $size = $v['value'];
                    }
                }
            }

            $itemAccept[] = (object)[
                '@type' => 'Offer',
                'itemOffered' => (object)[
                    '@type' => 'Product',
                    'name' => $product->getName(),
                    'image' => Mage::getModel('catalog/product_media_config')
                        ->getMediaUrl($product->getThumbnail()),
                    'sku' => ($item->getSku()) ? $item->getSku() : $item->getId(),
                    'url' => Mage::helper('catalog/product')->getProductUrl($product->getId()),
                    'color' => $color,
                    'size' => $size
                ],
                'price' => $item->getBasePrice(),
                'priceCurrency' => $baseCurrencyCode,
                'eligibleQuantity' => (object)[
                    '@type' => 'QuantitativeValue',
                    'value' => $item->getQtyOrdered()
                ],
                'manufacturer' => (object)[
                    '@type' => 'Organization',
                    'name' => $product->getAttributeText('manufacturer')
                ],
                'seller' => $sellerData,
                'status' => $item->getStatus()
            ];
        }
        //prepare array json ld format
        $schemaOrg = "http://schema.org/";
        $data = [
            '@context' => $schemaOrg,
            '@type' => 'SellerAccept',
            'merchant' => $merchant,
            'customer' => $customerData,
            'itemAccept' => $itemAccept,
            'OrderNumber' => (string)$order->getIncrementId(),
            'orderStatus' => 'http://schema.org/OrderConfirmed',
        ];

        $jsonld = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $jsonld;
    }
    /**
     * @param $jsonld
     * @param $order
     * @param string $subject
     * @param string $text
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Mail_Exception
     */
    public function zendSend($jsonld, $order = null, $subject = '', $text = '')
    {
        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));
        $senderName = Mage::getStoreConfig('trans_email/ident_sales/name');
        $senderEmail = Mage::getStoreConfig('trans_email/ident_sales/email');
        $dataKustomerKey = $this->getKustomerDataKey();
        $to = $this->getKustomerEmailHook();
        if ($to == null || $to == '') {
            return;
        }
        if ($dataKustomerKey == null || $dataKustomerKey == '') {
            return;
        }
        $toName = substr($to, 0, strpos($to, '@'));
        if ($text == '') {
            $text .= '<p>Order recieved from <b>' . $order->getCustomerName() . '</b></p>';
        }

        if ($subject == '') {
            $subject = 'ELABELZ New Order #' . $order->getIncrementId();
        }

        $body = '<html>
<head>
    <title>' . $subject . '</title>
    <script type="application/ld+json" data-kustomer-key="' . $dataKustomerKey . '">
    ' . $jsonld . '
    </script>
</head>
<body>
' . $text . '
</body>
</html>';
        $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);

        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $senderEmail;
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if (Mage::getStoreConfig('emailsmtp/smtp/enabled')) {
            $config = array(
                'ssl' => Mage::getStoreConfig('emailsmtp/smtp/ssl'),
                'port' => Mage::getStoreConfig('emailsmtp/smtp/port'),
                'auth' => Mage::getStoreConfig('emailsmtp/smtp/auth'),
                'username' => Mage::getStoreConfig('emailsmtp/smtp/login'),
                'password' => Mage::getStoreConfig('emailsmtp/smtp/password'),
            );

            if ($config['ssl'] == 'none') {
                unset($config['ssl']);
            }

            $mailTransport = new Zend_Mail_Transport_Smtp(Mage::getStoreConfig('emailsmtp/smtp/host'), $config);
            Zend_Mail::setDefaultTransport($mailTransport);
        } elseif ($returnPathEmail !== null) {
            $mailTransport = new Zend_Mail_Transport_Sendmail('-f'.$returnPathEmail);
            Zend_Mail::setDefaultTransport($mailTransport);
        }

        $mail = new Zend_Mail('utf-8');
        //add bcc email after validation
        $validator = new Zend_Validate_EmailAddress();
        if ($this->getBccEmail() != null
            && $validator->isValid($this->getBccEmail())) {
            $mail->addBcc($this->getBccEmail(), '=?utf-8?B?' . base64_encode('Kustomer Hook') . '?=');
        }
        $mail->addTo($to, '=?utf-8?B?' . base64_encode($toName) . '?=');
        $mail->setBodyHtml($body);
        $mail->setSubject('=?utf-8?B?' . base64_encode($subject) . '?=');
        //->setHeaderEncoding(Zend_Mime::ENCODING_8BIT)
        $mail->setFrom($senderEmail, $senderName);
        //$mail->setType(Zend_Mime::MULTIPART_ALTERNATIVE);
        try {
            $mail->send();
        } catch (Exception $e) {
            if ($this->isKustomerLog()) {
                Mage::log($e->getMessage(), null, 'jsonld.log');
            }
        }
        unset($mail);
        if ($this->isKustomerLog()) {
            Mage::log($subject . ': ' . $jsonld, null, 'jsonld.log');
        }
        return $this;
    }
    /**
     * this will compose an array
     * according to json ld format
     * @param $order
     * @return mixed|string
     */
    public function getJSONLD($order)
    {
        $storecredit = Mage::helper('aw_storecredit/totals')->getQuoteStoreCredit($order->getQuoteId());
        $shippingAddr = $order->getShippingAddress();
        $storecreditApplied = '';
        if (!empty($storecredit)) {
            foreach ($storecredit as $item) {
                $storecreditApplied = $item->getStorecreditAmount();
            }
        }
        //fetch order object
        $orderItems = $order->getAllVisibleItems();
        //order item data
        $acceptedOffer = [];
        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($order->getStoreId());
        foreach ($orderItems as $item) {
            //Mage::app()->setCurrentStore($order->getStoreId());
            $product = $item->getProduct();
            $itemOpt = $item->getProductOptionByCode('attributes_info');
            $color = '';
            $size = '';
            if (!empty($itemOpt)) {
                foreach ($itemOpt as $k => $v) {
                    if ($v['label'] == 'Color') {
                        $color = $v['value'];
                    }
                    if ($v['label'] == 'Size') {
                        $size = $v['value'];
                    }
                }
            }

            $acceptedOffer[] = (object)[
                '@type' => 'Offer',
                'itemOffered' => (object)[
                    '@type' => 'Product',
                    'name' => $product->getName(),
                    'image' => Mage::getModel('catalog/product_media_config')
                        ->getMediaUrl($product->getThumbnail()),
                    'sku' => ($item->getSku()) ? $item->getSku() : $item->getId(),
                    'url' => Mage::helper('catalog/product')->getProductUrl($product->getId()),
                    'color' => $color,
                    'size' => $size
                ],
                'price' => $item->getPrice(),
                'priceCurrency' => $order->getOrderCurrencyCode(),
                'eligibleQuantity' => (object)[
                    '@type' => 'QuantitativeValue',
                    'value' => $item->getQtyOrdered()
                ],
                'seller' => (object)[
                    '@type' => 'Organization',
                    'name' => $product->getAttributeText('manufacturer')
                ]
            ];
        }
        $merchant = (object)[
            '@type' => 'Organization',
            'name' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
        ];

        $phone = @$order->getShippingAddress()->getTelephone();

        $customerName = $order->getCustomerName();
        $customerData = (object)[
            '@type' => 'Person',
            'name' => $customerName,
            'email' => $order->getCustomerEmail(),
            'phone' => $phone //order quote address phone number
        ];

        $payment = $order->getPayment();
        $paymentMethodTitle = str_replace(' ', '', $payment->getMethodInstance()->getTitle());
        $paymentMethod = (object)[
            '@type' => 'PaymentMethod',
            'name' => 'http://schema.org/' . $paymentMethodTitle
        ];

        $billingAddress = $order->getBillingAddress();

        $billingAddr = (object)[
            '@type' => 'PostalAddress',
            'name' => $billingAddress->getName(),
            'streetAddress' => $billingAddress->getStreet(),
            'addressLocality' => '',
            'addressRegion' => $billingAddress->getRegionCode(),
            'addressCountry' => $billingAddress->getCountryId(),
            'phone' => @$billingAddress->getTelephone()
        ];
        //shipping address
        $shippingAddr = (object)[
            '@type' => 'PostalAddress',
            'name' => $shippingAddr->getName(),
            'streetAddress' => $shippingAddr->getStreet(),
            'addressLocality' => '',
            'addressRegion' => $shippingAddr->getRegionCode(),
            'addressCountry' => $shippingAddr->getCountryId(),
            'postalCode' => $shippingAddr->getPostcode(),
            'phone' => @$shippingAddr->getTelephone()
        ];
        //prepare array json ld format
        $schemaOrg = "http://schema.org/";
        $data = [
            '@context' => $schemaOrg,
            '@type' => 'order',
            'merchant' => $merchant,
            'customer' => $customerData,
            'OrderNumber' => (string)$order->getIncrementId(),
            'priceCurrency' => $order->getOrderCurrencyCode(),
            'price' => $order->getGrandTotal(),
            'subtotal' => $order->getSubtotal(),
            'storecreditApplied' => $storecreditApplied,
            'acceptedOffer' => $acceptedOffer,
            'orderStatus' => 'http://schema.org/OrderProcessing',
            'paymentMethod' => $paymentMethod,
            'billingAddress' => $billingAddr,
            'shippingAddress' => $shippingAddr,
            'orderDate' => date(DATE_ISO8601, strtotime($order->getCreatedAt())),
            'discount' => $order->getDiscountAmount(),
            'discountCurrency' => $order->getOrderCurrencyCode(),
        ];
        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        $jsonld = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $jsonld;
    }

    public function getShipmentTrackingJsonld($track)
    {
        $order = Mage::getModel('sales/order')->load($track->getOrderId());
        $shippingAddr = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $orderItems = $order->getAllVisibleItems();
        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($order->getStoreId());
        //order item data
        $itemShipped = [];
        foreach ($orderItems as $item) {
//            Mage::app()->setCurrentStore($order->getStoreId());
            $product = $item->getProduct();
            $itemOpt = $item->getProductOptionByCode('attributes_info');
            $color = '';
            $size = '';
            if (!empty($itemOpt)) {
                foreach ($itemOpt as $k => $v) {

                    if ($v['label'] == 'Color') {
                        $color = $v['value'];
                    }
                    if ($v['label'] == 'Size') {
                        $size = $v['value'];
                    }
                }
            }

            $itemShipped[] = (object)[
                '@type' => 'Product',
                'name' => $product->getName(),
                'url' => Mage::helper('catalog/product')->getProductUrl($product->getId()),
                'image' => Mage::getModel('catalog/product_media_config')
                    ->getMediaUrl($product->getThumbnail()),
                'sku' => ($item->getSku()) ? $item->getSku() : $item->getId(),
                'description' => $this->stripTags($product->getDescription()),
                'brand' => [
                    '@type' => 'Brand',
                    'name' => $product->getAttributeText('manufacturer')
                ],
                'color' => $color,
            ];
        }
        
        $phone = @$order->getShippingAddress()->getTelephone();
        //customer is invalid property against parcelDelivery
        $customerName = $order->getCustomerName();
        $customerData = (object)[
            '@type' => 'Person',
            'name' => $customerName,
            'email' => $order->getCustomerEmail(),
            'phone' => $phone, //shipping address telephone number from order object
        ];

        $billingAddr = (object)[
            '@type' => 'PostalAddress',
            'name' => $billingAddress->getName(),
            'streetAddress' => $billingAddress->getStreet(),
            'addressLocality' => '',
            'addressRegion' => $billingAddress->getRegionCode(),
            'addressCountry' => $billingAddress->getCountryId(),
            'phone' => @$billingAddress->getTelephone()
        ];

        $deliveryAddr = (object)[
            '@type' => 'PostalAddress',
            'name' => $shippingAddr->getName(),
            'streetAddress' => $shippingAddr->getStreet(),
            'addressLocality' => '',
            'addressRegion' => $shippingAddr->getRegionCode(),
            'addressCountry' => $shippingAddr->getCountryId(),
            'postalCode' => $shippingAddr->getPostcode(),
            'phone' => @$shippingAddr->getTelephone()
        ];

        $carrier = (object)[
            '@type' => 'Organization',
            'name' => $track->getTitle(),
            'url' => ''
        ];

        $merchant = (object)[
            '@type' => 'Organization',
            'name' => Mage::app()->getStore()->getFrontendName(),
        ];

        $object = [
            '@context' => 'http://schema.org',
            '@type' => 'ParcelDelivery',
            'deliveryAddress' => $deliveryAddr,
            'originAddress' => $billingAddr,
            'customer' => $customerData,
            'carrier' => $carrier,
            'itemShipped' => $itemShipped,
            'trackingNumber' => $track->getTrackNumber(),
            'partOfOrder' => [
                '@type' => 'Order',
                'orderNumber' => (string)$order->getIncrementId(),
                'merchant' => $merchant,
                'orderStatus' => 'http://schema.org/OrderInTransit'
            ]
        ];
        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        $jsonld = json_encode($object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $jsonld;
    }

    public function getCreditmemoJsonld($creditmemo)
    {
        $order = $creditmemo->getOrder();
        $creditReturn = $order->getStore()->roundPrice($creditmemo->getGrandTotal());

        $shippingAddress = $creditmemo->getShippingAddress();
        $billingAddress = $creditmemo->getBillingAddress();
        $orderItems = $order->getAllVisibleItems();
        $deliveryAddr = (object)[
            '@type' => 'PostalAddress',
            'name' => $shippingAddress->getName(),
            'streetAddress' => $shippingAddress->getStreet(),
            'addressLocality' => '',
            'addressRegion' => $shippingAddress->getRegionCode(),
            'addressCountry' => $shippingAddress->getCountryId(),
            'postalCode' => $shippingAddress->getPostcode(),
            'phone' => @$shippingAddress->getTelephone()
        ];

        $billingAddr = (object)[
            '@type' => 'PostalAddress',
            'name' => $billingAddress->getName(),
            'streetAddress' => $billingAddress->getStreet(),
            'addressLocality' => '',
            'addressRegion' => $billingAddress->getRegionCode(),
            'addressCountry' => $billingAddress->getCountryId(),
            'phone' => @$billingAddress->getTelephone()
        ];

        $storecredit = Mage::getModel('aw_storecredit/storecredit')->loadByCustomerId($creditmemo->getCustomerId());
        $creditAvailable = null;
        if (count($storecredit->getData()) > 0) {
            $creditAvailable = $order->getStore()
                ->convertPrice($storecredit->getBalance());
            $creditAvailable = $order->getStore()
                ->roundPrice($creditAvailable);
        }
        $phone = @$order->getShippingAddress()->getTelephone();
        //customer is invalid property against parcelDelivery
        $customerName = $order->getCustomerName();
        $customerData = (object)[
            '@type' => 'Person',
            'name' => $customerName,
            'email' => $order->getCustomerEmail(),
            'phone' => $phone
        ];

        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($order->getStoreId());
        $itemRefund = [];
        foreach ($orderItems as $item) {
            //Mage::app()->setCurrentStore($order->getStoreId());
            $product = $item->getProduct();
            $itemOpt = $item->getProductOptionByCode('attributes_info');
            $color = '';
            $size = '';
            if (!empty($itemOpt)) {
                foreach ($itemOpt as $k => $v) {
                    Mage::log($k, null, 'shipment.log');
                    if ($v['label'] == 'Color') {
                        $color = $v['value'];
                    }
                    if ($v['label'] == 'Size') {
                        $size = $v['value'];
                    }
                }
            }

            $itemRefund[] = (object)[
                '@type' => 'Product',
                'name' => $product->getName(),
                'url' => Mage::helper('catalog/product')->getProductUrl($product->getId()),
                'image' => Mage::getModel('catalog/product_media_config')
                    ->getMediaUrl($product->getThumbnail()),
                'sku' => ($item->getSku()) ? $item->getSku() : $item->getId(),
                'description' => $this->stripTags($product->getDescription()),
                'brand' => [
                    '@type' => 'Brand',
                    'name' => $product->getAttributeText('manufacturer')
                ],
                'color' => $color,
            ];
        }

        $merchant = (object)[
            '@type' => 'Organization',
            'name' => Mage::app()->getStore()->getFrontendName(),
            'sameAs' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
        ];

        $object = [
            '@context' => 'http://schema.org',
            '@type' => 'Creditmemo',
            'deliveryAddress' => $deliveryAddr,
            'originAddress' => $billingAddr,
            'customer' => $customerData,
            'itemRefund' => $itemRefund,
            'priceCurrency' => $creditmemo->getOrderCurrencyCode(),
            'price' => $creditReturn, //total order refund amount
            'storeCredit' => $creditAvailable, //latest customer balance after creditmemo
            'orderNumber' => (string)$order->getIncrementId(),
            'merchant' => $merchant,
            'orderStatus' => 'http://schema.org/OrderReturned'
        ];
        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        $jsonld = json_encode($object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $jsonld;
    }

    /* Get Category Herarchy same like as breadcrumb showing on product detail Page.*/
    public function getCategoryPathUsingProductPath($productId)
    {
        $_product = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('manufacturer')
            ->addAttributeToSelect('category_ids')
            ->addAttributeToSelect('sku')
            ->addAttributeToFilter('entity_id', $productId)
            ->getFirstItem();
        /* Get Category tree path of Product */

        $currentCatIds = $_product->getCategoryIds();
        $categoryLevelCollection = array();
        $categoryLevelCollection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')//2 is actually the first level
            ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
            ->addAttributeToFilter('level', 2)
            ->addAttributeToFilter('is_active', 1);
        $counter_category = $counter_newarrivals = $counter_sales = 0;
        foreach ($categoryLevelCollection as $cat):
            if ($cat->getName() != "Sales" && $cat->getName() != "Create Your Own" && $cat->getName() != "New Arrivals"):
                $counter_category = 1;
                $curen_category_name = $cat->getName();
            elseif ($cat->getName() == "Create Your Own" || $cat->getName() == "New Arrivals"):
                $counter_newarrivals = 1;
            elseif ($cat->getName() == "Sales"):
                $counter_sales = 1;
            endif;
        endforeach;
        $path = array();
        $categoryCollection =
            Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('*')//2 is actually the first level
                ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToSort('level', DESC);
        foreach ($categoryCollection as $cat):
            $path_cat = $cat->getPath();
            $ids = explode('/', $path_cat);

            if (isset($ids[2])) {
                $topParent = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($ids[2]);
            } else {
                $topParent = null;//it means you are in one catalog root.
            }
            if ($counter_category == 1):
                if ($topParent->getName() == $curen_category_name):
                    $pathInStore = $cat->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));

                    $categories = $cat->getParentCategories();

                    // add category path breadcrumb
                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $path['category' . $categoryId] = array(
                                'label' => $categories[$categoryId]->getName(),
                            );
                        }
                    }
                    break;
                endif;
            elseif ($counter_category != 1 && $counter_newarrivals == 1):
                if ($topParent->getName() == "Create Your Own" || $topParent->getName() == "New Arrivals"):
                    $pathInStore = $cat->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));

                    $categories = $cat->getParentCategories();

                    // add category path breadcrumb
                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $path['category' . $categoryId] = array(
                                'label' => $categories[$categoryId]->getName(),
                            );
                        }
                    }
                    break;
                endif;
            elseif ($counter_category != 1 && $counter_newarrivals != 1 && $counter_sales == 1):
                if ($topParent->getName() == "Sales"):
                    $pathInStore = $cat->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));

                    $categories = $cat->getParentCategories();
                    // add category path breadcrumb
                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $path['category' . $categoryId] = array(
                                'label' => $categories[$categoryId]->getName(),
                            );
                        }
                    }
                    break;
                endif;
            endif;
        endforeach;

        $categorys = '';
        $pathIncreament = 1;
        $tatol_count = count($path);
        foreach ($path as $pathvalues) {
            $categorys .= $pathvalues['label'];
            if ($pathIncreament < $tatol_count) {
                $categorys .= '/';
            }
            $pathIncreament++;
        }
        return $categorys;
    }

    /**
     * save data in fpc with key and tag
     * @param mix $data
     * @param string $key
     * @param array $cacheTag array of string
     * @param string $time
     * @param Zend_Controller_Response_Http $response
     * @return bool
     **/
    public function saveInFpc($data, $key, $cacheTag = [], $time = null, $response = null)
    {
        if (empty($cacheTag)) {
            $cacheTag = [self::FPC_BREADCRUMB_DATALAYER];
        }
        if ($time == null) {
            $time = time();
        }
        if ($response == null) {
            $response = Mage::app()->getResponse();
        }
        return $this->_getFpc()->save(
            new Lesti_Fpc_Model_Fpc_CacheItem($data, $time, Mage::helper('fpc')->getContentType($response)),
            $key,
            $cacheTag
        );
    }

    /**
     * get fpc data by key, if not exists
     * save data in fpcs and return
     * @param string $key
     * @return array $data
     **/
    public function getFpcData($key)
    {
        $data = [];
        //check if key exists
        $cacheItem = $this->_getFpc()->load($key);
        if ($cacheItem) {
            $content = $cacheItem->getContent();
            if (!empty($content)) {
                $data = $content;
            }
        }
        return $data;
    }

    /**
     * @return Lesti_Fpc_Model_Fpc
     */
    protected function _getFpc()
    {
        return Mage::getSingleton('fpc/fpc');
    }

    /**
     * create key for the product in relevant store
     * @param Mage_Catalog_Model_Product $product
     * @param mix $store
     * @return string $key
     **/
    public function getFpcDatalayerKey($product, $store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }

        $key = $product->getId() . '_' . $store->getCode() . '_' . $product->getUrlKey();

        return $key;
    }

    /**
     * get category paths from fpc for product in relevant stores
     * if not in fpc, fetch from breadcrumb and store in fpc
     * @param Mage_Catalog_Model_Product $product
     * @param mix $store
     * @return array $categoryPaths
     **/
    public function getCategoryPaths($product, $store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $categoryPaths = [];
        $fpcKey = $this->getFpcDatalayerKey($product, $store);
        if ($this->_getFpc()->isActive()) {
            $categoryPaths = $this->getFpcData($fpcKey);
        }
        //if no data in fpc call ccatalog helper
        if (empty($categoryPaths)) {
            $categoryPaths = Mage::helper('progos_ccatalog')->getBreadcrumbPath();
            if ($this->_getFpc()->isActive()) {
                $this->saveInFpc($categoryPaths, $fpcKey);
            }
        }

        return $categoryPaths;
    }
}

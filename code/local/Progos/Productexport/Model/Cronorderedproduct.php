<?php

class Progos_Productexport_Model_Cronorderedproduct
{
    public function __construct(){
        Mage::init();
    }

    public function exprotcsv(){
        $this->getOrderedProductCollection();
    }

    public function from(){
        $from = Mage::getStoreConfig('progos_product_export/productexport_settings/from');
        return date("Y-m-d".' 00:00:00', strtotime($from));
    }
    public function isWeekDataTrue(){
        return Mage::getStoreConfig('progos_product_export/productexport_settings/weekend');
    }
    public function getWeekData( $week ){
        $dateArray = array();
        $dateArray['from'] = date("Y-m-d".' 00:00:00', strtotime("-".$week." week"));
        $dateArray['to'] = date('Y-m-d H:i:s');
        return $dateArray;
    }

    public function to(){
        $to = Mage::getStoreConfig('progos_product_export/productexport_settings/to');
        return date("Y-m-d".' 23:59:59', strtotime($to));
    }

    public function getSellerName( $id ){
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $id, 'seller_id' );
        if( $seller ){
            return $seller->getStoreTitle();
        }else{
            return "";
        }
    }

    public function categoryPath($product)
    {
        $currentCatIds = $product->getCategoryIds();
        $categoryLevelCollection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')//2 is actually the first level
            ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
            ->addAttributeToFilter('level',2)
            ->addAttributeToFilter('is_active', 1);
        $counter_category = 0;
        $counter_newarrivals = 0;
        $counter_sales = 0;
        foreach($categoryLevelCollection as $cat):
            if($cat->getName()!="Sales" && $cat->getName()!="Create Your Own" && $cat->getName()!="New Arrivals"):
                $counter_category = 1;
                $curen_category_name = $cat->getName();
            elseif($cat->getName() == "Create Your Own" || $cat->getName() == "New Arrivals"):
                $counter_newarrivals = 1;
            elseif($cat->getName() == "Sales"):
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
        foreach($categoryCollection as $cat):
            $path_cat = $cat->getPath();
            $ids = explode('/', $path_cat);

            if (isset($ids[2])){
                $topParent = Mage::getModel('catalog/category')->load($ids[2]);
            }
            else{
                $topParent = null;//it means you are in one catalog root.
            }
            if($counter_category == 1):
                if($topParent->getName() == $curen_category_name):
                    $pathInStore = $cat->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));

                    $categories = $cat->getParentCategories();

                    // add category path breadcrumb
                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $path['category'.$categoryId] = array(
                                'label' => $categories[$categoryId]->getName()
                            );
                        }
                    }
                    break;
                endif;
            elseif($counter_category != 1 && $counter_newarrivals == 1):
                if($topParent->getName()=="Create Your Own" || $topParent->getName()=="New Arrivals"):
                    $pathInStore = $cat->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));

                    $categories = $cat->getParentCategories();

                    // add category path breadcrumb
                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $path['category'.$categoryId] = array(
                                'label' => $categories[$categoryId]->getName()
                            );
                        }
                    }
                    break;
                endif;
            elseif($counter_category != 1 && $counter_newarrivals != 1 && $counter_sales == 1):
                if($topParent->getName() == "Sales"):
                    $pathInStore = $cat->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));

                    $categories = $cat->getParentCategories();

                    // add category path breadcrumb
                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $path['category'.$categoryId] = array(
                                'label' => $categories[$categoryId]->getName()
                            );
                        }
                    }
                    break;
                endif;
            endif;
        endforeach;
        $categoryPath = "";
        foreach ($path as $category ):
            $label = str_replace( '&',' and ',str_replace( '&amp;','',  $category['label'] ));
            $categoryPath .= $label." -> ";
        endforeach;
        return rtrim($categoryPath , ' -> ');
    }

    public function getNoOfViews( $from , $to ){
        $viewedProducts = Mage::getResourceModel('reports/product_collection')->addViewsCount($from, $to);
        $result = array();
        foreach($viewedProducts as $product) {
            $result[$product->getData('entity_id')] = $product->getData('views');
        }
        return $result;
    }

    public function getOrderedProductCollection(){
        $dateArray = array();
        if( $this->isWeekDataTrue() != "" ){
            $dateArray = $this->getWeekData( $this->isWeekDataTrue() );
        }else{
            $dateArray['from'] = $this->from();
            $dateArray['to'] = $this->to();
        }

        ini_set('memory_limit', '-1');
        $simpleProductArray = array();
        $configProductArray = array();
        $orderArray = array() ;
        $marketplaceCollection = Mage::getModel('marketplace/commission')
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('created_at', array('from' => $dateArray['from'], 'to' => $dateArray['to']));
        $views = $this->getNoOfViews($dateArray['from'],$dateArray['to']);
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));

        $data  = "Supplier Name,";          $data .= "Customer Id,";
        $data .= "Inventory Type,";         $data .= "Brand,";
        $data .= "Category Path,";
        $data .= "Elabelz Sku,";            $data .= "Supplier Sku,";
        $data .= "Color,";                  $data .= "Size,";
        $data .= "Style Name,";             $data .= "RRP(AED),";
        $data .= "Special Price(AED),";     $data .= "Cost Price(AED),";
        $data .= "Cost Margin%,";           $data .= "Season,";
        $data .= "Year,";                   $data .= "Creation Date,";
        $data .= "Age (Number of Days Since live),"; $data .= "Model Name,";
        $data .= "Order Date,";         $data .= "Order Time,";
        $data .= "Order Country,"; $data .= "Order Status,";
        $data .= "Sales Quantity,"; $data .= "Sales Price,";
        $data .= "Discount Code,"; $data .= "Status,";
        $data .= "No of Views \n";
        foreach( $marketplaceCollection as $collect ){
            if( !isset(  $simpleProductArray[$collect->getProductId()] ) ){
                $collection = Mage::getModel('catalog/product')
                    ->getCollection()->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', array('eq' => $collect->getProductId()))
                    ->addAttributeToFilter('type_id', array('eq' => 'simple'));
                foreach( $collection as $product ){
                    $simpleProductArray[$product->getId()]['status']         =    $product->getAttributeText('status');
                    $brand = $product->getAttributeText('manufacturer');
                    $simpleProductArray[$product->getId()]['brand']          =    $brand;
                    $inventryType = $product->getAttributeText('inventory_type');
                    $simpleProductArray[$product->getId()]['inventory_type'] = $inventryType;
                    $simpleProductArray[$product->getId()]['sku']           =    $product->getSku();
                    $simpleProductArray[$product->getId()]['suppliersku']   =    $product->getSupplierSku();
                    $simpleProductArray[$product->getId()]['color']         =    $product->getAttributeText('color');
                    $simpleProductArray[$product->getId()]['size']          =    $product->getAttributeText('size');
                    $year =  date('Y', strtotime($product->getCreatedAt()));
                    $simpleProductArray[$product->getId()]['year']          =    $year;
                    $simpleProductArray[$product->getId()]['createddate']   =    $product->getCreatedAt();
                    $productDate = strtotime($product->getCreatedAt());
                    $age = time() - $productDate ;
                    $simpleProductArray[$product->getId()]['age']           =    floor($age / (60 * 60 * 24));

                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                    $id = $product->getId();
                    if( !empty( $parentIds ) ) {
                        $parentId = $parentIds[0];
                        if( !isset( $configProductArray[$parentId] ) ){
                            $configProduct = Mage::getModel('catalog/product')
                                ->getCollection()
                                ->addAttributeToSelect('*')
                                ->addAttributeToFilter('entity_id', array('eq' => $parentId));
                            foreach( $configProduct as $config){
                                if( $inventryType == '' ):
                                    $configProductArray[$parentId]['inventory_type']   = $simpleProductArray[$id]['inventory_type']   =  $config->getAttributeText('inventory_type');
                                endif;
                                if( $brand == "" ):
                                    $configProductArray[$parentId]['brand']            = $simpleProductArray[$id]['brand']        =  $config->getAttributeText('manufacturer');
                                endif;
                                if( isset( $views[$config->getId()] ) ):
                                    $configProductArray[$parentId]['views']            = $simpleProductArray[$id]['views']        =  $views[$config->getId()];
                                endif;
                                $configProductArray[$parentId]['stylename']            = $simpleProductArray[$id]['stylename']        =  $config->getSku();
                                $configProductArray[$parentId]['price']                = $simpleProductArray[$id]['price']            =  $config->getPrice();
                                $configProductArray[$parentId]['special_price']        = $simpleProductArray[$id]['special_price']    =  $config->getSpecialPrice();
                                $configProductArray[$parentId]['cost']                 = $simpleProductArray[$id]['cost']             =  $config->getCost();
                                $configProductArray[$parentId]['cost_price_percentage']= $simpleProductArray[$id]['cost_price_percentage']   =  $config->getCostPricePercentage();
                                $configProductArray[$parentId]['season']               = $simpleProductArray[$id]['season']           =  $config->getAttributeText('season');
                                $configProductArray[$parentId]['seller_name']          = $simpleProductArray[$id]['seller_name']      =  $this->getSellerName( $config->getSellerId() );
                                $configProductArray[$parentId]['sellerId']             = $simpleProductArray[$id]['sellerId']         =  $config->getSellerId();
                                $configProductArray[$parentId]['division']             = $simpleProductArray[$id]['division']         =  $this->categoryPath($config);
                            }
                        }else{
                            if( $inventryType == '' ):
                                $simpleProductArray[$id]['inventory_type']= $configProductArray[$parentId]['inventory_type'];
                            endif;
                            if( $brand == "" ):
                                $simpleProductArray[$id]['brand']         = $configProductArray[$parentId]['brand'];
                            endif;
                            if( isset( $views[$config->getId()] ) ):
                                $simpleProductArray[$id]['views']         = $configProductArray[$parentId]['views'];
                            endif;
                            $simpleProductArray[$id]['stylename']         = $configProductArray[$parentId]['stylename'];
                            $simpleProductArray[$id]['price']             = $configProductArray[$parentId]['price'];
                            $simpleProductArray[$id]['special_price']     = $configProductArray[$parentId]['special_price'];
                            $simpleProductArray[$id]['cost']              = $configProductArray[$parentId]['cost'];
                            $simpleProductArray[$id]['cost_price_percentage'] = $configProductArray[$parentId]['cost_price_percentage'];
                            $simpleProductArray[$id]['season']            = $configProductArray[$parentId]['season'];
                            $simpleProductArray[$id]['seller_name']       = $configProductArray[$parentId]['seller_name'];
                            $simpleProductArray[$id]['sellerId']          = $configProductArray[$parentId]['sellerId'];
                            $simpleProductArray[$id]['division']          = $configProductArray[$parentId]['division'];
                        }
                    }else{
                        $simpleProductArray[$id]['stylename']       = "";
                        $simpleProductArray[$id]['views']           = "";
                        $simpleProductArray[$id]['price']           = "";
                        $simpleProductArray[$id]['special_price']   = "";
                        $simpleProductArray[$id]['cost']            = "";
                        $simpleProductArray[$id]['cost_price_percentage'] = "";
                        $simpleProductArray[$id]['season']          = "";
                        $simpleProductArray[$id]['seller_name']     = $this->getSellerName( $product->getSellerId() );
                        $simpleProductArray[$id]['sellerId']        = $product->getSellerId();
                        $simpleProductArray[$id]['division']        = "";
                    }
                }
            }

            if( !isset( $orderArray[$collect->getIncrementId()] ) ){
                $order = Mage::getModel('sales/order')->loadByIncrementId($collect->getIncrementId() );
                if( !empty($order->getIncrementId())  ) {
                    $orderArray[$collect->getIncrementId()]['orderdate'] = date('Y-m-d', strtotime($order->getCreatedAt()));
                    $orderArray[$collect->getIncrementId()]['ordertime'] = date("H:i:s", strtotime($order->getCreatedAt()));
                    $orderArray[$collect->getIncrementId()]['ordercountry'] = $order->getShippingAddress()->getCountryModel()->getName() ;
                    $orderArray[$collect->getIncrementId()]['orderstatus'] = $order->getStatus();
                    $currentCurrencyCode = $order->getOrderCurrencyCode();
                    if ($currentCurrencyCode != 'AED') {
                        // the price converted
                        $customs_value = $collect->getProductAmt() / $rates[$currentCurrencyCode];
                    }else{
                        $customs_value = $collect->getProductAmt();
                    }
                    $orderArray[$collect->getIncrementId()]['salesqty'] = $collect->getProductQty();
                    $orderArray[$collect->getIncrementId()]['saleprice'] = (int)$customs_value;
                    $orderArray[$collect->getIncrementId()]['discountcode'] = $order->getCouponCode();
                }else{
                    continue;
                }
            }

            $id = $collect->getProductId();
            //Add Content In CSV File.
            $data .= $simpleProductArray[$id]['seller_name'].",";       $data .= $simpleProductArray[$id]['sellerId'].",";
            $data .= $simpleProductArray[$id]['inventory_type'].",";    $data .= $simpleProductArray[$id]['brand'].",";
            $data .= $simpleProductArray[$id]['division'].",";
            $data .= $simpleProductArray[$id]['sku'].",";               $data .= $simpleProductArray[$id]['suppliersku'].",";
            $data .= $simpleProductArray[$id]['color'].",";             $data .= $simpleProductArray[$id]['size'].",";
            $data .= $simpleProductArray[$id]['stylename'].",";         $data .= $simpleProductArray[$id]['price'].",";
            $data .= $simpleProductArray[$id]['special_price'].",";     $data .= $simpleProductArray[$id]['cost'].",";
            $data .= $simpleProductArray[$id]['cost_price_percentage'].",";
            $data .= $simpleProductArray[$id]['season'].",";            $data .= $simpleProductArray[$id]['year'].",";
            $data .= $simpleProductArray[$id]['createddate'].",";
            $data .= $simpleProductArray[$id]['age'].",";               $data .= "Model Name,";
            $data .= $orderArray[$collect->getIncrementId()]['orderdate'].",";
            $data .= $orderArray[$collect->getIncrementId()]['ordertime'].",";
            $data .= $orderArray[$collect->getIncrementId()]['ordercountry'].",";
            $data .= $orderArray[$collect->getIncrementId()]['orderstatus'].",";
            $data .= $collect->getProductQty().",";
            $data .= $collect->getProductAmt().",";
            $data .= $orderArray[$collect->getIncrementId()]['discountcode'].",";
            $data .= $simpleProductArray[$id]['status'].",";
            $data .= $simpleProductArray[$id]['views']." \n";
        }

        if( !file_exists( Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import') ){
            mkdir(Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import' ,0777, true);
        }
        $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import'.DS.'productsalesexport.csv';

        if( !file_exists( $mainDirectory ) ){
            chmod($mainDirectory, 0777);
        }
        file_put_contents($mainDirectory, $data);

        if( $this->getEmailStatus() )
            $this->sendEmail();
    }

    public function sendEmail(){
        $templateId = (int) Mage::getStoreConfig('progos_product_export/productexport_settings/email_template');
        $adminEmailId = Mage::getStoreConfig('progos_product_export/productexport_settings/admin_email_id');
        $toName = Mage::getStoreConfig("trans_email/ident_$adminEmailId/name");
        $toMailId = Mage::getStoreConfig("trans_email/ident_$adminEmailId/email");
        $emailTemplate = Mage::getModel ( 'core/email_template' )->load ( $templateId );
        $emailTemplate->setSenderName($toName);
        $emailTemplate->setSenderEmail($toMailId);
        $html = $emailTemplate->getProcessedTemplate();
        $emailTemplate->setTemplateText($html);

        $file = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import'.DS.'productsalesexport.csv';
        $attachment = file_get_contents($file);

        $emailTemplate->getMail()->createAttachment(
            $attachment,
            Zend_Mime::TYPE_OCTETSTREAM,
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            'productsalesexport.csv'
        );

        if( $this->getEmailAdress() != "" ){
            $to_email_arr = $this->getEmailAdress();
            $to_name_arr  = $this->getEmailAdressName();
        }else{
            $to_email_arr = $toMailId;
            $to_name_arr  = $toName;
        }

        $emailTemplate->send($to_email_arr, $to_name_arr);
        return;
    }

    public function getEmailAdress(){
        return Mage::getStoreConfig('progos_product_export/productexport_settings/email');
    }

    public function getEmailAdressName(){
        return Mage::getStoreConfig('progos_product_export/productexport_settings/name');
    }

    public function getEmailStatus(){
        $status = Mage::getStoreConfig('progos_product_export/productexport_settings/emailstatus');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}
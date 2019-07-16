<?php

class Progos_Productexport_Model_Cron
{
    public function __construct(){
        Mage::init();
    }

    public function exprotcsv(){
        $this->getProductCollection();
    }

    public function getSellerName( $id ){
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $id, 'seller_id' );
        if( $seller ){
            return $seller->getStoreTitle();
        }else{
            return "";
        }

    }

    public function getNoOfViews(){
        $viewedProducts = Mage::getResourceModel('reports/product_collection')->addViewsCount();
        $result = array();
        foreach($viewedProducts as $product) {
            $result[$product->getData('entity_id')] = $product->getData('views');
        }
        return $result;
    }

    public function getProductCollection(){
        ini_set('memory_limit', '-1');
        if( !file_exists( Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import') ){
            mkdir(Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import' ,0777, true);
        }
        $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import'.DS.'productexport.csv';

        if( !file_exists( $mainDirectory ) ){
            //$data = "Supplier Name,Customer Id,Inventory Type,Brand,Elabelz Sku,Category Path,Supplier Sku,Color,Size,Style Name,RRP(AED),Special Price(AED),Cost Price(AED),Cost Margin%,Season,Year,Creation Date,Age (Number of Days Since live),Model Name,Stock on Hand Units,Status,No of Views,Product Type,Product Id \n";
            $data = "Supplier Name,Customer Id,Inventory Type,Brand,Elabelz Sku,Supplier Sku,Color,Size,Style Name,RRP(AED),Special Price(AED),Cost Price(AED),Cost Margin%,Season,Year,Creation Date,Age (Number of Days Since live),Model Name,Stock on Hand Units,Status,No of Views,Product Type,Product Id \n";
            chmod($mainDirectory, 0777);
        }else{
            unlink($mainDirectory);
            //$data = "Supplier Name,Customer Id,Inventory Type,Brand,Elabelz Sku,Category Path,Supplier Sku,Color,Size,Style Name,RRP(AED),Special Price(AED),Cost Price(AED),Cost Margin%,Season,Year,Creation Date,Age (Number of Days Since live),Model Name,Stock on Hand Units,Status,No of Views,Product Type,Product Id \n";
            $data = "Supplier Name,Customer Id,Inventory Type,Brand,Elabelz Sku,Supplier Sku,Color,Size,Style Name,RRP(AED),Special Price(AED),Cost Price(AED),Cost Margin%,Season,Year,Creation Date,Age (Number of Days Since live),Model Name,Stock on Hand Units,Status,No of Views,Product Type,Product Id \n";
            chmod($mainDirectory, 0777);
        }

        file_put_contents($mainDirectory, $data , FILE_APPEND|LOCK_EX );
        chmod($mainDirectory, 0777);
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', array('eq' => 'simple'))
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');

        $parentObject = array();
        $views = $this->getNoOfViews();
        foreach( $collection as $product ){
            $InventoryType  = $product->getAttributeText('inventory_type');
            $brand = $product->getAttributeText('manufacturer');
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if( !empty( $parentIds ) ){
                $parentId = $parentIds[0];
                if( !isset( $parentObject[$parentId] ) ){
                    $parentObject[$parentId] = array();

                    $configProduct = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('entity_id', array('eq' => $parentId));

                    foreach( $configProduct as $config){
                        //$parentObject[$parentId]['categoryPath']            =    $this->categoryPath($config);
                        if( $InventoryType == "" ):
                            $parentObject[$parentId]['inventory_type']      =   $config->getAttributeText('inventory_type');
                        else:
                            $parentObject[$parentId]['inventory_type'] = $InventoryType ;
                        endif;
                        if( $brand == "" ):
                            $brand = $parentObject[$parentId]['brand'] = $config->getAttributeText('manufacturer');
                        endif;
                        if( isset( $views[$config->getId()] ) ):
                            $view = $parentObject[$parentId]['views']      =  $views[$config->getId()];
                        endif;
                        $parentObject[$parentId]['stylename']               =   $config->getSku();
                        $parentObject[$parentId]['price']                   =   $config->getPrice();
                        $parentObject[$parentId]['special_price']           =   $config->getSpecialPrice();
                        $parentObject[$parentId]['cost']                    =   $config->getCost();
                        $parentObject[$parentId]['cost_price_percentage']   =   $config->getCostPricePercentage();
                        $parentObject[$parentId]['season']                  =   $config->getAttributeText('season');
                        $parentObject[$parentId]['seller_name']             =   $this->getSellerName( $config->getSellerId() );
                        $parentObject[$parentId]['sellerId']                =   $config->getSellerId();
                        $stylename      =   $parentObject[$parentId]['stylename'];
                        $InventoryType  =   $parentObject[$parentId]['inventory_type'];
                        $price          =   $parentObject[$parentId]['price'];
                        $specialPrice   =   $parentObject[$parentId]['special_price'];
                        $cost           =   $parentObject[$parentId]['cost'];
                        $costPercentage =   $parentObject[$parentId]['cost_price_percentage'];
                        $season         =   $parentObject[$parentId]['season'];
                        $seller_name    =   $parentObject[$parentId]['seller_name'];
                        $sellerId       =   $parentObject[$parentId]['sellerId'];
                        //$categoryPath   =   $parentObject[$parentId]['categoryPath'];

                        $cdata  = $this->getSellerName( $config->getSellerId() ).",";   $cdata .= $config->getSellerId().",";
                        $cdata .= $InventoryType.",";   $cdata .= $brand.",";   $cdata .= $config->getSku().",";
                        //$cdata .= $categoryPath.",";
                        $cdata .= $config->getSupplierSku().",";
                        $cdata .= ",";  $cdata .= ",";  $cdata .= $stylename.",";   $cdata .= $price.",";   $cdata .= $specialPrice.",";
                        $cdata .= $cost.",";    $cdata .= $costPercentage.",";  $cdata .= $season.",";
                        $year =  date('Y', strtotime($config->getCreatedAt()));;
                        $cdata .= $year.",";    $cdata .= $config->getCreatedAt().",";
                        $now = time();
                        $productDate = strtotime($config->getCreatedAt());
                        $age = $now - $productDate ;
                        $age =  floor($age / (60 * 60 * 24));
                        $cdata .= $age.","; $cdata .= "Model Name,";    $cdata .= $config->getQty().",";    $cdata .= $config->getAttributeText('status').",";
                        $cdata .= $view.",";    $cdata .= $config->getTypeId().","; $cdata .= $config->getId().","; $cdata .= " \n";
                        file_put_contents( $mainDirectory, $cdata , FILE_APPEND|LOCK_EX );
                    }
                }else{
                    if( $InventoryType == "" ):
                        $InventoryType      =   $parentObject[$parentId]['inventory_type'];
                    endif;
                    if( $brand == "" ):
                        $brand              = $parentObject[$parentId]['brand'];
                    endif;
                    if( isset( $views[$parentId] ) ):
                        $view = $parentObject[$parentId]['views']      =  $views[$parentId];
                    endif;

                    //$categoryPath       =   $parentObject[$parentId]['categoryPath'];
                    $stylename          =   $parentObject[$parentId]['stylename'];
                    $price              =   $parentObject[$parentId]['price'];
                    $specialPrice       =   $parentObject[$parentId]['special_price'];
                    $cost               =   $parentObject[$parentId]['cost'];
                    $costPercentage     =   $parentObject[$parentId]['cost_price_percentage'];
                    $season             =   $parentObject[$parentId]['season'];
                    $seller_name        =   $parentObject[$parentId]['seller_name'];
                    $sellerId           =   $parentObject[$parentId]['sellerId'];
                }
            }else{
                $price = $specialPrice = $cost = $costPercentage = $view = $season = $stylename = $categoryPath = "";
                $seller_name    = $this->getSellerName( $product->getSellerId() );
                $sellerId       = $product->getSellerId();
            }

            $data  = $seller_name.",";  $data .= $sellerId.","; $data .= $InventoryType.",";    $data .= $brand.",";
            $data .= $product->getSku().",";
            //$data .= $categoryPath.",";
            $data .= $product->getSupplierSku().",";    $data .= $product->getAttributeText('color').",";
            $data .= $product->getAttributeText('size').",";    $data .= $stylename.",";    $data .= $price.",";    $data .= $specialPrice.",";
            $data .= $cost.","; $data .= $costPercentage.",";   $data .= $season.",";   $year =  date('Y', strtotime($product->getCreatedAt()));;
            $data .= $year.","; $data .= $product->getCreatedAt().",";  $now = time();
            $productDate = strtotime($product->getCreatedAt());
            $age = $now - $productDate ;
            $age =  floor($age / (60 * 60 * 24));
            $data .= $age.",";  $data .= "Model Name,"; $data .= $product->getQty().",";    $data .= $product->getAttributeText('status').",";
            $data .= $view.","; $data .= $product->getTypeId().","; $data .= $product->getId().","; $data .= " \n";
            file_put_contents( $mainDirectory, $data , FILE_APPEND|LOCK_EX );
        }
        if( $this->getEmailStatus() )
            $this->sendEmail();
        return true;
    }

    public function inQueryString( $strings ){
        $strings = explode(",",$strings);
        $str = '';
        foreach( $strings as $string ){
            $str .= "'".$string."'";
            $str .=",";
        }
        return rtrim($str,',');
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

        $file = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import'.DS.'productexport.csv';
        $attachment = file_get_contents($file);

        $emailTemplate->getMail()->createAttachment(
            $attachment,
            Zend_Mime::TYPE_OCTETSTREAM,
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            'productexport.csv'
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
    public function getEmailStatus(){
        $status = Mage::getStoreConfig('progos_product_export/productexport_settings/emailstatus');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getEmailAdress(){
        return Mage::getStoreConfig('progos_product_export/productexport_settings/email');
    }

    public function getEmailAdressName(){
        return Mage::getStoreConfig('progos_product_export/productexport_settings/name');
    }
}
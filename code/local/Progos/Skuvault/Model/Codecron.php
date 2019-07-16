<?php
/**
 * Progos_Skuvault.
 * @category Elabelz
 * @Author Saroop Chand
 * @Date 06-02-2018
 */
class Progos_Skuvault_Model_Codecron{
    const API_ALLPRODUCTS  = 'https://app.skuvault.com/api/products/getProducts';
    protected $curl = null;
    protected $Skuvaulthelper;

    public function __construct(){
        Mage::init();
        $this->curl = new Zend_Http_Client();
        $this->Skuvaulthelper = Mage::helper('progos_skuvault');
    }

    /*
     *  Description : This Method Run from Admin. When clicked on 'Add Code'
     * */
    public function addProductCode(){
        /* If Code update Script is disabled from Admin Config. */
        if( !$this->getEnable() )
            return "Please Enable Extension.";
        try{
            /* If code update from start */
            if( $this->getCodeBegning() ){
                $body['PageNumber']= $this->getPageStart() ;
                $body['PageSize']= $this->getRecordPerPage() ;
                $response = $this->sendRequest(self::API_ALLPRODUCTS, $body);
                $response = Zend_Http_Response::extractBody($response);
                $response = json_decode($response, true);
                if( $response['Products'] ){
                    $this->productCodeUpdate( $response['Products'] , $body['PageNumber'] );
                    return 'Success For One Iteration of All Product. Api Page Number = '.$body['PageNumber'];
                }
            }else{ //Code update only for those skus which are not updated previous.
                return $this->getProductSku();
            }
        }catch (Exception $e){
            Mage::log( 'something went wrong --- '.$e->getMessage() , null , 'skuvault_code_add.log');
        }
    }

    public function addProductCodeByCron(){
        if( !$this->getEnable() )
            return "Please Enable Extension.";
        try{
            /* If code update from start */
            if( $this->getCodeBegning() ) {
                $body['PageNumber'] = $this->getPageStart();
                $body['PageSize'] = $this->getRecordPerPage();
                $response = $this->sendRequest(self::API_ALLPRODUCTS, $body);
                $response = Zend_Http_Response::extractBody($response);
                $response = json_decode($response, true);
                if ($response['Products']) {
                    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                    $this->recursiveService($response['Products'], $body['PageNumber'], $body['PageSize']);
                    return " Success ";
                }
            }else{ //Code update only for those skus which are not updated previous.
                return $this->getProductSku();
            }
        }catch (Exception $e){
            Mage::log('something went wrong --- '.$e->getMessage(), null, 'skuvault_code_add.log');
        }
        return "Success";
    }

    public function recursiveService( $products , $pageNumber , $qty ){
        if(is_array($products)){
            $products = $this->getAllSku( $products );
            $collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('visibility')
                ->addAttributeToSelect('status')
                ->addAttributeToFilter('sku', array('in' => $products));
            $productFliped = array_flip( $products );
            foreach( $collection as $product ){
                if( $productFliped[$product->getSku()] ) {
                    $product->setSkuvaultCode($productFliped[$product->getSku()]);
                    $product->save();
                }
            }

            Mage::getConfig()->saveConfig( 'sku_vault_general/skuvault_product_code/addcode_pagenumber' , $pageNumber );

            if( count($products) == $qty ){
                echo $pageNumber." --- Done \n";
                $pageNumber++;
                $body['PageNumber'] = $pageNumber ;
                $body['PageSize'] = $qty;
                $response = $this->sendRequest(self::API_ALLPRODUCTS, $body);
                $response = Zend_Http_Response::extractBody($response);
                $response = json_decode($response, true);
                $this->recursiveService( $response['Products'] ,  $pageNumber , $qty );
            }
        }
        return;
    }

    /*
     * Get All Those sku of the product which are not updated.
     * */
    public function getProductSku(){
        $productSkus = array();
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToFilter('skuvault_code', array('null' => true));
        foreach( $collection as $product ){
            $productSkus[] = $product->getSku();
        }

        if( empty( $productSkus ) )
            return "No sku available without skuvault code.";

        $body['ProductSKUs']= $productSkus ;
        $response = $this->sendRequest(self::API_ALLPRODUCTS, $body);
        $response = Zend_Http_Response::extractBody($response);
        $response = json_decode($response, true);
        if( $response['Products'] ){
            //If config set `Add Skuvault Code from Beginning` set to No.
            $this->productCodeUpdateSkuBased( $response['Products'] , $response['Errors'] );
            return 'Success Sku based.';
        }
        if( $response['Errors'] ){
            Mage::log( print_r( $response['Errors'],true), null, 'skuvault_code_add.log');
            return "Sku's are not available. Please check skuvault_code_add.log file.";
        }
        return "No Changes.";
    }

    /*
     * Update Product only for those sku which are newly added. Means run for new product. If config set `Add Skuvault Code from Beginning` set to No.
     * */
    public function productCodeUpdateSkuBased( $products , $errors ){
        $products = $this->getAllSku( $products );
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('visibility')
            ->addAttributeToFilter('sku', array('in' => $products));
        $productFliped = array_flip( $products );
        foreach( $collection as $product ){
            if( $productFliped[$product->getSku()] ) {
                $product->setSkuvaultCode($productFliped[$product->getSku()]);
                $product->save();
            }
        }
        if( !empty($errors) ){
            Mage::log(print_r($errors,true), null, 'skuvault_code_add.log');
        }
        return true;
    }

    /*
     * Product Code are updated from Admin side after click on Add Code Button.
     * It will not run in recursive method its just work for one iteration of api.
     * */
    public function productCodeUpdate( $products , $pageNumber ){
        $products = $this->getAllSku( $products );
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('visibility')
            ->addAttributeToFilter('sku', array('in' => $products));
        $productFliped = array_flip( $products );
        foreach( $collection as $product ){
            if( $productFliped[$product->getSku()] ) {
                $product->setSkuvaultCode($productFliped[$product->getSku()]);
                $product->save();
            }
        }
        $pageNumber++;
        Mage::getConfig()->saveConfig( 'sku_vault_general/skuvault_product_code/addcode_pagenumber' , $pageNumber );
        return true;
    }

    public function getAllSku( $products ){
        $prod = array();
        foreach( $products as $product ){
            $prod[$product['Code']] = $product['Sku'];
        }
        return $prod;
    }

    /*
     * Check script is running for dry run or implementation of changes.
     * */
    public function getEnable(){
        $status = Mage::getStoreConfig('sku_vault_general/skuvault_product_code/addcode_enable');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getCodeBegning(){
        $status = Mage::getStoreConfig('sku_vault_general/skuvault_product_code/addcode_beginning');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }

    public function getPageStart(){
        return Mage::getStoreConfig('sku_vault_general/skuvault_product_code/addcode_pagenumber');
    }

    public function getRecordPerPage(){
        return Mage::getStoreConfig('sku_vault_general/skuvault_product_code/addcode_perpagerecord');
    }

    /**
     * @param $url
     * @param null $body
     * @return null|string
     *
     * Sends request with data to SkuVault using SkuVault API endpoints.
     */
    protected function sendRequest($url, $body = null)
    {
        $response = null;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $requestBody = [
            'TenantToken' => $this->Skuvaulthelper->getSkuvaultTenantToken(),
            'UserToken' => $this->Skuvaulthelper->getSkuvaultUserToken(),
        ];

        if (!is_null($body)) {
            foreach ($body as $key => $value) {
                $requestBody[$key] = $value;
            }
        }
        $requestBody = json_encode($requestBody);

        try {
            // Such big timeout is needed because SkuVault updateProducts request is taking a lot of time.
            $this->curl->setConfig(array('timeout' => 120));
            $this->curl->setUri($url);
            $this->curl->setHeaders($headers);
            $this->curl->setRawData($requestBody);

            $response = $this->curl->request(Zend_Http_Client::POST);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $response;
    }
}
?>
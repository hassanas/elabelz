<?php

/**
 * Progos_Skuvault.
 *
 * @category Elabelz
 *
 * @Author Hassan Ali Shahzad
 * @Date 20-06-2017
 *
 */

class Progos_Skuvault_Model_Cron
{
    const GET_BRANDS_URL = 'https://app.skuvault.com/api/products/getBrands';
    const CREATE_BRANDS_URL = 'https://app.skuvault.com/api/products/createBrands';
    const GET_SUPPLIERS_URL = 'https://app.skuvault.com/api/products/getSuppliers';
    const CREATE_SUPPLIERS_URL = 'https://app.skuvault.com/api/products/createSuppliers';
    const GET_PRODUCTS_URL = 'https://app.skuvault.com/api/products/getProducts';
    const UPDATE_PRODUCTS_URL = 'https://app.skuvault.com/api/products/updateProducts';
    const SKU_VAULT_API_MODERATE_REGUEST_TIMEOUT = 6;
    const SKU_VAULT_API_LIMIT = 100;

    /**
     * @var Zend_Http_Client
     */
    protected $curl = null;

    protected $skuVaultBrands = [];

    protected $skuVaultSuppliers = [];

    protected $adminBrands = [];

    protected $tenantToken = 'SssekIKZ9ibj4RsAkhxRvtE74q51xZt/2Xhb3rR4gak=';

    protected $userToken = 'Kg3uO2eIUWWgAqBC5VHYK3SWI/dMQ+YDFGdkhaILWNA=';

    protected $brandsToCreate = [];

    protected $suppliersToCreate = [];

    protected $productData = [];

    protected $productCounter = 0;

    protected $productUpdateWithoutError = true;

    public function __construct()
    {
        $this->curl = new Zend_Http_Client();
        //$this->skuVaultBrands = $this->getBrands(); // Disabled for right now
        $this->skuVaultSuppliers = $this->getSuppliers();
    }

    /**
     * @return bool
     *
     * Just shell function to run cron with correct parameter.
     */
    public function runBrandSyncShell()
    {
        $runBrandSyncForAllProducts = false;

        return $this->runBrandSync($runBrandSyncForAllProducts);
    }

    /**
     * @param bool $allProducts
     * @return bool
     *
     * Run sync process that will take all brands that are not in SkuVault yet and create them.
     */
    public function runBrandSync($allProducts = false)
    {
        $this->productData = [];
        $this->brandsToCreate = [];

        try {
            $products = $this->getProductCollection($allProducts);

            if ($products == null) {
                Mage::log('No products found for today', null, 'skuvault.log');

                return true;
            }

            Mage::getSingleton('core/resource_iterator')->walk(
                $products->getSelect(),
                array(array($this, 'callbackIterateProductCollection'))
            );

            if (!empty($this->brandsToCreate)) {
                $this->createBrands($this->brandsToCreate);
            }
            if (!empty($this->productData)) {
                $response = $this->updateProducts($this->productData);

                return $response;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return true;
    }

    /**
     * This function will sync Magento Brands with skuvault's supplier
     */
    public function productBrandSyncWithSkuvaultSupplier()
    {
        $this->productData = [];
        $this->suppliersToCreate = [];
        try {
            Mage::init();
            $products = Mage::helper("progos_skuvault")->getNonUpdatedSkuvaultProducts();
            if (count($products) ==  0) {
                Mage::log('No products found for today', null, 'skuvault.log');
                return true;
            }
            foreach($products as $product){
                if (!in_array(strtolower($product['manufacturer']), $this->skuVaultSuppliers)) {
                    $this->suppliersToCreate[] = trim($product['manufacturer']);
                }
                $this->productData[$product['Sku']] = trim($product['manufacturer']);
            }
            if (!empty($this->suppliersToCreate)) {
                $this->suppliersToCreate = array_unique($this->suppliersToCreate);
                $this->createSuppliers($this->suppliersToCreate);
            }
            if (!empty($this->productData)) {
                $response = $this->updateProductsSupplier($this->productData);
                return $response;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
    /**
     * @param $args
     *
     * Callback function that collects product information and prepares it to be sent to SkuVault.
     * Faster way to do a foreach loop thru all collection elements.
     */
    public function callbackIterateProductCollection($args)
    {
        $brandName = trim($args['row']['brand_name']);
        $productSku = $args['row']['sku'];
        if (!in_array(strtolower($brandName), $this->skuVaultBrands)) {
            $this->brandsToCreate[] = $brandName;
        }
        $this->productData[$productSku] = $brandName;
    }

    /**
     * @return bool
     */
    public function runAdminBrandSync()
    {
        $success = true;
        $brandsToCreate = [];
        $brands = $this->getBrandCollection();
        if ($brands->getData() || !empty($brands->getData())) {
            foreach ($brands as $brand) {
                $brandName = trim($brand->getName());
                if (!in_array(strtolower($brandName), $this->skuVaultBrands)) {
                    $brandsToCreate[] = $brandName;
                }
            }
            if (!empty($brandsToCreate)) {
                $response = $this->createBrands($brandsToCreate);
                $response = Zend_Http_Response::extractBody($response);
                if (!is_null($response)) {
                    $response = json_decode($response, true);
                    $responseErrors = $response['Errors'];
                    if (!empty($responseErrors)) {
                        $this->handleErrors($responseErrors);
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * This function will update Magento Brands into SkuVault suppliers
     */
    public function runAdminBrandSyncWithSupplier()
    {
        $success = true;
        $suppliersToCreate = [];
        $brands = $this->getBrandCollection();
        if ($brands->getData() || !empty($brands->getData())) {
            foreach ($brands as $brand) {
                $brandName = trim($brand->getName());
                if (!in_array(strtolower($brandName), $this->skuVaultSuppliers)) {
                    $suppliersToCreate[] = $brandName;
                }
            }
            $suppliersToCreate = array_filter($suppliersToCreate);
            $suppliersToCreate = array_unique($suppliersToCreate);
            if (!empty($suppliersToCreate)) {
                $response = $this->createSuppliers($suppliersToCreate);
                $response = Zend_Http_Response::extractBody($response);
                if (!is_null($response)) {
                    $response = json_decode($response, true);
                    $responseErrors = $response['Errors'];
                    if (!empty($responseErrors)) {
                        $this->handleErrors($responseErrors);
                        $success = false;
                    }
                }
            }
        }
        return $success;
    }

    /**
     * @param $allProducts
     * @return object
     */
    protected function getProductCollection($allProducts)
    {
        $collection = Mage::getResourceModel('catalog/product_collection')
                          ->addAttributeToFilter('status', array('eq' => 1));
        // Filter products that are in stock. If some products will become In stock they will be picked-up on next run.
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        if ($allProducts !== true) {
            $currentDate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $previousDate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(strtotime('-1 day')));
            $collection->addAttributeToFilter('updated_at', array(
                'from' => $previousDate,
                'to' => $currentDate,
                'date' => true,
            ));
            // If there are no products for today we can quit.
            if ($collection->getSize() == 0) {
                return null;
            }
        }
        //Find brand name by product id in list of product uds using SQL Regex - [[:<:]]product_id[[:>:]]
        $collection->getSelect()
                   ->joinLeft(
                       array('brand_names' => Mage::getModel('core/resource')->getTableName('brand')),
                       'brand_names.product_ids REGEXP CONCAT(\'[[:<:]]\',e.entity_id,\'[[:>:]]\')',
                       array('brand_name' => 'brand_names.name')
                   )->where('brand_names.name IS NOT NULL');

        return $collection;
    }

    /**
     * @return object
     */
    protected function getBrandCollection()
    {
        /**
         * @var $collection Magestore_Shopbybrand_Model_Mysql4_Brand_Collection
         */
        $collection = Mage::getResourceModel('shopbybrand/brand_collection')->addFieldToFilter('status', '1');

        return $collection;
    }

    /**
     * @return array
     */
    protected function getBrands()
    {
        $brands = [];
        $data = $this->sendRequest(self::GET_BRANDS_URL);
        if (!is_null($data) && Zend_Http_Response::extractCode($data) === 200) {
            $data = Zend_Http_Response::extractBody($data);
            $data = json_decode($data, true);
            foreach ($data['Brands'] as $brand) {
                array_push($brands, strtolower($brand['Name']));
            }
        }

        return $brands;
    }

    /**
     * @return array
     */
    protected function getSuppliers()
    {
        $suppliers = [];
        $data = $this->sendRequest(self::GET_SUPPLIERS_URL);
        if (!is_null($data) && Zend_Http_Response::extractCode($data) === 200) {
            $data = Zend_Http_Response::extractBody($data);
            $data = json_decode($data, true);
            foreach ($data['Suppliers'] as $supplier) {
                array_push($suppliers, strtolower($supplier['Name']));
            }
        }

        return $suppliers;
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
            'TenantToken' => $this->tenantToken,
            'UserToken' => $this->userToken,
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

    /**
     * @param $brands
     * @return null|string
     *
     * Prepares data to sent to the SkuVault.
     * SkuVault API has limit of 100 brands per request, so if more then 100 brands passed, then chunk array of brands.
     */
    protected function createBrands($brands)
    {
        $success = true;
        $recursiveBrandArray = [];

        if (count($brands) >= self::SKU_VAULT_API_LIMIT) {
            $slicedBrands = array_slice($brands, 0, self::SKU_VAULT_API_LIMIT);
            $recursiveBrandArray = array_slice($brands, self::SKU_VAULT_API_LIMIT);
            $brands = $slicedBrands;
        }

        // Collect brands
        $body = ['Brands'];
        foreach ($brands as $brand) {
            $body['Brands'][] = [
                'Name' => $brand,
            ];
        }

        $response = $this->sendRequest(self::CREATE_BRANDS_URL, $body);
        $response = Zend_Http_Response::extractBody($response);
        if (!empty($response)) {
            $response = json_decode($response, true);
            $responseErrors = $response['Errors'];
            if (!empty($responseErrors)) {
                $this->handleErrors($responseErrors);
                $success = false;
            }
        }

        if (!empty($recursiveBrandArray)) {
            // Limitation of SkuVault API. Only 10 requests per minute.
            sleep(self::SKU_VAULT_API_MODERATE_REGUEST_TIMEOUT);
            $this->createBrands($recursiveBrandArray);
        }

        return $success;
    }
    /**
     * @param $suppliers
     * @return null|string
     *
     * Prepares data to sent to the SkuVault.
     * SkuVault API has limit of 100 suppliers per request, so if more then 100 suppliers passed, then chunk array of suppliers.
     */
    protected function createSuppliers($suppliers)
    {
        $success = true;
        $recursiveBrandArray = [];

        if (count($suppliers) > self::SKU_VAULT_API_LIMIT) {
            $slicedBrands = array_slice($suppliers, 0, self::SKU_VAULT_API_LIMIT);
            $recursiveBrandArray = array_slice($suppliers, self::SKU_VAULT_API_LIMIT);
            $suppliers = $slicedBrands;
        }

        // Collect Suppliers
        $body = array();
        foreach ($suppliers as $supplier) {
            $body['Suppliers'][] = [
                'Name' => $supplier,
            ];
        }
        $response = $this->sendRequest(self::CREATE_SUPPLIERS_URL, $body);
        $response = Zend_Http_Response::extractBody($response);
        if (!empty($response)) {
            $response = json_decode($response, true);
            $responseErrors = $response['Errors'];
            if (!empty($responseErrors)) {
                $this->handleErrors($responseErrors);
                $success = false;
            }
        }

        if (!empty($recursiveBrandArray)) {
            // Limitation of SkuVault API. Only 10 requests per minute.
            sleep(self::SKU_VAULT_API_MODERATE_REGUEST_TIMEOUT);
            $this->createBrands($recursiveBrandArray);
        }

        return $success;
    }

    /**
     * @param $productData
     * @return null|string
     */
    protected function updateProducts($productData)
    {
        $recursiveProductArray = [];
        $productCount = count($productData);
        if ($productCount > self::SKU_VAULT_API_LIMIT) {
            $slicedProducts = array_slice($productData, 0, self::SKU_VAULT_API_LIMIT);
            $recursiveProductArray = array_slice($productData, self::SKU_VAULT_API_LIMIT);
            $productData = $slicedProducts;
        }
        $body = ['Items'];
        foreach ($productData as $sku => $brand) {
            $body['Items'][] = [
                'Sku' => $sku,
                'Brand' => $brand,
            ];
            Mage::log('Updated product: ' . $sku . ' with brand: ' . $brand, null, 'skuvault.log');
        }

        $response = $this->sendRequest(self::UPDATE_PRODUCTS_URL, $body);
        if (!is_null($response)) {
            $response = Zend_Http_Response::extractBody($response);
            $response = json_decode($response, true);
            $responseErrors = $response['Errors'];
            if (!empty($responseErrors)) {
                $this->productUpdateWithoutError = false;
                $this->handleErrors($responseErrors);
            }
        }
        $this->productCounter += count($productData);
        echo "Updated $this->productCounter products. $productCount products left\n";
        if (!empty($recursiveProductArray)) {
            // Limitation of SkuVault API. Only 10 requests per minute.
            sleep(self::SKU_VAULT_API_MODERATE_REGUEST_TIMEOUT);
            $this->updateProducts($recursiveProductArray);
        }

        return $this->productUpdateWithoutError;
    }

    /**
     * @param $productData
     * This contain products sku(in keys) and corresponding brand name(in value)
     * @return null|string
     */
    public  function updateProductsSupplier($productData){
        $body = ['Items'];
        foreach ($productData as $sku => $supplier) {
            $supplierInfo = [
                'SupplierName'=>$supplier,
                'isPrimary'=> true
            ];
            $body['Items'][] = [
                'Sku' => $sku,
                'SupplierInfo' => $supplierInfo,
            ];
        }
        $response = $this->sendRequest(self::UPDATE_PRODUCTS_URL, $body);
        if (!is_null($response)) {
            $response = Zend_Http_Response::extractBody($response);
            $response = json_decode($response, true);
            //Mage::log($response, null, 'hassan.log');
            $responseErrors = $response['Errors'];
            if (!empty($responseErrors)) {
                $this->productUpdateWithoutError = false;
                $skusWithErrors = $this->handleErrors($responseErrors);
            }
            if($response['Status'] == "OK"){ // 200 OK: This status is returned when the entire payload is OK.
                $flag = 1;
                Mage::helper("progos_skuvault")->updatedSkuvaultProductCollection(array_keys($productData),$flag);
            }
            elseif($response['Status'] == "Accepted" && !empty($skusWithErrors)){ //202 Accepted: This status is returned when part of a bulk call returns an error, but other elements of the payload are OK and have been accepted by SkuVault.
                $skusUpdated = array_diff(array_keys($productData),$skusWithErrors);
                $flag = 1;
                Mage::helper("progos_skuvault")->updatedSkuvaultProductCollection($skusUpdated,$flag);
            }
        }
        return $this->productUpdateWithoutError;
    }

    /**
     * @param $errors
     * @return Its returing sku's array which are not updated
     * General method to handle SkuVault errors.
     */
    protected function handleErrors($errors)
    {
        $skusWithErrors = array();
        foreach ($errors as $error) {
            if (is_string($error)) {
                $errMessages = $error;
            } elseif (is_array($error['ErrorMessages']) && count($error['ErrorMessages']) > 1) {
                $errMessages = '';
                foreach ($error['ErrorMessages'] as $key => $errMsg) {
                    $errMessages .= $key . ': ' . $errMsg;
                }
            } else {
                $errMessages = $error['ErrorMessages'][0];
            }
            if(isset($error['Sku'])) {
                $skusWithErrors[]=$error['Sku'];
                $errMessages .= "(".$error['Sku'].")";
            }
            Mage::log($errMessages, null, 'skuvault.log');
        }
        return $skusWithErrors;
    }
}
<?php

/**
 * Progos_Skuvault.
 *
 * @category Elabelz
 *
 * @Author Saroop Chand
 * @Reauthored RT (08/12/17)
 * @Date 15-11-2017
 *
 */
class Progos_Skuvault_Model_ProductQtySync_Cron
{
    const GET_WAREHOUSE_ITEMS_QTY_URL  = 'https://app.skuvault.com/api/inventory/getWarehouseItemQuantities';
    const GET_EXTERNAL_WAREHOUSE_ITEMS_QTY_URL  = 'https://app.skuvault.com/api/inventory/getExternalWarehouseQuantities';
    const API_ALL_SKU_QTY = 'https://app.skuvault.com/api/inventory/getAvailableQuantities';
    const API_INVENTORY_LOC = 'https://app.skuvault.com/api/inventory/getInventoryByLocation';
    //get warehouse by sku
    /**
     * @var Zend_Http_Client
     */
    protected $curl = null;
    protected $Skuvaulthelper;
    protected $mageSkus = [];
    protected $from = null;
    protected $to = null;
    public function __construct(){
        Mage::init();
        $this->curl = new Zend_Http_Client();
        $this->Skuvaulthelper = Mage::helper('progos_skuvault');
    }

    public function syncQtySkuBased(){
        ini_set('memory_limit', '-1');
        $skus   =   rtrim(Mage::getStoreConfig('sku_vault_general/skuvault_statuses/syncqtysku'),',');
        if( !empty($skus) ){
            $skus   =   explode(',',$skus);
            $body['ProductSKUs'] = $skus;
            $body['PageNumber']                 =   $this->Skuvaulthelper->getSkuvaultItemPageStart() ;
            $body['PageSize']                   =   $this->Skuvaulthelper->getSkuvaultItemQtyPerPage() ;
            $response                           =   $this->sendRequest(self::API_INVENTORY_LOC, $body);
            $response                           =   Zend_Http_Response::extractBody($response);
            $response                           =   json_decode($response, true);
            if( $response['Items'] ){
                $productSkus = $this->getItemQtyBasedSku( $response['Items'] );
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                $this->updateProductsWarehouse( $productSkus );
                return " Success.";
            }else{
                return " Skuvalut Have no item.";
            }
        }else{
            return "Please provide Skus";
        }
    }

    public function getItemQtyBasedSku($products){
        $wareHouseCode = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/warehouse_code');
        $result =array();
        $prod = array();
        $prodSkuQty = array();
        foreach( $products as $k => $multiWarehouseProd ){
            $qty = 0;
            if( !empty( $multiWarehouseProd ) ){
                foreach($multiWarehouseProd as $product ){
                    if( $product['WarehouseCode'] ==  $wareHouseCode ){
                        $qty += $product['Quantity'];
                    }
                }
                $prod[] =  $k;
                $prodSkuQty[$k] = $qty;
            }else{
                $prod[] =  $k;
                $prodSkuQty[$k] = $qty;
            }
        }
        $result['prod'] = $prod;
        $result['prodSkuQty'] = $prodSkuQty;
        return $result;
    }

    public function syncMageSkus(){
        ini_set('memory_limit', '-1');
        if( !empty( $this->mageSkus ) ){
            $body['ProductSKUs']                =   array_values($this->mageSkus);
            $body['PageNumber']                 =   $this->Skuvaulthelper->getSkuvaultItemPageStart() ;
            $body['PageSize']                   =   $this->Skuvaulthelper->getSkuvaultItemQtyPerPage() ;
            $response                           =   $this->sendRequest(self::API_INVENTORY_LOC, $body);
            $response                           =   Zend_Http_Response::extractBody($response);
            $response                           =   json_decode($response, true);
            if( $response['Items'] ){
                $productSkus = $this->getItemQtyBasedSku( $response['Items'] );
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                $this->updateProductsWarehouse( $productSkus );
                echo "Product Modified under date => From:".$this->from."  To:".$this->to." \n";
            }else{
                echo "No Item available on skuvault site:".$this->from."  To:".$this->to." \n";
            }
        }else{
            echo "Product Not Available under Modified date => From:".$this->from."  To:".$this->to." \n";
        }
        return;
    }

    public function getMagentoProductSkus(){
        $inventryType = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/inventry_type');
        $inventryType = rtrim($inventryType,',');
        $inventryTypeId = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/inventry_type_attriubte_id');
        $inventryTypeId = rtrim($inventryTypeId,',');

        $query = "SELECT e.entity_id,e.sku,e.created_at,e.updated_at,ei.attribute_id,ei.value FROM catalog_product_entity as e
                    INNER JOIN catalog_product_entity_int AS ei
                    ON (ei.entity_id = e.entity_id)
                    AND (ei.attribute_id = '".$inventryTypeId."')
                    AND ei.store_id = 0  AND ei.entity_type_id = '4'
                    WHERE (e.updated_at BETWEEN '".$this->from."' AND '".$this->to."') 
                    AND e.type_id = 'simple' AND ei.value IN ($inventryType)
                    order by e.updated_at desc;
                    ";
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $results = $connection->fetchAll($query);
        foreach( $results as $result ){
            $this->mageSkus[] = $result['sku'];
        }
        return;
    }
    public function syncQtyApiBased( $timeStatus = false ){
        ini_set('memory_limit', '-1');
        $lastExecutionTimeDate              =   Mage::getStoreConfig('sku_vault_general/skuvault_statuses/last_script_execution_time');
        $lastExecutionTimeDateTo              =   Mage::getStoreConfig('sku_vault_general/skuvault_statuses/last_script_execution_time_to');
        //If Qty synced with time defined in Settings.
        $currentTimeZone    =   date_default_timezone_get();
        $cTime              =   time();

        if( $timeStatus == false ) {
            $modifiedAfterDateTimeUtc = explode( 'T',$lastExecutionTimeDate);
            $tmp = $modifiedAfterDateTimeUtc[0];
            $modifiedAfterDateTimeUtc = explode( '.',$modifiedAfterDateTimeUtc[1]);
            $fromChnage = $tmp." ".$modifiedAfterDateTimeUtc[0];
            $this->from = date( "Y-m-d H:i:s" ,strtotime($fromChnage) );
            $body['ModifiedAfterDateTimeUtc'] = date("Y-m-d", strtotime($fromChnage) ).'T'.date("H:i:s", strtotime($fromChnage)).'.0000000Z';

            if (!empty($lastExecutionTimeDateTo)) {
                $modifiedBeforeDateTimeUtc = explode( 'T',$lastExecutionTimeDateTo);
                $tmp = $modifiedBeforeDateTimeUtc[0];
                $modifiedBeforeDateTimeUtc = explode( '.',$modifiedBeforeDateTimeUtc[1]);
                $fromChnage = $tmp." ".$modifiedBeforeDateTimeUtc[0];
                $this->to = date( "Y-m-d H:i:s" ,strtotime($fromChnage) );
                $body['ModifiedBeforeDateTimeUtc'] = $lastExecutionTimeDateTo;
            } else {
                $this->to = date("Y-m-d H:i:s", $cTime);
                $body['ModifiedBeforeDateTimeUtc'] = date('Y-m-d',$cTime) . 'T' . date('H:i:s',$cTime) . '.0000000Z';
                if( $currentTimeZone != 'UTC' ) {
                    date_default_timezone_set("UTC");
                    $utccTime              =   time();
                    $body['ModifiedBeforeDateTimeUtc']  = date("Y-m-d", $utccTime).'T'.date("H:i:s", $utccTime).'.0000000Z';
                    $body['ModifiedAfterDateTimeUtc']   = date("Y-m-d", strtotime($fromChnage)).'T'.date("H:i:s", strtotime($fromChnage)).'.0000000Z';
                    date_default_timezone_set($currentTimeZone);
                }
            }
        }else{ /*If Qty synced with last two days cron.*/
            $body['ModifiedAfterDateTimeUtc']   = date("Y-m-d", strtotime('-2 days', $cTime)).'T'.date("H:i:s", $cTime).'.0000000Z';
            $body['ModifiedBeforeDateTimeUtc']  = date("Y-m-d", $cTime).'T'.date("H:i:s", $cTime).'.0000000Z';
            $this->from = date( "Y-m-d H:i:s" ,strtotime('-2 days', $cTime) );
            $this->to = date( "Y-m-d H:i:s" ,$cTime );
            if( $currentTimeZone != 'UTC' ) {
                date_default_timezone_set("UTC");
                $utccTime              =   time();
                $body['ModifiedAfterDateTimeUtc']   = date("Y-m-d", strtotime('-2 days', $utccTime)).'T'.date("H:i:s", $utccTime).'.0000000Z';
                $body['ModifiedBeforeDateTimeUtc']  = date("Y-m-d", $utccTime).'T'.date("H:i:s", $utccTime).'.0000000Z';
                date_default_timezone_set($currentTimeZone);
            }
        }
        $mageItemIncludeStatus = $this->getMageItemInclude();
        try {
            if ($mageItemIncludeStatus) {
                $this->getMagentoProductSkus();
            }
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'skuvault.log');
        }
        $body['PageNumber']                 =   $this->Skuvaulthelper->getSkuvaultItemPageStart() ;
        $body['PageSize']                   =   $this->Skuvaulthelper->getSkuvaultItemQtyPerPage() ;
        $response                           =   $this->sendRequest(self::API_ALL_SKU_QTY, $body);
        $response                           =   Zend_Http_Response::extractBody($response);
        $response                           =   json_decode($response, true);
        if( $response['Items'] ){
            $productSkus = $this->getAllSku( $response['Items'] );
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $this->updateProductsWarehouse( $productSkus );
            if( count( $response['Items'] ) == 5000 ){
                $this->repeatIteration( $body , 0 );
            }

            if ( $this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty() ) {
                Mage::getConfig()->saveConfig('sku_vault_general/skuvault_statuses/last_script_execution_time', $this->to );
            }
            try {
                if ($mageItemIncludeStatus) {
                    $this->syncMageSkus();
                }
            }catch(Exception $e){
                Mage::log($e->getMessage(), null, 'skuvault.log');
            }

            return " Changes occoured between last script run time : ".$body['ModifiedAfterDateTimeUtc']." AND current Execution time.".$body['ModifiedBeforeDateTimeUtc'];
        }else{
            Mage::getConfig()->saveConfig( 'sku_vault_general/skuvault_statuses/last_script_execution_time' , $body['ModifiedBeforeDateTimeUtc'] );
            return " No Changes occoured between last script run time : ".$body['ModifiedAfterDateTimeUtc']." AND current Execution time.".$body['ModifiedBeforeDateTimeUtc'];
        }
    }

    public function repeatIteration( $body , $lastcount ){
        $lastcount++;
        $body['PageNumber']                 =   $lastcount;
        $response                           =   $this->sendRequest(self::API_ALL_SKU_QTY, $body);
        $response                           =   Zend_Http_Response::extractBody($response);
        $response                           =   json_decode($response, true);
        if( $response['Items'] ){
            $productSkus = $this->getAllSku( $response['Items'] );
            $this->updateProductsWarehouse( $productSkus );
            if( count( $response['Items']  ) == 5000 ){
                sleep(30);
                $this->repeatIteration( $body , $lastcount );
            }else{
                return true;
            }
        }
        return true;
    }

    public function updateProductsWarehouse( $productSkus ){
        $inventryType = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/inventry_type');
        $inventryType = explode(',', rtrim($inventryType,','));

        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('visibility')
            ->addAttributeToFilter('inventory_type', array('in' => $inventryType))
            ->addAttributeToFilter('type_id', array('eq' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE))
            ->addAttributeToFilter('sku', array('in' => $productSkus['prod']));

        foreach( $collection as $product ){
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $mageItemQty = (int)$stock->getQty();
            $skuvalutQty = $productSkus['prodSkuQty'][$product->getSku()];
            $skuvalutRes = ['warehouse_qty' => $skuvalutQty, 'total_qty' => $skuvalutQty, 'product_qty' => $mageItemQty];
            //update magento product, stock by api response
            $this->updateBySkuvaultApi($product, $stock, $skuvalutRes);

            if (($key = array_search( $product->getSku() , $this->mageSkus )) !== false) {
                unset($this->mageSkus[$key]);
            }
        }
    }

    public function updateBySkuvaultApi($product, $stock, $skuvaultRes) {
        if ($skuvaultRes['warehouse_qty'] == 0 ) {
            if ($stock->getIsInStock()) {
                if (!$this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty()) {
                    Mage::log('To be Out of stock in Magento ===== current_magento_qty --> ' . $stock->getQty() . ' magento_product_sku --> ' . $product->getSku(), null, 'oos_to_magento.log');
                } else {
                    //prevent already out of stock product from stock update as skuvault has 0 stock
                    Mage::log('Before Done Out of stock in Magento===== current_magento_qty --> ' . $stock->getQty() . ' magento_product_sku --> ' . $product->getSku(), null, 'action_oos_to_magento.log');
                    $this->updateapi($product, 0, $stock);
                }
            }
        } else {
            //get on hold orders from magento
            $onHoldOrderTotal = (int)$this->getOrderStatusSumApi($product->getId());
            $skuvaultQty = $skuvaultRes['warehouse_qty'];
            $tempLog = '  skuvault_qty --> '.$skuvaultQty.'  onhold_qty --> '.$onHoldOrderTotal.'  ';
            //get total inventory after subtracting magento on hold orders from skuvault
            $tQty = (int)$skuvaultQty - $onHoldOrderTotal;
            //if qty > 0, update stock with the total inventory $tQty
            if ($tQty > 0) {
                //If Qty greater then Zero and skuvault and magento not equal then update
                if ($tQty != (int)$stock->getQty()) {
                    //if dry run enabled
                    if (!$this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty()) {
                        Mage::log('To update stock in Magento ===== current_magento_qty --> ' . $stock->getQty() . ' update_into_magento_qty --> ' . $tQty .$tempLog.' magento_product_sku --> ' . $product->getSku(), null, 'update_qty_skuvault_to_magento.log');
                    } else {
                        Mage::log('Before increase stock in Magento ===== current_magento_qty: ' . $stock->getQty() . ' update_into_magento_qty --> ' . $tQty .$tempLog.' magento_product_sku --> ' . $product->getSku(), null, 'action_update_qty_skuvault_to_magento.log');
                        $this->updateapi($product, $tQty, $stock);
                    }
                }
                //if $tQty <= 0 update product inventory to out of stock in magento
            } elseif ($tQty <= 0) {
                //prevent already out of stock product from stock update as skuvault has 0 stock
                if ($stock->getIsInStock()) {
                    //if dry run enabled
                    if (!$this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty()) {
                        Mage::log('To update out of stock in Magento ===== current_magento_qty --> ' . $stock->getQty() .$tempLog. ' magento_product_sku --> ' . $product->getSku(), null, 'oos_skuvault_to_magento.log');
                    } else {
                        Mage::log('Before update out of stock in Magento===== current_magento_qty --> ' . $stock->getQty() .$tempLog. ' magento_product_sku --> ' . $product->getSku(), null, 'action_oos_skuvault_to_magento.log');
                        $this->updateapi($product, 0, $stock);
                    }
                }
            }
        }
    }

    public function updateapi($product, $qtyUpdate, $stock){
        $productId = $product->getId();
        if( $qtyUpdate <= 0 ){
            $stock->setData('qty' , 0 );
            $stock->setData('is_in_stock',0);
            $stock->save();
        }else{
            $stock->setData('qty' , $qtyUpdate);
            $stock->setData('is_in_stock',1);
            $stock->save();
            // Enable product set admin store
            $product->setStatus('1');
            $product->save();
            //check parent product status and other child status
            //if any child has stock and parent product status if oos
            //update parent product status to in stock
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            //we can apply loop if product has more than one parent products
            $parentProdId = $parentIds[0];
            $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
            if ($parentProduct) {
                //update stock for parent product
                $parentProductstock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parentProduct);
                //only if product is out stock than update
                if (!$parentProductstock->getIsInStock()) {
                    $parentProductstock->setData('is_in_stock', 1);
                    $parentProductstock->save();
                }
                //update product status if not disabled
                if ($parentProduct->getStatus() == '2') {
                    $parentProduct->setStatus('1')
                        ->save();
                }
                $this->addProductReindex($parentProdId);
            }
        }
        $this->addProductReindex($productId);
        return true;
    }

    public function getOrderStatusSumApi($productId){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $itemStatusIn = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/item_include');
        $itemStatusEx = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/item_exclude');
        $qitemStatusIn = "'" . str_replace(",", "','", $itemStatusIn) . "'";
        $qitemStatusEx = "'" . str_replace(",", "','", $itemStatusEx) . "'";

        $sql = "SELECT sum(product_qty) as total_qty 
        FROM marketplace_commission 
        where product_id = {$productId}
        and item_order_status IN ({$qitemStatusIn})";
        if ($qitemStatusEx) {
            $sql .= " and item_order_status NOT IN ({$qitemStatusEx})";
        }
        $commissionData = $connection->fetchAll($sql);
        return $commissionData[0]['total_qty'];
    }

    public function getAllSku( $products ){
        $result =array();
        $prod = array();
        $prodSkuQty = array();
        foreach( $products as $product ){
            $prod[] = $product['Sku'];
            $prodSkuQty[$product['Sku']] = $product['AvailableQuantity'];
        }
        $result['prod'] = $prod;
        $result['prodSkuQty'] = $prodSkuQty;
        return $result;
    }

    public function skuvaultProductQtySyncWithMagento(){
        $updateType = Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_update_type');
        if ($updateType == 'disable') {
            return;
        } elseif ($updateType == 'csv') {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $this->getSkuvaultDataFromCsv();
            return;
        } elseif ($updateType == 'api') {
            $this->productData = [];
            try {
                $this->syncQtyApiBased();
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'skuvault.log');
                return;
            }
        }
        Mage::log('something went wrong', null, 'skuvault.log');
        return;
    }

    public function recursiveService( $response , $pageNumber , $qty ){
        if(is_array($response)){
            foreach($response as $item){ 
                //get product if in warehouse and is not configurable
                $product = $this->getItemInWarehouse($item['Sku']);
                if (!$product) {
                    continue;
                } else {
                    //product exists in warehouse and is not configurable
                    $this->productInventory($product, (int)$item['AvailableQuantity']);
                }
            }
            if(count($response) == $this->Skuvaulthelper->getSkuvaultItemQtyPerPage()){
                $pageNumber++;
                $body['PageNumber'] = $pageNumber ;
                $body['PageSize'] = $this->Skuvaulthelper->getSkuvaultItemQtyPerPage() ;
                $response = $this->sendRequest(self::API_ALL_SKU_QTY, $body);
                $response = Zend_Http_Response::extractBody($response);
                $response = json_decode($response, true);
                $this->recursiveService( $response['Items'] ,  $pageNumber , $qty );
            }
            return true;
        }
        Mage::log('response should be an array', null, 'skuvault.log');
        return;
    }
    /**
    * get product from ware house if not configurable and is 
    * in warehouse
    * @param $sku product sku from warehouse
    * @return mix product if true else bool false
    **/
    public function getItemInWarehouse($sku) {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if (!$product) {
            //this product does not exist in magento
            Mage::log('product does not exists in magento', null, 'skuvault.log');
            return false;
        } elseif ($product->getTypeId() == 'simple') { 
            $wareHouseCode = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/warehouse_code');
            $body['ProductSKUs'] = [$sku];
            $response = $this->sendRequest(self::API_INVENTORY_LOC, $body);
            $response = Zend_Http_Response::extractBody($response);
            $response = json_decode($response, true);
            if (empty($response['Errors']) && 
                in_array($wareHouseCode, array_column($response['Items'][$sku], 'WarehouseCode'))) {
                return $product;
            } else {
                Mage::log("product {$sku} does not exist in warehouse", null, 'skuvault.log');
                return false;
            }
        } else {
            Mage::log("{$sku} product type is not simple", null, 'skuvault.log');
            return false;
        }
        Mage::log('unexpected error', null, 'skuvault.log');
        return false;
    }

    public function productInventory($_product, $skuvalutQty){
        $inventryType = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/inventry_type');
        if($_product){
            $productId = $_product->getId();
            if($_product->getInventoryType() != $inventryType) {
               return true;
            }
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
            $mageItemQty = (int)$stock->getQty();
            $skuvalutRes = ['warehouse_qty' => $skuvalutQty, 'total_qty' => $skuvalutQty, 'product_qty' => $mageItemQty];
            //update magento product, stock by api response
            $this->updateBySkuvault($_product, $stock, $skuvalutRes);
        }
        return true;
    }

    public function getSkuvaultDataFromCsv() {
        //read file using magento lib
        $data = $this->readSkuvaultCsv();
        foreach($data as $key=>$row){
            //skip column names
            if ($key == 0) {
                continue;
            } 
            //change string to number
            $salePrice =  filter_var($row[6], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            //skip configurable products based on sale price > 0 as only configurable product can have price
            if ($salePrice > 0) {
                continue;
            }
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $row['0']);
            //product object could be false
            if (!$product) {
                continue;
            }                

            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            //total qty $row[16] on skuvault, warehouse qty = $row[7] is 0 for product
            $skuvaultRes = ['warehouse_qty' => $row[7], 'total_qty' => $row[16]];
            $this->updateBySkuvault($product, $stock, $skuvaultRes);
        }
    }

    public function updateBySkuvault($product, $stock, $skuvaultRes) {
        $inventryType = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/inventry_type');
        if ($skuvaultRes['warehouse_qty'] == 0 && $skuvaultRes['total_qty'] == 0) {
            //only for simple product
            if ($product->getTypeId() == 'simple') { 
                //if dry run enabled
                if($product->getInventoryType() == $inventryType) {
                    if ($stock->getIsInStock()) {
                        if (!$this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty()) {
                            Mage::log('To be Out of stock in Magento ===== current_magento_qty --> ' . $stock->getQty() . ' magento_product_sku --> ' . $product->getSku(), null, 'oos_to_magento.log');
                        } else {
                            //prevent already out of stock product from stock update as skuvault has 0 stock
                            Mage::log('Before Done Out of stock in Magento===== current_magento_qty --> ' . $stock->getQty() . ' magento_product_sku --> ' . $product->getSku(), null, 'action_oos_to_magento.log');
                            $this->update($product, 0, $stock);
                            
                        }
                    }
                }
            }
        } else {
            $orderStatusIn = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/order_include');
            $orderStatusEx = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/order_exclude');
            $qOrderStatusIn = "'" . str_replace(",", "','", $orderStatusIn) . "'";
            $qOrderStatusEx = "'" . str_replace(",", "','", $orderStatusEx) . "'";
            //get on hold orders from magento
            $onHoldOrderTotal = (int)$this->getOrderStatusSum($product->getId(), $qOrderStatusIn, $qOrderStatusEx);
            $skuvaultQty = $skuvaultRes['warehouse_qty'];
            //get total inventory after subtracting magento on hold orders from skuvault
            $tQty = (int)$skuvaultQty - $onHoldOrderTotal;
            //apply for simple products
            if ($product->getTypeId() == 'simple') { 
                //inventory type should be "IN House"
                if($product->getInventoryType() == $inventryType) {
                //if qty > 0, update stock with the total inventory $tQty
                    if ($tQty > 0) {
                        //If Qty greater then Zero and skuvault and magento not equal then update
                        if ($tQty != (int)$stock->getQty()) { 
                            //if dry run enabled
                            if (!$this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty()) {
                                Mage::log('To update stock in Magento ===== current_magento_qty --> ' . $stock->getQty() . ' update_into_magento_qty --> ' . $tQty . ' magento_product_sku --> ' . $product->getSku(), null, 'update_qty_skuvault_to_magento.log');
                            } else {
                                Mage::log('Before increase stock in Magento ===== current_magento_qty: ' . $stock->getQty() . ' update_into_magento_qty --> ' . $tQty . ' magento_product_sku --> ' . $product->getSku(), null, 'action_update_qty_skuvault_to_magento.log');
                                $this->update($product, $tQty, $stock);
                            }
                        }
                        //if $tQty <= 0 update product inventory to out of stock in magento
                    } elseif ($tQty <= 0) {
                        //prevent already out of stock product from stock update as skuvault has 0 stock
                        if ($stock->getIsInStock()) {
                            //if dry run enabled
                            if (!$this->Skuvaulthelper->getSkuvaultskuvaultSaveSyncQty()) {
                                Mage::log('To update out of stock in Magento ===== current_magento_qty --> ' . $stock->getQty() . ' magento_product_sku --> ' . $product->getSku(), null, 'oos_skuvault_to_magento.log');
                            } else {
                                Mage::log('Before update out of stock in Magento===== current_magento_qty --> ' . $stock->getQty() . ' magento_product_sku --> ' . $product->getSku(), null, 'action_oos_skuvault_to_magento.log');
                                $this->update($product, 0, $stock);
                                
                            }
                        }
                    }
                } else {
                    Mage::log("Product {$product->getSku()} Inventory type did not match", null, 'skuvault.log');
                }
            }
        }
    }

    public function readSkuvaultCsv() {
        $file = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/file_name');
        if($file) {
            $absPath = Mage::getBaseDir('var') . DS . 'import';
            if (!file_exists($absPath . DS . $file)) {
                return;
            }
            $csv = new Varien_File_Csv();
            return $data = $csv->getData($absPath . '/' . $file);
        } else {
            Mage::log('File does not exist', null, 'skuvault.log');
            die('file does not exist');
        }
    }
    
    public function getOrderStatusSum($productId, $qOrderStatusIn,$qOrderStatusEx = null){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sqlOrderRes = [];
        $sqlOrder = "SELECT sfoi.order_id, sfoi.product_id, sfo.status 
                    FROM sales_flat_order_item  AS sfoi 
                    INNER JOIN sales_flat_order AS sfo ON sfoi.order_id=sfo.entity_id 
                    WHERE sfoi.product_id={$productId}";
        if ($qOrderStatusIn != null) {             
            $sqlOrder .= " AND sfo.status IN({$qOrderStatusIn})";
        }
        if ($qOrderStatusEx != null) {             
            $sqlOrder .= " AND sfo.status NOT IN ({$qOrderStatusEx});";
        }
        //get results on query
        $sqlOrderRes = $connection->fetchAll($sqlOrder);
        $orderIds= [];
        foreach($sqlOrderRes as $id){
            array_push($orderIds, $id['order_id']);
        }    
        $orderIds = implode(',', $orderIds);

        $itemStatusIn = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/item_include');
        $itemStatusEx = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/item_exclude');
        $qitemStatusIn = "'" . str_replace(",", "','", $itemStatusIn) . "'";
        $qitemStatusEx = "'" . str_replace(",", "','", $itemStatusEx) . "'";

        $sql = "SELECT sum(product_qty) as total_qty 
        FROM marketplace_commission 
        where product_id = {$productId}
        and item_order_status IN ({$qitemStatusIn})";
        if ($qitemStatusEx) {
            $sql .= " and item_order_status NOT IN ({$qitemStatusEx})";
        }
        if (!empty($orderIds)) {
            $sql .= " and order_id IN ({$orderIds})";
        }
        $commissionData = $connection->fetchAll($sql);
        return $commissionData[0]['total_qty'];
    }
    
    public function addProductReindex( $productId ){
        $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "INSERT INTO {$catalogProductPartialindex} (product_id)
                    SELECT * FROM (SELECT '{$productId}') AS tmp
                    WHERE NOT EXISTS (
                            SELECT product_id FROM {$catalogProductPartialindex} WHERE product_id = '{$productId}'
                    ) LIMIT 1;";
        $write->query($sql);
        return;
    }
    
    public function update($product, $qtyUpdate, $stock){
        $productId = $product->getId();
        if( $qtyUpdate <= 0 ){
            $stock->setData('qty' , 0 );
            $stock->setData('is_in_stock',0);
            $stock->save();
            $this->addProductReindex($productId);
        }else{
            $stock->setData('qty' , $qtyUpdate);
            $stock->setData('is_in_stock',1);
            $stock->save();
            // Enable product set admin store
            $product->setStatus('1');
            $product->save();

            //check parent product status and other child status
            //if any child has stock and parent product status if oos
            //update parent product status to in stock
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            //we can apply loop if product has more than one parent products
            $parentProdId = $parentIds[0];
            $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
            if ($parentProduct) {
                //update stock for parent product
                $parentProductstock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parentProduct);
                //only if product is out stock than update
                if (!$parentProductstock->getIsInStock()) {
                    $parentProductstock->setData('is_in_stock', 1);
                    $parentProductstock->save();
                }
                //update product status if not disabled
                if ($parentProduct->getStatus() == '2') {
                    $parentProduct->setStatus('1')
                        ->save();
                }
                $this->addProductReindex($parentProdId);
            }
            $this->addProductReindex($productId);
        }
        return true;
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

    public function getMageItemInclude(){
        $status = Mage::getStoreConfig('sku_vault_general/skuvault_statuses/include_mage_items');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}
<?php
class Progos_Syncproduct_Model_Syncproduct extends Mage_Core_Model_Abstract
{
    protected $config;
    protected $curl = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init('progos_syncproduct/syncproduct');
        $this->curl = new Zend_Http_Client();
        $this->config = Mage::getSingleton('progos_syncproduct/config');
    }

    public function getConfig(){
        return $this->config;
    }

    public function authenticate( $params ){
        $proxy  = new SoapClient($params->soapclient);
        $result = array();
        try {
            $session = $proxy->login(array(
                'username'  => $params->username,
                'apiKey'    => $params->apikey
            ));
            if( $session->result )
                $result['status']   =   true;
        }catch(SoapFault $fault){
            $result['error']        =   $fault->faultstring;
            $result['status']       =   false;
        }
        return $result;
    }

    /*
     *  Get Sku From Sync Product Table
     * */
    public function getSkus(){
        $skus = Mage::getModel('progos_syncproduct/syncproduct')->getCollection();
        $skus->addFieldToFilter('status',array('eq'=>'1'));
        return $skus->getData();
    }

    public function updateStatus( $params ){
        $resutl = $params->result;
        if( !empty( $resutl->complete ) ){
            $skusForDelete = Mage::getModel('progos_syncproduct/syncproduct')->getCollection();
            $skusForDelete->addFieldToFilter('sku',array('in'=>$resutl->complete));
            if( !empty( $skusForDelete->getData() ) ){
                foreach( $skusForDelete->getData() as $skuD ){
                    $syncproduct = Mage::getModel('progos_syncproduct/syncproduct');
                    $syncproduct->setId($skuD['syncproduct_id'])
                    ->delete();
                }
            }
        }

        if( !empty( $resutl->fail ) ){
            $skusFail = Mage::getModel('progos_syncproduct/syncproduct')->getCollection();
            $skusFail->addFieldToFilter('sku',array('in'=>$resutl->fail));
            if( !empty( $skusFail->getData() ) ){
                foreach( $skusFail->getData() as $skuF ){
                    $syncproduct = Mage::getSingleton('progos_syncproduct/syncproduct')
                        ->load($skuF['syncproduct_id'])
                        ->setStatus(3)
                        ->save();
                }
            }
        }
        return true;
    }

    public function updateStatusCall($result){
        if( $this->config->getStatus() ) {
            $url = $this->config->getUrl();
            $response = null;
            $requestBody = array(
                "username" => $this->config->getUsername(),
                "apikey" => $this->config->getApiKey(),
                "soapclient" => $this->config->getSoapUrl(),
                "request" => 'update',
                'result'    => $result
            );
            $requestBody = json_encode($requestBody);
            try {
                // Such big timeout is needed because SkuVault updateProducts request is taking a lot of time.
                $this->curl->setConfig(array('timeout' => $this->config->getTimeOut()));
                $this->curl->setUri($url);
                $this->curl->setRawData($requestBody, 'application/json');
                $response = $this->curl->request(Zend_Http_Client::POST);
                $body = $response->getBody();
            } catch (Exception $e) {
                Mage::log("Authuntication Fail. " . $e->getMessage(), null, 'syncproduct.log');
                return array();
            }
            return $response = json_decode($body);
        }
        return array();
    }

    public function prepareProducts(){
        $skus = $this->getSkus();
        $data = array();
        if( !empty( $skus ) ){
            /* Get All Site Active Store */
            $stores = Mage::app()->getStores();
            $activeStore = array();
            foreach($stores as $key => $store){
                $activeStore[] = array( 'id'=>$key,'code'=>Mage::app()->getStore($key)->getCode());
            }
            /* Get All Site Active Store */
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            foreach( $skus as $skuDetail ){
                $sku = $skuDetail['sku'];
                $skuObje = Mage::getModel("catalog/product")->loadByAttribute('sku',$sku);
                if( $skuObje ){
                    $id = $skuObje->getId();
                    //Mage::app()->setCurrentStore( $adminStore );
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($adminStore);

                    $product = Mage::getModel("catalog/product")->load($id)->setStoreId( $adminStore );
                    if( $product ){
                        if( $product->getTypeId() == "simple" ){
                            // seller_product_status , websites , config_attributes , image , small_image , thumbnail , gallery , tier_prices , associated ,
                            $data['simple'][$sku]       = $this->createProductArray( $product );
                        }else if( $product->getTypeId() == "configurable" ){
                            $data['configurable'][$sku] = $this->createProductArray( $product );
                            $childProducts = Mage::getModel('catalog/product_type_configurable')
                                ->getUsedProducts(null,$product);
                            $associatedArray = array();
                            foreach($childProducts as $child) {
                                $associatedArray[$child->getSku()] = $child->getId();
                            }
                            $data['configurable'][$sku]['associated_products']  = $associatedArray;
                            $data['configurable'][$sku]['storebased']           = $this->setStoreBasedData($data['configurable'][$sku],$sku , $activeStore , $id );
                        }else{
                            $data['product_not_exist'][] = $sku;
                        }
                    }else{
                        $data['product_not_exist'][] = $sku;
                    }
                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                }else{
                    $data['product_not_exist'][] = $sku ;
                }
            }
        }
        return serialize($data);
    }

    public function setStoreBasedData( $object , $sku , $stores , $id ){
        $storeData = array();
        foreach( $stores as $store ){
            //Mage::app()->setCurrentStore( $store['id'] );
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store['id']);
            $product = Mage::getModel("catalog/product")->load($id)->setStoreId( $store['id'] );
            if( $object['name'] != $product->getName() )
                $storeData[$store['code']]['name'] = $product->getName();
            if( $object['description'] != $product->getDescription() )
                $storeData[$store['code']]['description'] = $product->getDescription();
            if( $object['short_description'] != $product->getShortDescription() )
                $storeData[$store['code']]['short_description'] = $product->getShortDescription();
            if( $object['inventory']['product_name'] != $product->getStockItem()->getProductName() )
                $storeData[$store['code']]['product_name'] = $product->getStockItem()->getProductName();
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
        return $storeData;
    }

    public function createProductArray( $product ){
        $result = array();
        $stock = $product->getStockItem();
        $result['store']          = $product->getStoreId();     $result['attribute_set']            = $product->getAttributeSetId();
        $result['type_id']        = $product->getTypeId();      $result['category_ids']             = $product->getCategoryIds();
        $result['sku']            = $product->getSku();         $result['has_options']              = $product->getHasOptions();
        $result['name']           = $product->getName();        $result['seller_shipping_option']   = $product->getSellerShippingOption();
        $result['price']          = $product->getPrice();       $result['manufacturer']             = $product->getAttributeText('manufacturer');
        $result['color']          = $product->getAttributeText('color');    $result['size']         = $product->getAttributeText('size');
        $result['status']         = $product->getStatus();      $result['visibility']               = $product->getVisibility();
        $result['tax_class_id']   = $product->getTaxClassId();  $result['description']              = $product->getDescription();
        $result['short_description'] = $product->getShortDescription();     $result['seller_id']    = $product->getSellerId();
        $result['group_id']       = $product->getGroupId();     $result['inventory']['qty']         = $stock->getQty();
        $result['inventory']['is_in_stock']    = $stock->getIsInStock();    $result['inventory']['manage_stock']    = $stock->getManageStock();
        $result['inventory']['product_name']   = $stock->getProductName();  $result['inventory']['product_type_id'] = $stock->getProductTypeId();
        $result['weight'] = $product->getWeight();              $result['material']                 = $product->getMaterial();

        $image = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        if ( false!==file($image) )
            $result['image'] = $image;
        $smallimage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
        if ( false!==file($smallimage) )
            $result['small_image'] = $smallimage;
        $thumbnail = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
        if ( false!==file($thumbnail) )
            $result['thumbnail'] = $thumbnail;
        if( $product->getMediaGalleryImages() ){
            $result['gallery'] = array();
            foreach( $product->getMediaGalleryImages() as $img ){
                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $img->getFile();
                if ( false!==file($url) ){
                    $result['gallery'][] = array('position'=> $img->getPosition() , 'url'=>$url );
                }
            }
        }
        return $result;
    }

    public function creationOptions( $att , $val ){
        $installer = new Mage_Eav_Model_Entity_Setup('core_setup');
        $installer->startSetup();

        $attributeCode = $att;
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attributeCode);

        if($attribute->getId() && $attribute->getFrontendInput()=='select') {

            $newOptions =  array($val);
            $exitOptions =  array();
            $options = Mage::getModel('eav/entity_attribute_source_table')
                ->setAttribute($attribute)
                ->getAllOptions(false);

            foreach ($options as $option) {
                if (in_array($option['label'], $newOptions)) {
                    array_push($exitOptions, $option['label']);
                }
            }
            $insertOptions = array_diff($newOptions, $exitOptions);
            if(!empty($insertOptions)) {
                $optionAdd['attribute_id'] = $attribute->getId();
                $optionAdd['value']['r'][0] = $insertOptions[0];
                $installer->addAttributeOption($optionAdd);
            }
        }
        $installer->endSetup();
        return;
    }

    public function getOptionId( $att , $val){
        $this->creationOptions($att,$val);
        $attributeCode = $att;
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attributeCode);
        $options = Mage::getModel('eav/entity_attribute_source_table')
            ->setAttribute($attribute)
            ->getAllOptions(false);
        $id = "";
        foreach ($options as $option) {
            if( strtolower($val) == strtolower($option['label']) ){
                $id = $option['value'];
                break;
            }
        }
        return $id;
    }

    public function getResponse(){
        if( $this->config->getStatus() ) {
            $url = $this->config->getUrl();
            $response = null;
            $requestBody = array(
                "username" => $this->config->getUsername(),
                "apikey" => $this->config->getApiKey(),
                "soapclient" => $this->config->getSoapUrl(),
                "request" => 'fetch'
            );
            $requestBody = json_encode($requestBody);
            try {
                // Such big timeout is needed because SkuVault updateProducts request is taking a lot of time.
                $this->curl->setConfig(array('timeout' => $this->config->getTimeOut()));
                $this->curl->setUri($url);
                $this->curl->setRawData($requestBody, 'application/json');
                $response = $this->curl->request(Zend_Http_Client::POST);
                $body = $response->getBody();
            } catch (Exception $e) {
                Mage::log("Authuntication Fail. " . $e->getMessage(), null, 'syncproduct.log');
                return array();
            }
            return $response = json_decode($body);
        }
        return array();
    }

    public function createProduct( $response ,  $date ){
        $result = array();
        $data = unserialize( $response->data );
        $this->addLog( " Unserialized Data. " , $date );
        $stores = Mage::app()->getStores();
        $activeStore = array();
        foreach($stores as $key => $store){
            $activeStore[] = array( 'id'=>$key,'code'=>Mage::app()->getStore($key)->getCode());
        }
        $this->addLog( " Store Array Created. " , $date );
        $websiteCode = 'base';
        $website = Mage :: app() -> getWebsite( trim( $websiteCode ) );
        $websiteIds = array ($website -> getId());
        /* Upload Simple Product */
        $simpleProductArray = array();
        if( !empty( $data['simple'] ) ){
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            foreach( $data['simple'] as $sku => $simple  ){

                $appEmulation = Mage::getSingleton('core/app_emulation');
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($adminStore, Mage_Core_Model_App_Area::AREA_ADMIN );

                //Mage::app()->setCurrentStore( $adminStore );
                $skuObje = Mage::getModel("catalog/product")->loadByAttribute('sku',$sku);
                if( $skuObje ) {
                    $product = Mage::getModel("catalog/product")->load($skuObje->getId())->setStoreId($adminStore);
                    $this->addLog( $sku." Simple Product Update Start. " , $date );
                }else {
                    $product = Mage::getModel('catalog/product')->setStoreId($adminStore);
                    $this->addLog( $sku." Simple Product Creation Start . " , $date );
                }
                /* Add Simple Product Start Here*/
                $manufacturer =  $this->getOptionId('manufacturer',$simple['manufacturer']);
                $color        =  $this->getOptionId('color',$simple['color']);
                $size         =  $this->getOptionId('size',$simple['size']);
                $product->setStoreId($simple['store']);
                $product->setWebsiteIds($websiteIds);                       $product->setWeight($simple['weight']);
                $product->setAttributeSetId( $simple['attribute_set'] );    $product->setTypeId($simple['type_id']);
                $product->setCategoryIds($simple['category_ids']);          $product->setSku( $simple['sku'] );
                $product->setHasOptions($simple['has_options']);            $product->setName( $simple['name'] );
                $product->setSellerShippingOption($simple['seller_shipping_option']);   $product->setPrice($simple['price']);
                $product->setManufacturer($manufacturer);                   $product->setColor($color);
                $product->setSize($size);                                   $product->setStatus($simple['status']);
                $product->setVisibility($simple['visibility']);             $product->setTaxClassId($simple['tax_class_id']);
                $product->setDescription($simple['description']);           $product->setShortDescription($simple['short_description']);
                $product->setSellerId($simple['seller_id']);                $product->setGroupId($simple['group_id']);
                $product->setStockData(array(
                        'manage_stock'=>$simple['inventory']['manage_stock'],
                        'is_in_stock' => $simple['inventory']['is_in_stock'],
                        'qty' => $simple['inventory']['qty'],
                        'product_name' => $simple['inventory']['product_name'],
                        'product_type_id' => $simple['inventory']['product_type_id']
                    )
                );

                $this->addLog( $sku." All Basic Attribute Data Added. " , $date );
                /* Add Media Gallery */
                if( $skuObje ){
                    $this->removeMedia($product);
                    $this->addLog( $sku." Media Gallery Deleted. " , $date );
                }
                $product = $this->updateMedia( $product , $simple );
                $this->addLog( $sku." Media Gallery Added. " , $date );
                if( $skuObje ) {
                    $product->getResource()->save($product);
                    $result['complete'][] = $product->getSku();
                }else{
                    $product->save();
                    $result['complete'][] = $product->getSku();
                }
                $this->addLog( $sku." Product Saved. " , $date );
                Mage::helper("partialindex")->addPartialIndexer($product->getId(), 9998);
                $this->addLog( $sku." Product Added Into Partial Indexer. " , $date );
                /* Add Simple Product End Here*/
                $simpleProductArray[$sku] = $product->getId();

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            }
        }else{
            $this->addLog( " No Any Simple Product Exist. " , $date );
        }
        /* Upload Simple Product End */

        /* Upload Config Product */
        if( !empty( $data['configurable'] ) ){
            foreach( $data['configurable'] as $sku => $configurable  ){
                //Mage::app()->setCurrentStore( $adminStore );
                $appEmulation = Mage::getSingleton('core/app_emulation');
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($adminStore, Mage_Core_Model_App_Area::AREA_ADMIN );

                $skuObje = Mage::getModel("catalog/product")->loadByAttribute('sku',$sku);
                if( $skuObje ) {
                    $productConfig = Mage::getModel("catalog/product")->load($skuObje->getId());
                    $this->addLog( $sku." Config Product Creation Start . " , $date );
                }else {
                    $productConfig = Mage::getModel('catalog/product');
                    $this->addLog( $sku." Config Product Update Start . " , $date );
                }
                $manufacturer =  $this->getOptionId('manufacturer',$simple['manufacturer']);

                /*Basic Detail Add For Config Product Start here*/
                $productConfig->setStoreId($configurable['store']);
                $productConfig->setWebsiteIds($websiteIds);
                $productConfig->setWeight($configurable['weight']);
                $productConfig->setAttributeSetId( $configurable['attribute_set'] );      $productConfig->setTypeId($configurable['type_id']);
                $productConfig->setCategoryIds($configurable['category_ids']);            $productConfig->setSku( $configurable['sku'] );
                $productConfig->setHasOptions($configurable['has_options']);              $productConfig->setName( $configurable['name'] );
                $productConfig->setSellerShippingOption($configurable['seller_shipping_option']);     $productConfig->setPrice($configurable['price']);
                $productConfig->setManufacturer($manufacturer);                           $productConfig->setColor($configurable['color']);
                $productConfig->setSize($configurable['size']);                           $productConfig->setStatus($configurable['status']);
                $productConfig->setVisibility($configurable['visibility']);               $productConfig->setTaxClassId($configurable['tax_class_id']);
                $productConfig->setDescription($configurable['description']);             $productConfig->setShortDescription($configurable['short_description']);
                $productConfig->setSellerId($configurable['seller_id']);                  $productConfig->setGroupId($configurable['group_id']);
                $productConfig->setStockData(array(
                        'manage_stock'=>$configurable['inventory']['manage_stock'],
                        'is_in_stock' => $configurable['inventory']['is_in_stock'],
                        'qty' => $configurable['inventory']['qty'],
                        'product_name' => $configurable['inventory']['product_name'],
                        'product_type_id' => $configurable['inventory']['product_type_id']
                    )
                );
                $this->addLog( $sku." All Basic Attribute Data Added. " , $date );
                /* Add Media Gallery */
                if( $skuObje ){
                    $this->removeMedia( $productConfig );
                    $this->addLog( $sku." Media Gallery Deleted. " , $date );
                }

                $productConfig = $this->updateMedia( $productConfig , $configurable );
                $this->addLog( $sku." Media Gallery Added. " , $date );
                /*Basic Detail Add For Config Product End here*/
                /* Create Associated Product. Array to assign Config Product Start here*/
                $this->addLog( $sku." Config Product Associated Product creation start. " , $date );
                $configurableProductsData = array();
                foreach( $configurable['associated_products'] as $key => $associated_products ){
                    if( isset( $simpleProductArray[$key] ) ){
                        $configurableProductsData[$simpleProductArray[$key]] = array();
                    }else{
                        $skuObje1 = Mage::getModel("catalog/product")->loadByAttribute('sku',$key);
                        if( $skuObje1 )
                            $configurableProductsData[$skuObje1->getId()]=array();
                    }
                }
                /* Create Associated Product. Array to assign Config Product End here*/

                /* Get Attribute Id of Color And Size Start Here*/
                $usingAttributeIds = array();
                $configAttributeCodes = array('color','size');
                foreach($configAttributeCodes as $attributeCode) {
                    $attribute = $productConfig->getResource()->getAttribute($attributeCode);
                    if ($productConfig->getTypeInstance()->canUseAttribute($attribute)) {
                        $usingAttributeIds[] = $attribute->getAttributeId();
                    }
                }

                /* Get Attribute Id of Color And Size End Here*/
                //Set Configurable product variations
                $productConfig->getTypeInstance()->setUsedProductAttributeIds($usingAttributeIds);
                $configurableAttributesData = $productConfig->getTypeInstance()->getConfigurableAttributesAsArray();
                $productConfig->setCanSaveConfigurableAttributes(true);
                $productConfig->setConfigurableAttributesData($configurableAttributesData);
                $productConfig->setConfigurableProductsData($configurableProductsData);
                $this->addLog( $sku." Config Product Associated Product creation complete. " , $date );
                //Mage::dispatchEvent('catalog_product_save_before', array('product' => $productConfig));
                if( $skuObje ) {
                    $urlkey = $this->createProductUrl( $productConfig->getId() ,$configurable['manufacturer'] , $configurable['category_ids'] , $configurable['name']  );
                    $productConfig->setUrlKey( $urlkey );
                    $productConfig->getResource()->save($productConfig);
                    $result['complete'][] = $productConfig->getSku();
                }else{
                    $urlkey = $this->createProductUrl( "" ,$configurable['manufacturer'] , $configurable['category_ids'] , $configurable['name']  );
                    $productConfig->setUrlKey( $urlkey );
                    $productConfig->save();
                    $result['complete'][] = $productConfig->getSku();
                }
                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

                $this->addLog( $sku." Product Saved. " , $date );
                Mage::helper("partialindex")->addPartialIndexer($productConfig->getId(), 9997);
                $this->addLog( $sku." Product Added Into Partial Indexer. " , $date );

                if( !empty( $configurable['storebased'] ) ){
                    $this->updateStoreBasedData( $configurable['storebased'] , $productConfig->getId() , $activeStore , $urlkey );
                    $this->addLog( $sku." Config Store Based Data Added. " , $date );
                }
            }
            /* Upload Config Product End*/
        }else{
            $this->addLog( " No Any Config Product Exist. " , $date );
        }

        if( !empty( $data['product_not_exist'] ) ){
            foreach( $data['product_not_exist'] as $noexist ){
                $result['fail'][] = $noexist;
                $this->addLog( $noexist." Product Not Exist of Data Server. " , $date );
            }
        }
        return $result;
    }


    public function addLog( $message , $date ){
        Mage::log(" Date Start : ".$date. " | Message : " . $message , null, 'syncproduct_cron.log');
        return true;
    }

    public function removeMedia( $product ){
        //$mediaGalleryAttribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'media_gallery');
        $attributes = $product->getTypeInstance()->getSetAttributes();
        if (isset($attributes['media_gallery'])) {
            $gallery = $attributes['media_gallery'];
            //Get the images
            $galleryData = $product->getMediaGallery();
            if (!empty($galleryData)) {
                foreach ($galleryData['images'] as $image) {
                    //If image exists
                    if ($gallery->getBackend()->getImage($product, $image['file'])) {
                        $gallery->getBackend()->removeImage($product, $image['file']);
                        //if ( file_exists(Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . $image['file'] ) ) {
                        if (file_exists($image['file'])) {
                            unlink(Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . $image['file']);
                        }
                    }
                }
            }
        }
        return $product;
    }

    public function updateMedia( $product , $simple ){
        $importDir = Mage::getBaseDir('media') . DS;
        $image      = $simple['image'];
        $imageName  = basename($image);
        $thumbnail = $simple['thumbnail'];
        $thumbnailName = basename($thumbnail);
        $smallimage = $simple['small_image'];
        $smallimageName = basename($smallimage);
        $imgArray = array();
        if( $image ==  $thumbnail && $thumbnail == $smallimageName ){
            $filePath = $importDir . $imageName;
            copy($image, $importDir . $imageName);
            $imgArray[] = $imageName;
            $product->addImageToMediaGallery($filePath, array('image', 'small_image', 'thumbnail'), false, false);
        }else {
            if (!empty($simple['image'])) {
                $imgArray[] = $imageName;
                $filePath = $importDir . $imageName;
                copy($image, $importDir . $imageName);
                $product->addImageToMediaGallery($filePath, array('image'), false, false);
            }
            if (!empty($simple['thumbnail'])) {
                $imgArray[] = $thumbnailName;
                $filePath = $importDir . $thumbnailName;
                copy($thumbnail, $importDir . $thumbnailName);
                $product->addImageToMediaGallery($filePath, array('thumbnail'), false, false);
            }

            if (!empty($simple['small_image'])) {
                $imgArray[] = $smallimageName;
                $filePath = $importDir . $smallimageName;
                copy($smallimage, $importDir . $smallimageName);
                $product->addImageToMediaGallery($filePath, array('small_image'), false, false);
            }
        }
        if (!empty($simple['gallery'])) {
            foreach ($simple['gallery'] as $img) {
                $url = $img['url'];
                $basename = basename($url);
                if( !in_array( $basename , $imgArray ) ){
                    $imgArray[] = $basename;
                    $filePath = $importDir . $basename;
                    copy($url, $importDir . $basename);
                    $product->addImageToMediaGallery($filePath, null, false, false);
                }
            }
        }
        return $product;
    }

    public function createProductUrl( $product , $brand , $category , $name ){
        $seoUrl = "buy ";
        if (!empty($brand)) {
            $seoUrl .= $brand . " ";
        }
        $seoUrl .= $name . " ";
        if (!empty( $category )) {
            $cats = $category;
            sort($cats);
            // here get the parent category ids which need to exclude from product url
            // also get all the child categories from above to remove
            $excludedCategories = explode(',', Mage::getModel('core/variable')->setStoreId(Mage::app()->getStore()->getId())->loadByCode('exclude_categories_from_product_url')->getValue('text'));
            $toExclude = array();
            foreach ($excludedCategories as $excludedCategory) {
                $toExclude = Mage::helper('mirasvitseo')->retrieveAllChildCategories($excludedCategory);
                $toExclude = Mage::helper('mirasvitseo')->getAllKeysForMultiLevelArrays($toExclude);
                $cats = array_diff($cats, $toExclude);
            }
            $cats = array_diff($cats, $excludedCategories);
            $targetedCat = array();
            if (count($cats) > 0) {
                $targetedCat[] = reset($cats);
                $targetedCat[] = end($cats);
                $categories = Mage::getResourceModel('catalog/category_collection')
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('entity_id', array('in' => $targetedCat));
                $seoUrl .= "for ";
                foreach ($categories as $category)
                    $seoUrl .= $category->getName() . " ";
            }
        }

        if( $product == "" ) {
            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->addAttributeToSort('entity_id', 'DESC');
            $collection->setPage(1, 1);
            $lastInsertedProductId = (integer)$collection->getFirstItem()->getId();
            $lastInsertedProductId++;
            $seoUrl .= $lastInsertedProductId . " ";
        }else{
            $seoUrl .= $product . " ";
        }

        $seoUrl = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($seoUrl));
        $seoUrl = strtolower($seoUrl);
        $seoUrl = trim($seoUrl, '-');
        Mage::getSingleton('core/session')->setSeoProductUrl($seoUrl);
        return Mage::getSingleton('core/session')->getSeoProductUrl();
    }

    /* Update Store Based Data */
    public function updateStoreBasedData( $object , $id , $stores , $urlkey ){
        foreach( $stores as $store ){
            if( isset( $object[$store['code']] ) ){
                //Mage::app()->setCurrentStore( $store['id'] );
                $appEmulation = Mage::getSingleton('core/app_emulation');
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation( $store['id'] ,Mage_Core_Model_App_Area::AREA_ADMIN );

                Mage::getSingleton('catalog/product_action')->updateAttributes(
                    array($id),
                    array(
                        'name'=>$object[$store['code']]['name'],
                        'description'=>$object[$store['code']]['description'],
                        'short_description'=>$object[$store['code']]['short_description'],
                        'url_key'=>$urlkey
                    ), //array with attributes to update
                    $store['id']
                );

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            }
        }
    }

    public function loadBySku($sku)
    {
        $read = $this->getResource()->getReadConnection();
        $select = $read->select()
            ->from($this->getResource()->getMainTable())
            ->where('sku LIKE ?', $sku);
        $data = $read->fetchRow($select);
        if ($data) {
            $this->setData($data);
        }

        return $this;
    }
}
<?php

/**
 * This Module is created for Desktop and Mobile App search from klevu
 * @category     Progos
 * @package      Progos_KlevuSearch
 * @copyright    Progos Tech Copyright (c) 11-09-2017
 * @author       Hassan Ali Shahzad
 *
 */
class Progos_KlevuSearch_Model_Product_Sync extends Klevu_Search_Model_Product_Sync
{


    /*
     * This function overrided to resolve OOS product issue This functionality provided by klevu Team
     * and updated as provided.
     * It will remove OOS products from search
     *
     * */
    public function syncData($store){

        if ($this->rescheduleIfOutOfMemory()) {
            return;
        }

        $config = Mage::helper('klevu_search/config');
        $session = Mage::getSingleton('klevu_search/session');
        $firstSync = $session->getFirstSync();
        try {
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if(!empty($firstSync) || $rating_upgrade_flag==0) {
                $this->updateProductsRating($store);
            }
        } catch(Exception $e) {
            Mage::helper('klevu_search')->log(Zend_Log::WARN, sprintf("Unable to update rating attribute %s", $store->getName()));
        }
        //set current store so will get proper bundle price
        Mage::app()->setCurrentStore($store->getId());

        $this->log(Zend_Log::INFO, sprintf("Starting sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));

        $actions = array(
            'delete' =>
                $this->getConnection()
                    ->select()
                    ->union(array(
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select synced products in the current store/mode that are no longer enabled
                                 * (don't exist in the products table, or have status disabled for the current
                                 * store, or have status disabled for the default store) or are not visible
                                 * (in the case of configurable products, check the parent visibility instead).
                                 */
                                ->from(
                                    array('k' => $this->getTableName("klevu_search/product_sync")),
                                    array('product_id' => "k.product_id", 'parent_id' => "k.parent_id")
                                )
                                ->joinLeft(
                                    array('v' => $this->getTableName("catalog/category_product_index")),
                                    "v.product_id = k.product_id AND v.store_id = :store_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('p' => $this->getTableName("catalog/product")),
                                    "p.entity_id = k.product_id",
                                    ""
                                )
                                ->joinLeft(
                                    array('si' => $this->getTableName("cataloginventory/stock_item")),
                                    "si.product_id = p.entity_id AND si.stock_id=1",
                                    ""
                                )
                                ->joinLeft(
                                    array('ss' => $this->getProductStatusAttribute()->getBackendTable()),
                                    "ss.attribute_id = :status_attribute_id AND ss.entity_id = k.product_id AND ss.store_id = :store_id",
                                    ""
                                )->joinLeft(
                                    array('sd' => $this->getProductStatusAttribute()->getBackendTable()),
                                    "sd.attribute_id = :status_attribute_id AND sd.entity_id = k.product_id AND sd.store_id = :default_store_id",
                                    ""
                                )

                                ->where("(k.store_id = :store_id) AND (k.type = :type) AND (k.test_mode = :test_mode) AND ((p.entity_id IS NULL) OR(si.is_in_stock = 0) OR (CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END != :status_enabled) OR (CASE WHEN k.parent_id = 0 THEN k.product_id ELSE k.parent_id END NOT IN (?)) )",
                                    $this->getConnection()
                                        ->select()
                                        ->from(
                                            array('i' => $this->getTableName("catalog/category_product_index")),
                                            array('id' => "i.product_id")
                                        )
                                        ->where("(i.store_id = :store_id) AND (i.visibility IN (:visible_both, :visible_search))")

                                ),
                            $this->getConnection()
                                ->select()
                                /*
                                 * Select products which are not associated with parent
                                 * but still parent exits in klevu product sync table with parent id
                                 *
                                 */
                                ->from(
                                    array('ks' => $this->getTableName("klevu_search/product_sync")),
                                    array('product_id' => "ks.product_id","parent_id" => 'ks.parent_id')
                                )
                                ->where("(ks.parent_id !=0 AND ks.product_id NOT IN (?) AND ks.store_id = :store_id)",
                                    $this->getConnection()
                                        ->select()
                                        /*
                                         * Select products from catalog super link table
                                         */
                                        ->from(
                                            array('s' => $this->getTableName("catalog/product_super_link")),
                                            array('product_id' => "s.product_id")
                                        )
                                )
                        )
                    )
                    ->group(array('k.product_id', 'k.parent_id'))
                    ->bind(array(
                        'type'          => "products",
                        'store_id'       => $store->getId(),
                        'default_store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                        'test_mode'      => $this->isTestModeEnabled(),
                        'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                        'status_enabled' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                        'visible_both'   => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                        'visible_search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
                    )),

            'update' => $this->getConnection()
                ->select()
                ->union(array(
                    // Select products without parents that need to be updated
                    $this->getConnection()
                        ->select()
                        /*
                         * Select synced non-configurable products for the current store/mode
                         * that are visible (using the category product index) and have been
                         * updated since last sync.
                         */
                        ->from(
                            array('k' => $this->getTableName("klevu_search/product_sync")),
                            array('product_id' => "k.product_id", 'parent_id' => "k.parent_id")
                        )
                        ->join(
                            array('p' => $this->getTableName("catalog/product")),
                            "p.entity_id = k.product_id",
                            ""
                        )
                        ->join(
                            array('i' => $this->getTableName("catalog/category_product_index")),
                            "i.product_id = k.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                            ""
                        )
                        ->where("(k.store_id = :store_id) AND (k.type = :type) AND (k.test_mode = :test_mode) AND (p.type_id != :configurable) AND (p.updated_at > k.last_synced_at)"),
                    // Select products with parents (configurable) that need to be updated
                    $this->getConnection()
                        ->select()
                        /*
                         * Select synced products for the current store/mode that are configurable
                         * children (have entries in the super link table), are enabled for the current
                         * store (or the default store), have visible parents (using the category product
                         * index) and, either the product or the parent, have been updated since last sync.
                         */
                        ->from(
                            array('k' => $this->getTableName("klevu_search/product_sync")),
                            array('product_id' => "k.product_id", 'parent_id' => "k.parent_id")
                        )
                        ->join(
                            array('s' => $this->getTableName("catalog/product_super_link")),
                            "k.parent_id = s.parent_id AND k.product_id = s.product_id",
                            ""
                        )
                        ->join(
                            array('i' => $this->getTableName("catalog/category_product_index")),
                            "k.parent_id = i.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                            ""
                        )
                        ->join(
                            array('p1' => $this->getTableName("catalog/product")),
                            "k.product_id = p1.entity_id",
                            ""
                        )
                        ->join(
                            array('p2' => $this->getTableName("catalog/product")),
                            "k.parent_id = p2.entity_id",
                            ""
                        )
                        ->joinLeft(
                            array('ss' => $this->getProductStatusAttribute()->getBackendTable()),
                            "ss.attribute_id = :status_attribute_id AND ss.entity_id = k.product_id AND ss.store_id = :store_id",
                            ""
                        )
                        ->joinLeft(
                            array('sd' => $this->getProductStatusAttribute()->getBackendTable()),
                            "sd.attribute_id = :status_attribute_id AND sd.entity_id = k.product_id AND sd.store_id = :default_store_id",
                            ""
                        )
                        ->where("(k.store_id = :store_id) AND (k.type = :type) AND (k.test_mode = :test_mode) AND (CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END = :status_enabled) AND ((p1.updated_at > k.last_synced_at) OR (p2.updated_at > k.last_synced_at))")
                ))
                ->group(array('k.product_id', 'k.parent_id'))
                ->bind(array(
                    'type'          => "products",
                    'store_id' => $store->getId(),
                    'default_store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                    'test_mode' => $this->isTestModeEnabled(),
                    'configurable' => Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE,
                    'visible_both' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                    'visible_search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                    'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                    'status_enabled' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                )),

            'add' => $this->getConnection()
                ->select()
                ->union(array(
                    // Select non-configurable products that need to be added
                    $this->getConnection()
                        ->select()
                        /*
                         * Select non-configurable products that are visible in the current
                         * store (using the category product index), but have not been synced
                         * for this store yet.
                         */
                        ->from(
                            array('p' => $this->getTableName("catalog/product")),
                            array('product_id' => "p.entity_id", 'parent_id' => new Zend_Db_Expr("0"))
                        )
                        ->join(
                            array('i' => $this->getTableName("catalog/category_product_index")),
                            "p.entity_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                            ""
                        )
                        ->join(
                            array('si' => $this->getTableName("cataloginventory/stock_item")),
                            "si.product_id = i.product_id",
                            ""
                        )
                        ->joinLeft(
                            array('k' => $this->getTableName("klevu_search/product_sync")),
                            "p.entity_id = k.product_id AND k.parent_id = 0 AND i.store_id = k.store_id AND k.test_mode = :test_mode AND k.type = :type",
                            ""
                        )
                        ->where("(p.type_id != :configurable) AND (si.stock_id=1 AND si.is_in_stock = 1) AND (k.product_id IS NULL)"),
                    // Select configurable parent & product pairs that need to be added
                    $this->getConnection()
                        ->select()
                        /*
                         * Select configurable product children that are enabled (for the current
                         * store or for the default store), have visible parents (using the category
                         * product index) and have not been synced yet for the current store with
                         * the current parent.
                         */
                        ->from(
                            array('s' => $this->getTableName("catalog/product_super_link")),
                            array('product_id' => "s.product_id", 'parent_id' => "s.parent_id")
                        )
                        ->join(
                            array('si' => $this->getTableName("cataloginventory/stock_item")),
                            "si.product_id = s.product_id AND si.is_in_stock =1 AND si.stock_id=1",
                            ""
                        )
                        ->join(
                            array('i' => $this->getTableName("catalog/category_product_index")),
                            "s.parent_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                            ""
                        )
                        ->joinLeft(
                            array('ss' => $this->getProductStatusAttribute()->getBackendTable()),
                            "ss.attribute_id = :status_attribute_id AND ss.entity_id = s.product_id AND ss.store_id = :store_id",
                            ""
                        )
                        ->joinLeft(
                            array('sd' => $this->getProductStatusAttribute()->getBackendTable()),
                            "sd.attribute_id = :status_attribute_id AND sd.entity_id = s.product_id AND sd.store_id = :default_store_id",
                            ""
                        )
                        ->joinLeft(
                            array('k' => $this->getTableName("klevu_search/product_sync")),
                            "s.parent_id = k.parent_id AND s.product_id = k.product_id AND k.store_id = :store_id AND k.test_mode = :test_mode AND k.type = :type",
                            ""
                        )

                        ->where("(CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END = :status_enabled) AND (si.is_in_stock=1) AND (k.product_id IS NULL)")
                ))
                ->group(array('k.product_id', 'k.parent_id'))
                ->bind(array(
                    'type' => "products",
                    'store_id' => $store->getId(),
                    'default_store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                    'test_mode' => $this->isTestModeEnabled(),
                    'configurable' => Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE,
                    'visible_both' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                    'visible_search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                    'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                    'status_enabled' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                ))

        );

        $errors = 0;

        foreach ($actions as $action => $statement) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }

            $method = $action . "Products";
            $products = $this->getConnection()->fetchAll($statement, $statement->getBind());

            $total = count($products);
            $this->log(Zend_Log::INFO, sprintf("Found %d products to %s.", $total, $action));
            $pages = ceil($total / static::RECORDS_PER_PAGE);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }

                $offset = ($page - 1) * static::RECORDS_PER_PAGE;
                $result = $this->$method(array_slice($products, $offset, static::RECORDS_PER_PAGE));

                if ($result !== true) {
                    $errors++;
                    $this->log(Zend_Log::ERR, sprintf("Errors occurred while attempting to %s products %d - %d: %s",
                        $action,
                        $offset + 1,
                        ($offset + static::RECORDS_PER_PAGE <= $total) ? $offset + static::RECORDS_PER_PAGE : $total,
                        $result
                    ));
                    /*$this->notify(
                        Mage::helper('klevu_search')->__("Product Sync for %s (%s) failed to %s some products. Please consult the logs for more details.",
                            $store->getWebsite()->getName(),
                            $store->getName(),
                            $action
                        ),
                        $store
                    );*/
                }
            }
        }

        $this->log(Zend_Log::INFO, sprintf("Finished sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));

        /* Sync category content */
        $this->runCategory($store);

        if (!$config->isExtensionEnabled($store) && !$config->hasProductSyncRun($store)) {
            // Enable Klevu Search after the first sync
            if(!empty($firstSync)) {
                $config->setExtensionEnabledFlag(true, $store);
                $this->log(Zend_Log::INFO, sprintf("Automatically enabled Klevu Search on Frontend for %s (%s).",
                    $store->getWebsite()->getName(),
                    $store->getName()
                ));
            }

        }
        $config->setLastProductSyncRun("now", $store);

        if ($errors == 0) {
            // If Product Sync finished without any errors, notifications are not relevant anymore
            $this->deleteNotifications($store);
        }

    }

    /**
     * This function need to override to avoid the re-generation of product images in klevu_images Folder L:290
     *
     * Add the Product Sync data to each product in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $products An array of products. Each element should be an array with
     *                        containing an element with "id" as the key and the product
     *                        ID as the value.
     *
     * @return $this
     */
    protected function addProductSyncData(&$products) {
        $product_ids = array();
        $parent_ids = array();
        foreach ($products as $product) {
            $product_ids[] = $product['product_id'];
            if ($product['parent_id'] != 0) {
                $product_ids[] = $product['parent_id'];
                $parent_ids[] = $product['parent_id'];
            }
        }
        $product_ids = array_unique($product_ids);
        $parent_ids = array_unique($parent_ids);
        $data = Mage::getModel('catalog/product')->getCollection()
            ->addIdFilter($product_ids)
            ->setStore($this->getStore())
            ->addStoreFilter()
            ->addFinalPrice()
            ->addAttributeToSelect($this->getUsedMagentoAttributes());

        $data->load()
            ->addCategoryIds();

        $url_rewrite_data = $this->getUrlRewriteData($product_ids);
        $visibility_data = $this->getVisibilityData($product_ids);
        //$configurable_price_data = $this->getConfigurablePriceData($parent_ids);

        $stock_data = $this->getStockData($product_ids);

        $attribute_map = $this->getAttributeMap();
        $config = Mage::helper('klevu_search/config');
        if($config->isSecureUrlEnabled($this->getStore()->getId())) {
            $base_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK,true);
            $media_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA,true);

        }else {
            $base_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            $media_url = $this->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        }
        $currency = $this->getStore()->getDefaultCurrencyCode();
        $media_url .= Mage::getModel('catalog/product_media_config')->getBaseMediaUrlAddition();
        Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND,Mage_Core_Model_App_Area::PART_EVENTS);
        $backend = Mage::getResourceModel('catalog/product_attribute_backend_media');
        $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', 'media_gallery');
        $container = new Varien_Object(array(
            'attribute' => new Varien_Object(array('id' => $attributeId))
        ));
        $rc = 0;

        foreach ($products as $index => &$product) {

            if($rc % 5 == 0) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return $rc;
                }
            }

            if($config->getCollectionMethod()) {
                $item = $data->getItemById($product['product_id']);
                $parent = ($product['parent_id'] != 0) ?  $data->getItemById($product['parent_id']) : null;
                $this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID %d using collection method", $product['product_id']));
                $this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID Parent ID %d using collection method", $product['parent_id']));
            } else {
                $item = Mage::getModel('catalog/product')->load($product['product_id']);
                $item->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
                $this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID %d", $product['product_id']));
                $parent = ($product['parent_id'] != 0) ? Mage::getModel('catalog/product')->load($product['parent_id'])->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID): null;
                $this->log(Zend_Log::DEBUG, sprintf("Retrieve data for product ID Parent ID %d", $product['parent_id']));
            }

            if (!$item) {
                // Product data query did not return any data for this product
                // Remove it from the list to skip syncing it
                $this->log(Zend_Log::WARN, sprintf("Failed to retrieve data for product ID %d", $product['product_id']));
                unset($products[$index]);
                continue;
            }

            /* Use event to add any external module data to product */
            Mage::dispatchEvent('add_external_data_to_sync', array(
                'parent' => $parent,
                'product'=> &$product,
                'store' => $this->getStore()
            ));

            // Add data from mapped attributes
            foreach ($attribute_map as $key => $attributes) {
                $product[$key] = null;

                switch ($key) {
                    case "boostingAttribute":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $parent->getData($attribute);
                                break;
                            } else {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
                    case "rating":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $this->convertToRatingStar($parent->getData($attribute));
                                break;
                            } else {
                                $product[$key] = $this->convertToRatingStar($item->getData($attribute));
                                break;
                            }
                        }
                        break;
                    case "otherAttributeToIndex":
                    case "other":
                        $product[$key] = array();
                        foreach ($attributes as $attribute) {
                            if ($item->getData($attribute)) {
                                $product[$key][$attribute] = $this->getAttributeData($attribute, $item->getData($attribute));
                            } else if ($parent && $parent->getData($attribute)) {
                                $product[$key][$attribute] = $this->getAttributeData($attribute, $parent->getData($attribute));
                            }
                        }
                        break;
                    case "sku":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = Mage::helper('klevu_search')->getKlevuProductSku($item->getData($attribute), $parent->getData($attribute));
                                break;
                            } else {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
                    case "name":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $parent->getData($attribute);
                                break;
                            }else if ($item->getData($attribute)) {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
                    case "desc":
                        foreach ($attributes as $attribute) {
                            if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $parent->getData($attribute).$item->getData($attribute);
                                break;
                            } else {
                                $product[$key] = $item->getData($attribute);
                                break;
                            }
                        }
                        break;
                    case "shortDesc":
                        foreach ($attributes as $attribute) {
                            if($config->isUseConfigDescription($this->getStore()->getId())) {
                                if ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $parent->getData($attribute);
                                    break;
                                } else {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                }
                            } else {
                                if ($item->getData($attribute)) {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                } else {
                                    if ($parent && $parent->getData($attribute)) {
                                        $product[$key] = $parent->getData($attribute);
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case "image":
                        $config = Mage::helper('klevu_search/config');
                        foreach ($attributes as $attribute) {
                            if($config->isUseConfigImage($this->getStore()->getId())) {
                                if ($parent && $parent->getData($attribute) && $parent->getData($attribute) != "no_selection") {
                                    $product[$key] = $parent->getData($attribute);
                                    break;
                                } else if ($item->getData($attribute) && $item->getData($attribute) != "no_selection") {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                }

                                if ($parent && $parent->getData($attribute) == "no_selection") {
                                    $product[$key] = $parent->getData('small_image');
                                    if($product[$key] == "no_selection"){
                                        $product_media = new Varien_Object(array(
                                            'id' => $product['parent_id'],
                                            'store_id' => $this->getStore()->getId(),
                                        ));
                                        $media_image = $backend->loadGallery($product_media, $container);
                                        if(count($media_image) > 0) {
                                            $product[$key] = $media_image[0]['file'];
                                        }
                                    }
                                    break;
                                } else if ($item->getData($attribute) && $item->getData($attribute) == "no_selection") {
                                    $product[$key] = $item->getData('small_image');
                                    if($product[$key] == "no_selection"){
                                        $product_media = new Varien_Object(array(
                                            'id' => $product['product_id'],
                                            'store_id' => $this->getStore()->getId(),
                                        ));
                                        $media_image = $backend->loadGallery($product_media, $container);
                                        if(count($media_image) > 0) {
                                            $product[$key] = $media_image[0]['file'];
                                        }
                                    }
                                    break;
                                }

                            } else {
                                if ($item->getData($attribute) && $item->getData($attribute) != "no_selection") {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                } else if ($parent && $parent->getData($attribute) && $parent->getData($attribute) != "no_selection") {
                                    $product[$key] = $parent->getData($attribute);
                                    break;
                                }

                                if ($item->getData($attribute) && $item->getData($attribute) == "no_selection") {
                                    $product[$key] = $item->getData('small_image');
                                    if($product[$key] == "no_selection"){
                                        $product_media = new Varien_Object(array(
                                            'id' => $product['product_id'],
                                            'store_id' => $this->getStore()->getId(),
                                        ));
                                        $media_image = $backend->loadGallery($product_media, $container);

                                        if(count($media_image) > 0) {
                                            $product[$key] = $media_image[0]['file'];
                                        }
                                    }
                                    break;
                                } else if ($parent && $parent->getData($attribute) && $parent->getData($attribute) == "no_selection") {
                                    $product[$key] = $parent->getData('small_image');
                                    if($product[$key] == "no_selection"){
                                        $product_media = new Varien_Object(array(
                                            'id' => $product['parent_id'],
                                            'store_id' => $this->getStore()->getId(),
                                        ));
                                        $media_image = $backend->loadGallery($product_media, $container);
                                        if(count($media_image) > 0) {
                                            $product[$key] = $media_image[0]['file'];
                                        }
                                    }
                                    break;
                                }

                            }
                        }
                        if(!is_array($product[$key])) {
                            if ($product[$key] != "" && strpos($product[$key], "http") !== 0) {
                                if(strpos($product[$key],"/", 0) !== 0 && !empty($product[$key]) && $product[$key]!= "no_selection" ){
                                    $product[$key] = "/".$product[$key];
                                }
                                //No need to generate thumbnail image for each products separately use base images for more reference see old changes on original file(Hassan Ali shahzad)
                                $imageResized = $media_url.$product[$key];
                                if (file_exists($imageResized)) {
                                    $product[$key] = $imageResized;
                                }else{
                                    if(empty($product[$key]) || $product[$key] == "no_selection") {
                                        $placeholder_image = Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
                                        if(!empty($placeholder_image)) {
                                            $product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
                                        } else {
                                            $product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/image_placeholder");
                                        }
                                    }else {
                                        $product[$key] = $media_url . $product[$key];
                                    }
                                }
                            }
                        } else {
                            $placeholder_image = Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
                            if(!empty($placeholder_image)) {
                                $product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/small_image_placeholder");
                            } else {
                                $product[$key] = $media_url .'/placeholder/' .Mage::getStoreConfig("catalog/placeholder/image_placeholder");
                            }
                        }
                        break;
                    case "salePrice":
                        // Default to 0 if price can't be determined
                        $product['salePrice'] = 0;
                        $tax_class_id = "";
                        if ($item->getData("tax_class_id") !== null) {
                            $tax_class_id = $item->getData("tax_class_id");
                        } else if ($parent) {
                            $tax_class_id = $parent->getData("tax_class_id");
                        }else {
                            $tax_class_id = "";
                        }

                        if ($parent && $parent->getData("type_id") == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                            // Calculate configurable product price based on option values
                            $fprice = $parent->getFinalPrice();
                            $price = (isset($fprice)) ? $fprice: $parent->getData("price");

                            // show low price for config products
                            $product['startPrice'] = $this->processPrice($price , $tax_class_id, $parent);

                            // also send sale price for sorting and filters for klevu
                            $product['salePrice'] = $this->processPrice($price , $tax_class_id, $parent);
                        } else {
                            // Use price index prices to set the product price and start/end prices if available
                            // Falling back to product price attribute if not
                            if ($item) {

                                // Always use minimum price as the sale price as it's the most accurate
                                $product['salePrice'] = $this->processPrice($item->getFinalPrice(), $tax_class_id, $item);

                                if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                                    Mage::helper('klevu_search')->getGroupProductMinPrice($item,$this->getStore());
                                    $sPrice = $item->getFinalPrice();
                                    $product['startPrice'] = $this->processPrice($sPrice, $tax_class_id, $item);
                                    $product["salePrice"] = $this->processPrice($sPrice, $tax_class_id, $item);
                                }

                                if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                                    list($minimalPrice, $maximalPrice) = Mage::helper('klevu_search')->getBundleProductPrices($item,$this->getStore());
                                    $product["salePrice"] = $this->processPrice($minimalPrice, $tax_class_id, $item);
                                    $product['startPrice'] = $this->processPrice($minimalPrice, $tax_class_id, $item);
                                    $product['toPrice'] = $this->processPrice($maximalPrice, $tax_class_id, $item);
                                }

                            } else {
                                if ($item->getData("price") !== null) {
                                    $product["salePrice"] = $this->processPrice($item->getData("price"), $tax_class_id, $item);
                                } else if ($parent) {
                                    $product["salePrice"] = $this->processPrice($parent->getData("price"), $tax_class_id, $parent);
                                }
                            }
                        }

                        break;
                    case "price":
                        // Default to 0 if price can't be determined
                        $product['price'] = 0;
                        $tax_class_id = "";
                        if ($parent && $parent->getData("type_id") == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                            // Calculate configurable product price based on option values
                            $orgPrice = $parent->getPrice();
                            $price = (isset($orgPrice)) ? $orgPrice: $parent->getData("price");

                            // also send sale price for sorting and filters for klevu
                            $product['price'] = $this->processPrice($price , $tax_class_id, $parent);
                        } else {
                            // Use price index prices to set the product price and start/end prices if available
                            // Falling back to product price attribute if not
                            if ($item) {

                                // Always use minimum price as the sale price as it's the most accurate
                                $product['price'] = $this->processPrice($item->getPrice(), $tax_class_id, $item);

                                if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                                    // Get the group product original price
                                    Mage::helper('klevu_search')->getGroupProductOriginalPrice($item,$this->getStore());
                                    $sPrice = $item->getPrice();
                                    $product["price"] = $this->processPrice($sPrice, $tax_class_id, $item);
                                }

                                if ($item->getData('type_id') == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {

                                    // product detail page always shows final price as price so we also taken final price as original price only for bundle product
                                    list($minimalPrice, $maximalPrice) = Mage::helper('klevu_search')->getBundleProductPrices($item,$this->getStore());
                                    $product["price"] = $this->processPrice($minimalPrice, $tax_class_id, $item);
                                }

                            } else {
                                if ($item->getData("price") !== null) {
                                    $product["price"] = $this->processPrice($item->getData("price"), $tax_class_id, $item);
                                } else if ($parent) {
                                    $product["price"] = $this->processPrice($parent->getData("price"), $tax_class_id, $parent);
                                }
                            }
                        }
                        break;
                    default:
                        foreach ($attributes as $attribute) {
                            if ($item->getData($attribute)) {
                                $product[$key] = $this->getAttributeData($attribute, $item->getData($attribute));
                                break;
                            } else if ($parent && $parent->getData($attribute)) {
                                $product[$key] = $this->getAttributeData($attribute, $parent->getData($attribute));
                                break;
                            }
                        }
                }
            }

            // Add non-attribute data
            $product['currency'] = $currency;

            if ($parent) {
                $product['category'] = $this->getLongestPathCategoryName($parent->getCategoryIds());
                $product['category_ids'] = implode(';',$parent->getCategoryIds());
                $product['listCategory'] = $this->getCategoryNames($parent->getCategoryIds());
            } else if ($item->getCategoryIds()) {
                $product['category'] = $this->getLongestPathCategoryName($item->getCategoryIds());
                $product['category_ids'] = implode(';',$item->getCategoryIds());
                $product['listCategory'] = $this->getCategoryNames($item->getCategoryIds());
            } else {
                $product['category'] = "";
                $product['listCategory'] = "KLEVU_PRODUCT";
            }


            if ($parent) {
                //Get the price based on customer group
                $product['groupPrices'] = $this->getGroupPrices($parent);
            } else if($item) {
                $product['groupPrices'] = $this->getGroupPrices($item);
            } else {
                $product['groupPrices'] = "";
            }



            // Use the parent URL if the product is invisible (and has a parent) and
            // use a URL rewrite if one exists, falling back to catalog/product/view
            if (isset($visibility_data[$product['product_id']]) && !$visibility_data[$product['product_id']] && $parent) {
                $product['url'] = $base_url . (
                    (isset($url_rewrite_data[$product['parent_id']])) ?
                        $url_rewrite_data[$product['parent_id']] :
                        "catalog/product/view/id/" . $product['parent_id']
                    );
            } else {
                if($parent) {
                    $product['url'] = $base_url . (
                        (isset($url_rewrite_data[$product['parent_id']])) ?
                            $url_rewrite_data[$product['parent_id']] :
                            "catalog/product/view/id/" . $product['parent_id']
                        );
                } else {
                    $product['url'] = $base_url . (
                        (isset($url_rewrite_data[$product['product_id']])) ?
                            $url_rewrite_data[$product['product_id']] :
                            "catalog/product/view/id/" . $product['product_id']
                        );
                }
            }

            // Add stock data
            $product['inStock'] = ($stock_data[$product['product_id']]) ? "yes" : "no";


            // Configurable product relation
            if ($product['parent_id'] != 0) {
                $product['itemGroupId'] = $product['parent_id'];
            }

            // Set ID data
            $product['id'] = Mage::helper('klevu_search')->getKlevuProductId($product['product_id'], $product['parent_id']);


            if($item) {
                $item->clearInstance();
                $item = null;
            }

            if($parent) {
                if(!$config->getCollectionMethod()) {
                    $parent->clearInstance();
                    $parent = null;
                }
            }
            unset($product['product_id']);
            unset($product['parent_id']);
            $rc++;
        }

        return $this;
    }


    /**
     * Given a list of category IDs, return the name of the category
     * in that list that has the longest path.
     *
     * @param array $categories
     *
     * @return string
     *
     * Hassan: This function overrided just to remove all categories present in the category chain
     * like: before we are passing this to product sync data in klevu: "category":"[women, clothing, dresses, evening dresses]"
     * after that "category":"[women, evening dresses]"
     *
     */
    protected function getLongestPathCategoryName(array $categories)
    {
        $category_paths = $this->getCategoryPaths();

        $length = 0;
        $name = "";
        foreach ($categories as $id) {
            if (isset($category_paths[$id])) {
                $name .= end($category_paths[$id]) . ";";
            }
        }
        $targetedCat = array();
        $arr = explode(';', substr($name, 0, strrpos($name, ";") + 1 - 1));
        $targetedCat[] = reset($arr);
        $targetedCat[] = end($arr);
        return (implode(';', $targetedCat));
    }

    /**
     * This function need to override to remove the reindexing check code commented on L: 558
     *
     *
     * Perform Product Sync on any configured stores, adding new products, updating modified and
     * deleting removed products since last sync.
     */
    public function run() {
        try {

            // reset the flag for fail message
            Mage::getSingleton('core/session')->setKlevuFailedFlag(0);

            // check the status of indexing when collection method selected to sync data
            $config = Mage::helper('klevu_search/config');
            if($config->getCollectionMethod()) {
                if(Mage::helper('klevu_search')->getStatuOfIndexing()) {
                    /*$this->notify(Mage::helper('klevu_search')->__("Product sync failed:One of your Magento indexes is not up-to-date.  Please, rebuild your indexes (see System > Index Management)."),null);
                    Mage::helper('klevu_search')->log(Zend_Log::INFO, "Product sync failed:One of your Magento indexes is not up-to-date.  Please, rebuild your indexes (see System > Index Management).");

                    return true;*/
                }
            }

            /* mark for update special price product */
            $this->markProductForUpdate();

            /* update boosting rule event */
            try {
                Mage::helper('klevu_search')->log(Zend_Log::INFO, "Boosting rule update is started");
                Mage::dispatchEvent('update_rule_of_products', array());
            } catch(Exception $e) {
                Mage::helper('klevu_search')->log(Zend_Log::WARN, "Unable to update boosting rule");
            }

            // Sync Data only for selected store from config wizard
            $firstSync = Mage::getSingleton('klevu_search/session')->getFirstSync();

            if(!empty($firstSync)){
                /** @var Mage_Core_Model_Store $store */
                $this->reset();
                $onestore = Mage::app()->getStore($firstSync);
                if (!$this->setupSession($onestore)) {
                    return;
                }

                $this->syncData($onestore);
                return;
            }

            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(Zend_Log::INFO, "Stopping because another copy is already running.");
                return;
            }

            $stores = Mage::app()->getStores();


            foreach ($stores as $store) {
                $this->reset();
                if (!$this->setupSession($store)) {
                    continue;
                }
                $this->syncData($store);
            }

            // update rating flag after all store view sync
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if($rating_upgrade_flag==0) {
                $config->saveRatingUpgradeFlag(1);
            }
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * This function created to sync All data store wise
     *
     * @param $storeCodesToSync Array of store codes
     * @throws Exception
     */
    public function syncStores($storeCodesToSync) {

        try {

            // reset the flag for fail message
            Mage::getSingleton('core/session')->setKlevuFailedFlag(0);

            // check the status of indexing when collection method selected to sync data
            $config = Mage::helper('klevu_search/config');

            $stores = Mage::app()->getStores();
            foreach ($stores as $store) {
                if(in_array($store->getCode(),$storeCodesToSync)){
                    $this->markAllProductsForUpdate($store->getId());
                }
            }

            /* update boosting rule event */
            try {
                Mage::helper('klevu_search')->log(Zend_Log::INFO, "Boosting rule update is started");
                Mage::dispatchEvent('update_rule_of_products', array());
            } catch(Exception $e) {
                Mage::helper('klevu_search')->log(Zend_Log::WARN, "Unable to update boosting rule");
            }

            // Sync Data only for selected store from config wizard
            $firstSync = Mage::getSingleton('klevu_search/session')->getFirstSync();

            if(!empty($firstSync)){
                /** @var Mage_Core_Model_Store $store */
                $this->reset();
                $onestore = Mage::app()->getStore($firstSync);
                if (!$this->setupSession($onestore)) {
                    return;
                }
                $this->syncData($onestore);
                return;
            }

            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(Zend_Log::INFO, "Stopping because another copy is already running.");
                return;
            }

            $stores = Mage::app()->getStores();
            $syncedStores = array();
            foreach ($stores as $store) {
                if(in_array($store->getCode(),$storeCodesToSync)){
                    $this->reset();
                    if (!$this->setupSession($store)) {
                        continue;
                    }
                    $this->syncData($store);
                    $syncedStores[] = $store->getCode();
                }
            }

            // update rating flag after all store view sync
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if($rating_upgrade_flag==0) {
                $config->saveRatingUpgradeFlag(1);
            }
            return $syncedStores;
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            Mage::helper('klevu_search')->log(Zend_Log::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }

}
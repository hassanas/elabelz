<?php
/**
 * Progos
 * @package   Progos Product
 * @description Extended for the data of sorting order into the feed.
 */

class Progos_FeedExport_Model_Feed_Generator_Pattern_Product extends Mirasvit_FeedExport_Model_Feed_Generator_Pattern_Product
{
    private static $_parentProductsCache = array();
    private $_dynamicCategory = array();

    public function getValue($pattern, $product)
    {
        $value = null;
        $pattern = $this->parsePattern($pattern);

        $this->evalValue($pattern, $value, $product);

        if ($pattern['type'] == 'parent') {
            $product = $this->getParentProduct($product);
        } elseif ($pattern['type'] == 'only_parent') {
            $product = $this->getParentProduct($product, true);
        }

        if (in_array($pattern['type'], array('grouped', 'salable_grouped'))) {
            $products = $this->_getChildProducts($product, ($pattern['type'] == 'salable_grouped'));
            $values = array();
            $childPattern = $pattern;
            $childPattern['type'] = null;
            foreach ($products as $child) {
                $child = $child->load($child->getId());
                $value = $this->getValue($childPattern, $child);
                if ($value) {
                    $values[] = $value;
                }
            }

            $value = implode(',', $values);

            return $value;
        }

        switch ($pattern['key']) {
            case 'url':
                $value = Mage::helper('feedexport')->getProductUrl($product, $this->getFeed()->getStoreId());

                if ($product->getConfigOptions() && 0) { //enable if nessesary
                    $value .= strpos($value, '?') !== false ? '&' : '?';
                    $value .= $product->getConfigOptions();
                }

                if ($this->getFeed()) {
                    $getParams = array();

                    if ($this->getFeed()->getReportEnabled()) {
                        $getParams['fee'] = $this->getFeed()->getId();
                        $getParams['fep'] = $product->getId();
                    }

                    $patternModel = Mage::getSingleton('feedexport/feed_generator_pattern');
                    if ($this->getFeed()->getGaSource()) {
                        $getParams['utm_source'] = $patternModel->getPatternValue($this->getFeed()->getGaSource(), 'product', $product);
                    }
                    if ($this->getFeed()->getGaMedium()) {
                        $getParams['utm_medium'] = $patternModel->getPatternValue($this->getFeed()->getGaMedium(), 'product', $product);
                    }
                    if ($this->getFeed()->getGaName()) {
                        $getParams['utm_campaign'] = $patternModel->getPatternValue($this->getFeed()->getGaName(), 'product', $product);
                    }
                    if ($this->getFeed()->getGaTerm()) {
                        $getParams['utm_term'] = $patternModel->getPatternValue($this->getFeed()->getGaTerm(), 'product', $product);
                    }
                    if ($this->getFeed()->getGaContent()) {
                        $getParams['utm_content'] = $patternModel->getPatternValue($this->getFeed()->getGaContent(), 'product', $product);
                    }

                    if (count($getParams)) {
                        $value .= strpos($value, '?') !== false ? '&' : '?';
                        $value .= http_build_query($getParams);
                    }
                }

                break;

            case 'image':
            case 'thumbnail':
            case 'small_image':
                $this->imageValue($pattern, $value, $product);
                break;

            case 'image1':
            case 'image2':
            case 'image3':
            case 'image4':
            case 'image5':
            case 'image6':
            case 'image7':
            case 'image8':
            case 'image9':
            case 'image10':
            case 'image11':
            case 'image12':
            case 'image13':
            case 'image14':
            case 'image15':
                $this->imageGalleryValue($pattern, $value, $product);

                break;

            case 'qty':
                $stockItem = $product->getStockItem();
                if (!($stockItem && $stockItem->getData('item_id'))) {
                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
                }
                if ($stockItem && $stockItem->getData('item_id')) {
                    $product->setStockItem($stockItem);
                    $value = ceil($stockItem->getQty());
                } else {
                    $value = 0;
                }
                $value = intval($value);

                break;

            case 'parent_qty':
                $value = 0;
                if ($product->getTypeId() == 'configurable') {
                    $childIds = Mage::getModel('catalog/product_type_configurable')
                        ->getChildrenIds($product->getId());
                    if (is_array($childIds) && isset($childIds[0])) {
                        $childCollection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('entity_id', array('in' => $childIds[0]))
                            ->joinField(
                                'qty',
                                'cataloginventory/stock_item',
                                'qty',
                                'product_id = entity_id',
                                '{{table}}.stock_id = 1',
                                'left'
                            );
                        foreach ($childCollection as $child) {
                            if ($child->getIsSalable() == 1) {
                                $value += $child->getQty();
                            }
                        }
                    }
                }
                break;

            case 'is_in_stock':
                $stockItem = $product->getStockItem();
                if (!($stockItem && $stockItem->getData('item_id'))) {
                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
                }
                if ($stockItem) {
                    $value = $stockItem->getIsInStock();
                } else {
                    $value = 0;
                }
                break;

            case 'category_id':
                $this->_prepareProductCategory($product);
                $value = $product->getData('category_id');
                break;

            case 'best_sellers':
                /*Resource Model of the Amasty extension for am_sorting_bestsellers not created. So used custom Query here.*/
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $storeId = $this->getFeed()->getStoreId();
                $am_sorting_bestsellers = $resource->getTableName('am_sorting_bestsellers');
                $id = $product->getId();
                $query = 'SELECT bestsellers FROM '. $am_sorting_bestsellers . ' WHERE id=' . $product->getId() . ' AND store_id=' . $storeId;
                $results = $readConnection->fetchCol($query);
                if(!empty($results))
                    $value =  $results[0]; //Retun total number of count.
                else
                    $value = '';
                break;

            case 'most_viewed':
                /*Resource Model of the Amasty extension for am_sorting_most_viewed not created. So used custom Query here.*/
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');
                $storeId = $this->getFeed()->getStoreId();
                $am_sorting_most_viewed = $resource->getTableName('am_sorting_most_viewed');
                $id = $product->getId();
                $query = 'SELECT most_viewed FROM '. $am_sorting_most_viewed . ' WHERE id=' . $product->getId() . ' AND store_id=' . $storeId;
                $results = $readConnection->fetchCol($query);
                if(!empty($results))
                    $value =  $results[0]; //Retun total number of count.
                else
                    $value = '';
                break;
            case 'category_ids':
                $value = implode(', ', $product->getCategoryIds());
                break;
            case 'manufacturer_id':
                $value = $product->getManufacturer();
                break;
            case 'category':
                $this->_prepareProductCategory($product);
                $value = $product->getCategory();
                break;

            case 'category_url':
                $this->_prepareProductCategory($product);
                if ($product->getCategoryModel()) {
                    $value = $product->getCategoryModel()->getUrl();
                }
                break;

            case 'category_path':
                $this->_prepareProductCategory($product);
                $value = $product->getCategoryPath();
                break;

            case 'category_paths':
                $this->_prepareProductCategories($product);
                $value = $product->getCategoryPaths();
                break;

            case 'price':
                $value = Mage::helper('tax')->getPrice($product, $product->getPrice());
                break;

            case 'final_price':
                if ($product->getTypeId() == 'bundle') {
                    $bundle = Mage::getModel('bundle/product_price');
                    $prices = $bundle->getTotalPrices($product);
                    if (isset($prices[0])) {
                        $value = $prices[0];
                        break;
                    }
                } else {
                    $value = Mage::helper('tax')->getPrice($product, $product->getFinalPrice());
                }

                break;

            case 'store_price':
                $value = $this->getStore()->convertPrice($product->getFinalPrice(), false, false);
                break;

            case 'base_price':
                $value = $product->getPrice();
                break;

            case 'tier_price':
                $tierPrice = $product->getTierPrice();
                if (count($tierPrice)) {
                    $value = $tierPrice[0]['price'];
                }
                break;

            case 'min_price':
                $prices = array();
                $productType = $product->getTypeId();
                if ($productType == 'bundle') {
                    $bundle = Mage::getModel('bundle/product_price');
                    $prices = $bundle->getTotalPrices($product);
                    if (isset($prices[0])) {
                        $value = $prices[0];
                        break;
                    }
                } elseif ($productType == 'grouped') {
                    $childIds = $product->getTypeInstance(true)
                        ->getAssociatedProducts($product);
                    foreach ($childIds as $child) {
                        if ($child->getIsSalable()==1) {
                            $prices[] = $child->getFinalPrice();
                        }
                    }
                    if (isset($prices[0])) {
                        $value = min($prices);
                        break;
                    }
                } else {
                    $value = Mage::helper('tax')->getPrice($product, $product->getFinalPrice());
                }

                $tierPrice = $product->getTierPrice();

                if (count($tierPrice)) {
                    foreach ($tierPrice as $key => $it) {
                        if ($value > $it['price'] && $it['price'] > 0) {
                            $value = $it['price'];
                        }
                    }
                }
                break;

            case 'group_price':
                $groupPrice = $product->getData('group_price');
                if (count($groupPrice)) {
                    $value = $groupPrice[0]['price'];
                }
                break;

            case 'attribute_set':
                $attributeSetModel = Mage::getModel('eav/entity_attribute_set');
                $attributeSetModel->load($product->getAttributeSetId());

                $value = $attributeSetModel->getAttributeSetName();
                break;

            case 'weight':
                if ($product->getTypeId() == 'bundle') {
                    $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
                        $product->getTypeInstance(true)->getOptionsIds($product), $product
                    );
                    $productIds = array(0);
                    $productQts = array();
                    foreach ($selectionCollection as $option) {
                        $productIds[] = $option->product_id;
                        $productQts[$option->product_id] = $option->getSelectionQty();
                    }
                    $collection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('weight')
                        ->addFieldToFilter('entity_id', array('in' => $productIds));
                    $value = 0;
                    foreach ($collection as $subProduct) {
                        $weight = $subProduct->getWeight();
                        $qty = $productQts[$subProduct->getEntityId()];
                        intval($qty > 0) ? intval($qty) : 1;
                        $value += $weight * $qty;
                    }
                } else {
                    $value = $product->getData('weight');
                }
                break;

            case 'rating_summary':
                $summaryData = Mage::getModel('review/review_summary')->load($product->getId());
                $value = $summaryData->getRatingSummary() * 0.05;
                break;

            case 'reviews_count':
                $summaryData = Mage::getModel('review/review_summary')->load($product->getId());
                $value = $summaryData->getReviewsCount();
                break;

            default:
                if (substr($pattern['key'], 0, strlen('group_price')) == 'group_price') {
                    $custId = substr($pattern['key'], strlen('group_price'));
                    $groupPrice = $product->getData('group_price');
                    if (is_array($groupPrice)) {
                        foreach ($groupPrice as $key => $price) {
                            if ($price['cust_group'] == $custId) {
                                $value = $price['price'];
                            }
                        }
                    }
                    break;
                }

                $attribute = $this->_getProductAttribute($pattern['key']);
                if ($attribute) {
                    if ($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect') {
                        $value = $product->getResource()
                            ->getAttribute($pattern['key'])
                            ->getSource()
                            ->getOptionText($product->getData($pattern['key']));
                        $value = implode(', ', (array) $value);
                    } else {
                        $value = $product->getData($pattern['key']);
                    }
                } else {
                    if ($product->hasData($pattern['key'])) {
                        $value = $product->getData($pattern['key']);
                    }
                }
        }

        $this->dynamicAttributeValue($pattern, $value, $product);
        $this->dynamicCategoryValue($pattern, $value, $product);
        $this->amastyMetaValue($pattern, $value, $product);

        if (!$value || $value == '') {
            if ($pattern['type'] == 'parent_if_empty') {
                $parent = $this->getParentProduct($product, true);
                $pattern['type'] = '';
                $value = $this->getValue($pattern, $parent);
            }
        }

        $value = $this->applyFormatters($pattern, $value);

        return $value;
    }

}
<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


/*******************************************
Mirasvit
This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
If you wish to customize this module for your needs
Please refer to http://www.magentocommerce.com for more information.
@category Mirasvit
@copyright Copyright (C) 2012 Mirasvit (http://mirasvit.com.ua), Vladimir Drok <dva@mirasvit.com.ua>, Alexander Drok<alexander@mirasvit.com.ua>
*******************************************/

class Mirasvit_Seo_Model_System_Template_Worker extends Varien_Object
{
    protected $maxPerStep = 500;
    protected $totalNumber;
    protected $isEnterprise = false;

    public function run() {
        $this->totalNumber = $this->getTotalProductNumber();
        if (($this->getStep()-1)*$this->maxPerStep >= $this->totalNumber) {
            return false;
        }
        $this->process();
        return true;
    }

    protected function getTotalProductNumber()
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix.'catalog_product_entity');
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->columns('COUNT(*)');
        $number = $connection->fetchOne($select);
        return $number;
    }

    public function formatUrlKey($str)
    {
        $urlKey = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($str));
        $urlKey = strtolower($urlKey);
        $urlKey = trim($urlKey, '-');
        return $urlKey;
    }

    public function getMaxPerStep() {
        return $this->maxPerStep;
    }

    public function getCurrentNumber() {
        $c = $this->getStep() * $this->getMaxPerStep();
        if ($c > $this->totalNumber) {
            return $this->totalNumber;
        } else {
            return $c;
        }
    }

    public function getTotalNumber() {
        return $this->totalNumber;
    }

    public function prepareUrlKeys($connection, $urlKey, $tablePrefix, $urlKeyTable) {  //for Magento Enterprise only
        if ($urlKey) {
            $selectAllStores = $connection->select()->from($tablePrefix.$urlKeyTable)->
                                    where("value LIKE ?", $urlKey."%");
            $rowAllStores    = $connection->fetchAll($selectAllStores);
            if($rowAllStores) {
                $urlKeyValues = array();
                $addNewKey = false;
                foreach ($rowAllStores as $valueStores) {
                    if ($valueStores['value'] == $urlKey) {
                        $addNewKey = true;
                    }
                    $urlKeyValues[] =  $valueStores['value'];
                }
                if ($addNewKey) { //True if such url key exist. We can't add the same url key because value is UNIQUE for magento Enterprise.
                    $i = 1;
                    do {
                        $urlNewKey = $urlKey . '-' . $i;
                        $i++;
                    } while (in_array($urlNewKey, $urlKeyValues));
                    $urlKey = $urlNewKey;
                    return $urlKey;
                }
            }
        }

        return false;
    }

    public function process() {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
        $select = $connection->select()->from($tablePrefix.'eav_entity_type')->where("entity_type_code = 'catalog_product'");
        $productTypeId = $connection->fetchOne($select);
        $select = $connection->select()->from($tablePrefix.'eav_attribute')->where("entity_type_id = $productTypeId AND (attribute_code = 'url_path')");
        $urlPathId = $connection->fetchOne($select);
        $select = $connection->select()->from($tablePrefix.'eav_attribute')->where("entity_type_id = $productTypeId AND (attribute_code = 'url_key')");
        $urlKeyId = $connection->fetchOne($select);

        $config = Mage::getSingleton('seo/config');
        if (Mage::helper('mstcore/version')->getEdition() == 'ee' && Mage::getVersion() >= '1.13.0.0') {
            $stores = Mage::app()->getStores(true);
            $urlKeyTable = 'catalog_product_entity_url_key';
            $this->isEnterprise = true;
        } else {
            $stores = Mage::app()->getStores();
            $urlKeyTable = 'catalog_product_entity_varchar';
        }
        foreach ($stores as $store) {
            $products = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('visibility', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH))
                        ->setCurPage($this->getStep())
                        ->setPageSize($this->maxPerStep)
                        ->setStore($store);
            foreach ($products as $product) {
                $urlKeyTemplate = $config->getProductUrlKey($store);
                if ($this->isEnterprise) {
                    if (empty($urlKeyTemplate)) { // if "Product URL Key Template" is empty we will create [product_name] template
                        $urlKeyTemplate = '[product_name]';
                    }
                }
                $storeId = $store->getId();
                $templ = Mage::getModel('seo/object_producturl')
                            ->setProduct($product)
                            ->setStore($store);
                $urlKey = $templ->parse($urlKeyTemplate);
                $urlKey = $this->formatUrlKey($urlKey);

                if ($product->getUrlKey() == $urlKey) {
                    continue;
                }

                $urlSuffix = Mage::getStoreConfig('catalog/seo/product_url_suffix', $store);

                $select = $connection->select()->from($tablePrefix.$urlKeyTable)->
                            where("entity_type_id = $productTypeId AND attribute_id = $urlKeyId AND entity_id = {$product->getId()} AND store_id = {$storeId}");

                $row = $connection->fetchRow($select); //echo $select;die;
                if ($row) {
                    if ($this->isEnterprise) {
                        if ($urlKeyPrepared = $this->prepareUrlKeys($connection, $urlKey, $tablePrefix, $urlKeyTable)) {
                            $urlKey = $urlKeyPrepared;
                        }
                    }

                    $connection->update($tablePrefix.$urlKeyTable, array('value' => $urlKey), "entity_type_id = $productTypeId AND attribute_id = $urlKeyId AND entity_id = {$product->getId()} AND store_id = {$storeId}");

                } else {
                    if(!$this->isEnterprise) {
                        $data = array(
                            'entity_type_id' => $productTypeId,
                            'attribute_id' => $urlKeyId,
                            'entity_id' => $product->getId(),
                            'store_id' => $storeId,
                            'value' => $urlKey
                        );

                        $connection->insert($tablePrefix.$urlKeyTable, $data);
                    }
                }

                if(!$this->isEnterprise) {
                    $select = $connection->select()->from($tablePrefix.$urlKeyTable)->
                            where("entity_type_id = $productTypeId AND attribute_id = $urlPathId AND entity_id = {$product->getId()} AND store_id = {$storeId}");
                    $row = $connection->fetchRow($select);
                    if ($row) {
                        $connection->update($tablePrefix.$urlKeyTable, array('value' => $urlKey . $urlSuffix), "entity_type_id = $productTypeId AND attribute_id = $urlPathId AND entity_id = {$product->getId()} AND store_id = {$storeId}");
                    } else {
                        $data = array(
                            'entity_type_id' => $productTypeId,
                            'attribute_id' => $urlPathId,
                            'entity_id' => $product->getId(),
                            'store_id' => $storeId,
                            'value' => $urlKey.$urlSuffix
                        );
                        $connection->insert($tablePrefix.$urlKeyTable, $data);
                    }
                }
            }
        }
    }
}
<?php

require_once 'abstract.php';

class Progos_Shell_FixImageBasedOnColor extends Mage_Shell_Abstract
{
    const CONFIG_XML_PATH_UPDATED_PRODUCT_IDS = 'shell/fix_image_based_on_color/updated_product_ids';

    const PRODUCT_SPLIT_AMOUNT = 5000;

    protected $_collection = null;

    protected $_totalProductCount = null;

    protected $_updatedProductIds = array();

    /** @var bool This has to be used because counting updated product ids doesn't work as expected */
    protected $_allProductsUpdated = false;

    /**
     * Run script
     */
    public function run()
    {
        error_reporting(0);
        ini_set('display_errors', '0');

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $this->_fixImages();

        $productsUpdatedPercent = !$this->_allProductsUpdated ? number_format((count($this->getUpdatedProductIds()) * 100) / $this->_totalProductCount, 2) : 100;
        $endTime = (microtime(true) - $startTime) / 60;
        $endMemory = (memory_get_usage() - $startMemory) / 1000000;

        echo "Products updated: {$productsUpdatedPercent}% of {$this->_totalProductCount}\n";
        echo "Time: {$endTime}  minutes\n";
        echo "Memory: {$endMemory} megabytes\n";
    }

    /**
     * Goes through configurable products and fixes the image gallery
     *
     * @return void
     */
    protected function _fixImages()
    {
        $updatedProductIds = $this->getUpdatedProductIds();
        $productAction = $this->getProductAction();
        $collection = $this->getProductCollection();
        $this->_splitCollection();

        $imageAttributes = array('image', 'small_image', 'thumbnail');
        $connection = $this->_getConnection();

        foreach ($collection as $product) {
            $associatedProducts = $product->getTypeInstance(true)->getUsedProducts($imageAttributes, $product);

            $mediaGalleries = $connection->query("SELECT * FROM `catalog_product_entity_media_gallery` WHERE `entity_id` = {$product->getId()}")->fetchAll();
            foreach ($mediaGalleries as $mediaGallery) {
                $galleryValues = $connection->query("SELECT * FROM `catalog_product_entity_media_gallery_value` where `value_id` = {$mediaGallery['value_id']}")->fetchAll();

                /** @var Mage_Catalog_Model_Product $associatedProduct */
                foreach ($associatedProducts as $associatedProduct) {
                    $color = $this->getProductColorValue($associatedProduct->getEntityId());

                    // Continue if the main product image doesn't contain associated product color value
                    if (!strpos(strtolower($mediaGallery['value']), strtolower($color)) !== false) continue;

                    // Add media gallery for the associated product
                    $connection->query("
                        INSERT INTO
                            `catalog_product_entity_media_gallery`
                        SET 
                            `attribute_id` = {$mediaGallery['attribute_id']}, 
                            `entity_id` = {$associatedProduct->getEntityId()}, 
                            `value` = '{$mediaGallery['value']}'
                    ");

                    $lastInsertId = $connection->lastInsertId();

                    // Add values for previously inserted media gallery
                    foreach ($galleryValues as $value) {
                        $connection->query("
                            INSERT INTO
                              `catalog_product_entity_media_gallery_value`
                            SET 
                                `value_id` = {$lastInsertId},
                                `store_id` = {$value['store_id']},
                                `label` = '{$value['label']}',
                                `position` = {$value['position']},
                                `disabled` = {$value['disabled']}
                        ");
                    }
                }
            }

            // Get main product's image data
            $data = array();
            foreach ($imageAttributes as $attribute) {
                $data[$attribute] = $product->getData($attribute);
            }

            // Assign the image data to child products
            foreach ($associatedProducts as $associatedProduct) {
                $productAction->updateAttributes(array($associatedProduct->getEntityId()), $data, 0);

                // Fix the default media gallery not being set correctly
                Mage::helper('progos_catalog')->resetDefaultMediaGalleryImage($associatedProduct, $productAction);
            }

            $updatedProductIds[] = $product->getId();
            $this->_showProgress(count(array_diff($updatedProductIds, $this->_updatedProductIds)));

            $product->clearInstance();
        }

        $this->saveUpdatedProductIds($updatedProductIds);
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getProductCollection()
    {
        if (!$this->_collection) {
            $this->_collection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToFilter('type_id', array('eq' => Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE))
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('small_image')
                ->addAttributeToSelect('thumbnail')
                ->setOrder('sku', 'ASC');

            $this->_totalProductCount = $this->_collection->getSize();
        }

        return $this->_collection;
    }

    /**
     * @param int $entityId
     * @return string|null
     */
    public function getProductColorValue($entityId)
    {
        $value = (string)Mage::getResourceModel('catalog/product')->getAttributeRawValue($entityId, 'color', 0);

        if ($value) {
            return Mage::getModel('catalog/product')->getResource()->getAttribute('color')->getSource()->getOptionText($value);
        }
        return null;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Product_Action
     */
    public function getProductAction()
    {
        return Mage::getSingleton('catalog/product_action')->getResource();
    }

    /**
     * @return array
     */
    public function getUpdatedProductIds()
    {
        if (!$this->_updatedProductIds) {
            $configValue = Mage::getStoreConfig(self::CONFIG_XML_PATH_UPDATED_PRODUCT_IDS);

            $this->_updatedProductIds = $configValue ? explode(',', $configValue) : array();
        }

        return $this->_updatedProductIds;
    }

    /**
     * Saves updated product ids from the database, deletes if all products are updated
     *
     * @param array $updatedProductIds
     * @return void
     */
    public function saveUpdatedProductIds($updatedProductIds)
    {
        if (!is_array($updatedProductIds)) return;

        if (count($updatedProductIds) >= $this->_totalProductCount) {
            Mage::getConfig()->deleteConfig(self::CONFIG_XML_PATH_UPDATED_PRODUCT_IDS);
            $this->_updatedProductIds = array();
            $this->_allProductsUpdated = true;
        } else {
            Mage::getConfig()->saveConfig(self::CONFIG_XML_PATH_UPDATED_PRODUCT_IDS, implode(',', $updatedProductIds));
            $this->_updatedProductIds = $updatedProductIds;
        }
    }

    /**
     * Limits the collection item count and ignores already updated products
     *
     * @return void
     */
    protected function _splitCollection()
    {
        $collection = $this->getProductCollection();

        if ($updatedProductIds = $this->getUpdatedProductIds()) {
            $collection->addAttributeToFilter('entity_id', array('nin' => $updatedProductIds));
        }

        $collection->setPageSize(self::PRODUCT_SPLIT_AMOUNT);

        $this->_collection = $collection;
    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getConnection()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    /**
     * Output the percentage bar of the progress done based on collection size to process
     *
     * @var int $_percentDone Store the percent value so we can output the bar only when it changes
     */
    protected $_percentDone = 0;
    protected function _showProgress($count)
    {
        $percentDone = floor($count * 100 / self::PRODUCT_SPLIT_AMOUNT);

        $percentageBar = str_repeat('=', floor($percentDone / 2));
        $percentageBar .= str_repeat(' ', 50 - floor($percentDone / 2));

        if ($this->_percentDone < $percentDone) {
            $this->_percentDone = $percentDone;
            echo "[{$percentageBar}] {$percentDone}%\r";
        }
    }
}

$shell = new Progos_Shell_FixImageBasedOnColor();
$shell->run();
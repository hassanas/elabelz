<?php
/**
 * Progos_Shell
 *
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */

require_once 'abstract.php';

/**
 * Class Progos_Shell_RemoveDuplicatedImages
 */
class Progos_Shell_RemoveDuplicatedImages extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        error_reporting(E_ALL);
        ini_set('memory_limit', '10G');
        ini_set('display_errors', '1');

        try {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            // Delete all duplicated media gallery values
            $this->cleanMediaGallery();

            // Image attribute IDs
            $attributeIds = ['85', '86', '87'];

            // Delete all duplicated product images
            foreach ($attributeIds as $attributeId) {
                $this->cleanProductImages($attributeId);
            }

            $endTime = microtime(true) - $startTime;
            $endMemory = memory_get_usage() - $startMemory;

            echo "Time: {$endTime} seconds \n";
            echo "Memory: {$endMemory} bytes \n";
        } catch (Exception $e) {
            echo Mage::helper('core')->__('%s', $e->getMessage());
            exit;
        }
    }

    /**
     * Retrieve core resource model
     *
     * @return Mage_Core_Model_Resource
     */
    protected function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * Creates a connection to resource whenever needed
     *
     * @return Varien_Db_Adapter_Interface
     */
    protected function getConnection()
    {
        return $this->getResource()->getConnection('core_write');
    }

    /**
     * Retrieves name of table in DB
     *
     * @param string $tableName
     * @return string
     */
    public function getTable($tableName)
    {
        return $this->getResource()->getTableName($tableName);
    }

    /**
     * Delete all duplicated media gallery values
     */
    protected function cleanMediaGallery()
    {
        $connection = $this->getConnection();
        $table = $this->getTable('catalog/product_attribute_media_gallery_value');
        $query = $this->getMediaGalleryQuery($table);

        $mediaGalleryValues = $connection->fetchCol($query);
        foreach ($mediaGalleryValues as $mediaGalleryValue) {
            Mage::log($mediaGalleryValue, null, 'clean_images_gallery.log');

            // Delete duplicated media gallery value
            $this->deleteRow($table, $mediaGalleryValue);
        }
    }

    /**
     * Delete duplicated product images
     *
     * @param $attributeId
     * @throws Mage_Api_Exception
     */
    protected function cleanProductImages($attributeId)
    {
        $table = $this->getTable('catalog_product_entity_varchar');

        foreach ($this->getDuplicatedProductImages($table, $attributeId) as $entityId => $images) {
            $defaultImage = '';

            // Delete duplicated image for product
            foreach ($images as $storeId => $value) {
                if ($storeId == 0) {
                    $defaultImage = $value;

                    Mage::log("[{$entityId}] => {$storeId} => {$value}", null, "clean_images_default_{$attributeId}.log");
                } else {
                    if ($defaultImage) {
                        if ($defaultImage == $value || $value == 'no_selection') {
                            $this->deleteImageConfiguration($table, $entityId, $attributeId, $storeId, $value);

                            Mage::log("[{$entityId}] => {$storeId} => {$value}", null, "clean_images_settings_{$attributeId}.log");
                        } else {
                            $product = Mage::helper('catalog/product')->getProduct($entityId, $storeId, null);

                            if ($product->getId()) {
                                $attributes = $product->getTypeInstance(true)->getSetAttributes($product);

                                if (isset($attributes['media_gallery'])) {
                                    $gallery = $attributes['media_gallery'];

                                    if ($gallery->getBackend()->getImage($product, $value)) {
                                        $gallery->getBackend()->removeImage($product, $value);

                                        try {
                                            $product->save();
                                        } catch (Mage_Core_Exception $e) {
                                            throw new Mage_Api_Exception("Image with [{$value}] value can't be removed\n", $e->getMessage());
                                        }

                                        $product->clearInstance();
                                    } else {
                                        Mage::log("Image with [{$value}] value doesn't exist", null, "clean_images_errors_{$attributeId}.log");
                                    }
                                } else {
                                    Mage::log("Media gallery for product:{$entityId} doesn't set", null, "clean_images_errors_{$attributeId}.log");
                                }
                            } else {
                                Mage::log("Product with ID:{$entityId} doesn't exist", null, "clean_images_errors_{$attributeId}.log");
                            }

                            Mage::log("[{$entityId}] => {$storeId} => {$value}", null, "clean_images_duplicated_{$attributeId}.log");
                        }
                    } else {
                        Mage::log("[{$entityId}] => {$storeId} => {$value}", null, "clean_images_other_{$attributeId}.log");
                    }
                }
            }
        }
    }

    /**
     * Prepare all duplicated product images ids and paths
     *
     * @param $table
     * @param $attributeId
     * @return array
     */
    protected function getDuplicatedProductImages($table, $attributeId)
    {
        $imageValues = [];
        $connection = $this->getConnection();

        $query = $this->getDuplicatedProductImageQuery($table, $attributeId);
        $productImages = $connection->fetchAll($query);

        // Get array with all duplicated images values
        foreach ($productImages as $productImage) {
            $imageValues[$productImage['entity_id']][$productImage['store_id']] = $productImage['value'];
        }

        return $imageValues;
    }

    /**
     * Get all duplicated product images as query
     *
     * @param $table
     * @param $attributeId
     * @return mixed
     */
    protected function getDuplicatedProductImageQuery($table, $attributeId)
    {
        return $this->getConnection()
            ->select()
            ->from($table, array('entity_id', 'store_id', 'value'))
            ->where('attribute_id = ?', $attributeId);
    }

    /**
     * Get all duplicated media gallery values as query
     *
     * @param $table
     * @return mixed
     */
    protected function getMediaGalleryQuery($table)
    {
        return $this->getConnection()
            ->select()
            ->from($table, array('value_id'))
            ->where('store_id <> ?', 0);
    }

    /**
     * Delete data row from database table
     *
     * @param $table
     * @param $value
     * @return mixed
     */
    protected function deleteRow($table, $value)
    {
        return $this->getConnection()
            ->delete($table, array(
                'value_id = ?' => $value,
                'store_id <> ?' => 0
            ));
    }

    /**
     * Delete image configuration from product
     *
     * @param $table
     * @param $entityId
     * @param $attributeId
     * @param $storeId
     * @param $value
     * @return int
     */
    protected function deleteImageConfiguration($table, $entityId, $attributeId, $storeId, $value)
    {
        return $this->getConnection()
            ->delete($table, array(
                'entity_id = ?' => $entityId,
                'attribute_id = ?' => $attributeId,
                'store_id = ?' => $storeId,
                'value = ?' => $value
            ));
    }
}

$shell = new Progos_Shell_RemoveDuplicatedImages();
$shell->run();
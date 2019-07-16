<?php

class Progos_Catalog_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Resource_Product_Action|null $productAction Optional pre-loaded Product_Action resource singleton
     * @throws Exception
     */
    public function resetDefaultMediaGalleryImage($product, $productAction = null)
    {
        if (!$productAction) {
            $productAction = Mage::getSingleton('catalog/product_action')->getResource();
        }

        $attribute = $product->getResource()->getAttribute('media_gallery');
        $backend = $attribute->getBackend();
        $backend->afterLoad($product);

        foreach ($product->getMediaGalleryImages() as $image) {
            $attrData = array(
                'image' => $image['file'],
                'thumbnail' => $image['file'],
                'small_image' => $image['file'],
            );

            $productAction->updateAttributes(array($product->getId()), $attrData, 0);
            break;
        }
    }
}
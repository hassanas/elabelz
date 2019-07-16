<?php
/**
 * Highstreet_HSAPI_module
 * This class is created for elabelz to get the configruable product price instead of simple product's
 */
class Highstreet_Elabelz_Model_Products extends Highstreet_Hsapi_Model_Products
{
    public function __construct() {
        parent::__construct();
    }

    /**
     *
     * Gets attributes of a given product object.
     *
     * @param Mage_Catalog_Model_Product ResProduct, a product object
     * @param string Additional_attributes, an string of attributes to get for the product, comma delimited
     * @param bool include_configuration_details, weather to include child products in the product object and configurable attributes (For both configurable products and bundled products). Default value is fale
     * @param bool include_media_gallery, weather to include the media gallery in the product object. Default value is fale
     * @return array Array with information about the product, according to the Attributes array param
     *
     */

    protected function _getProductAttributes($resProduct = false, $additional_attributes = null, $include_configuration_details = false, $include_media_gallery = false) {
        $product = parent::_getProductAttributes($resProduct, $additional_attributes, $include_configuration_details, $include_media_gallery);

        $store = Mage::app()->getStore();


        if ($resProduct->getTypeId() == 'simple') {
            $getProductId = $resProduct->getId();
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($getProductId);
            $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);
            $product["price"] = $parent_product->getPrice();
            $product["special_from_date"] = $parent_product->getSpecialFromDate();
            $product["special_to_date"] = $parent_product->getSpecialToDate();
            $product["special_price"] = $parent_product->getSpecialPrice();
        }
        if($resProduct->getTypeId() == 'configurable' && $include_configuration_details){
            $product["special_from_date"] = $resProduct->getSpecialFromDate();
            $product["special_to_date"] = $resProduct->getSpecialToDate();
        }

        $product["price"] = $store->roundPrice($store->convertPrice($product["price"]));
        
        if ($product["special_price"] != null) {
            $product["special_price"] = $store->roundPrice($store->convertPrice($product["special_price"]));
        }
        
        $this->_convertProductDates($product);

        return $product;
    }
    
    
    /** 
     * Gets media gallery items for a given product id. Returns an array or media gallery items
     *
     * This function explicitly makes new product objects. 
     * During development this was found to be faster then passing a product object and calling "->load('media_gallery')" on the object
     *
     * @param integer Product ID, ID of a product to get media gallery images for
     * @return array Array of media gallery items
     */
    
    public function _getMediaGalleryImagesForProductID($productId = null) {
        if (!$productId) {
            return null;
        }

        $output = array();
        $imageArray = array(); //To avoid Duplicate Image , use initialized array
        $resProduct = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('media_gallery') 
                        ->addAttributeToFilter('entity_id',$productId)->getFirstItem();
        $galleryAttribute = $resProduct->getResource()->getAttribute('media_gallery');
        $getBackendGallery = Mage::getModel('catalog/product_attribute_backend_media')->setAttribute($galleryAttribute);
        $resProduct = Mage::getResourceModel('catalog/product_attribute_backend_media')->loadGallery($resProduct, $getBackendGallery);

        foreach ($resProduct as $key =>$imageData ) {
            
            /*Here we just check no duplicate image came into the media_gallery. For this we are checking here by using temprary array*/
            if( !in_array($imageData["file"], $imageArray) ): //If Image is not exist in $imageArray then it will add into that.
                $imageArray[] = $imageData["file"];
            else:
                continue;
            endif;

            $resProduct = false;
            if ($this->_shouldExcludeImageFromMediaGallery($imageData["file"], $resProduct)) {
                continue;
            }

            if (array_key_exists('file', $imageData) && !strstr($imageData['file'], self::PRODUCTS_MEDIA_PATH)) {
                $imageData['file'] = self::PRODUCTS_MEDIA_PATH . $imageData['file'];
            }
            unset($imageData["path"]);
            unset($imageData["url"]);
            unset($imageData["id"]);
            $output[] = $imageData;
        }
        
        return $output;
    }

}

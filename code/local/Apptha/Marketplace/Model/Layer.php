<?php
	 
class Apptha_Marketplace_Model_Layer extends Mage_Catalog_Model_Layer
{
	function getProductCollection($id,$displayCatProduct,$sortProduct)
     { 
       /**
     * Get Category Name
     */
      $displayCatProduct = $displayCatProduct;

      /**
       * Get Sorting Detail
       */
      $sortProduct = $sortProduct;
      /**
       * Get Id
       */
      $id = $id;
      /**
       * Get Category Collection
       */
      $catagoryModel = Mage::getModel ( 'catalog/category' )->load ( $displayCatProduct );
      $collection = Mage::getResourceModel ( 'catalog/product_collection' );
      $collection->addCategoryFilter ( $catagoryModel );
      /**
       * Filter by Status
       */
      $collection->addAttributeToFilter ( 'status', 1 );
      /**
       * Filter By all
       */
      $collection->addAttributeToSelect ( '*' );
      /**
       * Filter by seller id
       */
      $collection->addAttributeToFilter ( 'seller_id', $id );
      /**
       * Filter by visibilty
       */
      $collection->addAttributeToFilter ( 'visibility', array (
        2,
        3,
        4 
      ) );
      // $collection->addStoreFilter ();
      // $collection->addAttributeToSort ( $sortProduct );
      
      return $collection;
     
     }
}
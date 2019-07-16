<?php
/**
* @category Progos
* @package Progos_Partialindex
* @author Babar Ali <babar.ali@progos.org>
*/

class Progos_Partialindex_Model_Partialindex
{
  /*
   * name: addPartialIndexerProducts
   * description: Add products in partial indexer table
   * @params: $productsIds array()
   * @return void
   */
  function addPartialIndexerProducts($productIds, $sortOrder = 0 ) {
  	$write = Mage::getSingleton('core/resource')->getConnection('core_write');
	$catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
	if(!empty($productIds)) {
	    if( !is_array( $productIds ) ){
            $data["product_id"] = $productIds;
            $data["sort_order"] = $sortOrder;
            $write->insertOnDuplicate($catalogProductPartialindex, $data, array('product_id', 'sort_order'));
        }else {
            foreach ($productIds as $productId) {
                $data["product_id"] = $productId;
                $data["sort_order"] = $sortOrder;
                $write->insertOnDuplicate($catalogProductPartialindex, $data, array('product_id', 'sort_order'));
            }
        }
	}
  }
}
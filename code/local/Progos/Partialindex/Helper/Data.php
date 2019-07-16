<?php
/**
* @category Progos
* @package Progos_Partialindex
* @author Babar Ali <babar.ali@progos.org>
*/

class Progos_Partialindex_Helper_Data extends Mage_Core_Helper_Abstract
{
   /*
   * name: addPartialIndexer
   * description: helper to add products in partial Indexer
   * @params: $productsIds array()
   * @return void
   */
    public function addPartialIndexer($productIds, $sortOrder=0) {
    	Mage::getModel("partialindex/partialindex")->addPartialIndexerProducts($productIds, $sortOrder);		
    }
}
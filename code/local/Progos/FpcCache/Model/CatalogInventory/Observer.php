<?php

/**
* @category Progos
* @package Progos_FpcCache
* @author Gul Muhammad <gul.muhamamd@progos.org>
*/
class Progos_FpcCache_Model_CatalogInventory_Observer extends Aitoc_Aiteditablecart_Model_Rewrite_CatalogInventoryObserver
{
    const PARTIAL_SORT_ORDER = 9999;
    public function reindexQuoteInventory($observer)
    {
        // Reindex quote ids
        $quote = $observer->getEvent()->getQuote();
        $productIds = array();
        $productIdsStock = array();
        $configIds = array();
        $itemsIds = array();
        foreach ($quote->getAllItems() as $item) {
            $productIds[$item->getProductId()] = $item->getProductId();
            if( !empty($item->getParentItemId()) ){
                $productIdsStock[] = $item->getProductId();
                if( isset( $itemsIds[$item->getParentItemId()] ) ){
                    $configIds[$item->getProductId()] =  $itemsIds[$item->getParentItemId()] ;
                }

            }else{ /* Get config Product Ids for FPC Cache */
                $itemsIds[$item->getItemId()] = $item->getProductId();
            }
            $children   = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    $productIds[$childItem->getProductId()] = $childItem->getProductId();
                }
            }
        }

        if (count($productIds)) {
            Mage::getResourceSingleton('cataloginventory/indexer_stock')->reindexProducts($productIds);
        }
        
        // Reindex previously remembered items
        $productIds = array();
        foreach ($this->_itemsForReindex as $item) {
            $item->save();
            $productIds[] = $item->getProductId();
        }

        $clearCacheConfigItemsIds = array();
        if( !empty( $productIdsStock ) ){
            foreach( $productIdsStock as $productId ){
                /*Update Product to out of stock */
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
                if ($stockItem->getId() > 0 && $stockItem->getManageStock()) {
                    $qty = (int)$stockItem->getQty();
                    /*If product qty 0 <= then mark product as out of stock.*/
                    if( $qty <= 0 ){
                        $stockItem->setData('is_in_stock',0);
                        $stockItem->save();
                        if( isset( $configIds[$productId] ) ){
                            $clearCacheConfigItemsIds[] = $configIds[$productId];
                        }
                    }
                }
            }
        }

        if (count($productIds)) {
            Mage::helper("partialindex")->addPartialIndexer($productIds, self::PARTIAL_SORT_ORDER);
        }

        if( count( $clearCacheConfigItemsIds ) ){
            Mage::helper('fpccache/order')->clearCacheAfterOrderPlaced($clearCacheConfigItemsIds);
        }
        $this->_itemsForReindex = array(); // Clear list of remembered items - we don't need it anymore
        
        return $this;
    }
}


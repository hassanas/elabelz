<?php
/**
 * Progos_Algoliasearch.
 * @category Elabelz
 * @Author Hassan Ali Shahzad   <hassan.ali@progos.org>
 * @Date 05-08-2018
 *
 */
class Progos_Algoliasearch_Model_Indexer_Algolia extends Algolia_Algoliasearch_Model_Indexer_Algolia {

    /*
     *
     * This function overrided to disable function _registerCatalogInventoryStockItemEvent to removed category sync
     * suggested by Jan Petr on slack channel
     * 
     * */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, true);
        switch ($event->getEntity()) {
            case Mage_Catalog_Model_Product::ENTITY:
                $this->_registerCatalogProductEvent($event);
                break;
            case Mage_Catalog_Model_Convert_Adapter_Product::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', true);
                break;
            case Mage_Core_Model_Store_Group::ENTITY:
                $event->addNewData('algoliasearch_reindex_all', true);
                break;
            case Mage_CatalogInventory_Model_Stock_Item::ENTITY:
                if (false == $this->config->getShowOutOfStock()) {
                    //$this->_registerCatalogInventoryStockItemEvent($event);
                }
                break;
        }
    }
}
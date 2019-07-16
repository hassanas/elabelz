<?php

/**
 * @Author Hassan Ali Shahzad
 * @Date 20-06-2017
 *
 */
class Progos_Skuvault_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param bool $allProducts
     * @return bool
     *
     * Shell around cron job call to allow sync brands using shell script to synchronize all products once.
     */
    public function syncProductBrands($allProducts = false)
    {
        return Mage::getModel('progos_skuvault/cron')->runBrandSync($allProducts);
    }

    /**
     * @return bool
     *
     * Shell around functionality to sync brands in admin.
     */
    public function runAdminBrandSync()
    {
        return Mage::getModel('progos_skuvault/cron')->runAdminBrandSync();
    }

    /**
     * @return bool
     *
     * Shell around functionality to sync suppliers in admin.
     */
    public function runAdminBrandSyncWithSupplier()
    {
        return Mage::getModel('progos_skuvault/cron')->runAdminBrandSyncWithSupplier();
    }

    /**
     * @return Query results
     *  This query will get sku and its corresponding manufacturer In this way we dont need to load whole collection
     *  and then manufaturer for each product separately
     * we get only 100 products per call to update
     */
    public function getNonUpdatedSkuvaultProducts()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        /*
        This statement (e.skuvault_updated = 0)  means product which need to update in skuvault
        */
        $query = "SELECT
                        e.sku AS Sku,
                        e.entity_id AS Code,
                        option_value.value AS manufacturer
                    FROM
                        catalog_product_entity AS e
                            INNER JOIN
                        catalog_product_entity_int AS at_manufacturer ON (at_manufacturer.entity_id = e.entity_id)
                            INNER JOIN
                        eav_attribute_option_value AS option_value ON (at_manufacturer.value = option_value.option_id)
                            AND (e.skuvault_updated = 0)
                            AND (e.sku != 'null')
                            AND (at_manufacturer.attribute_id = 81)
                            AND (at_manufacturer.store_id = 0)
                            AND (option_value.store_id = 0)
                            AND (at_manufacturer.value != 'null')
                            order by e.entity_id
                            limit 100
                 ";
        $results = $readConnection->fetchAll($query);
        return $results;
    }

    /**
     * @param $responseData Array of product sku's
     *        $flag is the status 1 is updated 0 is need to update
     */
    public function updatedSkuvaultProductCollection($responseData,$flag=0){
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $query = 'UPDATE '. $resource->getTableName('catalog/product') . ' SET skuvault_updated='.$flag .' WHERE sku IN ("'.implode('", "',$responseData).'")';
        $writeConnection->query($query);
    }

    public function getSkuvaultTenantToken(){
        return Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_tenanttoken');
    }

    public function getSkuvaultUserToken(){
        return Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_usertoken');
    }

    public function getSkuvaultItemQtyPerPage(){
        return Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_item_qty_per_page');
    }

    public function getSkuvaultItemPageStart(){
        return Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_item_page_start');
    }

    public function getSkuvaultWarehouseId(){
        return Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_warehouse_id');
    }

    public function getSkuvaultskuvaultSaveSyncQty(){
        $status = Mage::getStoreConfig('sku_vault_general/sku_vault_settings/skuvault_save_sync_qty');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}

<?php

/**
 * This Module is created for Desktop and Mobile App search from klevu
 * @category     Progos
 * @package      Progos_KlevuSearch
 * @copyright    Progos TechCopyright (c) 06-09-2017
 * @author       Hassan Ali Shahzad
 *
 */
class Progos_KlevuSearch_Model_Observer extends Klevu_Search_Model_Observer
{

    public function applyLandingPageModelRewrites(Varien_Event_Observer $observer)
    {
        if (Mage::helper("klevu_search/config")->isLandingEnabled() == 1 && Mage::helper("klevu_search/config")->isExtensionConfigured()) {
            $rewrites = array(
                "global/models/catalogsearch_resource/rewrite/fulltext_collection" => "Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection",
                "global/models/catalogsearch_mysql4/rewrite/fulltext_collection" => "Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection",
                //"global/models/catalogsearch/rewrite/layer_filter_attribute"               => "Klevu_Search_Model_CatalogSearch_Layer_Filter_Attribute",
                //"global/models/catalog/rewrite/config"                                     => "Klevu_Search_Model_Catalog_Model_Config",
                //"global/models/catalog/rewrite/layer_filter_price"                         => "Klevu_Search_Model_CatalogSearch_Layer_Filter_Price",
                //"global/models/catalog/rewrite/layer_filter_category"                      => "Klevu_Search_Model_CatalogSearch_Layer_Filter_Category",
                //"global/models/catalog_resource/rewrite/layer_filter_attribute"            => "Klevu_Search_Model_CatalogSearch_Resource_Layer_Filter_Attribute",
                //"global/models/catalog_resource_eav_mysql4/rewrite/layer_filter_attribute" => "Klevu_Search_Model_CatalogSearch_Resource_Layer_Filter_Attribute"
            );

            $config = Mage::app()->getConfig();
            foreach ($rewrites as $key => $value) {
                $config->setNode($key, $value);
            }
        }
    }
}
		
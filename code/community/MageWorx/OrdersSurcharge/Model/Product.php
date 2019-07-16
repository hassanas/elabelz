<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Model_Product extends Mage_Core_Model_Abstract
{

    const MW_SURCHARGE_PRODUCT_SKU = 'mw_virtual_surcharge';

    public function getProduct()
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku('mw_virtual_surcharge');
        if ($productId) {
            return $product->load($productId);
        }

        $websites = Mage::getModel('core/website')->getCollection()->toArray(array('website_id'));
        $websitesIds = array();
        if (!empty($websites['items'])) {
            foreach ($websites['items'] as $website) {
                $websitesIds[] = $website['website_id'];
            }
        } else {
            $websitesIds[] = 1;
        }

        try {
            $product
                ->setStoreId(0)
                ->setWebsiteIds($websitesIds)
                ->setAttributeSetId(Mage::getModel('catalog/product')->getDefaultAttributeSetId())
                ->setTypeId('virtual')
                ->setCreatedAt(strtotime('now'))
                ->setUpdatedAt(strtotime('now'))
                ->setSku(self::MW_SURCHARGE_PRODUCT_SKU)
                ->setName('Virtual Surcharge')
                ->setWeight(0)
                ->setStatus(1)
                ->setTaxClassId(0)
                ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
                ->setPrice(0)
                ->setCost(0)
                ->setDescription('Virtual Surcharge Product')
                ->setShortDescription('Virtual Surcharge Product')
                ->setStockData(array(
                        'use_config_manage_stock' => 0,
                        'manage_stock' => 0,
                        'min_sale_qty' => 1,
                        'max_sale_qty' => 1,
                        'is_in_stock' => 1,
                        'qty' => 999
                    )
                );
            $product->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            throw $e;
        }

        return $product;
    }
}

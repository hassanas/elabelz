<?php

/**
 * This Module is created for Desktop and Mobile App search from klevu
 * @category     Progos
 * @package      Progos_KlevuSearch
 * @copyright    Progos Tech Copyright (c) 28-09-2017
 * @author       Hassan Ali Shahzad
 *
 */
class Progos_KlevuSearch_Helper_Klevudata extends Klevu_Search_Helper_Data
{

    /**
     * This function extended to sync only parent product sku instead of - separated with child product in klevu
     *
     * @param string $product_sku Magento Sku of the product to generate a Klevu sku for.
     * @param null $parent_sku Optional Magento Parent Sku of the parent product.
     *
     * @return string
     */
    public function getKlevuProductSku($product_sku, $parent_sku = "")
    {
        if (!empty($parent_sku)) {
            return $parent_sku;
        } else {
            return $product_sku;
        }
    }

}
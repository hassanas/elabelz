<?php
/**
 * Progos
 * @package   Progos HTML
 * @description Sorting Group added into the Dropdown.
 */
class Progos_FeedExport_Helper_Html extends Mirasvit_FeedExport_Helper_Html
{

    public function getAttributeGroup($attributeCode)
    {
        $group = '';

        $primary = array(
            'attribute_set',
            'attribute_set_id',
            'entity_id',
            'full_description',
            'meta_description',
            'meta_keyword',
            'meta_title',
            'name',
            'short_description',
            'description',
            'sku',
            'status',
            'status_parent',
            'url',
            'url_key',
            'visibility',
            'type_id',
            'php',
        );

        $stock = array(
            'is_in_stock',
            'qty',
            'parent_qty',
            'manage_stock'
        );

        $price = array(
            'tax_class_id',
            'special_from_date',
            'special_to_date',
            'cost',
            'msrp',
        );

        //Sorting Group added
        $sorting = array(
            'best_sellers',
            'most_viewed',
        );

        if (substr($attributeCode, 0, strlen('custom:')) == 'custom:') {
            $group = __('Custom Attributes');
        } elseif (substr($attributeCode, 0, strlen('mapping:')) == 'mapping:') {
            $group = __('Mapping');
        } elseif (strpos($attributeCode, 'ammeta') !== false ) {
            $group = __('Amasty Meta Tags');
        } elseif (in_array($attributeCode, $primary)) {
            $group = __('Primary Attributes');
        } elseif (in_array($attributeCode, $stock)) {
            $group = __('Stock Attributes');
        } elseif (in_array($attributeCode, $price) || strpos($attributeCode, 'price') !== false) {
            $group = __('Prices & Taxes');
        } elseif (strpos($attributeCode, 'image') !== false || strpos($attributeCode, 'thumbnail') !== false) {
            $group = __('Images');
        }  elseif (strpos($attributeCode, 'category') !== false ) {
            $group = __('Category');
        } elseif (in_array($attributeCode, $sorting)) {
            $group = __('Sorting');
        } else  {
            $group = __('Others Attributes');
        }

        return $group;
    }
}
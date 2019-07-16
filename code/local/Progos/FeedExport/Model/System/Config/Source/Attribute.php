<?php
/**
 * Progos
 * @package   Progos Product
 * @description Extended for the data of sorting order into the feed.
 */


class Progos_FeedExport_Model_System_Config_Source_Attribute extends Mirasvit_FeedExport_Model_System_Config_Source_Attribute
{
    protected $_additional = array(
        'entity_id'            => 'Product Id',
        'is_in_stock'          => 'Is In Stock',
        'qty'                  => 'Qty',
        'parent_qty'           => 'Parent Qty',
        'image'                => 'Image',
        'url'                  => 'Product Url',
        'category'             => 'Category Name',
        'category_id'          => 'Category Id',
        'final_price'          => 'Final Price',
        'store_price'          => 'Store Price',
        'min_price'            => 'Minimal Child Price of Grouped or Bundle Product',
        'category_path'        => 'Category Path (Category > Sub Category)',
        'category_paths'       => 'Category Paths (Category > Sub Category, Category > Sub Category, Category > Sub Category)',
        'image1'               => 'Image 1',
        'image2'               => 'Image 2',
        'image3'               => 'Image 3',
        'image4'               => 'Image 4',
        'image5'               => 'Image 5',
        'attribute_set'        => 'Attribute Set',
        'rating_summary'       => 'Rating Summary',
        'reviews_count'        => 'Number of Reviews',
        'type_id'              => 'Product Type',
        'best_sellers'         => 'Best Sellers',
        'most_viewed'          => 'Most Viewed',
        'manufacturer_id'          => 'Brand Id',
    );
}
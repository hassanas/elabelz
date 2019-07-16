<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */



class Mirasvit_Seo_Block_Adminhtml_CheckDuplicate_Check_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_pagerVisibility = false;
    protected $_filterVisibility = false;
    protected $_defaultLimit    = 200;

    public function __construct()
    {
        parent::__construct();
        $this->setId('grid');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = new Varien_Data_Collection();
        $collectionPrepared = new Varien_Data_Collection();


        foreach (Mage::app()->getStores() as $store) {
            $categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->setStoreId($store->getId())
                    ;
            $urlKey = $categories->getColumnValues('url_key');
            $urlKey = array_diff($urlKey, array(0, null));
            $duplicate = array_count_values($urlKey);
            $duplicate = array_diff($duplicate, array(1, 0, null));
            $duplicate = array_keys($duplicate);
            $duplicateCategory = array();
            $categoryNames = array();
            foreach ($categories as $category) {
                $categoryNames[$store->getId()][$category->getId()] = $category->getName();
                if ($category->getUrlKey() && in_array($category->getUrlKey(), $duplicate)) {
                    $duplicateCategory[$category->getId()]['store_id'] = $store->getId();
                    $duplicateCategory[$category->getId()]['category_path'] = $category->getPath();
                    $duplicateCategory[$category->getId()]['category_id'] = $category->getId();
                    $duplicateCategory[$category->getId()]['category_name'] = $category->getName();
                    $duplicateCategory[$category->getId()]['url_key'] = $category->getUrlKey();
                }
            }

            uasort($duplicateCategory, function($a, $b) {
                return strcmp($a['url_key'], $b['url_key']);
            });

            foreach ($duplicateCategory as $category) {
                    $categoryData = new Varien_Object();
                    $categoryData->setStoreId($store->getId());
                    $categoryData->setCategoryPath($this->_prepareCategoryPath($store->getId(), $category['category_path'], $categoryNames));
                    $categoryData->setCategoryId($category['category_id']);
                    $categoryData->setCategoryName($category['category_name']);
                    $categoryData->setUrlKey($category['url_key']);
                    $collection->addItem($categoryData);
            }
        }

        $this->setCollection($collection);
    }

    protected function _prepareCategoryPath($storeId, $categoryPath, $categoryNames) {
        $categoryPath = explode('/', $categoryPath);
        foreach ($categoryPath as $key => $categoryId) {
            $categoryPath[$key] = isset($categoryNames[$storeId][$categoryId]) ? $categoryNames[$storeId][$categoryId]. ' (ID: ' . $categoryId . ')' : $categoryId;
        }

        return implode(' / ', $categoryPath);
    }

    protected function _prepareColumns()
    {
        $this->addColumn('store_id', array(
            'header' => Mage::helper('seo')->__('Store Id'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'store_id',
            'filter' => false,
            'sortable' => false,
            )
        );

        $this->addColumn('category_path', array(
            'header' => Mage::helper('seo')->__('Category Path'),
            'align' => 'right',
            'width' => '200px',
            'index' => 'category_path',
            'filter' => false,
            'sortable' => false,
            )
        );

        $this->addColumn('category_id', array(
            'header' => Mage::helper('seo')->__('Category Id'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'category_id',
            'filter' => false,
            'sortable' => false,
            )
        );


        $this->addColumn('category_name', array(
            'header' => Mage::helper('seo')->__('Category Name'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'category_name',
            'filter' => false,
            'sortable' => false,
            )
        );

          $this->addColumn('url_key', array(
            'header' => Mage::helper('seo')->__('Url Key'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'url_key',
            'filter' => false,
            'sortable' => false,
            )
        );

        return parent::_prepareColumns();
    }
}

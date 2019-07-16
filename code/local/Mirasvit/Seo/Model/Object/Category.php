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


class Mirasvit_Seo_Model_Object_Category extends Mirasvit_Seo_Model_Object_Abstract
{
    protected $_product;
    protected $_category;
    protected $_parseObjects = array();

    public function _construct()
    {
        $uid = Mage::helper('mstcore/debug')->start();

        parent::_construct();
        $this->_category = Mage::registry('seo_current_category');

        if (!$this->_category) {
            $this->_category = Mage::registry('current_category');
        }

        if (!$this->_category) {
            return;
        }

        if($this->_category && $parent = $this->_category->setStoreId(Mage::app()->getStore()->getId())->getParentCategory()) {
            if (Mage::app()->getStore()->getRootCategoryId() != $parent->getId()) {
                if (($parentParent = $parent->getParentCategory())
                    && (Mage::app()->getStore()->getRootCategoryId() != $parentParent->getId())) {
                    $this->setAdditionalVariable('category', 'parent_parent_name', $parentParent->getName());
                }
                $this->setAdditionalVariable('category', 'parent_name', $parent->getName());
                $this->setAdditionalVariable('category', 'parent_url', $parent->getUrl());
            }
            $this->setAdditionalVariable('category', 'url', $this->_category->getUrl());
            $this->setAdditionalVariable('category', 'page_title', $this->_category->getMetaTitle());
        }
        if ($this->_category) {
            $this->_parseObjects['category'] = $this->_category;
        }

        //мы можем создавать данную модель при расчете сео продукта
        $this->_product = Mage::registry('current_product');
        if ($this->_product) {
            $this->_parseObjects['product'] = $this->_product;
            $this->setAdditionalVariable('product', 'url', $this->_product->getProductUrl());
        }
        $this->_parseObjects['store'] = Mage::getModel('seo/object_store');
        $this->_parseObjects['pager'] = Mage::getModel('seo/object_pager');
        $this->_parseObjects['filter'] = Mage::getModel('seo/object_wrapper_filter');

        $this->init();

        Mage::helper('mstcore/debug')->end($uid, array(
            'product_id'  => isset($this->_parseObjects['product'])?$this->_parseObjects['product']->getId(): false, //id продукта, данные по которому будут использоваться в шаблонах
            'category_id' => isset($this->_parseObjects['category'])?$this->_parseObjects['category']->getId():false,//id категории, данные по которой будут использоваться в шаблонах
            'store_id'    => isset($this->_parseObjects['store'])?$this->_parseObjects['store']->getStoreId():false//id стора, данные по которому будут использоваться в шаблонах
        ));
    }

    public function getConfig()
    {
        return Mage::getSingleton('seo/config');
    }

    protected function init()
    {
        $uid = Mage::helper('mstcore/debug')->start();
        // вначале устанавливаем из родительских категорий. низший приоритет.
        if (!$this->_category) {
            return;
        }

        $ids = $this->_category->getParentIds();
        $collection = Mage::getModel('catalog/category')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addIdFilter($ids)
                        ->addIsActiveFilter()
                        ->setOrder('level', 'asc')
                        ;
        foreach ($collection as $category) {
            $this->process($category);
        }

        //устанавливаем из текущей категории
        $this->processCurrentCategory($this->_category);

        if (!$this->getTitle()) {
            $this->setTitle($this->_category->getName());
        }

        if (!$this->getMetaTitle()) {
            if ($this->_category->getMetaTitle()) {
                $this->setMetaTitle($this->parse($this->_category->getMetaTitle()));
            } else {
                $this->setMetaTitle($this->_category->getName());
            }
        }

        if (!$this->getMetaKeywords()) {
            if ($this->_category->getMetaKeywords()) {
                $this->setMetaKeywords($this->parse($this->_category->getMetaKeywords()));
            } else {
                $this->setMetaKeywords($this->_category->getName());
            }
        }

        if (!$this->getMetaDescription()) {
            if ($this->_category->getMetaDescription()) {
                $this->setMetaDescription($this->parse($this->_category->getMetaDescription()));
            } elseif ($this->_category->getDescription()) {
                $this->setMetaDescription(Mage::helper('core/string')->substr($this->_category->getDescription(), 0, 255));
            } else {
                $this->setMetaDescription($this->_category->getName());
            }
        }

        Mage::helper('mstcore/debug')->end($uid, array(
            'this' => $this->getData(),
        ));
    }

    protected function processCurrentCategory()
    {
        $uid = Mage::helper('mstcore/debug')->start();

        if($this->_category->getSeoPageHeader()) {
            $this->setTitle($this->parse($this->_category->getSeoPageHeader()));
        }

        // set for current category. it has high priority.
        if ($this->getConfig()->isCategoryMetaTagsUsed()) {
            if ($this->_category->getMetaTitle()) {
                $this->setMetaTitle($this->parse($this->_category->getMetaTitle()));
            }

            if ($this->_category->getMetaKeywords()) {
                $this->setMetaKeywords($this->parse($this->_category->getMetaKeywords()));
            }
            if ($this->_category->getMetaDescription()) {
                $this->setMetaDescription($this->parse($this->_category->getMetaDescription()));
            }
        }
        //not necessary for most of customers
        //~ if ($this->_category->getDescription()) {
            //~ $this->setDescription($this->parse($this->_category->getDescription()));
        //~ }

        if ($this->_category->getProductTitleTpl()) {
            $this->setProductTitle($this->parse($this->_category->getProductTitleTpl()));
        }
        if ($this->_category->getProductDescriptionTpl()) {
            $this->setProductDescription($this->parse($this->_category->getProductDescriptionTpl()));
        }
        if ($this->_category->getProductMetaTitleTpl()) {
            $this->setProductMetaTitle($this->parse($this->_category->getProductMetaTitleTpl()));
        }
        if ($this->_category->getProductMetaKeywordsTpl()) {
            $this->setProductMetaKeywords($this->parse($this->_category->getProductMetaKeywordsTpl()));
        }
        if ($this->_category->getProductShortDescriptionTpl()) {
            $this->setProductShortDescription($this->parse($this->_category->getProductShortDescriptionTpl()));
        }
        if ($this->_category->getProductFullDescriptionTpl()) {
            $this->setProductFullDescription($this->parse($this->_category->getProductFullDescriptionTpl()));
        }
        if ($this->_category->getProductMetaDescriptionTpl()) {
            $this->setProductMetaDescription($this->parse($this->_category->getProductMetaDescriptionTpl()));
        }

        Mage::helper('mstcore/debug')->end($uid, array(
            'category_data' => $this->_category->getData(),
            'this'          => $this->getData(),
        ));
    }

    protected function process($category)
    {
        $uid = Mage::helper('mstcore/debug')->start();

        if ($category->getCategoryMetaTitleTpl()) {
            $this->setMetaTitle($this->parse($category->getCategoryMetaTitleTpl()));
        }
        if ($category->getCategoryMetaKeywordsTpl()) {
            $this->setMetaKeywords($this->parse($category->getCategoryMetaKeywordsTpl()));
        }
        if ($category->getCategoryMetaDescriptionTpl()) {
            $this->setMetaDescription($this->parse($category->getCategoryMetaDescriptionTpl()));
        }
            if ($category->getCategoryTitleTpl()) {
            $this->setTitle($this->parse($category->getCategoryTitleTpl()));
        }

        if ($category->getCategoryDescriptionTpl()) {
            $this->setDescription($this->parse($category->getCategoryDescriptionTpl()));
        }

        if ($category->getProductTitleTpl()) {
            $this->setProductTitle($this->parse($category->getProductTitleTpl()));
        }
        if ($category->getProductDescriptionTpl()) {
            $this->setProductDescription($this->parse($category->getProductDescriptionTpl()));
        }
        if ($category->getProductMetaTitleTpl()) {
            $this->setProductMetaTitle($this->parse($category->getProductMetaTitleTpl()));
        }
        if ($category->getProductMetaKeywordsTpl()) {
            $this->setProductMetaKeywords($this->parse($category->getProductMetaKeywordsTpl()));
        }
        if ($category->getProductShortDescriptionTpl()) {
            $this->setProductShortDescription($this->parse($category->getProductShortDescriptionTpl()));
        }
        if ($category->getProductFullDescriptionTpl()) {
            $this->setProductFullDescription($this->parse($category->getProductFullDescriptionTpl()));
        }
        if ($category->getProductMetaDescriptionTpl()) {
            $this->setProductMetaDescription($this->parse($category->getProductMetaDescriptionTpl()));
        }

        Mage::helper('mstcore/debug')->end($uid, array(
            'category_data' => $category->getData(),
            'this'          => $this->getData(),
        ));
    }
}
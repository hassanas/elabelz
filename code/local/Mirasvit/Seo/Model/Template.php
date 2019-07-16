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


class Mirasvit_Seo_Model_Template extends Mage_Rule_Model_Rule
{
    protected $_productId;
    protected $_categoryId;
    protected static $_rules = array();

    protected function _construct()
    {
        $this->_init('seo/template');
    }

    public function getRule($ruleId = false)
    {
        $ruleId = ($ruleId) ? $ruleId : $this->getId();

        $rule = Mage::getModel('seo/template')->getCollection()
            ->addFieldToFilter('template_id', $ruleId)
            ->getFirstItem();

        if (isset(self::$_rules[$ruleId])) {
            $rule = self::$_rules[$ruleId];
        } else {
            $rule = $rule->load($rule->getId());
            self::$_rules[$ruleId] = $rule;
        }

        return $rule;
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('seo/template_rule_condition_combine');
    }

    public function isProductApplied($productId)
    {
        if (is_null($this->_productId)) {
            $this->_productIds = array();
            $this->setCollectedAttributes(array());
            $productCollection = Mage::getResourceModel('catalog/product_collection')->addFieldToFilter('entity_id', $productId);
            $this->getConditions()->collectValidatedAttributes($productCollection);

            Mage::getSingleton('core/resource_iterator')->walk(
                $productCollection->getSelect(),
                array(array($this, 'callbackValidateProduct')),
                array(
                    'attributes' => $this->getCollectedAttributes(),
                    'product'    => Mage::getModel('catalog/product'),
                )
            );
        }

        if ($this->_productId) {
            return true;
        }

        return false;
    }

    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);
        if ($this->getConditions()->validate($product)) {
            $this->_productId[] = $product->getId();
        }
    }

    public function isCategoryApplied($categoryId)
    {
        if (is_null($this->_categoryId)) {
            $this->_categoryId = array();
            $this->setCollectedAttributes(array());
            $categoryCollection = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('entity_id', $categoryId);
            $this->getConditions()->collectValidatedAttributes($categoryCollection);

            Mage::getSingleton('core/resource_iterator')->walk(
                $categoryCollection->getSelect(),
                array(array($this, 'callbackValidateCategory')),
                array(
                    'attributes' => $this->getCollectedAttributes(),
                    'category'    => Mage::getModel('catalog/category'),
                )
            );
        }

        if ($this->_categoryId) {
            return true;
        }

        return false;
    }

    public function callbackValidateCategory($args)
    {
        $category = clone $args['category'];
        $category->setData($args['row']);
        if ($this->getConditions()->validate($category)) {
            $this->_categoryId[] = $category->getId();
        }
    }

}
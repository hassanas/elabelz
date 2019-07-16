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


class Mirasvit_Seo_Model_Template_Rule_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    protected $_groups = array(
        'category' => array(
            'category_ids',
        ),
        'base' => array(
            'name',
            'attribute_set_id',
            'sku',
            'url_key',
            'visibility',
            'status',
            'default_category_id',
            'meta_description',
            'meta_keyword',
            'meta_title',
            'price',
            'special_price',
            'special_price_from_date',
            'special_price_to_date',
            'tax_class_id',
            'short_description',
            'full_description'
        ),
        'extra' => array(
            'created_at',
            'updated_at',
            'qty',
            'price_diff',
            'percent_discount'
        )
    );
    public function __construct()
    {
        parent::__construct();
        $this->setType('seo/template_rule_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        $productCondition  = Mage::getModel('seo/template_rule_condition_validate');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();

        $attributes        = array();
        foreach ($productAttributes as $code => $label) {
            $group = 'attributes';
            foreach ($this->_groups as $key => $values) {
                if (in_array($code, $values)) {
                    $group = $key;
                }
            }
            $attributes[$group][] = array(
                'value' => 'seo/template_rule_condition_validate|'.$code,
                'label' => $label
            );
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, array(
            array(
                'value' => 'seo/template_rule_condition_combine',
                'label' => Mage::helper('seo')->__('Conditions Combination')
            ),
            array(
                'label' => Mage::helper('seo')->__('Categories and Layered navigation'),
                'value' => $attributes['category']
            ),
        ));

        $model  = Mage::registry('current_template_model');
        if ($model && $model->getRuleType()
            && $model->getRuleType() == Mirasvit_Seo_Model_Config::PRODUCTS_RULE) {
                $conditions = array_merge_recursive($conditions, array(
                    array(
                    'label' => Mage::helper('seo')->__('Products'),
                    'value' => $attributes['base']
                    ),
                    // array(
                    //     'label' => Mage::helper('seo')->__('Products Additional'),
                    //     'value' => $attributes['extra']
                    // ),
                ));

                if (isset($attributes['attributes'])) {
                    $conditions = array_merge_recursive($conditions, array(
                        array(
                            'label' => Mage::helper('seo')->__('Products Attributes'),
                            'value' => $attributes['attributes']
                        ),
                    ));
                }
        }

        return $conditions;
    }

    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }
}

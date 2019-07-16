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


class Mirasvit_Seo_Model_System_Config_Source_Weightrichsnippets
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('seo')->__('Disabled')),
            array('value' => Mirasvit_Seo_Model_Config::PRODUCT_WEIGHT_RICH_SNIPPETS_KG, 'label'=>Mage::helper('seo')->__('Kilograms (kg)')),
            array('value' => Mirasvit_Seo_Model_Config::PRODUCT_WEIGHT_RICH_SNIPPETS_LB, 'label'=>Mage::helper('seo')->__('Pounds (lb)')),
            array('value' => Mirasvit_Seo_Model_Config::PRODUCT_WEIGHT_RICH_SNIPPETS_G, 'label'=>Mage::helper('seo')->__('Grams (g)')),
        );
    }
}

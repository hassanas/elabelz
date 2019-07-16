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


class Mirasvit_Seo_Block_Adminhtml_System_TemplateRenderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $metaTitle        = (trim($row->getData('meta_title')))        ? $row->getData('meta_title')       : '-';
        $metaKeywords     = (trim($row->getData('meta_keywords')))     ? $row->getData('meta_keywords')    : '-';
        $metaDescription  = (trim($row->getData('meta_description')))  ? $row->getData('meta_description') : '-';
        $title            = (trim($row->getData('title')))             ? $row->getData('title')            : '-';
        $description      = (trim($row->getData('description')))       ? $row->getData('description')      : '-';
        $shortDescription = (trim($row->getData('short_description'))) ? $row->getData('short_description'): '-';
        $fullDescription  = (trim($row->getData('full_description')))  ? $row->getData('full_description') : '-';

        $value = '<b>Meta title: </b>' . $metaTitle
                . "<br/>" .'<b>Meta keywords: </b>' . $metaKeywords
                . "<br/>" .'<b>Meta description: </b>' . $metaDescription
                . "<br/>" .'<b>Title (H1): </b>' . $title
                . "<br/>" .'<b>SEO description: </b>' . $description;

        if ($row->getData('rule_type') == Mirasvit_Seo_Model_Config::PRODUCTS_RULE) {
            $value .= "<br/>" .'<b>Product short description: </b>' . $shortDescription
                      . "<br/>" .'<b>Product description: </b>' . $fullDescription;
        }

        return $value;
    }
}
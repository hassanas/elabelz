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


class Mirasvit_Seo_Model_System_Config_Source_Producturlformat
{
     public function toOptionArray()
    {
        $productUrlArray = array(
            array('value' => 0, 'label'=>Mage::helper('seo')->__('Don\'t change')),
            array('value' => Mirasvit_Seo_Model_Config::URL_FORMAT_LONG, 'label'=>Mage::helper('seo')->__('Include categories path to Product URLs')),
            array('value' => Mirasvit_Seo_Model_Config::URL_FORMAT_SHORT, 'label'=>Mage::helper('seo')->__('Don\'t include categories path to Product URLs')),
        );

        if (Mage::helper('mstcore/version')->getEdition() == 'ee' && Mage::getVersion() >= '1.13.0.0') {
            unset($productUrlArray[1]);  //exist in Catalog->Search Engine Optimizations->Use Categories Path for Product URLs
        }

        return $productUrlArray;
    }
}

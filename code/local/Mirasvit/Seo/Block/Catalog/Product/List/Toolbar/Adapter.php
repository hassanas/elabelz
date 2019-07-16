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


if (Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter') && class_exists('Mirasvit_SeoFilter_Block_Catalog_Product_List_Toolbar')) {
    class Mirasvit_Seo_Block_Catalog_Product_List_Toolbar_Adapter extends Mirasvit_SeoFilter_Block_Catalog_Product_List_Toolbar {
    }
} else {
    class Mirasvit_Seo_Block_Catalog_Product_List_Toolbar_Adapter extends Mirasvit_Seo_Block_Catalog_Product_List_Toolbar {
    }
}


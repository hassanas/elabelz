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


class Mirasvit_Seo_Helper_Validator extends Mirasvit_MstCore_Helper_Validator_Abstract
{
    public function testMirasvitCrc()
    {
        $modules = array('SEO');
        return Mage::helper('mstcore/validator_crc')->testMirasvitCrc($modules);
    }

//    public function testMemoryLimit()
//    {
//        $memoryLimit = ini_get('memory_limit');
//        $result = self::SUCCESS;
//        $title = "Memory limit (current is $memoryLimit)";
//        $description = array();
//
//
//        if (strpos($memoryLimit, 'M') !== false) {
//            $description[] = "You use a lowercase in the settings of PHP memory limit (php.ini). It may be a source of errors for GD lib. If you have a problem with sitemap generation, please, ask your hosting provider to switch to lowercase (eg. 512m).";
//        }
//
//        $memoryLimitNumber = $memoryLimit;
//        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
//            if ($matches[2] == 'M' || $matches[2] == 'm') {
//                $memoryLimitNumber = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
//            } else if ($matches[2] == 'K' || $matches[2] == 'k') {
//                $memoryLimitNumber = $matches[1] * 1024; // nnnK -> nnn KB
//            }
//        }
//        if ($memoryLimitNumber < 512 * 1024 * 1024) {
//            $description[] = "You have a low memory limit. It may be a source of sitemaps errors. Please, increase it to 512M or more.";
//        }
//        $result = self::FAILED;
//        return array($result, $title, $description);
//    }

        public function testTablesExists()
    {
        $result = self::SUCCESS;
        $title = 'Advanced SEO Suite: Required tables exist';
        $description = array();

        $tables = array(
            'core/store',
            'seo/rewrite',
            'seo/rewrite_store',
            'seo/redirect',
            'seo/template',
            'seo/template_store',
            'seofilter/rewrite',
        );

        foreach ($tables as $table) {
            if (!$this->dbTableExists($table)) {
                $description[] = "Table '$table' does not exist";
                $result = self::FAILED;
            }
        }
        return array($result, $title, $description);
    }

    public function testConflictExtensions()
    {
        $result = self::SUCCESS;
        $title = 'Advanced SEO Suite: Conflict Extensions';
        $description = array();

        if (Mage::helper('mstcore')->isModuleInstalled('Pektsekye_OptionExtended')) {
            $result = self::FAILED;
            $description[] = "<b>Pektsekye OptionExtended</b> installed. \"Enable SEO-friendly URLs for Product Images\" will work incorrectly (images can be broken).";
            $description[] = "Please, change in file /app/code/local/Pektsekye/OptionExtended/Block/Product/View/Js.php following code \"->init(\$this->getProduct(), 'thumbnail', \$image)\" at \"->init(\$this->getProduct(), 'thumbnail', \$image, false)\"";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_Fpc')) {
            if (!file_exists(Mage::getBaseDir().'/app/etc/modules/1Mirasvit_Seo.xml')) {
                $result = self::FAILED;
                $description[] = "<b>Amasty Fpc</b> installed. Please, rename file /app/etc/modules/Mirasvit_Seo.xml to /app/etc/modules/1Mirasvit_Seo.xml";
            }
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Creare_CreareSeoCore')) {
            $result = self::FAILED;
            $description[] = "<b>Creare CreareSeoCore</b> installed. Please disable this extension, because it has the same functions as Mirasvit SEO.";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Creare_CreareSeoSitemap') && Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoSitemap')) {
            $result = self::FAILED;
            $description[] = "<b>Creare CreareSeoSitemap</b> is installed. Please disable this extension to avoid class conflicts with the same sitemap generation functionality as Mirasvit SEO Suite has.";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Magehouse_Slider')) {
            $result = self::FAILED;
            $description[] = '<b>Magehouse_Slider</b> is installed. If "Enable SEO-friendly URLs for Layered Navigation = Yes" slider can work incorrect. To fix the issue in file /app/code/community/Magehouse/Slider/Block/Catalog/Layer/Filter/Price.php
            change
            <br/>
            public function prepareParams(){<br/>
            &nbsp;&nbsp;&nbsp;    $url=\"\";<br/>

            &nbsp;&nbsp;&nbsp;    $params=$this->getRequest()->getParams();<br/>
            &nbsp;&nbsp;&nbsp;    foreach ($params as $key=>$val)<br/>
            &nbsp;&nbsp;&nbsp;        {<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if($key==\'id\'){ continue;}<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if($key==\'min\'){ continue;}<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if($key==\'max\'){ continue;}<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                $url .= \'&\' . $key . \'=\' . $val;<br/>
            &nbsp;&nbsp;&nbsp;        }<br/>
            &nbsp;&nbsp;&nbsp;    return $url;<br/>
            }<br/>
            at<br/>
            public function prepareParams(){<br/>
            &nbsp;&nbsp;&nbsp;    $url=\"\";<br/>

            &nbsp;&nbsp;&nbsp;    $params=$this->getRequest()->getParams();<br/>
            &nbsp;&nbsp;&nbsp;    $isSeoFilter = false;<br/>
            &nbsp;&nbsp;&nbsp;    if (Mage::helper(\'mstcore\')->isModuleInstalled(\'Mirasvit_SeoFilter\')<br/>
            &nbsp;&nbsp;&nbsp;        && Mage::getModel(\'seofilter/config\')->isEnabled()) {<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;            $isSeoFilter = true;<br/>
            &nbsp;&nbsp;&nbsp;    }<br/>
            &nbsp;&nbsp;&nbsp;    foreach ($params as $key=>$val)<br/>
            &nbsp;&nbsp;&nbsp;        {<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if($key==\'id\'){ continue;}<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if($key==\'min\'){ continue;}<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if($key==\'max\'){ continue;}<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                if (!$isSeoFilter) {<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                    $url .= \'&\' . $key . \'=\' . $val;<br/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;                }<br/>
            &nbsp;&nbsp;&nbsp;        }<br/>
            &nbsp;&nbsp;&nbsp;    return $url;<br/>
            }<br/>';
        }

 if (Mage::helper('mstcore')->isModuleInstalled('Amasty_Shopby') && Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty Improved Navigation</b> third-party extension for Layered Navigation is installed. To avoid any class conflicts with Mirasvit SEO Suite, please disable a subsystem of our module that corresponds for Layered Navigation Friendly URLs by renaming file app/code/local/modules/Mirasvit_SeoFilter.xml to something like Mirasvit_SeoFilter.xml_DIS. This way you will avoid conflicts and use all other capabilities of our extension except  \"SEO Friendly URLs for Layered Navigation\" - this should be handled by Amasty module.";
            $description[] = "To avoid warnings in Amasty validator please comment out the code below in a file /app/code/local/Mirasvit/Seo/etc/config.xml:";
            $description[] = "&lt;catalog&gt;";
            $description[] = "&nbsp;&nbsp; &lt;rewrite&gt;";
            $description[] = "&nbsp;&nbsp;&nbsp;&nbsp; &lt;product_list_toolbar&gt;Mirasvit_Seo_Block_Catalog_Product_List_Toolbar_Adapter&lt;/product_list_toolbar&gt;";
            $description[] = "&nbsp;&nbsp; &lt;/rewrite&gt;";
            $description[] = "&lt;/catalog&gt;";
            $description[] = "</br> <b>For changes to apply on the frontend, please clear Magento cache in System > Cache Managenent section of Admin Panel<b>";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('AW_Layerednavigation') && Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter')) {
            $result = self::FAILED;
            $description[] = "<b>AheadWorks Layer Navigation</b> third-party extension for Layered Navigation is installed. To avoid any class conflicts with Mirasvit SEO Suite, please disable a subsystem of our module that corresponds for Layered Navigation Friendly URLs by renaming file app/code/local/modules/Mirasvit_SeoFilter.xml to something like Mirasvit_SeoFilter.xml_DIS. This way you will avoid conflicts and use all other capabilities of our extension except  \"SEO Friendly URLs for Layered Navigation\" - this should be handled by AheadWorks module.";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Magentothem_Layerednavigationajax') && Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter')) {
            $result = self::FAILED;
            $description[] = "<b>Magentothem Layered Navigationajax</b> third-party extension for Layered Navigation is installed. To avoid any class conflicts with Mirasvit SEO Suite, please disable a subsystem of our module that corresponds for Layered Navigation Friendly URLs by renaming file app/code/local/modules/Mirasvit_SeoFilter.xml to something like Mirasvit_SeoFilter.xml_DIS. This way you will avoid conflicts and use all other capabilities of our extension except  \"SEO Friendly URLs for Layered Navigation\" - this should be handled by Magentothem module.";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Potato_Compressor')) {
            if (!file_exists(Mage::getBaseDir().'/app/etc/modules/ZMirasvit_Seo.xml')) {
                $result = self::FAILED;
                $description[] = "<b>Potato Compressor</b> is installed. Please, rename file /app/etc/modules/Mirasvit_Seo.xml to /app/etc/modules/ZMirasvit_Seo.xml";
            }
        }

        if (Mage::helper('mstcore')->isModuleInstalled('WBL_Minify')) {
            if (!file_exists(Mage::getBaseDir().'/app/etc/modules/ZMirasvit_Seo.xml')) {
                $result = self::FAILED;
                $description[] = "<b>WBL Minify</b> is installed. Please, rename file /app/etc/modules/Mirasvit_Seo.xml to /app/etc/modules/ZMirasvit_Seo.xml";
            }
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Yoast_CanonicalUrl') && class_exists('Yoast_CanonicalUrl_Block_Head')) {
            $result = self::FAILED;
            $description[] = "<b>Yoast CanonicalUrl</b> is installed. Please disable this extension, because it duplicates \"Add Canonical URL Meta Header\" function of Mirasvit SEO Suite.";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Magentothem_Googlerichsnippet')) {
            $result = self::FAILED;
            $description[] = "<b>Magentothem Googlerichsnippet</b> is installed. Please note that it has similar functions with some Rich snippets functionality of Mirasvit SEO SUite. To avoid adding several sets of similar metadata to the same page, please use snippets either from System > Configuration > Magentothem > \"Google Rich Snippets\" section OR from SEO > Settings > \"Rich Snippets and Opengraph\" section.";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_Seo')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty Seo</b> is installed. To avoid class conflicts, please disable it as some functions duplicate functionality of Mirasvit SEO Suite";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_SeoToolKit')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty SeoToolKit</b> is installed. To avoid class conflicts, please disable it as it duplicates \"Redirect Management\" functionality of Mirasvit SEO Suite";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_SeoTags')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty SeoTags</b> is installed. To avoid class conflicts, please disable it as it duplicates \"SEO Templates\" functionality of Mirasvit SEO Suite";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_SeoSingleUrl')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty SeoSingleUrl</b> is installed. To avoid class conflicts, please disable it as it duplicates \"SEO > Settings > SEO-friendly URLs Settings\" functionality of Mirasvit SEO Suite";
        }

        if ((Mage::helper('mstcore')->isModuleInstalled('Amasty_SeoGoogleSitemap') || Mage::helper('mstcore')->isModuleInstalled('Amasty_SeoHtmlSitemap'))
            && Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoSitemap')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty Sitemap</b> extension is installed. To avoid class conflicts, please disable it Amasty_SeoGoogleSitemap.xml and/or Amasty_SeoHtmlSitemap.xml as it duplicates \"System > Configuration > Mirasvit Extensions > Extended Site Map\" functionality of Mirasvit SEO Suite";

        }

        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_SeoRichData')) {
            $result = self::FAILED;
            $description[] = "<b>Amasty_SeoRichData</b> is installed. To avoid class conflicts, please disable it as it duplicates \"SEO > Settings > Rich Snippets and Opengraph\" functionality of Mirasvit SEO Suite";
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Smartwave_Porto')) {
            if (!file_exists(Mage::getBaseDir().'/app/etc/modules/ZMirasvit_Seo.xml')) {
                $result = self::FAILED;
                $description[] = "<b>Smartwave Porto</b> extension is installed. Please, rename file /app/etc/modules/Mirasvit_Seo.xml to /app/etc/modules/ZMirasvit_Seo.xml";
            }
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Sle_Compare')) {
            if (!file_exists(Mage::getBaseDir().'/app/etc/modules/ZMirasvit_Seo.xml')) {
                $result = self::FAILED;
                $description[] = "<b>Sle Compare</b> extension is installed. Please, rename file /app/etc/modules/Mirasvit_Seo.xml to /app/etc/modules/ZMirasvit_Seo.xml";
            }
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Itactica_LayeredNavigation') && Mage::helper('mstcore')->isModuleInstalled('Mirasvit_SeoFilter')) { 
            $result = self::FAILED; 
            $description[] = "<b>Itactica Layered Navigation</b> third-party extension for Layered Navigation is installed. To avoid any class conflicts with Mirasvit SEO Suite, please disable a subsystem of our module that corresponds for Layered Navigation Friendly URLs by renaming file app/code/local/modules/Mirasvit_SeoFilter.xml to something like Mirasvit_SeoFilter.xml_DIS. This way you will avoid conflicts and use all other capabilities of our extension except \"SEO Friendly URLs for Layered Navigation\" - this should be handled by Itactica module."; 
        }

        if (Mage::helper('mstcore')->isModuleInstalled('Smartwave_Blog') && class_exists('Smartwave_Blog_Model_Sitemap')) {
            if (!file_exists(Mage::getBaseDir().'/app/etc/modules/ZMirasvit_SeoSitemap.xml')) { 
                $result = self::FAILED; 
                $description[] = "<b>Smartwave Blog</b> extension is installed. Please, rename file /app/etc/modules/Mirasvit_SeoSitemap.xml to /app/etc/modules/ZMirasvit_SeoSitemap.xml to avoid class conflicts"; 
            }
        }

        return array($result, $title, $description);
    }
}
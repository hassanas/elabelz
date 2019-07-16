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



if (Mage::helper('mstcore')->isModuleInstalled('Magpleasure_Blog') && class_exists('Magpleasure_Blog_Model_Sitemap')) {
    abstract class Mirasvit_SeoSitemap_Model_Sitemap_Abstract extends Magpleasure_Blog_Model_Sitemap {
    }
} elseif (Mage::helper('mstcore')->isModuleInstalled('Smartwave_Blog') && class_exists('Smartwave_Blog_Model_Sitemap')) {
    abstract class Mirasvit_SeoSitemap_Model_Sitemap_Abstract extends Smartwave_Blog_Model_Sitemap {
    }
} elseif (Mage::helper('mstcore')->isModuleInstalled('Fishpig_Wordpress') && class_exists('Fishpig_Wordpress_Model_Sitemap_Sitemap')) {
    abstract class Mirasvit_SeoSitemap_Model_Sitemap_Abstract extends Fishpig_Wordpress_Model_Sitemap_Sitemap {
    }
} elseif (Mage::helper('mstcore')->isModuleInstalled('AW_Blog') && class_exists('AW_Blog_Model_Sitemap')) {
    abstract class Mirasvit_SeoSitemap_Model_Sitemap_Abstract extends AW_Blog_Model_Sitemap {
    }
} elseif (Mage::helper('mstcore')->isModuleInstalled('MC_Faq') && class_exists('MC_Faq_Model_Sitemap')) {
    abstract class Mirasvit_SeoSitemap_Model_Sitemap_Abstract extends MC_Faq_Model_Sitemap {
    }
} else {
    abstract class Mirasvit_SeoSitemap_Model_Sitemap_Abstract extends Mage_Sitemap_Model_Sitemap {
    }
}

class Mirasvit_SeoSitemap_Model_Sitemap extends Mirasvit_SeoSitemap_Model_Sitemap_Abstract
{
    protected $io;
    protected $generateSitemapIndex;
    protected $currentLinks;
    protected $maxLinks;
    protected $sitemapNum;
    protected $splitSize;
    protected $storeId;
    protected $excludeLinks;
    protected $date;
    protected $baseUrl;

    public function init($storeId) {
        $this->generateSitemapIndex = false;
        $this->currentLinks = 0;
        $this->maxLinks = $this->getConfig()->getMaxLinks($storeId);
        $this->sitemapNum = 0;
        $this->splitSize = $this->getConfig()->getSplitSize($storeId);
    }

    protected $config;
    public function getConfig() {
        if (!$this->config) {
            $this->config = Mage::getSingleton('seositemap/config');
        }
        return $this->config;
    }

    public function openSitemap() {
        $this->io = new Varien_Io_File();
        $this->io->setAllowCreateFolders(true);
        $this->io->open(array('path' => $this->getPath()));

        $file = $this->getSitemapFilename($this->sitemapNum);

        if ($this->io->fileExists($file) && !$this->io->isWriteable($file)) {
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }
        try {
            $this->io->streamOpen($file);
        } catch (Exception $e) { //catch Permission denied for write exception
            throw new Mage_Core_Exception($e->getMessage());
        }

        $this->io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $this->io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">');
    }

    public function closeSitemap() {
        if (!$this->io->streamWrite('</urlset>') == 9) { // function returns number of written symbols on success
            throw new Mage_Core_Exception("XML is not valid - please check " . $this->getPath().$this->getSitemapFilename());
        }
        $this->io->streamClose();
    }

    public function getSitemapFilename($i = 0) {
        if ($i == 0) {
            return parent::getSitemapFilename();
        }
        $file = parent::getSitemapFilename();
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $fileNew = str_replace('.'.$ext, $i.'.'.$ext, $file);
        return $fileNew;
    }

    public function writeStream($xml) {
        $this->io->streamWrite($xml);
        $this->io->streamWrite("\n");
        $this->currentLinks++;
        if ($this->currentLinks == $this->maxLinks ||
            ($this->splitSize  > 0 && $this->io->streamStat('size') + 500 >= $this->splitSize*1024)) {
            if ($this->sitemapNum == 0) {
                $this->sitemapNum = 1;
                rename ($this->getPath().$this->getSitemapFilename(), $this->getPath().$this->getSitemapFilename($this->sitemapNum));
                $this->generateSitemapIndex = true;
            }
            $this->sitemapNum++;
            $this->currentLinks = 0;
            $this->closeSitemap();
            $this->openSitemap();
        }
    }

    public function getImageUrl($file, $item, $storeId)
    {
        $config = $this->getConfig();
        if ($config->getIsEnableImageFriendlyUrls($storeId)) {
            if ($template = $config->getImageUrlTemplate($storeId)) {
                $urlKey = Mage::helper('mstcore/parsevariables')->parse(
                    $template,
                    array('product' => $item)
                );
            } else {
                $urlKey = $item->getName();
            }
            $urlKey = Mage::getSingleton('catalog/product_url')->formatUrlKey($urlKey);
            $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();
            $baseFile = $baseDir . $file;
            $md5 = md5($baseFile);
            //AdBlock browser extention blocks content with "ad*" in URL
            $md5 = preg_match("/ad[A-Za-z0-9]/i", substr($md5, 3, 3)) ? substr($md5, 4, 3) : substr($md5, 3, 3);
            $urlKey = $urlKey.'-'.$md5;

            $imageUrlModel = Mage::helper('seositemap/image')
                ->init($item, 'thumbnail', 'catalog/product', $file)
                ->setUrlKey($urlKey)
                ->setUrldir('product')
                ;
            if ($config->getImageSizeWidth($storeId) && $config->getImageSizeHeight($storeId)) {
                $imageUrlModel->resize(
                    $config->getImageSizeWidth($storeId),
                    $config->getImageSizeHeight($storeId)
                );
            }
            try {
                $imageUrl = $imageUrlModel->toStr();
                $imageUrlModel->cleanMemory();
            } catch (Exception $e) {
                $imageUrl = false;
            }
        } else {
            $imageUrl = Mage::helper('mstcore/image')->init($item, 'thumbnail', 'catalog/product', $file);
        }
        $imageUrl = str_replace('https://', 'http://', $imageUrl); //if backend has https://, we don't want to have https:// in images
        return $imageUrl;
    }

    /**
     * Generate categories sitemap
     */
    public function generateCategorySitemap() {
        $changefreq = (string)Mage::getStoreConfig('sitemap/category/changefreq', $this->storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/category/priority', $this->storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($this->storeId);
        foreach ($collection as $item) {
            if (Mage::helper('seositemap')->checkArrayPattern('/'.$item->getUrl(), $this->excludeLinks)) {
                continue;
            }

            $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($this->prepareUrl($this->baseUrl . $item->getUrl())),
                $this->date,
                $changefreq,
                $priority
            );
            $this->writeStream($xml);

        }
        unset($collection);
    }

    /**
     * Get available categories
     * @return array
     */
    protected function getAvailableCategoryIds() {
        $catCollection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($this->storeId);
        $rootCatId = Mage::app()->getStore($this->storeId)->getRootCategoryId();
        $availableCategory = array($rootCatId);
        foreach ($catCollection as $catItem) {
            $availableCategory[] = $catItem->getId();
        }

        return $availableCategory;

    }

    /**
     * Generate products sitemap
     */
    public function generateProductSitemap() {
        $isAddProductImages   = $this->getConfig()->getIsAddProductImages($this->storeId);
        $changefreq = (string)Mage::getStoreConfig('sitemap/product/changefreq', $this->storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/product/priority', $this->storeId);

        $availableCategory = $this->getAvailableCategoryIds();
        $isEe = (Mage::helper('mstcore/version')->getEdition() == 'ee') ? true : false;
        $whereIn = new Zend_Db_Expr('at_category_id.category_id IN (' . implode(',', $availableCategory) . ')');
        $whereIs = new Zend_Db_Expr('at_category_id.category_id IS NULL');
        //~ $collection = Mage::getResourceModel('seositemap/catalog_product')->getCollection($this->storeId);
        //we need to load correct urls
        $i = 1;
        $step = 1000;
        do {
           if ($isEe) {
                $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addStoreFilter($this->storeId)
                    ->addAttributeToFilter('status', 1)
                    ->addAttributeToFilter('visibility', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
                    ;
            } else {
                $collection = Mage::getModel('catalog/product')->getCollection()
                    ->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
                    ->addAttributeToSelect('*')
                    ->addStoreFilter($this->storeId)
                    ->addAttributeToFilter('status', 1)
                    ->addAttributeToFilter('visibility', array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH))
                    ;

                $collection->getSelect()->where("{$whereIn} OR {$whereIs}");
                $collection->getSelect()->group('e.entity_id');
            }

            $collection->getSelect()->limitPage($i,$step);
            $attribute = Mage::getSingleton('catalog/product')->getResource()->getAttribute('media_gallery');
            $media = Mage::getResourceSingleton('catalog/product_attribute_backend_media');
            $empty = true;
            foreach ($collection as $item) {
                if (Mage::helper('seositemap')->checkArrayPattern($item->getProductUrl(), $this->excludeLinks)) {
                    continue;
                }

                $empty = false;
                $item->setStoreId($this->storeId);
                $images = '';
                if ($isAddProductImages) {

                    $gallery = $media->loadGallery($item, new Varien_Object(array('attribute' => $attribute)));
                    if (is_array($gallery)) {
                        foreach ($gallery as $image) {
                            if ($image['disabled'] == 1) {
                                continue;
                            }
//@dva I don't know why we need this. it consumes memory.
//                            if (strpos(htmlspecialchars(Mage::helper('mstcore/image')->init($item, 'thumbnail', 'catalog/product', $image['file'])), 'thumbnail.jpg') === false) {
                            try {
                                $imageUrl = $this->getImageUrl($image['file'], $item,  $this->storeId);
//                                echo $this->convert(memory_get_usage(true)).'<br>';
                                if ($imageUrl) {
                                    $images .= '<image:image><image:loc>' . htmlspecialchars($imageUrl) . '</image:loc></image:image>';
                                }
                            } catch (Mirasvit_SeoSitemap_BadImageException $e) {}
//                            }
                        }
                    }
                }
                $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority>%s</url>',
                        htmlspecialchars($this->prepareUrl($item->getProductUrl())),
                        $this->date,
                        $changefreq,
                        $priority,
                        $images
                    );
                $this->writeStream($xml);
            }
            if ($empty) {
                break;
            }
            unset($collection);
            $i++;
        } while (true);
    }

    /**
     * Generate cms pages sitemap
     */
    public function generateCmsPagesSitemap() {
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $this->storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/page/priority', $this->storeId);
        $ignore = $this->getConfig()->getIgnoreCmsPages($this->storeId);
        $collection = Mage::getModel('cms/page')->getCollection()
                         ->addStoreFilter($this->storeId)
                         ->addFieldToFilter('is_active', true)
                         ->addFieldToFilter('main_table.identifier', array('nin' => $ignore));
        $isMagentoEe = false;
        if (Mage::helper('mstcore/version')->getEdition() == 'ee') {
            $isMagentoEe = true;
            $table = Mage::getSingleton('core/resource')->getTableName('enterprise_cms/hierarchy_node');
            $collection->getSelect()->join(array('cmsHierarchyTable' => $table), 'main_table.page_id = cmsHierarchyTable.page_id',  array('hierarchy_request_url' => 'request_url'));
        }
        foreach ($collection as $page) {
            $pageIdentifier = ($isMagentoEe && $page->getHierarchyRequestUrl()) ? $page->getHierarchyRequestUrl() : $page->getIdentifier();

            if (Mage::helper('seositemap')->checkArrayPattern('/'.$pageIdentifier, $this->excludeLinks)) {
                continue;
            }

            $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($this->prepareUrl(Mage::getUrl(null, array('_direct' => $pageIdentifier, '_store' => $this->storeId)))),
                $this->date,
                $changefreq,
                $priority
            );
            $this->writeStream($xml);
        }
        unset($collection);
    }

    /**
     * Generate Product Tags sitemap if enabled
     */
    public function generateProductTagSitemap() {
        if ($this->config->getIsAddProductTags($this->storeId)) {
            $tagCollection = Mage::getModel('tag/tag')->getCollection();
            if (!$tagCollection) {
                return;
            }
            $changefreq = (string)$this->config->getProductTagsChangefreq($this->storeId);
            $priority   = (string)$this->config->getProductTagsPriority($this->storeId);
            $collection = $tagCollection
                            ->addStoreFilter($this->storeId)
                            ->addStatusFilter(Mage_Tag_Model_Tag::STATUS_APPROVED)
                            ;
                            // echo $priority;die;
            foreach ($collection as $item) {
                if (Mage::helper('seositemap')->checkArrayPattern($item->getTaggedProductsUrl(), $this->excludeLinks)) {
                    continue;
                }

                $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($this->prepareUrl($item->getTaggedProductsUrl())),
                    $this->date,
                    $changefreq,
                    $priority
                );
                $this->writeStream($xml);
            }
            unset($collection);
        }
    }

    /**
     * Generate Additional links sitemap if exist
     */
    public function generateAdditionalLinkSitemap() {
        $changefreq = (string)$this->config->getLinkChangefreq($this->storeId);
        $priority   = (string)$this->config->getLinkPriority($this->storeId);
        $links = $this->config->getAdditionalLinks();

        foreach ($links as $item) {
                $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($this->prepareUrl($this->baseUrl . ltrim($item->getUrl(), "/"))),
                    $this->date,
                    $changefreq,
                    $priority
                );
                $this->writeStream($xml);
        }
        unset($collection);
    }

    /**
     * Generate AW Blog sitemap if exist
     */
    public function generateAWblogSitemap() {
        if (Mage::helper('mstcore')->isModuleInstalled('AW_Blog')) {
            //AW_Blog_Model_Observer - function addBlogSection
            /**
             * Generate blog pages sitemap
             */
            $changefreq = (string)Mage::getStoreConfig('sitemap/blog/changefreq');
            $priority = (string)Mage::getStoreConfig('sitemap/blog/priority');
            $collection = Mage::getModel('blog/blog')->getCollection()->addStoreFilter($this->storeId);
            Mage::getSingleton('blog/status')->addEnabledFilterToCollection($collection);
            $route = Mage::getStoreConfig('blog/blog/route');
            if ($route == "") {
                $route = "blog";
            }


            foreach ($collection as $item) {
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($this->baseUrl . $route . '/' . $item->getIdentifier()), $this->date, $changefreq, $priority
                );

                $this->writeStream($xml);
            }
            unset($collection);
        }
    }

    /**
     * Generate Amasty_Xlanding sitemap if exist
     */
    public function generateAmastyXlandingSitemap() {
        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_Xlanding')) {
            $select = Mage::getModel('amlanding/page')->getCollection()
                    ->addFieldToFilter('is_active', 1)
                    ->getSelect()
                    ->join(
            array('amlanding_page_store' => Mage::getSingleton('core/resource')->getTableName('amlanding/page_store')),
            'main_table.page_id = amlanding_page_store.page_id',
            array())
            ->where('amlanding_page_store.store_id IN (?)', array($this->storeId));

            $query = Mage::getSingleton('core/resource')->getConnection('core_write')->query($select);

            $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $this->storeId);
            $priority   = (string)Mage::getStoreConfig('sitemap/page/priority', $this->storeId);

            while ($row = $query->fetch()) {
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($this->baseUrl .  $row['identifier'] . '/'), $this->date, $changefreq, $priority
                );

                $this->writeStream($xml);
            }
        }
    }

    /**
     * Generate Magpleasure_Blog sitemap if exist
     */
    public function generateMagpleasureBlogSitemap() {
        if (Mage::helper('mstcore')->isModuleInstalled('Magpleasure_Blog')) {
            foreach ($this->generateLinks() as $item){
                $itemDate = isset($item['date']) ? $item['date'] : $this->date;
                $changefreq = 'daily';
                $priority   = '0.2';
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($item['url']), $itemDate, $changefreq, $priority
                );

                $this->writeStream($xml);
            }
        }
    }


    /**
     * Generate Mirasvit_Kb sitemap if exist
     */
    public function generateMirasvitKbSitemap()
    {
        if (Mage::helper('mstcore')->isModuleInstalled('Mirasvit_Kb')) {
            $changefreq = 'daily';
            $priority   = '0.2';
            $suffix =  Mage::helper('catalog/product')->getProductUrlSuffix($this->storeId);
            $kbHelper = Mage::helper('seositemap/kb');
            $kbHomeUrl = $kbHelper->getKbHomeUrl($this->storeId);
            $kbSitemapObject = new Varien_Object();
            $kbSitemapObject->setData('home', $kbHomeUrl);
            $slash = '/';

            $kbCollection = $kbHelper->getKbCategoryCollection($this->storeId);
            foreach ($kbCollection as $kbCategory) {
                $currentKbCategoryUrl = $kbHomeUrl . $slash . $kbCategory->getUrlKey();
                $kbSitemapObject->setData('category' . $kbCategory->getId(), $currentKbCategoryUrl);
                $articleCollection = $kbHelper->getKbArticleCollection($kbCategory->getId(), $this->storeId);
                foreach ($articleCollection as $article) {
                    $kbSitemapObject->setData('article' . $article->getId(), $currentKbCategoryUrl . $slash . $article->getUrlKey());
                }
            }

            foreach ($kbSitemapObject->getData() as $kbUrl) {
                $xml = sprintf(
                    '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                    htmlspecialchars($kbUrl . $suffix), $this->date, $changefreq, $priority
                );

                $this->writeStream($xml);
            }
        }
    }

    /**
     * Generate XML file
     *
     * @return Mage_Sitemap_Model_Sitemap
     */

    public function generateXml()
    {
        set_time_limit(0);
        $this->config = $this->getConfig();
        $this->storeId = $this->getStoreId();
        $this->excludeLinks = $this->config->getExcludeLinks($this->storeId);
        $this->init($this->storeId);
        Mage::app()->setCurrentStore($this->storeId);//need for correct URLs generation


        $this->date    = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $this->baseUrl = Mage::app()->getStore($this->storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

        $this->openSitemap();

        $this->generateCategorySitemap();

        $this->generateProductSitemap();

        $this->generateCmsPagesSitemap();

        $this->generateProductTagSitemap();

        $this->generateAdditionalLinkSitemap();

        $this->generateAWblogSitemap();

        $this->generateAmastyXlandingSitemap();

        $this->generateMagpleasureBlogSitemap();

        $this->generateMirasvitKbSitemap();

        Mage::dispatchEvent('sitemap_generate_action', array('sitemap' => $this));

        $this->closeSitemap();
        $this->generateSitemapIndex();
        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();

        if (isset($_SERVER['REMOTE_ADDR']) &&
            (
                strpos($_SERVER['REMOTE_ADDR'],'192.168.0') !== false
                || in_array($_SERVER['REMOTE_ADDR'], Mage::helper('mstcore/config')->getDeveloperIp())
            )
        ) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('sitemap')->__('Memory used: %s', $this->convert(memory_get_usage(true)))
            );
        }

        Mage::app()->setCurrentStore(0);//revert previous setting back to admin store
        return $this;
    }


    protected function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }


    public function prepareUrl($url) {
        //clear URLs like
        //http://devstore.dva/index.php/htc-touch-diamond.html
        $url = str_replace('/index.php', '', $url);
        //clear URLs like
        //http://devstore2.dva/customer-service?SID=7e5o7l5cmrm6v48m96enb4oqk0
        $p = strpos($url, '?');
        if ($p !== false) {
            $url = substr($url, 0, $p);
        }
        //check for traling slash
        if (Mage::helper('mstcore')->isModuleInstalled('Mirasvit_Seo')) {
            $config = Mage::getSingleton('seo/config');
            $extension = substr(strrchr($url, '.'), 1);
            if (substr($url, -1) != '/' && $config->getTrailingSlash() == Mirasvit_Seo_Model_Config::TRAILING_SLASH) {
                if (!in_array($extension, array('html', 'htm', 'php', 'xml', 'rss'))) {
                   $url.= '/';
                }
            } elseif ($url != '/' && substr($url, -1) == '/' && $config->getTrailingSlash() == Mirasvit_Seo_Model_Config::NO_TRAILING_SLASH) {
               $url = rtrim($url, '/');
            }
        }
        return $url;
    }
    public function generateSitemapIndex() {
        if (!$this->generateSitemapIndex) {
            return;
        }
        if ($this->io->fileExists($this->getSitemapFilename()) && !$this->io->isWriteable($this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $this->io->streamOpen($this->getSitemapFilename());

        $this->io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $this->io->streamWrite('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

        $storeId = $this->getStoreId();
        $date = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $store = Mage::app()->getStore($storeId);
        $baseUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK).ltrim($this->getSitemapPath(), '/');
        for ($i = 1; $i <= $this->sitemapNum; $i++) {

            if (file_exists($this->getPath() . $this->getSitemapFilename($i))) {
                $xml = sprintf('<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>',
                                htmlspecialchars($baseUrl . $this->getSitemapFilename($i)),
                                $date
                );
                $this->io->streamWrite($xml);
                $this->io->streamWrite("\n");
            }
        }

        $this->io->streamWrite('</sitemapindex>');
        $this->io->streamClose();
    }
}

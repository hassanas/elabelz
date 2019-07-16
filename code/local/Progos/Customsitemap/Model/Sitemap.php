<?php

/**
 * This Module will add custom entries as per our need in site map at the end of native work
 * Like Our brand pages
 * generateXml Is the function which write xml file I will add custom functionality at the end at line 100
 *
 * @category       Progos
 * @package        Progos_Customsitemap
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           02-10-2017 14:00
 */
class Progos_Customsitemap_Model_Sitemap extends Mage_Sitemap_Model_Sitemap
{
    const CATEGORY_BRAND = 'category_brand';
    const CMS = 'cms';
    const PRODUCT = 'product';
    const BRAND = 'brand';
    const URLLIMIT = 49000;
    protected $sitemaps;

    public function generateXml()
    {
        /**
         * Generate cms pages sitemap
         */
        // Hassan: add custom links here in the file
        $storeId = $this->getStoreId();
        $date = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $priority = (string)Mage::getStoreConfig('sitemap/page/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/cms_page')->getCollection($storeId);
        /**
         * Generate cms pages home sitemap url
         */
        $i = 0;
        $no = 1;
        $io = $this->createDirectoryPlusFilePointer(self::CMS, $no);
        $xml = sprintf(
            '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
            htmlspecialchars($baseUrl),
            $date,
            $changefreq,
            $priority
        );
        $io->streamWrite($xml);
        $i++;
        /**
         * Generate cms pages home sitemap url
         */
        foreach ($collection as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $io->streamWrite($xml);
            $i++;
            if ($i == self::URLLIMIT) {
                $this->closeAndClosingTag($io);
                $no++;
                $io = $this->createDirectoryPlusFilePointer(self::CMS, $no);
                $i = 0;
            }
        }
        unset($collection);
        $this->closeAndClosingTag($io);

        /**
         * Generate brand pages sitemap
         */

        // add brand urls like: https://www.elabelz.com/en_ae/361-degrees/
        // Hassan: get brand collection
        $i = 0;
        $no = 1;
        $io = $this->createDirectoryPlusFilePointer(self::BRAND, $no);
        $brandCollections = Mage::getModel('shopbybrand/brand')->getCollection()
            ->addFieldToSelect('url_key')
            ->addFieldToSelect('category_ids')
            ->addFieldToSelect('product_ids');
        $brandCollections->getSelect()->where("url_key <> '' and category_ids <> '' and status = 1"); // HASSAN:  Added this condition like that because Mageworks module messed addFieldToFilter function it start joing with other tables
        foreach ($brandCollections as $brandCollection) {
            $xml = sprintf(
                '<url><loc>%s</loc></url>',
                htmlspecialchars($baseUrl . $brandCollection->getUrlKey() . '/')
            );
            $io->streamWrite($xml);
            $i++;
            if ($i == self::URLLIMIT) {
                $this->closeAndClosingTag($io);
                $no++;
                $io = $this->createDirectoryPlusFilePointer(self::BRAND, $no);
                $i = 0;
            }
        }
        $this->closeAndClosingTag($io);
        /**
         * Generate categories sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/category/changefreq', $storeId);
        $priority = (string)Mage::getStoreConfig('sitemap/category/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_category')->getCollection($storeId);
        $categories = new Varien_Object();
        $categories->setItems($collection);
        Mage::dispatchEvent('sitemap_categories_generating_before', array(
            'collection' => $categories
        ));
        $i = 0;
        $no = 1;
        $io = $this->createDirectoryPlusFilePointer(self::CATEGORY_BRAND, $no);
        // Hassan: get brand collection
        foreach ($categories->getItems() as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $io->streamWrite($xml);
            $i++;
            if ($i == self::URLLIMIT) {
                $this->closeAndClosingTag($io);
                $no++;
                $io = $this->createDirectoryPlusFilePointer(self::CATEGORY_BRAND, $no);
                $i = 0;
            }
            // Hassan: Add category/brand like: https://www.elabelz.com/en_ae/women-shoes/361-degrees/
            foreach ($brandCollections as $brandItem) {
                $catArray = explode(',', $brandItem->getCategoryIds());
                if (empty($catArray) || !in_array($item->getId(), $catArray)) {
                    continue;
                }
                //if brand has no products, skip -- additional check
                $brandProductIds = explode(',', $brandItem->getData('product_ids'));
                if (empty($brandProductIds)) {
                    continue;
                }
                //fetch enabled products in current category iteration
                $byBrandProducts = Mage::getModel('catalog/product')->getCollection()
                    ->addCategoryFilter(Mage::getModel('catalog/category')->load($item->getId()))
                    ->addIdFilter($brandProductIds)
                    ->addAttributeToFilter('status', ['eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED]);
                //if there's no enabled product in the category, skip brand from sitemap
                if (count($byBrandProducts->getData()) == 0) {
                    continue;
                }

                $xml = sprintf(
                    '<url><loc>%s</loc></url>',
                    htmlspecialchars($baseUrl . $item->getUrl() . $brandItem->getUrlKey() . '/')
                );
                $io->streamWrite($xml);
                $i++;
                if ($i == self::URLLIMIT) {
                    $this->closeAndClosingTag($io);
                    $no++;
                    $io = $this->createDirectoryPlusFilePointer(self::CATEGORY_BRAND, $no);
                    $i = 0;
                }
            }
            // Hassan: Add category/brand like: https://www.elabelz.com/en_ae/women-shoes/361-degrees/
        }

        unset($collection);
        unset($brandCollection);
        $this->closeAndClosingTag($io);
        /**
         * Generate products sitemap
         */
        $changefreq = (string)Mage::getStoreConfig('sitemap/product/changefreq', $storeId);
        $priority = (string)Mage::getStoreConfig('sitemap/product/priority', $storeId);
        $collection = Mage::getResourceModel('sitemap/catalog_product')->getCollection($storeId);
        $products = new Varien_Object();
        $products->setItems($collection);
        Mage::dispatchEvent('sitemap_products_generating_before', array(
            'collection' => $products
        ));
        $i = 0;
        $no = 1;
        $io = $this->createDirectoryPlusFilePointer(self::PRODUCT, $no);
        foreach ($products->getItems() as $item) {
            $xml = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            $io->streamWrite($xml);
            $i++;
            if ($i == self::URLLIMIT) {
                $this->closeAndClosingTag($io);
                $no++;
                $io = $this->createDirectoryPlusFilePointer(self::PRODUCT, $no);
                $i = 0;
            }
        }
        unset($collection);
        $this->closeAndClosingTag($io);
        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();
        $this->generateIndexFile();
        return $this;
    }

    public function generateIndexFile()
    {
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);

        $date = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $io->open(array('path' => $this->getPath()));
        $io->streamOpen($this->getSitemapFilename());
        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?> <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        foreach ($this->sitemaps as $item) {
            $xml = sprintf(
                '<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>',
                $item, $date
            );
            $io->streamWrite($xml);
        }
        $io->streamWrite('</sitemapindex>');
        $io->streamClose();
    }

    public function createDirectoryPlusFilePointer($type, $no)
    {
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $path = "";
        $typeString = "";
        if ($type == self::CMS) {
            $io->open(array('path' => $this->getPath() . self::CMS));
            $path = $this->getPath() . self::CMS;
            $typeString = self::CMS;
        } else if ($type == self::CATEGORY_BRAND) {
            $io->open(array('path' => $this->getPath() . self::CATEGORY_BRAND));
            $path = $this->getPath() . self::CATEGORY_BRAND;
            $typeString = self::CATEGORY_BRAND;
        } else if ($type == self::PRODUCT) {
            $io->open(array('path' => $this->getPath() . self::PRODUCT));
            $path = $this->getPath() . self::PRODUCT;
            $typeString = self::PRODUCT;
        } else if ($type == self::BRAND) {
            $io->open(array('path' => $this->getPath() . self::BRAND));
            $path = $this->getPath() . self::BRAND;
            $typeString = self::BRAND;
        }
        if ($io->fileExists($this->getSitemapFilename()) && !$io->isWriteable($this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $path));
        }
        $name = explode(".", $this->getSitemapFilename(), 2)[0] . '_' . $no . '.xml';
        $baseUrl = rtrim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),"/ ");;
        $this->sitemaps[] = $baseUrl.$this->getData('sitemap_path').$typeString . DS . $name;
        $io->streamOpen($name);

        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        return $io;
    }

    public function closeAndClosingTag(&$pointer)
    {
        $pointer->streamWrite('</urlset>');
        $pointer->streamClose();
    }
}